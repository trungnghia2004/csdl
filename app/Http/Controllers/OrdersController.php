<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartDetail;
use App\Models\DiscountProgram;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductDetail;
use App\Models\User;
use App\Notifications\NewOrderNotification;
use App\Notifications\OrderConfirmationForCustomer;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class OrdersController extends Controller
{
    public function index(Request $request)
    {
        $cartData = json_decode($request->input('cart_data'), true);
        $discountCode = $request->input('discount_code');

        $cartDetails = [];

        foreach ($cartData as $item) {
            $cartDetail = CartDetail::with(['product', 'product.firstImage','productDetail.size', 'productDetail.color'])
                ->find($item['id']);
            if ($cartDetail) {
                $cartDetail->quantity = $item['quantity'];
                $cartDetails[] = $cartDetail;
            }
        }

        $subtotal = 0;
        foreach ($cartDetails as $detail) {
            $price = $detail->productDetail?->product->productSellPrice ?? 0;
            $quantity = $detail->quantity;
            $subtotal += $price * $quantity;
        }

        $discountValue = 0;


        if (!empty($discountCode)) {
            $program = DiscountProgram::find($discountCode);
            $discountValue = $program->calculateDiscount($subtotal);
            $total = $subtotal - $discountValue;
        } else {
            $discountValue = 0;
            $total = $subtotal;
        }

        $total = $subtotal - $discountValue;

        if ($total < 0) {
            $total = 0;
        }

        $payments = Payment::all();

        return view('UserPage.Checkout', compact(
            'cartDetails',
            'total',
            'subtotal',
            'discountValue',
            'discountCode',
            'payments'
        ));
    }


    public function storeFromCustomer(Request $request)
    {
        $discountCode = $request->discountCode;
        $discountProgram = null;

        if (!empty($discountCode)) {
            $discountProgram = DiscountProgram::where('id', $discountCode)->first();
        }
        $order = new Order();
        $order->cusID = auth()->id();
        $order->adminID = null; // hoặc từ $request
        $order->orderPhoneNumber = $request->phone;
        $order->shipping_street = $request->street_address;
        $order->shipping_city = $request->city;
        $order->shipping_district = $request->district;
        $order->shipping_ward = $request->ward;
        $order->payID = $request->payment;
        $order->staID = 1;
        $order->totalPrice = $request->total ?? 0;
        if ($discountProgram) {
            $order->discount_program_id = $discountProgram->id;
        }
        $order->save();

        foreach ($request->productDetails as $productDetail) {

            OrderDetail::create([
                'orderID' => $order->orderID,
                'productDetailID' => $productDetail['productDetailID'],
                'orderQuantity' => $productDetail['quantity'],
                'unitPrice' => $productDetail['unitPrice'],
            ]);
            $product = ProductDetail::find($productDetail['productDetailID']);
            if ($product) {
                $product->productQuantity -= $productDetail['quantity'];
                if ($product->productQuantity < 0) {
                    $product->productQuantity = 0;
                }
                $product->save();
            }
        }


        $cartID = Cart::where('userID', auth()->id())->value('cartID');
        CartDetail::where('cartID', $cartID)->delete();

        $admin = User::where('role', 'admin')->first();
        Notification::route('mail', $admin->email)->notify(new NewOrderNotification($order));

        $order->customer->notify(new OrderConfirmationForCustomer($order));
        // if ($request->payment == 2) {
        //     return $this->redirectToVnpay($order->totalPrice, $order->orderID);
        // }

        return redirect()->route('customerPage')->with('success', 'Đơn hàng đã được tạo thành công!');
    }



    public function showOrders(Request $request)
    {
        $keyword = $request->input('keyword');
        $status = $request->input('statusID');

        $query = Order::with([
            'customer',
            'status',
            'orderDetails.productDetail.product.images'
        ])
            ->where('cusID', auth()->id())
            ->orderBy('created_at', 'desc');

        if (!empty($status)) {
            $query->whereHas('status', function ($q) use ($status) {
                $q->where('staID', $status);
            });
        }

        $orders = $query->paginate(5)->appends($request->query());

        $statusCounts = Order::where('cusID', auth()->id())
            ->selectRaw('staID, COUNT(*) as total')
            ->groupBy('staID')
            ->pluck('total', 'staID')
            ->toArray();

        $totalOrders = Order::where('cusID', auth()->id())->count();

        $results = [];
        foreach ($orders as $order) {
            $productImages = [];
            $product = null;

            foreach ($order->orderDetails as $detail) {
                $product = $detail->productDetail->product ?? null;
                $image = $product->images->first()->imageLink ?? null;
                if ($image) $productImages[] = $image;
            }

            $results[] = [
                'orderID' => $order->orderID,
                'payStatus' => $order->isPayment,
                'orderCode' => '#ORD-' . $order->created_at->format('Y-m-d') . '-' . $order->orderID,
                'orderDate' => $order->created_at->format('d/m/Y \l\ú\c H:i'),
                'expectedDelivery' => Carbon::parse($order->created_at)->addDays(3)->format('d/m/Y') . ' - ' . Carbon::parse($order->created_at)->addDays(5)->format('d/m/Y'),
                'status' => $order->status->statusValue ?? 'Không rõ',
                'statusClass' => $this->getStatusClass($order->status->statusValue ?? ''),
                'totalPrice' => number_format($order->totalPrice, 0, ',', '.') . 'đ',
                'productCount' => $order->orderDetails->sum('orderQuantity'),
                'productImages' => $productImages,
                'product' => $product,
            ];
        }

        return view('UserPage.order-list', compact('results', 'orders', 'keyword', 'statusCounts', 'totalOrders'));
    }


    private function getStatusClass($status)
    {
        return match ($status) {
            'Đang chờ duyệt'   => 'status-pending',
            'Đã duyệt'         => 'status-approved',
            'Đang giao hàng'   => 'status-shipping',
            'Đã giao hàng'          => 'status-delivered',
            'Đã hủy'           => 'status-cancelled',
            default            => 'status-default',
        };

    }
    public function showDetails($orderID)
    {
        $order = Order::with([
            'customer',
            'admin',
            'status',
            'payment',
            'orderDetails.productDetail.product',
            'orderDetails.productDetail.size',
            'orderDetails.productDetail.color',
        ])->findOrFail($orderID);


        return view('UserPage.orders-details', compact('order'));
    }
    public function delivered($orderID)
    {
        $cusID = auth()->id();
        $order = Order::where('orderID', $orderID)->where('cusID', $cusID)->first();

        $order = Order::find($orderID);
        if ($order) {
            $order->isPayment = true;
            $order->save();
        }
        $order->staID = 4;
        $order->save();

        return redirect()->route('orders.showOrders')->with('success', "Xác nhận thành công. Cảm ơn quý khách.");
    }
    public function cancel($orderID)
    {
        $cusID = auth()->id();

        $order = Order::with('orderDetails')->where('orderID', $orderID)->where('cusID', $cusID)->first();

        if (!$order) {
            return back()->with('error', 'Không tìm thấy đơn hàng hoặc bạn không có quyền hủy đơn này.');
        }

        if (!in_array($order->staID, [1, 2])) {
            return back()->with('error', 'Chỉ có thể hủy đơn hàng đang chờ xử lý.');
        }

        foreach ($order->orderDetails as $detail) {
            $productDetail = ProductDetail::find($detail->productDetailID);
            if ($productDetail) {
                $productDetail->productQuantity += $detail->orderQuantity;
                $productDetail->save();
            }
        }

        $order->staID = 5;
        $order->save();

        return back()->with('success', "Đơn hàng của quý khách đã được hủy và sản phẩm đã hoàn lại kho.");
    }


}
