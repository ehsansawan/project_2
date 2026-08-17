<?php

namespace App\Services;

use App\Models\Profile;
use App\Models\ScoreLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CitizenshipService
{
    private const MAX_SCORE = 100;
    private const MIN_SCORE = 0;

    /**
     * زيادة مؤشر المواطنة
     * المعادلة: القيمة الجديدة = القيمة القديمة + (مقدار الزيادة ÷ القيمة القديمة) × معامل الزيادة
     */
    public function increase(User $user, float $amount = 10): float
    {
        return DB::transaction(function () use ($user, $amount) {
            $profile = $this->profileOf($user);
            $current = (float) $profile->citizenship_score;
            
            $factor = $this->getIncreaseFactor($current);
            $denominator = max($current, 1); // حماية من القسمة على صفر
            
            $gain = ($amount / $denominator) * $factor;
            $newScore = min(self::MAX_SCORE, $current + $gain);
            
            $profile->update(['citizenship_score' => $newScore]);
            
            $this->log($user, $gain, "citizenship_increase_amount_{$amount}_factor_{$factor}");
            
            return $newScore;
        });
    }

    /**
     * نقصان مؤشر المواطنة
     * المعادلة: القيمة الجديدة = القيمة القديمة - (مقدار النقصان ÷ (100 - القيمة القديمة)) × معامل النقصان
     */
    public function decrease(User $user, float $amount = 10): float
    {
        return DB::transaction(function () use ($user, $amount) {
            $profile = $this->profileOf($user);
            $current = (float) $profile->citizenship_score;
            
            $factor = $this->getDecreaseFactor($current);
            $denominator = max(self::MAX_SCORE - $current, 1); // حماية من القسمة على صفر
            
            $loss = ($amount / $denominator) * $factor;
            $newScore = max(self::MIN_SCORE, $current - $loss);
            
            $profile->update(['citizenship_score' => $newScore]);
            
            $this->log($user, -$loss, "citizenship_decrease_amount_{$amount}_factor_{$factor}");
            
            return $newScore;
        });
    }

    /**
     * زيادة ثابتة (بدون معاملات)
     */
    public function increaseFixed(User $user, int $points = 5): float
    {
        return DB::transaction(function () use ($user, $points) {
            $profile = $this->profileOf($user);
            $newScore = min(self::MAX_SCORE, (float) $profile->citizenship_score + $points);
            
            $profile->update(['citizenship_score' => $newScore]);
            $this->log($user, $points, 'citizenship_increase_fixed');
            
            return $newScore;
        });
    }

    private function profileOf(User $user): Profile
    {
        return $user->profile()->firstOrCreate(['user_id' => $user->id]);
    }

    private function log(User $user, float $change, string $reason): void
    {
        ScoreLog::create([
            'user_id' => $user->id,
            'point_change' => $change,
            'type' => 'citizenship',
            'reason' => $reason,
        ]);
    }

    private function getIncreaseFactor(float $score): float
    {
        return match(true) {
            $score >= 0 && $score < 20 => 10.0,
            $score >= 20 && $score < 40 => 8.0,
            $score >= 40 && $score < 60 => 6.0,
            $score >= 60 && $score < 70 => 5.0,
            $score >= 70 && $score < 80 => 4.0,
            $score >= 80 && $score < 90 => 2.0,
            $score >= 90 && $score <= 100 => 1.0,
            default => 1.0,
        };
    }

    private function getDecreaseFactor(float $score): float
    {
        return match(true) {
            $score >= 0 && $score < 40 => 1.0,
            $score >= 40 && $score < 60 => 2.0,
            $score >= 60 && $score < 70 => 3.0,
            $score >= 70 && $score < 80 => 4.0,
            $score >= 80 && $score < 90 => 6.0,
            $score >= 90 && $score <= 100 => 7.0,
            default => 1.0,
        };
    }
}