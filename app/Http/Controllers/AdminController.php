<?php

namespace App\Http\Controllers;

use App\Mail\verificationCodeMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

class AdminController extends Controller
{
    public function adminLogout(Request $request)
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/login');
    }
    public function adminLogin(Request $request)
    {
        $credentials = $request->only('email', 'password');
        if(Auth::attempt($credentials)){
            $user = Auth::user();
            $verificationCode = random_int('100000', '999999');
            session(['verificationCode' => $verificationCode, 'user_id' => $user->id]);
            Mail::to($user->email)->send(new verificationCodeMail($verificationCode));

            Auth::logout();
            return redirect()->route('login.verification.form')->with('status', 'Verification code sent to your email.');
        }

        return redirect()->back()->withErrors(['email' => 'Invalid credentials provided']);

    }

    public function showVerificationForm(){
        return view('auth.login-verification');
    }

    public function adminLoginVerification(Request $request){
        $request->validate(['code' => 'required|numeric|max_digits:6']);

        if($request->code ==  session('verificationCode')){
            Auth::loginUsingId(session('user_id'));
            session()->forget(['verificationCode', 'user_id']);     //after login the verfication code and the user id will be removed

            return redirect()->intended('dashboard');
        }

        return redirect()->back()->withErrors(['code' => 'Invalid verification code provided']);
    }
}
