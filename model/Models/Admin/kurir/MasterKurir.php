<?php

namespace App\Models\Admin\kurir;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MasterKurir extends Model
{
    use HasFactory;
    protected $guarded = '';
    protected $table = 'masterkurir';

    public function isDipakai(){
        $dipakai = false;
        if($this->status == 'dipakai'){
            $dipakai = true;
        }
        return $dipakai;
    }
}
