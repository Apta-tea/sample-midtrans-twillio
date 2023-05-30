<?php

namespace App\Models\Admin\penukaran_poin;

use App\Models\Admin\penukaran_poin\KeranjangPoin;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PenukaranPoin extends Model
{
    use HasFactory;
    protected $guarded = '';
    protected $with = 'keranjang_poin';

    public function keranjang_poin()
    {
        return $this->belongsTo(KeranjangPoin::class);
    }
}
