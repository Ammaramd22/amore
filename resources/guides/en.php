<?php

return [
    'title' => 'How to use QRPOS',
    'sections' => [
        [
            'h' => '1. First login',
            'p' => 'Sign in with the admin email and password created during install. Change the password under Users after first login. Software Owner accounts manage Email/SMTP, PWA, Integrations, and Fresh Start.',
        ],
        [
            'h' => '2. Business settings',
            'p' => 'Go to Settings → Business. Set restaurant name, phone, currency, tax, and invoice logo. Software Logo is owner-only branding for the product.',
        ],
        [
            'h' => '3. Menu setup',
            'p' => 'Create Categories, then Products with prices. Assign categories to Kitchens for KOT/BOT printing. Enable QR Menu under owner settings if guests order from the table QR.',
        ],
        [
            'h' => '4. Floors & tables',
            'p' => 'Open Floors & Tables, add floors and tables, then print QR cards for dine-in ordering.',
        ],
        [
            'h' => '5. POS billing',
            'p' => 'Open POS → start Shift (or Day). Add items, place order (KOT), then Pay Now. After 10pm (cutoff), closing the last shift also creates Day End with all shifts.',
        ],
        [
            'h' => '6. Waiter & kitchen',
            'p' => 'Waiter Panel for floor staff. Kitchen Display when KOT Confirmation is ON. Print-only kitchens can leave confirmation OFF.',
        ],
        [
            'h' => '7. Reports & reminders',
            'p' => 'Reports → Sales / Shifts. Notifications panel sends payment & hosting reminders (email/SMS/WhatsApp/in-app bell).',
        ],
        [
            'h' => '8. Avenque AI chatbot',
            'p' => 'Software Owner unlocks Avenque Guide under Settings → Avenque AI Agent, then turn it On. A friendly helper peeks bottom-right. Ask “how to billing” for steps, then tap Take billing tour for a live walkthrough on POS (order type → products → Place Order → Pay Now). “How to add product” has a tour too. No Gemini key needed for guides; optional Gemini unlocks smarter chat and live sales/stock answers.',
        ],
        [
            'h' => '9. Support',
            'p' => 'QRPOS by Avenque — qrpos@avenque.io | 076 822 2201',
        ],
    ],
];
