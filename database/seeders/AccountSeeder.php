<?php

namespace Database\Seeders;

use App\Models\Account;
use Illuminate\Database\Seeder;

class AccountSeeder extends Seeder
{
    public function run(): void
    {
        Account::firstOrCreate(
            ['code' => 'CASH'],
            [
                'name' => 'Cash Account',
                'type' => 'cash',
                'payment_methods' => ['cash'],
                'opening_balance' => 0,
                'current_balance' => 0,
                'is_system' => true,
                'is_active' => true,
                'notes' => 'Default cash account — POS cash sales & cash movements',
            ]
        );

        Account::firstOrCreate(
            ['code' => 'BANK'],
            [
                'name' => 'Bank Account',
                'type' => 'bank',
                'payment_methods' => ['card', 'bank_transfer', 'online'],
                'opening_balance' => 0,
                'current_balance' => 0,
                'is_system' => true,
                'is_active' => true,
                'notes' => 'Default bank account — card, bank transfer & online payments',
            ]
        );
    }
}
