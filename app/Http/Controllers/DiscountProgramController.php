<?php

namespace App\Http\Controllers;

use App\Models\DiscountProgram;
use Illuminate\Http\Request;

class DiscountProgramController extends Controller
{
    public function index()
    {
        $programs = DiscountProgram::orderBy('created_at', 'desc')->get();
        return view('AdminPage.DiscountProgram', compact('programs'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'discount_type' => 'required|in:percent,fixed',
            'discount_value' => 'required|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        DiscountProgram::create($validated);

        return redirect()->route('discount_programs.index')->with('success', 'Thêm chương trình thành công!');
    }

    public function update(Request $request, DiscountProgram $discount_program)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'discount_type' => 'required|in:percent,fixed',
            'discount_value' => 'required|numeric|min:0',
            'max_discount' => 'nullable|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $discount_program->update($validated);

        return redirect()->route('discount_programs.index')->with('success', 'Cập nhật thành công!');
    }

    public function destroy(DiscountProgram $discount_program)
    {
        $discount_program->delete();
        return redirect()->route('discount_programs.index')->with('success', 'Đã xóa thành công!');
    }
}
