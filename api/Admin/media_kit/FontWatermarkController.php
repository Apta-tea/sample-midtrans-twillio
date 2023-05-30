<?php

namespace App\Http\Controllers\Api\Admin\media_kit;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\media_kit\FontWatermarkResource;
use App\Models\Admin\media_kit\FontWatermark;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

class FontWatermarkController extends Controller
{
    public function index(Request $request)
    {
        $fonts = FontWatermark::query();
        $fonts = $fonts->orderBy('created_at', 'desc');
        if(!empty($request->color)){
            $fonts = $fonts->whereHas('color', function($color) use($request){
                $color->where('id', $request->color);
            });
        }
        if(!empty($request->size)){
            $fonts = $fonts->whereHas('size', function($size) use($request){
                $size->where('id', $request->size);
            });
        }
        $fonts = $fonts->get();
        return FontWatermarkResource::collection($fonts);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'required|min:3|max:100',
            'fnt' => 'required',
            'png' => 'nullable|mimes:png',
            'color_id' => 'required|integer|min:1',
            'size_id' => 'required|integer|min:1'
        ]);

        if($validator->fails()){
            return response([
                'errors' => json_decode($validator->messages()->toJson())
            ]);
        };

        if($request->file('fnt')->getClientOriginalExtension() != 'fnt'){
            return response([
                'errors' => [
                    'fnt' => ['File harus bertipe .fnt']
                ]
            ]);
        }
        $filename = $request->file('fnt')->getClientOriginalName();
        $file = $request->file('fnt')->move('font/fnt/', $filename);
        $filepath = $file->getPathname();

        $filename = $request->file('png')->getClientOriginalName();
        $file = $request->file('png')->move('font/fnt/', $filename);
        $filepngpath = $file->getPathname();

        FontWatermark::create([
            'nama' => $request->nama,
            'fnt' => $filepath,
            'png' => $filepngpath,
            'size_id' => $request->size_id,
            'color_id' => $request->color_id
        ]);

        return response([
            'session' => 'success',
            'message' => 'Data berhasil disimpan.'
        ]);
    }

    public function update(Request $request, $id)
    {
        $font = FontWatermark::findOrFail($id);
        
        $validator = Validator::make($request->all(), [
            'nama' => 'required|min:3|max:100',
            'fnt' => 'nullable',
            'png' => 'nullable|mimes:png',
            'color_id' => 'required|integer|min:1',
            'size_id' => 'required|integer|min:1'
        ]);

        if($validator->fails()){
            return response([
                'errors' => json_decode($validator->messages()->toJson())
            ]);
        };

        $filepath = $font->fnt;
        $filepngpath = $font->png;
        if(!empty($request->file('fnt'))){
            if($request->file('fnt')->getClientOriginalExtension() != 'fnt'){
                return response([
                    'errors' => [
                        'fnt' => ['File harus bertipe .fnt']
                    ]
                ]);
            }
            if(File::isFile(public_path($font->file))){
                File::delete(public_path($font->file));
            }
            $filename = $request->file('fnt')->getClientOriginalName();
            $file = $request->file('fnt')->move('font/fnt/', $filename);
            $filepath = $file->getPathname();
        }
        if(!empty($request->file('png'))){
            if(File::isFile(public_path($font->file))){
                File::delete(public_path($font->file));
            }
            $filename = $request->file('png')->getClientOriginalName();
            $file = $request->file('png')->move('font/fnt/', $filename);
            $filepngpath = $file->getPathname();

        }

        $font->update([
            'nama' => $request->nama,
            'fnt' => $filepath,
            'png' => $filepngpath,
            'size_id' => $request->size_id,
            'color_id' => $request->color_id
        ]);

        return response([
            'session' => 'success',
            'message' => 'Data berhasil diupdate.'
        ]);
    }

    public function destroy($id)
    {
        $font = FontWatermark::findOrFail($id);
        if(File::isFile(public_path($font->fnt))){
            File::delete(public_path($font->fnt));
        }
        if(File::isFile(public_path($font->png))){
            File::delete(public_path($font->png));
        }
        $font->delete();
        return response([
            'session' => 'failed',
            'message' => 'Data berhasil dihapus.'
        ]);
    }
    
}
