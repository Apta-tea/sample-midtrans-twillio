<?php

namespace App\Models\Admin\kurir;

use App\Models\Home\Checkout;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kurir extends Model
{
    use HasFactory;
    protected $guarded = '';
    public function checkout()
    {
        return $this->hasMany(Checkout::class);
    }

    public function potongan_harga()
    {
        $harga = $this->harga * 0.5;
        if($harga >= 20000){
            $harga = 20000;
        }
        return $harga * -1;
    }
}
