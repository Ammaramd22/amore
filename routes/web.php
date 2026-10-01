<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\CategoryController;
use App\Http\Controllers\Admin\SubcategoryController;
use App\Http\Controllers\Admin\AddonController;
use App\Http\Controllers\Admin\AddonGroupController;
use App\Http\Controllers\Admin\ProductController;
use App\Http\Controllers\Admin\IngredientController;
use App\Http\Controllers\Admin\RecipeController;
use App\Http\Controllers\Admin\CustomerController;
use App\Http\Controllers\Admin\SupplierController;
use App\Http\Controllers\Admin\PurchaseController;
use App\Http\Controllers\Admin\ChequeController;
use App\Http\Controllers\Admin\BilliardBookingController;
use App\Http\Controllers\Admin\BilliardTableController;
use App\Http\Controllers\Admin\BilliardReportController;
use App\Http\Controllers\BilliardDisplayController;
use App\Http\Controllers\Admin\LoyaltyController;
use App\Http\Controllers\LoyaltyCardController;
use App\Http\Controllers\Admin\FloorController;
use App\Http\Controllers\Admin\TableController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Admin\InventoryController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\GuideController;
use App\Http\Controllers\Admin\InboxNotificationController;
use App\Http\Controllers\Admin\NotificationController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\BarcodeLabelController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\BranchController;
use App\Http\Controllers\Admin\ExpenseController;
use App\Http\Controllers\Admin\AccountController;
use App\Http\Controllers\Admin\DeliveryPartnerController;
use App\Http\Controllers\Admin\DeliveryPartnerLedgerController;
use App\Http\Controllers\Pos\PosController;
use App\Http\Controllers\Pos\PosApiController;
use App\Http\Controllers\Admin\KitchenController as AdminKitchenController;
use App\Http\Controllers\Kitchen\KitchenController as KitchenPanelController;
use App\Http\Controllers\Kitchen\KdsController;
use App\Http\Controllers\Kitchen\BarController;
use App\Http\Controllers\Kitchen\CustomerDisplayController;
use App\Http\Controllers\Qr\QrMenuController;
use App\Http\Controllers\SelfOrder\SelfOrderController;
use App\Http\Controllers\Waiter\WaiterController;
use App\Http\Controllers\GuestRatingController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ShopUiController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LoginController::class, 'showLoginForm'])->name('home');

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/login/pin', [LoginController::class, 'loginPin'])->name('login.pin');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// QRPOS Waiter Panel PWA manifest (dynamic from Settings → PWA App)
Route::get('/manifest-waiter.webmanifest', function () {
    $pwa = \App\Models\Setting::pwaConfig();
    $iconPath = public_path('pwa/waiter/icon-192.png');
    $v = is_file($iconPath) ? (string) filemtime($iconPath) : (string) time();
    $icon = fn (string $file, string $sizes, string $purpose = 'any') => [
        'src' => url('/pwa/waiter/'.$file).'?v='.$v,
        'sizes' => $sizes,
        'type' => 'image/png',
        'purpose' => $purpose,
    ];

    $manifest = [
        'id' => '/waiter/dashboard',
        'name' => $pwa['name'],
        'short_name' => $pwa['short_name'],
        'description' => $pwa['name'].' — QRPOS By Avenque waiter floor panel.',
        'start_url' => '/waiter/dashboard?source=pwa',
        'scope' => '/waiter/',
        'display' => 'standalone',
        'orientation' => 'portrait-primary',
        'background_color' => $pwa['background_color'],
        'theme_color' => $pwa['theme_color'],
        'lang' => 'en',
        'categories' => ['business', 'food', 'productivity'],
        'icons' => [
            $icon('icon-96.png', '96x96'),
            $icon('icon-192.png', '192x192'),
            $icon('icon-512.png', '512x512'),
            $icon('maskable-512.png', '512x512', 'maskable'),
        ],
        'shortcuts' => [
            [
                'name' => 'Dashboard',
                'short_name' => 'Home',
                'url' => '/waiter/dashboard?view=home',
                'icons' => [['src' => '/pwa/waiter/icon-96.png', 'sizes' => '96x96', 'type' => 'image/png']],
            ],
            [
                'name' => 'Kitchen ready',
                'short_name' => 'Kitchen',
                'url' => '/waiter/dashboard?view=kitchen',
                'icons' => [['src' => '/pwa/waiter/icon-96.png', 'sizes' => '96x96', 'type' => 'image/png']],
            ],
            [
                'name' => 'Order panel',
                'short_name' => 'Orders',
                'url' => '/waiter/panel',
                'icons' => [['src' => '/pwa/waiter/icon-96.png', 'sizes' => '96x96', 'type' => 'image/png']],
            ],
        ],
        'prefer_related_applications' => false,
    ];

    return response()->json($manifest, 200, [
        'Content-Type' => 'application/manifest+json; charset=utf-8',
        'Cache-Control' => 'no-cache, must-revalidate',
    ]);
})->name('waiter.manifest');

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::post('/admin/bulk-delete', [\App\Http\Controllers\Admin\BulkActionController::class, 'destroy'])->name('admin.bulk-delete');

    Route::resource('categories', CategoryController::class);
    Route::resource('subcategories', SubcategoryController::class);
    Route::resource('addons', AddonController::class);
    Route::resource('addon-groups', AddonGroupController::class);
    Route::resource('option-sets', \App\Http\Controllers\Admin\OptionSetController::class);
    Route::get('/products/import/template', [ProductController::class, 'importTemplate'])->name('products.import.template');
    Route::post('/products/import', [ProductController::class, 'import'])->name('products.import');
    Route::get('/products/next-code', [ProductController::class, 'nextCode'])->name('products.next-code');
    Route::resource('products', ProductController::class);
    Route::get('/products/subcategories-by-category/{category}', [ProductController::class, 'subcategoriesByCategory'])->name('products.subcategories');

    Route::get('/barcode-labels', [BarcodeLabelController::class, 'index'])->name('barcode-labels.index');
    Route::get('/barcode-labels/designer/{layout?}', [BarcodeLabelController::class, 'designer'])->name('barcode-labels.designer');
    Route::post('/barcode-labels/layouts', [BarcodeLabelController::class, 'storeLayout'])->name('barcode-labels.layouts.store');
    Route::put('/barcode-labels/layouts/{layout}', [BarcodeLabelController::class, 'updateLayout'])->name('barcode-labels.layouts.update');
    Route::delete('/barcode-labels/layouts/{layout}', [BarcodeLabelController::class, 'destroyLayout'])->name('barcode-labels.layouts.destroy');
    Route::post('/barcode-labels/browser-print', [BarcodeLabelController::class, 'browserPrint'])->name('barcode-labels.browser-print');
    Route::get('/barcode-labels/search', [BarcodeLabelController::class, 'search'])->name('barcode-labels.search');
    Route::get('/barcode-labels/category/{category}', [BarcodeLabelController::class, 'byCategory'])->name('barcode-labels.category');
    Route::post('/barcode-labels/export', [BarcodeLabelController::class, 'export'])->name('barcode-labels.export');
    Route::get('/barcode-labels/batch', [BarcodeLabelController::class, 'downloadBatch'])->name('barcode-labels.batch');

    Route::get('/ingredients/import/template', [IngredientController::class, 'importTemplate'])->name('ingredients.import.template');
    Route::post('/ingredients/import', [IngredientController::class, 'import'])->name('ingredients.import');
    Route::resource('ingredients', IngredientController::class);
    Route::resource('recipes', RecipeController::class);
    Route::resource('customers', CustomerController::class);
    Route::get('loyalty', [LoyaltyController::class, 'index'])->name('loyalty.index');
    Route::get('loyalty/members/{customer}', [LoyaltyController::class, 'show'])->name('loyalty.show');
    Route::post('loyalty/members/{customer}/enroll', [LoyaltyController::class, 'enroll'])->name('loyalty.enroll');
    Route::post('loyalty/config', [LoyaltyController::class, 'saveConfig'])->name('loyalty.config');
    Route::resource('suppliers', SupplierController::class);
    Route::resource('purchases', PurchaseController::class);
    Route::post('purchases/{purchase}/payment', [PurchaseController::class, 'addPayment'])->name('purchases.payment');
    Route::resource('cheques', ChequeController::class);
    Route::post('cheques/{cheque}/clear', [ChequeController::class, 'clear'])->name('cheques.clear');
    Route::post('cheques/{cheque}/return', [ChequeController::class, 'returnCheque'])->name('cheques.return');

    // Billiards module (software-owner unlock)
    Route::get('billiards', [BilliardBookingController::class, 'desk'])->name('billiards.desk');
    Route::get('billiards/pos', [BilliardBookingController::class, 'pos'])->name('billiards.pos');
    Route::get('billiards/settings', [\App\Http\Controllers\Admin\BilliardSettingController::class, 'index'])->name('billiards.settings');
    Route::put('billiards/settings', [\App\Http\Controllers\Admin\BilliardSettingController::class, 'update'])->name('billiards.settings.update');
    Route::get('billiards/alerts', [BilliardBookingController::class, 'alertsJson'])->name('billiards.alerts');
    Route::get('billiards/customers/lookup', [BilliardBookingController::class, 'customerLookup'])->name('billiards.customers.lookup');
    Route::get('billiards/availability', [BilliardBookingController::class, 'availability'])->name('billiards.availability');
    Route::get('billiards/tables', [BilliardTableController::class, 'index'])->name('billiards.tables.index');
    Route::post('billiards/tables', [BilliardTableController::class, 'store'])->name('billiards.tables.store');
    Route::put('billiards/tables/{billiardTable}', [BilliardTableController::class, 'update'])->name('billiards.tables.update');
    Route::delete('billiards/tables/{billiardTable}', [BilliardTableController::class, 'destroy'])->name('billiards.tables.destroy');
    Route::get('billiards/bookings', [BilliardBookingController::class, 'index'])->name('billiards.bookings.index');
    Route::get('billiards/bookings/create', [BilliardBookingController::class, 'create'])->name('billiards.bookings.create');
    Route::post('billiards/bookings', [BilliardBookingController::class, 'store'])->name('billiards.bookings.store');
    Route::get('billiards/bookings/{booking}', [BilliardBookingController::class, 'show'])->name('billiards.bookings.show');
    Route::post('billiards/bookings/{booking}/start', [BilliardBookingController::class, 'start'])->name('billiards.bookings.start');
    Route::post('billiards/bookings/{booking}/complete', [BilliardBookingController::class, 'complete'])->name('billiards.bookings.complete');
    Route::post('billiards/bookings/{booking}/cancel', [BilliardBookingController::class, 'cancel'])->name('billiards.bookings.cancel');
    Route::post('billiards/bookings/{booking}/pay', [BilliardBookingController::class, 'pay'])->name('billiards.bookings.pay');
    Route::post('billiards/bookings/{booking}/remind', [BilliardBookingController::class, 'sendReminder'])->name('billiards.bookings.remind');
    Route::post('billiards/bookings/{booking}/ebill', [BilliardBookingController::class, 'sendEbill'])->name('billiards.bookings.ebill');
    Route::get('billiards/bookings/{booking}/print', [BilliardBookingController::class, 'printReceipt'])->name('billiards.bookings.print');
    Route::get('billiards/reports', [BilliardReportController::class, 'index'])->name('billiards.reports');

    Route::resource('floors', FloorController::class);
    Route::resource('tables', TableController::class);
    Route::post('/tables/{table}/regenerate-code', [TableController::class, 'regenerateCode'])->name('tables.regenerate-code');
    Route::get('/tables/{table}/qr-card', [TableController::class, 'qrCard'])->name('tables.qr-card');
    Route::post('/tables/{table}/send-qr', [TableController::class, 'sendQrLink'])->name('tables.send-qr');
    Route::resource('orders', OrderController::class);
    Route::resource('expenses', ExpenseController::class);
    Route::post('accounts/transfer', [AccountController::class, 'transfer'])->name('accounts.transfer');
    Route::post('accounts/{account}/transactions', [AccountController::class, 'storeTransaction'])->name('accounts.transactions.store');
    Route::resource('accounts', AccountController::class);
    Route::resource('users', UserController::class);

    // Multi-branch
    Route::get('branches/select', [BranchController::class, 'selectForm'])->name('branches.select');
    Route::post('branches/select', [BranchController::class, 'selectStore'])->name('branches.select.store');
    Route::get('shop-ui/select', [ShopUiController::class, 'selectForm'])->name('shop-ui.select');
    Route::post('shop-ui/select', [ShopUiController::class, 'selectStore'])->name('shop-ui.select.store');
    Route::post('branches/switch', [BranchController::class, 'switch'])->name('branches.switch');
    Route::resource('branches', BranchController::class)->except(['show']);

    Route::get('roles', [RoleController::class, 'index'])->name('roles.index');
    Route::get('roles/{role}/edit', [RoleController::class, 'edit'])->name('roles.edit');
    Route::put('roles/{role}', [RoleController::class, 'update'])->name('roles.update');

    Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
    Route::post('/inventory/adjust', [InventoryController::class, 'adjust'])->name('inventory.adjust');
    Route::get('/inventory/movements', [InventoryController::class, 'movements'])->name('inventory.movements');
    Route::get('/inventory/low-stock', [InventoryController::class, 'lowStock'])->name('inventory.low-stock');

    Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/sales', [ReportController::class, 'sales'])->name('reports.sales');
    Route::get('/reports/products', [ReportController::class, 'products'])->name('reports.products');
    Route::get('/reports/customers', [ReportController::class, 'customers'])->name('reports.customers');
    Route::get('/reports/stock', [ReportController::class, 'stock'])->name('reports.stock');
    Route::get('/reports/waiters', [ReportController::class, 'waiters'])->name('reports.waiters');
    Route::get('/reports/payments', [ReportController::class, 'payments'])->name('reports.payments');
    Route::get('/reports/shifts', [ReportController::class, 'shifts'])->name('reports.shifts');
    Route::get('/reports/day-ends', [ReportController::class, 'dayEnds'])->name('reports.day-ends');
    Route::get('/reports/loyalty', [ReportController::class, 'loyalty'])->name('reports.loyalty');
    Route::get('/reports/cancelled-bills', [ReportController::class, 'cancelledBills'])->name('reports.cancelled-bills');

    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');
    Route::get('/settings/download-print-bridge', [SettingController::class, 'downloadPrintBridge'])->name('settings.download-print-bridge');

    Route::get('/ai-agent', [\App\Http\Controllers\Admin\AiAgentController::class, 'index'])->name('ai.index');
    Route::post('/ai-agent/toggle', [\App\Http\Controllers\Admin\AiAgentController::class, 'toggle'])->name('ai.toggle');
    Route::post('/ai-agent/chat', [\App\Http\Controllers\Admin\AiAgentController::class, 'chat'])->name('ai.chat');

    Route::get('/guide', [GuideController::class, 'index'])->name('guide.index');
    Route::post('/guide/fresh-start', [GuideController::class, 'freshStart'])->name('guide.fresh-start');
    Route::post('/guide/dismiss-welcome', [GuideController::class, 'dismissWelcome'])->name('guide.dismiss-welcome');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index')->middleware('can:notifications.view');
    Route::post('/notifications/send', [NotificationController::class, 'send'])->name('notifications.send')->middleware('can:notifications.send');
    Route::post('/notifications/hosting-auto', [NotificationController::class, 'saveHostingAuto'])->name('notifications.hosting-auto')->middleware('can:notifications.send');

    Route::get('/inbox/notifications', [InboxNotificationController::class, 'index'])->name('inbox.notifications');
    Route::post('/inbox/notifications/{id}/read', [InboxNotificationController::class, 'markRead'])->name('inbox.notifications.read');
    Route::post('/inbox/notifications/read-all', [InboxNotificationController::class, 'markAllRead'])->name('inbox.notifications.read-all');

    Route::resource('delivery-partners', DeliveryPartnerController::class);
    Route::get('/delivery-partners/{deliveryPartner}/toggle', [DeliveryPartnerController::class, 'toggle'])->name('delivery-partners.toggle');
    Route::get('/delivery-partner-ledger', [DeliveryPartnerLedgerController::class, 'index'])->name('delivery-partners.ledger');
    Route::post('/delivery-partners/{deliveryPartner}/payments', [DeliveryPartnerLedgerController::class, 'storePayment'])->name('delivery-partners.payments.store');

    Route::resource('kitchens', AdminKitchenController::class);
    Route::post('/kitchens/{kitchen}/assign-categories', [AdminKitchenController::class, 'assignCategories'])->name('kitchens.assign-categories');
    Route::get('/kitchens/{kitchen}/print-kot/{order}', [KitchenPanelController::class, 'printKot'])->name('kitchens.print-kot');

    Route::get('/pos', [PosController::class, 'index'])->name('pos.index');
    Route::get('/pos/categories', [PosApiController::class, 'categories'])->name('pos.categories');
    Route::get('/pos/products', [PosApiController::class, 'products'])->name('pos.products');
    Route::get('/pos/tables', [PosApiController::class, 'tables'])->name('pos.tables');
    Route::get('/pos/customers', [PosApiController::class, 'customers'])->name('pos.customers');
    Route::get('/pos/loyalty/lookup', [PosApiController::class, 'loyaltyLookup'])->name('pos.loyalty.lookup');
    Route::get('/pos/loyalty/customer/{customer}', [PosApiController::class, 'loyaltyStatus'])->name('pos.loyalty.status');
    Route::post('/pos/loyalty/enroll/{customer}', [PosApiController::class, 'loyaltyEnroll'])->name('pos.loyalty.enroll');
    Route::post('/pos/loyalty/redeem/{customer}', [PosApiController::class, 'loyaltyRedeem'])->name('pos.loyalty.redeem');
    Route::post('/pos/cart/broadcast', [PosApiController::class, 'broadcastCart'])->name('pos.cart.broadcast');
    Route::post('/pos/customer/quick-add', [PosApiController::class, 'quickAddCustomer'])->name('pos.customer.quick-add');
    Route::post('/pos/customer/{customer}/address', [PosApiController::class, 'updateCustomerAddress'])->name('pos.customer.address');
    Route::post('/pos/hold', [PosApiController::class, 'holdOrder'])->name('pos.hold');
    Route::get('/pos/held', [PosApiController::class, 'heldOrders'])->name('pos.held');
    Route::post('/pos/recall', [PosApiController::class, 'recallOrder'])->name('pos.recall');
    Route::post('/pos/checkout', [PosApiController::class, 'checkout'])->name('pos.checkout');
    Route::post('/pos/place-order', [PosApiController::class, 'placeOrder'])->name('pos.place-order');
    Route::get('/pos/billing-orders', [PosApiController::class, 'billingOrders'])->name('pos.billing-orders');
    Route::get('/pos/open-bills/count', [PosApiController::class, 'openBillsCount'])->name('pos.open-bills.count');
    Route::post('/pos/delivery-order/{order}/delivered', [PosApiController::class, 'markDeliveryDelivered'])->name('pos.delivery.delivered');
    Route::get('/pos/table-open-order/{table}', [PosApiController::class, 'tableOpenOrder'])->name('pos.table-open-order');
    Route::get('/pos/pay-bills', [PosApiController::class, 'payBills'])->name('pos.pay-bills');
    Route::get('/pos/pay-bills/count', [PosApiController::class, 'payBillsCount'])->name('pos.pay-bills.count');
    Route::post('/pos/settle-order/{order}', [PosApiController::class, 'settleOrder'])->name('pos.settle-order');
    Route::post('/pos/void', [PosApiController::class, 'voidOrder'])->name('pos.void');
    Route::post('/pos/comp', [PosApiController::class, 'markComp'])->name('pos.comp');
    Route::post('/pos/refund', [PosApiController::class, 'refundOrder'])->name('pos.refund');
    Route::get('/pos/print-receipt/{order}', [PosApiController::class, 'printReceipt'])->name('pos.print-receipt');
    Route::get('/pos/receipt-escpos/{order}', [PosApiController::class, 'receiptEscPos'])->name('pos.receipt-escpos');
    Route::get('/pos/last-receipt-escpos', [PosApiController::class, 'lastReceiptEscPos'])->name('pos.last-receipt-escpos');
    Route::post('/pos/network-print-receipt/{order}', [PosApiController::class, 'networkPrintReceipt'])->name('pos.network-print-receipt');
    Route::post('/pos/network-print-last-receipt', [PosApiController::class, 'networkPrintLastReceipt'])->name('pos.network-print-last-receipt');
    Route::get('/pos/print-kot/{order}', [PosApiController::class, 'printKot'])->name('pos.print-kot');
    Route::get('/pos/print-bot/{order}', [PosApiController::class, 'printBot'])->name('pos.print-bot');
    Route::get('/pos/last-receipt', [PosApiController::class, 'lastReceipt'])->name('pos.last-receipt');
    Route::get('/pos/last-kot', [PosApiController::class, 'lastKot'])->name('pos.last-kot');
    Route::get('/pos/last-bot', [PosApiController::class, 'lastBot'])->name('pos.last-bot');
    Route::post('/pos/network-print/{kitchenOrder}', [PosApiController::class, 'networkPrint'])->name('pos.network-print');
    Route::get('/pos/kot-escpos/{kitchenOrder}', [PosApiController::class, 'kitchenOrderEscPos'])->name('pos.kot-escpos');
    Route::get('/pos/pending-kot-prints', [PosApiController::class, 'pendingKotPrints'])->name('pos.pending-kot-prints');
    Route::get('/pos/waiter-order-alerts', [PosApiController::class, 'waiterOrderAlerts'])->name('pos.waiter-order-alerts');
    Route::post('/pos/kot-printed/{kitchenOrder}', [PosApiController::class, 'markKotPrinted'])->name('pos.kot-printed');
    Route::get('/pos/kitchen-order/{kitchenOrder}', [PosApiController::class, 'kotDetails'])->name('pos.kot-details');
    Route::post('/pos/kitchen-order/{kitchenOrder}', [PosApiController::class, 'updateKot'])->name('pos.update-kot');
    Route::get('/pos/recent-orders', [PosApiController::class, 'recentOrders'])->name('pos.recent-orders');
    Route::get('/pos/waiter-report', [PosApiController::class, 'waiterReport'])->name('pos.waiter-report');
    Route::get('/pos/order-details/{order}', [PosApiController::class, 'orderDetails'])->name('pos.order-details');
    Route::post('/pos/update-order/{order}', [PosApiController::class, 'updateOrder'])->name('pos.update-order');
    Route::post('/pos/assign-waiter/{order}', [PosApiController::class, 'assignWaiter'])->name('pos.assign-waiter');
    Route::get('/pos/dine-in', [PosController::class, 'dineIn'])->name('pos.dine-in');
    Route::get('/pos/dine-in/orders', [PosApiController::class, 'dineInOrders'])->name('pos.dine-in.orders');
    Route::post('/pos/dine-in/change-table', [PosApiController::class, 'changeTable'])->name('pos.dine-in.change-table');

    // Cash Register Routes
    Route::get('/pos/register/status', [\App\Http\Controllers\Pos\RegisterController::class, 'status'])->name('pos.register.status');
    Route::post('/pos/register/open', [\App\Http\Controllers\Pos\RegisterController::class, 'open'])->name('pos.register.open');
    Route::post('/pos/register/close', [\App\Http\Controllers\Pos\RegisterController::class, 'close'])->name('pos.register.close');
    Route::get('/pos/register/report', [\App\Http\Controllers\Pos\RegisterController::class, 'report'])->name('pos.register.report');
    Route::post('/pos/register/cash-movement', [\App\Http\Controllers\Pos\RegisterController::class, 'cashMovement'])->name('pos.register.cash-movement');
    Route::get('/pos/register/history', [\App\Http\Controllers\Pos\RegisterController::class, 'history'])->name('pos.register.history');
    Route::post('/pos/register/open-drawer', [\App\Http\Controllers\Pos\RegisterController::class, 'openDrawer'])->name('pos.register.open-drawer');
    Route::post('/pos/register/day-end', [\App\Http\Controllers\Pos\RegisterController::class, 'dayEndAggregate'])->name('pos.register.day-end');
    Route::get('/pos/register/{register}/print', [\App\Http\Controllers\Pos\RegisterController::class, 'printReport'])->name('pos.register.print');

    // Kitchen display only - use POS billing for kitchen order management
    Route::redirect('/kitchen', '/kitchen/display')->name('kitchen.index');

    Route::get('/kds', [KdsController::class, 'index'])->name('kds.index');
    Route::get('/kds/orders', [KdsController::class, 'orders'])->name('kds.orders');
    Route::post('/kds/update-status', [KdsController::class, 'updateStatus'])->name('kds.update-status');

    Route::get('/bar', [BarController::class, 'index'])->name('bar.index');
    Route::get('/bar/orders', [BarController::class, 'orders'])->name('bar.orders');
    Route::post('/bar/update-status', [BarController::class, 'updateStatus'])->name('bar.update-status');

    Route::get('/display', [CustomerDisplayController::class, 'index'])->name('display.index');
    Route::get('/display/orders', [CustomerDisplayController::class, 'orders'])->name('display.orders');

    Route::get('/waiter/panel', [WaiterController::class, 'index'])->name('waiter.index');
    Route::redirect('/waiter', '/waiter/panel', 301)->name('waiter.legacy');
    Route::get('/waiter/dashboard', [WaiterController::class, 'dashboard'])->name('waiter.dashboard');
    Route::get('/waiter/stats', [WaiterController::class, 'stats'])->name('waiter.stats');
    Route::get('/waiter/my-orders', [WaiterController::class, 'myOrders'])->name('waiter.my-orders');
    Route::get('/waiter/kitchen-orders', [WaiterController::class, 'kitchenOrders'])->name('waiter.kitchen-orders');
    Route::post('/waiter/kitchen-orders/{kitchenOrder}/served', [WaiterController::class, 'markKotServed'])->name('waiter.kitchen-orders.served');
    Route::post('/waiter/rate/{order}', [WaiterController::class, 'rateOrder'])->name('waiter.rate');
    Route::get('/waiter/rate-link/{order}', [WaiterController::class, 'rateLink'])->name('waiter.rate-link');
    Route::post('/waiter/rate-link/{order}/send', [WaiterController::class, 'sendRateLink'])->name('waiter.rate-send');
    Route::post('/waiter/request-bill/{order}', [WaiterController::class, 'requestBill'])->name('waiter.request-bill');
    Route::get('/waiter/tables', [WaiterController::class, 'tables'])->name('waiter.tables');
    Route::get('/waiter/menu', [WaiterController::class, 'menu'])->name('waiter.menu');
    Route::get('/waiter/customer-lookup', [WaiterController::class, 'lookupCustomer'])->name('waiter.customer-lookup');
    Route::post('/waiter/customer', [WaiterController::class, 'saveCustomer'])->name('waiter.customer');
    Route::post('/waiter/order/{order}/customer', [WaiterController::class, 'attachCustomer'])->name('waiter.attach-customer');
    Route::get('/waiter/order/{order}', [WaiterController::class, 'orderDetails'])->name('waiter.order.show');
    Route::post('/waiter/order', [WaiterController::class, 'placeOrder'])->name('waiter.order');
    Route::post('/waiter/order/{order}/update', [WaiterController::class, 'updateOrder'])->name('waiter.order.update');
    Route::post('/waiter/order/{order}/transfer', [WaiterController::class, 'transferOrder'])->name('waiter.order.transfer');
    Route::post('/waiter/order/{order}/cancel', [WaiterController::class, 'cancelOrder'])->name('waiter.order.cancel');
    Route::get('/waiter/qr-orders', [WaiterController::class, 'pendingQrOrders'])->name('waiter.qr-orders');
    Route::post('/waiter/qr-orders/{order}/accept', [WaiterController::class, 'acceptQrOrder'])->name('waiter.qr-accept');
    Route::post('/waiter/qr-orders/{order}/reject', [WaiterController::class, 'rejectQrOrder'])->name('waiter.qr-reject');

    // Marketing Settings (Admin) — customer display idle poster
    Route::get('/admin/marketing', [\App\Http\Controllers\Admin\MarketingSettingsController::class, 'index'])->name('admin.marketing.index');
    Route::put('/admin/marketing', [\App\Http\Controllers\Admin\MarketingSettingsController::class, 'update'])->name('admin.marketing.update');
    Route::post('/admin/marketing/ads', [\App\Http\Controllers\Admin\MarketingSettingsController::class, 'storeAd'])->name('admin.marketing.ads.store');
    Route::post('/admin/marketing/ads/{ad}/toggle', [\App\Http\Controllers\Admin\MarketingSettingsController::class, 'toggleAd'])->name('admin.marketing.ads.toggle');
    Route::delete('/admin/marketing/ads/{ad}', [\App\Http\Controllers\Admin\MarketingSettingsController::class, 'destroyAd'])->name('admin.marketing.ads.destroy');

    // Promo campaigns — WhatsApp (Meta Cloud API) + SMS
    Route::get('/admin/promos', [\App\Http\Controllers\Admin\PromoCampaignController::class, 'index'])->name('admin.promos.index');
    Route::get('/admin/promos/create', [\App\Http\Controllers\Admin\PromoCampaignController::class, 'create'])->name('admin.promos.create');
    Route::post('/admin/promos', [\App\Http\Controllers\Admin\PromoCampaignController::class, 'store'])->name('admin.promos.store');
    Route::get('/admin/promos/{promo}', [\App\Http\Controllers\Admin\PromoCampaignController::class, 'show'])->name('admin.promos.show');

});

