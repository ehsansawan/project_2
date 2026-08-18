<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\CitizenshipService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\AuthenticatesUsers;
use Tests\TestCase;

class CitizenshipServiceTest extends TestCase
{
    use RefreshDatabase;
    use AuthenticatesUsers;

    private CitizenshipService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(CitizenshipService::class);
    }

    public function test_donation_amount_tiers_map_to_the_correct_increase_amount(): void
    {
        $this->assertSame(0.0, $this->service->donationAmountToIncreaseAmount(999));
        $this->assertSame(10.0, $this->service->donationAmountToIncreaseAmount(1000));
        $this->assertSame(10.0, $this->service->donationAmountToIncreaseAmount(9999));
        $this->assertSame(15.0, $this->service->donationAmountToIncreaseAmount(10000));
        $this->assertSame(15.0, $this->service->donationAmountToIncreaseAmount(99999));
        $this->assertSame(20.0, $this->service->donationAmountToIncreaseAmount(100000));
        $this->assertSame(20.0, $this->service->donationAmountToIncreaseAmount(999999));
        $this->assertSame(25.0, $this->service->donationAmountToIncreaseAmount(1000000));
        $this->assertSame(25.0, $this->service->donationAmountToIncreaseAmount(5000000));
    }

    public function test_volunteering_count_tiers_grow_with_repeat_volunteering(): void
    {
        $this->assertSame(0.0, $this->service->volunteeringCountToIncreaseAmount(0));

        $first = $this->service->volunteeringCountToIncreaseAmount(1);
        $second = $this->service->volunteeringCountToIncreaseAmount(2);
        $fourth = $this->service->volunteeringCountToIncreaseAmount(4);
        $seventh = $this->service->volunteeringCountToIncreaseAmount(7);
        $twentieth = $this->service->volunteeringCountToIncreaseAmount(20);

        // Monotonically non-decreasing: more approved volunteering never earns less.
        $this->assertGreaterThan(0, $first);
        $this->assertGreaterThanOrEqual($first, $second);
        $this->assertGreaterThanOrEqual($second, $fourth);
        $this->assertGreaterThanOrEqual($fourth, $seventh);
        $this->assertSame($seventh, $twentieth); // top tier caps out, doesn't grow forever
    }

    public function test_increase_never_pushes_citizenship_score_above_100(): void
    {
        $user = User::factory()->create();
        $this->makeProfile($user, 99);

        // The largest possible single increase() call, repeated well past what
        // it would take to overshoot 100 without the cap.
        for ($i = 0; $i < 20; $i++) {
            $newScore = $this->service->increase($user, 25);
            $this->assertLessThanOrEqual(100.0, $newScore);
        }

        $finalScore = $user->profile()->first()->citizenship_score;
        $this->assertLessThanOrEqual(100, $finalScore);
    }

    public function test_increase_raises_the_score_from_a_mid_range_starting_point(): void
    {
        $user = User::factory()->create();
        $this->makeProfile($user, 50);

        $newScore = $this->service->increase($user, 10);

        $this->assertGreaterThan(50.0, $newScore);
        $this->assertLessThanOrEqual(100.0, $newScore);
    }
}
