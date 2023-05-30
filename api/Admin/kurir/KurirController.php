<?php

namespace App\Http\Controllers\Api\Admin\kurir;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\kurir\KurirResource;
use App\Models\Admin\kurir\Kurir;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class KurirController extends Controller
{
    public function index()
    {
        $kurir = Kurir::get();
        return KurirResource::collection($kurir);
    }

    /**
     * Request Cost to rajaongkir
     */
    public function cost($kota, $berat, $courier)
    {
        if(env('RAJAONGKIR_KEY','3029d123ea37082c43a12ed9929ddf93')){
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://pro.rajaongkir.com/api/cost",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => "destinationType=city&originType=city&origin=23&destination=".$kota."&weight=".$berat."&courier=".$courier,
                CURLOPT_HTTPHEADER => array(
                    "content-type: application/x-www-form-urlencoded",
                    "key: ".env('RAJAONGKIR_KEY','3029d123ea37082c43a12ed9929ddf93')
                ),
            ));
    
            $response = curl_exec($curl);
            $err = curl_error($curl);
    
            curl_close($curl);
    
            if ($err) {
                return "cURL Error #:" . $err;
            } else {
                return json_decode($response, true);
            }
        }
    }

    /**
     * get kota from raja ongkir
     */
    public function kota($provinsi)
    {
        if(env('RAJAONGKIR_KEY','3029d123ea37082c43a12ed9929ddf93')){
            $curl = curl_init();
    
            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://pro.rajaongkir.com/api/city?province=".$provinsi,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_HTTPHEADER => array(
                    "key: ".env('RAJAONGKIR_KEY','3029d123ea37082c43a12ed9929ddf93')
                ),
            ));
    
            $response = curl_exec($curl);
            $err = curl_error($curl);
    
            curl_close($curl);
    
            if ($err) {
                return response("cURL Error #:" . $err);
            } else {
                return json_decode($response, true);
            }
        }
        return response([
            'data' => "key null"
        ]);
    }


    /**
     * get provinsi from raja ongkir
     */
    public function provinsi()
    {
        // dd(env('RAJAONGKIR_KEY','3029d123ea37082c43a12ed9929ddf93'));
        if(env('RAJAONGKIR_KEY','3029d123ea37082c43a12ed9929ddf93')){
            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => "https://pro.rajaongkir.com/api/province",
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "GET",
                CURLOPT_HTTPHEADER => array(
                    "key: ".env('RAJAONGKIR_KEY','3029d123ea37082c43a12ed9929ddf93')
                ),
            ));

            $response = curl_exec($curl);
            $err = curl_error($curl);

            curl_close($curl);

            // if($response["rajaongkir"]["results"]["status"]["code"] == 400){
            //     return response($response["rajaongkir"]["results"]["status"]["description"]);
            // }

            if ($err) {
                dd("cURL Error #:" . $err);
            } else {
                return json_decode($response, true);
            }
        }
    }

    /**
     * Input :
     * provinsi => ID Provinsi Rajaongkir
     * kota => ID Kota Rajaongkir
     * berat => berat total keranjang
     * slug => slug untuk request cost rajaongkir
     * 
     * Output: 
     * Cost from rajaongkir
     */
    public function kurir(Request $request)
    {
        $prov_id = $request->provinsi["id"]; 
        $kot_id = $request->kota["id"];
        $berat = $request->berat_dari_volume;
        $slug = $request->slug;

        if(empty($request->slug)){
            return response()->json([
                'session' => 'failed',
                'message' => 'Slug tidak boleh kosong'
            ]);
        }

        if(!empty($prov_id)){

            //Jika Provinsi ada di pulau jawa pakai berat asli
            $idProvPulauJawa = [9, 10, 11];
            if(in_array($prov_id, $idProvPulauJawa)){
                $berat = $request->berat;
            }

        }

        $cost = $this->cost($kot_id, $berat, $slug);

        return response()->json($cost["rajaongkir"]);
    }
}
