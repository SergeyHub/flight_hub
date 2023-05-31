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

class Pnthist implements ShouldQueue
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
        $table = 'pntHist';

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
                        \App\Models\Pnthist::updateOrCreate(['PNTHIST_ID' => $node->filterXPath("//pnthist_id")->text()], [
                            'PNTHIST_ID' => $cbdService->nodeNotNull($node, '//pnthist_id'),
                            'POINTS_ID' => $cbdService->nodeNotNull($node, '//points_id'),
                            'ICAOLAT5' => $cbdService->nodeNotNull($node, '//icaolat5'),
                            'ICAORUS5' => $cbdService->nodeNotNull($node, '//icaorus5'),
                            'ICAOLAT6' => $cbdService->nodeNotNull($node, '//icaolat6'),
                            'ICAORUS6' => $cbdService->nodeNotNull($node, '//icaorus6'),
                            'ISMVL' => $cbdService->nodeNotNull($node, '//ismvl'),
                            'ISACP' => $cbdService->nodeNotNull($node, '//isacp'),
                            'ISGATEWAY' => $cbdService->nodeNotNull($node, '//isgateway'),
                            'LATITUDE' => $cbdService->nodeNotNull($node, '//latitude'),
                            'LONGITUDE' => $cbdService->nodeNotNull($node, '//longitude'),
                            'MAGNDEVIATION' => $cbdService->nodeNotNull($node, '//magndeviation'),
                            'ISINOUT' => $cbdService->nodeNotNull($node, '//isinout'),
                            'ISINOUTSNG' => $cbdService->nodeNotNull($node, '//isinoutsng'),
                            'PNTBEGINDATE' => $cbdService->nodeNotNull($node, '//pntbegindate'),
                            'ENDDATE' => $cbdService->nodeNotNull($node, '//enddate'),
                            'ISDELETE' => $cbdService->nodeNotNull($node, '//isdelete'),
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
