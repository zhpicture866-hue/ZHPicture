<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CrmTemplate extends Model
{
    public function render(Customer $c, array $extra = []): string
{
    $name = $c->user?->fullname ?? '';

    $vars = array_merge([
        '{nama}'      => $name,
        '{panggilan}' => $c->user?->nickname ?: strtok($name, ' '),
        '{sapaan}'    => $c->display_name_with_title,   // "Bapak/Ibu ..."
    ], $extra);

    return strtr($this->body, $vars);
}
}
