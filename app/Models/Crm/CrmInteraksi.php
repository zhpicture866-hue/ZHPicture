<?php

namespace App\Models\Crm;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Model;

class CrmInteraksi extends Model
{
    protected $table = 'zhpicture.crm_interaksi';
    protected $guarded = ['id'];
    protected $casts = ['tanggal' => 'date', 'diterima_at' => 'datetime', 'dibalas_at' => 'datetime'];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
}
