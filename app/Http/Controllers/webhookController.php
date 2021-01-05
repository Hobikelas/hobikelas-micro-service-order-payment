<?php

namespace App\Http\Controllers;
use App\Order;
use App\PaymentLog;
use Illuminate\Http\Request;

class webhookController extends Controller
{
    //

    public function midtransHandler(Request $request)
    {
        $data = $request->all();

        $signatureKey = $data['signature_key'];

        $orderId = $data['order_id'];
        $statusCode = $data['status_code'];
        $grossAmount = $data['gross_amount'];
        $serverKey = env('MIDTRANS_SERVER_KEY');

        $mySignatureKey = hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey);

        $transactionStatus = $data['transaction_status'];
        $type = $data['payment_type'];
        $fraudStatus = $data['fraud_status'];

        if ($signatureKey !== $mySignatureKey ) 
        {
            return response()->json([
                'status' => 'error',
                'message' => 'invalid signature'
            ], 400);
        }

        $realOrderId = explode('-', $orderId);
        // echo $realOrderId[0];
        $order = Order::find($realOrderId[0]);
        // return $order;

        if(!$order)
        {
            return response()->json([
                'status' => 'error',
                'message' => "Order not found"
            ], 404);
        }

        if($order->status === 'success')
        {
            return response()->json([
                'status' => 'error',
                'message' => 'operation not permitted'
            ], 405);
        }



        if ($transactionStatus == 'capture'){
            if ($fraudStatus == 'challenge'){
                // TODO set transaction status on your database to 'challenge'
                // and response with 200 OK
                $order->status = 'challenge';
            } else if ($fraudStatus == 'accept'){
                // TODO set transaction status on your database to 'success'
                // and response with 200 OK
                $order->status = 'success';
            }
        } else if ($transactionStatus == 'settlement'){
            // TODO set transaction status on your database to 'success'
            // and response with 200 OK
            $order->status = 'success';
        } else if ($transactionStatus == 'cancel' ||
          $transactionStatus == 'deny' ||
          $transactionStatus == 'expire'){
          // TODO set transaction status on your database to 'failure'
          // and response with 200 OK
          $order->status = 'failure';
        } else if ($transactionStatus == 'pending'){
          // TODO set transaction status on your database to 'pending' / waiting payment
          // and response with 200 OK
          $order->status = 'pending';
        }


        $logData = [
            'status' => $transactionStatus,
            'raw_response' => json_encode($data),
            'order_id' => $realOrderId[0],
            'payment_type' => $type
        ];

        PaymentLog::create($logData);
        $order->save();
        
        if($order->status === 'success')
        {
            // akses premium
            createPremiumAccess([
                'user_id' => $order->user_id,
                'course_id' => $order->course_id
            ]);
        }

        return response()->json('ok');

        // return true;

    }
}
