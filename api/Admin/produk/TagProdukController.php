<?php

namespace App\Http\Controllers\Api\Admin\produk;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\produk\TagProdukResource;
use App\Models\Admin\produk\TagProduk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TagProdukController extends Controller
{
    public function index()
    {
        $tagProduk = TagProduk::get();
        return TagProdukResource::collection($tagProduk);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'nama' => 'required|string|min:3',
        ]);
        if($validator->fails()){
            return response([
                'errors' => json_decode($validator->messages()->toJson())
            ]);
        }
        TagProduk::create([
            'nama' => $request->nama
        ]);
        return response([
            'session' => 'success',
            'message' => 'Kategori produk telah disimpan',
        ]);
    }
}
