<?php

namespace App\Http\Controllers\Api\Admin\kurir;

use App\Http\Controllers\Api\Admin\kurir\KurirController;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Admin\kurir\MasterKurir;
use App\Http\Resources\Admin\kurir\MasterKurirResource;
use Illuminate\Support\Facades\Validator;

use function Complex\add;

class masterkurirController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $kurir = MasterKurir::query();
        $kurir = $kurir->orderBy('created_at', 'desc')->paginate(30);
        return MasterKurirResource::collection($kurir);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if(request()->user() != 'admin'){ return abort(401); }
        $validation = Validator::make($request->all(), [
            'nama' => 'required|string|min:2|max:255',
            'slug' => 'required|string|min:2|max:255',
        ]);
        if($validation->fails()){
            return response()->json([
                'errors' => $validation->messages()->toJson()
            ]);
        }
        $status = null;
        if($request->status){
            $status = 'dipakai';
        }else{
            $status = 'tidakdipakai';
        }
        
        // Insert to database
        MasterKurir::create([
            'nama' => $request->nama,
            'slug' => \Str::slug($request->slug),
            'status' => $status,
        ]);

        return response()->json([
            'session' => 'success',
            'message' => 'Data telah disimpan.'
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if(request()->user() != 'admin'){ return abort(401); }
        $kurir = MasterKurir::find($id);
        $validation = Validator::make($request->all(), [
            'nama' => 'required|string|min:3|max:255',
            'slug' => 'required|string|min:2|max:255',
        ]);
        if($validation->fails()){
            return response()->json([
                'errors' => $validation->messages()->toJson()
            ]);
        }
        $status = null;
        if($request->status){
            $status = 'dipakai';
        }else{
            $status = 'tidakdipakai';
        }
        
        // Insert to database
        $kurir->update([
            'nama' => $request->nama,
            'slug' => \Str::slug($request->slug),
            'status' => $status,
        ]);

        return response()->json([
            'session' => 'success',
            'message' => 'Data telah diupdate.'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        if(request()->user() != 'admin'){ return abort(401); }
        $kurir = MasterKurir::find($id);
        $kurir->delete();
        return response()->json([
            'session' => 'failed',
            'message' => 'Data telah dihapus.'
        ]);
    }

    public function all(Request $request)
    {
        // dd($request->all());
        $kurir = MasterKurir::query();
        if(!empty($request->status)){
            $kurir->where('status', $request->status);
        }
        if(!empty($request->dipakai)){
            if($request->dipakai == 'true'){
                $kurir->where('status', 'dipakai');
            }else{
                $kurir->where('status', 'tidakdipakai');
            }
        }
        $kurir = $kurir->get();
        return MasterKurirResource::collection($kurir);
    }

    public function kurir_all(Request $request)
    {
        $validation = Validator::make($request->all(),[
            'kota_id' => 'required',
            'berat' => 'required',
        ]);
        if($validation->fails()){
            return response([
               'errors' => $validation->messages()->toJson() 
            ]);
        };
        $masterKurir = MasterKurir::query();
        if(!empty($request->status)){
            $masterKurir->where('status', $request->status);
        }
        if(!empty($request->dipakai)){
            if($request->dipakai == 'true'){
                $masterKurir->where('status', 'dipakai');
            }else{
                $masterKurir->where('status', 'tidakdipakai');
            }
        }
        $masterKurir = $masterKurir->get();
        $kurir = collect([
            'data' => collect()
        ]);
        foreach ($masterKurir as $mk) {
            $kurirController = new KurirController();
            $kurirCollection = $kurirController->cost($request->kota_id, $request->berat, $mk->slug);
            $kurirCollection = $kurirCollection;
            // dd($kurirCollection['rajaongkir']);
            foreach ($kurirCollection['rajaongkir']['results'][0]['costs'] as $cost) {
                $data = [];
                $data['nama'] = $kurirCollection['rajaongkir']['results'][0]['name'] .' '. $cost['service'];
                $data['harga'] = $cost['cost'][0]['value'];
                $data['harga_currency'] = number_format($cost['cost'][0]['value'], 0, ',', '.');
                $data['slug'] = $mk->slug;
                $data['deskripsi'] = $cost['description']; 
                $data['estimasi'] = $cost['cost'][0]['etd']; 
                $data['link'] = '#';
                $kurir['data']->add($data);
            }
            // $kurir->add($kurirCollection);
        };
        return $kurir;
    }
}
