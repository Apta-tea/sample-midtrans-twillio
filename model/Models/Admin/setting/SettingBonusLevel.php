<?php

namespace App\Models\Admin\setting;

use App\Models\Admin\user\BonusLevelUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SettingBonusLevel extends Model
{
    use HasFactory;
    protected $guarded = '';
    protected $with = 'rpv';
    public function rpv()
    {
        return $this->hasMany(SettingRPVBonusLevel::class);
    }
    public function bonus_level_user()
    {
        return $this->hasMany(BonusLevelUser::class);
    }
}
