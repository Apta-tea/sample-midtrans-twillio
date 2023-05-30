<?php

namespace App\Http\Controllers\Api\Admin\penukaran_poin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\penukaran_poin\PenukaranPoinResource;
use App\Http\Resources\Admin\penukaran_poin\ProdukPoinResource;
use App\Http\Resources\Admin\produk\ProdukResource;
use App\Http\Resources\UserResource;
use App\Models\Admin\penukaran_poin\KeranjangPoin;
use App\Models\Admin\penukaran_poin\PenukaranPoin;
use App\Models\Admin\penukaran_poin\ProdukPoin;
use App\Models\Admin\produk\Produk;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class PenukaranPoinController extends Controller
{

    public function index(Request $request)
    {
        $penukaranPoin = PenukaranPoin::query();

        // dd($request->all());
        if(!empty($request->tahun)){
            $penukaranPoin = $penukaranPoin->whereYear('created_at', $request->tahun);
            $penukaranPoin = $penukaranPoin->whereMonth('created_at', $request->bulan);
        }

        $penukaranPoin = $penukaranPoin->orderBy('updated_at', 'desc')->paginate(10);
        return PenukaranPoinResource::collection($penukaranPoin);
    }

    public function show($id)
    {
        $penukaranPoin = PenukaranPoin::find($id);
        return new PenukaranPoinResource($penukaranPoin);
    }
    public function terima_permintaan(Request $request)
    {
        //validation
        if(empty($request->id)){
            return response([
                'session' => 'failed',
                'message' => 'Request ID null.'
            ]);
        }
        $penukaranPoin = PenukaranPoin::whereIn('id', $request->id);
        if(empty($penukaranPoin)){
            return response([
                'session' => 'failed',
                'message' => 'Gagal mendapatkan data.'
            ]);
        }
        if(empty($request->tanggal_pengiriman)){
            return response([
                'session' => 'failed',
                'message' => 'Tanggal pengiriman tidak boleh kosong.'
            ]);
        }
        $penukaranPoin->update([
            'status' => 'diverifikasi',
            'tanggal_pengiriman' => $request->tanggal_pengiriman,
        ]);

        return response([
            'session' => 'success',
            'message' => 'Permintaan telah diverifikasi.'
        ]);
    }

    public function tolak_permintaan(Request $request)
    {
        if(empty($request->alasan)){
            return response([
                'session' => 'failed',
                'message' => 'Alasan penolakan harus diisi'
            ]);
        }
        if(empty($request->id)){
            return response([
                'session' => 'failed',
                'message' => 'Request ID null.',
            ]);
        }
        $penukaranPoin = PenukaranPoin::whereIn('id', $request->id);
        $penukaranPoin->update([
            'status' => 'ditolak',
            'alasan' => $request->alasan
        ]);

        foreach($penukaranPoin->get() as $penukaran){
            $user = $penukaran->keranjang_poin->user;
            $user->updatePoinFromPenukaranPoin($penukaran);
        }
        return response([
            'session' => 'failed',
            'message' => 'Permintaan telah ditolak.'
        ]);
    }

    public function user(Request $request)
    {
        $user = User::find($request->user_id);
        return new UserResource($user);
    }
    public function produk(Request $request)
    {
        $produk = ProdukPoin::query();
        $produk = $produk->orderBy('updated_at', 'desc');
        $produk = $produk->paginate(8);
        return ProdukPoinResource::collection($produk);
    }
    public function store(Request $request)
    {
        $keranjangPoin = KeranjangPoin::find($request->keranjang_poin_id);
        if(empty($keranjangPoin)){
            return response('keranjang poin id cannot null'); //for debug
        }
        if($keranjangPoin->produk_poin->count() < 1){
            return response([
                'session' => 'failed',
                'message' => 'Keranjang tidak boleh kosong.'
            ]);
        }
        if(!empty($keranjangPoin->penukaran_poin)){
            return response([
                'session' => 'failed',
                'message' => 'Keranjang telah diproses.'
            ]);
        }
        $PenukaranPoin = PenukaranPoin::create([
            'keranjang_poin_id' => $keranjangPoin->id,
            'total' => $keranjangPoin->subtotal,
            'status' => 'diproses'
        ]);

        $user = $keranjangPoin->user;
        $user->updatePoinFromPenukaranPoin($PenukaranPoin);

        KeranjangPoin::create([
            'user_id' => $user->id,
        ]);
        return response([
            'session' => 'success',
            'message' => 'Poin kamu telah ditukar.'
        ]);
    }
}
