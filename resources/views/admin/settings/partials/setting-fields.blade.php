<div class="row g-3 settings-fields">
                    @foreach($items as $setting)
                    @php
                        $label = $labels[$setting->key] ?? ucwords(str_replace('_', ' ', $setting->key));
                        $hint = $hints[$setting->key] ?? $setting->description;
                        $fieldCol = $setting->key === 'bakery_category_ids' ? 'col-md-12 col-xl-8' : 'col-md-6 col-xl-4';
                    @endphp
                    <div class="{{ $fieldCol }}">
                        <label class="settings-label" for="field-{{ $setting->key }}">
                            {{ $label }}
                            @if($hint)
                                <i class="fas fa-info-circle settings-hint-icon" title="{{ $hint }}"></i>
                            @endif
                        </label>

                        @if($setting->type === 'boolean')
                            <div class="settings-switch-wrap">
                                <input type="hidden" name="{{ $setting->key }}" value="0">
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input" type="checkbox"
                                           name="{{ $setting->key }}" value="1"
                                           id="field-{{ $setting->key }}"
                                           {{ $setting->value ? 'checked' : '' }}>
                                    <label class="form-check-label" for="field-{{ $setting->key }}">
                                        {{ $setting->value ? 'Enabled' : 'Disabled' }}
                                    </label>
                                </div>
                            </div>
                        @elseif(in_array($setting->key, ['currency_position'], true))
                            <select name="{{ $setting->key }}" id="field-{{ $setting->key }}" class="form-select">
                                <option value="before" @selected($setting->value === 'before')>Before amount</option>
                                <option value="after" @selected($setting->value === 'after')>After amount</option>
                            </select>
                        @elseif(in_array($setting->key, ['shortcut_focus_search', 'shortcut_place_order', 'shortcut_pay_now', 'shortcut_open_bills'], true))
                            <select name="{{ $setting->key }}" id="field-{{ $setting->key }}" class="form-select">
                                <option value="">None</option>
                                @foreach(range(1, 12) as $n)
                                    @php $fkey = 'F'.$n; @endphp
                                    <option value="{{ $fkey }}" @selected((string) $setting->value === $fkey)>{{ $fkey }}</option>
                                @endforeach
                            </select>
                        @elseif($setting->key === 'pos_ui_mode')
                            <select name="{{ $setting->key }}" id="field-{{ $setting->key }}" class="form-select">
                                <option value="restaurant" @selected(($setting->value ?: 'restaurant') === 'restaurant')>Restaurant UI — tables, KOT, open bills</option>
                                <option value="bakery" @selected($setting->value === 'bakery')>Bakery UI — categories · products · fast cart</option>
                                <option value="ice_cream" @selected($setting->value === 'ice_cream')>Ice Cream UI — 2-col categories · 4-col products · tablet cart</option>
                            </select>
                        @elseif($setting->key === 'receipt_print_mode')
                            <select name="{{ $setting->key }}" id="field-{{ $setting->key }}" class="form-select" onchange="togglePrintBridgeCard()">
                                <option value="direct" @selected(($setting->value ?: 'preview') === 'direct')>Direct — Print Bridge / XP-80C (no preview)</option>
                                <option value="preview" @selected(($setting->value ?: 'preview') === 'preview')>Preview — browser print dialog</option>
                            </select>
                        @elseif($setting->key === 'customer_display_mode')
                            <select name="{{ $setting->key }}" id="field-{{ $setting->key }}" class="form-select">
                                <option value="digital" @selected(($setting->value ?: 'digital') === 'digital')>Digital — TV / second monitor cart display</option>
                                <option value="analog" @selected($setting->value === 'analog')>Analog — physical rear LED on POS (COM / USB)</option>
                            </select>
                        @elseif($setting->key === 'customer_display_protocol')
                            <select name="{{ $setting->key }}" id="field-{{ $setting->key }}" class="form-select">
                                <option value="plain" @selected(($setting->value ?: 'plain') === 'plain')>Plain amount (rear LED showing 0.00)</option>
                                <option value="escpos" @selected($setting->value === 'escpos')>ESC/POS (2-line VFD)</option>
                                <option value="dsp800" @selected($setting->value === 'dsp800')>DSP-800 (2-line pole)</option>
                            </select>
                        @elseif($setting->key === 'customer_display_baud')
                            <select name="{{ $setting->key }}" id="field-{{ $setting->key }}" class="form-select">
                                @foreach([2400, 4800, 9600, 19200, 38400, 115200] as $baud)
                                    <option value="{{ $baud }}" @selected((int) ($setting->value ?: 9600) === $baud)>{{ $baud }}</option>
                                @endforeach
                            </select>
                        @elseif($setting->key === 'bakery_category_ids')
                            @php
                                $bakerySelectedIds = json_decode((string) $setting->value, true);
                                if (! is_array($bakerySelectedIds)) {
                                    $bakerySelectedIds = [];
                                }
                                $bakerySelectedIds = array_map('intval', $bakerySelectedIds);
                            @endphp
                            <input type="hidden" name="_bakery_category_ids_form" value="1">
                            <select name="bakery_category_ids[]"
                                    id="field-{{ $setting->key }}"
                                    class="form-select"
                                    multiple
                                    size="8">
                                @foreach(($bakeryCategories ?? []) as $cat)
                                    <option value="{{ $cat->id }}" @selected(in_array((int) $cat->id, $bakerySelectedIds, true))>
                                        {{ $cat->name }}{{ $cat->type ? ' ('.$cat->type.')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">Hold Ctrl/Cmd to select multiple. Only selected categories appear in Bakery POS. Empty = show all.</div>
                        @elseif($setting->key === 'sms_provider')
                            <select name="{{ $setting->key }}" id="field-{{ $setting->key }}" class="form-select">
                                <option value="notify_lk" @selected(($setting->value ?: 'notify_lk') === 'notify_lk')>Notify.lk</option>
                                <option value="smslenz" @selected($setting->value === 'smslenz')>SMSLenz</option>
                            </select>
                        @elseif(in_array($setting->key, ['pos_theme'], true))
                            <select name="{{ $setting->key }}" id="field-{{ $setting->key }}" class="form-select">
                                <option value="light" @selected($setting->value === 'light')>Light</option>
                                <option value="dark" @selected($setting->value === 'dark')>Dark</option>
                            </select>
                        @elseif($setting->key === 'shift_method')
                            <select name="{{ $setting->key }}" id="field-{{ $setting->key }}" class="form-select">
                                <option value="shift" @selected($setting->value === 'shift')>Shift (per cashier)</option>
                                <option value="day_end" @selected($setting->value === 'day_end')>Day End (store-wide)</option>
                            </select>
                        @elseif($setting->key === 'business_day_cutoff')
                            <input type="time"
                                   name="{{ $setting->key }}"
                                   id="field-{{ $setting->key }}"
                                   class="form-control"
                                   value="{{ $setting->value ?: '22:00' }}">
                        @elseif($setting->key === 'timezone')
                            @php
                                $tzValue = (string) ($setting->value ?: \App\Models\Setting::timezone());
                                $tzList = timezone_identifiers_list();
                            @endphp
                            <select name="{{ $setting->key }}" id="field-{{ $setting->key }}" class="form-select">
                                @foreach($tzList as $tz)
                                    <option value="{{ $tz }}" @selected($tzValue === $tz)>{{ $tz }}</option>
                                @endforeach
                            </select>
                        @elseif($setting->key === 'mail_mailer')
                            <select name="{{ $setting->key }}" id="field-{{ $setting->key }}" class="form-select">
                                <option value="smtp" @selected($setting->value === 'smtp')>SMTP</option>
                                <option value="log" @selected($setting->value === 'log')>Log (testing)</option>
                                <option value="sendmail" @selected($setting->value === 'sendmail')>Sendmail</option>
                            </select>
                        @elseif($setting->key === 'mail_encryption')
                            <select name="{{ $setting->key }}" id="field-{{ $setting->key }}" class="form-select">
                                <option value="tls" @selected($setting->value === 'tls')>TLS</option>
                                <option value="ssl" @selected($setting->value === 'ssl')>SSL</option>
                                <option value="none" @selected(in_array($setting->value, ['none', '', null], true))>None</option>
                            </select>
                        @elseif($setting->key === 'mail_password' || $setting->key === 'gemini_api_key')
                            <input type="password"
                                   name="{{ $setting->key }}"
                                   id="field-{{ $setting->key }}"
                                   class="form-control"
                                   value="{{ $setting->value }}"
                                   autocomplete="new-password">
                        @elseif($setting->key === 'gemini_model')
                            <select name="{{ $setting->key }}" id="field-{{ $setting->key }}" class="form-select">
                                <option value="gemini-2.0-flash" @selected($setting->value === 'gemini-2.0-flash')>gemini-2.0-flash (recommended)</option>
                                <option value="gemini-2.0-flash-lite" @selected($setting->value === 'gemini-2.0-flash-lite')>gemini-2.0-flash-lite</option>
                                <option value="gemini-1.5-flash" @selected($setting->value === 'gemini-1.5-flash')>gemini-1.5-flash</option>
                                <option value="gemini-1.5-pro" @selected($setting->value === 'gemini-1.5-pro')>gemini-1.5-pro</option>
                            </select>
                        @elseif($setting->key === 'qr_menu_theme')
                            <select name="{{ $setting->key }}" id="field-{{ $setting->key }}" class="form-select">
                                <option value="amber" @selected(in_array($setting->value, ['amber', 'modern'], true))>Amber Spice — warm restaurant</option>
                                <option value="ocean" @selected($setting->value === 'ocean')>Ocean Fresh — teal &amp; clean</option>
                                <option value="luxe" @selected($setting->value === 'luxe')>Night Luxe — dark &amp; gold</option>
                            </select>
                        @elseif($setting->key === 'company_logo')
                            @php $logoPreview = \App\Models\Setting::logoUrl(); @endphp
                            <div class="d-flex align-items-center gap-3 mb-2">
                                <div style="width:64px;height:64px;border-radius:14px;overflow:hidden;background:#1c1917;display:grid;place-items:center;border:1px solid #e7e5e4;">
                                    @if($logoPreview)
                                        <img src="{{ $logoPreview }}" alt="Logo" style="width:100%;height:100%;object-fit:cover;">
                                    @else
                                        <i class="fas fa-utensils" style="color:#f59e0b;"></i>
                                    @endif
                                </div>
                                <div class="flex-grow-1">
                                    <input type="file"
                                           name="company_logo_file"
                                           id="field-company_logo_file"
                                           class="form-control"
                                           accept="image/png,image/jpeg,image/webp,image/gif">
                                    <input type="hidden" name="company_logo" value="{{ $setting->value }}">
                                </div>
                            </div>
                            @if($logoPreview)
                            <div class="form-check mb-1">
                                <input type="checkbox" name="clear_company_logo" value="1" class="form-check-input" id="clear_company_logo">
                                <label class="form-check-label" for="clear_company_logo">Remove logo</label>
                            </div>
                            @endif
                            <div class="form-text">{{ $hint ?: 'Used on login and sidebar.' }}</div>
                        @elseif($setting->key === 'invoice_logo')
                            @php $invoiceLogoPreview = \App\Models\Setting::logoUrlFor('invoice_logo') ?? \App\Models\Setting::logoUrl(); @endphp
                            <div class="d-flex align-items-center gap-3 mb-2">
                                <div style="width:64px;height:64px;border-radius:14px;overflow:hidden;background:#fff;display:grid;place-items:center;border:1px solid #e7e5e4;">
                                    @if($invoiceLogoPreview)
                                        <img src="{{ $invoiceLogoPreview }}" alt="Invoice logo" style="width:100%;height:100%;object-fit:contain;padding:4px;">
                                    @else
                                        <i class="fas fa-receipt" style="color:#a8a29e;"></i>
                                    @endif
                                </div>
                                <div class="flex-grow-1">
                                    <input type="file"
                                           name="invoice_logo_file"
                                           id="field-invoice_logo_file"
                                           class="form-control"
                                           accept="image/png,image/jpeg,image/webp,image/gif">
                                    <input type="hidden" name="invoice_logo" value="{{ $setting->value }}">
                                </div>
                            </div>
                            @if(\App\Models\Setting::logoUrlFor('invoice_logo'))
                            <div class="form-check mb-1">
                                <input type="checkbox" name="clear_invoice_logo" value="1" class="form-check-input" id="clear_invoice_logo">
                                <label class="form-check-label" for="clear_invoice_logo">Remove invoice logo</label>
                            </div>
                            @endif
                            <div class="form-text">{{ $hint ?: 'Printed on invoices above company name.' }}</div>
                        @elseif($setting->key === 'pwa_app_logo')
                            @php $pwaLogoPreview = \App\Models\Setting::logoUrlFor('pwa_app_logo') ?? \App\Models\Setting::logoUrl() ?? asset('pwa/waiter/icon-192.png'); @endphp
                            <div class="d-flex align-items-center gap-3 mb-2">
                                <div style="width:72px;height:72px;border-radius:16px;overflow:hidden;background:#1c1917;display:grid;place-items:center;border:1px solid #e7e5e4;">
                                    @if($pwaLogoPreview)
                                        <img src="{{ $pwaLogoPreview }}" alt="PWA logo" style="width:100%;height:100%;object-fit:contain;">
                                    @else
                                        <i class="fas fa-mobile-screen-button" style="color:#f59e0b;"></i>
                                    @endif
                                </div>
                                <div class="flex-grow-1">
                                    <input type="file"
                                           name="pwa_app_logo_file"
                                           id="field-pwa_app_logo_file"
                                           class="form-control"
                                           accept="image/png,image/jpeg,image/webp,image/gif">
                                    <input type="hidden" name="pwa_app_logo" value="{{ $setting->value }}">
                                </div>
                            </div>
                            @if(\App\Models\Setting::logoUrlFor('pwa_app_logo'))
                            <div class="form-check mb-1">
                                <input type="checkbox" name="clear_pwa_app_logo" value="1" class="form-check-input" id="clear_pwa_app_logo">
                                <label class="form-check-label" for="clear_pwa_app_logo">Remove app logo</label>
                            </div>
                            @endif
                            <div class="form-text">{{ $hint }}</div>
                        @elseif($setting->key === 'pwa_splash_image')
                            @php $pwaSplashPreview = \App\Models\Setting::logoUrlFor('pwa_splash_image'); @endphp
                            <div class="d-flex align-items-center gap-3 mb-2">
                                <div style="width:72px;height:120px;border-radius:14px;overflow:hidden;background:#1c1917;display:grid;place-items:center;border:1px solid #e7e5e4;">
                                    @if($pwaSplashPreview)
                                        <img src="{{ $pwaSplashPreview }}" alt="Splash" style="width:100%;height:100%;object-fit:cover;">
                                    @else
                                        <i class="fas fa-image" style="color:#a8a29e;"></i>
                                    @endif
                                </div>
                                <div class="flex-grow-1">
                                    <input type="file"
                                           name="pwa_splash_image_file"
                                           id="field-pwa_splash_image_file"
                                           class="form-control"
                                           accept="image/png,image/jpeg,image/webp,image/gif">
                                    <input type="hidden" name="pwa_splash_image" value="{{ $setting->value }}">
                                </div>
                            </div>
                            @if($pwaSplashPreview)
                            <div class="form-check mb-1">
                                <input type="checkbox" name="clear_pwa_splash_image" value="1" class="form-check-input" id="clear_pwa_splash_image">
                                <label class="form-check-label" for="clear_pwa_splash_image">Remove splash image</label>
                            </div>
                            @endif
                            <div class="form-text">{{ $hint }}</div>
                        @elseif(in_array($setting->key, ['pwa_theme_color', 'pwa_background_color'], true))
                            <div class="d-flex align-items-center gap-2">
                                <input type="color"
                                       id="field-{{ $setting->key }}-picker"
                                       value="{{ preg_match('/^#[0-9A-Fa-f]{6}$/', (string) $setting->value) ? $setting->value : '#1c1410' }}"
                                       class="form-control form-control-color"
                                       style="width:52px;height:42px;padding:4px;"
                                       oninput="document.getElementById('field-{{ $setting->key }}').value=this.value">
                                <input type="text"
                                       name="{{ $setting->key }}"
                                       id="field-{{ $setting->key }}"
                                       class="form-control"
                                       value="{{ $setting->value }}"
                                       pattern="^#?[0-9A-Fa-f]{3,8}$"
                                       placeholder="#1c1410">
                            </div>
                        @elseif($setting->type === 'integer' || $setting->type === 'float')
                            <input type="number"
                                   step="{{ $setting->type === 'float' ? '0.01' : '1' }}"
                                   min="0"
                                   name="{{ $setting->key }}"
                                   id="field-{{ $setting->key }}"
                                   class="form-control"
                                   value="{{ $setting->value }}">
                        @else
                            <input type="text"
                                   name="{{ $setting->key }}"
                                   id="field-{{ $setting->key }}"
                                   class="form-control"
                                   value="{{ $setting->value }}">
                        @endif

                        @if($hint && $setting->type !== 'boolean' && ! in_array($setting->key, ['company_logo', 'invoice_logo', 'pwa_app_logo', 'pwa_splash_image', 'bakery_category_ids'], true))
                            <div class="form-text">{{ $hint }}</div>
                        @endif
                    </div>
                    @endforeach
                </div>
