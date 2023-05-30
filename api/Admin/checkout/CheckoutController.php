<?php

namespace App\Http\Controllers\Api\Admin\checkout;

use App\Http\Controllers\Controller;
use App\Http\Resources\Home\checkout\CheckoutResource;
use App\Mail\Admin\checkout\TolakBuktiMail;
use App\Models\Admin\stok\Stok;
use App\Models\Admin\user\KomisiUser;
use App\Models\Home\Checkout;
use App\Models\Home\Pengiriman;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class CheckoutController extends Controller
{

    public function setupMidtrans()
    {
        if(env('APP_ENV') == 'local'){
            \Midtrans\Config::$serverKey = env('SB_MIDTRANS_SERVER_KEY');
            \Midtrans\Config::$clientKey = env('SB_MIDTRANS_CLIENT_KEY');
            \Midtrans\Config::$isProduction = false;
        }else{
            \Midtrans\Config::$serverKey = env('MIDTRANS_SERVER_KEY');
            \Midtrans\Config::$clientKey = env('MIDTRANS_CLIENT_KEY');
            \Midtrans\Config::$isProduction = true;
        }
        // Set sanitization on (default)
        \Midtrans\Config::$isSanitized = true;
        // Set 3DS transaction for credit card to true
        \Midtrans\Config::$is3ds = true;
    }

    public function index(Request $request)
    {
        $checkout = Checkout::query();
        if(!empty($request->date)){
            if($request->date_type == 'checkout'){
                $checkout->whereDate('created_at', $request->date);
            }
            if($request->date_type == 'verifikasi'){
                $checkout->whereDate('updated_at', $request->date);
            }
        }
        if(!empty($request->status)){
            if($request->status != 'semua'){
                $checkout->where('status', 'LIKE' , '%'.$request->status.'%');
            }
            if(!empty($request->cari)){
                $checkout->where('invoice', 'LIKE', '%'.$request->cari.'%')
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
        }
        $checkout->orderBy('created_at', 'desc');

        $checkout = $checkout->paginate(30);
        return CheckoutResource::collection($checkout);
    }

    public function bayar_pesanan($checkout_id)
    {   
        $checkout = Checkout::find($checkout_id);

        $params = array(
            'transaction_details' => array(
                'order_id' => $checkout->order_id,
                'gross_amount' => $checkout->total,
            ),
            'customer_details' => array(
                'first_name' => $checkout->detail_checkout->nama_depan,
                'last_name' => $checkout->detail_checkout->nama_belakang,
                'email' => $checkout->detail_checkout->email,
                'phone' => $checkout->detail_checkout->nomor_telp,
            ),
        );

        if(count($checkout->pembayaranDenganSaldo()) > 0){
            $total = $checkout->total + $checkout->pembayaranDenganSaldo()[0]->komisi;
            $params = array(
                'transaction_details' => array(
                    'order_id' => $checkout->order_id,
                    'gross_amount' => $total,
                ),
                'customer_details' => array(
                    'first_name' => $checkout->detail_checkout->nama_depan,
                    'last_name' => $checkout->detail_checkout->nama_belakang,
                    'email' => $checkout->detail_checkout->email,
                    'phone' => $checkout->detail_checkout->nomor_telp,
                ),
            );
        }

        $this->setupMidtrans();
        $snapToken = \Midtrans\Snap::getSnapToken($params);

        return response()->json([
            'snap_token' => $snapToken,
            'checkout_id' => $checkout->id,
        ]);
    }

    public function index_pengguna(Request $request, $user_id)
    {
        $checkout = Checkout::query();
        $checkout->whereHas('keranjang.user', function($user) use ($user_id){
            $user->where('id', $user_id);
        });

        if(!empty($request->date)){
            if($request->date_type == 'checkout'){
                $checkout->whereDate('created_at', $request->date);
            }
            if($request->date_type == 'verifikasi'){
                $checkout->whereDate('updated_at', $request->date);
            }
        }
        if(!empty($request->status)){
            if($request->status != 'semua'){
                $checkout->where('status', 'LIKE' , '%'.$request->status.'%');
            }
            if(!empty($request->cari)){
                $checkout->where('invoice', 'LIKE', '%'.$request->cari.'%')
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
        }

        $checkout = $checkout->orderBy('updated_at', 'desc')->paginate(30);
        return CheckoutResource::collection($checkout);
    }

    public function show($id)
    {
        $checkout = Checkout::find($id);
        // dd($checkout);
        return new CheckoutResource($checkout);
    }

    public function terima_bukti($id)
    {
        if(request()->user()->level != 'admin'){ return abort(401); }
        $checkout = Checkout::find($id);
        $user = $checkout->keranjang->user;
        //jika telah diverifikasi 
        if($checkout->status == 'diverifikasi'){
            return response([
                'session' => 'failed',
                'message' => 'Checkout Telah diverifikasi',
            ]);
        }

        $produk_id_keranjang = $checkout->keranjang->produk()->pluck('produk_id')->toArray();
        $produk_id_stok = $checkout->keranjang->stok->pluck('produk_id')->toArray();
        if(count(array_intersect($produk_id_keranjang, $produk_id_stok)) != count($produk_id_keranjang)){
            return response([
                'session' => 'failed',
                'message' => 'Stok belum diproses.'
            ]);
        }

        $checkout->update(['status' => 'diverifikasi']);
        Pengiriman::create([
            'checkout_id' => $checkout->id,
            'status' => 'diproses',
        ]);

        // update Poin User Komisi dan bonuslevel
        $user->updatePoin($checkout);

        //notif pesanan sukses dan segera diproses
        $message = "pesanan anda sedang kami proses, akan segera dikirim.";
        $checkout->sendWhatsappMessage($message);

        //notif pesanan masuk untuk admin
        $checkout->sendWhatsappMessageAdmin();

        // $message = "\n\nSelamat pesananmu telah berhasil diverifikasi\n";
        // $checkout->sendWhatsappMessage($message);

        return response([
            'session' => 'success',
            'message' => 'Bukti pembayaran telah diverifikasi',
            'hpv' => $user->poin_user->last()->hpv,
            'poin_user' => $user->poin_user->last()
        ]);
    }

    public function tolak_bukti(Request $request, $id)
    {
        if(request()->user() != 'admin'){ return abort(401); }
        $validator = Validator::make($request->all(), [
            'foto' => 'required|mimes:jpg,png,jpeg|max:1024',
            'alasan' => 'required|min:3',
        ]);
        if($validator->fails()){
            return response()->json([
                'errors' => json_decode($validator->messages()->toJson())
            ]);
        }
        $checkout = Checkout::find($id);
        $image = $checkout->order_id.'.'.$request->file('foto')->getClientOriginalExtension();
        $file = $request->file('foto')->move('img/checkout/refund', $image);
        $checkout->update([
            'status' => 'ditolak_admin',
            'bukti_refund' => $file->getPathname(),
        ]);
        $checkout->detail_checkout->update([
            'alasan' => $request->alasan,
        ]);

        //Jika ada stok yg telah ter relasi maka lepas relasi
        $checkout->lepasStok();

        // Jika dibayar pakai saldo maka refund saldo
        if($checkout->pembayaranDenganSaldo()->count() > 0){
            $komisi_user_from_checkout = $checkout->pembayaranDenganSaldo()->first()->komisi * -1;
            $user = $checkout->keranjang->user;
            $total = $user->komisi_user->last()->total + $komisi_user_from_checkout;
            
            // TODO: Buat check if checkout has refund 
            if(!$checkout->hasRefundSaldo()){
                KomisiUser::create([
                    'user_id' => $checkout->keranjang->user_id,
                    'komisi' => $komisi_user_from_checkout,
                    'total' => $total,
                    'checkout_id' => $checkout->id,
                ]);
            }
        }
        
        $user = $checkout->keranjang->user;

        // $message = "\n\nMaaf pesananmu telah kami tolak";
        // $message .= "\nDikarenakan : \n".$checkout->alasan;
        // $checkout->sendWhatsappMessage($message);
        // Mail::to($user->email)->queue(new TolakBuktiMail($request->all(), $checkout->id));
        
        return response([
            'session' => 'failed',
            'message' => 'Bukti pembayaran telah ditolak',
            // 'request' => $request->all(),
        ]);
    }

    public function status(Request $request, $checkout_id)
    {
        $request->validate([
            'status' => 'required'
        ]);
        $checkout = Checkout::find($checkout_id);
        $checkout->update([
            'status' => $request->status
        ]);
        return response()->json([
            'session' => 'success',
            'message' => 'Status Checkout '.$checkout->invoice.' menjadi '.$request->status,
        ]);
    }

    public function status_pembayaran($checkout_id)
    {
        $checkout = Checkout::find($checkout_id);
        $this->setupMidtrans();
        // $checkout = Checkout::find($checkout_id);
        if(env('APP_ENV') == 'local'){
            $url_status = "https://api.sandbox.midtrans.com/v2/".$checkout->order_id."/status";
        }else{
            $url_status = "https://api.midtrans.com/v2/".$checkout->order_id."/status";
        }
        $server_key = \Midtrans\Config::$serverKey.": ";
        $auth_string = base64_encode($server_key);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url_status);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Accept: application/json",
            "Content-Type: application/json",
            "Authorization: Basic ".$auth_string,
        ));
        $output = curl_exec($ch);
        curl_close($ch);  
        $response = json_decode($output);
        return $response;
    }

    public function update_status($checkout_id)
    {
        $checkout = Checkout::find($checkout_id);
        $this->setupMidtrans();
        if(env('APP_ENV') == 'local'){
            $url_status = "https://api.sandbox.midtrans.com/v2/".$checkout->order_id."/status";
        }else{
            $url_status = "https://api.midtrans.com/v2/".$checkout->order_id."/status";
        }
        $server_key = \Midtrans\Config::$serverKey.": ";
        $auth_string = base64_encode($server_key);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url_status);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Accept: application/json",
            "Content-Type: application/json",
            "Authorization: Basic ".$auth_string,
        ));
        $output = curl_exec($ch);
        curl_close($ch);  
        $response = json_decode($output);
        if($response->status_code == 200){
            if($response->transaction_status == 'settlement'){
                $checkout->update([
                    'status' => 'diproses'
                ]);
                return response([
                    'session' => 'success',
                    'message' => 'Status pembayaran telah selesai.'
                ]);
            }
        }
        if($response->status_code == 201){
            return response([
                'session' => 'failed',
                'message' => 'Pembayaran belum selesai.'
            ]);
        }
        return response([
            'session' => 'failed',
            'message' => 'Gagal mendapatkan status pembayaran.',
            'response' => $response
        ]);
    }
    
    public function refund(Request $request, $checkout_id)
    {
        $this->setupMidtrans();
        $checkout = Checkout::find($checkout_id);
        if(env('APP_ENV') == 'local'){
            $url_status = "https://api.sandbox.midtrans.com/v2/".$checkout->order_id."/status";
        }else{
            $url_status = "https://api.midtrans.com/v2/".$checkout->order_id."/status";
        }
        $server_key = \Midtrans\Config::$serverKey.": ";
        $auth_string = base64_encode($server_key);
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url_status);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Accept: application/json",
            "Content-Type: application/json",
            "Authorization: Basic ".$auth_string,
        ));
        $output = curl_exec($ch);
        curl_close($ch);  
        $response = json_decode($output);
        if($response->transaction_status == 'settlement'){
            $checkout->update([
                'status' => 'refund',
            ]);
            return response([
                'session' => 'success',
                'message' => 'Berhasil mengajukan refund tunggu admin untuk memproses refund.'
            ]);
        }
        if($response->transaction_status != 'settlement'){
            return response([
                'session' => 'failed',
                'message' => 'Checkout '.$checkout->invoice.' belum dibayar.',
            ]);
        }
        return $response;
    }

}
