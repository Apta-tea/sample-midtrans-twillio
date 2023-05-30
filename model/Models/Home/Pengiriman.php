<?php

namespace App\Models\Home;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pengiriman extends Model
{
    use HasFactory;
    protected $table = 'pengirimans';
    protected $guarded = '';
    public function checkout()
    {
        return $this->belongsTo(Checkout::class);
    }
    
}
