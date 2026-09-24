<?php

namespace App\Models;

use App\Traits\HasUuid;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    protected $table = 'zhpicture.invoices';
    use HasUuid;

    const TYPE_SURVEY  = 'survey';
    const TYPE_DP      = 'dp';
    const TYPE_FINAL   = 'final';
    const TYPE_RAB     = 'rab';
    const TYPE_BUILD   = 'build';
    const TYPE_WEDDING = 'wedding'; // baru — dipakai buat flow wedding syariah (1 invoice per project)

    const STATUS_DRAFT    = 'draft';
    const STATUS_WAITING  = 'waiting_approval';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_PAID     = 'paid';

    protected $keyType = 'string';
    public $incrementing = false;

    protected $casts = [
        'invoice_date' => 'date',
        'approved_at'  => 'datetime',
        'rejected_at'  => 'datetime',
    ];

    protected $fillable = [
        'project_id',
        'invoice_number',
        'invoice_date',
        'invoice_type',
        'termin_no',
        'termin_label',
        'amount',
        'status',
        'approved_at',
        'approved_by',
        'approval_token',
        'rejected_at',
        'rejected_by',
        'reject_note',
        'invoice_dp_downloaded_at',
        'invoice_dp_approved_at',
        'downloaded_at',
        'approve_by_name',
        'approved_ip',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function scopeOrderByTermin($query)
    {
        return $query->orderBy('termin_no');
    }

    // planning() dihapus — itu peninggalan copy-paste dari model lain, Invoice tidak ada hubungan ke Planning.
}