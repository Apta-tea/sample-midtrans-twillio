<?php

namespace App\Http\Controllers\Api\Admin\bank;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\bank\BankResource;
use App\Models\Admin\bank\Bank;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BankController extends Controller
{
    public function index(Request $request)
    {
        $bank = Bank::query();
        $bank = $bank->orderBy('created_at', 'desc')->paginate(30);
        return BankResource::collection($bank);
    }

    public function all()
    {
        $bank = Bank::get();
        return BankResource::collection($bank);
    }

    public function store(Request $request)
    {
        if($request->user() != 'admin'){ return abort(401); }
        $validation = Validator::make($request->all(), [
            'nama' => 'required|string|min:2|max:255',
            'biaya_admin' => 'required|integer',
        ]);
        if($validation->fails()){
            return response()->json([
                'errors' => $validation->messages()->toJson()
            ]);
        }
        $status = null;
        if($request->status){
            $status = 'dipakai';
        }
        
        // Insert to database
        Bank::create([
            'nama' => $request->nama,
            'status' => $status,
            'biaya_admin' => $request->biaya_admin,
        ]);

        return response()->json([
            'session' => 'success',
            'message' => 'Data telah disimpan.'
        ]);
    }

    public function update(Request $request, $id)
    {
        if($request->user() != 'admin'){ return abort(401); }
        $bank = Bank::find($id);
        $validation = Validator::make($request->all(), [
            'nama' => 'required|string|min:3|max:255',
            'biaya_admin' => 'required|integer',
        ]);
        if($validation->fails()){
            return response()->json([
                'errors' => $validation->messages()->toJson()
            ]);
        }
        $status = null;
        if($request->status){
            $status = 'dipakai';
        }
        
        // Insert to database
        $bank->update([
            'nama' => $request->nama,
            'status' => $status,
            'biaya_admin' => $request->biaya_admin,
        ]);

        return response()->json([
            'session' => 'success',
            'message' => 'Data telah diupdate.'
        ]);
    }
    public function destroy($id)
    {
        if(request()->user() != 'admin'){ return abort(401); }
        $bank = Bank::find($id);
        $bank->delete();
        return response()->json([
            'session' => 'failed',
            'message' => 'Data telah dihapus.'
        ]);
    }
}
