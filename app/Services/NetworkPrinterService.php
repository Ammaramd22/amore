<?php

namespace App\Services;

use App\Models\KitchenOrder;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;

class NetworkPrinterService
{
    /**
     * Send a KOT/BOT to a kitchen network printer (ESC/POS over TCP 9100).
     * USB printers should use Local Print Bridge + escPosKitchenPayload().
     */
    public function printKitchenOrder(KitchenOrder $kitchenOrder): array
    {
        $kitchenOrder->loadMissing(['order.table', 'order.waiter', 'order.cashier', 'items', 'kitchen']);

        $kitchen = $kitchenOrder->kitchen;
        $resolved = \App\Models\Kitchen::resolvePrinterForKitchenOrder($kitchen, (string) $kitchenOrder->type);
        $ip = $resolved['printer_ip'];
        $port = $resolved['printer_port'];
        $name = $resolved['printer_name'];
        $payload = $this->buildEscPosTicket($kitchenOrder);

        $base = [
            'payload_base64' => base64_encode($payload),
            'printer_ip' => $ip,
            'printer_port' => $port,
            'printer_name' => $name,
            'print_mode' => 'direct',
            'needs_local_bridge' => true,
        ];

        if (! ($resolved['has_printer'] ?? false)) {
            $isBar = $kitchenOrder->type === 'bar';

            return array_merge($base, [
                'success' => false,
                'message' => $isBar
                    ? 'No bar/BOT printer configured — not sending to kitchen KOT'
                    : 'No kitchen printer configured',
            ]);
        }

        if ($ip === '') {
            return array_merge($base, [
                'success' => false,
                'message' => 'No printer IP — use Local Print Bridge with Windows printer name (USB)',
            ]);
        }

        try {
            $socket = @fsockopen($ip, $port, $errno, $errstr, 4);
            if (! $socket) {
                return array_merge($base, [
                    'success' => false,
                    'message' => "Kitchen printer is offline or unreachable ({$ip}:{$port})",
                    'errno' => $errno,
                ]);
            }

            stream_set_timeout($socket, 4);
            fwrite($socket, $payload);
            fclose($socket);

            return array_merge($base, [
                'success' => true,
                'message' => 'KOT sent to '.$name.' ('.$ip.':'.$port.')',
                'needs_local_bridge' => false,
            ]);
        } catch (\Throwable $e) {
            Log::error('Network print failed', [
                'ip' => $ip,
                'kitchen_order_id' => $kitchenOrder->id,
                'error' => $e->getMessage(),
            ]);

            return array_merge($base, [
                'success' => false,
                'message' => "Kitchen printer is offline or unreachable ({$ip}:{$port})",
            ]);
        }
    }

    /** Raw ESC/POS bytes for a kitchen ticket (Print Bridge). */
    public function escPosKitchenPayload(KitchenOrder $kitchenOrder): string
    {
        $kitchenOrder->loadMissing(['order.table', 'order.waiter', 'order.cashier', 'items', 'kitchen']);

        return $this->buildEscPosTicket($kitchenOrder);
    }

