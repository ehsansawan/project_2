<?php

namespace App\Repositories;

use App\Models\Complain;
use App\Models\ComplainMedia;
use App\Models\MapPin;

class ComplainRepository
{
    public function createMapPin(float $latitude, float $longitude): MapPin
    {
        return MapPin::query()->create([
            'latitude' => $latitude,
            'longitude' => $longitude,
            'type' => 'other',
        ]);
    }

    public function createComplain(array $data): Complain
    {
        return Complain::query()->create($data);
    }

    public function attachMedia(int $complainId, array $mediaPaths): void
    {
        foreach ($mediaPaths as $media) {
            ComplainMedia::query()->create([
                'complain_id' => $complainId,
                'file_path' => $media['file_path'],
                'media_type' => $media['media_type'],
            ]);
        }
    }
}