// Public Display Routes (kitchen screens, customer displays — no auth needed)
Route::get('/billiards/display', [BilliardDisplayController::class, 'index'])->name('billiards.display');
Route::get('/billiards/display/feed', [BilliardDisplayController::class, 'feed'])->name('billiards.display.feed');
Route::get('/kitchen/display', [\App\Http\Controllers\Kitchen\KitchenDisplayController::class, 'index'])->name('kitchen.display');
Route::get('/kitchen/orders', [\App\Http\Controllers\Kitchen\KitchenDisplayController::class, 'orders'])->name('kitchen.orders');
Route::get('/kitchen/ready-orders', [\App\Http\Controllers\Kitchen\KitchenDisplayController::class, 'readyOrders'])->name('kitchen.ready-orders');
Route::post('/kitchen/orders/{kitchenOrder}/ready', [\App\Http\Controllers\Kitchen\KitchenDisplayController::class, 'markReady'])->name('kitchen.orders.ready');
Route::post('/kitchen/orders/{kitchenOrder}/started', [\App\Http\Controllers\Kitchen\KitchenDisplayController::class, 'markStarted'])->name('kitchen.orders.started');
Route::post('/kitchen/orders/{kitchenOrder}/item-ready', [\App\Http\Controllers\Kitchen\KitchenDisplayController::class, 'markItemReady'])->name('kitchen.orders.item-ready');
Route::post('/kitchen/orders/{kitchenOrder}/served', [\App\Http\Controllers\Kitchen\KitchenDisplayController::class, 'markServed'])->name('kitchen.orders.served');

