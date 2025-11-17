<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class CustomerAccountController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');

        $query = User::where('role', 'customer');

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%");
            });
        }

        $customerAccounts = $query->orderBy('id', 'desc')->paginate(10)->appends(['search' => $search]);

        return view('AdminPage.UserAccount', compact('customerAccounts', 'search'));
    }

    public function update(User $customer)
    {
        $customer->update(['isDeleted' => 0]);
        return redirect()->route('customer.index')->with('success', 'Gỡ cấm người dùng thành công.');
    }

    public function destroy(User $customer)
    {
        $customer->update(['isDeleted' => 1]);
        return redirect()->route('customer.index')->with('success', 'Cấm người dùng thành công.');
    }



}
