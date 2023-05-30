<?php

namespace App\Models\Admin\produk;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FotoProduk extends Model
{
    use HasFactory;
    protected $guarded = '';
    public function produk()
    {
        return $this->belongsTo(Produk::class);
    }
}
