<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Support\Facades\Storage;

class ProductImageController extends Controller
{
    public function index(Product $product){
        $productImage = ProductImage::where('prdID', $product->productID)->get();
        return view('AdminPage.ProductImage', compact('productImage', 'product'));
    }

    // Thêm nhiều ảnh
    public function upload(Request $request, Product $product)
    {
        if ($request->hasFile('productImages')) {
            foreach ($request->file('productImages') as $image) {
                $path = $image->store('product_images', 'public'); // lưu vào storage/app/public/product_images

                ProductImage::create([
                    'prdID' => $product->productID,
                    'imageLink' => $path,
                ]);
            }
        }

        return redirect()->route('products.index')->with('success', 'Thêm hình ảnh thành công.');
    }

    // Xoá ảnh
    public function destroy(ProductImage $image)
    {
        // Xoá file ảnh trong storage
        if (Storage::disk('public')->exists($image->imageLink)) {
            Storage::disk('public')->delete($image->imageLink);
        }

        $image->delete();
        return back()->with('success', 'Xóa hình ảnh thành công.');
    }

    // Cập nhật ảnh (thay ảnh mới)
    public function update(Request $request, ProductImage $image)
    {
        $request->validate([
            'newImage' => 'required|image|max:2048',
        ]);

        if (Storage::disk('public')->exists($image->imageLink)) {
            Storage::disk('public')->delete($image->imageLink);
        }

        $newPath = $request->file('newImage')->store('product_images', 'public');
        $image->update(['imageLink' => $newPath]);

        return back()->with('success', 'Image updated successfully.');
    }
}
