<?php

namespace App\Http\Controllers\Api\Admin\rajaongkir;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\rajaongkir\KecamatanResource;
use App\Http\Resources\Admin\rajaongkir\KotaResource;
use App\Http\Resources\Admin\rajaongkir\ProvinsiResource;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;

class RajaongkirController extends Controller
{
    public function token()
    {
        $user = User::first();
        $token = $user->createToken(\Str::random(6))->plainTextToken;
        return response([
            'token' => $token
        ]);
    }
    public function provinsi(Request $request)
    {
        $url = "https://pro.rajaongkir.com/api/province";
        if(!empty($request->id)){
            $url .= "?id=".$request->id;
        }
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_HTTPHEADER => array(
                "Accept-Encoding: gzip, deflate, br",
                "Connection: Keep-alive",
                "key: ".env('RAJAONGKIR_KEY', '3029d123ea37082c43a12ed9929ddf93')
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);
        
        curl_close($curl);
        // dd(json_decode($response));s
        if(empty($response) || $response == false){
            return response([
                'message' => 'Data gagal didapat.'
            ]);
        }

        if ($err) {
            "cURL Error #:" . $err;
        } else {
            $response = json_decode($response);
            if(!empty($request->id)){
                return new ProvinsiResource($response->rajaongkir->results);
            }
            return ProvinsiResource::collection($response->rajaongkir->results);
        }
    }

    public function kota(Request $request)
    {
        $validator = Validator($request->all(), [
            'provinsi_id' => 'required'
        ]);
        if($validator->fails()){
            return response([
                'errors' => json_decode($validator->messages()->toJson()),
            ]);
        }
        $url = "https://pro.rajaongkir.com/api/city?province=".$request->provinsi_id;
        if(!empty($request->id)){
            $url .= "&id=".$request->id;
        }
        $curl = curl_init();
        curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "key: ".env('RAJAONGKIR_KEY', '3029d123ea37082c43a12ed9929ddf93')
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            "cURL Error #:" . $err;
        } else {
            $response = json_decode($response);
            // dd($response);
            if(!empty($response)){
                if(!empty($response->rajaongkir)){
                    if(!empty($response->rajaongkir->results)){
                        if(!empty($request->id)){
                            return new KotaResource($response->rajaongkir->results);
                        }
                        return KotaResource::collection($response->rajaongkir->results);
                    }
                    return response([
                        'session' => 'failed',
                        'message' => 'Response rajaongkir results null',
                        'data' => $response
                    ]);
                }
                return response([
                    'session' => 'failed',
                    'message' => 'Response rajaongkir null',
                    'data' => $response
                ]);
            }
            return response([
                'session' => 'failed',
                'message' => 'Response null',
                'data' => $response
            ]);
        }
    }
    public function kecamatan(Request $request)
    {
        $validator = Validator($request->all(), [
            'kota_id' => 'required'
        ]);
        if($validator->fails()){
            return response([
                'errors' => json_decode($validator->messages()->toJson()),
            ]);
        }
        $url = "https://pro.rajaongkir.com/api/subdistrict?city=".$request->kota_id;
        if(!empty($request->id)){
            $url .= "&id=".$request->id;
        }
        $curl = curl_init();
        curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "key: ".env('RAJAONGKIR_KEY', '3029d123ea37082c43a12ed9929ddf93')
            ),
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            "cURL Error #:" . $err;
        } else {
            $response = json_decode($response);
            if(!empty($request->id)){
                return new KecamatanResource($response->rajaongkir->results);
            }
            return KecamatanResource::collection($response->rajaongkir->results);
        }
    }

    public function test(Request $request)
    {
        $requestProvinsi = new Request();
        // dd($request->all());
        $requestProvinsi->merge(['id' => $request->provinsi_id]);
        // dd($requestProvinsi->all());
        $provinsi = $this->provinsi($requestProvinsi);
        return json_decode( $provinsi->toJson());
        // return $provinsi;
    }
}
