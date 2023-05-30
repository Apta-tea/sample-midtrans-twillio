<?php

namespace App\Http\Controllers\Api\Admin\penarikan;

use App\Exports\Admin\penarikan\PenarikanKomisiExport;
use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\penarikan\PenarikanKomisiResource;
use App\Mail\Admin\penarikan\PenarikanKomisiStoreMail;
use App\Mail\Admin\penarikan\PenarikanKomisiUpdateMail;
use App\Models\Admin\penarikan\PenarikanBonusLevel;
use App\Models\Admin\penarikan\PenarikanKomisi;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\File;
use App\Models\Admin\user\KomisiUser;
use Illuminate\Support\Facades\Mail;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\App;

class PenarikanKomisiController extends Controller
{

    public function index(Request $request)
    {
        $user = auth()->user();
        $penarikanKomisi = PenarikanKomisi::query();

        if(!empty($user)){
            if($user->level == 'pengguna'){
                $penarikanKomisi = $penarikanKomisi->whereHas('komisi_user', function($komisi_user) use($user){
                    $komisi_user->where('user_id', $user->id);
                });
            }
        }

        $penarikanKomisi = $penarikanKomisi->orderBy('updated_at', 'desc')->paginate(10);
        return PenarikanKomisiResource::collection($penarikanKomisi);
    }

    public function show($user_id)
    {
        $penarikanKomisi = PenarikanKomisi::whereHas('komisi_user', function($komisi_user) use ($user_id){
            $komisi_user->where('user_id', $user_id);
        })
        ->where('status', 'selesai')
        ->orderBy('updated_at', 'desc')
        ->take(5)->get();
        return PenarikanKomisiResource::collection($penarikanKomisi);
    }

    public function store(Request $request)
    {
        /**
         * Validation 
         */
        $validator = Validator::make($request->all(), [
            'catatan' => 'nullable|string|min:3',
            'saldo' => 'required|integer|min:50000'
        ]);
        if($validator->fails()){
            return response([
                'errors' => json_decode($validator->messages()->toJson()),
            ]);
        }
        $user = User::where('id', $request->user_id)->first();
        $jumlah = $request->saldo;
        $jumlah_yang_diminta = $request->saldo;
        $biaya_admin = 0;
        if(!$user->detail_user->bank->isDipakai()){ // jika selain bank yang dipakai
            $biaya_admin = $user->detail_user->bank->biaya_admin;
            $jumlah += $biaya_admin; // di tambah biaya admin
        }
        if(empty($user)){
            return response('User Not found');
        }
        if($user->id != $request->user()->id){
            return  response('401 Access denied');
        }
        if($user->komisi_user->last()->total == 0){
            return response([
                'session' => 'failed',
                'message' => 'Saldo kamu masih nol',
            ]);
        }
        if(intval($request->saldo) + $biaya_admin > $user->komisi_user->last()->total){
            return response([
                'session' => 'failed',
                'message' => 'Saldo kamu tidak cukup.',
            ]);
        }
        //check jika user sudah melengkapi detail user yg dibutuhkan
        if(empty($user->detail_user->nama_depan) && empty($user->detail_user->nomor_rekening)){
            return response([
                'session' => 'failed',
                'message' => "Harap lengkapi detail user, nama lengkap dan nomor rekening"
            ]);
        }
        //check jika permintaan sebelumnya telah diproses atau belum
        $penarikanKomisiLatest = PenarikanKomisi::whereHas('komisi_user', function($komisi_user) use($user){
            $komisi_user->where('user_id', $user->id);
        })->orderBy('created_at', 'desc')->first();
        if(!empty($penarikanKomisiLatest)){
            if($penarikanKomisiLatest->status != 'selesai'){
                return response([
                    'session' => 'failed',
                    'message' => 'Permintaan kamu yang sebelumnya belum selesai',
                ]);
            }
        }

        /**
         * Update Status
         */
        $komisiUser = KomisiUser::create([
            'user_id' => $user->id,
            'komisi' => $jumlah,
            'total' => $user->komisi_user->last()->total,
        ]);
        $penarikanKomisi = PenarikanKomisi::create([
            'komisi_user_id' => $komisiUser->id,
            'jumlah' => $jumlah,
            'jumlah_yang_diminta' => $jumlah_yang_diminta,
            'biaya_admin' => $biaya_admin,
            'catatan' => $request->catatan,
            'bank_id' => $user->detail_user->bank_id,
            'cabang_bank' => $user->detail_user->cabang_bank,
            'nomor_rekening' => $user->detail_user->nomor_rekening,
            'status' => 'diproses'
        ]);


        /**
         * Send notification
         */
        $userAdmin = User::where('level', 'admin')->get();
        foreach($userAdmin as $user){
            $response = $this->sendMessageFromWhatspie($penarikanKomisi, $user);
            Mail::to($user->email)->queue(new PenarikanKomisiStoreMail($penarikanKomisi, $user));
            if(!empty($response)){
                if(empty($response->message->id)){
                    return response()->json([
                        'session' => 'failed',
                        'message' => $response->message
                    ]);
                }
            }
        }

        //Kirim notifikasi whatsapp ke admin ada permintaan pencairan komisi
        $this->sendWhatsappMessageAdmin($penarikanKomisi, $user);
        //kirim notifikasi whatsapp pengajuan pencairan komisi diterima & segera diproses
        $this->sendWhatsappMessage($penarikanKomisi, $user);

        return response([
            'session' => 'success',
            'message' => 'Permintaan Komisi telah dikirim',
            'penarikan_komisi' => $penarikanKomisi,
        ]);
    }

