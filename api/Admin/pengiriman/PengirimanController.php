<?php

namespace App\Http\Controllers\Api\Admin\pengiriman;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\pengririman\PengirimanResource;
use App\Models\Home\Pengiriman;
use App\Models\Home\Checkout;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PengirimanController extends Controller
{
    public function index()
    {
        $pengiriman = Pengiriman::with('checkout')->orderBy('updated_at', 'desc')->paginate(10);
        return PengirimanResource::collection($pengiriman);
    }

    public function show_checkout($checkout_id)
    {
        $pengiriman = Pengiriman::with('checkout')->where('checkout_id', $checkout_id);
        if($pengiriman->count() > 0){
            return new PengirimanResource($pengiriman->first());
        }
        return response([
            'session' => 'failed',
            'message' => 'Data tidak ditemukan untuk checkout '.$checkout_id
        ]);
    }

    public function index_pengguna($user_id)
    {
        $pengiriman = Pengiriman::whereHas('checkout', function($checkout) use ($user_id){
            $checkout->whereHas('keranjang', function($keranjang) use ($user_id){
                $keranjang->where('user_id', $user_id);
            });
        })->with('checkout')->orderBy('updated_at', 'desc')->paginate(10);
        return PengirimanResource::collection($pengiriman);
    }

    public function kirim(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'nomor_resi' => 'required|string|min:3',
            'tgl_pengiriman' => 'nullable|date',
        ]);
        if($validator->fails()){
            return response()->json([
                'errors' => $validator->messages()->toJson()
            ]);
        }
        $pengiriman = Pengiriman::find($id);
        if(!empty($pengiriman)){
            $pengiriman->update([
                'status' => 'dikirim',
                'nomor_resi' => $request->nomor_resi,
                // jika request->tgl_pengiriman kosong pake waktu skrng
                'tgl_pengiriman' => $request->tgl_pengiriman ?? now(), 
            ]);
            
            //kirim notif whatsapp sudah dikirim
            $cid = $pengiriman->checkout_id;
            $checkout = Checkout::find($cid);
            $message = "pesanan anda telah kami kirim.";
            $checkout->sendWhatsappMessage($message);

            return response([
                'session' => 'success',
                'message' => 'Pesanan Telah dikirim',
            ]);
        }
        return response([
            'session' => 'failed',
            'message' => 'Data tidak ditemukan',
        ]);
    }

    public function status(Request $request)
    {
        // mecari slug untuk api
        // $slug = 'jne';
        // $needle = 'tiki';
        // if(!empty($request->kurir)){
        //     $haystack = strtolower($request->kurir);
        //     if(str_contains($haystack, $needle)){
        //         $slug = 'tiki'; //slug jadi tiki
        //     }
        // }

        if(empty($request->nomor_resi)){
            return response()->json([
                'session' => 'failed',
                'message' => 'Nomor Resi tidak boleh kosong'
            ]);
        }

        $curl = curl_init();
        // dd($slug);

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://pro.rajaongkir.com/api/waybill",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => "waybill=".$request->nomor_resi."&courier=".$request->slug,
            CURLOPT_HTTPHEADER => array(
                "content-type: application/x-www-form-urlencoded",
                "key: ".env("RAJAONGKIR_KEY")
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            return response("cURL Error #:" . $err);
        } else {
            return response($response);
        }
    }
}
