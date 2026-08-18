<?php

namespace Database\Seeders;

use App\Models\License;
use App\Models\LicenseProperty;
use App\Models\MapPin;
use App\Models\Property;
use App\Models\User;
use App\Models\UserLicenseProperty;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PropertyLicenseSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('user_license_properties')->truncate();
        DB::table('license_properties')->truncate();
        DB::table('licenses')->truncate();
        DB::table('properties')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Damascus city center, small random spread.
        $baseLat = 33.5138;
        $baseLng = 36.2765;

        $propertyTypes = ['commercial', 'residential', 'land', 'industrial'];
        $ownerships = ['citizen', 'government', 'private'];

        $properties = [];
        for ($i = 1; $i <= 15; $i++) {
            $pin = MapPin::create([
                'latitude' => $baseLat + (mt_rand(-300, 300) / 10000),
                'longitude' => $baseLng + (mt_rand(-300, 300) / 10000),
                'type' => 'other',
                'description' => "Property location #{$i}",
            ]);

            $properties[] = Property::create([
                'number' => 'PROP-' . str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                'status' => $i % 10 === 0 ? 'suspended' : 'active',
                'ownership' => $ownerships[$i % 3],
                'type' => $propertyTypes[$i % 4],
                'address_details' => "Building {$i}, Damascus Old City District",
                'pin_id' => $pin->id,
            ]);
        }

        $licenseTypes = ['residential', 'commercial'];
        $licenses = [];
        for ($i = 1; $i <= 8; $i++) {
            $licenses[] = License::create([
                'type' => $licenseTypes[$i % 2],
                'status' => $i % 6 === 0 ? 'inactive' : 'active',
            ]);
        }

        $clients = User::role('client')->orderBy('id')->get();

        foreach ($properties as $index => $property) {
            if ($index % 2 !== 0) {
                continue; // only some properties currently have a license
            }

            $license = $licenses[$index % count($licenses)];
            $statusRoll = $index % 3;
            $status = match ($statusRoll) {
                0 => 'valid',
                1 => 'expired',
                default => 'revoked',
            };

            $licenseProperty = LicenseProperty::create([
                'license_id' => $license->id,
                'property_id' => $property->id,
                'status' => $status,
                'issue_date' => now()->subYears(1),
                'expiry_date' => $status === 'expired' ? now()->subMonths(2) : now()->addYears(1),
            ]);

            $owner = $clients[$index % $clients->count()];
            UserLicenseProperty::create([
                'user_id' => $owner->id,
                'license_property_id' => $licenseProperty->id,
            ]);
        }
    }
}
