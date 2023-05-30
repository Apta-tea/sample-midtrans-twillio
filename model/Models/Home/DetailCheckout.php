<?php

namespace App\Models\Home;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;
use App\Mail\Home\checkout\CheckoutMail;
use Illuminate\Support\Str;

class DetailCheckout extends Model
{
    use HasFactory;
    protected $guarded = '';

    public function checkout()
    {
        return $this->belongsTo(Checkout::class);
    }

    public function nama_lengkap()
    {
        return $this->nama_depan.' '.$this->nama_belakang;
    }

    public function alamat_lengkap()
    {
        $provinsi = json_decode($this->provinsi);
        $provinsi = Str::title($provinsi->name);
        $kota = json_decode($this->kota);
        $kota = Str::title($kota->name);
        
        $kecamatan = json_decode($this->kecamatan);
        $kecamatan = !empty($kecamatan) ? Str::title($kecamatan->name) : '';
        // $desa = !empty($this->desa) ? json_decode($this->desa) : '';
        // // dd($desa);
        // $desa = !empty($desa->name) ? Str::title($desa->name) : '';
        $kode_pos = $this->kode_pos;
        $alamat = $this->alamat.', '.$kecamatan.', '.$kota.', '.$provinsi.'. '.$kode_pos;
        return $alamat;
    }

    public function format_nomor_telp()
    {
        $nomor_telp = $this->nomor_telp;
        if($nomor_telp[0] == "0"){
            $nomor_telp = "+62".substr($nomor_telp, -strlen($nomor_telp) + 1);
        }
        else if($nomor_telp[0] != "0"){
            $nomor_telp = "+62$nomor_telp";
        }

        return $nomor_telp;
    }
}
