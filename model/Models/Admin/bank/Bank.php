<?php

namespace App\Models\Admin\bank;

use App\Models\Admin\user\DetailUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bank extends Model
{
    use HasFactory;
    protected $guarded = '';
    public function detail_user()
    {
        return $this->hasMany(DetailUser::class);
    }

    public function isDipakai()
    {
        $dipakai = false;
        if($this->status == 'dipakai'){
            $dipakai = true;
        }
        return $dipakai;
    }
}
