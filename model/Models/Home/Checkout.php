<?php

namespace App\Models\Home;

use App\Http\Resources\Home\checkout\CheckoutResource;
use App\Models\Admin\kurir\Kurir;
use App\Models\Admin\user\KomisiUser;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Twilio\Rest\Client;
use Error;

class Checkout extends Model
{
    use HasFactory;
    protected $guarded = '';
    protected $with = [
        'detail_checkout',
        'keranjang',
        'kurir',
        'komisi_user'
    ];

    public static function booted()
    {
        static::created(function($checkout){
            $invoice  = $checkout->createInvoice();
            $checkout->update([
                'invoice' => $invoice,
                'order_id' => $checkout->createOrderId($invoice),
            ]);
        });

        // static::updated(function($checkout){
        //     $checkout->keranjang->user->updatePoin($checkout);
        // });
    }

    public function keranjang()
    {
        return $this->belongsTo(Keranjang::class);
    }
    public function kurir()
    {
        return $this->belongsTo(Kurir::class);
    }
    public function detail_checkout()
    {
        return $this->hasOne(DetailCheckout::class);
    }
    public function pengiriman()
    {
        return $this->hasOne(Pengiriman::class);
    }
    public function komisi_user()
    {
        return $this->hasMany(KomisiUser::class);
    }

    public function createInvoice()
    {
        // $id = Checkout::whereMonth('created_at', date('m', strtotime($this->created_at)))
        // ->whereYear('created_at', date('Y', strtotime($this->created_at)))->count();
        $id = Checkout::whereDate('created_at', date('Y-m-d', strtotime($this->created_at)))->count(); // hitung checkout hari ini
        $length = 5;
        $inv = 'INV/';
        $inv .= date('Ymd', strtotime($this->created_at)).'/';
        $inv .= str_pad((string)$id, $length, "0", STR_PAD_LEFT);
        return $inv;
    }

    public function createOrderId($invoice)
    {
        $order_id = '';
        if(!empty($invoice)){
            $expl = explode("/", $invoice);
            $order_id = join('', $expl);
        }
        return $order_id;
    }


    /**
     * Fungsi untuk mencari komisi user yang digunakan sebagai pembayaran checkout
     * @return Array $komisi_user
     */
    public function pembayaranDenganSaldo()
    {
        $komisi_user = $this->komisi_user;
        $komisi_user = $komisi_user->where('komisi', '<', '0');
        return $komisi_user;
    }

    /**
     * Fungsi ini akan mengirim pesan melalui nomor whatsapp yang ada di detail checkout
     * $message tempatkan dibawah info checkout
     * @param String message
     * @return void
     */
    public function sendWhatsappMessage($message)
    {
        $checkout = new CheckoutResource($this);
        $checkout = json_decode($checkout->toJson());
        $twilio_whatsapp_number = getenv('TWILIO_WHATSAPP_NUMBER');
        $account_sid = getenv("TWILIO_SID");
        $auth_token = getenv("TWILIO_AUTH_TOKEN");
        $nomorTujuan = "whatsapp:".$this->detail_checkout->format_nomor_telp();
        $client = new Client($account_sid, $auth_token);
        try{
            // dd($checkout->detail_checkout);
            $body =  "Hai, ".$checkout->detail_checkout->nama_lengkap."\n";
            $body .= "Nomor Invoice : ".$checkout->invoice."\n";
            $body .= "Nama : ".$checkout->detail_checkout->nama_lengkap."\n";
            $body .= "Pengiriman : ".$checkout->kurir->nama."\n";
            $body .= "Alamat : ".$checkout->detail_checkout->alamat_lengkap."\n";
            $body .= "Subtotal : Rp. ".$checkout->keranjang->subtotal_currency.",-\n";
            $body .= "Biaya Pengiriman : Rp. ".$checkout->kurir->harga_currency.",-\n";
            $body .= "Total : Rp. ".$checkout->total_currency.",-\n";
            $body .= "Kami informasikan, ".$message." \n";
            $body .= "Terimakasih.";
            $client->messages->create(
                "whatsapp:$nomorTujuan", [
                    "from" => "whatsapp:$twilio_whatsapp_number",
                    "body" => $body,
                ]
            );
        }catch(Error $err){
            return $err;
        }
    }


