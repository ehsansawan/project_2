<?php

namespace Tests\Concerns;

use App\Models\Profile;
use App\Models\User;
use Spatie\Permission\Models\Permission;

trait AuthenticatesUsers
{
    protected function makeUser(array $attributes = []): User
    {
        return User::factory()->create($attributes);
    }

    protected function makeProfile(User $user, int $citizenshipScore = 50): Profile
    {
        return Profile::create([
            'user_id' => $user->id,
            'citizenship_score' => $citizenshipScore,
            'credibility_score' => 50,
        ]);
    }

    /**
     * @return array{0: User, 1: array<string, string>} [user, auth headers]
     */
    protected function actingAsApi(User $user, array $permissions = []): array
    {
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'api');
        }

        if (!empty($permissions)) {
            $user->givePermissionTo($permissions);
        }

        $token = auth('api')->login($user);

        return [$user, ['Authorization' => "Bearer {$token}"]];
    }
}
