<?php

namespace App\Http\Controllers;

use App\Models\CommentAndRate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentAndRateController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'productID' => 'required|exists:products,productID',
            'contentComment' => 'required|string',
            'rate' => 'required|integer|min:1|max:5',
        ]);

        $user = Auth::user();
        if ($user->role !== 'customer') {
            return response()->json(['message' => 'Chỉ khách hàng mới được bình luận.'], 403);
        }

        $comment = CommentAndRate::create([
            'cusID' => $user->id,
            'productID' => $request->productID,
            'contentComment' => $request->contentComment,
            'rate' => $request->rate,
        ]);

        return back()->with('success', 'Bình luận đã được tạo.');
    }


    public function update(Request $request, $id)
    {
        $comment = CommentAndRate::findOrFail($id);

        if (Auth::id() !== $comment->cusID) {
            return back()->withErrors(['message' => 'Bạn không có quyền sửa bình luận này.']);
        }

        $request->validate([
            'contentComment' => 'required|string|max:500',
            'rate' => 'required|integer|min:1|max:5',
        ]);

        $comment->update([
            'contentComment' => $request->contentComment,
            'rate' => $request->rate,
        ]);

        return back()->with('success', 'Bình luận đã được cập nhật.');
    }

    public function destroy($id)
    {
        $comment = CommentAndRate::findOrFail($id);

        if (Auth::id() !== $comment->cusID) {
            return back()->withErrors(['message' => 'Bạn không có quyền xoá bình luận này.']);
        }

        $comment->delete();

        return back()->with('success', 'Đã xoá bình luận.');
    }
}
