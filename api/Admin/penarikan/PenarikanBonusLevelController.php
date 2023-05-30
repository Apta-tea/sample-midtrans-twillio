<?php

namespace App\Http\Controllers\Api\Admin\penarikan;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\penarikan\PenarikanBonusLevelResource;
use App\Models\Admin\penarikan\PenarikanBonusLevel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\Admin\user\BonusLevelUser;
use Carbon\Carbon;

class PenarikanBonusLevelController extends Controller
{
    public function index()
    {
        $penarikanBonusLevel = PenarikanBonusLevel::orderBy('created_at', 'desc')->paginate(10);
        return PenarikanBonusLevelResource::collection($penarikanBonusLevel);
    }

    public function show($user_id)
    {
        $penarikanBonusLevel = PenarikanBonusLevel::whereHas('bonus_level_user', function($bonus_level_user) use($user_id){
            $bonus_level_user->where('user_id', $user_id);
        })->where('status', 'selesai')
        ->orderBy('updated_at', 'desc')
        ->take(5)
        ->get();
        return PenarikanBonusLevelResource::collection($penarikanBonusLevel);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'catatan' => 'nullable|string|min:3'
        ]);
        if($validator->fails()){
            return response([
                'errors' => json_decode($validator->messages()->toJson()),
            ]);
        }
        $user = User::where('id', $request->user_id)->first();
        if(empty($user)){
            return response('User Not Found');
        }
        if($user->bonus_level_user->last()->bonus_level == 0){
            return response([
                'session' => 'failed',
                'message' => 'Bonus root tidak boleh nol',
            ]);
        }

        //check jika user sudah melengkapi detail user yg dibutuhkan
        if(empty($user->detail_user->nama_depan) && empty($user->detail_user->nomor_rekening)){
            return response([
                'session' => 'failed',
                'message' => "Harap lengkapi detail user, nama lengkap dan nomor rekening kamu diperlukan"
            ]);
        }

        //check jika permintaan sebelumnya telah diproses atau belum
        $penarikanBonusLevelLatest = PenarikanBonusLevel::whereHas('bonus_level_user', function($bonus_level_user) use($user){
            $bonus_level_user->where('user_id', $user->id);
        })->orderBy('created_at', 'desc')->first();
        if(!empty($penarikanBonusLevelLatest)){
            if($penarikanBonusLevelLatest->status != 'selesai'){
                return response([
                    'session' => 'failed',
                    'message' => 'Permintaan kamu yang sebelumnya belum selesai',
                ]);
            }
            //check jika permintaan sebelumnya belum satu bulan
            $penarikanBonusLevelLatestDate = new Carbon($penarikanBonusLevelLatest->created_at);
            $dateNow = new Carbon();
            if($penarikanBonusLevelLatestDate->diffInMonths($dateNow) < 1){
                return response([
                    'session' => 'failed',
                    'message' => 'Permintaan kamu hanya dapat dilakukan satu bulan sekali',
                ]);
            }
        }

        $bonus_level = BonusLevelUser::create([
            'user_id' => $user->id,
            'bonus_level' => $user->bonus_level_user->last()->total,
            'total' => $user->bonus_level_user->last()->total
        ]);
        $penarikanBonusLevel = PenarikanBonusLevel::create([
            'bonus_level_user_id' => $bonus_level->id,
            'jumlah' => $bonus_level->bonus_level,
            'catatan' => $request->catatan,
            'status' => 'diproses',
        ]);

        return response([
            'session' => 'success',
            'message' => 'Permintaan Bonus Root telah dikirim',
            'penarikan_bonus_level' => $penarikanBonusLevel,
        ]);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'foto_bukti_transfer' => 'required|mimes:jpg,jpeg,png,svg|max:2024',
        ]);
        if($validator->fails()){
            return response([
                'errors' => json_decode($validator->messages()->toJson()),
            ]);
        }
        
        $penarikanBonusLevel = PenarikanBonusLevel::where('id', $id)->first();
        $bonusLevelUserLatest = BonusLevelUser::where('user_id', $penarikanBonusLevel->bonus_level_user->user->id)->orderBy('created_at', 'desc')->first();

        if(!empty($request->file('foto_bukti_transfer'))){
            $image = time().$request->file('foto_bukti_transfer')->getClientOriginalExtension();
            $file = $request->file('foto_bukti_transfer')->move('img/penarikan/bonus-level', $image);
            $penarikanBonusLevel->update([
                'foto_bukti_transfer' => $file->getPathname(),
                'status' => 'selesai'
            ]);
            $penarikanBonusLevel->bonus_level_user()->update([
                'updated_at' => now(),
                'total' => intval($bonusLevelUserLatest->bonus_level) - intval($penarikanBonusLevel->jumlah),
            ]);
        }

        // buat bonus level user dgn nilai yg baru
        BonusLevelUser::create([
            'user_id' => $penarikanBonusLevel->bonus_level_user->user->id,
            'total' => intval($bonusLevelUserLatest->bonus_level) - intval($penarikanBonusLevel->jumlah),
        ]);

        return response([
            'session' => 'success',
            'message' => 'Permintaan Komisi telah dikirim',
            // 'penarikan_komisi' => $penarikanBonusLevel,
        ]);
    }
}
