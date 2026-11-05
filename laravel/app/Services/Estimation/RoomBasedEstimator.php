<?php

namespace App\Services\Estimation;

class RoomBasedEstimator
{
    public function __construct(
        protected ResidentialEstimator $residentialEstimator
    ) {}

    public function estimate(
        int $rooms = 3,
        int $toilets = 3,
        int $balconies = 1,
        int $floors = 1,
        bool $includeLivingDining = true,
        bool $includeKitchen = true,
        bool $seismicZoneV = true
    ): array {
        $livingDiningSqft = $includeLivingDining ? 220.0 : 0.0;
        $kitchenSqft = $includeKitchen ? 90.0 : 0.0;
        $toiletUnitSqft = 45.0;
        $balconyUnitSqft = 50.0;

        $bedroomAreaSqft = 0.0;
        $roomBreakdown = [];
        for ($i = 1; $i <= $rooms; $i++) {
            if ($i === 1) {
                $area = 150.0;
                $name = 'Master Bedroom';
            } elseif ($i === 2) {
                $area = 130.0;
                $name = 'Bedroom 2';
            } else {
                $area = 120.0;
                $name = "Bedroom {$i}";
            }
            $bedroomAreaSqft += $area;
            $roomBreakdown[$name] = "{$area} sq.ft";
        }

        $toiletsTotalSqft = $toilets * $toiletUnitSqft;
        $balconiesTotalSqft = $balconies * $balconyUnitSqft;

        $carpetAreaPerFloorSqft = (
            $bedroomAreaSqft +
            $livingDiningSqft +
            $kitchenSqft +
            $toiletsTotalSqft +
            $balconiesTotalSqft
        );

        $wallAndCirculationSqft = round($carpetAreaPerFloorSqft * 0.22, 2);
        $builtUpAreaPerFloorSqft = round($carpetAreaPerFloorSqft + $wallAndCirculationSqft, 2);

        $estimation = $this->residentialEstimator->estimate(
            builtUpAreaSqft: $builtUpAreaPerFloorSqft,
            floors: $floors,
            seismicZoneV: $seismicZoneV
        );

        $estimation['architectural_configuration'] = [
            'rooms_count' => $rooms,
            'toilets_count' => $toilets,
            'balconies_count' => $balconies,
            'floors_count' => $floors,
            'room_size_details' => $roomBreakdown,
            'living_dining_sqft' => $livingDiningSqft,
            'kitchen_sqft' => $kitchenSqft,
            'toilets_total_sqft' => $toiletsTotalSqft,
            'balconies_total_sqft' => $balconiesTotalSqft,
            'carpet_area_per_floor_sqft' => round($carpetAreaPerFloorSqft, 2),
            'walls_and_circulation_sqft' => $wallAndCirculationSqft,
            'calculated_built_up_area_per_floor_sqft' => $builtUpAreaPerFloorSqft,
            'total_built_up_area_all_floors_sqft' => round($builtUpAreaPerFloorSqft * $floors, 2),
        ];

        return $estimation;
    }
}

