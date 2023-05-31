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

class Point implements ShouldQueue
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
        $table = 'points';

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
                        \App\Models\Point::updateOrCreate(['POINTS_ID' => $node->filterXPath("//points_id")->text()], [
                            'POINTS_ID' => $cbdService->nodeNotNull($node, '//points_id'),
                            'AIRPORTS_ID' => $cbdService->nodeNotNull($node, '//airports_id'),
                            'TIMEZONE_ID' => $cbdService->nodeNotNull($node, '//timezone_id'),
                            'FIRS_ID' => $cbdService->nodeNotNull($node, '//firs_id'),
                            'FACILITY_ID' => $cbdService->nodeNotNull($node, '//facility_id'),
                            'AIRITEM_ID' => $cbdService->nodeNotNull($node, '//airitem_id'),
                            'ISINAIP' => $cbdService->nodeNotNull($node, '//isinaip'),
                            'NAMELAT' => $cbdService->nodeNotNull($node, '//namelat'),
                            'NAMERUS' => $cbdService->nodeNotNull($node, '//namerus'),
                            'LATITUDE' => $cbdService->nodeNotNull($node, '//latitude'),
                            'LONGITUDE' => $cbdService->nodeNotNull($node, '//longitude'),
                            'MAGNDEVIATION' => $cbdService->nodeNotNull($node, '//magndeviation'),
                            'FREQENCYWORK' => $cbdService->nodeNotNull($node, '//freqencywork'),
                            'FREQENCYTYPE' => $cbdService->nodeNotNull($node, '//freqencytype'),
                            'ISRPTONQRY' => $cbdService->nodeNotNull($node, '//isrptonqry'),
                            'ISACP' => $cbdService->nodeNotNull($node, '//isacp'),
                            'ISINOUT' => $cbdService->nodeNotNull($node, '//isinout'),
                            'ISINOUTSNG' => $cbdService->nodeNotNull($node, '//isinoutsng'),
                            'ISGATEWAY' => $cbdService->nodeNotNull($node, '//isgateway'),
                            'ISTRANSFERPOINT' => $cbdService->nodeNotNull($node, '//istransferpoint'),
                            'ISTRANSFERPOINT_APRT' => $cbdService->nodeNotNull($node, '//istransferpoint_aprt'),
                            'ISMVL' => $cbdService->nodeNotNull($node, '//ismvl'),
                            'ISINARZ' => $cbdService->nodeNotNull($node, '//isinarz'),
                            'ISOUTARZ' => $cbdService->nodeNotNull($node, '//isoutarz'),
                            'ISPNTRA' => $cbdService->nodeNotNull($node, '//ispntra'),
                            'ISPNTAIRWAY' => $cbdService->nodeNotNull($node, '//ispntairway'),
                            'BEGINDATE' => $cbdService->nodeNotNull($node, '//begindate'),
                            'ENDDATE' => $cbdService->nodeNotNull($node, '//enddate'),
                            'UPDATEDATE' => $cbdService->nodeNotNull($node, '//updatedate'),
                        ]);
                    });

                    if(!is_null($exception)) throw new Exception();

                } catch(Exception $e) {Log::debug("$table transaction error", (array) $e->getMessage());}
            });

            Log::info("$table transactions successfully");

        } else Log::info("$table transactions failed");
    }
}
