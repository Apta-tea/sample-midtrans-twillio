<?php

namespace App\Models;

use App\Jobs\Admin\email\EmailVerifikasi;
use App\Models\Admin\penukaran_poin\KeranjangPoin;
use App\Models\Admin\user\BonusLevelUser;
use App\Models\Home\Keranjang;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Models\Admin\user\DetailUser;
use App\Models\Admin\user\KomisiUser;
use App\Models\Admin\user\PoinUser;
use App\Models\Home\Checkout;
use Laravel\Sanctum\HasApiTokens;
use App\Models\Admin\penukaran_poin\PenukaranPoin;
use App\Models\Admin\setting\SettingBonusLevel;
use App\Models\Admin\user\OTPUser;
use Carbon\Carbon;
use Illuminate\Contracts\Auth\CanResetPassword;

class User extends Authenticatable implements MustVerifyEmail, CanResetPassword
{
    use HasFactory, Notifiable, HasApiTokens;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */

    protected $guarded = '';
    protected $with = [
        'detail_user',
        // 'poin_user',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     * Public Var
     */
    public $bonus_level_pv  = false;
    public $bonus_level_hpv = false;
    public $bonus_level_rpv = false;
    public $bonus_level_data = [];
    public $temp_level = 0;
    public $dump = [];

    protected static function booted()
    {
        static::created(function ($user) {
            PoinUser::create([
                'user_id' => $user->id,
            ]);
            KomisiUser::create([
                'user_id' => $user->id,
            ]);
            DetailUser::create([
                'user_id' => $user->id,
            ]);
            BonusLevelUser::create([
                'user_id' => $user->id,
            ]);
        });
    }

    /**
    *   Fungsi Untuk cek user ada di bonus level mana
    *   Fungsi dijalankan ketika poin user diupdate
    *   return 0 on false
    */
    public function checkBonusLevel()
    {
        // init data
        $settingBonusLevel = SettingBonusLevel::orderBy('level')->get();
        $result = 0;
        $user = User::with('children')->where('id', $this->id)->first();

        $pvFromCheckout = PoinUser::where('user_id', $this->id)
            ->whereHas('checkout.keranjang', function($q){
                $q->where('user_id', $this->id);
            })
            ->whereMonth('created_at', date('m'))
            ->sum('poin');
        $pvFromReferral = PoinUser::where('user_id', $this->id)
            ->whereHas('refferal_user', function($q){
                $q->where('parent_id', $this->id);
            })
            ->whereMonth('created_at', date('m'))
            ->sum('poin');
        $pvUser = $pvFromCheckout + $pvFromReferral;
        $hpvUser = $user->poin_user->last()->hpv;
       
        /**
         * Variabel untuk lacak data
         */
        $dumpForRPVUSer = [];

        /**
         * Mulai Perhitungan setting bonus level
         */
        foreach ($settingBonusLevel as $setting) {
            // init data for this local variable
            $data = [];
            $this->iterateChildrenRootsForBonusLevelData($user->children, $data);
            $rpvUser = collect($data);
            $rpvUser = $rpvUser->sortBy('hpv', SORT_ASC, true);
            // push data ke dump agar bisa dilacak
            array_push($dumpForRPVUSer, [
                'Bonus Level' => $setting->level,
                'RPV User' => $rpvUser,
            ]);

            // perihtungan PV dan HPV
            if($pvUser >= $setting->pv){
                $this->bonus_level_pv = true;
            }else{
                $this->bonus_level_pv = false;
            }
            if($hpvUser >= $setting->hpv){
                $this->bonus_level_hpv = true;
            }else{
                $this->bonus_level_hpv = false;
            }

            // perhitungan RPV
            $jumlah_true = 0;
            // loop semua syarat RPV di setting
            foreach($setting->rpv as $rpv){
                //mecari data yang melebihi syarat RPV, di ambil dari yang terbesar
                $data = $rpvUser->where('hpv', '>=', $rpv->rpv)->sortBy('hpv', SORT_ASC, true)->take($rpv->jumlah);
                array_push($dumpForRPVUSer, [
                    'Bonus Level' => $setting->level,
                    'RPV User' => $rpvUser->toArray(),
                    'Data where hpv >= '.$rpv->rpv => $data->toArray()
                ]);
                // Jika jumlah data sesuai dengan jumlah syarat RPV, data RPV User yang telah dipakai akan dihapus.
                // Menyisakan data yang belum di filter
                $jumlah = $data->count();
                if($jumlah >= $rpv->jumlah){
                    foreach($data as $item){
                        $rpvUser->where('user_id', $item["user_id"])->first()["used"] = true;
                    }
                    $jumlah_true += 1;
                }
                // Hapus data yang telah dipakai, karena data ini tidak akan dipakai untuk perhitungan selanjutnya
                $rpvUser = $rpvUser->filter(function($item){
                    return $item["used"] == false;
                });
                array_push($dumpForRPVUSer, [
                    'Bonus Level' => $setting->level,
                    'RPV User after filter' => $rpvUser->toArray(),
                ]);
            }
            // jika semua syarat RPV terpehuni maka, syarat RPV True
            if($jumlah_true >= count($setting->rpv)){
                $this->bonus_level_rpv = true;
            }else{
                $this->bonus_level_rpv = false;
            }
            // Jika syarat PV, HPV dan RPV terpenuhi maka fungsi mengembalikan nilai balik, 
            // berupa id SettingBonusLevel yang telah terpenuhi
            if($this->bonus_level_pv && $this->bonus_level_hpv && $this->bonus_level_rpv){
                $result = $setting->id;
            }

            array_push($this->dump, [
                'Bonus Level' => $setting->level,
                'PV' => $this->bonus_level_pv,
                'HPV' => $this->bonus_level_hpv,
                'RPV' => $this->bonus_level_rpv,
            ]);
        }

        // uncomment untuk melihat data
        // akses localhost/test
        // dd([
        //     'dump' => $this->dump,
        //     'dump For RPV User' => $dumpForRPVUSer,
        // ]);
        return $result;
    }


    /**
     * Mengecek seberapa kurang PV User terhadap bonus root level
     * @param SettingBonusLevel $settingBonusRoot
     * @return Boolean
     */
    public function syaratPV(SettingBonusLevel $settingBonusLevel)
    {
        $poinBulanIni = PoinUser::where('user_id', $this->id)
                        ->whereHas('checkout.keranjang.user', function($user){
                            $user->where('id', $this->id);
                        })
                        ->whereMonth('created_at', date('m'))
                        ->sum('poin');
        return $poinBulanIni >= $settingBonusLevel->pv; 
    }
    /**
     * Mengecek seberapa kurang HPV User terhadap bonus root level
     * @param SettingBonusLevel $settingBonusLevel
     * @return Boolean
     */
    public function syaratHPV(SettingBonusLevel $settingBonusLevel)
    {
        $hpv = $this->poin_user->last()->hpv;
        return $hpv >= $settingBonusLevel->hpv;
    }
    /**
     * Mengecek seberapa kurang Root Point Value User  terhadap Setting bonus level
     * @param SettingBonusLevel
     * @return Boolean
     */
    public function syaratRPV(SettingBonusLevel $settingBonusLevel)
    {
        $this->bonus_level_rpv = false;
        $jumlah_true = 0;
        $user = User::with('children')->where('id', $this->id)->first();
        $data = [];
        $this->iterateChildrenRootsForBonusLevelData($user->children, $data);
        $rpvUser = collect($data);
        foreach($settingBonusLevel->rpv as $rpv){
            $data = $rpvUser->where('hpv', '>=', $rpv->rpv);
            $jumlah = $data->count();
            if($jumlah >= $rpv->jumlah){
                foreach($data as $item){
                    $rpvUser->where('user_id', $item["user_id"])->first()["used"] = true;
                }
                $jumlah_true += 1;
            }
            $rpvUser = $rpvUser->filter(function($item){
                return $item["used"] == false;
            });
        }
        if($jumlah_true >= count($settingBonusLevel->rpv)){
            $this->bonus_level_rpv = true;
        }else{
            $this->bonus_level_rpv = false;
        }
        return $this->bonus_level_rpv;
    }

    public function current_level()
    {
        $this->checkParent($this->id, 0);
        return $this->temp_level;
    }

    //Check parent
    public function checkParent($id, $level)
    {
        $user = User::with('parent')->find($id);
        if($user->parent_id != null){
            $this->checkParent($user->parent_id, $level+1);
        }else{
            $this->temp_level = $level;
        }
    }

    /**
     * Fungsi untuk menghitung home poin value user
     * fungsi akan loop seluruh children dan menjumlahkan hpvnya
     * return Home poin value user
     */
    public function hitungHPV() //untuk updated poin user
    {
        $tree = $this->hpv->toArray();
        $poinUserFromCheckout = PoinUser::where('user_id', $this->id)
                ->whereHas('checkout.keranjang', function($keranjang){
                    $keranjang->where('user_id', $this->id);
                })
                ->whereMonth('created_at', date('m'))
                ->sum('poin');
        $poinUserFromRefferal = PoinUSer::where('user_id', $this->id)
                ->whereHas('refferal_user', function($refferal_user){
                    $refferal_user->where('parent_id', $this->id);
                })
                ->whereMonth('created_at', date('m'))
                ->sum('poin');
        $hpv = $poinUserFromCheckout + $poinUserFromRefferal;
        // $this->iterateChildrenRootsForHPV($tree, $this->parent_id, $hpv);
        return $hpv;
    }

    /**
     * Fungsi untuk menghitung home poin value user
     * fungsi akan loop seluruh children dan menjumlahkan hpvnya
     * return Home poin value user
     */
    public function hitungHPVFastest()
    {
        $poinUserFromCheckout = PoinUser::where('user_id', $this->id)
                ->whereHas('checkout.keranjang', function($keranjang){
                    $keranjang->where('user_id', $this->id);
                })
                ->whereMonth('created_at', date('m'))
                ->sum('poin');
        $poinUserFromRefferal = PoinUSer::where('user_id', $this->id)
                ->whereHas('refferal_user', function($refferal_user){
                    $refferal_user->where('parent_id', $this->id);
                })
                ->whereMonth('created_at', date('m'))
                ->sum('poin');
        $hpv = $poinUserFromCheckout + $poinUserFromRefferal;
        return $hpv;
    }

    /**
     * Fungsi untuk update bonus level 
     * Dijalankan ketika user Checkout
     */
    public function updateBonusLevel()
    {
        // Update Bonus Level
        $bonus_user_id = $this->checkBonusLevel();
        if(!empty($bonus_user_id)){ // update relation to SettingBonusLevel Model
            $this->bonus_level_user->last()->update([
                'setting_bonus_level_id' => $bonus_user_id,
                'updated_at' => now()
            ]);
        }else{ // jika user tidak punya bonus level hapus relasi ke SettingBonusLevel
            if(!empty($this->bonus_level_user->last()->setting_bonus_level_id)){
                $this->bonus_level_user->last()->update([
                    'setting_bonus_level_id' => null,
                    'updated_at' => now()
                ]);
            }
        }
    }

    /**
     * Fungsi untuk hitung bonus level yang didapat pada bulan ini.
     * Fungsi ini dijalankan ketika admin melakukan tutup buku di awal atau akhir bulan, dari last online sebelumnya
     * @param Date $date
     * @return void
     */
    public function hitungBonusLevelBulanIni($date, $simpan = true)
    {
        $bulanSebelumnya = Carbon::parse($date);
        // $bulanSebelumnya->subMonth(1);
        $totalBonusLevel = 0;
        // dd($bulanSebelumnya);
        // dd(!empty($this->bonus_level_user->last()->setting_bonus_level_id));
        if(!empty($this->bonus_level_user->last()->setting_bonus_level_id)){
            $setting_bonus_level = $this->bonus_level_user->last()->setting_bonus_level; //Setting bonus Level
            // dd($setting_bonus_level);
            if(!empty($setting_bonus_level->bonus_1)){
                $daftar_checkout_dari_keturunan_1 = Checkout::whereHas('keranjang.user', function($user){
                    $user->where('parent_id', $this->id);
                })->whereMonth('updated_at', $bulanSebelumnya->format('m'))
                ->where('status', 'diverifikasi')
                ->get();
                foreach ($daftar_checkout_dari_keturunan_1 as $checkout) {
                    $komisi_from_this_checkout = 0;
                    foreach($checkout->keranjang->produk as $produk){
                        $komisi_from_this_checkout += intval($produk->harga * $setting_bonus_level->bonus_1 / 100);
                    }
                    if($simpan){
                        $bonus_level_from_this_checkout = BonusLevelUser::create([
                            'user_id' => $this->id,
                            'bonus_level' => $komisi_from_this_checkout,
                            'total' => $this->bonus_level_user->last()->total + $komisi_from_this_checkout,
                            'setting_bonus_level_id' => $setting_bonus_level->id,
                            'checkout_id' => $checkout->id
                        ]);
                        KomisiUser::create([
                            'user_id' => $this->id,
                            'komisi' => $bonus_level_from_this_checkout->bonus_level,
                            'total' => $this->komisi_user->last()->total + $bonus_level_from_this_checkout->bonus_level,
                            'checkout_id' => $bonus_level_from_this_checkout->checkout_id,
                        ]);
                    }
                    $totalBonusLevel += $this->bonus_level_user->last()->total + $komisi_from_this_checkout;
                }
            }
            if(!empty($setting_bonus_level->bonus_2)){
                $daftar_checkout_dari_keturunan_2 = Checkout::whereHas('keranjang.user.parent', function($user){
                    $user->where('parent_id', $this->id);
                })->whereMonth('updated_at', $bulanSebelumnya->format('m'))
                ->where('status', 'diverifikasi')
                ->get();
                foreach ($daftar_checkout_dari_keturunan_2 as $checkout) {
                    $komisi_from_this_checkout = 0;
                    foreach ($checkout->keranjang->produk as $produk) {
                        $komisi_from_this_checkout += intval($produk->harga * $setting_bonus_level->bonus_2 / 100);
                    }
                    if($simpan){
                        $bonus_level_from_this_checkout = BonusLevelUser::create([
                            'user_id' => $this->id,
                            'bonus_level' => $komisi_from_this_checkout,
                            'total' => $this->bonus_level_user->last()->total + $komisi_from_this_checkout,
                            'setting_bonus_level_id' => $setting_bonus_level->id,
                            'checkout_id' => $checkout->id,
                        ]);
                        KomisiUser::create([
                            'user_id' => $this->id,
                            'komisi' => $bonus_level_from_this_checkout->bonus_level,
                            'total' => $this->komisi_user->last()->total + $bonus_level_from_this_checkout->bonus_level,
                            'checkout_id' => $checkout->id,
                        ]);
                    }
                    $totalBonusLevel += $this->bonus_level_user->last()->total + $komisi_from_this_checkout;
                }
            }

        }
        return $totalBonusLevel;
    }

    /**
     *  Fungsi untuk update HPV user
     *  Dijalankan ketika User Checkout
     * Fungsi ini akan membuat semua parent update HPVnya dari checkout user ini
     */
    public function updateHPV(Checkout $checkout = null)
    {
        if(!empty($this->parent)){
            $all_parent = $this->all_parent->toArray();
            $this->iterateParentRootsToUpdateParentHPV($all_parent, 0, $checkout);
        }
    }

    /**
     *   Fungsi ini dipakai untuk update Komisi user juga
     *   Fungsi dijalakankan ketika checkout user diverifikasi
     *   Update Poin From Checkout
     */
    public function updatePoin(Checkout $checkout)
    {
        if($checkout->status == 'diverifikasi'){
            $produks = $checkout->keranjang->produk;
            //User poin
            $poin = $this->poin_user->last()->total;
            $komisi = 0;
            $komisi_last = $this->komisi_user->last()->total;

            foreach($produks as $p){
                $poin += $p->poin_produk->poin;
                $k = $p->harga * $p->poin_produk->komisi / 100; //hitung komisi
                $komisi += $k;
            }

            // membuat poin user baru dari checkout
            $poin_from_this_checkout = $poin - $this->poin_user->last()->total;
            PoinUser::create([
                'user_id' => $this->id,
                'total' => $poin,
                'poin' => $poin_from_this_checkout,
                'checkout_id' => $checkout->id,
                'hpv' => $this->hitungHPV() + $poin_from_this_checkout,
            ]);
            // menghitung pertambahan poin all parent dari checkout ini
            $this->updateHPV($checkout);
            // update Bonus level
            $this->updateBonusLevel();
            
            //membuat komisi user baru dari checkout
            $komisi_total = $komisi_last + $komisi;
            KomisiUser::create([
                'user_id' => $this->id,
                'komisi' => intval($komisi),
                'total' => intval($komisi_total),
                'checkout_id' => $checkout->id
            ]);
        }
    }

    //loop tree 
    /**
     * Loop children tree
     * mengembalikan var &$data berisi array HPV semua children
     */
    public function iterateChildrenRootsForHPV($tree, $level, &$hpv) {
        foreach($tree as $index => $node) {
            // dd($tree);
            foreach($node as $index => $value) {
                
                if($index == "hpv") {
                    $this->iterateChildrenRootsForHPV($value, $level + 1, $hpv);
                }
                if($index == 'id'){
                    $poinUserFromCheckout = PoinUser::where('user_id', $value)
                        ->whereMonth('created_at', date('m'))
                        ->whereHas('checkout.keranjang.user', function($queryUser)use($value){
                            $queryUser->where('id', $value);  
                        })
                        ->sum('poin');
                    $poinUserFromRefferal = PoinUser::where('user_id', $value)
                        ->whereHas('refferal_user', function($refferal_user) use($value){
                            $refferal_user->where('parent_id', $value);
                        })
                        ->whereMonth('created_at', date('m'))
                        ->sum('poin');
                    $hpv += $poinUserFromCheckout + $poinUserFromRefferal;
                }
            }
        }
    }
    /**
     * loop tree parent
     * Buat Poin User parent yang baru dari transaksi children
     * mengembalikan var &$data berisi HPV semua parent yg telah dihitung
     */
    public function iterateParentRootsToUpdateParentHPV($tree, $level, Checkout $checkout = null) {
        $poinFromCheckout = 0;
        if(!empty($checkout)){
            foreach($checkout->keranjang->produk as $produk){
                $poinFromCheckout += $produk->poin_produk->poin;
            }
        }
        foreach($tree as $index => $value) {
            if($index == "all_parent") {
                if(!empty($value)){
                    $this->iterateParentRootsToUpdateParentHPV($value, $level + 1, $checkout);
                }
            }
            if($index == "poin_user") {
                // Hanya update HPV
                $collection_poin_user = collect($value);
                $poin_user = PoinUser::find($collection_poin_user->last()['id']);
                $checkout_id = !empty($checkout) ? $checkout->id : null;
                $hpv = $poin_user->user->hitungHPV();
                $poin_user = PoinUser::create([
                    'user_id' => $poin_user->user_id,
                    'hpv' => $hpv,
                    'poin' => $poinFromCheckout,
                    'total' => $poin_user->total,
                    'checkout_id' => $checkout_id,
                ]);
                $poin_user->user->updateBonusLevel();
            }
        }
    }
    public function iterateChildrenRootsForBonusLevelData($tree, &$data) {
        foreach($tree as $node_user) {
            // $poinUserFromCheckout = PoinUser::where('user_id', $node_user->id)
            // ->whereHas('checkout.keranjang', function($keranjang) use ($node_user) {
            //     $keranjang->where('user_id', $node_user->id);
            // })->whereMonth('created_at', date('m'))
            // ->sum('poin');
            // $poinUserFromRefferal = PoinUser::where('user_id', $node_user->id)
            // ->whereHas('refferal', function($refferal) use ($node_user){
            //     $refferal->where('parent_id', $node_user->id);
            // })->whereMonth('created_at', date('m'))
            // ->sum('poin');
            // $poinUserThisMonth = $poinUserFromCheckout + $poinUserFromRefferal;
            array_push($data, collect([
                'user_id' => $node_user->id,
                'hpv' => $node_user->poin_user->last()->hpv,
                'used' => false,
            ]));
        }
    }


    /**
     * Update Poin User dari penukaran poin
     */
    public function updatePoinFromPenukaranPoin(PenukaranPoin $penukaranPoin)
    {
        $userPoinLatest = $this->poin_user->last();
        $totalPoin = $userPoinLatest->total - $penukaranPoin->total;
        $poin = $penukaranPoin->total * -1;  // bentuk penukaran_poin->total bernilai positif. di poin user di ubah menjadi negatif disimpan di kolom poin

        /** jika penukaran ditolak membuat negasi dari ekspresi diatas
        *   poin yg asalnya negatif jadi positif
        *   total poin yg asalnya dikurang poin, diubah jadi ditambah dengan poin
        */
        if($penukaranPoin->status == 'ditolak'){
            $poin *= -1;
            $totalPoin = PoinUser::where('user_id', $this->id)
                ->whereHas('penukaran_poin.keranjang_poin', function($keranjangPoin){
                    $keranjangPoin->where('user_id', $this->id);
                })
                ->latest()
                ->first()
                ->total;
            $totalPoin += $poin;
        }

        // Membuat poin user sesuai perhitungan diatas
        PoinUser::create([
            'user_id' => $this->id,
            'poin' => $poin,
            'total' => $totalPoin,
            'hpv' => $userPoinLatest->hpv,
            'penukaran_poin_id' => $penukaranPoin->id
        ]);
    }

    /**
     * Update Poin User dari Refferal
     */
    public function updatePoinFromRefferal(User $user)
    {
        $poin = 1;
        $total_poin = $this->poin_user->last()->total + $poin;
        $count_referral = $this->poin_user->where('refferal_user_id', $user->id)->count();
        if($count_referral == 0){
            PoinUser::create([
                'user_id' => $this->id,
                'refferal_user_id' => $user->id,
                'total' => $total_poin,
                'poin' => $poin,
                'hpv' => $this->hitungHPV() + $poin,
            ]);
        }
    }

    public function updatePoinFromResetTahun($tanggal)
    {
        $poin = $this->poin_user->last()->total * -1;
        $total = $this->poin_user->last()->total + $poin;
        $hpv = $this->hitungHPV();
        PoinUser::create([
            'poin' => $poin,
            'total' => $total,
            'hpv' => $hpv,
            // 'tanggal_direset' => $tanggal
        ]);
    }

    public function changeName()
    {
        $name = $this->name . '1';
        echo $name;
        $this->update([
            'name' => $name
        ]);
    }

    public function format_nomor_telp()
    {
        $nomor_telp = $this->nomor_telp;
        if(!empty($nomor_telp)){
            if($nomor_telp[0] == "0"){
                $nomor_telp = "62".substr($nomor_telp, -strlen($nomor_telp) + 1);
            }
            else if($nomor_telp[0] != "0"){
                $nomor_telp = "62$nomor_telp";
            }
        }
        return $nomor_telp;
    }

    /**
     * Mengecek kelengkapan data user
     * return true jika lengkap
     * return false jika tidak lengkap
     * @return boolean
     */
    public function isDetailUserLengkap()
    {
        if(!empty($this->detail_user)){
            return !empty($this->detail_user->nama_depan) && !empty($this->email);
            // !empty($this->detail_user->jenis_kelamin) && 
            // !empty($this->detail_user->alamat) &&
            // !empty($this->detail_user->provinsi) &&
            // !empty($this->detail_user->kota) &&
            // !empty($this->detail_user->kecamatan) &&
            // !empty($this->nomor_telp);
            // !empty($this->detail_user->nomor_rekening) &&
            // !empty($this->detail_user->bank) &&
            // !empty($this->detail_user->cabang_bank)
        }else{
            return false;
        }
    }

    /**
     * check if user email and phone number is verified or not
     * @return boolean
     */
    public function diverifikasi()
    {
        $diverifikasi = true;
        if(
            empty($this->email_verified_at) 
            // || empty($this->nomor_telp_verified_at)
        ){
            $diverifikasi = false;
        }
        return $diverifikasi;
    }

    /**
     * Override MustVerifiEmail
     * fungsi ini dispatch EmailVerifikasi Jobs
     * @param User
     * @return void
     */
    public function sendEmailVerificationNotification(){
        EmailVerifikasi::dispatch($this);
    }

    public function hasCheckout()
    {
        $checkout_belum_lunas = Checkout::whereHas('keranjang', function($keranjang){
            $keranjang->where('user_id', $this->id);
        })->where('status', 'tertunda')->orWhere('status', null)->count();
        if($checkout_belum_lunas > 0){
            return true;
        }
        return false;
    }

    // All relation
    public function bonus_level_user()
    {
        return $this->hasMany(BonusLevelUser::class);
    }

    public function keranjang()
    {
        return $this->hasMany(Keranjang::class);
    }

    public function detail_user()
    {
        return $this->hasOne(DetailUser::class);
    }

    public function checkout()
    {
        return $this->hasManyThrough(Checkout::class, Keranjang::class, 'id', 'keranjang_id');
    }

    public function parent()
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function all_parent()
    {
        return $this->parent()->with('all_parent');
    }

    public function children()
    {
        return $this->hasMany(self::class, 'parent_id');
    }

    public function hpv()
    {
        return $this->children()->with('hpv');
    }

    public function poin_user()
    {
        return $this->hasMany(PoinUser::class);
    }

    public function komisi_user()
    {
        return $this->hasMany(KomisiUser::class);
    }

    public function keranjang_poin()
    {
        return $this->hasMany(KeranjangPoin::class);
    }

    public function otp()
    {
        return $this->hasOne(OTPUser::class);
    }

}
