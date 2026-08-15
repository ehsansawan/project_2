<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use App\Enums\AccountStatus;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    public function run(): void
    {

        //
        //create Roles
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('roles')->truncate();
        DB::table('permissions')->truncate();
        DB::table('role_has_permissions')->truncate();
        DB::table('model_has_roles')->truncate();
        DB::table('model_has_permissions')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');


        $super_admin_role=Role::create(['name' => 'super-admin']);
        $admin_role=Role::create(['name' => 'admin']);
        $client_role=Role::create(['name' => 'client']);

        //Define permissions (every permission) for super admin

        $permissions = [

            // ===== المواطن =====
            'profile.index',
            'profile.show',
            'profile.update',

            'verification.index',
            'verification.show',
            'verification.store',
            'verification.update',
            'verification.SearchByNationalId',

            'skill.index',
            'skill.show',
            'skill.store',
            'skill.update',
            'skill.destroy',

            'certificate.index',
            'certificate.show',
            'certificate.store',
            'certificate.update',
            'certificate.destroy',

            'complain.store',
            'complain.index',
            'complain.show',
            'complain.update',
            'complain.destroy',

            'report.store',

            'queue.showService',
            'queue.join',
            'queue.info',

            // ===== الأدمن =====
            'verification.adminIndex',
            'verification.adminShow',
            'verification.adminIndexByUser',
            'verification.approve',
            'verification.reject',

            'certificate.adminIndex',
            'certificate.adminShow',
            'certificate.adminIndexByUser',
            'certificate.approve',
            'certificate.reject',

            'admin.services.index',
            'admin.services.show',
            'admin.services.store',
            'admin.services.update',
            'admin.services.destroy',
            'admin.services.assignEmployee',
            'admin.services.unassignEmployee',

            'admin.queue.dashboard',
            'admin.queue.addManual',
            'admin.queue.callNext',
            'admin.queue.markAsServed',
            'admin.queue.markAsNoShow',
            'admin.queue.returnToQueue',

            'admin.statistics.overview',
            'admin.statistics.serviceStats',
            'admin.statistics.employeeStats',
            'admin.statistics.history',
        ];

        $admin_permissions = [
            'verification.adminIndex',
            'verification.adminShow',
            'verification.adminIndexByUser',
            'verification.approve',
            'verification.reject',

            'certificate.adminIndex',
            'certificate.adminShow',
            'certificate.adminIndexByUser',
            'certificate.approve',
            'certificate.reject',

            'admin.services.index',
            'admin.services.show',
            'admin.services.store',
            'admin.services.update',
            'admin.services.destroy',
            'admin.services.assignEmployee',
            'admin.services.unassignEmployee',

            'admin.queue.dashboard',
            'admin.queue.addManual',
            'admin.queue.callNext',
            'admin.queue.markAsServed',
            'admin.queue.markAsNoShow',
            'admin.queue.returnToQueue',

            'admin.statistics.overview',
            'admin.statistics.serviceStats',
            'admin.statistics.employeeStats',
            'admin.statistics.history',
        ];

        $client_permissions = [
            'profile.index',
            'profile.show',
            'profile.update',

            'verification.index',
            'verification.show',
            'verification.store',
            'verification.update',
            'verification.SearchByNationalId',

            'skill.index',
            'skill.show',
            'skill.store',
            'skill.update',
            'skill.destroy',

            'certificate.index',
            'certificate.show',
            'certificate.store',
            'certificate.update',
            'certificate.destroy',

            'complain.store',
            'complain.index',
            'complain.show',
            'complain.update',
            'complain.destroy',

            'report.store',

            'queue.showService',
            'queue.join',
            'queue.info',
        ];
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission,'api');
        }

        $super_admin_role->givePermissionTo($permissions);
        $admin_role->givePermissionTo($admin_permissions);
        $client_role->givePermissionTo($client_permissions);


