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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

use Symfony\Component\DomCrawler\Crawler;

use Exception;

class Organiz implements ShouldQueue
{
    use Batchable,
        Dispatchable,
        InteractsWithQueue,
        Queueable,
        SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {

    }

    public function handle()
    {
        $url = "https://websvcwork.matfmc.ru/CBD2/1.1/test/Airspace.asmx?WSDL";
        $table = 'organiz';

        $cbdService = new CBDService();
        $auth = $cbdService->auth();
        $xml = $cbdService->airspace($auth, $table);


        /* Start connection */

        Log::info("$table CBD connection");


        /* Http */
        $response = Http::withHeaders([
            'Content-Type' => 'application/soap+xml',
            'Content-Length' => '<calculated when request is sent>',
            'Accept-Encoding' => 'gzip, deflate, br',
            'Connection' => 'keep-alive'
        ])->send('POST', $url, [
            'body' => $xml,
        ]);



        if($response->successful()) {
            Log::info("$table response received successfully");

            $data = $response->body();

            /* Parse */
            $crawler = new Crawler();

            $crawler->addHtmlContent($data);

            Log::info("$table transactions start");
            $crawler->filterXPath("//rowset/row")->each(function($node) use ($table, $cbdService)
            {
                try {
                    $exception = DB::transaction(function() use ($node, $cbdService) {
                        \App\Models\Organiz::updateOrCreate(['ORGANIZ_ID' => $node->filterXPath("//organiz_id")->text()], [
                            'ORGANIZ_ID' => $cbdService->nodeNotNull($node, '//organiz_id'),
                            'ADR1RUS' => $cbdService->nodeNotNull($node, '//adr1rus'),
                            'ADR2RUS' => $cbdService->nodeNotNull($node, '//adr2rus'),
                            'MAIL' => $cbdService->nodeNotNull($node, '//mail'),
                            'TELEX' => $cbdService->nodeNotNull($node, '//telex'),
                            'ATEL' => $cbdService->nodeNotNull($node, '//atel'),
                            'INTERNET' => $cbdService->nodeNotNull($node, '//internet'),
                            'INN' => $cbdService->nodeNotNull($node, '//inn'),
                            'KPP' => $cbdService->nodeNotNull($node, '//kpp'),
                            'OKONH' => $cbdService->nodeNotNull($node, '//okonh'),
                            'OKPO' => $cbdService->nodeNotNull($node, '//okpo'),
                            'UPDATEDATE' => $cbdService->nodeNotNull($node, '//updatedate'),
                        ]);
                    });

                    if(!is_null($exception)) throw new Exception;

                } catch(Exception $e) {Log::debug("$table transaction error", (array) $e->getMessage());}
            });

            Log::info("$table transactions successfully");

        } else Log::info("$table transactions failed");
    }
}
