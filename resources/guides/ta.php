<?php

return [
    'title' => 'QRPOS எப்படி பயன்படுத்துவது',
    'sections' => [
        [
            'h' => '1. முதல் உள்நுழைவு',
            'p' => 'நிறுவலின் போது உருவாக்கப்பட்ட நிர்வாக மின்னஞ்சல்/கடவுச்சொல்லால் உள்நுழையவும். பிறகு Users-இல் கடவுச்சொல்லை மாற்றவும். Software Owner Email/SMTP, PWA, Integrations மற்றும் Fresh Start-ஐ நிர்வகிக்கிறார்.',
        ],
        [
            'h' => '2. வணிக அமைப்புகள்',
            'p' => 'Settings → Business-இல் உணவகப் பெயர், தொலைபேசி, நாணயம், வரி, விலைப்பட்டியல் லோகோவை அமைக்கவும்.',
        ],
        [
            'h' => '3. மெனு அமைப்பு',
            'p' => 'Categories உருவாக்கி Products சேர்க்கவும். KOT/BOTக்கு Categories-ஐ Kitchen-க்கு ஒதுக்கவும். மேசை QR ஆர்டருக்கு QR Menu-ஐ இயக்கவும்.',
        ],
        [
            'h' => '4. தளங்கள் & மேசைகள்',
            'p' => 'Floors & Tables-இல் தளம்/மேசை சேர்த்து QR அட்டைகளை அச்சிடவும்.',
        ],
        [
            'h' => '5. POS பில்லிங்',
            'p' => 'POS திறந்து Shift தொடங்கவும். உருப்படி சேர்த்து Place Order (KOT), பிறகு Pay Now. இரவு cutoffக்குப் பிறகு கடைசி shift மூடும்போது Day End உருவாகும்.',
        ],
        [
            'h' => '6. Waiter & Kitchen',
            'p' => 'Waiter Panel தரை ஊழியருக்கு. KOT Confirmation ON ஆனால் Kitchen Display. அச்சு மட்டும் வேண்டுமானால் Confirmation OFF வைக்கவும்.',
        ],
        [
            'h' => '7. அறிக்கைகள் & நினைவூட்டல்கள்',
            'p' => 'Reports → Sales / Shifts. Notifications-இல் payment/hosting நினைவூட்டல்கள் (email/SMS/WhatsApp/in-app).',
        ],
        [
            'h' => '8. Avenque AI chatbot',
            'p' => 'Software Owner Settings → Avenque AI Agent-இல் unlock செய்த பிறகு உணவகம் On செய்யும். கீழ் வலது ரோபோட் “A” chatbot தோன்றும். Gemini API key இல்லாமலேயே sales/stock/profit கேள்விகளுக்கு local mode பதில் அளிக்கும். Gemini key சேர்த்தால் மேம்பட்ட உரையாடல்.',
        ],
        [
            'h' => '9. ஆதரவு',
            'p' => 'QRPOS by Avenque — qrpos@avenque.io | 076 822 2201',
        ],
    ],
];
