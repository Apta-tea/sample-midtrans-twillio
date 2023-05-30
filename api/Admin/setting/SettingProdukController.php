<?php

namespace App\Http\Controllers\Api\Admin\setting;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\setting\SettingProdukResource;
use App\Models\Admin\setting\SettingProduk;
use Illuminate\Http\Request;

class SettingProdukController extends Controller
{
    public function index()
    {
        $settingProduk  = SettingProduk::first();
        if(empty($settingProduk)){
            $settingProduk = SettingProduk::create([
                'ppn' => 10,
                'komisi' => 25
            ]);
        }
        return new SettingProdukResource($settingProduk);
    }
}
