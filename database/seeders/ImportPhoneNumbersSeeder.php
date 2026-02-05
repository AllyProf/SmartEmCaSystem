<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class ImportPhoneNumbersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Phone numbers to import (cleaned and formatted)
        $phoneNumbers = [
            '255754668229',
            '255767034510',
            '25575333437',   // 11 digits - needs fixing
            '255784242993',
            '255755263545',
            '255758534210',
            '255652945727',
            '255763572925',
            '255627609506',
            '255659738028',
            '255713599627',
            '255718164044',
            '255717157640',
            '255762030592',
            '255759558865',
            '255767808623',
            '255774598605',
            '255774818170',
            '255745371494',
            '255754772753',
            '255757101182',
            '255686445794',
            '255717044469',
            '255756096146',
            '255765525555',
            '255713112991',
            '255713345197',
            '255754023829',
            '255766210651',  // Fixed: removed spaces
            '255627275378',  // Fixed: removed spaces
        ];

        // Get the first admin/CEO user for created_by, or use ID 1
        $createdBy = User::whereIn('role', ['super_admin', 'ceo'])->first();
        $createdById = $createdBy ? $createdBy->id : 1;

        $added = 0;
        $skipped = 0;
        $fixed = 0;

        foreach ($phoneNumbers as $phone) {
            // Clean the phone number (remove spaces, dashes, etc.)
            $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
            
            // Fix phone numbers that are 11 digits (missing one digit)
            if (substr($cleanPhone, 0, 3) === '255' && strlen($cleanPhone) === 11) {
                // Try to fix by checking if it might be missing the first digit after 255
                // Tanzanian numbers typically start with 6, 7, or 8 after 255
                $after255 = substr($cleanPhone, 3);
                
                // If the number after 255 doesn't start with 6, 7, or 8, try adding 6 (most common)
                if (!in_array(substr($after255, 0, 1), ['6', '7', '8'])) {
                    $cleanPhone = '2556' . $after255;
                    $fixed++;
                    echo "Fixed: {$phone} -> {$cleanPhone} (added missing digit)\n";
                } else {
                    // Already starts with 6, 7, or 8, might be missing last digit - can't auto-fix
                    echo "Warning: Phone number {$cleanPhone} has 11 digits (should be 12). Cannot auto-fix. Skipping.\n";
                    $skipped++;
                    continue;
                }
            }
            
            // Ensure it's 12 digits starting with 255
            if (substr($cleanPhone, 0, 3) !== '255') {
                // If it starts with 0, convert to 255
                if (substr($cleanPhone, 0, 1) === '0') {
                    $cleanPhone = '255' . substr($cleanPhone, 1);
                } else {
                    // Add 255 prefix
                    $cleanPhone = '255' . $cleanPhone;
                }
            }
            
            // Final validation - must be 12 digits
            if (strlen($cleanPhone) !== 12) {
                echo "Error: Phone number {$phone} could not be formatted correctly. Length: " . strlen($cleanPhone) . "\n";
                $skipped++;
                continue;
            }

            // Check if customer already exists
            $existing = Customer::where('phone_number', $cleanPhone)->first();
            
            if ($existing) {
                echo "Skipping {$cleanPhone} - already exists\n";
                $skipped++;
                continue;
            }

            // Create the customer
            Customer::create([
                'name' => null, // No name provided
                'phone_number' => $cleanPhone,
                'location' => null,
                'visiting_purpose' => null,
                'created_by' => $createdById,
            ]);

            echo "Added: {$cleanPhone}\n";
            $added++;
        }

        echo "\n=== Import Summary ===\n";
        echo "Added: {$added}\n";
        echo "Skipped: {$skipped}\n";
        echo "Total processed: " . ($added + $skipped) . "\n";
    }
}
