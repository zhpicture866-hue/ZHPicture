<?php

namespace App\Models\Crm;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Model;

class CrmCustomerProfile extends Model
{
    protected $table = 'zhpicture.crm_customer_profiles';
    protected $guarded = ['id'];
    protected $casts = [];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
}
