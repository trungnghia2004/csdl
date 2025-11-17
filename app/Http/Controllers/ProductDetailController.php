<?php

namespace App\Http\Controllers;
use App\Imports\ProductDetailImport;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\ProductDetail;
use App\Models\Product;
use App\Models\Size;
use App\Models\Color;
use Illuminate\Http\Request;

class ProductDetailController extends Controller
{
    public function index(Product $product)
    {
        $infoProduct = Product::with(['images','category'])
            ->where('isDeleted', 0)
            ->where('productID', $product->productID)
            ->whereHas('category', function ($q) {
                $q->where('isDeleted', 0);
            })->firstOrFail();
        $query = ProductDetail::with(['product', 'size', 'color'])
            ->where('isDeleted', false)
            ->where('prdID', $product->productID);
        $productDetails = $query->paginate(10);
        $sizes = Size::where('isDeleted', false)->get();
        $colors = Color::where('isDeleted', false)->get();


        return view('AdminPage.ProductDetails', compact('productDetails','sizes','colors','infoProduct'));

    }

    public function store(Request $request, Product $product)
    {
        // Nếu upload file Excel
        if ($request->hasFile('excel_file')) {
            $request->validate([
                'excel_file' => 'required|mimes:xlsx,xls'
            ]);

            Excel::import(new ProductDetailImport($product->productID), $request->file('excel_file'));

            return redirect()->back()->with('success', 'Nhập Excel thành công!');
        }

        // Nếu nhập thủ công
        $request->validate([
            'sizeId' => 'required',
            'colorId' => 'required',
            'productQuantity' => 'required|integer|min:1',
        ]);

        $existingDetail = ProductDetail::where('prdID', $product->productID)
            ->where('sizeId', $request->sizeId)
            ->where('colorId', $request->colorId)
            ->where('isDeleted', false)
            ->first();

        if ($existingDetail) {
            $existingDetail->productQuantity += $request->productQuantity;
            $existingDetail->save();
        } else {
            ProductDetail::create([
                'prdID' => $product->productID,
                'sizeId' => $request->sizeId,
                'colorId' => $request->colorId,
                'productQuantity' => $request->productQuantity,
                'isDeleted' => false,
            ]);
        }

        return redirect()->back()->with('success', 'Thêm chi tiết sản phẩm thành công.');
    }


    public function update(Request $request, $id)
    {
        $request->validate([
            'prdID' => 'required',
            'sizeId' => 'required',
            'colorId' => 'required',
            'productQuantity' => 'required',
        ]);

        $productDetail = ProductDetail::findOrFail($id);
        $productDetail->update([
            'prdID' => $request->prdID,
            'sizeId' => $request->sizeId,
            'colorId' => $request->colorId,
            'productQuantity' => $request->productQuantity,
        ]);

        return redirect()->back()->with('success', 'Cập nhật thành công.');
    }

    public function destroy($id)
    {
        $productDetail = ProductDetail::findOrFail($id);
        $productDetail->update(['isDeleted' => true]);
        return redirect()->back()->with('success', 'Đã xoá (ẩn) chi tiết sản phẩm.');
    }
}
