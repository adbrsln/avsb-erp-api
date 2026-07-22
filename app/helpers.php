<?php

use Illuminate\Database\Eloquent\Model;

function fillableData(Model $model, array $data): array
{
    return array_intersect_key($data, array_flip($model->getFillable()));
}

function r2(float $v): float
{
    return round($v, 2);
}
