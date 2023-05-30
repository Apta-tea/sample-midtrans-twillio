<?php

namespace App\Models\Admin\user;

use App\Models\Admin\penarikan\PenarikanBonusLevel;
use App\Models\Admin\setting\SettingBonusLevel;
use App\Models\Home\Checkout;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BonusLevelUser extends Model
{
    use HasFactory;
    protected $guarded = '';
    protected $with = 'setting_bonus_level';

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function setting_bonus_level()
    {
        return $this->belongsTo(SettingBonusLevel::class);
    }
    public function penarikan_bonus_level()
    {
        return $this->hasOne(PenarikanBonusLevel::class);
    }
    public function checkout()
    {
        return $this->belongsTo(Checkout::class);
    }
}
