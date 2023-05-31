<?php

namespace App\Jobs\CBD;

use App\UseCases\CBD\CBDService;

use Illuminate\Bus\Batchable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class Token implements ShouldQueue
{
    use Batchable,
        Dispatchable,
        InteractsWithQueue,
        Queueable,
        SerializesModels;

    public function __construct()
    {

    }

    public function handle()
    {
        $url = "https://websvcwork.matfmc.ru/CBD2/1.1/test/Meta.asmx?WSDL";

        $cbdService = new CBDService();
        $auth = $cbdService->auth();
        $xml = $cbdService->meta($auth);

        /* Start connection */

        Log::info("Token CBD connection");

        /* Http */
        $response = Http::withHeaders([
            'Content-Type' => 'application/soap+xml',
            'Content-Length' => '<calculated when request is sent>',
            'Accept-Encoding' => 'gzip, deflate, br',
            'Connection' => 'keep-alive'
        ])->send('POST', $url, [
            'body' => $xml,
        ]);

        /* Successful */
        if($response->successful()) {

            Log::info("Token response received successfully");

            /* Parse */
            preg_match('/<GetTokenResult>([\s\S]+?)<\/GetTokenResult>/', $response->body(), $token);

            $token = $token[1];

            /* Save */
            \App\Models\CBD\Token::create([
                'token' => $token
            ]);

            Log::info('Token save token successful');
        }
    }
}
