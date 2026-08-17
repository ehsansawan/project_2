<?php

namespace Database\Factories;

use App\Enums\ProjectStatus;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    protected $model = Project::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'type' => 'municipal',
            'budget' => null,
            'is_votable' => false,
            'is_voluntary' => false,
            'is_donation' => false,
            'status' => ProjectStatus::Planning->value,
        ];
    }

    public function submitted(): static
    {
        return $this->state(fn () => ['status' => ProjectStatus::Submitted->value]);
    }

    public function approved(): static
    {
        return $this->state(fn () => ['status' => ProjectStatus::Approved->value]);
    }

    public function votable(): static
    {
        return $this->state(fn () => ['is_votable' => true]);
    }

    public function voluntary(): static
    {
        return $this->state(fn () => ['is_voluntary' => true]);
    }

    public function donatable(): static
    {
        return $this->state(fn () => ['is_donation' => true]);
    }
}
