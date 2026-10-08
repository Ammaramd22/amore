<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\LoyaltyService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LoyaltyCardController extends Controller
{
    public function __construct(protected LoyaltyService $loyalty) {}

    public function show(string $token): View
    {
        if (! LoyaltyService::enabled()) {
            abort(404);
        }

        $customer = $this->loyalty->findByToken($token);
        if (! $customer) {
            abort(404, 'Stamp card not found');
        }

        $payload = $this->loyalty->customerPayload($customer);
        $business = Setting::get('company_name', 'QRPOS');
        $qrImage = 'https://api.qrserver.com/v1/create-qr-code/?size=320x320&margin=12&data='.urlencode($this->loyalty->qrPayload($customer));
        $cardUrl = $this->loyalty->cardUrl($customer);

        return view('loyalty.card', compact('customer', 'payload', 'business', 'qrImage', 'cardUrl'));
    }

    /** Wallet landing — explains Add to Apple/Google Wallet + home screen. */
    public function wallet(string $token)
    {
        if (! LoyaltyService::enabled()) {
            abort(404);
        }
        $customer = $this->loyalty->findByToken($token);
        if (! $customer) {
            abort(404);
        }

        $cardUrl = $this->loyalty->cardUrl($customer);
        $payload = $this->loyalty->customerPayload($customer);
        $business = Setting::get('company_name', 'QRPOS');

        return view('loyalty.wallet', compact('customer', 'payload', 'cardUrl', 'business'));
    }
}
