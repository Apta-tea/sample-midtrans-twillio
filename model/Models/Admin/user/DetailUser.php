<?php

namespace App\Models\Admin\user;

use App\Models\Admin\bank\Bank;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use Illuminate\Support\Str;
use PDO;

class DetailUser extends Model
{
    use HasFactory;
    protected $guarded = '';
    protected $with = ['bank'];
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }

    public function nama_lengkap()
    {
        return $this->nama_depan.' '.$this->nama_belakang;
    }
    
    public function alamat_lengkap()
    {
        $alamat = $this->alamat;
        $provinsi = is_string($this->provinsi) ? 
            json_decode($this->provinsi) : 
            json_decode(json_encode($this->provinsi));
        $alamat .= !empty($provinsi) ? ', '.Str::title($provinsi->name) : '';
        // dd(json_decode(json_encode($this->kota)));
        $kota = is_string($this->kota) ? 
            json_decode($this->kota) : 
            json_decode(json_encode($this->kota));
        $alamat .= !empty($kota) ? ', '.Str::title($kota->name) : '';
        $kecamatan = is_string($this->kecamatan) ? 
            json_decode($this->kecamatan) : 
            json_decode(json_encode($this->kecamatan));
        $alamat .= !empty($kecamatan) ? ', '.Str::title($kecamatan->name) : '';
        $desa = !empty($this->desa) ? 
            $this->desa : 
            '';
        $desa = is_string($desa) ?
            json_decode($desa) : 
            json_decode(json_encode($desa));
        $alamat .= !empty($desa) ? ', '.Str::title($desa->name) : '';
        $alamat  .= !empty($this->kode_pos) ? '. '.$this->kode_pos : '';
        return $alamat;
    }


    /**
     * Fungsi untuk mengetahui apakah alamat user ada di pulau jawa
     * @return Boolean
     */
    public function tinggalDiPulauJawa()
    {
        $idProvinsi = [9, 10, 11]; // ID provinsi di pulau jawa
        $provinsi = json_decode($this->provinsi);
        if(empty($provinsi)){
            return false;
        }
        if(in_array($provinsi->id, $idProvinsi)){
            return true;
        }else{
            return false;
        }
    }
}
