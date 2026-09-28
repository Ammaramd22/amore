<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            // Business
            ['key' => 'company_name', 'value' => 'Sri Lankan Restaurant', 'type' => 'string', 'group' => 'business'],
            ['key' => 'company_address', 'value' => 'Colombo, Sri Lanka', 'type' => 'string', 'group' => 'business'],
            ['key' => 'company_phone', 'value' => '+94 11 234 5678', 'type' => 'string', 'group' => 'business'],
            ['key' => 'company_email', 'value' => 'info@restaurant.lk', 'type' => 'string', 'group' => 'business'],
            ['key' => 'company_logo', 'value' => '', 'type' => 'string', 'group' => 'business'],
            ['key' => 'invoice_logo', 'value' => '', 'type' => 'string', 'group' => 'business'],
            ['key' => 'receipt_footer', 'value' => 'Thank you for dining with us! Visit again.', 'type' => 'string', 'group' => 'business'],
            ['key' => 'currency_symbol', 'value' => 'LKR', 'type' => 'string', 'group' => 'business'],
            ['key' => 'currency_position', 'value' => 'before', 'type' => 'string', 'group' => 'business'],
            ['key' => 'timezone', 'value' => 'Asia/Colombo', 'type' => 'string', 'group' => 'business', 'description' => 'System timezone for POS receipts, KOT, and order timestamps'],

            // Tax
            ['key' => 'tax_enabled', 'value' => '0', 'type' => 'boolean', 'group' => 'tax', 'description' => 'Apply tax on POS bills'],
            ['key' => 'tax_rate', 'value' => '0', 'type' => 'float', 'group' => 'tax'],
            ['key' => 'tax_name', 'value' => 'VAT', 'type' => 'string', 'group' => 'tax'],
            ['key' => 'service_charge_rate', 'value' => '0', 'type' => 'float', 'group' => 'tax'],
            ['key' => 'service_charge_enabled', 'value' => '0', 'type' => 'boolean', 'group' => 'tax'],

            // Prefixes
            ['key' => 'invoice_prefix', 'value' => 'INV-', 'type' => 'string', 'group' => 'prefixes'],
            ['key' => 'invoice_reset_daily', 'value' => '1', 'type' => 'boolean', 'group' => 'prefixes', 'description' => 'Reset invoice number sequence every day'],
            ['key' => 'kot_prefix', 'value' => 'KOT-', 'type' => 'string', 'group' => 'prefixes'],
            ['key' => 'bot_prefix', 'value' => 'BOT-', 'type' => 'string', 'group' => 'prefixes'],
            ['key' => 'kot_reset_daily', 'value' => '1', 'type' => 'boolean', 'group' => 'prefixes', 'description' => 'Reset KOT/BOT number sequence every day'],
            ['key' => 'self_order_prefix', 'value' => 'SO-', 'type' => 'string', 'group' => 'prefixes'],

            // POS
            ['key' => 'pos_theme', 'value' => 'light', 'type' => 'string', 'group' => 'pos'],
            ['key' => 'pos_ui_mode', 'value' => 'restaurant', 'type' => 'string', 'group' => 'pos', 'description' => 'POS front UI: restaurant or bakery'],
            ['key' => 'shop_ui_picker_enabled', 'value' => '1', 'type' => 'boolean', 'group' => 'pos', 'description' => 'Ask Restaurant / Bakery / Ice Cream UI after login'],
            ['key' => 'pos_categories_per_row', 'value' => '6', 'type' => 'integer', 'group' => 'pos'],
            ['key' => 'pos_products_per_row', 'value' => '4', 'type' => 'integer', 'group' => 'pos'],
            ['key' => 'auto_print_receipt', 'value' => '0', 'type' => 'boolean', 'group' => 'pos'],
            ['key' => 'auto_print_kot', 'value' => '0', 'type' => 'boolean', 'group' => 'pos'],
            ['key' => 'pos_bridge_print_pending_kot', 'value' => '1', 'type' => 'boolean', 'group' => 'pos', 'description' => 'POS Print Bridge auto-prints waiter KOTs that never printed'],
            ['key' => 'print_ask_before', 'value' => '1', 'type' => 'boolean', 'group' => 'pos', 'description' => 'If enabled, show print preview. If disabled, send KOT/BOT silently to kitchen network printer IP'],
            ['key' => 'receipt_printer_ip', 'value' => '', 'type' => 'string', 'group' => 'pos'],
            ['key' => 'receipt_printer_port', 'value' => '9100', 'type' => 'integer', 'group' => 'pos'],
            ['key' => 'receipt_printer_name', 'value' => 'XP-80C', 'type' => 'string', 'group' => 'pos', 'description' => 'Windows printer queue name for Print Bridge'],
            ['key' => 'receipt_print_mode', 'value' => 'preview', 'type' => 'string', 'group' => 'pos', 'description' => 'direct = Local Print Bridge; preview = browser dialog'],
            ['key' => 'receipt_print_bridge_url', 'value' => 'http://127.0.0.1:18181', 'type' => 'string', 'group' => 'pos', 'description' => 'Local Print Bridge URL on POS PC'],
            ['key' => 'open_drawer_after_print', 'value' => '1', 'type' => 'boolean', 'group' => 'pos', 'description' => 'Open cash drawer after each direct receipt print'],
            ['key' => 'cash_drawer_pin', 'value' => '0', 'type' => 'integer', 'group' => 'pos'],
            ['key' => 'shortcut_focus_search', 'value' => 'F2', 'type' => 'string', 'group' => 'pos'],
            ['key' => 'shortcut_place_order', 'value' => 'F6', 'type' => 'string', 'group' => 'pos'],
            ['key' => 'shortcut_pay_now', 'value' => 'F7', 'type' => 'string', 'group' => 'pos'],
            ['key' => 'shortcut_open_bills', 'value' => 'F8', 'type' => 'string', 'group' => 'pos'],
            ['key' => 'table_selection_required', 'value' => '0', 'type' => 'boolean', 'group' => 'pos', 'description' => 'Require selecting a table for dine-in orders before payment'],
            ['key' => 'show_screen_numbers_keyboard', 'value' => '1', 'type' => 'boolean', 'group' => 'pos', 'description' => 'Show the on-screen numeric keypad in the POS cart'],
            ['key' => 'shift_method', 'value' => 'shift', 'type' => 'string', 'group' => 'pos', 'description' => 'shift = per cashier, day_end = store-wide day'],

            // Bakery UI (software owner)
            ['key' => 'bakery_show_dine_in', 'value' => '1', 'type' => 'boolean', 'group' => 'bakery', 'description' => 'Bakery UI: show Dine-in'],
            ['key' => 'bakery_show_delivery', 'value' => '0', 'type' => 'boolean', 'group' => 'bakery', 'description' => 'Bakery UI: show Delivery'],
            ['key' => 'bakery_show_express', 'value' => '0', 'type' => 'boolean', 'group' => 'bakery', 'description' => 'Bakery UI: show Express'],
            ['key' => 'bakery_disable_kot', 'value' => '0', 'type' => 'boolean', 'group' => 'bakery', 'description' => 'Bakery UI: no KOT/BOT'],
            ['key' => 'bakery_direct_billing', 'value' => '0', 'type' => 'boolean', 'group' => 'bakery', 'description' => 'Bakery UI: pay & finish — no Place Order / tables'],
            ['key' => 'bakery_show_cart_display', 'value' => '1', 'type' => 'boolean', 'group' => 'bakery', 'description' => 'Bakery UI: show Cart display'],
            ['key' => 'bakery_show_status_display', 'value' => '0', 'type' => 'boolean', 'group' => 'bakery', 'description' => 'Bakery UI: show Status display'],
            ['key' => 'bakery_show_orders_display', 'value' => '0', 'type' => 'boolean', 'group' => 'bakery', 'description' => 'Bakery UI: show Orders display'],

            // Email / SMTP + end-of-day reports
            ['key' => 'day_end_email_enabled', 'value' => '0', 'type' => 'boolean', 'group' => 'email'],
            ['key' => 'day_end_email_to', 'value' => '', 'type' => 'string', 'group' => 'email'],
            ['key' => 'mail_mailer', 'value' => 'log', 'type' => 'string', 'group' => 'email'],
            ['key' => 'mail_host', 'value' => '', 'type' => 'string', 'group' => 'email'],
            ['key' => 'mail_port', 'value' => '587', 'type' => 'string', 'group' => 'email'],
            ['key' => 'mail_username', 'value' => '', 'type' => 'string', 'group' => 'email'],
            ['key' => 'mail_password', 'value' => '', 'type' => 'string', 'group' => 'email'],
            ['key' => 'mail_encryption', 'value' => 'tls', 'type' => 'string', 'group' => 'email'],
            ['key' => 'mail_from_address', 'value' => '', 'type' => 'string', 'group' => 'email'],
            ['key' => 'mail_from_name', 'value' => 'QRPOS', 'type' => 'string', 'group' => 'email'],

            // Waiter panel
            ['key' => 'waiter_show_direct_items', 'value' => '1', 'type' => 'boolean', 'group' => 'waiter', 'description' => 'Show direct/retail categories on waiter panel'],
            ['key' => 'bring_bill_enabled', 'value' => '1', 'type' => 'boolean', 'group' => 'waiter', 'description' => 'ON = waiter Bring Bill → POS Pay Bills'],
            ['key' => 'waiter_rating_enabled', 'value' => '1', 'type' => 'boolean', 'group' => 'waiter', 'description' => 'ON = Rate tab and guest rating prompts'],

            // PWA / Waiter installable app
            ['key' => 'pwa_app_name', 'value' => 'QRPOS Waiter Panel', 'type' => 'string', 'group' => 'pwa'],
            ['key' => 'pwa_app_short_name', 'value' => 'QRPOS Waiter', 'type' => 'string', 'group' => 'pwa'],
            ['key' => 'pwa_app_logo', 'value' => '', 'type' => 'string', 'group' => 'pwa'],
            ['key' => 'pwa_splash_image', 'value' => '', 'type' => 'string', 'group' => 'pwa'],
            ['key' => 'pwa_theme_color', 'value' => '#1c1410', 'type' => 'string', 'group' => 'pwa'],
            ['key' => 'pwa_background_color', 'value' => '#1c1410', 'type' => 'string', 'group' => 'pwa'],

            // Kitchen
            ['key' => 'kitchen_display_enabled', 'value' => '1', 'type' => 'boolean', 'group' => 'kitchen', 'description' => 'Show kitchen display links and allow kitchen display screen'],
            ['key' => 'kot_confirmation_enabled', 'value' => '1', 'type' => 'boolean', 'group' => 'kitchen', 'description' => 'ON = Accept/Preparing/Ready/Serve. OFF = print KOT only'],
            ['key' => 'kitchen_auto_accept', 'value' => '0', 'type' => 'boolean', 'group' => 'kitchen', 'description' => 'When confirmation ON, start tickets as Preparing'],
            ['key' => 'kitchen_sound_alert', 'value' => '1', 'type' => 'boolean', 'group' => 'kitchen'],
            ['key' => 'kitchen_alert_interval', 'value' => '30', 'type' => 'integer', 'group' => 'kitchen'],
            ['key' => 'waiter_sound_alert', 'value' => '1', 'type' => 'boolean', 'group' => 'kitchen', 'description' => 'Bell when kitchen marks order ready (waiter tablet)'],
            ['key' => 'pos_ready_sound_alert', 'value' => '1', 'type' => 'boolean', 'group' => 'kitchen', 'description' => 'Bell when kitchen marks order ready (POS cashier)'],
            ['key' => 'customer_display_ready_seconds', 'value' => '120', 'type' => 'integer', 'group' => 'kitchen', 'description' => 'Seconds READY stays on customer status board (0 = until Serve)'],

            // QR Menu
            ['key' => 'qr_menu_enabled', 'value' => '1', 'type' => 'boolean', 'group' => 'qr'],
            ['key' => 'qr_menu_theme', 'value' => 'amber', 'type' => 'string', 'group' => 'qr'],
            ['key' => 'qr_order_approval', 'value' => '1', 'type' => 'boolean', 'group' => 'qr'],
            ['key' => 'qr_show_prices', 'value' => '1', 'type' => 'boolean', 'group' => 'qr'],

            // Notifications
            ['key' => 'sms_enabled', 'value' => '0', 'type' => 'boolean', 'group' => 'notifications'],
            ['key' => 'sms_provider', 'value' => 'notify_lk', 'type' => 'string', 'group' => 'notifications'],
            ['key' => 'notify_lk_api_key', 'value' => '', 'type' => 'string', 'group' => 'notifications'],
            ['key' => 'notify_lk_sender_id', 'value' => '', 'type' => 'string', 'group' => 'notifications'],
            ['key' => 'smslenz_user_id', 'value' => '', 'type' => 'string', 'group' => 'notifications'],
            ['key' => 'smslenz_api_key', 'value' => '', 'type' => 'string', 'group' => 'notifications'],
            ['key' => 'smslenz_sender_id', 'value' => '', 'type' => 'string', 'group' => 'notifications'],
            ['key' => 'whatsapp_enabled', 'value' => '0', 'type' => 'boolean', 'group' => 'notifications'],
            ['key' => 'meta_wa_access_token', 'value' => '', 'type' => 'string', 'group' => 'notifications'],
            ['key' => 'meta_wa_phone_number_id', 'value' => '', 'type' => 'string', 'group' => 'notifications'],
            ['key' => 'meta_wa_api_version', 'value' => 'v22.0', 'type' => 'string', 'group' => 'notifications'],
            ['key' => 'meta_wa_template_name', 'value' => '', 'type' => 'string', 'group' => 'notifications'],
            ['key' => 'meta_wa_template_language', 'value' => 'en', 'type' => 'string', 'group' => 'notifications'],
        ];

        foreach ($settings as $setting) {
            $row = Setting::firstOrNew(['key' => $setting['key']]);
            if (!$row->exists) {
                $row->fill($setting);
            } else {
                $row->group = $setting['group'];
                if (empty($row->type)) {
                    $row->type = $setting['type'];
                }
                if (!empty($setting['description']) && empty($row->description)) {
                    $row->description = $setting['description'];
                }
            }
            $row->save();
        }
    }
}
