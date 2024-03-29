<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\File;
use App\Models\Setting;
use App\Models\Post;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

class AuthorController extends Controller
{
    //Log out function, redirectes user to the login page, alongside logging them out.
    public function logout(){
        Auth::guard('web')->logout();
        return redirect()->route('author.login');
    }

    //User can change their profile picture, their profile picture is also uploaded to a personal directory stored within the site.
    public function changeProfilePicture(Request $request){
        $user = User::find(auth('web')->id());
        $path = 'back/dist/img/authors/';
        $file = $request->file('file');
        $old_picture = $user->getAttributes()['picture'];
        $file_path = $path.$old_picture;
        $new_picture_name = "AIMG".$user->id.time().rand(1,100000).'.jpg';

        if($old_picture != null && File::exists(public_path($file_path))){
            File::delete(public_path($file_path));
        }
        $upload = $file->move(public_path($path), $new_picture_name);
        if($upload){
            $user->update([
                'picture'=>$new_picture_name
            ]);
            return response()->json(['status'=>1, 'msg'=>'Your Profile Picture has Successfully Updated!']);
        }else{
            return response()->json(['status'=>0, 'Something Went Wrong!']);
        }
    }

    //Create posts - Validation on what is/isn't allowed. Alongside implementing thumbnails and picturers
    public function createPost(Request $request){
        $request->validate([
            'post_title'=>'required|unique:posts,post_title',
            'post_content'=>'required',
            'post_category'=>'required|exists:sub_categories,id',
            'featured_image'=>'required|mimes:jpeg,jpg,png|max:1024',
        ]);

        if($request->hasFile('featured_image')){
            $path = "images/post_images/";
            $file = $request->file('featured_image');
            $filename = $file->getClientOriginalName();
            $new_filename = time().'_'.$filename;

            $upload = Storage::disk('public')->put($path.$new_filename, (string) file_get_contents($file));

            $post_thumbnails_path = $path.'thumbnails';
            if( !Storage::disk('public')->exists($post_thumbnails_path) ){
                Storage::disk('public')->makeDirectory($post_thumbnails_path, 0755, true, true);
            }

            //Creates a Square Image - Extension of the Image Intervention Plugin
            Image::make( storage_path('app/public/'.$path.$new_filename) )
                  ->fit(200, 200)
                  ->save( storage_path('app/public/'.$path.'thumbnails/'.'thumb_'.$new_filename) );

            //Creates a Resized Image - Extension of the Image Intervention Plugin
            Image::make( storage_path('app/public/'.$path.$new_filename) )
                  ->fit(500, 350)
                  ->save( storage_path('app/public/'.$path.'thumbnails/'.'resized_'.$new_filename) );
                  
            if( $upload ){
                 $post = new Post();
                 $post->author_id = auth()->id();
                 $post->category_id = $request->post_category;
                 $post->post_title = $request->post_title;
                 $post->post_content = $request->post_content;
                 $post->featured_image = $new_filename;
                 $saved = $post->save();

                 if($saved){
                    return response()->json(['code'=>1, 'msg'=>'New post has been successfully created.']);
                 }else{
                    return response()->json(['code'=>3, 'msg'=>'Something went wrong ins saving post data.']);
                 }
            }else{
                return response()->json(['code'=>3,'msg'=>'Something went wrong for uploading featured image.']);
            }
        }
    }


    //Takes variable request, if no post_id in request, provide 404 page. 
    //Else, find the post field with equal post_id, open edit_post.blade.php. 
    public function editPost(Request $request){
        if( !request()->post_id ){
            return abort(404);
        }else{
            $post = Post::find(request()->post_id);
            $data = [
                'post'=>$post,
                'pageTitle'=>'Edit Post',
            ];
            return view('back.pages.edit_post',$data);
        }
    }


    //deleting all the information from an old post, and creating all the new information for a new post.
    public function updatePost(Request $request){
        if( $request->hasFile('featured_image') ){

            $request->validate([
                'post_title'=>'required|unique:posts,post_title,'.$request->post_id,
                'post_content'=>'required',
                'post_category'=>'required|exists:sub_categories,id',
                'featured_image'=>'mimes:jpeg,jpg,png|max:1024',
            ]);

            $path = "images/post_images/";
            $file = $request->file('featured_image');
            $filename = $file->getClientOriginalName();
            $new_filename = time().'_'.$filename;

            $upload = Storage::disk('public')->put($path.$new_filename, (string) file_get_contents($file));

            $post_thumbnails_path = $path.'thumbnails';
            if( !Storage::disk('public')->exists($post_thumbnails_path) ){
                Storage::disk('public')->makeDirectory($post_thumbnails_path, 0755, true, true);
            }

            Image::make( storage_path('app/public/'.$path.$new_filename) )
                  ->fit(200, 200)
                  ->save( storage_path('app/public/'.$path.'thumbnails/'.'thumb_'.$new_filename) );

            Image::make( storage_path('app/public/'.$path.$new_filename) )
                  ->fit(500, 350)
                  ->save( storage_path('app/public/'.$path.'thumbnails/'.'resized_'.$new_filename) );
        //Deleting old posts from user directory, and adding their new post information.
        if( $upload ){
           $old_post_image = Post::find($request->post_id)->featured_image;

           if( $old_post_image != null && Storage::disk('public')->exists($path.$old_post_image) ){
              Storage::disk('public')->delete($path.$old_post_image);

              if( Storage::disk('public')->exists($path.'thumbnails/resized_'.$old_post_image) ){
                 Storage::disk('public')->delete($path.'thumbnails/resized_'.$old_post_image);
              }

              if( Storage::disk('public')->exists($path.'thumbnails/thumb_'.$old_post_image) ){
                 Storage::disk('public')->delete($path.'thumbnails/thumb_'.$old_post_image);
              }
           }

           $post = Post::find($request->post_id);
           $post->category_id = $request->post_category;
           $post->post_title = $request->post_title;
           $post->post_slug = null;
           $post->post_content = $request->post_content;
           $post->featured_image = $new_filename;
           $saved = $post->save();

           if( $saved ){
            return response()->json(['code'=>1,'msg'=>'Post has been successfully updated.']);
           }else{
            return response()->json(['code'=>3,'msg'=>'Something went wrong for updating post.']);
           }

        }else{
            return response()->json(['code'=>3,'msg'=>'Error in uploading new feaured image.']);
        }

        }else{
            $request->validate([
                'post_title'=>'required|unique:posts,post_title,'.$request->post_id,
                'post_content'=>'required',
                'post_category'=>'required|exists:sub_categories,id'
            ]);

            $post = Post::find($request->post_id);
            $post->category_id = $request->post_category;
            $post->post_slug = null;
            $post->post_content = $request->post_content;
            $post->post_title = $request->post_title;
            $saved = $post->save();

            if($saved){
                return response()->json(['code'=>1,'msg'=>'Post has been successfully updated.']);
            }else{
                return response()->json(['code'=>3,'msg'=>'Something went wrong for updating post.']);
            }
        }
    }
}