    /**
     * Pulse cash drawer via receipt printer (RJ11 drawer port + ESC/POS over TCP 9100).
     * Requires a network-reachable printer (Ethernet/WiFi). USB-only printers need a print bridge.
     */
    public function openCashDrawer(?string $ip = null, ?int $port = null, ?int $pin = null): array
    {
        $ip = trim($ip ?: (string) Setting::get('receipt_printer_ip', ''));
        $port = $port ?: (int) Setting::get('receipt_printer_port', 9100);
        $pin = $pin ?? (int) Setting::get('cash_drawer_pin', 0);
        if ($port < 1 || $port > 65535) {
            $port = 9100;
        }

        $payload = $this->cashDrawerKickPayload($pin);
        $base = [
            'payload_base64' => base64_encode($payload),
            'printer_name' => trim((string) Setting::get('receipt_printer_name', 'XP-80C')) ?: 'XP-80C',
            'needs_local_bridge' => true,
        ];

        if ($ip === '') {
            return array_merge($base, [
                'success' => false,
                'message' => 'Open drawer via Local Print Bridge (USB) — keep Start-Print-Bridge.bat running',
            ]);
        }

        try {
            $socket = @fsockopen($ip, $port, $errno, $errstr, 4);
            if (! $socket) {
                return array_merge($base, [
                    'success' => false,
                    'message' => "Cannot reach printer {$ip}:{$port} — use Print Bridge on POS PC",
                    'errno' => $errno,
                ]);
            }

            stream_set_timeout($socket, 4);
            fwrite($socket, $payload);
            fclose($socket);

            return array_merge($base, [
                'success' => true,
                'needs_local_bridge' => false,
                'message' => "Cash drawer kick sent to {$ip}:{$port}",
                'printer_ip' => $ip,
            ]);
        } catch (\Throwable $e) {
            Log::error('Cash drawer open failed', [
                'ip' => $ip,
                'port' => $port,
                'error' => $e->getMessage(),
            ]);

            return array_merge($base, [
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }

    /** ESC/POS cash drawer — one solid pulse (pin 2 or 5). */
    public function cashDrawerKickPayload(?int $pin = null, bool $withInit = true): string
    {
        $pin = $pin ?? (int) Setting::get('cash_drawer_pin', 0);
        $m = $pin === 1 ? "\x01" : "\x00";

        // One pulse with long on/off so XP-80C / RJ11 drawers actually fire (still one beep)
        $pulse = "\x1B\x70".$m."\xFA\xFA";

        if (! $withInit) {
            return $pulse;
        }

        // Tiny feed keeps Windows RAW spooler from dropping a kick-only job
        return "\x1B\x40"."\x1B\x61\x01"."\x0A".$pulse;
    }

    private function buildEscPosTicket(KitchenOrder $kot): string
    {
        $order = $kot->order;
        $isBot = $kot->type === 'bar';
        // Font A on XP-80 80mm ≈ 42–48 cols; 42 keeps separators inside printable width
        $width = 42;
        $orderTypeLabel = \App\Support\KotPrint::orderTypeLabel($order?->order_type);
        $title = ($isBot ? 'BOT' : 'KOT').' ('.$orderTypeLabel.')';
        $sep = str_repeat('-', $width);
        $waiter = \App\Support\KotPrint::waiterLabel($order);
        $table = \App\Support\KotPrint::tableLabel($order);
        $hasTakeAway = \App\Support\KotPrint::hasTakeAwayNote($order, $kot);

        // Full printer reset + normal Font A, LTR, no rotate/double-size leftover
        $out = '';
        $out .= "\x1B\x40";       // ESC @ init
        $out .= "\x1B\x4D\x00";   // Font A
        $out .= "\x1D\x21\x00";   // normal width/height
        $out .= "\x1B\x56\x00";   // cancel 90° rotate
        $out .= "\x1B\x7B\x00";   // cancel upside-down
        $out .= "\x1B\x45\x00";   // bold off
        $out .= "\x1B\x61\x00";   // left align
        $out .= "\x1B\x32";       // default line spacing

        // Title: center + bold + double height (same as preview kot-title)
        $out .= "\x1B\x61\x01";
        $out .= "\x1B\x45\x01";
        $out .= "\x1D\x21\x10";
        $out .= $this->safeEscPosText($title)."\n";
        if ($kot->is_reorder) {
            $out .= $this->safeEscPosText('** REORDER **')."\n";
        }
        $out .= "\x1D\x21\x00";
        $out .= "\x1B\x45\x00";
        $out .= "\x1B\x61\x00";

        $out .= $sep."\n";

        // Header block: bold + double height (Invoice / Table match preview)
        $out .= "\x1B\x45\x01";
        $out .= "\x1D\x21\x10";
        $out .= $this->safeEscPosText((string) $kot->kot_number)."\n";
        $out .= 'Invoice: '.$this->safeEscPosText((string) ($order?->order_number ?? '-'))."\n";
        $out .= 'Table: '.$this->safeEscPosText($table)."\n";
        $out .= 'Waiter: '.$this->safeEscPosText($waiter)."\n";
        $out .= 'Time: '.Setting::formatDateTime($kot->created_at, 'H:i')."\n";
        $out .= "\x1D\x21\x00";
        $out .= "\x1B\x45\x00";

        if ($hasTakeAway) {
            $out .= "\x1B\x61\x01";
            $out .= "\x1B\x45\x01";
            $out .= "\x1D\x21\x10";
            $out .= $this->safeEscPosText('*** TAKE AWAY ***')."\n";
            $out .= "\x1D\x21\x00";
            $out .= "\x1B\x45\x00";
            $out .= "\x1B\x61\x00";
        }

        $out .= $sep."\n";

        // Items: left, bold, DOUBLE HEIGHT (same as preview kot-item)
        $out .= "\x1B\x45\x01";
        $out .= "\x1D\x21\x10";
        foreach ($kot->items as $item) {
            $qtyLabel = rtrim(rtrim(number_format((float) $item->quantity, 3, '.', ''), '0'), '.');
            $itemLine = $qtyLabel.'x '.(string) $item->product_name;
            $wrapped = $this->wrapLines($itemLine, $width);
            if ($wrapped === []) {
                $wrapped = [$this->safeEscPosText($itemLine)];
            }
            foreach ($wrapped as $i => $wLine) {
                $out .= ($i === 0 ? '' : '   ').$wLine."\n";
            }
            if ($item->special_instructions) {
                foreach ($this->wrapLines('NOTE: '.(string) $item->special_instructions, $width - 2) as $noteLine) {
                    $out .= '  '.$noteLine."\n";
                }
            }
        }
        $out .= "\x1D\x21\x00";
        $out .= "\x1B\x45\x00";
        $out .= $sep."\n";

        if ($order?->order_notes) {
            $out .= "\x1B\x45\x01";
            $out .= "\x1D\x21\x10";
            foreach ($this->wrapLines('NOTES: '.(string) $order->order_notes, $width) as $noteLine) {
                $out .= $noteLine."\n";
            }
            $out .= "\x1D\x21\x00";
            $out .= "\x1B\x45\x00";
            $out .= $sep."\n";
        }

        // Footer: centered from settings; never print site URL
        $softwareFooter = trim((string) Setting::get('invoice_software_footer', ''));
        if ($softwareFooter === '') {
            $softwareFooter = 'QRPOS By Avenque (Pvt) Ltd | 076 822 2201';
        }
        $softwareFooter = preg_replace('#https?://\S+#i', '', $softwareFooter) ?? $softwareFooter;
        $softwareFooter = trim(preg_replace('/\s+/', ' ', $softwareFooter) ?? $softwareFooter);

        $out .= $sep."\n";
        $out .= "\x1B\x61\x01";
        if ($softwareFooter !== '') {
            foreach (preg_split('/\r\n|\r|\n/', $softwareFooter) as $sfLine) {
                $sfLine = trim((string) $sfLine);
                if ($sfLine === '') {
                    continue;
                }
                foreach ($this->wrapLines($sfLine, $width) as $wrapped) {
                    $out .= $wrapped."\n";
                }
            }
        }
        $out .= "\x1B\x61\x00";

        // Small feed + full cut (same as receipt cut sequence)
        $out .= "\n"."\x1B\x64\x03"."\x1D\x56\x00";

        return $out;
    }

    private function center(string $text, int $width = 48): string
    {
        $text = trim($text);
        if (strlen($text) >= $width) {
            return $text;
        }
        $pad = (int) floor(($width - strlen($text)) / 2);

        return str_repeat(' ', $pad).$text;
    }

    private function boldLine(string $text): string
    {
        // ESC E 1 = bold on, ESC E 0 = bold off
        return "\x1B\x45\x01".$text."\x1B\x45\x00";
    }

    /**
     * Send an 80mm sales receipt to the counter printer (ESC/POS TCP 9100).
     * No browser preview — direct to XP-80C / network thermal.
     *
     * @return array{success:bool,message:string,printer_ip?:string,printer_name?:string}
     */
    public function printReceipt(\App\Models\Order $order): array
    {
        $order->loadMissing([
            'items', 'payments.creator', 'cashier', 'waiter', 'table',
            'customer', 'deliveryPartner', 'branch',
        ]);

        $ip = trim((string) Setting::get('receipt_printer_ip', ''));
        $port = (int) Setting::get('receipt_printer_port', 9100);
        $name = trim((string) Setting::get('receipt_printer_name', 'XP-80C')) ?: 'XP-80C';

        if ($ip === '') {
            return [
                'success' => false,
                'message' => 'Set Receipt Printer IP in Settings → POS (for '.$name.')',
            ];
        }

        if ($port < 1 || $port > 65535) {
            $port = 9100;
        }

        $payload = $this->buildEscPosReceipt($order);

        try {
            $socket = @fsockopen($ip, $port, $errno, $errstr, 4);
            if (! $socket) {
                $hint = ' Run Local Print Bridge on the Windows POS PC (Settings → POS), or host QRPOS on that same PC.';
                if (stripos((string) $errstr, 'refused') !== false || in_array((int) $errno, [111, 10061], true)) {
                    $hint = ' Connection refused: this web server is not on the printer LAN. Start Local Print Bridge on the POS PC (Ethernet XP-80C).';
                }

                return [
                    'success' => false,
                    'message' => "Cannot reach {$name} at {$ip}:{$port} — {$errstr}.".$hint,
                    'errno' => $errno,
                    'needs_local_bridge' => true,
                ];
            }

            stream_set_timeout($socket, 4);
            fwrite($socket, $payload);
            fclose($socket);

            return [
                'success' => true,
                'message' => "Receipt sent to {$name} ({$ip})",
                'printer_ip' => $ip,
                'printer_name' => $name,
            ];
        } catch (\Throwable $e) {
            Log::error('Receipt network print failed', [
                'ip' => $ip,
                'order_id' => $order->id,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'message' => $e->getMessage(), 'needs_local_bridge' => true];
        }
    }

    /** Raw ESC/POS for browser → Local Print Bridge on the POS PC. */
    public function escPosReceiptPayload(\App\Models\Order $order): string
    {
        $order->loadMissing([
            'items', 'payments.creator', 'cashier', 'waiter', 'table',
            'customer', 'deliveryPartner', 'branch',
        ]);

        return $this->buildEscPosReceipt($order);
    }

    /** Raw ESC/POS for cash in / cash out slip (Local Print Bridge). */
    public function escPosCashMovementPayload(\App\Models\CashRegisterMovement $movement): string
    {
        $movement->loadMissing(['user', 'category', 'register']);

        return $this->buildEscPosCashMovement($movement);
    }

    public function printCashMovement(\App\Models\CashRegisterMovement $movement): array
    {
        $ip = trim((string) Setting::get('receipt_printer_ip', ''));
        $port = (int) Setting::get('receipt_printer_port', 9100);
        $name = trim((string) Setting::get('receipt_printer_name', 'XP-80C')) ?: 'XP-80C';

        if ($ip === '') {
            return [
                'success' => false,
                'message' => 'Set Receipt Printer IP in Settings → POS (for '.$name.')',
                'needs_local_bridge' => true,
            ];
        }

        if ($port < 1 || $port > 65535) {
            $port = 9100;
        }

        $payload = $this->escPosCashMovementPayload($movement);

        try {
            $socket = @fsockopen($ip, $port, $errno, $errstr, 4);
            if (! $socket) {
                return [
                    'success' => false,
                    'message' => "Cannot reach {$name} at {$ip}:{$port} — {$errstr}",
                    'needs_local_bridge' => true,
                ];
            }

            stream_set_timeout($socket, 4);
            fwrite($socket, $payload);
            fclose($socket);

            return [
                'success' => true,
                'message' => 'Cash slip printed on '.$name,
                'printer_ip' => $ip,
                'printer_name' => $name,
            ];
        } catch (\Throwable $e) {
            Log::error('Cash movement network print failed', [
                'movement_id' => $movement->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'needs_local_bridge' => true,
            ];
        }
    }

    private function buildEscPosCashMovement(\App\Models\CashRegisterMovement $movement): string
    {
        // Software-owner company (Settings → Business), not branch invoice overrides
        $company = trim((string) Setting::get('company_name', 'QRPOS'));
        $address = trim((string) Setting::get('company_address', ''));
        $phone = trim((string) Setting::get('company_phone', ''));
        $currency = (string) Setting::get('currency_symbol', 'LKR');
        $isOut = $movement->type === 'out';
        $width = 48;

        $out = "\x1B\x40"."\x1B\x4D\x00"."\x1B\x47\x01"."\x1B\x45\x01";
        $out .= "\x1B\x61\x01";

        $nameRaster = $this->escPosRasterText($company, 384, 36);
        if ($nameRaster !== '') {
            $out .= $nameRaster;
        } else {
            $out .= "\x1D\x21\x11".$this->safeEscPosText($company)."\n"."\x1D\x21\x00";
        }

        foreach ($this->wrapLines($address, $width) as $addrLine) {
            $out .= $this->safeEscPosText($addrLine)."\n";
        }
        if ($phone !== '') {
            $out .= $this->safeEscPosText('Tel: '.$phone)."\n";
        }

        $out .= str_repeat('=', $width)."\n";
        $out .= "\x1D\x21\x11".$this->safeEscPosText($isOut ? 'CASH OUT' : 'CASH IN')."\n"."\x1D\x21\x00";
        $out .= $this->safeEscPosText('Slip #'.$movement->id)."\n";
        $out .= str_repeat('-', $width)."\n"."\x1B\x61\x00";

        $when = Setting::formatDateTime($movement->created_at, 'Y-m-d H:i');
        $out .= $this->pair('Date', $this->safeEscPosText((string) $when), $width)."\n";
        $out .= $this->pair('Cashier', $this->safeEscPosText((string) ($movement->user?->name ?? 'N/A')), $width)."\n";
        $out .= $this->pair('Register', '#'.$movement->cash_register_id, $width)."\n";

        if ($isOut) {
            $out .= $this->pair('Category', $this->safeEscPosText((string) ($movement->category?->name ?? '-')), $width)."\n";
        }

        $reason = trim((string) ($movement->reason ?? ''));
        $out .= $this->pair('Reason', $this->safeEscPosText($reason !== '' ? $reason : '-'), $width)."\n";
        $out .= str_repeat('-', $width)."\n";

        $amountLabel = ($isOut ? '-' : '+').$currency.' '.number_format((float) $movement->amount, 2);
        $out .= "\x1D\x21\x11";
        $out .= $this->pair($isOut ? 'Cash OUT' : 'Cash IN', $this->safeEscPosText($amountLabel), $width)."\n";
        $out .= "\x1D\x21\x00";
        $out .= str_repeat('=', $width)."\n"."\x1B\x61\x01";
        $out .= $this->safeEscPosText($isOut ? 'Cash removed from drawer' : 'Cash added to drawer')."\n";
        $out .= $this->safeEscPosText('Thank you')."\n\n\n";
        $out .= "\x1D\x56\x00";

        return $out;
    }

    /** Raw ESC/POS for shift / cashier close slip (Local Print Bridge). */
    public function escPosShiftReportPayload(\App\Models\CashRegister $register, bool $cashierLimited = false): string
    {
        $register->loadMissing('user');

        return $this->buildEscPosShiftReport($register, $cashierLimited);
    }

    public function printShiftReport(\App\Models\CashRegister $register, bool $cashierLimited = false): array
    {
        $ip = trim((string) Setting::get('receipt_printer_ip', ''));
        $port = (int) Setting::get('receipt_printer_port', 9100);
        $name = trim((string) Setting::get('receipt_printer_name', 'XP-80C')) ?: 'XP-80C';

        if ($ip === '') {
            return [
                'success' => false,
                'message' => 'Set Receipt Printer IP in Settings → POS (for '.$name.')',
                'needs_local_bridge' => true,
            ];
        }

        if ($port < 1 || $port > 65535) {
            $port = 9100;
        }

        $payload = $this->escPosShiftReportPayload($register, $cashierLimited);

        try {
            $socket = @fsockopen($ip, $port, $errno, $errstr, 4);
            if (! $socket) {
                return [
                    'success' => false,
                    'message' => "Cannot reach {$name} at {$ip}:{$port} — {$errstr}",
                    'needs_local_bridge' => true,
                ];
            }

            stream_set_timeout($socket, 4);
            fwrite($socket, $payload);
            fclose($socket);

            return [
                'success' => true,
                'message' => 'Shift slip printed on '.$name,
                'printer_ip' => $ip,
                'printer_name' => $name,
            ];
        } catch (\Throwable $e) {
            Log::error('Shift report network print failed', [
                'register_id' => $register->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => $e->getMessage(),
                'needs_local_bridge' => true,
            ];
        }
    }

    private function buildEscPosShiftReport(\App\Models\CashRegister $register, bool $cashierLimited = false): string
    {
        $company = trim((string) Setting::get('company_name', 'QRPOS'));
        $address = trim((string) Setting::get('company_address', ''));
        $phone = trim((string) Setting::get('company_phone', ''));
        $currency = (string) Setting::get('currency_symbol', 'LKR');
        $width = 48;
        $fmt = fn ($n) => $currency.' '.number_format((float) $n, 2);

        $out = "\x1B\x40"."\x1B\x4D\x00"."\x1B\x47\x01"."\x1B\x45\x01";
        $out .= "\x1B\x61\x01";

        $nameRaster = $this->escPosRasterText($company, 384, 36);
        if ($nameRaster !== '') {
            $out .= $nameRaster;
        } else {
            $out .= "\x1D\x21\x11".$this->safeEscPosText($company)."\n"."\x1D\x21\x00";
        }

        foreach ($this->wrapLines($address, $width) as $addrLine) {
            $out .= $this->safeEscPosText($addrLine)."\n";
        }
        if ($phone !== '') {
            $out .= $this->safeEscPosText('Tel: '.$phone)."\n";
        }

        $title = $cashierLimited ? 'CASHIER SHIFT CLOSE' : 'SHIFT REPORT / DAY END REPORT';
        $out .= str_repeat('=', $width)."\n";
        $out .= "\x1D\x21\x11".$this->safeEscPosText($title)."\n"."\x1D\x21\x00";
        $out .= $this->safeEscPosText('#'.$register->id)."\n";
        $out .= str_repeat('-', $width)."\n"."\x1B\x61\x00";

        $opened = Setting::formatDateTime($register->opened_at, 'Y-m-d H:i');
        $closed = $register->closed_at
            ? Setting::formatDateTime($register->closed_at, 'Y-m-d H:i')
            : 'OPEN';

        $out .= $this->pair('Cashier', $this->safeEscPosText((string) ($register->user?->name ?? 'N/A')), $width)."\n";
        $out .= $this->pair('Opened', $this->safeEscPosText((string) $opened), $width)."\n";
        $out .= $this->pair('Closed', $this->safeEscPosText((string) $closed), $width)."\n";

        if ($cashierLimited) {
            $out .= str_repeat('-', $width)."\n";
            $out .= $this->pair('Opening Balance', $this->safeEscPosText($fmt($register->opening_balance)), $width)."\n";
            $out .= $this->pair('Card Sales', $this->safeEscPosText($fmt($register->card_sales)), $width)."\n";
            if ($register->closing_balance !== null) {
                $out .= $this->pair('Cash in Drawer', $this->safeEscPosText($fmt($register->closing_balance)), $width)."\n";
            }
        } else {
            $out .= $this->pair('Orders', (string) (int) $register->orders_count, $width)."\n";
            $out .= str_repeat('-', $width)."\n";
            $out .= $this->pair('Cash Sales', $this->safeEscPosText($fmt($register->cash_sales)), $width)."\n";
            $out .= $this->pair('Card Sales', $this->safeEscPosText($fmt($register->card_sales)), $width)."\n";
            $out .= $this->pair('Bank/Online', $this->safeEscPosText($fmt(
                (float) $register->bank_transfer_sales + (float) $register->online_sales
            )), $width)."\n";
            $out .= $this->pair('Credit Sales', $this->safeEscPosText($fmt($register->credit_sales)), $width)."\n";
            $out .= $this->pair('TOTAL SALES', $this->safeEscPosText($fmt($register->total_sales)), $width)."\n";
            $out .= str_repeat('-', $width)."\n";
            $out .= $this->pair('Opening', $this->safeEscPosText($fmt($register->opening_balance)), $width)."\n";
            $out .= $this->pair('Cash In', $this->safeEscPosText($fmt($register->cash_in)), $width)."\n";
            $out .= $this->pair('Cash Out', $this->safeEscPosText('-'.$fmt($register->cash_out)), $width)."\n";
            $out .= $this->pair('Expected', $this->safeEscPosText($fmt($register->expected_cash)), $width)."\n";
            if ($register->closing_balance !== null) {
                $out .= $this->pair('Counted', $this->safeEscPosText($fmt($register->closing_balance)), $width)."\n";
                $diff = (float) ($register->difference ?? 0);
                $diffLabel = ($diff >= 0 ? '+' : '-').$fmt(abs($diff));
                $out .= $this->pair('Difference', $this->safeEscPosText($diffLabel), $width)."\n";
            }
        }

        $out .= str_repeat('=', $width)."\n"."\x1B\x61\x01";
        $out .= $this->safeEscPosText('Printed '.Setting::formatDateTime(now(), 'Y-m-d H:i'))."\n";
        $footer = trim((string) Setting::get('invoice_software_footer', 'Software By QPOS'));
        if ($footer !== '') {
            $out .= $this->safeEscPosText($footer)."\n";
        }
        $out .= "\n\n\n";
        $out .= "\x1D\x56\x00";

        return $out;
    }

    /**
     * Scan LAN subnet(s) for hosts accepting TCP 9100 (typical ESC/POS / XP-80C).
     * Only works when this app server is on the same network as the printer.
     *
     * @return array{success:bool,message:string,printers:list<array{ip:string,port:int}>,subnets:list<string>,note?:string|null}
     */
    public function discoverEscPosPrinters(?string $subnet = null, ?int $port = null): array
    {
        $port = $port ?: (int) Setting::get('receipt_printer_port', 9100);
        if ($port < 1 || $port > 65535) {
            $port = 9100;
        }

        $subnets = [];
        if ($subnet !== null && trim($subnet) !== '') {
            $normalized = $this->normalizeSubnetPrefix($subnet);
            if ($normalized === null) {
                return [
                    'success' => false,
                    'message' => 'Invalid subnet. Use e.g. 192.168.1 or 192.168.1.0',
                    'printers' => [],
                    'subnets' => [],
                ];
            }
            $subnets[] = $normalized;
        } else {
            $subnets = $this->guessLanPrefixes();
        }

        if ($subnets === []) {
            return [
                'success' => false,
                'message' => 'Could not detect a private LAN. Enter subnet manually (e.g. 192.168.1).',
                'printers' => [],
                'subnets' => [],
                'note' => 'Cloud/cPanel servers cannot see shop printers. Enter IP from the XP-80C network self-test page, or run the Windows scan tip below on the POS PC.',
            ];
        }

        @set_time_limit(120);
        $found = [];
        foreach ($subnets as $prefix) {
            foreach ($this->scanSubnetForPort($prefix, $port) as $ip) {
                $found[] = ['ip' => $ip, 'port' => $port];
            }
        }

        $unique = [];
        foreach ($found as $row) {
            $unique[$row['ip'].':'.$row['port']] = $row;
        }
        $printers = array_values($unique);

        $note = $printers === []
            ? 'No device answered on port '.$port.'. Connect XP-80C Ethernet to the router, wait, retry. If QRPOS is on cPanel, this scan cannot reach your shop LAN — use the printer self-test IP or Windows tip.'
            : null;

        return [
            'success' => true,
            'message' => $printers === []
                ? 'Scan finished — no printers found on port '.$port
                : ('Found '.count($printers).' device(s) on port '.$port),
            'printers' => $printers,
            'subnets' => $subnets,
            'note' => $note,
        ];
    }

    /**
     * @return array{success:bool,message:string,printer_ip?:string,printer_port?:int}
     */
    public function testEscPosPrinter(string $ip, ?int $port = null, bool $printSlip = true): array
    {
        $ip = trim($ip);
        if (! filter_var($ip, FILTER_VALIDATE_IP)) {
            return ['success' => false, 'message' => 'Invalid IP address'];
        }

        $port = $port ?: (int) Setting::get('receipt_printer_port', 9100);
        if ($port < 1 || $port > 65535) {
            $port = 9100;
        }

        $socket = @fsockopen($ip, $port, $errno, $errstr, 3);
        if (! $socket) {
            return [
                'success' => false,
                'message' => "Cannot reach {$ip}:{$port} — {$errstr}",
            ];
        }

        if ($printSlip) {
            $name = trim((string) Setting::get('receipt_printer_name', 'XP-80C')) ?: 'XP-80C';
            $company = (string) Setting::get('company_name', 'QRPOS');
            $lines = [
                $this->center($company, 32),
                $this->center('PRINTER TEST', 32),
                str_repeat('-', 32),
                $this->center($name, 32),
                $this->center($ip.':'.$port, 32),
                $this->center(date('Y-m-d H:i:s'), 32),
                str_repeat('-', 32),
                $this->center('OK — use this IP in Settings', 32),
                '',
                '',
            ];
            $payload = "\x1B\x40".implode("\n", $lines)."\n"."\x1D\x56\x00";
            stream_set_timeout($socket, 4);
            fwrite($socket, $payload);
        }

        fclose($socket);

        return [
            'success' => true,
            'message' => $printSlip
                ? "Connected — test slip sent to {$ip}:{$port}"
                : "Port {$port} is open on {$ip}",
            'printer_ip' => $ip,
            'printer_port' => $port,
        ];
    }

    /**
     * @return list<string>
     */
    private function guessLanPrefixes(): array
    {
        $ips = [];

        if (! empty($_SERVER['SERVER_ADDR']) && is_string($_SERVER['SERVER_ADDR'])) {
            $ips[] = $_SERVER['SERVER_ADDR'];
        }

        $hostIp = @gethostbyname((string) gethostname());
        if (is_string($hostIp) && $hostIp !== '') {
            $ips[] = $hostIp;
        }

        if (function_exists('net_get_interfaces')) {
            foreach (net_get_interfaces() ?: [] as $iface) {
                foreach ($iface['unicast'] ?? [] as $unicast) {
                    $addr = $unicast['address'] ?? null;
                    if (is_string($addr)) {
                        $ips[] = $addr;
                    }
                }
            }
        }

        $prefixes = [];
        foreach ($ips as $ip) {
            if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                continue;
            }
            if (! $this->isPrivateIpv4($ip)) {
                continue;
            }
            $parts = explode('.', $ip);
            if (count($parts) !== 4) {
                continue;
            }
            $prefixes[$parts[0].'.'.$parts[1].'.'.$parts[2]] = true;
        }

        return array_keys($prefixes);
    }

    private function isPrivateIpv4(string $ip): bool
    {
        return (bool) filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)
            && ! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE);
    }

    private function normalizeSubnetPrefix(string $input): ?string
    {
        $input = trim($input);
        if (preg_match('/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})(?:\.0)?$/', $input, $m)) {
            foreach ([1, 2, 3] as $i) {
                $n = (int) $m[$i];
                if ($n < 0 || $n > 255) {
                    return null;
                }
            }

            return $m[1].'.'.$m[2].'.'.$m[3];
        }

        if (filter_var($input, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $parts = explode('.', $input);

            return $parts[0].'.'.$parts[1].'.'.$parts[2];
        }

        return null;
    }

    /**
     * @return list<string>
     */
    private function scanSubnetForPort(string $prefix, int $port): array
    {
        $found = [];
        for ($i = 1; $i <= 254; $i++) {
            $ip = $prefix.'.'.$i;
            $socket = @fsockopen($ip, $port, $errno, $errstr, 0.09);
            if ($socket) {
                fclose($socket);
                $found[] = $ip;
            }
        }

        return $found;
    }

    private function buildEscPosReceipt(\App\Models\Order $order): string
    {
        $order->loadMissing(['branch', 'items', 'payments.creator', 'cashier', 'waiter', 'table', 'customer', 'deliveryPartner']);
        $invoice = \App\Services\BranchService::invoiceSettings($order->branch ?? \App\Services\BranchService::current());

        $company = trim((string) ($invoice['company_name'] ?? Setting::get('company_name', 'QRPOS')));
        $branch = $order->branch;
        $address = trim((string) ($branch?->address ?: ($invoice['company_address'] ?? '')));
        $phone = trim((string) ($branch?->phone ?: ($invoice['company_phone'] ?? '')));
        $email = trim((string) ($branch?->email ?: ($invoice['company_email'] ?? '')));
        $footer = trim((string) ($invoice['receipt_footer'] ?? 'Thank you!'));
        $taxName = (string) Setting::get('tax_name', 'Tax');
        $taxEnabled = (bool) Setting::get('tax_enabled', false);

        $width = 48;
        $liveItems = $order->items->where('is_void', false);
        $itemDiscountTotal = (float) $liveItems->sum(fn ($i) => (float) ($i->discount_amount ?? 0));
        $hasItemDiscount = $itemDiscountTotal > 0.009;
        $grossSubtotal = (float) $order->subtotal + $itemDiscountTotal;
        // With discount: unit×qty | Disc. | Amount — without: Item | Qty | Amount
        $colW = $hasItemDiscount ? [28, 9, 11] : [32, 5, 11];

        $payments = $order->payments
            ? $order->payments->where('status', 'completed')->values()
            : collect();
        $cashierName = $payments->sortByDesc('id')->first()?->creator?->name
            ?? $order->cashier?->name
            ?? 'N/A';

        $orderTypeLabel = match ($order->order_type) {
            'dine_in' => 'Dine In',
            'takeaway' => 'Take Away',
            'delivery' => 'Delivery',
            'express' => 'Express',
            default => ucfirst(str_replace('_', ' ', (string) $order->order_type)),
        };

        // Bold + double-strike for entire receipt
        $out = "\x1B\x40"."\x1B\x4D\x00"."\x1B\x47\x01"."\x1B\x45\x01";
        $out .= "\x1B\x61\x01";
        $logoPath = $this->resolveReceiptLogoPath($order);
        $logo = $this->escPosRasterLogo(384, $logoPath);
        if ($logo !== '') {
            $out .= $logo;
        }

        // Business name (dynamic company_name) — Arial only (never Forte), smaller size
        $arial = $this->arialFontPath();
        $nameRaster = $arial ? $this->escPosRasterText($company, 384, 28, $arial) : '';
        if ($nameRaster !== '') {
            $out .= $nameRaster;
        } else {
            // Fallback: printer Font A (sans-serif), normal size — still dynamic $company
            $out .= "\x1B\x4D\x00"."\x1D\x21\x00".$this->safeEscPosText($company)."\n";
        }

        foreach ($this->wrapLines($address, $width) as $addrLine) {
            $out .= $this->safeEscPosText($addrLine)."\n";
        }
        if ($phone !== '') {
            $out .= $this->safeEscPosText('Tel: '.$phone)."\n";
        }
        if ($email !== '') {
            $out .= $this->safeEscPosText($email)."\n";
        }

        $out .= str_repeat('-', $width)."\n";
        $out .= $this->safeEscPosText($orderTypeLabel)."\n";
        $out .= str_repeat('-', $width)."\n"."\x1B\x61\x00";
        $out .= 'Order: '.$this->safeEscPosText((string) $order->order_number)."\n";
        if ($order->customer) {
            $out .= 'Customer: '.$this->safeEscPosText((string) $order->customer->name)."\n";
            if (! empty($order->customer->phone)) {
                $out .= 'Phone: '.$this->safeEscPosText((string) $order->customer->phone)."\n";
            }
        }
        if ($order->order_type === 'dine_in') {
            $out .= 'Table: '.$this->safeEscPosText((string) ($order->table?->name ?? 'N/A'))."\n";
        }
        if ($order->order_type === 'delivery') {
            if ($order->delivery_address) {
                $out .= 'Address: '.$this->safeEscPosText((string) $order->delivery_address)."\n";
            }
            if ($order->deliveryPartner) {
                $out .= 'Partner: '.$this->safeEscPosText((string) $order->deliveryPartner->name)."\n";
            }
        }
        $out .= 'Waiter: '.$this->safeEscPosText((string) ($order->waiter?->name ?? '—'))."\n";
        $out .= 'Cashier: '.$this->safeEscPosText($cashierName)."\n";
        $out .= 'Date: '.Setting::formatDateTime($order->created_at, 'Y-m-d H:i')."\n";
        $out .= str_repeat('-', $width)."\n";
        if ($hasItemDiscount) {
            $out .= $this->cols(['Item', 'Disc.', 'Amount'], $colW)."\n";
        } else {
            $out .= $this->cols(['Item', 'Qty', 'Amount'], $colW)."\n";
        }
        $out .= str_repeat('-', $width)."\n";

        foreach ($liveItems as $item) {
            $qty = (float) $item->quantity;
            $itemDisc = (float) ($item->discount_amount ?? 0);
            $lineNet = (float) $item->total_price;
            $lineGross = $lineNet + $itemDisc;
            $isFree = $lineNet <= 0 || str_contains(strtoupper((string) $item->special_instructions), 'LOYALTY FREE');
            $name = $this->safeEscPosText((string) $item->product_name.($isFree ? ' [FREE]' : ''));
            $qtyLabel = rtrim(rtrim(number_format($qty, 3, '.', ''), '0'), '.') ?: '0';
            $amtLabel = $isFree ? 'FREE' : number_format($lineNet, 2);

            if ($hasItemDiscount) {
                $unitDisplay = $qty > 0 ? ($lineGross / $qty) : (float) $item->unit_price;
                $unitQty = $isFree ? 'FREE' : (number_format($unitDisplay, 2).' x '.$qtyLabel);
                $discLabel = ($itemDisc > 0 && ! $isFree) ? number_format($itemDisc, 2) : '-';
                // Name on first line; unit×qty / disc / amount under
                $out .= $name."\n";
                $out .= $this->cols([$unitQty, $discLabel, $amtLabel], $colW)."\n";
            } else {
                // Simple: Item | Qty | Amount (no Disc. column)
                if (strlen($name) > $colW[0]) {
                    $out .= $name."\n".$this->cols(['', $qtyLabel, $amtLabel], $colW)."\n";
                } else {
                    $out .= $this->cols([$name, $qtyLabel, $amtLabel], $colW)."\n";
                }
            }
        }

        $out .= str_repeat('-', $width)."\n";
        $out .= $this->pair('Subtotal', number_format($hasItemDiscount ? $grossSubtotal : (float) $order->subtotal, 2), $width)."\n";
        if ($hasItemDiscount) {
            $out .= $this->pair('Item Discount', '-'.number_format($itemDiscountTotal, 2), $width)."\n";
        }
        if ((float) $order->discount_amount > 0) {
            $billLabel = str_contains(strtolower((string) $order->order_notes), 'loyalty') ? 'Free drink' : 'Bill Discount';
            $out .= $this->pair($billLabel, '-'.number_format((float) $order->discount_amount, 2), $width)."\n";
        }
        if ($taxEnabled) {
            $out .= $this->pair($taxName, number_format((float) $order->tax_amount, 2), $width)."\n";
        }
        if ((float) $order->service_charge > 0) {
            $out .= $this->pair('Service', number_format((float) $order->service_charge, 2), $width)."\n";
        }
        if ((float) $order->delivery_charge > 0) {
            $out .= $this->pair('Delivery', number_format((float) $order->delivery_charge, 2), $width)."\n";
        }

        $out .= "\x1D\x21\x01";
        $out .= $this->pair('TOTAL', number_format((float) $order->total_amount, 2), $width)."\n";
        $out .= "\x1D\x21\x00";

        $byMethod = $payments->groupBy('method')->map(fn ($rows) => (float) $rows->sum('amount'));
        if ($byMethod->isNotEmpty()) {
            foreach ($byMethod as $method => $amount) {
                $label = match ($method) {
                    'cash' => 'Cash',
                    'card' => 'Card',
                    'bank_transfer' => 'Bank Transfer',
                    'online' => 'Online',
                    'credit' => 'Credit',
                    'split' => 'Split',
                    default => ucfirst(str_replace('_', ' ', (string) $method)),
                };
                $out .= $this->pair($label, number_format((float) $amount, 2), $width)."\n";
            }
            $out .= $this->pair('Paid Total', number_format((float) $order->paid_amount, 2), $width)."\n";
        } else {
            $out .= $this->pair('Paid', number_format((float) $order->paid_amount, 2), $width)."\n";
        }
        if ((float) $order->change_amount > 0) {
            $out .= $this->pair('Change', number_format((float) $order->change_amount, 2), $width)."\n";
        }

        $out .= str_repeat('=', $width)."\n"."\x1B\x61\x01";
        foreach ($this->wrapLines($footer !== '' ? $footer : 'Thank you!', $width) as $footerLine) {
            $out .= $this->safeEscPosText($footerLine)."\n";
        }
        $softwareFooter = trim((string) ($invoice['invoice_software_footer'] ?? Setting::get('invoice_software_footer', 'Software By QPOS')));
        if ($softwareFooter !== '') {
            foreach (preg_split('/\r\n|\r|\n/', $softwareFooter) as $sfLine) {
                $sfLine = trim((string) $sfLine);
                if ($sfLine === '') {
                    continue;
                }
                foreach ($this->wrapLines($sfLine, $width) as $wrapped) {
                    $out .= $this->safeEscPosText($wrapped)."\n";
                }
            }
        }
        $out .= "\x1B\x61\x00";
        $out .= "\n\n\n"."\x1B\x64\x03"."\x1D\x56\x00";
        return $out;
    }

    private function resolveReceiptLogoPath(\App\Models\Order $order): ?string
    {
        $branch = $order->branch;
        if ($branch?->invoice_logo) {
            $relative = ltrim(str_replace('storage/', '', (string) $branch->invoice_logo), '/');
            $path = storage_path('app/public/'.$relative);
            if (is_file($path)) {
                return $path;
            }
        }

        return Setting::logoPathFor('invoice_logo') ?? Setting::logoPathFor('company_logo');
    }

    private function forteFontPath(): ?string
    {
        $path = public_path('fonts/FORTE.TTF');

        return is_file($path) ? $path : null;
    }

    /** Arial for cashier receipt business name only (never applied globally). */
    private function arialFontPath(): ?string
    {
        $candidates = [
            public_path('fonts/arialbd.ttf'),
            public_path('fonts/Arialbd.ttf'),
            public_path('fonts/arial.ttf'),
            public_path('fonts/Arial.ttf'),
            'C:\\Windows\\Fonts\\arialbd.ttf',
            'C:\\Windows\\Fonts\\arial.ttf',
            '/usr/share/fonts/truetype/msttcorefonts/Arial_Bold.ttf',
            '/usr/share/fonts/truetype/msttcorefonts/arialbd.ttf',
            '/usr/share/fonts/truetype/msttcorefonts/Arial.ttf',
            '/usr/share/fonts/truetype/msttcorefonts/arial.ttf',
            '/usr/share/fonts/truetype/liberation/LiberationSans-Bold.ttf',
            '/usr/share/fonts/truetype/liberation/LiberationSans-Regular.ttf',
        ];

        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    /** Render text as ESC/POS raster. Optional $fontPath overrides default Forte. */
    private function escPosRasterText(string $text, int $maxWidth = 384, int $fontSize = 40, ?string $fontPath = null): string
    {
        $font = $fontPath ?: $this->forteFontPath();
        if (! $font || ! function_exists('imagettftext') || ! function_exists('imagecreatetruecolor')) {
            return '';
        }

        $text = trim($text);
        if ($text === '') {
            return '';
        }

        $maxWidth = max(64, min(384, $maxWidth));
        $size = $fontSize;
        $bbox = null;
        for ($try = 0; $try < 12; $try++) {
            $bbox = @imagettfbbox($size, 0, $font, $text);
            if (! is_array($bbox)) {
                return '';
            }
            $textW = (int) abs($bbox[2] - $bbox[0]);
            if ($textW <= ($maxWidth - 8) || $size <= 18) {
                break;
            }
            $size -= 2;
        }

        $textW = (int) abs($bbox[2] - $bbox[0]);
        $textH = (int) abs($bbox[7] - $bbox[1]);
        $padX = 4;
        $padY = 1; // tight gap above/below name
        $dstW = (int) (floor(min($maxWidth, $textW + ($padX * 2)) / 8) * 8);
        if ($dstW < 8) {
            $dstW = 8;
        }
        $dstH = max(1, $textH + ($padY * 2));

        $dst = imagecreatetruecolor($dstW, $dstH);
        if (! $dst) {
            return '';
        }
        $white = imagecolorallocate($dst, 255, 255, 255);
        $black = imagecolorallocate($dst, 0, 0, 0);
        imagefilledrectangle($dst, 0, 0, $dstW, $dstH, $white);

        $x = (int) max(0, (int) (($dstW - $textW) / 2) - (int) min($bbox[0], 0));
        $y = (int) ($padY - $bbox[7]);
        imagettftext($dst, $size, 0, $x, $y, $black, $font, $text);

        $esc = $this->gdImageToEscPosRaster($dst);
        imagedestroy($dst);

        return $esc;
    }

    private function escPosRasterLogo(int $maxWidth = 384, ?string $path = null): string
    {
        if (! function_exists('imagecreatetruecolor')) {
            return '';
        }

        $path = $path ?: (Setting::logoPathFor('invoice_logo') ?? Setting::logoPathFor('company_logo'));
        if (! $path || ! is_file($path)) {
            return '';
        }

        $info = @getimagesize($path);
        if (! $info) {
            return '';
        }
        $mime = $info['mime'] ?? '';
        $src = match (true) {
            str_contains($mime, 'png') => @imagecreatefrompng($path),
            str_contains($mime, 'jpeg'), str_contains($mime, 'jpg') => @imagecreatefromjpeg($path),
            str_contains($mime, 'webp') && function_exists('imagecreatefromwebp') => @imagecreatefromwebp($path),
            str_contains($mime, 'gif') => @imagecreatefromgif($path),
            default => null,
        };
        if (! $src) {
            return '';
        }

        $srcW = imagesx($src);
        $srcH = imagesy($src);
        if ($srcW < 1 || $srcH < 1) {
            imagedestroy($src);

            return '';
        }

        $maxWidth = max(64, min(384, $maxWidth));
        $dstW = $srcW > $maxWidth ? $maxWidth : $srcW;
        // width must be multiple of 8 for raster
        $dstW = (int) (floor($dstW / 8) * 8);
        if ($dstW < 8) {
            $dstW = 8;
        }
        $dstH = max(1, (int) round($srcH * ($dstW / $srcW)));

        $dst = imagecreatetruecolor($dstW, $dstH);
        $white = imagecolorallocate($dst, 255, 255, 255);
        imagefilledrectangle($dst, 0, 0, $dstW, $dstH, $white);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $dstW, $dstH, $srcW, $srcH);
        imagedestroy($src);

        $esc = $this->gdImageToEscPosRaster($dst);
        imagedestroy($dst);

        return $esc;
    }

    /** @param \GdImage|resource $dst */
    private function gdImageToEscPosRaster($dst): string
    {
        $dstW = imagesx($dst);
        $dstH = imagesy($dst);
        $bytesPerRow = (int) ($dstW / 8);
        if ($bytesPerRow < 1 || $dstH < 1) {
            return '';
        }

        $raster = '';
        for ($y = 0; $y < $dstH; $y++) {
            for ($xByte = 0; $xByte < $bytesPerRow; $xByte++) {
                $byte = 0;
                for ($bit = 0; $bit < 8; $bit++) {
                    $x = $xByte * 8 + $bit;
                    $rgb = imagecolorat($dst, $x, $y);
                    $r = ($rgb >> 16) & 0xFF;
                    $g = ($rgb >> 8) & 0xFF;
                    $b = $rgb & 0xFF;
                    $luma = (0.299 * $r) + (0.587 * $g) + (0.114 * $b);
                    if ($luma < 180) {
                        $byte |= (0x80 >> $bit);
                    }
                }
                $raster .= chr($byte);
            }
        }

        $xL = $bytesPerRow & 0xFF;
        $xH = ($bytesPerRow >> 8) & 0xFF;
        $yL = $dstH & 0xFF;
        $yH = ($dstH >> 8) & 0xFF;

        // GS v 0 m xL xH yL yH data  (m=0 normal)
        return "\x1D\x76\x30\x00".chr($xL).chr($xH).chr($yL).chr($yH).$raster;
    }

    /** @return list<string> */
    private function wrapLines(string $text, int $width): array
    {
        $text = trim(preg_replace("/[\\r\\n\\t]+/", ' ', $text) ?? $text);
        $text = trim(preg_replace('/\s+/', ' ', $text) ?? $text);
        if ($text === '') {
            return [];
        }
        $safe = $this->safeEscPosText($text);
        if ($safe === '') {
            return [];
        }
        $words = preg_split('/\s+/', $safe) ?: [$safe];
        $lines = [];
        $current = '';
        foreach ($words as $word) {
            $word = (string) $word;
            if ($word === '') {
                continue;
            }
            if (strlen($word) > $width) {
                if ($current !== '') {
                    $lines[] = $current;
                    $current = '';
                }
                foreach (str_split($word, $width) as $chunk) {
                    $lines[] = $chunk;
                }
                continue;
            }
            $next = $current === '' ? $word : $current.' '.$word;
            if (strlen($next) > $width) {
                $lines[] = $current;
                $current = $word;
            } else {
                $current = $next;
            }
        }
        if ($current !== '') {
            $lines[] = $current;
        }

        return $lines;
    }

    /** Strip characters that confuse thermal printers; keep Latin + digits. */
    private function safeEscPosText(string $text): string
    {
        $text = str_replace(["\r", "\n", "\t"], ' ', $text);
        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
            if (is_string($converted) && $converted !== '') {
                $text = $converted;
            }
        }

        return preg_replace('/[^\x20-\x7E]/', '', $text) ?? $text;
    }

    /** @param list<string> $parts @param list<int> $widths */
    private function cols(array $parts, array $widths): string
    {
        $out = '';
        foreach ($parts as $i => $part) {
            $w = $widths[$i] ?? 8;
            $part = (string) $part;
            if ($i === 0) {
                $out .= str_pad(substr($part, 0, $w), $w, ' ', STR_PAD_RIGHT);
            } else {
                $out .= str_pad(substr($part, 0, $w), $w, ' ', STR_PAD_LEFT);
            }
        }

        return $out;
    }

    private function pair(string $label, string $value, int $width = 48): string
    {
        $value = (string) $value;
        $maxLabel = max(1, $width - strlen($value) - 1);
        $label = substr($label, 0, $maxLabel);

        return str_pad($label, $width - strlen($value), ' ', STR_PAD_RIGHT).$value;
    }
}
