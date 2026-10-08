<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiAgentService
{
    public static function isAvailable(): bool
    {
        return (bool) Setting::get('avenque_ai_available', false);
    }

    public static function isEnabled(): bool
    {
        return self::isAvailable() && (bool) Setting::get('avenque_ai_enabled', false);
    }

    public static function hasApiKey(): bool
    {
        return trim((string) Setting::get('gemini_api_key', '')) !== '';
    }

    /** Ready when unlocked + enabled. Gemini key is optional (local mode works without it). */
    public static function isReady(): bool
    {
        return self::isEnabled();
    }

    public static function mode(): string
    {
        if (! self::isReady()) {
            return 'off';
        }

        return self::hasApiKey() ? 'gemini' : 'local';
    }

    public static function status(): array
    {
        return [
            'available' => self::isAvailable(),
            'enabled' => (bool) Setting::get('avenque_ai_enabled', false),
            'has_api_key' => self::hasApiKey(),
            'ready' => self::isReady(),
            'mode' => self::mode(),
            'model' => (string) Setting::get('gemini_model', 'gemini-2.0-flash'),
        ];
    }

    /**
     * @param  list<array{role: string, content: string}>  $history
     * @return array{success: bool, reply?: string, error?: string, tools_used?: list<string>, mode?: string}
     */
    public function chat(string $message, array $history = []): array
    {
        if (! self::isAvailable()) {
            return [
                'success' => false,
                'error' => 'Avenque AI is not available. Ask the software owner to unlock it under Settings → Avenque AI Agent.',
            ];
        }

        if (! Setting::get('avenque_ai_enabled', false)) {
            return [
                'success' => false,
                'error' => 'Enable Avenque AI Agent to start chatting.',
            ];
        }

        // Built-in mode: works without Gemini API key
        if (! self::hasApiKey()) {
            $local = $this->localChat($message);

            return array_merge($local, ['mode' => 'local']);
        }

        $gemini = $this->geminiChat($message, $history);
        if (! ($gemini['success'] ?? false)) {
            // Soft fallback if Gemini fails
            $local = $this->localChat($message);
            if ($local['success'] ?? false) {
                return array_merge($local, [
                    'mode' => 'local',
                    'reply' => ($local['reply'] ?? '')."\n\n_(Gemini unavailable — answered in local mode.)_",
                ]);
            }
        }

        return array_merge($gemini, ['mode' => 'gemini']);
    }

    /**
     * Rule-based answers using live POS tools — no external API.
     *
     * @return array{success: bool, reply?: string, error?: string, tools_used?: list<string>}
     */
    public function localChat(string $message): array
    {
        $q = mb_strtolower(trim($message));
        $toolsUsed = [];

        if ($q === '' || preg_match('/\b(help|what can you|commands|hi|hello|hey|හෙල්ප්|உதவி|ஆயுபோ|හායි)\b/u', $q)) {
            return [
                'success' => true,
                'reply' => "Hi! I’m Avenque Guide — I can teach you QRPOS without Gemini.\n\n"
                    ."Ask me:\n"
                    ."• How to billing / place order\n"
                    ."• How to add product\n"
                    ."• How to add category\n"
                    ."• How to open / close shift\n"
                    ."• How to use waiter / kitchen\n\n"
                    ."Or live data: sales today, top products, low stock, profit.",
                'tools_used' => [],
            ];
        }

        // —— Software how-to guides (no API key) ——
        $guide = $this->softwareGuide($q);
        if ($guide !== null) {
            $payload = [
                'success' => true,
                'reply' => $guide['reply'],
                'tools_used' => ['software_guide'],
            ];
            if (! empty($guide['tour'])) {
                $payload['tour'] = $guide['tour'];
                $payload['tour_url'] = $guide['tour_url'] ?? null;
                $payload['tour_label'] = $guide['tour_label'] ?? 'Take tour';
            }

            return $payload;
        }

        try {
            if (preg_match('/\b(low\s*stock|out of stock|reorder|stock\s*low|குறைந்த\s*ஸ்டாக்|අඩු\s*ස්ටොක්)\b/u', $q)) {
                $toolsUsed[] = 'get_low_stock';
                $data = AiAgentTools::lowStock();

                return ['success' => true, 'reply' => $data['formatted'], 'tools_used' => $toolsUsed];
            }

            if (preg_match('/\b(forecast|predict|next\s*month|prediction|அடுத்த\s*மாதம்|ඊළඟ\s*මාසය)\b/u', $q)) {
                $toolsUsed[] = 'predict_next_month_sales';
                $data = AiAgentTools::salesForecastNextMonth();

                return ['success' => true, 'reply' => $data['formatted'], 'tools_used' => $toolsUsed];
            }

            if (preg_match('/\b(profit|gross|net\s*profit|இலாபம்|ලාභය)\b/u', $q)) {
                $period = $this->detectPeriod($q) ?? 'this_month';
                $toolsUsed[] = 'get_profit_estimate';
                $data = AiAgentTools::profitEstimate($period);

                return ['success' => true, 'reply' => $data['formatted']."\n\n".$data['note'], 'tools_used' => $toolsUsed];
            }

            if (preg_match('/\b(top\s*sell|best\s*sell|popular|top\s*product|விற்பனை\s*பொருள்|හොඳම\s*අයිතම)\b/u', $q)) {
                $period = $this->detectPeriod($q) ?? 'today';
                $toolsUsed[] = 'get_top_selling_products';
                $data = AiAgentTools::topSellingProducts($period);

                return ['success' => true, 'reply' => $data['formatted'], 'tools_used' => $toolsUsed];
            }

            if (preg_match('/\b(customer|purchase\s*history|வாடிக்கையாளர்|පාරිභෝගික)\b/u', $q)) {
                $query = trim(preg_replace('/\b(customer|purchase\s*history|history|for|of|வாடிக்கையாளர்|පාරිභෝගික|විස්තර)\b/ui', '', $message) ?? '');
                $query = trim($query, " \t\n\r\0\x0B:.-");
                if ($query === '') {
                    return [
                        'success' => true,
                        'reply' => 'Tell me the customer name or phone, e.g. “Customer history 0771234567”.',
                        'tools_used' => [],
                    ];
                }
                $toolsUsed[] = 'get_customer_history';
                $data = AiAgentTools::customerHistory($query);

                return ['success' => true, 'reply' => $data['formatted'], 'tools_used' => $toolsUsed];
            }

            if (preg_match('/\b(sales?|revenue|orders?|turnover|விற்பனை|අලෙවි|කොපමණ)\b/u', $q)
                || preg_match('/how many/i', $q)) {
                $period = $this->detectPeriod($q) ?? 'today';
                $toolsUsed[] = 'get_sales_summary';
                $data = AiAgentTools::salesSummary($period);

                return ['success' => true, 'reply' => $data['formatted'], 'tools_used' => $toolsUsed];
            }

            if (preg_match('/\b(email\s*(sales\s*)?report|send\s*report)\b/u', $q)) {
                $period = $this->detectPeriod($q) ?? 'today';
                $toolsUsed[] = 'email_sales_report';
                $data = AiAgentTools::emailReport($period);

                return ['success' => true, 'reply' => $data['message'] ?? 'Done.', 'tools_used' => $toolsUsed];
            }
        } catch (\Throwable $e) {
            return ['success' => false, 'error' => 'Could not answer: '.$e->getMessage()];
        }

        return [
            'success' => true,
            'reply' => "I’m not sure yet. Try:\n"
                ."• How to billing\n"
                ."• How to add product\n"
                ."• Sales today / low stock / profit this month\n\n"
                .'Or open User Guide in the sidebar for the full manual.',
            'tools_used' => [],
        ];
    }

    /**
     * Built-in QRPOS usage guides — works without Gemini.
     *
     * @return array{reply: string, tour?: string, tour_url?: string, tour_label?: string}|null
     */
    private function softwareGuide(string $q): ?array
    {
        if (preg_match('/\b(bill|billing|place\s*order|pos\s*order|pay\s*now|checkout|tour|எப்படி\s*பில்|බිල්පත්|ගෙවීම)\b/u', $q)
            || preg_match('/how\s+to\s+(do\s+)?(bill|order|pos)/u', $q)) {
            return [
                'reply' => "How to billing (POS)\n\n"
                    ."1. Open POS Billing.\n"
                    ."2. Open Shift (opening cash).\n"
                    ."3. Choose order type (Dine-in / Takeaway / Delivery).\n"
                    ."4. Select table if dine-in.\n"
                    ."5. Tap products → Place Order (KOT/BOT).\n"
                    ."6. Pay Now when the guest pays.\n\n"
                    .'Want me to walk you through it live? Tap Take billing tour.',
                'tour' => 'billing',
                'tour_url' => route('pos.index', ['tour' => 'billing']),
                'tour_label' => 'Take billing tour',
            ];
        }

        if (preg_match('/\b(add\s*product|create\s*product|new\s*product|product\s*add|பொருள்\s*சேர்|නිෂ්පාදන\s*එකතු)\b/u', $q)
            || preg_match('/how\s+to\s+add\s+product/u', $q)) {
            return [
                'reply' => "How to add a product\n\n"
                    ."1. Go to Products → + Add Product.\n"
                    ."2. Name, Code, Category, Selling price.\n"
                    ."3. Type: KOT / BOT / Direct.\n"
                    ."4. Optional image, cost, tax, stock.\n"
                    ."5. Show in POS / QR → Save.\n\n"
                    .'Tap Take product tour to highlight the buttons.',
                'tour' => 'product',
                'tour_url' => route('products.index', ['tour' => 'product']),
                'tour_label' => 'Take product tour',
            ];
        }

        if (preg_match('/\b(add\s*categor|create\s*categor|new\s*categor|வகை|කාණ්ඩ)\b/u', $q)) {
            return [
                'reply' => "How to add a category\n\n"
                    ."1. Sidebar → Categories → + Add.\n"
                    ."2. Name it (e.g. Rice & Curry, Beverages).\n"
                    ."3. Choose type if prompted (KOT / BOT / Direct).\n"
                    ."4. Save, then add Products under that category.\n"
                    .'5. Kitchens → Edit kitchen → Assign Categories so tickets print correctly.',
            ];
        }

        if (preg_match('/\b(shift|open\s*register|close\s*shift|day\s*end|ஷி프트|ශිෆ්ට්)\b/u', $q)
            && preg_match('/\b(how|open|close|start|end|guide|எப்படி|කොහොම)\b/u', $q)) {
            return [
                'reply' => "How to open / close shift\n\n"
                    ."Open:\n"
                    ."• POS → enter Opening cash → Start Shift.\n\n"
                    ."Close:\n"
                    ."• POS → Cash drawer / Close Shift → count cash → enter Closing cash → Close.\n"
                    ."• After cutoff time, you’ll be asked End the day? — Yes prints Day End for all shifts.\n\n"
                    .'Reports → Shifts / Day Ends for history.',
            ];
        }

        if (preg_match('/\b(waiter|kitchen|kds|kot|bot|வேட்டர்|කුස්සිය)\b/u', $q)
            && preg_match('/\b(how|use|guide|எப்படி|කොහොම)\b/u', $q)) {
            return [
                'reply' => "Waiter & Kitchen\n\n"
                    ."Waiter Panel: take table orders, send to kitchen, mark served.\n"
                    ."Kitchen Display: Accept → Preparing → Ready (if KOT Confirmation ON).\n"
                    ."Print-only kitchens: leave confirmation OFF — tickets print to kitchen IP.\n\n"
                    .'Owner settings control Kitchen Display, sounds, and QR menu.',
            ];
        }

        if (preg_match('/\b(guide|tutorial|how\s+to\s+use|software\s+guide|user\s+guide|பயன்படுத்து|භාවිතා)\b/u', $q)) {
            return [
                'reply' => "QRPOS quick start\n\n"
                    ."1. Settings → Business — name, currency, tax.\n"
                    ."2. Categories → Products — build your menu.\n"
                    ."3. Floors & Tables — dine-in layout + QR if needed.\n"
                    ."4. POS — open shift → bill → pay.\n"
                    ."5. Reports — check sales.\n\n"
                    ."Ask “how to billing” for a live POS tour, or “how to add product”.\n"
                    .'Full manual: sidebar → User Guide.',
                'tour' => 'billing',
                'tour_url' => route('pos.index', ['tour' => 'billing']),
                'tour_label' => 'Take billing tour',
            ];
        }

        return null;
    }

    private function detectPeriod(string $q): ?string
    {
        if (preg_match('/\b(yesterday|நேற்று|ඊයේ)\b/u', $q)) {
            return 'yesterday';
        }
        if (preg_match('/\b(this\s*week|வாரம்|සතිය)\b/u', $q)) {
            return 'this_week';
        }
        if (preg_match('/\b(last\s*month|கடந்த\s*மாதம்|පසුගිය\s*මාසය)\b/u', $q)) {
            return 'last_month';
        }
        if (preg_match('/\b(this\s*month|month|மாதம்|මාසය)\b/u', $q)) {
            return 'this_month';
        }
        if (preg_match('/\b(today|இன்று|අද)\b/u', $q)) {
            return 'today';
        }

        return null;
    }

    /**
     * @param  list<array{role: string, content: string}>  $history
     * @return array{success: bool, reply?: string, error?: string, tools_used?: list<string>}
     */
    private function geminiChat(string $message, array $history = []): array
    {
        $apiKey = trim((string) Setting::get('gemini_api_key', ''));
        $model = trim((string) Setting::get('gemini_model', 'gemini-2.0-flash')) ?: 'gemini-2.0-flash';
        $company = (string) Setting::get('company_name', 'Restaurant');
        $currency = (string) Setting::get('currency_symbol', 'LKR');

        $contents = [];
        foreach (array_slice($history, -8) as $turn) {
            $role = ($turn['role'] ?? '') === 'assistant' ? 'model' : 'user';
            $text = trim((string) ($turn['content'] ?? ''));
            if ($text === '') {
                continue;
            }
            $contents[] = [
                'role' => $role,
                'parts' => [['text' => $text]],
            ];
        }
        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => $message]],
        ];

        $payload = [
            'systemInstruction' => [
                'parts' => [[
                    'text' => "You are Avenque AI Agent for {$company} POS (QRPOS by Avenque). "
                        ."Currency is {$currency}. Answer clearly and concisely in the user's language "
                        .'(English, Tamil, or Sinhala). Use tools for live POS data — do not invent numbers. '
                        .'You can look up sales, top products, low stock, profit estimates, customer history, '
                        .'simple next-month forecasts, email a sales report, and send a WhatsApp bill when configured. '
                        .'Quotation creation, full invoice generation from chat, and voice input are coming soon — say so briefly if asked. '
                        .'When a tool returns formatted text, summarize it for the user.',
                ]],
            ],
            'contents' => $contents,
            'tools' => [['functionDeclarations' => $this->toolDeclarations()]],
            'generationConfig' => [
                'temperature' => 0.3,
                'maxOutputTokens' => 1024,
            ],
        ];

        $url = 'https://generativelanguage.googleapis.com/v1beta/models/'
            .rawurlencode($model).':generateContent?key='.urlencode($apiKey);

        $toolsUsed = [];

        try {
            for ($round = 0; $round < 4; $round++) {
                $response = Http::timeout(45)
                    ->acceptJson()
                    ->asJson()
                    ->post($url, $payload);

                if (! $response->successful()) {
                    $err = $response->json('error.message') ?: $response->body();
                    Log::warning('Gemini AI error', ['status' => $response->status(), 'body' => $err]);

                    return [
                        'success' => false,
                        'error' => 'Gemini API error: '.(is_string($err) ? $err : json_encode($err)),
                    ];
                }

                $candidate = $response->json('candidates.0');
                $parts = $candidate['content']['parts'] ?? [];
                $functionCalls = [];
                $textBits = [];

                foreach ($parts as $part) {
                    if (isset($part['functionCall'])) {
                        $functionCalls[] = $part['functionCall'];
                    }
                    if (isset($part['text'])) {
                        $textBits[] = $part['text'];
                    }
                }

                if ($functionCalls === []) {
                    $reply = trim(implode("\n", $textBits));
                    if ($reply === '') {
                        $reply = 'I could not generate a reply. Please try again.';
                    }

                    return [
                        'success' => true,
                        'reply' => $reply,
                        'tools_used' => $toolsUsed,
                    ];
                }

                $payload['contents'][] = [
                    'role' => 'model',
                    'parts' => array_map(fn ($fc) => ['functionCall' => $fc], $functionCalls),
                ];

                $fnResponseParts = [];
                foreach ($functionCalls as $fc) {
                    $name = (string) ($fc['name'] ?? '');
                    $args = is_array($fc['args'] ?? null) ? $fc['args'] : [];
                    $toolsUsed[] = $name;
                    $result = $this->executeTool($name, $args);
                    $fnResponseParts[] = [
                        'functionResponse' => [
                            'name' => $name,
                            'response' => ['result' => $result],
                        ],
                    ];
                }

                $payload['contents'][] = [
                    'role' => 'user',
                    'parts' => $fnResponseParts,
                ];
            }

            return [
                'success' => false,
                'error' => 'Too many tool rounds. Please ask a simpler question.',
                'tools_used' => $toolsUsed,
            ];
        } catch (\Throwable $e) {
            Log::error('Avenque AI chat failed: '.$e->getMessage());

            return [
                'success' => false,
                'error' => 'AI request failed: '.$e->getMessage(),
            ];
        }
    }

    /** @return list<array<string, mixed>> */
    private function toolDeclarations(): array
    {
        return [
            [
                'name' => 'get_sales_summary',
                'description' => 'Get sales totals and order counts for a period (today, yesterday, this_week, this_month, last_month).',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'period' => [
                            'type' => 'string',
                            'description' => 'today | yesterday | this_week | this_month | last_month',
                        ],
                    ],
                ],
            ],
            [
                'name' => 'get_top_selling_products',
                'description' => 'List best-selling products by quantity for a period.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'period' => ['type' => 'string'],
                        'limit' => ['type' => 'integer'],
                    ],
                ],
            ],
            [
                'name' => 'get_low_stock',
                'description' => 'List ingredients and tracked products that are low or out of stock.',
                'parameters' => ['type' => 'object', 'properties' => (object) []],
            ],
            [
                'name' => 'get_profit_estimate',
                'description' => 'Estimate gross/net profit from sales, product cost prices, and expenses.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'period' => ['type' => 'string'],
                    ],
                ],
            ],
            [
                'name' => 'get_customer_history',
                'description' => 'Find a customer by name or phone and show purchase history.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'query' => ['type' => 'string', 'description' => 'Customer name or phone'],
                    ],
                    'required' => ['query'],
                ],
            ],
            [
                'name' => 'predict_next_month_sales',
                'description' => 'Simple forecast of next month sales from the last 3 months.',
                'parameters' => ['type' => 'object', 'properties' => (object) []],
            ],
            [
                'name' => 'email_sales_report',
                'description' => 'Email a short sales summary report via configured SMTP.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'period' => ['type' => 'string'],
                        'to_email' => ['type' => 'string'],
                    ],
                ],
            ],
            [
                'name' => 'send_whatsapp_bill',
                'description' => 'Send an order bill summary via WhatsApp (Meta Cloud API).',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'order_number' => ['type' => 'string'],
                        'phone' => ['type' => 'string'],
                    ],
                    'required' => ['order_number'],
                ],
            ],
        ];
    }

    private function executeTool(string $name, array $args): array
    {
        try {
            return match ($name) {
                'get_sales_summary' => AiAgentTools::salesSummary((string) ($args['period'] ?? 'today')),
                'get_top_selling_products' => AiAgentTools::topSellingProducts(
                    (string) ($args['period'] ?? 'today'),
                    (int) ($args['limit'] ?? 10)
                ),
                'get_low_stock' => AiAgentTools::lowStock(),
                'get_profit_estimate' => AiAgentTools::profitEstimate((string) ($args['period'] ?? 'this_month')),
                'get_customer_history' => AiAgentTools::customerHistory((string) ($args['query'] ?? '')),
                'predict_next_month_sales' => AiAgentTools::salesForecastNextMonth(),
                'email_sales_report' => AiAgentTools::emailReport(
                    (string) ($args['period'] ?? 'today'),
                    isset($args['to_email']) ? (string) $args['to_email'] : null
                ),
                'send_whatsapp_bill' => AiAgentTools::sendWhatsAppBill(
                    (string) ($args['order_number'] ?? ''),
                    isset($args['phone']) ? (string) $args['phone'] : null
                ),
                default => ['error' => 'Unknown tool: '.$name],
            };
        } catch (\Throwable $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
