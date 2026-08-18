<?php

namespace App\Services;

use App\Models\Donation;
use App\Models\Project;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ProjectDonationService
{
    private CitizenshipService $citizenshipService;

    public function __construct(CitizenshipService $citizenshipService)
    {
        $this->citizenshipService = $citizenshipService;
    }

    public function record($request): array
    {
        $project = Project::query()->find($request['id']);

        if (!$project) {
            return ['data' => null, 'message' => 'project not found', 'code' => 404];
        }

        if (!$project->is_donation) {
            return ['data' => null, 'message' => 'this project does not accept donations', 'code' => 422];
        }

        $admin = auth('api')->user();

        $donation = DB::transaction(function () use ($project, $request, $admin) {
            $donation = Donation::query()->create([
                'project_id' => $project->id,
                'user_id' => $request['donor_user_id'],
                'payment' => $request['amount'],
                'recorded_by' => $admin->id,
            ]);

            $increaseAmount = $this->citizenshipService->donationAmountToIncreaseAmount((float) $request['amount']);

            if ($increaseAmount > 0) {
                $donor = User::query()->find($request['donor_user_id']);
                $this->citizenshipService->increase($donor, $increaseAmount);
            }

            return $donation;
        });

        // Note: deliberately not eager-loading the recordedBy() relation here -
        // its snake_case key ("recorded_by") collides with the raw recorded_by
        // FK column and would silently replace the integer with a full User
        // object in the serialized response.
        return ['data' => $donation->load('user.profile'), 'message' => 'donation recorded successfully', 'code' => 201];
    }

    public function listDonations($request): array
    {
        $project = Project::query()->find($request['id']);

        if (!$project) {
            return ['data' => null, 'message' => 'project not found', 'code' => 404];
        }

        // See the note in record() re: not eager-loading recordedBy().
        $donations = Donation::query()
            ->where('project_id', $project->id)
            ->with(['user.profile'])
            ->latest()
            ->paginate(15);

        return ['data' => $donations, 'message' => 'donations retrieved successfully', 'code' => 200];
    }

    public function stats($request): array
    {
        $project = Project::query()->find($request['id']);

        if (!$project) {
            return ['data' => null, 'message' => 'project not found', 'code' => 404];
        }

        $aggregate = Donation::query()
            ->where('project_id', $project->id)
            ->selectRaw('COALESCE(SUM(payment), 0) as total_donated, COUNT(DISTINCT user_id) as number_of_donors')
            ->first();

        $totalDonated = (float) $aggregate->total_donated;
        $donationTarget = $project->budget !== null ? (float) $project->budget : null;

        $remainingAmount = $donationTarget !== null ? max($donationTarget - $totalDonated, 0) : null;
        $donationPercentage = ($donationTarget !== null && $donationTarget > 0)
            ? min(($totalDonated / $donationTarget) * 100, 100)
            : 0;

        return [
            'data' => [
                'total_donated' => $totalDonated,
                'donation_target' => $donationTarget,
                'remaining_amount' => $remainingAmount,
                'donation_percentage' => round($donationPercentage, 2),
                'number_of_donors' => (int) $aggregate->number_of_donors,
            ],
            'message' => 'donation statistics retrieved successfully',
            'code' => 200,
        ];
    }

    public function topDonors($request): array
    {
        $project = Project::query()->find($request['id']);

        if (!$project) {
            return ['data' => null, 'message' => 'project not found', 'code' => 404];
        }

        $donors = Donation::query()
            ->where('project_id', $project->id)
            ->select('user_id')
            ->selectRaw('SUM(payment) as total_donated, COUNT(*) as donation_count')
            ->groupBy('user_id')
            ->orderByDesc('total_donated')
            ->with('user.profile')
            ->limit($request['limit'] ?? 10)
            ->get();

        return ['data' => $donors, 'message' => 'top donors retrieved successfully', 'code' => 200];
    }
}
