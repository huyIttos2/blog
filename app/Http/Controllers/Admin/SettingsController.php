<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Notifications\NewImageNotify;
use App\User;
use Brian2694\Toastr\Facades\Toastr;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class SettingsController extends Controller
{
    public function index(){
        return view('admin.settings');
    }

    public function updateProfile(Request $request){
        $this->validate($request,[
            'name' => 'required',
            'email' => 'required|email',
            'image' => 'required|image'
        ]);
        $image = $request->file('image');
        $slug =Str::slug($request->name);
        $user = User::findOrFail(Auth::id());
        if(isset($image)){
            $currenDate = Carbon::now()->toDateString();
            $imageName = $slug.'-'.$currenDate.'-'.uniqid().'-'.$image->getClientOriginalExtension();
            if(!Storage::disk('s3')->exists('profile')){
                Storage::disk('s3')->makeDirectory('profile');
            }
//            delete old Image
            if(Storage::disk('s3')->exists('profile/'.$user->image)){
                Storage::disk('s3')->delete('profile/'.$user->image);
            }
            $profile = Image::make($image)->resize(500,500)->save();
            Storage::disk('s3')->put('profile/'.$imageName,$profile);
        }else{
            $imageName = $user->image;
        }
        $user->name = $request->name;
        $user->email = $request->email;
        $user->image = $imageName;
        $user->about = $request->about;
        $user->save();
        $directory = 'profile';
        Notification::route('mail',Auth::user()->email)->notify(new NewImageNotify($profile,$directory));
        Toastr::success('Profile successfully updated :)','Success');
        return redirect()->back();
    }
    public function updatePassword(Request $request)
    {
        $this->validate($request,[
            'old_password' => 'required',
            'password' => 'required|confirmed',
        ]);

        $hashedPassword = Auth::user()->password;
        if (Hash::check($request->old_password,$hashedPassword))
        {
            if (!Hash::check($request->password,$hashedPassword))
            {
                $user = User::find(Auth::id());
                $user->password = Hash::make($request->password);
                $user->save();
                Toastr::success('Password Successfully Changed','Success');
                Auth::logout();
                return redirect()->back();
            } else {
                Toastr::error('New password cannot be the same as old password.','Error');
                return redirect()->back();
            }
        } else {
            Toastr::error('Current password not match.','Error');
            return redirect()->back();
        }

    }
}
