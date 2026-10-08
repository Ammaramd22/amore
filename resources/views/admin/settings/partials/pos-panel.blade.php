@php
    $byKey = collect($items)->keyBy('key');
    $posTabs = [
        'interface' => [
            'label' => 'Interface',
            'icon' => 'fa-desktop',
            'keys' => [
                'pos_ui_mode', 'shop_ui_picker_enabled', 'pos_theme',
                'pos_categories_per_row', 'pos_products_per_row',
                'table_selection_required', 'show_screen_numbers_keyboard',
            ],
        ],
        'printing' => [
            'label' => 'Printing',
            'icon' => 'fa-print',
            'keys' => [
                'auto_print_receipt', 'auto_print_kot', 'pos_bridge_print_pending_kot', 'print_ask_before',
                'receipt_print_mode', 'receipt_print_bridge_url',
                'receipt_printer_ip', 'receipt_printer_port', 'receipt_printer_name',
            ],
        ],
        'drawer' => [
            'label' => 'Cash Drawer',
            'icon' => 'fa-cash-register',
            'keys' => [
                'cash_drawer_pin', 'open_drawer_after_print',
            ],
        ],
        'shortcuts' => [
            'label' => 'Shortcuts',
            'icon' => 'fa-keyboard',
            'keys' => [
                'shortcut_focus_search', 'shortcut_place_order',
                'shortcut_pay_now', 'shortcut_open_bills',
            ],
        ],
        'shifts' => [
            'label' => 'Shifts',
            'icon' => 'fa-clock',
            'keys' => [
                'shift_method', 'business_day_cutoff',
            ],
        ],
        'display' => [
            'label' => 'Customer Display',
            'icon' => 'fa-tv',
            'keys' => [
                'customer_display_mode', 'customer_display_protocol', 'customer_display_baud',
            ],
        ],
        'expiry' => [
            'label' => 'Expiry',
            'icon' => 'fa-calendar-check',
            'keys' => [
                'expiry_remind_days',
            ],
        ],
    ];
@endphp

<div class="pos-shared-tabs" id="posSharedTabs">
    <div class="pos-shared-tablist" role="tablist" aria-label="POS setting groups">
        @foreach($posTabs as $tabSlug => $tab)
            @php
                $tabItems = collect($tab['keys'])->map(fn ($k) => $byKey->get($k))->filter()->values();
            @endphp
            @if($tabItems->isNotEmpty())
            <button type="button"
                    class="pos-shared-tab {{ $loop->first ? 'active' : '' }}"
                    role="tab"
                    aria-selected="{{ $loop->first ? 'true' : 'false' }}"
                    data-pos-tab="{{ $tabSlug }}"
                    onclick="switchPosSharedTab('{{ $tabSlug }}', this)">
                <i class="fas {{ $tab['icon'] }}"></i>
                <span>{{ $tab['label'] }}</span>
            </button>
            @endif
        @endforeach
    </div>

    @foreach($posTabs as $tabSlug => $tab)
        @php
            $tabItems = collect($tab['keys'])->map(fn ($k) => $byKey->get($k))->filter()->values();
        @endphp
        @if($tabItems->isNotEmpty())
        <div class="pos-shared-pane {{ $loop->first ? 'active' : '' }}"
             id="pos-shared-pane-{{ $tabSlug }}"
             role="tabpanel"
             data-pos-tab="{{ $tabSlug }}">
            @include('admin.settings.partials.setting-fields', [
                'items' => $tabItems,
                'labels' => $labels,
                'hints' => $hints,
                'bakeryCategories' => $bakeryCategories ?? [],
            ])

            @if($tabSlug === 'printing')
            <div class="print-bridge-card mt-3 {{ (\App\Models\Setting::get('receipt_print_mode', 'preview') === 'direct') ? '' : 'd-none' }}" id="printBridgeCard">
                <div class="alert alert-warning border-0 mb-0" style="background:#fff7ed;border:1px solid #fed7aa !important;border-radius:12px;">
                    <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-2">
                        <div>
                            <div class="fw-semibold text-dark"><i class="fas fa-print me-1 text-warning"></i>Local Print Bridge <span class="badge bg-dark">Direct print</span></div>
                            <div class="text-muted small">Download and run on the Windows POS PC so Direct receipts print to XP-80C (USB or Ethernet) without a browser dialog.</div>
                        </div>
                    </div>
                    <ol class="small mb-2 ps-3 text-dark">
                        <li>Download <strong>QRPOS-Print-Bridge.zip</strong> (do not open .bat in the browser).</li>
                        <li>Extract on the POS PC and double-click <code>Start-Print-Bridge.bat</code>.</li>
                        <li>Optional: run <code>Install-Print-Bridge-Startup.bat</code> so it starts at Windows login.</li>
                        <li>Windows printer name must match <strong>Receipt Printer Name</strong> (e.g. XP-80C).</li>
                    </ol>
                    <div class="d-flex flex-wrap gap-2">
                        @if(!empty($isOwner))
                        <a class="btn btn-sm btn-dark" href="{{ route('settings.download-print-bridge') }}">
                            <i class="fas fa-download me-1"></i>Download Print Bridge ZIP
                        </a>
                        @else
                        <span class="small text-muted align-self-center">Ask the software owner to download the Print Bridge ZIP.</span>
                        @endif
                        <button type="button" class="btn btn-sm btn-outline-dark" id="btnCheckPrintBridge">
                            <i class="fas fa-heartbeat me-1"></i>Check Bridge
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="btnProbePrinter">
                            <i class="fas fa-plug me-1"></i>Probe 9100
                        </button>
                    </div>
                    <div id="printBridgeStatus" class="small mt-2 text-muted"></div>
                </div>
            </div>
            @endif
        </div>
        @endif
    @endforeach
</div>
