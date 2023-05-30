<?php

namespace App\Http\Controllers\Api\Admin\stok;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\stok\StokResource;
use App\Models\Admin\stok\Stok;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;

class StokController extends Controller
{
    public function index(Request $request)
    {
        $stok_id = 0;
        if(!empty(Stok::latest()->first())){
            $stok_id = Stok::orderBy('id', 'desc')->first()->stok_id;
        }
        $status = 'masuk';
        if(!empty($request->status)){
            $status = $request->status;
        }
        $stok_collection = collect();
        for ($i=$stok_id; $i >= 0; $i--) { 
            $stok = Stok::query();
            $stok = $stok->where('status', $status)->where('stok_id', $i);
            if(!empty($request->produk_id) && $request->produk_id != 'null'){
                $stok = $stok->where('produk_id', $request->produk_id);
            }
            if(!empty($request->tanggal) && $request->tanggal != 'null'){
                if($status == 'masuk'){
                    $stok = $stok->whereDate('tanggal_masuk', $request->tanggal);
                }else{
                    $stok = $stok->whereDate('tanggal_keluar', $request->tanggal);
                }
            }
            $stok_collection->push($stok->first());
        }
        $filtered_collection = $stok_collection->filter(function ($value, $key){
            return $value != null;
        });
        return StokResource::collection($this->paginate($filtered_collection));
    }

    public function show(Request $request, $stok_id)
    {
        $status = 'masuk';
        if(!empty($request->status)){
            $status = $request->status;
        }
        $stok = Stok::query();
        $stok = $stok->where('stok_id', $stok_id);
        $stok = $stok->where('status', $status);
        if(!empty($request->kode_produk)){
            $stok = $stok->where('kode_produk', $request->kode_produk);
        }
        $stok = $stok->paginate(100);
        return StokResource::collection($stok);
    }

    public function deleteKodeProduk($id)
    {
        $stok = Stok::where('id', $id)->where('status', 'masuk')->first();
        $kode_produk = $stok->kode_produk;
        $stok->delete();
        return response([
            'session' => 'failed',
            'message' => 'Data dengan kode produk '.$kode_produk.' telah dihapus.'
        ]);
    }

    public function deleteStokBarang($stok_id)
    {
        $stok = Stok::where('stok_id', $stok_id)->where('status', 'masuk');
        $stok->delete();
        return response([
            'session' => 'failed',
            'message' => 'Data dengan Stok ID '.$stok_id.' telah dihapus.'
        ]);
    }

    public function paginate($items, $perPage = 100, $page = null, $options = [])
    {
        $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);
        $items = $items instanceof Collection ? $items : Collection::make($items);
        return new LengthAwarePaginator($items->forPage($page, $perPage), $items->count(), $perPage, $page, $options);
    }
}
