<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\dashboard\DashboardAdminResource;
use App\Http\Resources\Admin\dashboard\DashboardPenggunaResource;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use App\Models\User;

class AdminController extends Controller
{
    public function pengguna($id)
    {
        $user = User::where('id', $id)->with('komisi_user', 'bonus_level_user')->first();
        // $user->updateBonusLevel();
        return new DashboardPenggunaResource($user);
    }

    public function admin($id)
    {
        $user = User::where('id', $id)->with('komisi_user', 'bonus_level_user')->first();
        return new DashboardAdminResource($user);
    }
}
