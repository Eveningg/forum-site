<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Auth;
use App\Models\User;

class AuthorLoginForm extends Component
{

    public $email, $password;

    public function LoginHandler(){
        $this->validate([
            'email'=>'required|email|exists:users,email',
            'password'=>'required|min:5'
        ],[
            'email.required'=>'Enter Your Email Address',
            'email.email'=>'Invalid Email Address.',
            'email.exists'=>'This email is not registered!',
            'password.required'=>'A Password is Required.'
        ]);

        $creds = array('email' => $this->email, 'password'=>$this->password);

        if(Auth::guard('web')->attempt($creds) ){
            
            $checkUser = User::where('email', $this->email)->first();
            if($checkUser->blocked==1){
                Auth::guard('web')->logout();
                return redirect()->route('author.login')->with('fail', "Your Account has been Blocked.");
            }else{
                return redirect()->route('author.home');
            }
        }else{
            session()->flash('fail', 'Incorrect Email or Password!');
        }
    }

    public function render()
    {
        return view('livewire.author-login-form');
    }
}
