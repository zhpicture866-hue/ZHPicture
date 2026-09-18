<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RabProcess extends Model
{
    protected $table = 'zhpicture.offer_process';

    protected $fillable = [
        'project_id',
        'contact_name',
        'job_location',
        'job_duration',
        'base_subtotal',
        'subtotal',
        'discount',
        'subtotal_after_discount',
        'tax_rate',
        'tax_total',
        'shipping',
        'overhead',
        'profit',
        'grand_total',
        'notes',
        'created_by',
        'updated_by',
        'analisa_version',
    ];

        public function project()
    {
        return $this->belongsTo(Project::class);
    }
    
    public function items()
    {
        return $this->hasMany(RabProcessItem::class, 'rab_process_id');
    }

    // public function categories()
    // {
    //     return $this->hasMany(RabProcessCategory::class)
    //         ->orderBy('order_no');
    // }

    //     public function uraians()
    // {
    //     return $this->hasMany(RabProcessUraian::class);
    // }

        public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

}
