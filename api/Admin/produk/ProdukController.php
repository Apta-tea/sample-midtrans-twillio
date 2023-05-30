<?php

namespace App\Http\Controllers\Api\Admin\produk;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Admin\produk\Produk;
use App\Http\Resources\Admin\produk\ProdukResource;
use App\Models\Admin\content\Homepage;
use Illuminate\Support\Facades\Auth;

class ProdukController extends Controller
{

    public function index_admin(Request $request)
    {
        $produk = Produk::query();
        $produk = $produk->orderBy('updated_at', 'desc')->paginate(10);
        // return $produk;
        // dd($produk);
        return ProdukResource::collection($produk);
    }

    public function terpopuler(Request $request)
    {
        $produk = Produk::with('tag_produk')->orderBy('popularitas', 'desc')->take(4)->get();
        return ProdukResource::collection($produk);
    }

    public function terbaru()
    {
        $homepage = Homepage::firstOrCreate();
        $produk = Produk::with('tag_produk');
        if($homepage->sortir_produk == 'terbaru'){
            $produk = $produk->orderBy('created_at', 'desc');
        }if($homepage->sortir_produk == 'terlama'){
            $produk = $produk->orderBy('created_at', 'asc');
        }if($homepage->sortir_produk == 'harga_terendah'){
            $produk = $produk->orderBy('harga', 'asc');
        }if($homepage->sortir_produk == 'harga_tertinggi'){
            $produk = $produk->orderBy('harga', 'desc');
        }if($homepage->sortir_produk == 'popularitas'){
            $produk = $produk->orderBy('popularitas', 'desc');
        }
        $produk = $produk->take(12)->get();
        return ProdukResource::collection($produk);
    }

    public function related(Request $request)
    {
        $produk = Produk::whereHas('tag_produk', function($query) use ($request){
            return $query->whereIn('tag_produk_id', $request->tag);
        });

        $produk = $produk->take(6)->get();
        return ProdukResource::collection($produk);
    }

    public function show($id)
    {
        $produk = Produk::find($id);
        return new ProdukResource($produk);
    }
}
