<?php

namespace App\Http\Controllers\Api\Admin\user;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\user\HPVResource;
use App\Models\User;
use Illuminate\Http\Request;

class HPVController extends Controller
{
    public function show(Request $request, $id)
    {
        $user = User::with('children')->withCount('children')->where('id', $id)->first();
        $pagination = $user->children();
        if(!empty($request->username)){
            $pagination = $pagination->where('name', 'like', '%'.$request->username.'%');
        }
        if(!empty($request->sortBy)){
            if($request->sortBy == 'terbaru'){
                $pagination->orderBy('created_at', 'desc');
            }
            if($request->sortBy == 'terakhir'){
                $pagination->orderBy('created_at', 'asc');
            }
        }
        $pagination = $pagination->paginate(15);
        $user->setRelation('children', $pagination);


        // // $user->updateHPV();
        // $tree = $user->hpv->toArray();
        // // dd(User::find(3)->hpv->toArray());
        // $data = [];
        // $this->iterate_tree($tree, 0, $data);
        // // dd($data); i think i should comment this foreach loop ?
        // foreach($data as $d){
        //     $u = User::with('hpv')->find($d);
        //     // dd($u->hpv->toArray());
        //     $u->poin_user->last()->update([
        //         'hpv' => $u->hitungHPV()
        //     ]);
        //     // dd($user->hitungHPV());
        // }


        $user->current_id = $user->id;
        $next = null;
        $prev = null;
        if(!empty($pagination->nextPageUrl())){
            $next = array();
            parse_str(parse_url($pagination->nextPageUrl())['query'], $next);
            $next = $next['page'];
        }
        if(!empty($pagination->previousPageUrl())){
            $prev = array();
            parse_str(parse_url($pagination->previousPageUrl())['query'], $prev);
            $prev = $prev['page'];
        }
        return (new HPVResource($user))->additional([
            "links" => [
                "first" => 1,
                "last" => $pagination->lastPage(),
                "prev" => $prev,
                "next" => $next
            ],
            "meta" => [
                "current_page" => $pagination->currentPage(),
                "last_page" => $pagination->lastPage(),
                "total" => $pagination->total(),
                "from" => count($pagination->items()),
                "per_page" => $pagination->perPage(),
            ]
        ]);
    }
    //loop tree 
    public function iterate_tree($tree, $level, &$data) {
        foreach($tree as $index => $node) {
            // dd($node);
            foreach($node as $index => $value) {
                // dd($node);
                if($index == "hpv") {
                    $this->iterate_tree($value, $level + 1, $data);
                }
                if($index == "id") {
                    array_push($data, $value);
                }
            }
        }
    }
}