// super admin user
        $superAdminUser=User::query()->create([
            'first_name' => 'Ehsan',
            'last_name' => 'Sawan',
            'email' => 'ehsansawan7@gmail.com',
            'phone' => '0991234567',
            'national_id' => '12345678901',
            'password' => Hash::make('password123'),
            'birth_date' => '1990-01-01',
            'account_status' => AccountStatus::Verified->value,
            'email_verified_at' => Carbon::now(),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        $superAdminUser->assignRole($super_admin_role);

        //Assign permissions associated with the role to the user
        $permissions=$super_admin_role->permissions()->pluck('name')->toArray();
        $superAdminUser->givePermissionTo($permissions);

// admin user
        for ( $x = 1; $x <=3 ; $x++) {
            $adminUser = User::query()->create([
                'first_name' => 'adminUser ' . $x,
                'last_name' => 'User ' . $x,
                'email'=>    'admin'.$x.'@gmail.com',
                'national_id'=> '2234567890'.$x,
                'phone' => '093675776' . $x,
                'password' => Hash::make('password123' ),
                'birth_date' => '1990-01-01',
                'account_status' => AccountStatus::Verified->value,
                'email_verified_at' => Carbon::now(),
            ]);

            $adminUser->assignRole($admin_role);

            // Assign permissions associated with the role to the user
            $permissions = $admin_role->permissions()->pluck('name')->toArray();
            $adminUser->givePermissionTo($permissions);
        }


// client user

        //client
        for ( $x = 1; $x <= 5; $x++) {
            $clientUser = User::query()->create([
                'first_name' => 'client user ' . $x,
                'last_name' => 'User ' . $x,
                'email'=>    'user'.$x.'@gmail.com',
                'phone' => '093675779' . $x,
                'password' => Hash::make('password123' ),
                'birth_date' => '1990-01-01',
                'account_status' => AccountStatus::Verified->value,
                'email_verified_at' => Carbon::now(),
            ]);

            $clientUser->assignRole($client_role);

            // Assign permissions associated with the role to the user
            $permissions = $client_role->permissions()->pluck('name')->toArray();
            $clientUser->givePermissionTo($permissions);
        }

        $profiles = [
            [
                //super admin
                'user_id' => 1,
                // 'profile_name' => 'Ahmad Citizen',
                'citizenship_score' => 100,
                'credibility_score' => 100,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                //admin
                'user_id' => 2,
                // 'profile_name' => 'Sara Unverified',
                // 'status' => 'unverified',
                'citizenship_score' => 100,
                'credibility_score' => 100,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                //admin
                'user_id' => 3,
                // 'profile_name' => 'Omar Employee',
                // 'status' => 'verified',
                'citizenship_score' => 100,
                'credibility_score' => 100,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                //admin
                'user_id' => 4,
                // 'profile_name' => 'Mohammed Citizen',
                // 'status' => 'verified',
                'citizenship_score' => 100,
                'credibility_score' => 100,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                //client
                'user_id' => 5,
                // 'profile_name' => 'Ali Citizen',
                // 'status' => 'verified',
                'citizenship_score' => 50,
                'credibility_score' => 50,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                //client
                'user_id' => 6,
                // 'profile_name' => 'maia Citizen',
                // 'status' => 'verified',
                'citizenship_score' => 50,
                'credibility_score' => 50,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                //client
                'user_id' => 7,
                // 'profile_name' => 'Ali Citizen',
                // 'status' => 'verified',
                'citizenship_score' => 50,
                'credibility_score' => 50,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                //client
                'user_id' => 8,
                // 'profile_name' => 'maia Citizen',
                // 'status' => 'verified',
                'citizenship_score' => 50,
                'credibility_score' => 50,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                //client
                'user_id' => 9,
                // 'profile_name' => 'Ali Citizen',
                // 'status' => 'verified',
                'citizenship_score' => 50,
                'credibility_score' => 50,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],

        ];

        DB::table('profiles')->insert($profiles);
    }
}
