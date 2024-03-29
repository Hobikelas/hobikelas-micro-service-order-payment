<?php

namespace App\Http\Controllers;


use App\Order;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    //

    public function index(Request $request)
    {
        $userId = $request->input('user_id');

        $orders = Order::query();

        $orders->when($userId, function ($query) use ($userId){
            return $query->where('user_id', '=', $userId);
        });

        return response()->json([
            'status' => 'success',
            'data' => $orders->get()
        ]);
    }

    public function create(Request $request)
    {
        $user = $request->input('user');
        $course = $request->input('course');

        $order = Order::create([
            'user_id' => $user['id'],
            'course_id' => $course['id']
        ]);

        $transactionDetails = [
            'order_id' => $order->id.'-'.Str::random(5),
            'gross_amount' => $course['price'] 
        ];

        $itemDetails = [
            [
                'id' => $course['id'],
                'price' => $course['price'],
                'quantity' => 1,
                'name' => $course['name'],
                'brand' => 'Hobikelas',
                'category' => 'Online Course'
            ]
        ];

        $customerDetails = [
            'first_name' => $user['name'],
            'email' => $user['email']
        ];

        $midtransParams = [
            'transaction_details' => $transactionDetails,
            'item_details' => $itemDetails,
            "customer_details" => $customerDetails
        ];


        $midtransSnapUrl = $this->getMidtransSnapUrl($midtransParams);

        $order->metadata = [
            'course_id' => $course['id'],
            'course_price' => $course['price'],
            'course_name' => $course['name'],
            'course_thumbnail' => $course['thumbnail'],
            'course_level' => $course['level']
        ];
        $order->snap_url = $midtransSnapUrl;
        $order->save();
        // return $midtransSnapUrl;

        // return response()->json($order);

        return response()->json([
            'status' => 'succes',
            'data' => $order
        ]);
    }

    private function getMidtransSnapUrl($params)
    {
        // Set your Merchant Server Key
        \Midtrans\Config::$serverKey = env('MIDTRANS_SERVER_KEY');
        // Set to Development/Sandbox Environment (default). Set to true for Production Environment (accept real transaction).
        \Midtrans\Config::$isProduction = (bool) env('MIDTRANS_PRODUCTION');
        // Set sanitization on (default)
        // \Midtrans\Config::$isSanitized = true;
        // Set 3DS transaction for credit card to true
        \Midtrans\Config::$is3ds = (bool) env('MIDTRANS_3DS');

        $snapUrl = \Midtrans\Snap::createTransaction($params)->redirect_url;

        return $snapUrl;
    }
}
