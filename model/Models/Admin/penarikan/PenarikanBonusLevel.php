<?php

namespace App\Models\Admin\penarikan;

use App\Models\Admin\user\BonusLevelUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PenarikanBonusLevel extends Model
{
    use HasFactory;
    protected $guarded = '';

    public function bonus_level_user()
    {
        return $this->belongsTo(BonusLevelUser::class);
    }
}
