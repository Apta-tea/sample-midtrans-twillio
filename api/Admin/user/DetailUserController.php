<?php

namespace App\Http\Controllers\Api\Admin\user;

use App\Http\Controllers\Admin\user\OTPUserController;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Admin\user\DetailUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

class DetailUserController extends Controller
{
    public function show($id)
    {
        $user = User::find($id);
        if(empty($user->detail_user)){
            DetailUser::create([
                'user_id' => $user->id,
            ]);
        }
        $user = User::find($id);
        return new UserResource($user);
    }
    public function update(Request $request, $id)
    {
        // dd((!empty($request->nomor_telp)) ? 'nullable|digits_between:10,14' : 'nullable');
        $validator = Validator::make($request->all(), [
            'nama_depan' => 'required|string|min:1|max:255',
            'nama_belakang' => 'nullable|string|min:1|max:255',
            'jenis_kelamin' => 'nullable',
            'tanggal_lahir' => 'nullable',
            'nama_perusahaan' => 'nullable|string|min:1|max:255',
            'alamat' => 'nullable|string',
            'provinsi' => 'nullable',
            'kota' => 'nullable',
            'kecamatan' => 'nullable',
            'desa' => 'nullable',
            'kode_pos' => 'nullable|digits:5',
            'nomor_telp' => (!empty($request->nomor_telp)) ? 'nullable|digits_between:10,14' : 'nullable',
            'email' => 'required|email|unique:users,email,'.$id,
            'nomor_rekening' => 'nullable|numeric',
            'bank_id' => 'nullable|integer',
            'cabang_bank' => 'nullable|string|min:3|max:255'
        ]);
        // dD($request->all());
        if($validator->fails()){
            return response([
                'errors' => json_decode($validator->messages()->toJson()),
                'session' => 'failed',
                'message' => 'Mohon isi data dengan lengkap dan benar',
            ]);
        }
        // dd($request->nomor_telp);
        if(!empty($request->nomor_telp)){
            if(!str_starts_with($request->nomor_telp, '0')){
                return response(([
                    'errors' => [
                        'nomor_telp' => [
                            'Format nomor telpon salah.'
                        ]
                    ],
                    'session' => 'failed',
                    'message' => 'Mohon isi data dengan lengkap dan benar',
                ]));
            }
        }
        $attr_detail_user = $request->except('nomor_telp', 'email', 'old_password', 'new_password', 'confirm_password');
        $attr_user = $request->only('email', 'nomor_telp');
        $user = User::findOrFail($id);
        //update nomor telp
        if($request->nomor_telp != $user->nomor_telp){
            $user->update(['nomor_telp_verified_at' => null]);
            if(!empty($user->otp)){
                $user->otp->delete();
            }
        }
        // update email 
        if($request->email != $user->email){
            $user->update(['email_verified_at' => null]);
        }
        $user->detail_user->update($attr_detail_user);
        $user->update($attr_user);
        $valid = true;
        // ubah password
        if(!empty($request->old_password) && !empty($request->confirm_password)){
            $valid = Hash::check($request->old_password, $user->password);
            if(!$valid){
                return response()->json([
                    'pass_valid' => $valid,
                    'session' => 'failed',
                    'message' => 'Gagal menyimpan data',
                ]);
            }else{
                $user->update(['password' => Hash::make($request->new_password)]);
            }
        }

        $user = User::find($user->id);
        $userResource = new UserResource($user);

        return response()->json([
            'pass_valid' => $valid,
            'session' => 'success',
            'message' => 'Data telah disimpan',
            'user' => json_decode($userResource->toJson()),
        ]);
    }

    public function update_nomor_telp_from_checkout(Request $request, $id)
    {
        $user = User::find($id);
        $validator = Validator::make($request->all(),[
            'nomor_telp' => 'required|digits_between:11,14',
        ]);
        if($validator->fails()){
            return response([
                'errors' => json_decode($validator->messages()->toJson()),
            ]);
        }
        $user->update([
            'nomor_telp' => $request->nomor_telp
        ]);
        if(!empty($user->otp)){
            $user->otp->update([
                'percobaan' => 0
            ]);
        }
        return new UserResource($user);
    }

    public function update_from_checkout(Request $request, $id)
    {
        $user = User::find($id);
        $validator = Validator::make($request->all(), [
            'alamat' => 'required|min:3',
            'provinsi' => 'required',
            'kota' => 'required',
            'kecamatan' => 'required',
        ]);
        if($validator->fails()){
            return response([
                'errors' => json_decode($validator->messages()->toJson()),
            ]);
        }
        $user->detail_user->update([
            'alamat' => $request->alamat,
            'provinsi' => $request->provinsi,
            'kota' => $request->kota,
            'kecamatan' => $request->kecamatan,
        ]);
        return response([
            'session' => 'success',
            'message' => 'Data alamat dan nomor telepon telah berhasil disimpan.'
        ]);
    }
    public function upload_img(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'foto' => 'nullable|mimes:jpg,jpeg,png|max:2048'
         ]);
        
        if($validator->fails()){
            return response([
                'errors' => json_decode($validator->messages()->toJson())
            ]);
        }
        
        $du = DetailUser::where('user_id', $id)->first();
        if(!empty($request->file('foto'))){
            if(is_file(public_path($du->foto))){
                File::delete(public_path($du->foto));
            }
            $image = time().'.'.$request->file('foto')->getClientOriginalExtension();
            $file = $request->file('foto')->move('img/user', $image);
            $file_path = $file->getPathname();
            $du->update([
                'foto' => $file_path,
            ]);
            return response()->json([
                'session' => 'success',
                'message' => 'Foto telah disimpan',
            ]);
        }else{
            return response()->json([
                'session' => 'failed',
                'message' => 'Gagal menyimpan foto',
            ]);
        }
    }
}