// Customer Display Screens
Route::get('/customer-display', [\App\Http\Controllers\Kitchen\CustomerDisplayController::class, 'index'])->name('customer.display');
Route::get('/customer-display/summary', [\App\Http\Controllers\Kitchen\CustomerDisplayController::class, 'summary'])->name('customer.display.summary');
Route::get('/customer-display/status', [\App\Http\Controllers\Kitchen\CustomerDisplayController::class, 'status'])->name('customer.display.status');
Route::get('/customer-display/orders', [\App\Http\Controllers\Kitchen\CustomerDisplayController::class, 'orders'])->name('customer.display.orders');
Route::get('/customer-display/cart', [\App\Http\Controllers\Kitchen\CustomerDisplayController::class, 'currentCart'])->name('customer.cart');
Route::get('/customer-display/led', [\App\Http\Controllers\Kitchen\CustomerDisplayController::class, 'led'])->name('customer.display.led');
Route::get('/dual-display', [\App\Http\Controllers\Kitchen\CustomerDisplayController::class, 'dualDisplay'])->name('dual.display');

// cPanel fallback: serve storage when public/storage symlink is missing/broken
Route::get('/storage/{path}', \App\Http\Controllers\StorageFallbackController::class)
    ->where('path', '.*')
    ->name('storage.fallback');