    public function update(Request $request, $id)
    {
        /**
         * Validation
         */
        $validator = Validator::make($request->all(), [
            'foto_bukti_transfer' => 'required|max:2048',
        ]);
        if($validator->fails()){
            return response([
                'errors' => json_decode($validator->messages()->toJson()),
            ]);
        }
        $penarikanKomisi = PenarikanKomisi::where('id', $id)->first();
        if($penarikanKomisi->status == 'selesai'){
            return response([
                'session' => 'failed',
                'message' => 'Penarikan telah selesai.',
            ]);
        }
        if($penarikanKomisi->jumlah == 0){
            $penarikanKomisi->delete();
            return response([
                'session' => 'failed',
                'message' => 'Jumlah tidak boleh 0.',
            ]);
        }
        

        /**
         * Update Status
         */
        $komisiUserLatest = KomisiUser::where('user_id', $penarikanKomisi->komisi_user->user->id)->orderBy('created_at', 'desc')->first();
        if(!empty($request->file('foto_bukti_transfer'))){
            $image = time().$request->file('foto_bukti_transfer')->getClientOriginalExtension();
            $file = $request->file('foto_bukti_transfer')->move('img/penarikan/komisi', $image);
            $penarikanKomisi->update([
                'foto_bukti_transfer' => $file->getPathname(),
                'status' => 'selesai'
            ]);
            $penarikanKomisi->komisi_user()->update([
                'updated_at' => now(),
                'total' => intval($komisiUserLatest->total) - intval($penarikanKomisi->jumlah)
            ]);
        }
        //buat komisi user yg baru dgn nilai yg baru
        // KomisiUser::create([
        //     'user_id' => $penarikanKomisi->komisi_user->user->id,
        //     'total' => intval($komisiUserLatest->total) - intval($penarikanKomisi->jumlah),
        // ]);

        /**
         * Send Notification
         */
        //$this->sendMessageUpdateFromWhatspie($penarikanKomisi);
        Mail::to($penarikanKomisi->komisi_user->user->email)->queue(new PenarikanKomisiUpdateMail($penarikanKomisi));

        //kirim notifikasi whatsapp transfer komisi selesai diproses
        $this->sendWhatsappMessageUpdate($penarikanKomisi);
        
        return response([
            'session' => 'success',
            'message' => 'Permintaan Komisi telah dikirim',
            'penarikan_komisi' => $penarikanKomisi,
        ]);
    }
    
