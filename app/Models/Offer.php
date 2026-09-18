<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class Offer extends Model
{
    use HasUuid;

    protected $table = 'offers';
    protected $keyType = 'string';
    public $incrementing = false;
    public $timestamps = false;

    protected $casts = [
        'approved_at' => 'datetime',
        'contract_date' => 'date', // sekalian rapihin
    ];

    protected $fillable = [
        'project_id',
        'design_package_id',
        'rab_package_id',
        'rab_process_id',
        'offer_number',
        'offer_type',
        'offer_date',
        'contact_name',
        'volume',
        'subtotal',
        'subtotal_after_discount',
        'satuan',
        'price_meter',
        'total_price',
        'discount',
        'extra_discount',
        'tax_rate',
        'total_tax',
        'shipping',
        'grand_total',
        'notes',
        'created_by',
        'contract_number',
        'contract_date',
        'approved_at',
        'approved_by'
    ];

    public function items()
    {
        return $this->hasMany(OfferItem::class);
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function package()
    {
        return $this->belongsTo(DesignPackage::class, 'design_package_id');
    }

        public function rabpackage()
    {
        return $this->belongsTo(RabPackage::class, 'rab_package_id');
    }

    public function rab()
{
    return $this->belongsTo(RabProcess::class, 'rab_process_id');
}

    public function groupedItems()
{
    return $this->items
        ->groupBy('category')
        ->map(function ($items) {
            return $items;
        });
}

}
