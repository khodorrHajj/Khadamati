<?php

namespace App\Services;

use App\Models\GovernmentOffice;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class CitizenOfficeDiscoveryService
{
    public function build(array $filters, int $perPage = 9): array
    {
        $query = GovernmentOffice::query()
            ->with('municipality')
            ->withCount([
                'services as active_services_count' => function ($query) {
                    $query->where('is_active', true);
                },
            ])
            ->where('status', 'active')
            ->whereHas('services', function ($query) {
                $query->where('is_active', true);
            })
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($officeQuery) use ($search) {
                    $officeQuery->where('name', 'like', '%' . $search . '%')
                        ->orWhere('service_type', 'like', '%' . $search . '%')
                        ->orWhere('city', 'like', '%' . $search . '%')
                        ->orWhereHas('municipality', function ($municipalityQuery) use ($search) {
                            $municipalityQuery->where('name', 'like', '%' . $search . '%');
                        })
                        ->orWhereHas('services', function ($serviceQuery) use ($search) {
                            $serviceQuery->where('is_active', true)
                                ->where('name', 'like', '%' . $search . '%');
                        })
                        ->orWhereHas('serviceCategories', function ($categoryQuery) use ($search) {
                            $categoryQuery->where('name', 'like', '%' . $search . '%');
                        });
                });
            })
            ->when($filters['municipality'] ?? null, function ($query, $municipalityId) {
                $query->where('municipality_id', $municipalityId);
            })
            ->when($filters['category'] ?? null, function ($query, $categoryId) {
                $query->whereHas('services', function ($serviceQuery) use ($categoryId) {
                    $serviceQuery->where('is_active', true)
                        ->where('service_category_id', $categoryId);
                });
            })
            ->orderBy('name');

        $offices = $query->get();

        $hasLocationFilter = $this->hasLocationFilter($filters);

        if ($hasLocationFilter) {
            $latitude = (float) $filters['latitude'];
            $longitude = (float) $filters['longitude'];
            $radiusKm = isset($filters['radius_km']) && $filters['radius_km'] !== null && $filters['radius_km'] !== ''
                ? (float) $filters['radius_km']
                : null;

            $offices = $offices
                ->map(function (GovernmentOffice $office) use ($latitude, $longitude) {
                    $office->distance_km = $office->hasCoordinates()
                        ? round($this->distanceKm($latitude, $longitude, $office->latitude, $office->longitude), 1)
                        : null;

                    return $office;
                })
                ->filter(function (GovernmentOffice $office) use ($radiusKm) {
                    if ($office->distance_km === null) {
                        return false;
                    }

                    return $radiusKm === null || $office->distance_km <= $radiusKm;
                })
                ->sortBy('distance_km')
                ->values();
        }

        return [
            'hasLocationFilter' => $hasLocationFilter,
            'offices' => $this->paginate($offices, $perPage),
        ];
    }

    private function hasLocationFilter(array $filters): bool
    {
        return isset($filters['latitude'], $filters['longitude'])
            && $filters['latitude'] !== null
            && $filters['latitude'] !== ''
            && $filters['longitude'] !== null
            && $filters['longitude'] !== '';
    }

    private function paginate(Collection $items, int $perPage): LengthAwarePaginator
    {
        $page = LengthAwarePaginator::resolveCurrentPage();
        $total = $items->count();
        $results = $items->forPage($page, $perPage)->values();

        return new LengthAwarePaginator(
            $results,
            $total,
            $perPage,
            $page,
            [
                'path' => LengthAwarePaginator::resolveCurrentPath(),
                'query' => request()->query(),
            ]
        );
    }

    private function distanceKm(float $latitudeA, float $longitudeA, float $latitudeB, float $longitudeB): float
    {
        $earthRadiusKm = 6371;

        $latFrom = deg2rad($latitudeA);
        $lonFrom = deg2rad($longitudeA);
        $latTo = deg2rad($latitudeB);
        $lonTo = deg2rad($longitudeB);

        $latitudeDelta = $latTo - $latFrom;
        $longitudeDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(
            pow(sin($latitudeDelta / 2), 2) +
            cos($latFrom) * cos($latTo) * pow(sin($longitudeDelta / 2), 2)
        ));

        return $angle * $earthRadiusKm;
    }
}
