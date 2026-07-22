<?php

namespace App\Services;

class ActivityLogContext
{
    private static ?object $causer = null;

    public static function setCauser(?object $user): void
    {
        self::$causer = $user;
    }

    public static function getCauser(): ?object
    {
        return self::$causer;
    }

    public static function getCauserId(): ?int
    {
        $causer = self::$causer;
        if (! $causer) {
            return null;
        }

        return $causer->sub ?? $causer->id ?? null;
    }

    public static function clear(): void
    {
        self::$causer = null;
    }
}
