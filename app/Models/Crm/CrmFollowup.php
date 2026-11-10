<?php

namespace App\Models\Crm;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Model;

class CrmFollowup extends Model
{
    protected $table = 'zhpicture.crm_followups';
    protected $guarded = ['id'];
    protected $casts = ['jadwal_berikutnya' => 'date'];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
}
