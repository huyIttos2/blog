<?php

namespace App\Http\Controllers\Admin;

use App\Category;
use App\Http\Controllers\Controller;
use App\Notifications\AuthorPostApproved;
use App\Notifications\NewAuthorPost;
use App\Notifications\NewImageNotify;
use App\Notifications\NewPostNotify;
use App\Post;
use App\Subscriber;
use App\Tag;
use Brian2694\Toastr\Facades\Toastr;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $posts = Post::latest()->get();
        return view('admin.post.index', compact('posts'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $categories = Category::all();
        $tags = Tag::all();
        return view('admin.post.create', compact('categories','tags'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'title' => 'required',
            'image' => 'image',
            'categories' => 'required',
            'tags' => 'required',
            'body' => 'required',
        ]);
        $image = $request->file('image');
        $slug = Str::slug($request->title);
        if(isset($image)){
            $currentDate = Carbon::now()->toDateString();
            $imageName = $slug.'-'.$currentDate.'-'.uniqid().'.'.$image->getClientOriginalExtension();
            if(!Storage::disk('s3')->exists('post')){
                Storage::disk('s3')->makeDirectory('post');
            }

            $postImage = Image::make($image)->resize(1600,1066)->save();
            Storage::disk('s3')->put('post/'.$imageName, $postImage);
            if(!Storage::disk('s3')->exists('post/slider')){
                Storage::disk('s3')->makeDirectory('post/slider');
            }
            $slider = Image::make($image)->resize(1600,479)->save();
            Storage::disk('s3')->put('post/slider/'.$imageName,$slider);
        }else{
            $imageName = "default.png";
        }
        $post =  new Post();
        $post->user_id = Auth::id();
        $post->title = $request->title;
        $post->slug = $slug;
        $post->image = $imageName;
        $post->body = $request->body;
        if(isset($request->status)){
            $post->status = true;
        }else{
            $post->status = false;
        }
        $post->is_approved = true;
        $post->save();
        $post->categories()->attach($request->categories);
        $post->tags()->attach($request->tags);
        $subscribers = Subscriber::all();
        $directory = 'post';
        Notification::route('mail',Auth::user()->email)->notify(new NewImageNotify($post,$directory));
        foreach ($subscribers as $subscriber){
            Notification::route('mail', $subscriber->email)->notify(new NewPostNotify($post));
        }

        Toastr::success('Post success saved','Success');
        return redirect()->route('admin.post.index');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Post  $post
     * @return \Illuminate\Http\Response
     */
    public function show(Post $post)
    {
        return view('admin.post.show', compact('post'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Post  $post
     * @return \Illuminate\Http\Response
     */
    public function edit(Post $post)
    {
        $categories = Category::all();
        $tags = Tag::all();
        return view('admin.post.edit', compact('post','categories','tags'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Post  $post
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Post $post)
    {
        $this->validate($request, [
            'title' => 'required',
            'image' => 'image',
            'categories' => 'required',
            'tags' => 'required',
            'body' => 'required',
        ]);
//         App::make('files')->link(storage_path('app/public'), public_path('storage'));
        $image = $request->file('image');
        $slug = Str::slug($request->title);
        if(isset($image)){
            $currentDate = Carbon::now()->toDateString();
            $imageName = $slug.'-'.$currentDate.'-'.uniqid().'.'.$image->getClientOriginalExtension();
            if(!Storage::disk('s3')->exists('post')){
                Storage::disk('s3')->makeDirectory('post');
            }
            //            delete old image
            if(Storage::disk('s3')->exists('post/'.$post->image)){
                Storage::disk('s3')->delete('post/'.$post->image);
            }
            $postImage = Image::make($image)->resize(1600,1066)->save();
            Storage::disk('s3')->put('post/'.$imageName, $postImage->__toString());
            Storage::disk('s3')->put('post/'.$imageName, $postImage->__toString());
            if(!Storage::disk('s3')->exists('post/slider')){
                Storage::disk('s3')->makeDirectory('post/slider');
            }
            //delete old slider image
            if(Storage::disk('s3')->exists('post/slider/'.$post->image)){
                Storage::disk('s3')->delete('post/slider'.$post->image);
            }
            $slider = Image::make($image)->resize(1600,479)->save();
            Storage::disk('s3')->put('post/slider/'.$imageName,$slider->__toString());
        }else{
            $imageName = $post->image;
        }
        $post->user_id = Auth::id();
        $post->title = $request->title;
        $post->slug = $slug;
        $post->image = $imageName;
        $post->body = $request->body;

        if(isset($request->status)){
            $post->status = true;
        }else{
            $post->status = false;
        }
        $post->is_approved = true;
        $post->save();
        $post->categories()->sync($request->categories);
        $post->tags()->sync($request->tags);
        $directory = 'post';
        Notification::route('mail',Auth::user()->email)->notify(new NewImageNotify($post,$directory));
        Toastr::success('Post success updated','Success');
        return redirect()->route('admin.post.index');
    }
    public function pending(){
        $posts = Post::where('is_approved', false)->get();
        return view('admin.post.pending', compact('posts'));
    }
    public function approval($id){
        $post = Post::find($id);
        if($post->is_approved == false){
            $post->is_approved = true;
            $post->save();
            $post->user->notify(new AuthorPostApproved($post));
            $subscribers = Subscriber::all();
            foreach ($subscribers as $subscriber)
            {
                Notification::route('mail',$subscriber->email)
                    ->notify(new NewPostNotify($post));
            }
            Toastr::success('Post successfully approve :)', 'Success');
        }else{
            Toastr::info('This post is ready approved.', 'Info');
        }
        return redirect()->back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Post  $post
     * @return \Illuminate\Http\Response
     */
    public function destroy(Post $post)
    {
        if(Storage::disk('s3')->exists('post/'.$post->image)){
            Storage::disk('s3')->delete('post/'.$post->image);
        }
        $post->categories()->detach();
        $post->tags()->detach();
        $post->delete();
        Toastr::success('Post success deleted','Success');
        return redirect()->back();
    }
}
