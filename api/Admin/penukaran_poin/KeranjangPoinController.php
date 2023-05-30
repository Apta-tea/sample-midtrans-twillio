<?php

namespace App\Http\Controllers\Api\Admin\penukaran_poin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\penukaran_poin\KeranjangPoinResource;
use App\Models\Admin\penukaran_poin\KeranjangPoin;
use Illuminate\Http\Request;
use App\Models\Admin\produk\Produk;

class KeranjangPoinController extends Controller
{
    public function update(Request $request, $user_id)
    {
        $keranjangPoin = KeranjangPoin::where('user_id', $user_id)->latest()->first();
        if(empty($keranjangPoin)){
            $keranjangPoin = KeranjangPoin::create([
                'user_id' => $user_id,
            ]);
        }
        if(!empty($keranjangPoin->penukaran_poin)){
            $keranjangPoin = KeranjangPoin::create([
                'user_id' => $user_id,
            ]);
        }
        $keranjangPoin->produk_poin()->attach($request->produk);
        $last_id = 0;
        $subtotal = 0;
        foreach($keranjangPoin->produk_poin as $p){
            if($last_id != $p->id){
                $last_id = $p->id;
                $harga_poin = $p->harga_poin;
                $jumlah = $keranjangPoin->produk_poin->where('id', $p->id)->count();
                $subtotal += $harga_poin * $jumlah;
            }
        }
        $keranjangPoin->update([
            'subtotal' => $subtotal,
        ]);

        return new KeranjangPoinResource($keranjangPoin);
    }

    public function destroy(Request $request, $id)
    {
        // dd($request->all());
        if($request->produk_id == null){
            return response('produk id cannot null');
        }
        $keranjangPoin = KeranjangPoin::findOrFail($id);
        $keranjangPoin->produk_poin()->detach($request->produk_id);
        return response([
            'data' => 'data dihapus'
        ]);
    }

    public function produkCount($id)
    {
        $keranjang = KeranjangPoin::where('user_id', $id)->latest()->first();
        $counter = 0;
        if(!empty($keranjang)){
            $counter = $keranjang->produk_poin->count();
        }
        return response($counter);
    }
}