    public function sendMessageUpdateFromWhatspie(PenarikanKomisi $penarikanKomisi)
    {
        if(App::environment() === 'production'){
            $curl = curl_init();
            
            $user = $penarikanKomisi->komisi_user->user;
            $message = 'Hi, '.$user->detail_user->nama_lengkap()."\n";
            $message .= "Permintaan penarikan saldo kamu telah kami transfer.\n";
            $message .= "Bank : ".$user->detail_user->bank->nama." ".$user->detail_user->cabang_bank."\n";
            $message .= "Nomor rekening : ".$user->detail_user->nomor_rekening."\n";
            $message .= "Jumlah penarikan : Rp. ".number_format($penarikanKomisi->jumlah, 0, ',', '.').",-\n";
            $message .= "Tanggal : ".date('d-m-Y H:i:s', strtotime($penarikanKomisi->updated_at))."\n";
    
            $data = [
                'receiver' => $user->format_nomor_telp(),
                'device' => env('WHATSPIE_NUMBER','62811210555'),
                'type' => 'chat',
                'message' => $message,
            ];
    
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://app.whatspie.com/api/messages',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => http_build_query($data),
                CURLOPT_HTTPHEADER => array(
                    'Accept: application/json',
                    'Content-Type: application/x-www-form-urlencoded',
                    'Authorization: Bearer '.env('WHATSPIE_AUTH_TOKEN', 'CvjdIaaUyTeITGnfFQO1LVgCkZWYPefy4wkFGBLYVo6yekwypb')
                ),
            ));
    
