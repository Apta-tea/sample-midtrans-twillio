<?php

namespace App\Models\Admin\produk;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Admin\produk\PoinProduk;

class Produk extends Model
{
    use HasFactory;
    protected $guarded = '';
    protected $with = ['poin_produk', 'foto_produk', 'tag_produk'];
    protected $appends = [
        'berat_dari_volume'
    ];

    public static function booted()
    {
        static::created(function($produk){
            PoinProduk::create([
                'produk_id' => $produk->id,
            ]);
        });
    }

    public function tag_produk()
    {
        return $this->belongsToMany(TagProduk::class);
    }
    public function foto_produk()
    {
        return $this->hasMany(FotoProduk::class);
    }
    public function poin_produk()
    {
        return $this->hasOne(PoinProduk::class);
    }

    //relation buat factory
    public function tagProduk()
    {
        return $this->belongsToMany(TagProduk::class);
    }
    public function fotoProduk()
    {
        return $this->hasMany(FotoProduk::class);
    }
    public function poinProduk()
    {
        return $this->hasOne(PoinProduk::class);
    }

    // OTHER FUNCTION
    public function getBeratDariVolumeAttribute()
    {
        $explarr = explode('x', $this->dimensi);
        if(count($explarr) == 1){
            $explarr = explode('X', $this->dimensi);
        }
        if(count($explarr) == 0){
            return 0;
        }
        $intarr = [];
        foreach ($explarr as  $char) {
            if(is_numeric(trim($char))){
                array_push($intarr, intval($char));
            }
        }
        // rumus hitung berat ekspedisi dari volume adalah p*l*t / 6000
        $gramFromDimensi = 1;
        foreach ($intarr as  $val) {
            $gramFromDimensi *= $val; 
        }
        $gramFromDimensi /= 6000; 
        return $gramFromDimensi * 1000;
    }
}
