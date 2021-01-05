<?php

use Illuminate\Support\Facades\Http;


function createPremiumAccess($data)
{
    $url = env('URL_SERVICE_COURSE').'api/my-courses/premium';
    echo $url;
    try {
        //code...
        
        $response = Http::post($url, $data);
        echo $response;
        $data = $response->json();
        $data['http_code'] = $response->getStatusCode();
        return $data;

    } catch (\Throwable $th) {
        //throw $th;
        return [
            'status' => 'error',
            'http_code' => 500,
            'message' => "Service course unavailable"
        ];
    }
}