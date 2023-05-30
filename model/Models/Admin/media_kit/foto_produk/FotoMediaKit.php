<?php

namespace App\Models\Admin\media_kit\foto_produk;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FotoMediaKit extends Model
{
    use HasFactory;
    protected $guarded = '';
    public function produk_media_kit()
    {
        return $this->belongsTo(ProdukMediaKit::class);
    }
}
