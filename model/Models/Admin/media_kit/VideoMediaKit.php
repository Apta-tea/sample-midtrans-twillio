<?php

namespace App\Models\Admin\media_kit;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VideoMediaKit extends Model
{
    use HasFactory;
    protected $guarded = '';

    public function ytID()
    {
        $video_url = $this->link;
        //parse the url 
        $parsed_url = parse_url($video_url); 
        if($parsed_url["path"] == "youtube.com/watch"){ 
            $this->video_url = "https://www.".$video_url; 
        }elseif($parsed_url["path"] == "www.youtube.com/watch"){ 
            $this->video_url = "https://".$video_url; 
        } 
         
        if(isset($parsed_url["query"])){ 
            $query_string = $parsed_url["query"]; 
            //parse the string separated by '&' to array 
            parse_str($query_string, $query_arr); 
            if(isset($query_arr["v"])){ 
                return $query_arr["v"]; 
            } 
        }    
    }
}
