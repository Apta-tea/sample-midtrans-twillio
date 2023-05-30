<?php

namespace App\Models\Admin\setting;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SettingRPVBonusLevel extends Model
{
    use HasFactory;
    protected $guarded = '';
    public function setting_bonus_level()
    {
        return $this->belongsTo(SettingBonusLevel::class);
    }
}
