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

class Airport implements ShouldQueue
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
        $table = 'Airports';

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
                        \App\Models\Airport::updateOrCreate(['AIRPORTS_ID' => $node->filterXPath("//airports_id")->text()], [
                            'AIRPORTS_ID' => $cbdService->nodeNotNull($node, '//airports_id'),
                            'AIR_AIRPORTS_ID' => $cbdService->nodeNotNull($node, '//air_airports_id'),
                            'BRDZONE_ID' => $cbdService->nodeNotNull($node, '//brdzone_id'),
                            'FIRS_ID' => $cbdService->nodeNotNull($node, '//firs_id'),
                            'ARDMCLASS_ID' => $cbdService->nodeNotNull($node, '//ardmclass_id'),
                            'REGCTRL_ID' => $cbdService->nodeNotNull($node, '//regctrl_id'),
                            'AVADMIN_ID' => $cbdService->nodeNotNull($node, '//avadmin_id'),
                            'STATES_ID' => $cbdService->nodeNotNull($node, '//states_id'),
                            'ORGANIZ_ID' => $cbdService->nodeNotNull($node, '//organiz_id'),
                            'SPCCLASS_ID' => $cbdService->nodeNotNull($node, '//spcclass_id'),
                            'APRTCLASS_ID' => $cbdService->nodeNotNull($node, '//aprtclass_id'),
                            'AIRITEM_ID' => $cbdService->nodeNotNull($node, '//airitem_id'),
                            'SIRENA' => $cbdService->nodeNotNull($node, '//sirena'),
                            'IATALAT3' => $cbdService->nodeNotNull($node, '//iatalat3'),
                            'NAMELAT' => $cbdService->nodeNotNull($node, '//namelat'),
                            'NAMERUS' => $cbdService->nodeNotNull($node, '//namerus'),
                            'FULLNAMERUS' => $cbdService->nodeNotNull($node, '//namerus'),
                            'APRTTYPE' => $cbdService->nodeNotNull($node, '//aprttype'),
                            'USAGETYPEAPRT' => $cbdService->nodeNotNull($node, '//usagetypeaprt'),
                            'CIVMILAPRT' => $cbdService->nodeNotNull($node, '//civmilaprt'),
                            'ISINAIP' => $cbdService->nodeNotNull($node, '//isinaip'),
                            'ISALTERNATE' => $cbdService->nodeNotNull($node, '//isalternate'),
                            'ISFEDERALPORT' => $cbdService->nodeNotNull($node, '//isfederalport'),
                            'ISLOCALPORT' => $cbdService->nodeNotNull($node, '//islocalport'),
                            'ISINREESTRCA' => $cbdService->nodeNotNull($node, '//isinreestrca'),
                            'ISJOINUSE' => $cbdService->nodeNotNull($node, '//isjoinuse'),
                            'ISJOINBASE' => $cbdService->nodeNotNull($node, '//isjoinbase'),
                            'ARDMRELIEFTYPE' => $cbdService->nodeNotNull($node, '//ardmrelieftype'),
                            'HEIGHTPASSAGE' => $cbdService->nodeNotNull($node, '//heightpassage'),
                            'UNITALTITUDE' => $cbdService->nodeNotNull($node, '//unitaltitude'),
                            'FLPASSAGE' => $cbdService->nodeNotNull($node, '//flpassage'),
                            'ISTRUEMAGN' => $cbdService->nodeNotNull($node, '//istruemagn'),
                            'ISSUMMERTIMENO' => $cbdService->nodeNotNull($node, '//issummertimeno'),
                            'ISN_ZC' => $cbdService->nodeNotNull($node, '//isn_zc'),
                            'METEOMINIMUM' => $cbdService->nodeNotNull($node, '//meteominimum'),
                            'ARDMSTATUS' => $cbdService->nodeNotNull($node, '//ardmstatus'),
                            'EFFECTDATE' => $cbdService->nodeNotNull($node, '//effectdate'),
                            'EXPIRYDATE' => $cbdService->nodeNotNull($node, '//expirydate'),
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
