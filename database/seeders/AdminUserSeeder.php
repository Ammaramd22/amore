<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $ownerEmail = env('SOFTWARE_OWNER_EMAIL', 'owner@avenque.io');
        $ownerPassword = env('SOFTWARE_OWNER_PASSWORD', 'password');
        $ownerPin = env('SOFTWARE_OWNER_PIN', '0000');

        $owner = User::firstOrCreate(
            ['email' => $ownerEmail],
            [
                'name' => 'Software Owner',
                'phone' => '0768222201',
                'password' => Hash::make($ownerPassword),
                'employee_code' => 'OWN001',
                'login_code' => '0000',
                'user_type' => 'software_owner',
                'is_active' => true,
            ]
        );
        // Keep credentials / type in sync with .env on re-seed
        $owner->password = Hash::make($ownerPassword);
        $owner->pin = $ownerPin;
        $owner->login_code = $owner->login_code ?: '0000';
        $owner->user_type = 'software_owner';
        $owner->is_active = true;
        $owner->save();
        $owner->syncRoles(['software_owner']);

        $admin = User::firstOrCreate(
            ['email' => 'admin@respos.lk'],
            [
                'name' => 'System Administrator',
                'phone' => '0771234567',
                'password' => Hash::make('password'),
                'employee_code' => 'ADM001',
                'user_type' => 'admin',
                'is_active' => true,
            ]
        );
        $admin->syncRoles(['admin']);
        if (! $admin->hasPin()) {
            $admin->pin = '1111';
            $admin->save();
        }

        $cashier = User::firstOrCreate(
            ['email' => 'cashier@respos.lk'],
            [
                'name' => 'Main Cashier',
                'phone' => '0771234568',
                'password' => Hash::make('password'),
                'employee_code' => 'CAS001',
                'user_type' => 'cashier',
                'is_active' => true,
            ]
        );
        $cashier->syncRoles(['cashier']);
        if (! $cashier->hasPin()) {
            $cashier->pin = '2222';
            $cashier->save();
        }

        $waiter = User::firstOrCreate(
            ['email' => 'waiter@respos.lk'],
            [
                'name' => 'Head Waiter',
                'phone' => '0771234569',
                'password' => Hash::make('password'),
                'employee_code' => 'WTR001',
                'user_type' => 'waiter',
                'is_active' => true,
            ]
        );
        $waiter->syncRoles(['waiter']);
        if (! $waiter->hasPin()) {
            $waiter->pin = '3333';
            $waiter->save();
        }

        $kitchen = User::firstOrCreate(
            ['email' => 'kitchen@respos.lk'],
            [
                'name' => 'Kitchen Staff',
                'phone' => '0771234570',
                'password' => Hash::make('password'),
                'employee_code' => 'KTN001',
                'user_type' => 'kitchen',
                'is_active' => true,
            ]
        );
        $kitchen->syncRoles(['kitchen']);
        if (! $kitchen->hasPin()) {
            $kitchen->pin = '4444';
            $kitchen->save();
        }

        $manager = User::firstOrCreate(
            ['email' => 'manager@respos.lk'],
            [
                'name' => 'Restaurant Manager',
                'phone' => '0771234571',
                'password' => Hash::make('password'),
                'employee_code' => 'MGR001',
                'user_type' => 'manager',
                'is_active' => true,
            ]
        );
        $manager->syncRoles(['manager']);
        if (! $manager->hasPin()) {
            $manager->pin = '5555';
            $manager->save();
        }

        $delivery = User::firstOrCreate(
            ['email' => 'delivery@respos.lk'],
            [
                'name' => 'Delivery Staff',
                'phone' => '0771234572',
                'password' => Hash::make('password'),
                'employee_code' => 'DLV001',
                'user_type' => 'delivery',
                'is_active' => true,
            ]
        );
        $delivery->syncRoles(['delivery']);
        if (! $delivery->hasPin()) {
            $delivery->pin = '6666';
            $delivery->save();
        }
    }
}
