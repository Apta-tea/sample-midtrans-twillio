<?php

namespace App\Models\Admin\produk;

use App\Models\Admin\produk\Produk;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PoinProduk extends Model
{
    use HasFactory;
    protected $table = 'poin_produks';
    protected $guarded = '';

    protected static function booted(){
        static::created(function($poin_produk){
            $ppn = $poin_produk->produk->harga * $poin_produk->ppn / 100;
            $komisi = $poin_produk->produk->harga * $poin_produk->komisi / 100;
            $bv = $poin_produk->produk->harga - $komisi;
            $poin = $bv / 50000; // 50.000 di dapat dari power point kemarin
            $poin_produk->update([
                'bv' => $bv,
                'poin' => $poin
            ]);
        });
    }

    public function produk()
    {
        return $this->belongsTo(Produk::class);
    }

    public function hitungPoin()
    {
        $produk = $this->produk;
        $poinProduk = $this;
        $bv = $produk->harga - ($produk->harga * $poinProduk->komisi / 100);
        $poin = $bv / 50000;
        $poinProduk->update([
            'bv' => intval($bv),
            'poin' => intval($poin),
        ]);
        // dd($this);
    }
}
