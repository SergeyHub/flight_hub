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

class Airlflt implements ShouldQueue
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
        $table = 'airlFlt';

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

//            Log::info($data);

            /* Parse */
            $crawler = new Crawler();

            $crawler->addHtmlContent($data);

            Log::info("$table transactions start");
            $crawler->filterXPath("//rowset/row")->each(function($node) use ($table, $cbdService)
            {
                try {
                    $exception = DB::transaction(function() use ($node, $cbdService) {
                        \App\Models\Airlflt::updateOrCreate(['AIRLFLT_ID' => $node->filterXPath("//airlflt_id")->text()], [
                            'AIRLFLT_ID' => $cbdService->nodeNotNull($node, '//airlflt_id'),
                            'AIRLINES_ID' => $cbdService->nodeNotNull($node, '//airlines_id'),
                            'OWNERTYPE_ID' => $cbdService->nodeNotNull($node, '//ownertype_id'),
                            'FLEET_ID' => $cbdService->nodeNotNull($node, '//fleet_id'),
                            'ISDELETE' => $cbdService->nodeNotNull($node, '//isdelete'),
                            'BEGINDATE' => $cbdService->nodeNotNull($node, '//begindate'),
                            'ENDDATE' => $cbdService->nodeNotNull($node, '//enddate'),
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
