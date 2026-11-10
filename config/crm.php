<?php

return [
    /*
     | Jumlah project minimal agar customer masuk ke loyalty_level tertentu.
     | Level mengikuti Customer::getReadableLoyaltyLevelAttribute():
     | 1 Lead, 2 New Customer, 3 Silver, 4 Gold, 5 Platinum.
     | Angka mengikuti spreadsheet ZH CRM KPI: 1x = New Customer, >1x Silver, >10x Gold, >100x Platinum.
     */
    'loyalty_min_project' => [1 => 0, 2 => 1, 3 => 2, 4 => 11, 5 => 101],

    // Jadwal follow-up reaktivasi, dihitung dari tanggal project terakhir customer.
    'reaktivasi_interval' => ['H+2' => 2, 'H+30' => 30, 'H+90' => 90, 'H+1 tahun' => 365],

    'sumber' => [
        'instagram' => 'Instagram', 'tiktok' => 'TikTok', 'whatsapp' => 'WhatsApp',
        'offline' => 'Offline', 'referral' => 'Referral', 'fl' => 'FL', 'lainnya' => 'Lainnya',
    ],

    'platform' => [
        'instagram' => 'Instagram', 'tiktok' => 'TikTok', 'facebook' => 'Facebook',
        'whatsapp' => 'WhatsApp', 'offline' => 'Offline', 'lainnya' => 'Lainnya',
    ],

    'kanal' => ['wa' => 'WhatsApp', 'telepon' => 'Telepon', 'meeting' => 'Meeting', 'offline' => 'Offline'],
];
