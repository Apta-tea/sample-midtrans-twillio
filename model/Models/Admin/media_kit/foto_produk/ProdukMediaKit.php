<?php

namespace App\Models\Admin\media_kit\foto_produk;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProdukMediaKit extends Model
{
    use HasFactory;
    protected $guarded = '';
    protected $with = [
        'foto_media_kit'
    ];
    
    public function foto_media_kit()
    {
        return $this->hasMany(FotoMediaKit::class);
    }
}