            $response = curl_exec($curl);
            $response = json_decode($response);
            curl_close($curl);
            return $response;
        }
    }

    public function sendMessageFromWhatspie(PenarikanKomisi $penarikanKomisi, User $user)
    {
        if(App::environment() === 'production'){
            $curl = curl_init();
            
            $userPengguna = $penarikanKomisi->komisi_user->user;
            $message = 'Hi, '.$user->detail_user->nama_lengkap()."\n";
            $message .= "Ada permintaan penarikan saldo dari, ".$userPengguna->detail_user->nama_lengkap()."\n";
            $message .= "Bank : ".$userPengguna->detail_user->bank->nama." ".$userPengguna->detail_user->cabang_bank."\n";
            $message .= "Nomor rekening : ".$user->detail_user->nomor_rekening."\n";
            $message .= "Jumlah penarikan : Rp. ".$penarikanKomisi->jumlah.",-\n";
            $message .= "Tanggal : ".date('d-m-Y H:i:s', strtotime($penarikanKomisi->created_at))."\n";
            $message .= "Mohon segera proses permintaan penarikan saldo.";

            $data = [
                'receiver' => $user->format_nomor_telp(),
                'device' => env('WHATSPIE_NUMBER','62811210555'),
                'type' => 'chat',
                'message' => $message,
            ];

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://app.whatspie.com/api/messages',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => http_build_query($data),
                CURLOPT_HTTPHEADER => array(
                    'Accept: application/json',
                    'Content-Type: application/x-www-form-urlencoded',
                    'Authorization: Bearer '.env('WHATSPIE_AUTH_TOKEN', 'CvjdIaaUyTeITGnfFQO1LVgCkZWYPefy4wkFGBLYVo6yekwypb')
                ),
            ));

            $response = curl_exec($curl);
            $response = json_decode($response);
            curl_close($curl);
            return $response;
        }
    }

    public function sendWhatsappMessageAdmin(PenarikanKomisi $penarikanKomisi, User $user)
    {
        $admin = User::where('email', '!=', null)->where('level', 'admin')->first();
        $sid    = getenv("TWILIO_SID");
        $token  = getenv("TWILIO_AUTH_TOKEN");
        $wa_from= getenv("TWILIO_WHATSAPP_NUMBER");
        $recipient = $admin->format_nomor_telp();
        $twilio = new Client($sid, $token);
        
        try{
            
            $userPengguna = $penarikanKomisi->komisi_user->user;
            $body = "Hai, admin daclen! \n";
            $body .= "Ada permintaan penarikan saldo dari, ".$userPengguna->detail_user->nama_lengkap()."\n";
            $body .= "Bank : ".$userPengguna->detail_user->bank->nama." ".$userPengguna->detail_user->cabang_bank."\n";
            $body .= "Nomor rekening : ".$user->detail_user->nomor_rekening."\n";
            $body .= "Jumlah penarikan : Rp. ".$penarikanKomisi->jumlah.",-\n";
            $body .= "Tanggal : ".date('d-m-Y H:i:s', strtotime($penarikanKomisi->created_at))."\n";
            $body .= "Mohon segera diproses permintaan penarikan saldonya, terimakasih.";
            $twilio->messages->create(
                "whatsapp:$recipient",[
                    "from" => "whatsapp:$wa_from",
                    "body" => $body,
                ]
            );
        }catch(Error $err){
            return $err;
        }    
    }

    public function sendWhatsappMessageUpdate(PenarikanKomisi $penarikanKomisi)
    {
        $sid    = getenv("TWILIO_SID");
        $token  = getenv("TWILIO_AUTH_TOKEN");
        $wa_from= getenv("TWILIO_WHATSAPP_NUMBER");
        $user = $penarikanKomisi->komisi_user->user;
        $message= "Permintaan penarikan saldo kamu telah kami transfer.";
        $recipient = $user->format_nomor_telp();
        $twilio = new Client($sid, $token);
        
        try{
            $body = "Hai, ".$user->detail_user->nama_lengkap()."\n";
            $body .= "Bank : ".$user->detail_user->bank->nama."\n";
            $body .= "Nomor rekening : ".$user->detail_user->nomor_rekening."\n";
            $body .= "Jumlah penarikan : Rp. ".number_format($penarikanKomisi->jumlah, 0, ',', '.').",-\n";
            $body .= "Tanggal : ".date('d-m-Y H:i:s', strtotime($penarikanKomisi->updated_at))."\n";
            $body .= "Kami informasikan ".$message."\n";
            $body .= "Terimakasih.";
            $twilio->messages->create(
                "whatsapp:$recipient",[
                    "from" => "whatsapp:$wa_from",
                    "body" => $body,
                ]
            );
        }catch(Error $err){
            return $err;
        }    
    }

    public function sendWhatsappMessage(PenarikanKomisi $penarikanKomisi, User $user)
    {
        $sid    = getenv("TWILIO_SID");
        $token  = getenv("TWILIO_AUTH_TOKEN");
        $wa_from= getenv("TWILIO_WHATSAPP_NUMBER");
        $message= "Permintaan penarikan saldo anda telah kami terima dan segera kami proses.";
        $recipient = $user->format_nomor_telp();
        $twilio = new Client($sid, $token);
        
        try{
            
            $userPengguna = $penarikanKomisi->komisi_user->user;
            $body = "Hai, ".$userPengguna->detail_user->nama_lengkap()."\n";
            $body .= "Bank : ".$userPengguna->detail_user->bank->nama."\n";
            $body .= "Nomor rekening : ".$user->detail_user->nomor_rekening."\n";
            $body .= "Jumlah penarikan : Rp. ".$penarikanKomisi->jumlah.",-\n";
            $body .= "Tanggal : ".date('d-m-Y H:i:s', strtotime($penarikanKomisi->created_at))."\n";
            $body .= "Kami informasikan ".$message."\n";
            $body .= "Terimakasih.";
            $twilio->messages->create(
                "whatsapp:$recipient",[
                    "from" => "whatsapp:$wa_from",
                    "body" => $body,
                ]
            );
        }catch(Error $err){
            return $err;
        }    
    }

}
