<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ProfileController extends Controller
{
    public function edit()
    {
        $user = Auth::user();
        return view('UserPage.Profile', compact('user'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();

        $validator = Validator::make($request->all(), [
            'name'    => 'required|string|max:255',
            'email'   => 'required|email|unique:users,email,' . $user->id,
            'phone'   => 'nullable|string|max:10',
            'city' => 'nullable|string|max:100',
            'district' => 'nullable|string|max:100',
            'ward' => 'nullable|string|max:100',
            'street_address' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $user->update([
            'name'    => $request->name,
            'email'   => $request->email,
            'phone'   => $request->phone,
            'city' => $request->city,
            'district' => $request->district,
            'ward' => $request->ward,
            'street_address' => $request->address,
        ]);

        return redirect()->route('profile.edit')->with('success', 'Cập nhật thông tin thành công!');
    }

    public function updatePassword(Request $request)
    {
        $user = Auth::user();
        if (Hash::check($request->currentPassword, $user->password)) {
            $validator = Validator::make($request->all(), [
                'password' => 'string',
            ]);

            if ($validator->fails()) {
                return redirect()->back()->withErrors($validator)->withInput();
            }

            $user->update([
                'password' => $request->password,
            ]);

            return redirect()->route('profile.edit')->with('success', 'Cập nhật thông tin thành công!');
        } else {
            return redirect()->route('profile.edit')->with('error', 'Mật khẩu hiện tại của bạn nhập chưa chính xác!');
        }


    }
}
