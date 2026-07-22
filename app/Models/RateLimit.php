<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RateLimit extends Model
{
    protected $table = 'rate_limits';

    protected $fillable = ['ip_hash', 'endpoint', 'count', 'window_start'];

    public $timestamps = false;
}
