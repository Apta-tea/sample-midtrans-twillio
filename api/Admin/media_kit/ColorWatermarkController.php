<?php

namespace App\Http\Controllers\Api\Admin\media_kit;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\media_kit\ColorWatermarkResource;
use App\Models\Admin\media_kit\ColorWatermark;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ColorWatermarkController extends Controller
{
    public function index(Request $request)
    {
        $color = ColorWatermark::query();
        $color = $color->orderBy('nama', 'desc');
        $color = $color->get();
        return ColorWatermarkResource::collection($color);   
    }

    public function store(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'nama' => 'required|min:3|max:100',
            'warna' => 'required|max:9|min:4', 
        ]);
        if($validation->fails()){
            return response([
                'errors' => json_decode($validation->messages()->toJson())
            ]);
        }
        ColorWatermark::create([
            'nama' => $request->nama,
            'warna' => $request->warna,
        ]);
        return response([
            'session' => 'success',
            'message' => 'Data warna berhasil disimpan.'
        ]);
    }

    public function update(Request $request, $id)
    {
        $color = ColorWatermark::findOrFail($id);
        $validation = Validator::make($request->all(), [
            'nama' => 'required|min:3|max:100',
            'warna' => 'required|max:9|min:4', 
        ]);
        if($validation->fails()){
            return response([
                'errors' => json_decode($validation->messages()->toJson())
            ]);
        }
        $color->update([
            'nama' => $request->nama,
            'warna' => $request->warna,
        ]);
        return response([
            'session' => 'success',
            'message' => 'Data warna berhasil diubah.'
        ]);
    }

    public function destroy($id)
    {
        $color = ColorWatermark::findOrFail($id);
        $color->delete();
        return response([
            'session' => 'failed',
            'message' => 'Data warna telah dihapus.'
        ]);
    }
}
