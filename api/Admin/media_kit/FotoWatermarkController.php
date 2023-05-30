<?php

namespace App\Http\Controllers\Api\Admin\media_kit;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\media_kit\FotoWatermarkResource;
use App\Models\Admin\media_kit\FotoWatermark;
use GdImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

class FotoWatermarkController extends Controller
{
    public function index(Request $request)
    {
        $fotoWatermark = FotoWatermark::orderBy('created_at', 'desc')->get();
        return FotoWatermarkResource::collection($fotoWatermark);
    }

    public function store(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'foto' => 'required|mimes:jpg,png,jpeg|max:2048'
        ]);

        if($validation->fails()){
            return response([
                'errors' => json_decode($validation->messages()->toJson())
            ]);
        }

        $ext = $request->file('foto')->getClientOriginalExtension();
        $filename = time().$request->file('foto')->getClientOriginalName();;
        $file = $request->file('foto')->move('img/foto-watermark', $filename);
        $filepath = $file->getPathname();
        $image = $this->createImage(asset($filepath), $ext);
        if(!empty($image)){
            $width = imagesx($image);
            $height = imagesy($image);
        }
        
        FotoWatermark::create([
            'foto' => $filepath,
            'width' => $width,
            'height' => $height
        ]);

        return response([
            'session' => 'success',
            'message' => 'Data berhasil disimpan.'
        ]);
    }

    public function update(Request $request, $id)
    {
        $fotoWatermark = FotoWatermark::findOrFail($id);
        $validation = Validator::make($request->all(), [
            'foto' => 'required|mimes:jpg,png,jpeg|max:2048'
        ]);

        if($validation->fails()){
            return response([
                'errors' => json_decode($validation->messages()->toJson())
            ]);
        }

        if(is_file(public_path($fotoWatermark->foto))){
            File::delete(public_path($fotoWatermark->foto));
        }

        $ext = $request->file('foto')->getClientOriginalExtension();
        $filename = time().$request->file('foto')->getClientOriginalName();;
        $file = $request->file('foto')->move('img/foto-watermark', $filename);
        $filepath = $file->getPathname();
        $image = $this->createImage(asset($filepath), $ext);
        if(!empty($image)){
            $width = imagesx($image);
            $height = imagesy($image);
        }
        
        $fotoWatermark->update([
            'foto' => $filepath,
            'width' => $width,
            'height' => $height
        ]);

        return response([
            'session' => 'success',
            'message' => 'Data berhasil diubah.'
        ]);
    }

    public function destroy($id)
    {
        $fotoWatermark = FotoWatermark::findOrFail($id);
        if(is_file(public_path($fotoWatermark->foto))){
            File::delete(public_path($fotoWatermark->foto));
        }
        $fotoWatermark->delete();
        return response([
            'session' => 'failed',
            'message' => 'Data berhasil dihapus.'
        ]);
    }

    public function createImage($url, $ext)
    {
        $image = null;
        if($ext == 'png'){
            $image = imagecreatefrompng($url);
        }
        if($ext == 'jpeg'){
            $image = imagecreatefromjpeg($url);
        }
        if($ext == 'jpg'){
            $image = imagecreatefromjpeg($url);
        }
        return $image;
    }

}
