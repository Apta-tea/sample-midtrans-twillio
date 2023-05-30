<?php

namespace App\Http\Controllers\Api\Admin\media_kit;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\media_kit\SizeWatermarkResource;
use App\Models\Admin\media_kit\SizeWatermark;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SizeWatermarkController extends Controller
{
    public function index(Request $request)
    {
        $size = SizeWatermark::orderBy('ukuran', 'desc')->get();
        return SizeWatermarkResource::collection($size);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ukuran' => 'required|max:100|numeric'
        ]);

        if($validator->fails()){
            return response([
                'errors' => json_decode($validator->messages()->toJson())
            ]);
        }

        SizeWatermark::create([
            'ukuran' => $request->ukuran
        ]);
        
        return response([
            'session' => 'success',
            'message' => 'Data ukuran berhasil disimpan.'
        ]);
    }

    public function update(Request $request, $id)
    {
        $size = SizeWatermark::findOrFail($id);
        $validator = Validator::make($request->all(), [
            'ukuran' => 'required|max:100|numeric'
        ]);

        if($validator->fails()){
            return response([
                'errors' => json_decode($validator->messages()->toJson())
            ]);
        }

        $size->update([
            'ukuran' => $request->ukuran
        ]);
        
        return response([
            'session' => 'success',
            'message' => 'Data ukuran berhasil disimpan.'
        ]);
    }

    public function destroy($id)
    {
        $size = SizeWatermark::findOrFail($id);
        $size->delete();
        return response([
            'session' => 'failed',
            'message' => 'Data ukuran berhasil dihapus.'
        ]);
    }
}
