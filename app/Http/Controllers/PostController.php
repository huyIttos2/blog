<?php

namespace App\Http\Controllers;

use App\Category;
use App\Post;
use App\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Kunnu\Dropbox\Dropbox;
use Kunnu\Dropbox\DropboxApp;

class PostController extends Controller
{
    public function index(){
        $posts = Post::latest()->approved()->published()->paginate(6);
        return view('posts', compact('posts'));
    }
    public function details($slug){
        $post = Post::where('slug', $slug)->first();
        $blogKey = 'blog_' . $post->id;
        if(!Session::has($blogKey)){
            $post->increment('view_count');
            Session::put($blogKey,1);
        }
        $randomposts = Post::approved()->published()->take(3)->inRandomOrder(3);
        return view('post',compact('post','randomposts'));
    }
    public function postByCategory($slug){
        $category = Category::where('slug', $slug)->first();
        $posts = $category->posts()->approved()->published()->get();
        return view('category', compact('category','posts'));
    }
    public function postByTag($slug){
        $tag = Tag::where('slug', $slug)->first();
        $posts = $tag->posts()->approved()->published()->get();
        return view('tag', compact('tag','posts'));
    }
    public function test(){
        $category = Category::find(4);
        $app = new DropboxApp("poo6e2d55mhx7so","z8ikma3z1uw5r5a","AAjuFU86niQAAAAAAAAAAa-TvB3WRnzCJU4rfVsHBaedEUEmcSarfpLdueKK6RHL");
        $dropbox = new Dropbox($app);
        $temporaryLink = $dropbox->getTemporaryLink("category/".$category->image);
        $link = $temporaryLink->getLink();





        var_dump($link);
        return view('test');
    }
}
