<?php

namespace App\Models;

use Illuminate\Notifications\DatabaseNotification as LaravelDatabaseNotification;

class DatabaseNotification extends LaravelDatabaseNotification
{
    /**
     * Tabel notifications berada di schema zhpicture.
     */
    protected $table = 'zhpicture.notifications';
}