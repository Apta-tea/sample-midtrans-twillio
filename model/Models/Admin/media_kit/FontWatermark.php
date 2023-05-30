<?php

namespace App\Models\Admin\media_kit;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FontWatermark extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $with = [
        'color',
        'size'
    ];

    public function color()
    {
        return $this->belongsTo(ColorWatermark::class);
    }

    public function size()
    {
        return $this->belongsTo(SizeWatermark::class);
    }
}
