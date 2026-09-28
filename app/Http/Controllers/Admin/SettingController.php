<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Setting;
use App\Services\PwaIconService;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    /** Display order and labels for settings sections (QPOS-style). */
    public const SECTIONS = [
        'business' => [
            'label' => 'Business',
            'icon' => 'fa-building',
            'keys' => [
                'company_name', 'company_address', 'company_phone', 'company_email',
                'company_logo', 'invoice_logo', 'receipt_footer', 'currency_symbol', 'currency_position',
                'timezone',
            ],
        ],
        'tax' => [
            'label' => 'Tax',
            'icon' => 'fa-percent',
            'keys' => [
                'tax_enabled', 'tax_name', 'tax_rate',
                'service_charge_enabled', 'service_charge_rate',
            ],
        ],
        'pos' => [
            'label' => 'POS',
            'icon' => 'fa-cash-register',
            'keys' => [
                'pos_ui_mode', 'shop_ui_picker_enabled', 'pos_theme', 'pos_categories_per_row', 'pos_products_per_row',
                'table_selection_required', 'show_screen_numbers_keyboard',
                'auto_print_receipt', 'auto_print_kot', 'pos_bridge_print_pending_kot', 'print_ask_before',
                'receipt_print_mode', 'receipt_print_bridge_url',
                'receipt_printer_ip', 'receipt_printer_port', 'receipt_printer_name',
                'cash_drawer_pin', 'open_drawer_after_print',
                'shortcut_focus_search', 'shortcut_place_order', 'shortcut_pay_now', 'shortcut_open_bills',
                'shift_method', 'business_day_cutoff',
                'customer_display_mode',
                'customer_display_protocol',
                'customer_display_baud',
                'expiry_remind_days',
            ],
        ],
        'email' => [
            'label' => 'Email / SMTP',
            'icon' => 'fa-envelope',
            'keys' => [
                'day_end_email_enabled',
                'day_end_email_to',
                'mail_mailer',
                'mail_host',
                'mail_port',
                'mail_username',
                'mail_password',
                'mail_encryption',
                'mail_from_address',
                'mail_from_name',
            ],
        ],
        'waiter' => [
            'label' => 'Waiter Panel',
            'icon' => 'fa-user-tie',
            'keys' => [
                'waiter_show_direct_items',
                'bring_bill_enabled',
                'waiter_rating_enabled',
            ],
        ],
        'pwa' => [
            'label' => 'PWA App',
            'icon' => 'fa-mobile-alt',
            'keys' => [
                'pwa_app_name',
                'pwa_app_short_name',
                'pwa_app_logo',
                'pwa_splash_image',
                'pwa_theme_color',
                'pwa_background_color',
            ],
        ],
        'kitchen' => [
            'label' => 'Kitchen',
            'icon' => 'fa-utensils',
            'keys' => [
                'kitchen_display_enabled',
                'kot_confirmation_enabled',
                'kitchen_auto_accept',
                'kitchen_sound_alert', 'kitchen_alert_interval',
                'waiter_sound_alert', 'pos_ready_sound_alert',
                'customer_display_ready_seconds',
            ],
        ],
        'prefixes' => [
            'label' => 'Prefixes',
            'icon' => 'fa-hashtag',
            'keys' => [
                'invoice_prefix', 'invoice_reset_daily',
                'kot_prefix', 'bot_prefix', 'kot_reset_daily',
                'self_order_prefix',
            ],
        ],
        'qr' => [
            'label' => 'QR Menu',
            'icon' => 'fa-qrcode',
            'keys' => [
                'qr_menu_enabled', 'qr_menu_theme', 'qr_order_approval', 'qr_show_prices',
            ],
        ],
        'notifications' => [
            'label' => 'Integrations',
            'icon' => 'fa-plug',
            'keys' => [
                'sms_enabled', 'sms_provider', 'notify_lk_api_key', 'notify_lk_sender_id',
                'smslenz_user_id', 'smslenz_api_key', 'smslenz_sender_id',
                'whatsapp_enabled',
                'meta_wa_access_token', 'meta_wa_phone_number_id', 'meta_wa_api_version',
                'meta_wa_template_name', 'meta_wa_template_language',
            ],
        ],
        'ai' => [
            'label' => 'Avenque AI Agent',
            'icon' => 'fa-robot',
            'keys' => [
                'avenque_ai_available',
                'gemini_api_key',
                'gemini_model',
                'avenque_ai_enabled',
            ],
        ],
        'cheques' => [
            'label' => 'Cheque Management',
            'icon' => 'fa-money-check-alt',
            'keys' => [
                'cheque_management_enabled',
                'cheque_reminder_enabled',
                'cheque_reminder_days_before',
                'cheque_reminder_channels',
                'cheque_reminder_email',
                'cheque_reminder_phone',
            ],
        ],
        'loyalty' => [
            'label' => 'Loyalty Stamp Card',
            'icon' => 'fa-stamp',
            'keys' => [
                'loyalty_enabled',
                'loyalty_stamps_required',
                'loyalty_reward_label',
                'loyalty_category_ids',
                'loyalty_card_expiry_days',
            ],
        ],
        'billiards' => [
            'label' => 'Billiards Booking',
            'icon' => 'fa-bowling-ball',
            'keys' => [
                'billiards_enabled',
                'billiards_end_alert_minutes',
                'billiards_display_token',
            ],
        ],
        'branches' => [
            'label' => 'Multi Branch',
            'icon' => 'fa-code-branch',
            'keys' => [
                'multi_branch_enabled',
                'max_branches',
            ],
        ],
        'bakery' => [
            'label' => 'Bakery UI',
            'icon' => 'fa-bread-slice',
            'keys' => [
                'bakery_direct_billing',
                'bakery_disable_kot',
                'bakery_category_ids',
                'bakery_show_dine_in',
                'bakery_show_delivery',
                'bakery_show_express',
                'bakery_show_cart_display',
                'bakery_show_status_display',
                'bakery_show_orders_display',
            ],
        ],
        'bartender' => [
            'label' => 'BarTender / Labels',
            'icon' => 'fa-barcode',
            'keys' => [
                'bartender_save_server',
                'bartender_download_browser',
                'bartender_label_path',
                'bartender_show_open_btn',
            ],
        ],
    ];

    public const LABELS = [
        'company_name' => 'Business Name',
        'company_address' => 'Business Address',
        'company_phone' => 'Phone',
        'company_email' => 'Email',
        'company_logo' => 'Software Logo',
        'invoice_logo' => 'Invoice Logo',
        'receipt_footer' => 'Receipt Footer',
        'currency_symbol' => 'Currency',
        'currency_position' => 'Currency Symbol Placement',
        'timezone' => 'System Timezone',
        'tax_enabled' => 'Enable Tax',
        'tax_name' => 'Tax Name',
        'tax_rate' => 'Tax Rate (%)',
        'service_charge_enabled' => 'Enable Service Charge',
        'service_charge_rate' => 'Service Charge Rate (%)',
        'pos_ui_mode' => 'POS Front UI',
        'shop_ui_picker_enabled' => 'Ask Shop UI After Login',
        'bakery_show_dine_in' => 'Bakery: Show Dine-in',
        'bakery_show_delivery' => 'Bakery: Show Delivery',
        'bakery_show_express' => 'Bakery: Show Express',
        'bakery_disable_kot' => 'Bakery: No KOT / BOT Tickets',
        'bakery_direct_billing' => 'Bakery: Direct Billing (Pay & Finish)',
        'bakery_category_ids' => 'Bakery: Show Categories',
        'bakery_show_cart_display' => 'Bakery: Show Cart Display',
        'bakery_show_status_display' => 'Bakery: Show Status Display',
        'bakery_show_orders_display' => 'Bakery: Show Orders Display',
        'pos_theme' => 'POS Theme',
        'pos_categories_per_row' => 'Categories Per Row',
        'pos_products_per_row' => 'Products Per Row',
        'table_selection_required' => 'Require Table for Dine-in',
        'show_screen_numbers_keyboard' => 'Show On-screen Keypad',
        'auto_print_receipt' => 'Auto Print Receipt',
        'auto_print_kot' => 'Auto Print KOT/BOT',
        'pos_bridge_print_pending_kot' => 'POS Auto-Print Pending KOTs (Waiter)',
        'print_ask_before' => 'Ask Before Printing',
        'receipt_print_mode' => 'Receipt Print Mode',
        'receipt_print_bridge_url' => 'Local Print Bridge URL',
        'receipt_printer_ip' => 'Receipt Printer IP',
        'receipt_printer_port' => 'Receipt Printer Port',
        'receipt_printer_name' => 'Receipt Printer Name (Windows)',
        'cash_drawer_pin' => 'Cash Drawer Kick Pin',
        'open_drawer_after_print' => 'Open Drawer After Print',
        'shortcut_focus_search' => 'Shortcut: Focus Search',
        'shortcut_place_order' => 'Shortcut: Place Order',
        'shortcut_pay_now' => 'Shortcut: Pay Now',
        'shortcut_open_bills' => 'Shortcut: Open Bills',
        'shift_method' => 'Shift Method',
        'business_day_cutoff' => 'Day-End Cutoff Time',
        'day_end_email_enabled' => 'Email Report When Shift / Day Ends',
        'day_end_email_to' => 'Report Email To',
        'mail_mailer' => 'Mail Driver',
        'mail_host' => 'SMTP Host',
        'mail_port' => 'SMTP Port',
        'mail_username' => 'SMTP Username',
        'mail_password' => 'SMTP Password',
        'mail_encryption' => 'SMTP Encryption',
        'mail_from_address' => 'From Email',
        'mail_from_name' => 'From Name',
        'waiter_show_direct_items' => 'Show Direct Items on Waiter Panel',
        'bring_bill_enabled' => 'Enable Bring Bill',
        'waiter_rating_enabled' => 'Enable Guest Rating',
        'pwa_app_name' => 'App Name',
        'pwa_app_short_name' => 'Short Name',
        'pwa_app_logo' => 'App Logo',
        'pwa_splash_image' => 'Splash Screen',
        'pwa_theme_color' => 'Theme Color',
        'pwa_background_color' => 'Splash Background',
        'kitchen_display_enabled' => 'Enable Kitchen Display',
        'kot_confirmation_enabled' => 'Enable KOT Confirmation',
        'kitchen_auto_accept' => 'Auto Start Preparing',
        'kitchen_sound_alert' => 'Kitchen Sound Alert',
        'kitchen_alert_interval' => 'Alert Interval (seconds)',
        'waiter_sound_alert' => 'Waiter Ready Sound',
        'pos_ready_sound_alert' => 'POS / Cashier Ready Sound',
        'customer_display_mode' => 'Customer Display Type',
        'customer_display_protocol' => 'Analog LED Protocol',
        'customer_display_baud' => 'Analog LED Baud Rate',
        'expiry_remind_days' => 'Expiry Reminder (days before)',
        'customer_display_ready_seconds' => 'Ready Status Auto-Hide (seconds)',
        'invoice_prefix' => 'Invoice Prefix',
        'invoice_reset_daily' => 'Reset Invoice Daily',
        'kot_prefix' => 'KOT Prefix',
        'bot_prefix' => 'BOT Prefix',
        'kot_reset_daily' => 'Reset KOT/BOT Daily',
        'self_order_prefix' => 'Self Order Prefix',
        'qr_menu_enabled' => 'Enable QR Menu',
        'qr_menu_theme' => 'QR Menu Theme',
        'qr_order_approval' => 'Require Waiter Approval',
        'qr_show_prices' => 'Show Prices on QR Menu',
        'sms_enabled' => 'Enable SMS',
        'sms_provider' => 'SMS Provider',
        'notify_lk_api_key' => 'Notify.lk API Key',
        'notify_lk_sender_id' => 'Notify.lk Sender ID',
        'smslenz_user_id' => 'SMSLenz User ID',
        'smslenz_api_key' => 'SMSLenz API Key',
        'smslenz_sender_id' => 'SMSLenz Sender ID',
        'whatsapp_enabled' => 'Enable WhatsApp',
        'meta_wa_access_token' => 'Meta Access Token',
        'meta_wa_phone_number_id' => 'Meta Phone Number ID',
        'meta_wa_api_version' => 'Meta Graph API Version',
        'meta_wa_template_name' => 'Promo Template Name (optional)',
        'meta_wa_template_language' => 'Promo Template Language',
        'avenque_ai_available' => 'Make Avenque AI Available',
        'gemini_api_key' => 'Google Gemini API Key',
        'gemini_model' => 'Gemini Model',
        'avenque_ai_enabled' => 'Enable Avenque AI Agent',
        'cheque_management_enabled' => 'Enable Cheque Management',
        'cheque_reminder_enabled' => 'Cheque Due Reminders',
        'cheque_reminder_days_before' => 'Remind Days Before Due',
        'cheque_reminder_channels' => 'Reminder Channels',
        'cheque_reminder_email' => 'Reminder Email(s)',
        'cheque_reminder_phone' => 'Reminder Phone',
        'loyalty_enabled' => 'Enable Loyalty Stamp Card',
        'loyalty_stamps_required' => 'Stamps for free drink',
        'loyalty_reward_label' => 'Reward name',
        'loyalty_category_ids' => 'Stamp categories (JSON ids)',
        'loyalty_card_expiry_days' => 'Digital card expiry (days)',
        'billiards_enabled' => 'Enable Billiards Booking',
        'billiards_end_alert_minutes' => 'End Alert Minutes',
        'billiards_display_token' => 'Display Public Token',
        'multi_branch_enabled' => 'Enable Multi Branch',
        'max_branches' => 'Maximum Branches',
        'bartender_save_server' => 'Save to Fixed Server URL',
        'bartender_download_browser' => 'Download CSV to Browser',
        'bartender_label_path' => 'BarTender Label File Path',
        'bartender_show_open_btn' => 'Show “Open BarTender” Button',
    ];

    public const HINTS = [
        'whatsapp_enabled' => 'ON = send WhatsApp via official Meta Cloud API (developers.facebook.com)',
        'meta_wa_access_token' => 'Permanent System User token (or temporary token for testing) from Meta Developer → WhatsApp → API Setup',
        'meta_wa_phone_number_id' => 'Phone number ID from Meta WhatsApp → API Setup (not the phone number itself)',
        'meta_wa_api_version' => 'e.g. v22.0 — leave default unless Meta docs say otherwise',
        'meta_wa_template_name' => 'Approved template with 1 body variable {{1}} for promo campaigns. Leave empty to send free-form text (24h window / test only)',
        'meta_wa_template_language' => 'Template language code, e.g. en, en_US, si',
        'avenque_ai_available' => 'Software owner: unlock Avenque AI chatbot for this restaurant. Works in local mode without Gemini; API key unlocks smarter answers.',
        'gemini_api_key' => 'Optional. From Google AI Studio (aistudio.google.com). Leave empty to use built-in local answers for sales/stock/profit.',
        'gemini_model' => 'Recommended: gemini-2.0-flash (fast & low cost). Only used when an API key is set.',
        'cheque_management_enabled' => 'Software owner only: unlock supplier cheque tracking (pending / cleared / returned), purchase integration, account posting on clear, and dashboard dues.',
        'cheque_reminder_enabled' => 'Send daily reminders for pending cheques approaching or past due date.',
        'cheque_reminder_days_before' => 'Start reminding this many days before the cheque due date (also reminds while overdue).',
        'cheque_reminder_channels' => 'Comma-separated: inapp,email,sms,whatsapp',
        'cheque_reminder_email' => 'Owner / finance emails for cheque due alerts (comma-separated).',
        'cheque_reminder_phone' => 'Phone for SMS/WhatsApp cheque reminders.',
        'loyalty_enabled' => 'Software owner only: unlock digital stamp cards. Joined customers earn 1 stamp per item in selected categories; at N stamps they get a free drink. POS must select customer or scan card QR each time.',
        'loyalty_stamps_required' => 'How many stamps fill the card (e.g. 10). When full, customer gets 1 free drink credit.',
        'loyalty_reward_label' => 'Shown on card and POS (e.g. Free juice / Free drink).',
        'loyalty_category_ids' => 'JSON array of category IDs that earn stamps, e.g. [3,5]. Prefer configuring via Loyalty admin page.',
        'loyalty_card_expiry_days' => 'Days after joining until the digital stamp card expires (e.g. 365). Set 0 for never. Expired cards cannot earn or redeem until re-joined.',
        'billiards_enabled' => 'Software owner only: unlock pool/snooker table bookings, desk UI, payments, SMS eBill, live display, and reports.',
        'billiards_end_alert_minutes' => 'Minutes before session end to alert desk staff and highlight the table on the display.',
        'billiards_display_token' => 'Optional secret for public TV URL /billiards/display?token=…. Leave empty to require login.',
        'multi_branch_enabled' => 'Software owner only: unlock multi-branch. Restaurant can create branches (up to max), assign users, and print invoices with branch details.',
        'max_branches' => 'Maximum number of branches the restaurant may create (e.g. 2, 5, 10).',
        'bartender_save_server' => 'Save CSV to fixed server URL so the print PC batch file can download it for BarTender',
        'bartender_download_browser' => 'Also download CSV in the browser when exporting labels',
        'bartender_label_path' => 'Path to your .btw label file on the print computer (shown in the Print Labels guide)',
        'bartender_show_open_btn' => 'Show “Open BarTender” hint (browsers cannot open local apps — prefer the desktop batch shortcut)',
        'sms_enabled' => 'ON = allow promo, order, and billiards SMS via Notify.lk or SMSLenz',
        'sms_provider' => 'notify_lk or smslenz',
        'notify_lk_api_key' => 'API key from Notify.lk for SMS',
        'smslenz_user_id' => 'User ID from SMSLenz dashboard',
        'smslenz_api_key' => 'API key from SMSLenz',
        'smslenz_sender_id' => 'Approved sender ID on SMSLenz',
        'tax_enabled' => 'Turn off to hide tax on POS and skip tax on bills',
        'currency_position' => 'before or after amount (e.g. LKR 100 or 100 LKR)',
        'currency_symbol' => 'e.g. LKR, Rs.',
        'timezone' => 'Used for POS receipts, KOT/BOT, order timestamps, and shift/day-end times. Choose the restaurant local timezone (not the browser/device clock).',
        'tax_name' => 'Shown on bills (e.g. VAT, SSCL)',
        'table_selection_required' => 'Force table selection before dine-in payment',
        'print_ask_before' => 'ON = show print preview for Preview-mode kitchens. OFF = silent TCP when kitchen has IP. Direct-mode kitchens always use Print Bridge (like receipt).',
        'receipt_print_mode' => 'Direct = Local Print Bridge on POS PC (USB/Ethernet Windows queue). Preview = browser dialog.',
        'receipt_print_bridge_url' => 'Run Start-Print-Bridge.bat on the Windows POS PC (starts hidden). Default http://127.0.0.1:18181. Use Install-Print-Bridge-Startup.bat for login auto-start.',
        'receipt_printer_ip' => 'Optional Ethernet IP for XP-80C. Leave blank for USB via Windows printer name + Print Bridge.',
        'receipt_printer_port' => 'Usually 9100 for raw ESC/POS',
        'receipt_printer_name' => 'Must match the Windows printer name (Devices and Printers), e.g. XP-80C',
        'cash_drawer_pin' => '0 = kick pin 2 (most common), 1 = pin 5 — switch if drawer does not open',
        'open_drawer_after_print' => 'ON = after each direct receipt print via Print Bridge, send one drawer kick',
        'shortcut_focus_search' => 'F-key to focus product search (e.g. F2). Leave blank to disable',
        'shortcut_place_order' => 'F-key for Place Order / Update Bill / COD (shown on button)',
        'shortcut_pay_now' => 'F-key for Pay Now (shown on button)',
        'shortcut_open_bills' => 'F-key for Open Bills (shown on button)',
        'shift_method' => 'Shift = each cashier opens/closes their own drawer. Day End = one store-wide opening cash / closing cash for the whole day (no per-cashier shifts).',
        'business_day_cutoff' => 'When Shift Method is on: after this time (e.g. 22:00), closing the last open shift also creates the Day End report with all shifts for that day.',
        'day_end_email_enabled' => 'Send 80mm PDF report by email when a shift or day is closed',
        'day_end_email_to' => 'Admin email(s), comma-separated. Falls back to Business Email if empty.',
        'mail_mailer' => 'Use smtp for real email. Use log to write emails to the Laravel log (testing).',
        'mail_host' => 'e.g. mail.yourdomain.com or smtp.gmail.com',
        'mail_port' => 'Usually 587 (TLS) or 465 (SSL)',
        'mail_username' => 'SMTP login username',
        'mail_password' => 'SMTP login password / app password',
        'mail_encryption' => 'tls, ssl, or none',
        'mail_from_address' => 'Address shown as sender',
        'mail_from_name' => 'Name shown as sender',
        'auto_print_kot' => 'Auto-trigger KOT/BOT print when order is placed/updated',
        'pos_bridge_print_pending_kot' => 'ON = POS PC with Print Bridge watches for waiter (and failed) KOTs that never printed, then prints them automatically. Manual “Pending KOT” button always works.',
        'waiter_show_direct_items' => 'ON = waiters can add retail/direct items (no KOT). OFF = hide those categories from waiter panel',
        'bring_bill_enabled' => 'ON = waiter can tap Bring Bill → shows in POS Pay Bills. Works even if KOT Confirmation is OFF (after KOT is printed). OFF = hide Bring Bill and Pay Bills queue.',
        'waiter_rating_enabled' => 'ON = Rate tab + guest rating prompts on waiter dashboard. OFF = hide Rate menu and skip rating popups.',
        'kitchen_display_enabled' => 'ON = Kitchen Display tablet screen (/kitchen/display). OFF = hide links and block the screen. Use OFF if you only print KOT slips.',
        'kot_confirmation_enabled' => 'ON = kitchen Accept → Preparing → Ready → waiter Serve. OFF = print KOT/BOT only (no accept/ready/serve screens). Best for fast counter service.',
        'kitchen_auto_accept' => 'Only when KOT Confirmation is ON: skip Accept and start tickets as Preparing',
        'kitchen_sound_alert' => 'Bell when a new KOT arrives on the kitchen tablet',
        'waiter_sound_alert' => 'Bell on waiter dashboard when their order is Ready',
        'pos_ready_sound_alert' => 'Bell on POS when kitchen marks an order Ready',
        'customer_display_mode' => 'Software owner only (POS): Digital = second-monitor TV page. Analog = physical rear LED on the POS (COM/USB) — cashier connects once in Chrome/Edge.',
        'customer_display_protocol' => 'Software owner only (POS Analog): Plain = single amount (most rear LEDs showing 0.00). ESC/POS / DSP-800 for 2-line VFD poles.',
        'customer_display_baud' => 'Software owner only (POS Analog): serial speed. Usually 9600.',
        'expiry_remind_days' => 'Dashboard warns for products and ingredients expiring within this many days (and already expired). Set 0 to only show expired items.',
        'customer_display_ready_seconds' => 'How long READY tickets stay on the customer status board (e.g. 120 = 2 min). Serve removes them immediately. Set 0 to keep until Serve.',
        'qr_order_approval' => 'ON = guest order waits for waiter Accept before KOT. OFF = send to kitchen immediately',
        'qr_menu_theme' => 'Look of the guest phone menu: Amber Spice, Ocean Fresh, or Night Luxe',
        'qr_menu_enabled' => 'Allow guests to open /qr-menu/{table} and place orders',
        'pos_ui_mode' => 'Software owner only: Restaurant UI = tables, KOT, open bills. Bakery UI = left categories + cart. Ice Cream UI = 2-column category images, 4-column product images, right cart (10″ tablet).',
        'shop_ui_picker_enabled' => 'Software owner only: ON = after login ask Restaurant / Bakery / Ice Cream. OFF = use POS Front UI above and skip the picker (recommended for live restaurants).',
        'bakery_show_dine_in' => 'Software owner only (Bakery UI): show Dine-in. Off when Direct Billing is on.',
        'bakery_show_delivery' => 'Software owner only (Bakery UI): show Delivery button.',
        'bakery_show_express' => 'Software owner only (Bakery UI): show Express button.',
        'bakery_disable_kot' => 'Software owner only (Bakery UI): skip KOT/BOT tickets. Also forced on when Direct Billing is on.',
        'bakery_direct_billing' => 'Software owner only (Bakery UI): counter pay & finish — hide Place Order, table/waiter/open bills; payment only. Best with kitchen off.',
        'bakery_category_ids' => 'Software owner only (Bakery UI): pick which categories appear in Bakery POS. Only selected categories show. Leave empty to show all.',
        'bakery_show_cart_display' => 'Software owner only (Bakery UI): show Cart customer-display link in POS header.',
        'bakery_show_status_display' => 'Software owner only (Bakery UI): show Status board link in POS header.',
        'bakery_show_orders_display' => 'Software owner only (Bakery UI): show Orders / recent in POS header and bakery tools.',
        'company_logo' => 'Upload a logo for login and sidebar. PNG/JPG/WebP, max 2MB',
        'invoice_logo' => 'Shown centered at the top of printed invoices, above company details. Falls back to Software Logo if empty.',
        'pwa_app_name' => 'Full name under the home screen icon (e.g. QRPOS Waiter Panel)',
        'pwa_app_short_name' => 'Short label under the icon (max ~12 characters recommended)',
        'pwa_app_logo' => 'Icon for the installed Waiter PWA. Falls back to Software Logo. PNG with transparent background works best.',
        'pwa_splash_image' => 'Optional full-screen splash shown when the Waiter app opens. Recommended 1080×1920 or square.',
        'pwa_theme_color' => 'Browser / status bar color for the installed app',
        'pwa_background_color' => 'Background behind splash and icon padding',
    ];

    /** Settings sections only software owner may see / edit. */
    public const OWNER_SECTIONS = [
        'email' => 'settings.email',
        'pwa' => 'settings.pwa',
        'notifications' => 'settings.integrations',
        'ai' => 'settings.integrations',
        'cheques' => 'settings.system',
        'loyalty' => 'settings.system',
        'billiards' => 'settings.system',
        'branches' => 'settings.system',
        'bakery' => 'settings.system',
        'kitchen' => 'settings.system',
        'qr' => 'settings.system',
        'waiter' => 'settings.system',
    ];

    /** Individual keys that stay owner-only even inside restaurant sections. */
    public const OWNER_KEYS = [
        'company_logo',
        'shift_method',
        'business_day_cutoff',
        'pos_ui_mode',
        'shop_ui_picker_enabled',
        'customer_display_mode',
        'customer_display_protocol',
        'customer_display_baud',
    ];

    public function index()
    {
        $this->normalizeGroups();
        $user = auth()->user();

        $all = Setting::query()->get()->keyBy('key');
        $sections = [];

        foreach (self::SECTIONS as $slug => $meta) {
            if (isset(self::OWNER_SECTIONS[$slug]) && ! $user?->can(self::OWNER_SECTIONS[$slug]) && ! $user?->isSoftwareOwner()) {
                continue;
            }

            $items = collect($meta['keys'])
                ->map(fn ($key) => $all->get($key))
                ->filter()
                ->filter(function ($setting) use ($user) {
                    if (! in_array($setting->key, self::OWNER_KEYS, true)) {
                        return true;
                    }

                    return $user?->isSoftwareOwner() || $user?->can('settings.system');
                })
                ->values();

            if ($items->isEmpty()) {
                continue;
            }

            $sections[$slug] = [
                'label' => $meta['label'],
                'icon' => $meta['icon'],
                'items' => $items,
                'owner_only' => isset(self::OWNER_SECTIONS[$slug]),
            ];
        }

        // Any leftover keys (owner-only leftovers stay hidden from restaurant staff)
        $known = collect(self::SECTIONS)->pluck('keys')->flatten()->all();
        $extra = $all->reject(fn ($s) => in_array($s->key, $known, true))->values();
        if ($extra->isNotEmpty() && ($user?->isSoftwareOwner() || $user?->can('settings.system'))) {
            $sections['other'] = [
                'label' => 'Other',
                'icon' => 'fa-cog',
                'items' => $extra,
                'owner_only' => true,
            ];
        }

        $labels = self::LABELS;
        $hints = self::HINTS;
        $isOwner = (bool) ($user?->isSoftwareOwner());
        $bakeryCategories = Category::query()
            ->orderBy('display_order')
            ->orderBy('name')
            ->get(['id', 'name', 'type']);

        return view('admin.settings.index', compact('sections', 'labels', 'hints', 'isOwner', 'bakeryCategories'));
    }

    public function update(Request $request)
    {
        $user = $request->user();
        $isOwner = (bool) ($user?->isSoftwareOwner() || $user?->can('settings.system'));

        $ownerKeyBlocked = function (string $key) use ($isOwner): bool {
            if ($isOwner) {
                return false;
            }
            if (in_array($key, self::OWNER_KEYS, true)) {
                return true;
            }
            foreach (self::SECTIONS as $slug => $meta) {
                if (! isset(self::OWNER_SECTIONS[$slug])) {
                    continue;
                }
                if (in_array($key, $meta['keys'], true)) {
                    return true;
                }
            }
            // Owner-only prefixes / keys (feature packs + integrations)
            foreach (['pwa_', 'mail_', 'day_end_email', 'sms_', 'notify_lk_', 'smslenz_', 'whatsapp_', 'meta_wa_', 'hosting_', 'payment_reminder_', 'avenque_ai_', 'gemini_'] as $prefix) {
                if (str_starts_with($key, $prefix) || $key === 'day_end_email_enabled' || $key === 'day_end_email_to') {
                    return true;
                }
            }
            if (in_array($key, [
                'shift_method', 'business_day_cutoff',
                'kitchen_display_enabled', 'kot_confirmation_enabled', 'kitchen_auto_accept',
                'kitchen_sound_alert', 'kitchen_alert_interval', 'waiter_sound_alert',
                'pos_ready_sound_alert', 'customer_display_ready_seconds',
                'waiter_show_direct_items', 'bring_bill_enabled', 'waiter_rating_enabled',
                'qr_menu_enabled', 'qr_menu_theme', 'qr_order_approval', 'qr_show_prices',
            ], true)) {
                return true;
            }

            return false;
        };

        if ($request->hasFile('company_logo_file')) {
            if ($ownerKeyBlocked('company_logo')) {
                abort(403, 'Software logo can only be changed by the software owner.');
            }
            $request->validate([
                'company_logo_file' => 'image|mimes:jpg,jpeg,png,webp,gif|max:2048',
            ]);

            $path = $request->file('company_logo_file')->store('logos', 'public');
            Setting::set('company_logo', $path, 'business', 'Software / business logo', 'string');
        } elseif ($request->filled('company_logo') && ! $request->boolean('clear_company_logo')) {
            if (! $ownerKeyBlocked('company_logo') && Setting::where('key', 'company_logo')->exists()) {
                Setting::set('company_logo', $request->input('company_logo'));
            }
        }

        if ($request->boolean('clear_company_logo')) {
            if ($ownerKeyBlocked('company_logo')) {
                abort(403);
            }
            Setting::set('company_logo', '', 'business');
        }

        if ($request->hasFile('invoice_logo_file')) {
            $request->validate([
                'invoice_logo_file' => 'image|mimes:jpg,jpeg,png,webp,gif|max:2048',
            ]);

            $path = $request->file('invoice_logo_file')->store('logos', 'public');
            Setting::set('invoice_logo', $path, 'business', 'Invoice / receipt logo', 'string');
        } elseif ($request->filled('invoice_logo') && ! $request->boolean('clear_invoice_logo')) {
            if (Setting::where('key', 'invoice_logo')->exists()) {
                Setting::set('invoice_logo', $request->input('invoice_logo'));
            }
        }

        if ($request->boolean('clear_invoice_logo')) {
            Setting::set('invoice_logo', '', 'business');
        }

        $regeneratePwaIcons = false;

        if ($request->hasFile('pwa_app_logo_file')) {
            if ($ownerKeyBlocked('pwa_app_logo')) {
                abort(403);
            }
            $request->validate([
                'pwa_app_logo_file' => 'image|mimes:jpg,jpeg,png,webp,gif|max:2048',
            ]);
            $path = $request->file('pwa_app_logo_file')->store('logos', 'public');
            Setting::set('pwa_app_logo', $path, 'pwa', 'PWA / home screen app logo', 'string');
            $regeneratePwaIcons = true;
        } elseif ($request->filled('pwa_app_logo') && ! $request->boolean('clear_pwa_app_logo')) {
            if (! $ownerKeyBlocked('pwa_app_logo') && Setting::where('key', 'pwa_app_logo')->exists()) {
                Setting::set('pwa_app_logo', $request->input('pwa_app_logo'));
            }
        }
        if ($request->boolean('clear_pwa_app_logo')) {
            if ($ownerKeyBlocked('pwa_app_logo')) {
                abort(403);
            }
            Setting::set('pwa_app_logo', '', 'pwa');
            $regeneratePwaIcons = true;
        }

        if ($request->hasFile('pwa_splash_image_file')) {
            if ($ownerKeyBlocked('pwa_splash_image')) {
                abort(403);
            }
            $request->validate([
                'pwa_splash_image_file' => 'image|mimes:jpg,jpeg,png,webp,gif|max:4096',
            ]);
            $path = $request->file('pwa_splash_image_file')->store('logos', 'public');
            Setting::set('pwa_splash_image', $path, 'pwa', 'PWA splash screen image', 'string');
        } elseif ($request->filled('pwa_splash_image') && ! $request->boolean('clear_pwa_splash_image')) {
            if (! $ownerKeyBlocked('pwa_splash_image') && Setting::where('key', 'pwa_splash_image')->exists()) {
                Setting::set('pwa_splash_image', $request->input('pwa_splash_image'));
            }
        }
        if ($request->boolean('clear_pwa_splash_image')) {
            if ($ownerKeyBlocked('pwa_splash_image')) {
                abort(403);
            }
            Setting::set('pwa_splash_image', '', 'pwa');
        }

        if (! $ownerKeyBlocked('bakery_category_ids') && $request->boolean('_bakery_category_ids_form')) {
            $ids = collect($request->input('bakery_category_ids', []))
                ->map(fn ($id) => (int) $id)
                ->filter(fn ($id) => $id > 0)
                ->unique()
                ->values()
                ->all();
            Setting::set('bakery_category_ids', json_encode($ids), 'bakery', null, 'string');
        }

        foreach ($request->except(
            '_token',
            '_settings_section',
            '_bakery_category_ids_form',
            'bakery_category_ids',
            'company_logo_file',
            'company_logo',
            'clear_company_logo',
            'invoice_logo_file',
            'invoice_logo',
            'clear_invoice_logo',
            'pwa_app_logo_file',
            'pwa_app_logo',
            'clear_pwa_app_logo',
            'pwa_splash_image_file',
            'pwa_splash_image',
            'clear_pwa_splash_image'
        ) as $key => $value) {
            if (! Setting::where('key', $key)->exists()) {
                continue;
            }
            if ($ownerKeyBlocked($key)) {
                continue;
            }
            if (in_array($key, ['pwa_background_color', 'pwa_theme_color'], true)) {
                if (! is_string($value) || ! preg_match('/^#[0-9A-Fa-f]{6}$/', $value)) {
                    $value = '#1c1410';
                }
                $regeneratePwaIcons = true;
            }

            $existing = Setting::query()->where('key', $key)->first();
            if ($existing && $existing->type === 'boolean') {
                $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
            }
            if ($key === 'timezone') {
                $value = trim((string) $value);
                if ($value === '' || ! in_array($value, timezone_identifiers_list(), true)) {
                    continue;
                }
            }

            Setting::set($key, $value, $existing?->group, $existing?->description, $existing?->type);
        }

        // Hide POS "Shop UI" header immediately: drop session picker when disabled
        if (! \App\Http\Controllers\Auth\ShopUiController::isPickerEnabled()) {
            session()->forget(\App\Http\Controllers\Auth\ShopUiController::SESSION_KEY);
        }

        if ($regeneratePwaIcons || $request->hasFile('company_logo_file')) {
            try {
                app(PwaIconService::class)->regenerateWaiterIcons();
            } catch (\Throwable $e) {
                // Non-fatal — manifest can still use uploaded logo URL
            }
        }

        \App\Services\TimezoneConfigService::applyFromSettings();
        \App\Services\MailConfigService::applyFromSettings();

        $section = (string) $request->input('_settings_section', '');
        $url = route('settings.index');
        if ($section !== '' && array_key_exists($section, self::SECTIONS)) {
            $url .= '#'.$section;
        }

        return redirect()->to($url)->with('success', 'Settings saved.');
    }

    private function normalizeGroups(): void
    {
        $map = [
            'company_name' => 'business',
            'company_address' => 'business',
            'company_phone' => 'business',
            'company_email' => 'business',
            'company_logo' => 'business',
            'invoice_logo' => 'business',
            'receipt_footer' => 'business',
            'currency_symbol' => 'business',
            'currency_position' => 'business',
            'timezone' => 'business',
            'tax_enabled' => 'tax',
            'tax_name' => 'tax',
            'tax_rate' => 'tax',
            'service_charge_enabled' => 'tax',
            'service_charge_rate' => 'tax',
            'pos_ui_mode' => 'pos',
            'shop_ui_picker_enabled' => 'pos',
            'customer_display_mode' => 'pos',
            'customer_display_protocol' => 'pos',
            'customer_display_baud' => 'pos',
            'expiry_remind_days' => 'pos',
            'bakery_show_dine_in' => 'bakery',
            'bakery_show_delivery' => 'bakery',
            'bakery_show_express' => 'bakery',
            'bakery_disable_kot' => 'bakery',
            'bakery_direct_billing' => 'bakery',
            'bakery_category_ids' => 'bakery',
            'bakery_show_cart_display' => 'bakery',
            'bakery_show_status_display' => 'bakery',
            'bakery_show_orders_display' => 'bakery',
            'pos_theme' => 'pos',
            'pos_categories_per_row' => 'pos',
            'pos_products_per_row' => 'pos',
            'table_selection_required' => 'pos',
            'show_screen_numbers_keyboard' => 'pos',
            'auto_print_receipt' => 'pos',
            'auto_print_kot' => 'pos',
            'pos_bridge_print_pending_kot' => 'pos',
            'print_ask_before' => 'pos',
            'receipt_print_mode' => 'pos',
            'receipt_print_bridge_url' => 'pos',
            'receipt_printer_ip' => 'pos',
            'receipt_printer_port' => 'pos',
            'receipt_printer_name' => 'pos',
            'cash_drawer_pin' => 'pos',
            'open_drawer_after_print' => 'pos',
            'shortcut_focus_search' => 'pos',
            'shortcut_place_order' => 'pos',
            'shortcut_pay_now' => 'pos',
            'shortcut_open_bills' => 'pos',
            'shift_method' => 'pos',
            'day_end_email_enabled' => 'email',
            'day_end_email_to' => 'email',
            'mail_mailer' => 'email',
            'mail_host' => 'email',
            'mail_port' => 'email',
            'mail_username' => 'email',
            'mail_password' => 'email',
            'mail_encryption' => 'email',
            'mail_from_address' => 'email',
            'mail_from_name' => 'email',
            'waiter_show_direct_items' => 'waiter',
            'bring_bill_enabled' => 'waiter',
            'waiter_rating_enabled' => 'waiter',
            'pwa_app_name' => 'pwa',
            'pwa_app_short_name' => 'pwa',
            'pwa_app_logo' => 'pwa',
            'pwa_splash_image' => 'pwa',
            'pwa_theme_color' => 'pwa',
            'pwa_background_color' => 'pwa',
            'kitchen_display_enabled' => 'kitchen',
            'kot_confirmation_enabled' => 'kitchen',
            'kitchen_auto_accept' => 'kitchen',
            'kitchen_sound_alert' => 'kitchen',
            'kitchen_alert_interval' => 'kitchen',
            'waiter_sound_alert' => 'kitchen',
            'pos_ready_sound_alert' => 'kitchen',
            'customer_display_ready_seconds' => 'kitchen',
            'invoice_prefix' => 'prefixes',
            'invoice_reset_daily' => 'prefixes',
            'kot_prefix' => 'prefixes',
            'bot_prefix' => 'prefixes',
            'kot_reset_daily' => 'prefixes',
            'self_order_prefix' => 'prefixes',
            'qr_menu_enabled' => 'qr',
            'qr_menu_theme' => 'qr',
            'qr_order_approval' => 'qr',
            'qr_show_prices' => 'qr',
            'sms_enabled' => 'notifications',
            'sms_provider' => 'notifications',
            'notify_lk_api_key' => 'notifications',
            'notify_lk_sender_id' => 'notifications',
            'smslenz_user_id' => 'notifications',
            'smslenz_api_key' => 'notifications',
            'smslenz_sender_id' => 'notifications',
            'whatsapp_enabled' => 'notifications',
            'meta_wa_access_token' => 'notifications',
            'meta_wa_phone_number_id' => 'notifications',
            'meta_wa_api_version' => 'notifications',
            'meta_wa_template_name' => 'notifications',
            'meta_wa_template_language' => 'notifications',
            'avenque_ai_available' => 'ai',
            'gemini_api_key' => 'ai',
            'gemini_model' => 'ai',
            'avenque_ai_enabled' => 'ai',
            'cheque_management_enabled' => 'cheques',
            'cheque_reminder_enabled' => 'cheques',
            'cheque_reminder_days_before' => 'cheques',
            'cheque_reminder_channels' => 'cheques',
            'cheque_reminder_email' => 'cheques',
            'cheque_reminder_phone' => 'cheques',
            'loyalty_enabled' => 'loyalty',
            'loyalty_stamps_required' => 'loyalty',
            'loyalty_reward_label' => 'loyalty',
            'loyalty_category_ids' => 'loyalty',
            'loyalty_card_expiry_days' => 'loyalty',
            'billiards_enabled' => 'billiards',
            'billiards_end_alert_minutes' => 'billiards',
            'billiards_display_token' => 'billiards',
            'multi_branch_enabled' => 'branches',
            'max_branches' => 'branches',
        ];

        foreach ($map as $key => $group) {
            Setting::where('key', $key)->where('group', '!=', $group)->update(['group' => $group]);
        }

        Setting::firstOrCreate(
            ['key' => 'tax_enabled'],
            [
                'value' => '0',
                'type' => 'boolean',
                'group' => 'tax',
                'description' => 'Apply tax on POS bills',
            ]
        );

        Setting::firstOrCreate(
            ['key' => 'invoice_logo'],
            [
                'value' => '',
                'type' => 'string',
                'group' => 'business',
                'description' => 'Logo printed on invoices above company details',
            ]
        );

        Setting::firstOrCreate(
            ['key' => 'receipt_printer_ip'],
            [
                'value' => '',
                'type' => 'string',
                'group' => 'pos',
                'description' => 'Counter receipt printer IP for cash drawer kick',
            ]
        );

        Setting::firstOrCreate(
            ['key' => 'receipt_printer_port'],
            [
                'value' => '9100',
                'type' => 'integer',
                'group' => 'pos',
                'description' => 'Receipt printer ESC/POS port',
            ]
        );

        Setting::firstOrCreate(
            ['key' => 'receipt_print_mode'],
            [
                'value' => 'preview',
                'type' => 'string',
                'group' => 'pos',
                'description' => 'direct = Local Print Bridge; preview = browser dialog',
            ]
        );

        Setting::firstOrCreate(
            ['key' => 'receipt_print_bridge_url'],
            [
                'value' => 'http://127.0.0.1:18181',
                'type' => 'string',
                'group' => 'pos',
                'description' => 'Local Print Bridge URL on POS PC',
            ]
        );

        Setting::firstOrCreate(
            ['key' => 'pos_bridge_print_pending_kot'],
            [
                'value' => '1',
                'type' => 'boolean',
                'group' => 'pos',
                'description' => 'POS Print Bridge auto-prints waiter KOTs that never printed',
            ]
        );

        Setting::firstOrCreate(
            ['key' => 'receipt_printer_name'],
            [
                'value' => 'XP-80C',
                'type' => 'string',
                'group' => 'pos',
                'description' => 'Windows printer queue name for Print Bridge',
            ]
        );

        Setting::firstOrCreate(
            ['key' => 'open_drawer_after_print'],
            [
                'value' => '1',
                'type' => 'boolean',
                'group' => 'pos',
                'description' => 'Open cash drawer after receipt print',
            ]
        );

        Setting::firstOrCreate(
            ['key' => 'cash_drawer_pin'],
            [
                'value' => '0',
                'type' => 'integer',
                'group' => 'pos',
                'description' => 'Cash drawer kick pin (0 or 1)',
            ]
        );

        foreach ([
            'shortcut_focus_search' => ['F2', 'Focus product search'],
            'shortcut_place_order' => ['F6', 'Place order shortcut'],
            'shortcut_pay_now' => ['F7', 'Pay now shortcut'],
            'shortcut_open_bills' => ['F8', 'Open bills shortcut'],
        ] as $key => [$default, $desc]) {
            Setting::firstOrCreate(
                ['key' => $key],
                [
                    'value' => $default,
                    'type' => 'string',
                    'group' => 'pos',
                    'description' => $desc,
                ]
            );
        }

        Setting::firstOrCreate(
            ['key' => 'print_ask_before'],
            [
                'value' => '1',
                'type' => 'boolean',
                'group' => 'pos',
                'description' => 'Ask before printing (preview). Off = silent network print to kitchen printer IP',
            ]
        );

        Setting::firstOrCreate(
            ['key' => 'pos_ui_mode'],
            [
                'value' => 'restaurant',
                'type' => 'string',
                'group' => 'pos',
                'description' => 'POS front UI: restaurant or bakery',
            ]
        );

        Setting::firstOrCreate(
            ['key' => 'shop_ui_picker_enabled'],
            [
                'value' => '1',
                'type' => 'boolean',
                'group' => 'pos',
                'description' => 'Ask Restaurant / Bakery / Ice Cream UI after login',
            ]
        );

        foreach ([
            'bakery_show_dine_in' => ['1', 'Bakery UI: show Dine-in'],
            'bakery_show_delivery' => ['0', 'Bakery UI: show Delivery'],
            'bakery_show_express' => ['0', 'Bakery UI: show Express'],
            'bakery_disable_kot' => ['0', 'Bakery UI: no KOT/BOT tickets'],
            'bakery_direct_billing' => ['0', 'Bakery UI: direct pay & finish — no Place Order / tables'],
            'bakery_category_ids' => ['[]', 'Bakery UI: JSON category IDs visible in Bakery POS'],
            'bakery_show_cart_display' => ['1', 'Bakery UI: show Cart display in header'],
            'bakery_show_status_display' => ['0', 'Bakery UI: show Status display in header'],
            'bakery_show_orders_display' => ['0', 'Bakery UI: show Orders in header'],
        ] as $key => [$default, $desc]) {
            Setting::firstOrCreate(
                ['key' => $key],
                [
                    'value' => $default,
                    'type' => $key === 'bakery_category_ids' ? 'string' : 'boolean',
                    'group' => 'bakery',
                    'description' => $desc,
                ]
            );
        }

        Setting::firstOrCreate(
            ['key' => 'waiter_show_direct_items'],
            [
                'value' => '1',
                'type' => 'boolean',
                'group' => 'waiter',
                'description' => 'Show direct/retail categories on waiter panel',
            ]
        );

        Setting::firstOrCreate(
            ['key' => 'bring_bill_enabled'],
            [
                'value' => '1',
                'type' => 'boolean',
                'group' => 'waiter',
                'description' => 'ON = waiter Bring Bill → POS Pay Bills. OFF = hide the option',
            ]
        );

        Setting::firstOrCreate(
            ['key' => 'waiter_rating_enabled'],
            [
                'value' => '1',
                'type' => 'boolean',
                'group' => 'waiter',
                'description' => 'ON = Rate tab and guest rating prompts. OFF = hide rating',
            ]
        );

        foreach ([
            'pwa_app_name' => ['QRPOS Waiter Panel', 'Installed PWA full name'],
            'pwa_app_short_name' => ['QRPOS Waiter', 'Installed PWA short name'],
            'pwa_app_logo' => ['', 'PWA home screen logo'],
            'pwa_splash_image' => ['', 'PWA splash screen image'],
            'pwa_theme_color' => ['#1c1410', 'PWA theme / status bar color'],
            'pwa_background_color' => ['#1c1410', 'PWA splash background color'],
        ] as $key => [$default, $desc]) {
            Setting::firstOrCreate(
                ['key' => $key],
                [
                    'value' => $default,
                    'type' => 'string',
                    'group' => 'pwa',
                    'description' => $desc,
                ]
            );
        }

        Setting::firstOrCreate(
            ['key' => 'shift_method'],
            [
                'value' => 'shift',
                'type' => 'string',
                'group' => 'pos',
                'description' => 'shift or day_end',
            ]
        );

        Setting::firstOrCreate(
            ['key' => 'business_day_cutoff'],
            [
                'value' => '22:00',
                'type' => 'string',
                'group' => 'pos',
                'description' => 'HH:MM after which last shift close auto day-ends',
            ]
        );

        Setting::firstOrCreate(
            ['key' => 'timezone'],
            [
                'value' => env('APP_TIMEZONE', 'Asia/Colombo'),
                'type' => 'string',
                'group' => 'business',
                'description' => 'System timezone for POS receipts, KOT, and order timestamps',
            ]
        );

        Setting::firstOrCreate(
            ['key' => 'day_end_email_enabled'],
            [
                'value' => '0',
                'type' => 'boolean',
                'group' => 'email',
                'description' => 'Email shift/day-end report to admin',
            ]
        );

        foreach ([
            'day_end_email_to' => ['', 'Admin report recipients'],
            'mail_mailer' => ['log', 'Mail driver'],
            'mail_host' => ['', 'SMTP host'],
            'mail_port' => ['587', 'SMTP port'],
            'mail_username' => ['', 'SMTP username'],
            'mail_password' => ['', 'SMTP password'],
            'mail_encryption' => ['tls', 'SMTP encryption'],
            'mail_from_address' => ['', 'From address'],
            'mail_from_name' => ['QRPOS', 'From name'],
        ] as $key => [$default, $desc]) {
            Setting::firstOrCreate(
                ['key' => $key],
                [
                    'value' => $default,
                    'type' => 'string',
                    'group' => 'email',
                    'description' => $desc,
                ]
            );
        }

        Setting::firstOrCreate(
            ['key' => 'customer_display_mode'],
            [
                'value' => 'digital',
                'type' => 'string',
                'group' => 'pos',
                'description' => 'Customer display: digital (TV) or analog (LED hardware)',
            ]
        );

        Setting::firstOrCreate(
            ['key' => 'customer_display_protocol'],
            [
                'value' => 'plain',
                'type' => 'string',
                'group' => 'pos',
                'description' => 'Analog LED serial protocol: plain, escpos, dsp800',
            ]
        );

        Setting::firstOrCreate(
            ['key' => 'customer_display_baud'],
            [
                'value' => '9600',
                'type' => 'integer',
                'group' => 'pos',
                'description' => 'Analog LED serial baud rate',
            ]
        );

        Setting::firstOrCreate(
            ['key' => 'expiry_remind_days'],
            [
                'value' => '7',
                'type' => 'integer',
                'group' => 'pos',
                'description' => 'Days before expiry to show dashboard reminder',
            ]
        );

        Setting::firstOrCreate(
            ['key' => 'customer_display_ready_seconds'],
            [
                'value' => '120',
                'type' => 'integer',
                'group' => 'kitchen',
                'description' => 'Seconds READY stays on customer status board before auto-hide (0 = until Serve)',
            ]
        );

        Setting::firstOrCreate(
            ['key' => 'kot_confirmation_enabled'],
            [
                'value' => '1',
                'type' => 'boolean',
                'group' => 'kitchen',
                'description' => 'ON = Accept/Preparing/Ready/Serve workflow. OFF = print KOT only',
            ]
        );

        Setting::firstOrCreate(
            ['key' => 'kitchen_display_enabled'],
            [
                'value' => '1',
                'type' => 'boolean',
                'group' => 'kitchen',
                'description' => 'Show kitchen display links and allow kitchen display screen',
            ]
        );

        Setting::firstOrCreate(
            ['key' => 'meta_wa_access_token'],
            [
                'value' => '',
                'type' => 'string',
                'group' => 'notifications',
                'description' => 'Meta WhatsApp Cloud API access token',
            ]
        );

        Setting::firstOrCreate(
            ['key' => 'meta_wa_phone_number_id'],
            [
                'value' => '',
                'type' => 'string',
                'group' => 'notifications',
                'description' => 'Meta WhatsApp Phone Number ID',
            ]
        );

        Setting::firstOrCreate(
            ['key' => 'meta_wa_api_version'],
            [
                'value' => 'v22.0',
                'type' => 'string',
                'group' => 'notifications',
                'description' => 'Meta Graph API version',
            ]
        );

        Setting::firstOrCreate(
            ['key' => 'meta_wa_template_name'],
            [
                'value' => '',
                'type' => 'string',
                'group' => 'notifications',
                'description' => 'Optional approved promo template name',
            ]
        );

        Setting::firstOrCreate(
            ['key' => 'meta_wa_template_language'],
            [
                'value' => 'en',
                'type' => 'string',
                'group' => 'notifications',
                'description' => 'Promo template language code',
            ]
        );

        foreach ([
            'hosting_reminder_enabled' => ['0', 'boolean', 'Enable automated hosting renewal reminders'],
            'hosting_reminder_due_date' => ['', 'string', 'Next hosting renewal date (Y-m-d)'],
            'hosting_reminder_days_before' => ['7', 'integer', 'Days before due date to auto-send'],
            'hosting_reminder_email' => ['', 'string', 'Hosting reminder email recipients'],
            'hosting_reminder_phone' => ['', 'string', 'Hosting reminder SMS/WhatsApp number'],
            'hosting_reminder_channels' => ['email', 'string', 'Channels: email,sms,whatsapp'],
            'hosting_reminder_last_sent' => ['', 'string', 'Last auto hosting reminder date'],
            'payment_reminder_default_to' => ['', 'string', 'Default payment reminder email'],
            'payment_reminder_default_phone' => ['', 'string', 'Default payment reminder phone'],
        ] as $key => [$default, $type, $desc]) {
            Setting::firstOrCreate(
                ['key' => $key],
                [
                    'value' => $default,
                    'type' => $type,
                    'group' => 'reminders',
                    'description' => $desc,
                ]
            );
        }

        Setting::firstOrCreate(
            ['key' => 'avenque_ai_available'],
            [
                'value' => '0',
                'type' => 'boolean',
                'group' => 'ai',
                'description' => 'Software owner unlocks Avenque AI for this restaurant',
            ]
        );

        Setting::firstOrCreate(
            ['key' => 'gemini_api_key'],
            [
                'value' => '',
                'type' => 'string',
                'group' => 'ai',
                'description' => 'Google Gemini API key',
            ]
        );

        Setting::firstOrCreate(
            ['key' => 'gemini_model'],
            [
                'value' => 'gemini-2.0-flash',
                'type' => 'string',
                'group' => 'ai',
                'description' => 'Gemini model id',
            ]
        );

        Setting::firstOrCreate(
            ['key' => 'avenque_ai_enabled'],
            [
                'value' => '0',
                'type' => 'boolean',
                'group' => 'ai',
                'description' => 'Restaurant enables Avenque AI Agent chat',
            ]
        );

        foreach ([
            'cheque_management_enabled' => ['0', 'boolean', 'Enable supplier cheque management module'],
            'cheque_reminder_enabled' => ['0', 'boolean', 'Auto remind owner about cheque dues'],
            'cheque_reminder_days_before' => ['3', 'integer', 'Days before due date to start reminding'],
            'cheque_reminder_channels' => ['inapp,email', 'string', 'Channels: inapp,email,sms,whatsapp'],
            'cheque_reminder_email' => ['', 'string', 'Cheque reminder email recipients'],
            'cheque_reminder_phone' => ['', 'string', 'Cheque reminder phone'],
        ] as $key => [$default, $type, $desc]) {
            Setting::firstOrCreate(
                ['key' => $key],
                [
                    'value' => $default,
                    'type' => $type,
                    'group' => 'cheques',
                    'description' => $desc,
                ]
            );
        }

        foreach ([
            'loyalty_enabled' => ['0', 'boolean', 'Enable customer loyalty stamp cards'],
            'loyalty_stamps_required' => ['10', 'integer', 'Stamps needed for one free drink'],
            'loyalty_reward_label' => ['Free drink', 'string', 'Reward label on card and POS'],
            'loyalty_category_ids' => ['[]', 'string', 'JSON category IDs that earn stamps'],
            'loyalty_card_expiry_days' => ['365', 'integer', 'Days until digital stamp card expires (0 = never)'],
        ] as $key => [$default, $type, $desc]) {
            Setting::firstOrCreate(
                ['key' => $key],
                [
                    'value' => $default,
                    'type' => $type,
                    'group' => 'loyalty',
                    'description' => $desc,
                ]
            );
        }

        foreach ([
            'billiards_enabled' => ['0', 'boolean', 'Enable billiards booking module'],
            'billiards_end_alert_minutes' => ['10', 'integer', 'Minutes before end to alert desk'],
            'billiards_display_token' => ['', 'string', 'Optional public display token'],
            'billiards_auto_start_on_pay' => ['1', 'boolean', 'Auto-start session on Book & Pay'],
            'billiards_print_ask' => ['1', 'boolean', 'Ask to print 80mm after payment'],
            'billiards_sms_on_pay' => ['0', 'boolean', 'Offer eBill SMS after payment'],
        ] as $key => [$default, $type, $desc]) {
            Setting::firstOrCreate(
                ['key' => $key],
                [
                    'value' => $default,
                    'type' => $type,
                    'group' => 'billiards',
                    'description' => $desc,
                ]
            );
        }

        foreach ([
            'multi_branch_enabled' => ['0', 'boolean', 'Enable multi-branch module'],
            'max_branches' => ['2', 'integer', 'Maximum branches restaurant may create'],
        ] as $key => [$default, $type, $desc]) {
            Setting::firstOrCreate(
                ['key' => $key],
                [
                    'value' => $default,
                    'type' => $type,
                    'group' => 'branches',
                    'description' => $desc,
                ]
            );
        }

        foreach ([
            'smslenz_user_id' => ['', 'string', 'SMSLenz user id'],
            'smslenz_api_key' => ['', 'string', 'SMSLenz API key'],
            'smslenz_sender_id' => ['', 'string', 'SMSLenz sender id'],
        ] as $key => [$default, $type, $desc]) {
            Setting::firstOrCreate(
                ['key' => $key],
                [
                    'value' => $default,
                    'type' => $type,
                    'group' => 'notifications',
                    'description' => $desc,
                ]
            );
        }

        foreach ([
            'bartender_save_server' => ['1', 'boolean', 'Save barcode CSV to public/exports for BarTender batch download'],
            'bartender_download_browser' => ['1', 'boolean', 'Also download CSV in the browser on export'],
            'bartender_label_path' => ['C:\\Labels\\BarcodePrint.btw', 'string', 'BarTender .btw path on the print PC'],
            'bartender_show_open_btn' => ['0', 'boolean', 'Show Open BarTender hint on Print Labels page'],
        ] as $key => [$default, $type, $desc]) {
            Setting::firstOrCreate(
                ['key' => $key],
                [
                    'value' => $default,
                    'type' => $type,
                    'group' => 'bartender',
                    'description' => $desc,
                ]
            );
        }

        Setting::flushCache();
    }

    /** Software owner: download Local Print Bridge files for the Windows POS PC. */
    public function downloadPrintBridge(Request $request)
    {
        abort_unless($request->user()?->isSoftwareOwner(), 403);

        $dir = base_path('tools/print-bridge');
        $files = [
            'Start-Print-Bridge.bat',
            'Start-Print-Bridge-Hidden.vbs',
            'Start-Print-Bridge-Debug.bat',
            'Stop-Print-Bridge.bat',
            'Install-Print-Bridge-Startup.bat',
            'Uninstall-Print-Bridge-Startup.bat',
            'Print-Bridge.ps1',
            'README.txt',
        ];
        foreach ($files as $name) {
            abort_unless(is_file($dir.DIRECTORY_SEPARATOR.$name), 404, 'Print bridge file missing: '.$name);
        }

        // Helpers shipped later — include when present so the ZIP matches README.txt
        foreach (['Setup-Print-Bridge-Auto.bat'] as $name) {
            if (is_file($dir.DIRECTORY_SEPARATOR.$name)) {
                $files[] = $name;
            }
        }

        if (! class_exists(\ZipArchive::class)) {
            return response()->download($dir.DIRECTORY_SEPARATOR.'Start-Print-Bridge.bat', 'Start-Print-Bridge.bat');
        }

        $tmp = tempnam(sys_get_temp_dir(), 'qrposbridge');
        @unlink($tmp);
        $zipPath = $tmp.'.zip';
        $zip = new \ZipArchive;
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return response()->download($dir.DIRECTORY_SEPARATOR.'Start-Print-Bridge.bat', 'Start-Print-Bridge.bat');
        }
        foreach ($files as $name) {
            $zip->addFile($dir.DIRECTORY_SEPARATOR.$name, $name);
        }
        $zip->close();

        return response()->download($zipPath, 'QRPOS-Print-Bridge.zip')->deleteFileAfterSend(true);
    }
}
