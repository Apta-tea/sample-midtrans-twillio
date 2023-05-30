<?php

namespace App\Models\Admin\penarikan;

use App\Models\Admin\user\KomisiUser;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Admin\bank\Bank;

class PenarikanKomisi extends Model
{
    use HasFactory;
    protected $guarded = '';
    protected $with = ['komisi_user', 'bank'];

    public function komisi_user()
    {
        return $this->belongsTo(KomisiUser::class);
    }

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }
}
