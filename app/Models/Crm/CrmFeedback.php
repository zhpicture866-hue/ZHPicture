<?php

namespace App\Models\Crm;

use App\Models\Customer;
use Illuminate\Database\Eloquent\Model;

class CrmFeedback extends Model
{
    protected $table = 'zhpicture.crm_feedbacks';
    protected $guarded = ['id'];
    protected $casts = ['tanggal' => 'date'];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
}
