<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Super Admin
        User::firstOrCreate(
            ['email' => 'admin@emcatech.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
                'role' => 'super_admin'
            ]
        );

        // 2. CEO
        User::firstOrCreate(
            ['email' => 'ceo@emcatech.com'],
            [
                'name' => 'CEO',
                'password' => Hash::make('password'),
                'role' => 'ceo'
            ]
        );

        // 3. Specific Staff Members with Phone Numbers
        $staffMembers = [
            ['name' => 'Often Fred', 'email' => 'often.fred@emca.tech', 'phone' => '0764628402'],
            ['name' => 'Leonard Kingoye', 'email' => 'leonard.kingoye@emca.tech', 'phone' => '0749719998'],
            ['name' => 'Ally Ally', 'email' => 'ally.ally@emca.tech', 'phone' => '0712345678'],
            ['name' => 'Benjamin Bufumbe', 'email' => 'benjamin.bufumbe@emca.tech', 'phone' => '0712000001'],
            ['name' => 'Devid Ngungila', 'email' => 'devid.ngungila@emca.tech', 'phone' => '0712000002'],
            ['name' => 'Hassan Said', 'email' => 'hassan.said@emca.tech', 'phone' => '0713000003'],
            ['name' => 'Mariana Swai', 'email' => 'mariana.swai@emca.tech', 'phone' => '0714000004'],
            ['name' => 'Naomi Naomi', 'email' => 'naomi@emca.tech', 'phone' => '0715000005'],
            ['name' => 'Caroline Shija', 'email' => 'caroline.shija@emca.tech', 'phone' => '0716000006'],
            ['name' => 'Emmanuel Masaga', 'email' => 'emmanuel.masaga@emca.tech', 'phone' => '0717000007'],
        ];

        foreach ($staffMembers as $staff) {
            User::updateOrCreate(
                ['email' => $staff['email']],
                [
                    'name' => $staff['name'],
                    'phone' => $staff['phone'],
                    'password' => Hash::make('password'),
                    'role' => 'staff'
                ]
            );
        }
    }
}
