<?php

namespace App\Models\Admin\produk;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TagProduk extends Model
{
    use HasFactory;
    protected $guarded = '';
    protected $appends = ['icon_asset'];

    public function produk()
    {
        return $this->belongsToMany(Produk::class);
    }
    public function getIconAssetAttribute()
    {
        return asset($this->icon);
    }
}
