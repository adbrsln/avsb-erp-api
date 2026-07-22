<?php

namespace App\Traits;

use App\Services\ActivityLogger;

trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function ($model) {
            ActivityLogger::on($model)
                ->withProperties(['attributes' => $model->toArray()])
                ->log('created', 'created');
        });

        static::updated(function ($model) {
            $changes = $model->getChanges();
            $original = array_intersect_key($model->getOriginal(), $changes);

            ActivityLogger::on($model)
                ->withProperties([
                    'old' => $original,
                    'new' => $changes,
                ])
                ->log('updated', 'updated');
        });

        static::deleted(function ($model) {
            ActivityLogger::on($model)
                ->withProperties(['attributes' => $model->toArray()])
                ->log('deleted', 'deleted');
        });
    }
}
