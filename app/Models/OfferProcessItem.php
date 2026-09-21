<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OfferProcessItem extends Model
{
    protected $table = 'zhpicture.offer_process_items';

    protected $fillable = [
        'rab_process_id',
        'floor_name',
        'category_name',
        'job_name',
        'satuan',
        'volume',
        'base_price',
        'price',
        'total',
        'profit',
        'overhead',
        'description',
        'is_draft',
        'order_no',
    ];

    protected $casts = [
        'volume' => 'decimal:5',
        'base_price' => 'decimal:2',
        'price' => 'decimal:2',
        'total' => 'decimal:2',
        'profit' => 'decimal:2',
        'overhead' => 'decimal:2',
        'is_draft' => 'boolean',
        'order_no' => 'integer',
    ];

    public function rab()
    {
        return $this->belongsTo(RabProcess::class, 'rab_process_id');
    }

    public function getNamaPekerjaanAttribute()
    {
        return $this->job_name;
    }

    public function technicalJustificationItems(): HasMany
{
    return $this->hasMany(
        TechnicalJustificationItem::class,
        'rab_process_item_id'
    );
}
}
// class RabProcessItem extends Model
// {
//     protected $table = 'rab_process_items';
    
//     protected $fillable = [
//         'rab_process_id',
//         'job_category_id',
//         'floor_name',
//         'category_name',
//         'job_name',
//         'satuan',
//         'volume',
//         'base_price',
//         'price',
//         'total',
//         'uraian_id',
//         'order_no'
//     ];
//     public function category()
//     {
//         return $this->belongsTo(JobCategory::class, 'job_category_id');
//     }

//     public function rab()
//     {
//         return $this->belongsTo(RabProcess::class, 'rab_process_id');
//     }

//     public function getNamaPekerjaanAttribute()
//     {
//         return $this->job_name;
//     }

// }
