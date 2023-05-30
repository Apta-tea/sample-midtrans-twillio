<?php

namespace App\Models\Admin\media_kit;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SizeWatermark extends Model
{
    use HasFactory;
    protected $guarded = '';

    public function font_watermark()
    {
        return $this->hasMany(FontWatermark::class);
    }
}
