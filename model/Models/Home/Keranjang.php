<?php

namespace App\Models\Home;

use App\Models\Admin\produk\Produk;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Keranjang extends Model
{
    use HasFactory;
    protected $guarded = '';
    protected $with = ['produk', 'user', 'stok'];
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function produk()
    {
        return $this->belongsToMany(Produk::class)->orderByPivot('produk_id');
    }
    public function checkout()
    {
        return $this->hasOne(Checkout::class);
    }

    public function stok()
    {
        return $this->hasMany(\App\Models\Admin\stok\Stok::class)->orderBy('id');
    }
}
