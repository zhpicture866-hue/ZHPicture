<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectTypeLevel extends Model
{
    protected $table = 'zhpicture.project_type_levels';

    protected $fillable = [
        'project_type_id',
        'level_order',
        'level_name',
    ];

    public function projectType()
    {
        return $this->belongsTo(ProjectType::class);
    }
}