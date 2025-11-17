<?php

namespace App\Http\Controllers;

use App\Models\DiscountProgram;
use App\Models\ProductDetail;
use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\CartDetail;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;

class CartController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $cart = Cart::firstOrCreate(['userID' => $user->id]);
        $programs = DiscountProgram::getActivePrograms()->get();

        $cartDetails = $cart->cartDetails()
            ->with([
                'product.firstImage',
                'productDetail.size',
                'productDetail.color',
            ])
            ->get();

        $subtotal = 0;

        foreach ($cartDetails as $detail) {
            $price = $detail->productDetail->product->productSellPrice ?? 0;
            $quantity = $detail->quantity;
            $subtotal += $price * $quantity;
        }

        if (!$programs->isEmpty()) {
            foreach ($programs as $program) {
                $discountAmount = $program->calculateDiscount($subtotal);
                $finalPrice = $subtotal - $discountAmount;
            }
        } else {
            $discountAmount = 0;
            $finalPrice = $subtotal;
        }

        $total = $finalPrice ;

        return view('UserPage.Cart', compact('cartDetails','subtotal','total','programs','discountAmount'));
    }


    public function addToCart(Request $request)
    {
        $request->validate([
            'productID' => 'required|exists:products,productID',
            'quantity' => 'required|integer|min:1',
        ]);

        $size = $request->size;
        $color = $request->color;
        $sizeID = \App\Models\Size::where('sizeName', $size)->value('sizeId');
        $colorID = \App\Models\Color::where('colorName', $color)->value('colorId');
        $productDetailId = ProductDetail::where('prdID',$request->productID)->where('sizeId',$sizeID)->where('colorId',$colorID)->value('id');
        $user = Auth::user();
        $cart = Cart::firstOrCreate(['userID' => $user->id]);

        $cartDetail = CartDetail::where('cartID', $cart->cartID)
            ->where('productDetailID', $productDetailId)
            ->first();

        if ($cartDetail) {
            $cartDetail->quantity += $request->quantity;
            $cartDetail->save();
        } else {
            CartDetail::create([
                'cartID' => $cart->cartID,
                'productDetailID' => $productDetailId,
                'quantity' => $request->quantity,
            ]);
        }
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'cart_detail_id' => $cartDetail->id,
                'quantity' => $cartDetail->quantity
            ]);
        }
        return redirect()->route('cart.index')->with('success', 'Đã thêm vào giỏ hàng');
    }

    public function removeItem($id)
    {
        $item = CartDetail::findOrFail($id);
        $item->delete();

        return redirect()->back()->with('success', 'Đã xóa sản phẩm khỏi giỏ hàng');
    }

    public function getDiscount($id)
    {
        $program = DiscountProgram::find($id);

        if (!$program || !$program->isActive()) {
            return response()->json(['error' => 'Chương trình không hợp lệ hoặc đã hết hạn'], 400);
        }

        $originalPrice = request()->query('total');

        $discount = $program->calculateDiscount($originalPrice);

        return response()->json([
            'discount' => $discount
        ]);
    }



}