// One-click storage:link for cPanel (requires ?token=…)
Route::get('/setup/storage-link', \App\Http\Controllers\StorageLinkController::class)
    ->name('setup.storage-link');

// One-click migrate + cache clear for cPanel updates (no Terminal)
Route::get('/setup/migrate-update', \App\Http\Controllers\SetupMigrateController::class)
    ->middleware('web')
    ->name('setup.migrate-update');

Route::get('/qr-menu/{table}', [QrMenuController::class, 'index'])->name('qr.menu');
Route::post('/qr-menu/{table}/verify', [QrMenuController::class, 'verify'])->name('qr.verify');
Route::post('/qr-menu/{table}/order', [QrMenuController::class, 'placeOrder'])->name('qr.order');

Route::get('/rate/{order}', [GuestRatingController::class, 'show'])->name('guest.rate');
Route::post('/rate/{order}', [GuestRatingController::class, 'store'])->name('guest.rate.submit');

Route::get('/loyalty/card/{token}', [LoyaltyCardController::class, 'show'])->name('loyalty.card');
Route::get('/loyalty/card/{token}/wallet', [LoyaltyCardController::class, 'wallet'])->name('loyalty.wallet');

Route::get('/self-order', [SelfOrderController::class, 'index'])->name('self-order.index');
Route::post('/self-order/checkout', [SelfOrderController::class, 'checkout'])->name('self-order.checkout');

// Marketing API (public)
Route::get('/api/marketing-settings', [\App\Http\Controllers\Admin\MarketingSettingsController::class, 'getSettingsApi']);
