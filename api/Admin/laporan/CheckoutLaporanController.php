<?php

namespace App\Http\Controllers\Api\Admin\laporan;

use App\Http\Controllers\Controller;
use App\Http\Resources\Home\checkout\CheckoutResource;
use App\Models\Home\Checkout;
use Illuminate\Http\Request;
use App\Models\User;
use App\Exports\Admin\laporan\CheckoutLaporanExport;

class CheckoutLaporanController extends Controller
{
    public function index(Request $request)
    {
        if(empty($request->user_id)){
            return response('user id null');
        }
        $user = User::find($request->user_id);

        $checkout = Checkout::query();
        if(!empty($request->cari)){
            $checkout = $checkout->where('invoice', 'LIKE', '%'.$request->cari.'%')
           ->orWhereHas('detail_checkout', function($detail_checkout) use($request){
               $detail_checkout->where('alamat', 'LIKE', '%'.$request->cari.'%')
               ->orWhere('provinsi', 'LIKE', '%'.$request->cari.'%')
               ->orWhere('kota', 'LIKE', '%'.$request->cari.'%')
               ->orWhere('kecamatan', 'LIKE', '%'.$request->cari.'%')
               ->orWhere('desa', 'LIKE', '%'.$request->cari.'%')
               ->orWhere('kode_pos', 'LIKE', '%'.$request->cari.'%')
               ->orWhere('nama_depan', 'LIKE', '%'.$request->cari.'%')
               ->orWhere('nama_belakang', 'LIKE', '%'.$request->cari.'%');
           });
        }
        if(!empty($request->date)){
            if($request->date_type == 'checkout'){
                $checkout = $checkout->whereDate('created_at', $request->date);
            }
            if($request->date_type == 'verifikasi'){
                $checkout = $checkout->whereDate('updated_at', $request->date);
            }
        }
        if(!empty($request->status)){
            if($request->status != 'semua'){
                $checkout = $checkout->where('status', $request->status);
            }
        }
        $checkout = $checkout->orderBy('updated_at', 'desc');

        // jika pengguna
        if($user->level != 'admin'){
            $checkout = $checkout->whereHas('keranjang', function($keranjang) use($user){
                $keranjang->where('user_id', $user->id);
            });
        }

        $checkout = $checkout->paginate(10);
        return CheckoutResource::collection($checkout);
    }

    public function show(Request $request, $id)
    {
        $checkout = Checkout::where('id', $id)->firts();
        return new CheckoutResource($checkout);
    }
}
