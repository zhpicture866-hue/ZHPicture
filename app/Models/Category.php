<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Category extends Model
{
    protected $table = 'zhpicture.categories';

    protected $fillable = ['name', 'slug', 'parent_id'];
        protected static function booted()
    {
        static::saving(function ($category) {
            $base = Str::slug($category->slug ?: $category->name) ?: 'kategori';
            $slug = $base;
            $i = 2;
            while (static::where('slug', $slug)->where('id', '!=', $category->id ?? 0)->exists()) {
                $slug = $base . '-' . $i++;
            }
            $category->slug = $slug;
        });
    }
    public function articles()
    {
        return $this->belongsToMany(Article::class);
    }

    public function parent()
{
    return $this->belongsTo(self::class, 'parent_id');
}

public function children()
{
    return $this->hasMany(self::class, 'parent_id');
}

/** Semua kategori berurutan sebagai pohon; tiap item punya ->depth (0 = kategori utama). */
public static function flatTree(): \Illuminate\Support\Collection
{
    $all = static::orderBy('name')->get()->groupBy(fn ($c) => $c->parent_id ?? 0);
    $out = collect();

    $walk = function ($parentId, $depth) use (&$walk, $all, &$out) {
        foreach ($all->get($parentId, collect()) as $c) {
            $c->depth = $depth;
            $out->push($c);
            $walk($c->id, $depth + 1);
        }
    };
    $walk(0, 0);

    return $out;
}
}