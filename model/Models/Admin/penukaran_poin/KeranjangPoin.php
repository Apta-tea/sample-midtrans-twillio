<?php

namespace App\Models\Admin\penukaran_poin;

use App\Models\Admin\produk\Produk;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KeranjangPoin extends Model
{
    use HasFactory;
    protected $guarded = '';
    protected $with = [
        'user',
        'produk_poin',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function produk_poin()
    {
        return $this->belongsToMany(ProdukPoin::class)->orderBy('produk_poin_id');
    }

    public function produk()
    {
        return $this->belongsToMany(Produk::class);
    }

    public function penukaran_poin()
    {
        return $this->hasOne(PenukaranPoin::class);
    }
}
