<?php

namespace App\Http\Controllers\Api\Admin\log_bonus_level;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\user\UserLogBonusLevelResource;
use App\Http\Resources\UserResource;
use App\Models\Admin\user\LogBonusLevelUser;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;

class LogBonusLevelController extends Controller
{
    public function hitung(Request $request)
    {
        if(request()->user() != 'admin'){ return abort(401); }
        $validator = Validator::make($request->all(), [
            'bulan' => 'required',
            'tahun' => 'required|numeric',
        ]);
        if($validator->fails()){
            return response([
                'errors' => json_decode($validator->messages()->toJson())
            ]);
        }

        $user = User::query();
        $user = $user->whereHas('bonus_level_user', function($bonusLevelUser) use($request){
            $bonusLevelUser->whereMonth('updated_at', $request->bulan)
            ->whereYear('updated_at', $request->tahun)
            ->where('setting_bonus_level_id', '!=', null);
        });
        $date = Carbon::create($request->tahun, $request->bulan);
        $date_next_month = Carbon::create($request->tahun, $request->bulan);
        $date_next_month->addMonth(1);
        // VALIDATION
        if(date('m') == $date->format('m')){
            return response([
                'session' => 'failed',
                'message' => 'Perhitungan bonus level bulan '.$date->format('F').' tidak dapat dihitung pada bulan ini. Perhitungan dapat dilakukan dibulan '.$date_next_month->format('F').'.',
            ]);
        }
        $date_log_last = LogBonusLevelUser::orderBy('created_at', 'desc')->first();
        if(!empty($date_log_last)){
            $date_log_last = Carbon::parse($date_log_last->created_at);
            $date_latest = Carbon::create($date_log_last->format('Y'), $date_log_last->format('m'));
            if($date_latest->diffInMonths($date) <= 0){
                return response([
                    'session' => 'failed',
                    'message' => 'Perhitungan Bonus Level Bulan '.$date->format('F').' telah dihitung. Tidak dapat dihitung dua kali.'
                ]);
            }
        }
        // END VALIDATION
        $user_collection = collect();
        foreach ($user->get() as $u) {
            $u->bonus_level_bulan_ini = $u->hitungBonusLevelBulanIni($date, false);
            $user_collection->push($u);
        }
        $filtered_collection = $user_collection->filter(function ($value, $key){
            return $value != null;
        });
        return UserLogBonusLevelResource::collection($this->paginate($filtered_collection));
    }

    public function simpanBonusLevel(Request $request)
    {
        if(request()->user() != 'admin'){ return abort(401); }
        $validator = Validator::make($request->all(), [
            'bulan' => 'required',
            'tahun' => 'required|numeric',
        ]);
        if($validator->fails()){
            return response([
                'errors' => json_decode($validator->messages()->toJson())
            ]);
        }

        $user = User::query();
        $user = $user->whereHas('bonus_level_user', function($bonusLevelUser) use($request){
            $bonusLevelUser->whereMonth('updated_at', $request->bulan)
            ->whereYear('updated_at', $request->tahun)
            ->where('setting_bonus_level_id', '!=', null);
        });
        $date = Carbon::create($request->tahun, $request->bulan);
        foreach($user->get() as $u){
            $saldo_sebelum = $u->komisi_user->last()->total;
            $dihitung_pada = now();
            $bonus_level = $u->hitungBonusLevelBulanIni($date);
            $selesai_pada = now();
            LogBonusLevelUser::create([
                'user_id' => $u->id,
                'setting_bonus_level_id' => $u->bonus_level_user->last()->setting_bonus_level_id,
                'saldo_sebelum' => $saldo_sebelum,
                'saldo_sesudah' => $saldo_sebelum + $bonus_level,
                'dihitung_pada' => $dihitung_pada,
                'selesai_pada' => $selesai_pada,
                'bonus_level' => $bonus_level,
            ]);
            $u->updateBonusLevel();
        }
        return response([
            'session' => 'success',
            'message' => 'Bonus root telah masuk ke saldo user.'
        ]);
    }

    public function paginate($items, $perPage = 100, $page = null, $options = [])
    {
        $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);
        $items = $items instanceof Collection ? $items : Collection::make($items);
        return new LengthAwarePaginator($items->forPage($page, $perPage), $items->count(), $perPage, $page, $options);
    }
}
