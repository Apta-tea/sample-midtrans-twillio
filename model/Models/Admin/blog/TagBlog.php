<?php

namespace App\Models\Admin\blog;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TagBlog extends Model
{
    use HasFactory;
    protected $guarded = '';

    public function blog()
    {
        return $this->belongsToMany(Blog::class);
    }
}
