<?php

namespace App\Services;

use App\Enums\AccountStatus;
use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\Profile;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserService
{
    public function index($request): array
    {
        $query = User::query()->with('roles');

        if (!empty($request['roles'])) {
            $query->role($request['roles']);
        }

        $users = $query->latest()->paginate(15);

        return ['data' => $users, 'message' => 'users retrieved successfully', 'code' => 200];
    }

    public function create($request): array
    {
        $user = User::query()->create([
            'first_name' => $request['first_name'] ?? null,
            'last_name' => $request['last_name'] ?? null,
            'email' => $request['email'] ?? null,
            'phone' => $request['phone'] ?? null,
            'national_id' => $request['national_id'] ?? null,
            'password' => Hash::make($request['password']),
            'birth_date' => $request['birth_date'] ?? null,
            'account_status' => AccountStatus::Verified->value,
        ]);

        if (!$user) {
            return ['data' => $user, 'message' => 'something went wrong, try again later', 'code' => 500];
        }

        Profile::query()->create(['user_id' => $user->id,
                                  'citizenship_score' => 50,
                                  'credibility_score' => 50,]);

        $clientRole = Role::query()->where('name', 'client')->first();
        $user->assignRole($clientRole);

        $permissions = $clientRole->permissions()->pluck('name')->toArray();
        $user->givePermissionTo($permissions);

      //  $user->load('roles', 'permissions');
        $user=$user->load('profile');

        return ['data' => $user, 'message' => 'user created successfully', 'code' => 201];
    }
    public function createAdmin($request): array
    {
        $user = User::query()->create([
            'first_name' => $request['first_name'] ?? null,
            'last_name' => $request['last_name'] ?? null,
            'email' => $request['email'] ?? null,
            'phone' => $request['phone'] ?? null,
            'national_id' => $request['national_id'] ?? null,
            'password' => Hash::make($request['password']),
            'birth_date' => $request['birth_date'] ?? null,
            'account_status' => AccountStatus::Verified->value,
        ]);

        if (!$user) {
            return ['data' => $user, 'message' => 'something went wrong, try again later', 'code' => 500];
        }

        Profile::query()->create(['user_id' => $user->id,
            'citizenship_score' => 100,
            'credibility_score' => 100,]);

        $adminRole = Role::query()->where('name', 'admin')->first();
        $user->assignRole($adminRole);

        $permissions = $adminRole->permissions()->pluck('name')->toArray();
        $user->givePermissionTo($permissions);

        $user->load('roles');

        return ['data' => $user, 'message' => 'admin created successfully', 'code' => 201];
    }

    public function update($request): array
    {
        $user = auth('api')->user();

        $allowed = ['phone', 'fcm_token'];

        $data = collect($request)->only($allowed)->filter(fn ($value) => $value !== null)->toArray();

        if (empty($data)) {
            return ['data' => $user, 'message' => 'nothing to update', 'code' => 422];
        }

        $user->update($data);

        $user->load('roles');

        return ['data' => $user, 'message' => 'user updated successfully', 'code' => 200];
    }
    public function destroy($request): array
    {
        $actor = auth('api')->user();
        $targetUser = User::query()->find($request['id']);

        if (!$targetUser) {
            return ['data' => null, 'message' => 'user not found', 'code' => 404];
        }

        $actorRoles = $actor->getRoleNames()->toArray();
        $targetRoles = $targetUser->getRoleNames()->toArray();

        $isSuperAdmin = in_array('super-admin', $actorRoles);
        $isAdmin = in_array('admin', $actorRoles);
        $targetIsSuperAdmin = in_array('super-admin', $targetRoles);
        $targetIsAdmin = in_array('admin', $targetRoles);

        if ($targetIsSuperAdmin) {
            return ['data' => null, 'message' => 'you cannot delete a super admin user', 'code' => 403];
        }

        if (!$isSuperAdmin && !$isAdmin) {
            if ($actor->id !== $targetUser->id) {
                return ['data' => null, 'message' => 'you can only delete your own account', 'code' => 403];
            }
        }

        if ($isAdmin && !$isSuperAdmin && $targetIsAdmin) {
            return ['data' => null, 'message' => 'an admin cannot delete another admin', 'code' => 403];
        }

        AuditLog::query()->create([
            'user_id' => $actor->id,
            'auditable_type' => User::class,
            'auditable_id' => $targetUser->id,
            'action' => AuditAction::Delete->value,
        ]);

        $targetUser->delete();

        return ['data' => null, 'message' => 'user deleted successfully', 'code' => 200];
    }
}
