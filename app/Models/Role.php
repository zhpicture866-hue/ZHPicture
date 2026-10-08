<?php

namespace App\Models;

use App\Traits\HasUuid;
use Spatie\Permission\Models\Role as SpatieRole;
use Illuminate\Database\Eloquent\Builder;

class Role extends SpatieRole
{
    use HasUuid;

    protected $keyType = 'string';
    public $incrementing = false;

        public function getRouteKeyName()
    {
        return 'id'; 
    }

    protected $fillable = [
        'name',
        'guard_name',
        'role_group',
    ];

        public function scopeInternal($query)
    {
        return $query->where('role_group', 'Internal');
    }

    public function scopeExternal($query)
    {
        return $query->where('role_group', 'Eksternal');
    }

    public const GUARD = 'web';

    protected static function booted(): void
    {
        static::addGlobalScope('guard', function (Builder $q) {
            $q->where($q->getModel()->getTable().'.guard_name', self::GUARD);
        });

        static::creating(function ($model) {
            $model->guard_name = self::GUARD;
        });
    }
}
