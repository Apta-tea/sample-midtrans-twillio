<?php

namespace App\Http\Controllers\Api\Admin\blog;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\blog\BlogEditResource;
use App\Http\Resources\Admin\blog\BlogResource;
use Illuminate\Http\Request;
use App\Models\Admin\blog\Blog;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class BlogController extends Controller
{
    public function index(Request $request)
    {
        $blog = Blog::query();
        if(!empty($request->cari)){
            $blog = $blog->where('judul', 'LIKE', "%".$request->cari."%");
        }

        $blog = $blog->orderBy('created_at', 'desc')->paginate(12);
        return BlogResource::collection($blog);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'judul' => 'unique:blogs|required|min:3|max:200',
            'isi' => 'required|min:100',
            'foto' => 'required|mimes:jpg,png,svg|max:1024',
            'tag_blog' => 'nullable|array',
            'user_id' => 'nullable',
        ]);

        if($validator->fails()){
            return response([
                'errors' => json_decode($validator->messages()->toJson())
            ]);
        }

        $blogAttr = $request->except('tag_blog');
        $blogAttr['slug'] = Str::slug($request->judul);

        if($request->file('foto')){
            $imageName = $blogAttr['slug'].time().'.'.$request->file('foto')->getClientOriginalExtension();
            $file = $request->file('foto')->move('img/blog', $imageName);
            $blogAttr['foto'] = $file->getPathname();
        }

        $blog = Blog::create($blogAttr);
        $blog->tag_blog()->attach($request->tag_blog);

        return response([
            'session' => 'success',
            'message' => 'Blog telah tersimpan'
        ]);
    }

    public function update(Request $request, $id)
    {
        $blog = Blog::find($id);
        $validator = Validator::make($request->all(), [
            'judul' => 'required|min:3|max:200|unique:blogs,judul,'.$id,
            'isi' => 'required|min:100',
            'foto' => 'nullable|mimes:jpg,png,svg|max:1024',
            'tag_blog' => 'nullable',
        ]);
        if($validator->fails()){
            return response([
                'errors' => json_decode($validator->messages()->toJson())
            ]);
        }
        
        $blogAttr = $request->except('tag_blog');
        $blogAttr['slug'] = Str::slug($request->judul);

        if($request->file('foto')){
            if(is_file(public_path($blog->foto))){
                File::delete(public_path($blog->foto));
            }
            $imageName = $blogAttr['slug'].time().'.'.$request->file('foto')->getClientOriginalExtension();
            $file = $request->file('foto')->move('img/blog', $imageName);
            $blogAttr['foto'] = $file->getPathname();
        }else{
            $blogAttr['foto'] = $blog->foto;
        }

        $blog->update($blogAttr);
        $blog->tag_blog()->sync($request->tag_blog);

        return response([
            'session' => 'success',
            'message' => 'Blog telah terupdate'
        ]);
    }

    public function show($id)
    {
        $blog = Blog::find($id);
        return new BlogEditResource($blog);
    }

    public function destroy($id)
    {
        $blog = Blog::find($id);
        if(is_file(public_path($blog->foto))){
            File::delete(public_path($blog->foto));
        }
        $blog->delete();
        return response([
            'session' => 'failed',
            'message' => 'Blog telah dihapus'
        ]);
    }
}
