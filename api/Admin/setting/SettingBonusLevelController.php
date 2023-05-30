<?php

namespace App\Http\Controllers\Api\Admin\setting;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\setting\SettingBonusLevelResource;
use App\Models\Admin\setting\SettingBonusLevel;
use Illuminate\Http\Request;

class SettingBonusLevelController extends Controller
{
    public function index(Request $request)
    {
        $settingBonusLevel = SettingBonusLevel::get();
        return SettingBonusLevelResource::collection($settingBonusLevel);
    }

    public function bagan_syarat($id)
    {
        $settingBonusLevel = SettingBonusLevel::find($id);
        $id = 1;
        $datasource = [
            'id' => $id,
            'name' => 'Syarat Level',
            'title' => $settingBonusLevel->level,
            'children' => [
                collect([
                    'id' => ++$id,
                    'name' => 'Home Point Value',
                    'title' => $settingBonusLevel->hpv,
                    'children' => []
                ])
            ]
        ];
        $children = [];
        foreach ($settingBonusLevel->rpv as $rpvKey => $rpv) {
            for ($i=0; $i < $rpv->jumlah; $i++) { 
                array_push($children, collect([
                    'id' => ++$id,
                    'name' => 'Root Point Value ke-'.$rpvKey,
                    'title' => $rpv->rpv,
                ]));
            }
        }
        $datasource['children'][0]['children'] = $children;
        // $id = 1;
        // $datasource = [
        //     'id' => $id,
        //     'name' => 'Syarat Level',
        //     'title' => $settingBonusLevel->level,
        //     'children' => [
        //         collect([
        //             'id' => ++$id,
        //             'name' => 'Home Point Value',
        //             'title' => $settingBonusLevel->hpv,
        //             'children' => [],
        //         ])
        //     ]
        // ];
        // $dataChildren = [];
        // $level = 1;
        // $last_parent_id = 0;
        // foreach ($settingBonusLevel->rpv as $rpvKey => $rpv) {
        //     for ($i=0; $i < $rpv->jumlah; $i++) { 
        //         if($rpvKey != 0){
        //             array_push($dataChildren, collect([
        //                 'parent_id' => $last_parent_id,
        //                 'id' => ++$id,
        //                 'name' => 'Root Point Value ke-'.$rpvKey,
        //                 'title' => $rpv->rpv,
        //                 'level' => $level,
        //                 'children' => [],
        //             ]));
        //         }else{
        //             array_push($dataChildren, collect([
        //                 'id' => ++$id,
        //                 'name' => 'Root Point Value ke-'.$rpvKey,
        //                 'title' => $rpv->rpv,
        //                 'level' => $level,
        //                 'children' => [],
        //             ]));
        //         }
        //     }
        //     $last_parent_id = $dataChildren[count($dataChildren)-1]['id'];
        //     $level++;
        // }
        // $dataChildren = collect($dataChildren);
        // for ($i=1; $i < $level; $i++) { 
        //     $curentLevel = $dataChildren->where('parent_id', $dataChildren->where('level', $i)->last()['id'])->all();
        //     $child = [];
        //     foreach($curentLevel as $curlev){
        //         array_push($child, $curlev);
        //     }
        //     $dataChildren->where('level', $i)->last()->put('children', $child);
        // }
        // $datasource['children'][0]['children'] = $dataChildren->where('level', 1);
        $datasource = collect($datasource);
        $datasource = json_encode($datasource);
        $datasource = json_decode($datasource);
        return response([
            'data'=> $datasource,
        ]);
    }
}
