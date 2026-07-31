<?php

namespace App\Services;

use App\Models\Geofence;
use App\Models\Project;

class GeofenceService
{
    public const MAX_ACCURACY_METERS = 50;

    /**
     * Keeps a project's linked geofence in sync with its coordinates and status.
     *
     * - Project with coordinates: geofence created (or updated) with the project's
     *   center and radius. is_active mirrors the project status (active only).
     * - Radius is only set on creation — admin edits on the geofence are preserved.
     * - Project without coordinates: any linked geofence is deactivated but kept.
     */
    public static function syncFromProject(Project $project): void
    {
        $geofence = Geofence::where('project_id', $project->id)->first();

        $hasCoords = $project->latitude !== null && $project->longitude !== null;

        if (! $hasCoords) {
            if ($geofence && $geofence->is_active) {
                $geofence->update(['is_active' => false]);
            }

            return;
        }

        if (! $geofence) {
            $geofence = Geofence::create([
                'name' => $project->name.($project->project_code ? ' ('.$project->project_code.')' : ''),
                'description' => 'Auto-created from project '.$project->name,
                'latitude' => $project->latitude,
                'longitude' => $project->longitude,
                'radius_meters' => $project->radius_meters ?? 100,
                'is_active' => $project->status === 'active',
                'project_id' => $project->id,
                'created_by' => $project->project_manager_id,
            ]);

            return;
        }

        $geofence->update([
            'name' => $project->name.($project->project_code ? ' ('.$project->project_code.')' : ''),
            'latitude' => $project->latitude,
            'longitude' => $project->longitude,
            'is_active' => $project->status === 'active',
        ]);
    }

    /**
     * Haversine distance in meters between two coordinates.
     */
    public static function distanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000;

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }

    /**
     * Returns the nearest active geofence containing the given coordinates, or null.
     */
    public static function findContaining(float $lat, float $lng): ?Geofence
    {
        $geofences = Geofence::where('is_active', true)->get();

        $nearest = null;
        $nearestDistance = null;

        foreach ($geofences as $geofence) {
            $distance = self::distanceMeters($lat, $lng, (float) $geofence->latitude, (float) $geofence->longitude);
            if ($distance <= (float) $geofence->radius_meters && ($nearestDistance === null || $distance < $nearestDistance)) {
                $nearest = $geofence;
                $nearestDistance = $distance;
            }
        }

        return $nearest;
    }

    /**
     * Validates a GPS accuracy reading against the configured threshold.
     */
    public static function accuracyError(mixed $accuracy): ?string
    {
        if (! is_numeric($accuracy) || (float) $accuracy <= 0) {
            return 'A valid GPS accuracy reading is required.';
        }

        if ((float) $accuracy > self::MAX_ACCURACY_METERS) {
            return 'GPS accuracy must be within '.self::MAX_ACCURACY_METERS.'m to punch.';
        }

        return null;
    }
}
