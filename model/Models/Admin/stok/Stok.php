<?php

namespace App\Models\Admin\stok;

use App\Models\Admin\produk\Produk;
use App\Models\Home\Keranjang;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Stok extends Model
{
    use HasFactory;
    protected $guarded = '';
    protected $with = [
        'produk',

    ];
    protected $appends = ['jumlah_stok'];
    public function produk()
    {
        return $this->belongsTo(Produk::class);
    }

    public function keranjang()
    {
        return $this->belongsTo(Keranjang::class);
    }

    public function getJumlahStokAttribute()
    {
        return Stok::where('stok_id', $this->stok_id)->where('status', $this->status)->count();
    }
}
