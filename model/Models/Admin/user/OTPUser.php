<?php

namespace App\Models\Admin\user;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OTPUser extends Model
{
    use HasFactory;

    protected $guarded = '';

    public function user()
    {
        return $this->belongTo(User::class);
    }
}
