<?php

namespace App\Models\Admin\user;

use App\Models\Admin\setting\SettingBonusLevel;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LogBonusLevelUser extends Model
{
    use HasFactory;
    protected $guarded = '';
    protected $with = [
        'user',
        'setting_bonus_level'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function setting_bonus_level()
    {
        return $this->belongsTo(SettingBonusLevel::class);
    }
}
