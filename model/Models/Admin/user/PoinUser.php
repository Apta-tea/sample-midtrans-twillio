<?php

namespace App\Models\Admin\user;

use App\Models\Admin\penukaran_poin\PenukaranPoin;
use App\Models\Home\Checkout;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PoinUser extends Model
{
    use HasFactory;
    protected $table = 'poin_users';
    protected $guarded = '';

    // listen to update event
    // public static function booted(){
    //     // static::updated(function($poin_user){
    //     //     $user = $poin_user->user;
    //     //     $user->updateHPV();
    //     // });
    // }

    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

    public function checkout()
    {
        return $this->belongsTo(Checkout::class);
    }

    public function refferal_user()
    {
        return $this->belongsTo('\App\Models\User', 'refferal_user_id');
    }

    public function penukaran_poin()
    {
        return $this->belongsTo(PenukaranPoin::class);
    }
}
