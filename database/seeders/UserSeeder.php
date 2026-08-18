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
use Spatie\Permission\PermissionRegistrar;

class UserSeeder extends Seeder
{
    public function run(): void
    {

        //
        //create Roles
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('users')->truncate();
        DB::table('profiles')->truncate();
        DB::table('roles')->truncate();
        DB::table('permissions')->truncate();
        DB::table('role_has_permissions')->truncate();
        DB::table('model_has_roles')->truncate();
        DB::table('model_has_permissions')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        app(PermissionRegistrar::class)->forgetCachedPermissions();


        $super_admin_role=Role::create(['name' => 'super-admin']);
        $admin_role=Role::create(['name' => 'admin']);
        $client_role=Role::create(['name' => 'client']);

        //Define permissions (every permission) for super admin

        $permissions = [
            'auth.loginAdmin',
            // ===== المواطن (client) =====
            'profile.index',
            'profile.show',
            'profile.update',
            'profile.destroyAvatar',

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
            'complain.myComplains',
            'complain.vote',
            'complain.unvote',
            'complain.show',
            'complain.update',
            'complain.destroy',

            'report.store',

            'queue.showService',
            'queue.join',
            'queue.info',

            // ===== الأدمن (Admin) =====
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
            'admin.queue.myServices',

            'admin.queue.statistics.overview',
            'admin.queue.statistics.serviceStats',
            'admin.queue.statistics.employeeStats',
            'admin.queue.statistics.history',

            'admin.complains.index',
            'admin.complains.show',
            'admin.complains.review',
            'admin.complains.status',

            // ===== إدارة المستخدمين (User management) =====
            'user.index',
            'user.create',
            'user.createAdmin',
            'user.update',
            'user.changePassword',
            'user.destroy',

            // ===== المشاريع (Projects) =====
            'project.index',
            'project.show',
            'project.create',
            'project.submitForReview',
            'project.approve',
            'project.reject',
            'project.pendingApproval',
            'project.update',
            'project.destroy',
            'project.publicIndex',
            'project.votable',
            'project.vote',
            'project.unvote',
            'project.votingStats',
            'project.applyVolunteer',
            'project.listDonations',
            'project.donationStats',
            'project.topDonors',
            'project.listVolunteerApplications',
            'project.approveVolunteerApplication',
            'project.rejectVolunteerApplication',
            'project.closeVoting',
            'project.recordDonation',
            'project.votingOverview',
            'project.votingStatistics',


            // ===== الأخبار (News) =====
            'news.store',
            'news.index',
            'news.show',
            'news.adminIndex',
            'news.adminShow',
            'news.review',
        ];

        $admin_permissions = [
            'auth.loginAdmin',
            'profile.destroyAvatar',
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
            'admin.queue.myServices',
            
            'admin.queue.statistics.overview',
            'admin.queue.statistics.serviceStats',
            'admin.queue.statistics.employeeStats',
            'admin.queue.statistics.history',

            'admin.complains.index',
            'admin.complains.show',
            'admin.complains.review',
            'admin.complains.status',

            // ===== إدارة المستخدمين (Admin's user management) =====
            'user.index',
            'user.create',
            'user.update',
            'user.changePassword',
            'user.destroy',

            // ===== المشاريع (Projects) =====
            'project.index',
            'project.show',
            'project.create',
            'project.submitForReview',
            'project.update',
            'project.destroy',
            'project.listDonations',
            'project.donationStats',
            'project.topDonors',
            'project.listVolunteerApplications',
            'project.approveVolunteerApplication',
            'project.rejectVolunteerApplication',
            'project.closeVoting',
            'project.recordDonation',
            'project.votingOverview',
            'project.votingStatistics',

            // ===== الأخبار (News) =====
            'news.store',
        ];

        $client_permissions = [
            'profile.index',
            'profile.show',
            'profile.update',
            'profile.destroyAvatar',

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
            'complain.myComplains',
            'complain.vote',
            'complain.unvote',
            'complain.show',
            'complain.update',
            'complain.destroy',

            'report.store',

            // ===== المشاريع (Projects - citizen actions) =====
            'project.show',
            'project.publicIndex',
            'project.votable',
            'project.vote',
            'project.unvote',
            'project.votingStats',
            'project.applyVolunteer',
            'project.listDonations',
            'project.donationStats',
            'project.topDonors',

            'admin.services.index',
            'admin.services.show',

            // ===== إدارة حساب المستخدم (User's own account) =====
            'user.update',
            'user.changePassword',
            'user.destroy',

            // ===== الأخبار (News) =====
            'news.index',
            'news.show',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission,'api');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

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

        // profile for super admin
        $profiles[] = [
            'user_id' => $superAdminUser->id,
            'image' => 'https://randomuser.me/api/portraits/men/31.jpg',
            'citizenship_score' => 100,
            'credibility_score' => 100,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ];

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

            // profile for admin
            $profiles[] = [
                'user_id' => $adminUser->id,
                'image' => 'https://randomuser.me/api/portraits/men/' . ($x + 31) . '.jpg',
                'citizenship_score' => 100,
                'credibility_score' => 100,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ];
        }


// client user

        //client
        for ( $x = 1; $x <= 20; $x++) {
            $clientUser = User::query()->create([
                'first_name' => 'client user ' . $x,
                'last_name' => 'User ' . $x,
                'email'=>    'user'.$x.'@gmail.com',
                'phone' => '093675779' . $x,
                'password' => Hash::make('password123' ),
                'birth_date' => '1990-01-01',
                'account_status' => AccountStatus::Visitor->value,
                'email_verified_at' => Carbon::now(),
            ]);

            $clientUser->assignRole($client_role);

            // Assign permissions associated with the role to the user
            $permissions = $client_role->permissions()->pluck('name')->toArray();
            $clientUser->givePermissionTo($permissions);

            // profile for client
            $profiles[] = [
                'user_id' => $clientUser->id,
                'image' => 'https://randomuser.me/api/portraits/men/' . ($x + 40) . '.jpg',
                'citizenship_score' => 50,
                'credibility_score' => 50,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ];
        }

        // Save all profiles
        DB::table('profiles')->insert($profiles);
    }
}
