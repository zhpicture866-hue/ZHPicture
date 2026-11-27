<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FinanceTarget extends Model
{
    public const TIPE = ['omset', 'cost', 'profit'];

    protected $table = 'zhpicture.finance_targets';

    protected $fillable = ['year', 'type', 'month', 'target'];

    protected $casts = [
        'year'   => 'integer',
        'month'  => 'integer',
        'target' => 'decimal:2',
    ];
}