    /**
     * Fungsi untuk menghitung jika sebelumnya ada pengunaan saldo user pada checkout ini
     * untuk mencegah adanya double pengunaan saldo
     */
    public function hasUsedSaldo()
    {
        $hasUsedSaldo = false;
        $queryCount = $this->komisi_user
                        ->where('user_id', $this->keranjang->user_id)
                        ->where('komisi', '<', 0)
                        ->count();
        if($queryCount > 0){
            $hasUsedSaldo = true;
        }
        return $hasUsedSaldo;
    }

    /**
     * Fungsi untuk menghitung jika sebelumnya ada refund pada checkout ini
     * untuk mencegah adanya double refund
     */
    public function hasRefundSaldo()
    {
        $hasRefundSaldo = false;
        $queryCount = $this->komisi_user
        ->where('user_id', $this->keranjang->user_id)
        ->where('komisi', '>', 0)
        ->count();
        if($queryCount > 0){
            $hasRefundSaldo = true;
        }
        return $hasRefundSaldo;
    }

    /**
     * Fungsi untuk melepaskan relasi keranjang ke stok
     */
    public function lepasStok()
    {
        $keranjang = $this->keranjang;
        foreach ($keranjang->stok as $stok) {
            $stok->update([
                'keranjang_id' => null,
                'status' => 'masuk',
                'updated_at' => null,
            ]);
        }
    }
     /**
     * Fungsi ini akan mengirim pesan melalui nomor whatsapp ke Admin daclen
     */

     public function sendWhatsappMessageAdmin()
     {
         $admin = User::where('email', '!=', null)->where('level', 'admin')->first();
         $checkout = new CheckoutResource($this);
         $checkout = json_decode($checkout->toJson());
         $twilio_whatsapp_number = getenv('TWILIO_WHATSAPP_NUMBER');
         $account_sid = getenv("TWILIO_SID");
         $auth_token = getenv("TWILIO_AUTH_TOKEN");
         $nomorTujuan = "whatsapp:".$admin->format_nomor_telp();
         // dd($nomorTujuan);
         $client = new Client($account_sid, $auth_token);
         try{
             $items = '';
             foreach($checkout->keranjang->produk as $i => $produk){
                 $items .= $produk->nama.", ";
             }
             $body =  "Hai admin daclen pesanan ".$checkout->invoice." telah dibayar\n\n";
             $body .= "Detail Pembeli:\n";
             $body .= "- Nama : ".$checkout->detail_checkout->nama_lengkap."\n";
             $body .= "- Email : ".$checkout->detail_checkout->email."\n";
             $body .= "- Nomor Telepon : ".$checkout->detail_checkout->nomor_telp."\n";
             $body .= "- Nomor Invoice : ".$checkout->invoice."\n";
             $body .= "- Pengiriman : ".$checkout->kurir->nama."\n\n";
             $body .= "Detail Pesanan:\n";
             $body .= "- Alamat : ".$checkout->detail_checkout->alamat."\n";
             $body .= "- Items : ".$items."\n";
             $body .= "- Subtotal : Rp.".$checkout->keranjang->subtotal_currency."\n";
             $body .= "- Kurir : Rp.".$checkout->kurir->harga_currency."\n";
             $body .= "- Total : Rp.".$checkout->total_currency."\n\n#Mohon untuk segera proses pesanan ini.";
 
             $client->messages->create(
                 $nomorTujuan, [
                     "from" => "whatsapp:$twilio_whatsapp_number",
                     "body" => $body,
                 ]
             );
         }catch(Error $err){
 
             return $err;
         }
     }
}
