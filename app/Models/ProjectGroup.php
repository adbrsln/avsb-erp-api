<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectGroup extends Model
{
    protected $table = 'project_groups';

    protected $fillable = [
        'name', 'description', 'color', 'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function projects()
    {
        return $this->belongsToMany(Project::class, 'project_project_group');
    }
}
