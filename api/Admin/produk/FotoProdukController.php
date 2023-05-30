<?php

namespace App\Http\Controllers\Api\Admin\produk;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\produk\FotoProdukResource;
use App\Models\Admin\produk\FotoProduk;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;

class FotoProdukController extends Controller
{
    public function index($id)
    {
        $foto_produk = FotoProduk::where('produk_id', $id)->get();
        return FotoProdukResource::collection($foto_produk);
    }

    public function destroy($id)
    {
        $foto_produk = FotoProduk::findOrFail($id);
        if(is_file(public_path($foto_produk->foto))){
            File::delete($foto_produk->foto);
        }
        $foto_produk->delete();
        return response()->json([
            'session' => 'failed',
            'message' => 'Foto telah dihapus',
        ]);
    }
}
