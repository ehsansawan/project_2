<?php

namespace Database\Seeders;

use App\Enums\AccountStatus;
use App\Enums\RejectionReason;
use App\Enums\VerificationStatus;
use App\Models\User;
use App\Models\VerificationImages;
use App\Models\VerificationRejections;
use App\Models\VerificationRequests;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VerificationDemoSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('verification_rejections')->truncate();
        DB::table('verification_images')->truncate();
        DB::table('verification_requests')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        $admins = User::role('admin')->get();
        $clients = User::role('client')->orderBy('id')->get();

        foreach ($clients as $index => $client) {
            // Only two thirds of clients have submitted a verification request.
            if ($index % 3 === 2) {
                continue;
            }

            // Column is varchar(11): '9' + 10 zero-padded digits = 11 chars exactly.
            $nationalId = '9' . str_pad((string) $client->id, 10, '0', STR_PAD_LEFT);

            $statusRoll = $index % 3;
            $status = match ($statusRoll) {
                0 => VerificationStatus::Approved->value,
                1 => VerificationStatus::Pending->value,
                default => VerificationStatus::Rejected->value,
            };

            $request = VerificationRequests::create([
                'user_id' => $client->id,
                'national_id' => $nationalId,
                'status' => $status,
            ]);

            VerificationImages::create([
                'verification_request_id' => $request->id,
                'image_url' => 'verifications/demo/' . $client->id . '-front.jpg',
            ]);
            VerificationImages::create([
                'verification_request_id' => $request->id,
                'image_url' => 'verifications/demo/' . $client->id . '-back.jpg',
            ]);

            if ($status === VerificationStatus::Approved->value) {
                $client->update([
                    'account_status' => AccountStatus::Verified->value,
                    'national_id' => $nationalId,
                    'expires_at' => null,
                ]);
            }

            if ($status === VerificationStatus::Rejected->value) {
                $admin = $admins->random();

                VerificationRejections::create([
                    'user_id' => $admin->id,
                    'verification_request_id' => $request->id,
                    'reason' => [RejectionReason::BlurryImages->value],
                    'description' => 'One of the submitted ID images was too blurry to verify. Please resubmit clearer photos.',
                ]);
            }
        }
    }
}
