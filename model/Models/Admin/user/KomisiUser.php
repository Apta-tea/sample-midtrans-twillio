<?php

namespace App\Models\Admin\user;

use App\Models\Admin\penarikan\PenarikanKomisi;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Home\Checkout;

class KomisiUser extends Model
{
    use HasFactory;
    protected $guarded = '';
    protected $with = 'user';

    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

    public function checkout()
    {
        return $this->belongsTo(Checkout::class);
    }

    public function penarikan_komisi()
    {
        return $this->hasOne(PenarikanKomisi::class);
    }
}
