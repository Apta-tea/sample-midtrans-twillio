<?php

namespace App\Models\Admin\blog;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Blog extends Model
{
    use HasFactory;
    protected $guarded = '';
    protected $with = ['user', 'tag_blog'];

    public function tag_blog()
    {
        return $this->belongsToMany(TagBlog::class);
    }

    public function user()
    {
        return $this->belongsTo("\App\Models\User");
    }
}
