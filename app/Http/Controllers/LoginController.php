<?php

namespace App\Http\Controllers;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class LoginController extends Controller
{
    function viewLoginAndRegister()
    {
        return view('loginAndRes');
    }
    function login(Request $request)
    {
        $username = $request->get('username');
        $password = $request->get('password');
        if(Auth::attempt(['username' => $username, 'password' =>$password ])){
            $user = Auth::user();
            switch ($user->role) {
                case 'admin';
                    return redirect()->route('adminDashboard')->with('success','Đăng nhập thành công');
                    break;
                case 'customer';
                    if ($user->isDeleted == 1){
                        return redirect()->route('login')->with('error','Tài khoản của bạn đã bị khóa vì hành vi mua bán không trong sạch');
                    }else{
                        return redirect()->route('customerPage')->with('success','Đăng nhập thành công');
                    }
                    break;
            }
        }
        return redirect()->route('login')->with('error', 'Sai tên tài khoản hoặc mật khẩu, vui lòng nhập lại.');
    }
    public function register(Request $request)
    {
        try {
            $request->validate([
                'username' => 'required|string|unique:users,username',
                'email'    => 'required|email|unique:users,email',
                'password' => 'required|string|min:6|confirmed',
            ]);

            $user = User::create([
                'username' => $request->input('username'),
                'email'    => $request->input('email'),
                'password' => Hash::make($request->input('password')),
                'role'     => 'customer'
            ]);

            Auth::login($user);

            return redirect()->route('customerPage')->with('success', 'Tạo tài khoản thành công');

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Trường hợp trùng username/email
            $errors = $e->validator->errors();
            if ($errors->has('username')) {
                return redirect()->route('login')->with('error', 'Username đã tồn tại');
            }
            if ($errors->has('email')) {
                return redirect()->route('login')->with('error', 'Email đã tồn tại');
            }

            return redirect()->route('login')->with('error', 'Dữ liệu không hợp lệ');

        } catch (\Exception $e) {
            Log::error('Đăng ký thất bại: ' . $e->getMessage());
            return redirect()->route('login')->with('error', 'Đã xảy ra lỗi: ' . $e->getMessage());
        }

    }
    function logout()
    {
        Auth::logout();
        return view('loginAndRes');
    }

}
