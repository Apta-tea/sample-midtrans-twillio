<?php

namespace App\Http\Controllers\Api\Admin\user;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\user\UserHPVResource;
use App\Http\Resources\UserResource;
use Illuminate\Http\Request;
use App\Models\User;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $user = User::query();
        if(!empty($request->level)){
            $user->where('level', $request->level);
        }
        $user = $user->get();
        return UserResource::collection($user);
    }

    public function children(Request $request)
    {
        $user = collect(User::withCount('children')->where('level', '=', 'pengguna')->get());
        $user = $user->where('children_count', '>', 0);
        return UserResource::collection($user);
    }

    public function user($id)
    {
        $thisUser = auth('sanctum')->user();
        $user = User::find($id);
        if(empty($user)){
            return response('Not Found', 404);
        }
        $user = $user->load('otp'); 
        if($thisUser != null){
            if($thisUser->level != 'admin' && $user->id != $thisUser->id){
                $user = User::find($thisUser->id);
            }
        }
        return new UserResource($user);
    }

    public function hpv($id)
    {
        $thisUser = auth('sanctum')->user();
        $user = User::find($id);
        if(empty($user)){
            return response('Not Found', 404);
        }
        $user = $user->load('otp'); 
        if($thisUser != null){
            if($thisUser->level != 'admin' && $user->id != $thisUser->id){
                $user = User::find($thisUser->id);
            }
        }
        return new UserHPVResource($user);
    }
}
