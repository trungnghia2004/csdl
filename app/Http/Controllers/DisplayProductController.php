<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\CommentAndRate;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\ProductDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DisplayProductController extends Controller
{
//    Hien thi san pham va danh muc o trang chu
    public function customerPage()
    {

        $products = Product::with(['firstImage', 'category'])
            ->where('isDeleted', 0)
            ->whereHas('category', function ($query) {
                $query->where('isDeleted', 0);
            })
            ->take(4)
            ->get();
        foreach ($products as $product) {
            $product->average_star = $product->comments->avg('rate');
            $product->quantityComment = $product->comments->count('rate');
        }
        $comments = CommentAndRate::with('user')
            ->orderByDesc('rate')
            ->take(3)
            ->latest()
            ->get();
        $categories = Category::where('isDeleted', false)->take(4)
            ->get();
        return view('UserPage.HomePage', compact('products', 'categories','comments'));
    }
//    Hien thi san pham
    public function index(Request $request)
    {
        $search = request()->get('search');

        $products = Product::with(['firstImage', 'category'])
            ->where('isDeleted', 0)
            ->where('productName', 'like', "%$search%")
            ->whereHas('category', function ($query) {
                $query->where('isDeleted', 0);
            })
            ->paginate(8);


        $categories = Category::where('isDeleted', false)->get();
        foreach ($products as $product) {
            $product->average_star = $product->comments->avg('rate');
            $product->quantityComment = $product->comments->count('rate');

        }
        return view('UserPage.Product', compact('products','categories','search'));
    }
    public function productDetails($productID)
    {
        $user = Auth::user();
        // Lay nhieu hinh anh
        $product = Product::with('images')->where('productID', $productID)->firstOrFail();
        $comments = CommentAndRate::with('user')
            ->where('productID', $productID)
            ->orderByDesc('created_at')
            ->get();

        $hasPurchased = OrderDetail::whereHas('order', function ($query) use ($user) {
            $query->where('cusID', $user->id);
        })
            ->whereHas('productDetail', function ($query) use ($productID) {
                $query->where('prdID', $productID);
            })
            ->exists();
        // Lay 1 hinh anh
        $userComment = CommentAndRate::where('productID', $productID)
            ->where('cusID', $user->id)
            ->first();

        $products = Product::with(['firstImage', 'category'])
            ->where('isDeleted', 0)
            ->whereHas('category', function ($query) {
                $query->where('isDeleted', 0);
            })
            ->take(4)
            ->get();
        $productDetails = ProductDetail::with(['product', 'size', 'color'])
            ->where('isDeleted', false)
            ->where('prdID', $product->productID)
            ->get();

        $product->average_star = $product->comments->avg('rate');
        $product->quantityComment = $product->comments->count('rate');

        return view('UserPage.ProductDetails', compact('product','products','userComment','productDetails','comments','hasPurchased'));
    }

}
