<?php

namespace App\Services;

// app/Services/CredibilityService.php
use App\Models\Profile;
use App\Models\ScoreLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CredibilityService
{
    private const MAX_SCORE = 100;

    /**
     * يزيد المصداقية بشكل نسبي (من المسافة الباقية لـ 100)
     */
    public function increase(User $user, float $factor = 0.05): float
    {
        return DB::transaction(function () use ($user, $factor) {
            $profile = $this->profileOf($user);

            $current = (float) $profile->credibility_score;
            $gain = (self::MAX_SCORE - $current) * $factor;

            $newScore = min(self::MAX_SCORE, $current + $gain);
            $profile->update(['credibility_score' => $newScore]);

            $this->log($user, $gain, "credibility_increase_factor_{$factor}");

            return $newScore;
        });
    }

    /**
     * نقصان نسبي: نسبة من القيمة الحالية → لا ينزل تحت 0
     */
    public function decrease(User $user, float $factor = 0.10): float
    {
        return DB::transaction(function () use ($user, $factor) {
            $profile = $this->profileOf($user);

            $current = (float) $profile->credibility_score;

            $loss = $current * $factor;      // نقصان من القيمة الحالية
            $newScore = max(0, $current - $loss);

            $profile->update(['credibility_score' => $newScore]);

            $this->log($user, -$loss, "credibility_decrease_factor_{$factor}");

            return $newScore;
        });
    }

    public function increaseFixed(User $user, int $points = 5): float
    {
        return DB::transaction(function () use ($user, $points) {
            $profile = $this->profileOf($user);

            $newScore = min(self::MAX_SCORE, (float) $profile->credibility_score + $points);
            $profile->update(['credibility_score' => $newScore]);

            $this->log($user, $points, 'credibility_increase_fixed');

            return $newScore;
        });
    }

    private function profileOf(User $user): Profile
    {
        // ينشئ profile إن لم يوجد (لإعادة الاستخدام في أي مكان)
        return $user->profile()->firstOrCreate(['user_id' => $user->id]);
    }

    private function log(User $user, float $change, string $reason): void
    {
        ScoreLog::create([
            'user_id' => $user->id,
            'point_change' => $change,
            'type' => 'credibility',
            'reason' => $reason,
        ]);
    }
}
