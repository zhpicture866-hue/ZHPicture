<?php

namespace App\Models\Crm;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Model;

class CrmJadwalReaktivasi extends Model
{
    protected $table = 'zhpicture.crm_jadwal_reaktivasi';
    protected $guarded = ['id'];
    protected $casts = ['tanggal_jadwal' => 'date'];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
}
