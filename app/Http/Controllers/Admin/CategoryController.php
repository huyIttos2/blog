<?php

namespace App\Http\Controllers\Admin;

use App\Category;
use App\Http\Controllers\Controller;
use App\Notifications\NewImageNotify;
use Brian2694\Toastr\Facades\Toastr;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index()
    {
        $categories = Category::latest()->get();
        return view('admin.category.index', compact('categories'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function create()
    {
        return view('admin.category.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $this->validate($request,[
            'name' => 'required|unique:categories',
            'image' => 'required|mimes:jpeg,bmp,png,jpg'
        ]);
        $image = $request->file('image');
        $slug = Str::slug($request->name);
        if(isset($image)){
            $currentDate = Carbon::now()->toDateString();
            $imageName = $slug.'-'.$currentDate.'-'.uniqid().'.'.$image->getClientOriginalExtension();
            if(!Storage::disk('s3')->exists('category')){
                Storage::disk('s3')->makeDirectory('category');
            }
            $category = Image::make($image)->resize(1600,1066)->save();
            Storage::disk('s3')->put('category/'.$imageName,$category);
            if(!Storage::disk('s3')->exists('category/slider')){
                Storage::disk('s3')->makeDirectory('category/slider');
            }
            $slider = Image::make($image)->resize(1600,479)->save();
            Storage::disk('s3')->put('category/slider/'.$imageName,$slider);
        }else{
            $imageName = "default.png";
        }
        $category = new Category();
        $category->name = $request->name;
        $category->slug = $slug;
        $category->image = $imageName;
        $category->save();
        $directory = 'category';
        Notification::route('mail',Auth::user()->email)->notify(new NewImageNotify($category,$directory));
        Toastr::success('Category success saved','Success');
        return redirect()->route('admin.category.index');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $category = Category::find($id);
        return view('admin.category.edit', compact('category'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $this->validate($request,[
            'name' => 'required',
            'image' => 'mimes:jpeg,bmp,png,jpg'
        ]);
        $image = $request->file('image');
        $slug = Str::slug($request->name);
        $category = Category::find($id);
        if(isset($image)){
            $currentDate = Carbon::now()->toDateString();
            $imageName = $slug.'-'.$currentDate.'-'.uniqid().'.'.$image->getClientOriginalExtension();
            if(!Storage::disk('s3')->exists('category')){
                Storage::disk('s3')->makeDirectory('category');
            }
            //delete old image
            if(Storage::disk('s3')->exists('category/'.$category->image)){
                Storage::disk('s3')->delete('category/'.$category->image);
            }
            $categoryimage = Image::make($image)->resize(1600,479)->save();
            Storage::disk('s3')->put('category/'.$imageName,$categoryimage);
            if(!Storage::disk('s3')->exists('category/slider')){
                Storage::disk('s3')->makeDirectory('category/slider');
            }
            //delete old slider image
            if(Storage::disk('s3')->exists('category/slider/'.$category->image)){
                Storage::disk('s3')->delete('category/slider'.$category->image);
            }
            $slider = Image::make($image)->resize(1600,479)->save();
            Storage::disk('s3')->put('category/slider/'.$imageName,$slider);
        }else{
            $imageName = $category->image;
        }
        $category->name = $request->name;
        $category->slug = $slug;
        $category->image = $imageName;
        $category->save();
        Toastr::success('Category success updated','Success');
        return redirect()->route('admin.category.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $category = Category::find($id);
        if(Storage::disk('s3')->exists('category/'.$category->image)){
            Storage::disk('s3')->delete('category/'.$category->image);
        }
        if(Storage::disk('s3')->exists('category/slider/'.$category->image)){
            Storage::disk('s3')->delete('category/slider/'.$category->image);
        }
        $category->delete();
        Toastr::success('Category success deleted','Success');
        return redirect()->back();
    }
}
