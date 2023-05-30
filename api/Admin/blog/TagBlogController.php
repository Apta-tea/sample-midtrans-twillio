<?php

namespace App\Http\Controllers\Api\Admin\blog;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\blog\TagBlogResource;
use App\Models\Admin\blog\TagBlog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TagBlogController extends Controller
{
    public function index(Request $request)
    {
        $tagBlog = TagBlog::query();
        $tagBlog = $tagBlog->orderBy('created_at', 'desc')->paginate(10);
        return TagBlogResource::collection($tagBlog);
    }

    public function store(Request $request)
    {
        $validation = Validator::make($request->all(), [
            'nama' => 'required|min:3|max:200', 
        ]);

        if($validation->fails()){
            return response([
                'errors' => json_decode($validation->messages()->toJson())
            ]);
        }

        TagBlog::create([
            'nama' => $request->nama
        ]);

        return response([
            'session' => 'success',
            'message' => 'Tag blog telah tersimpan'
        ]);
    }

    public function update(Request $request, $id)
    {
        $tagBlog = TagBlog::find($id);
        $validation = Validator::make($request->all(), [
            'nama' => 'required|min:3|max:200', 
        ]);

        if($validation->fails()){
            return response([
                'errors' => json_decode($validation->messages()->toJson())
            ]);
        }

        $tagBlog->update([
            'nama' => $request->nama
        ]);

        return response([
            'session' => 'success',
            'message' => 'Tag blog telah terupdate'
        ]);
    }

    public function destroy($id)
    {
        $tagBlog = TagBlog::find($id);
        $tagBlog->delete();
        return response([
            'session' => 'failed',
            'message' => 'Tag blog telah dihapus'
        ]);
    }
}
