<?php

namespace App\Models\Concerns;

use App\Models\Crm\CrmCustomerProfile;
use App\Models\Crm\CrmFeedback;
use App\Models\Crm\CrmFollowup;
use App\Models\Crm\CrmInteraksi;
use App\Models\Crm\CrmJadwalReaktivasi;

/** Tambahkan `use HasCrm;` di dalam class App\Models\Customer. */
trait HasCrm
{
    public function crmProfil()
    {
        return $this->hasOne(CrmCustomerProfile::class, 'customer_id');
    }

    public function crmInteraksi()
    {
        return $this->hasMany(CrmInteraksi::class, 'customer_id');
    }

    public function crmFollowups()
    {
        return $this->hasMany(CrmFollowup::class, 'customer_id');
    }

    public function crmFeedbacks()
    {
        return $this->hasMany(CrmFeedback::class, 'customer_id');
    }

    public function crmJadwalReaktivasi()
    {
        return $this->hasMany(CrmJadwalReaktivasi::class, 'customer_id');
    }

    /** Nomor WhatsApp format 62xxx (dari users.phone, cadangan shipping_phone). */
    public function getWhatsappAttribute(): ?string
    {
        $n = preg_replace('/\D+/', '', (string) ($this->user?->phone ?: $this->shipping_phone));
        if ($n === '') {
            return null;
        }
        if (str_starts_with($n, '0')) {
            $n = '62' . substr($n, 1);
        }

        return $n;
    }

    /** loyalty_level (1-5) dari jumlah project, memakai config/crm.php. */
    public static function levelLoyaltyDariProject(int $jumlah): int
    {
        $level = 1;
        foreach (config('crm.loyalty_min_project') as $lv => $min) {
            if ($jumlah >= $min) {
                $level = max($level, (int) $lv);
            }
        }

        return $level;
    }
}
