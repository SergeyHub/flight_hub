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

class Airline implements ShouldQueue
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
        $table = 'Airlines';

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
                        \App\Models\Airline::updateOrCreate(['AIRLINES_ID' => $node->filterXPath("//airlines_id")->text()], [
                            'AIRLINES_ID' => $cbdService->nodeNotNull($node, '//airlines_id'),
                            'AIR_AIRLINES_ID' => $cbdService->nodeNotNull($node, '//air_airlines_id'),
                            'AVST_TYPE_ID' => $cbdService->nodeNotNull($node, '//avst_type_id'),
                            'STATES_ID' => $cbdService->nodeNotNull($node, '//states_id'),
                            'ORGFORM_ID' => $cbdService->nodeNotNull($node, '//orgform_id'),
                            'ORGANIZ_ID' => $cbdService->nodeNotNull($node, '//organiz_id'),
                            'REGCTRL_ID' => $cbdService->nodeNotNull($node, '//regctrl_id'),
                            'AVADMIN_ID' => $cbdService->nodeNotNull($node, '//avadmin_id'),
                            'IATALAT2' => $cbdService->nodeNotNull($node, '//iatalat2'),
                            'NAMELAT' => $cbdService->nodeNotNull($node, '//namelat'),
                            'SHORTNAMELAT' => $cbdService->nodeNotNull($node, '//shortnamelat'),
                            'SHORTNAMERUS' => $cbdService->nodeNotNull($node, '//shortnamerus'),
                            'FULLNAMELAT' => $cbdService->nodeNotNull($node, '//fullnamelat'),
                            'FULLNAMERUS' => $cbdService->nodeNotNull($node, '//fullnamerus'),
                            'ISUSEFULLNAMERUS' => $cbdService->nodeNotNull($node, '//isusefullnamerus'),
                            'NAMEJP' => $cbdService->nodeNotNull($node, '//namejp'),
                            'ISCHARTERONLY' => $cbdService->nodeNotNull($node, '//ischarteronly'),
                            'ISINTERCIS' => $cbdService->nodeNotNull($node, '//isintercis'),
                            'OPERTYPE' => $cbdService->nodeNotNull($node, '//opertype'),
                            'ISAON' => $cbdService->nodeNotNull($node, '//isaon'),
                            'ISBUSINESS' => $cbdService->nodeNotNull($node, '//isbusiness'),
                            'BUSINESSTYPE' => $cbdService->nodeNotNull($node, '//businesstype'),
                            'FAS_ISN' => $cbdService->nodeNotNull($node, '//fas_isn'),
                            'ISMUSTCE' => $cbdService->nodeNotNull($node, '//ismustce'),
                            'ISUSEFULLNAMELAT' => $cbdService->nodeNotNull($node, '//isusefullnamelat'),
                            'ISSBORNIK' => $cbdService->nodeNotNull($node, '//issbornik'),
                            'ISLANGRUSSTAFF' => $cbdService->nodeNotNull($node, '//islangrusstaff'),
                            'ISANCCONTROL' => $cbdService->nodeNotNull($node, '//isanccontrol'),
                            'ISDECLARANT' => $cbdService->nodeNotNull($node, '//isdeclarant'),
                            'EXPIRYTYPE' => $cbdService->nodeNotNull($node, '//expirytype'),
                            'TYPEAIRLINES' => $cbdService->nodeNotNull($node, '//typeairlines'),
                            'EFFECTDATE' => $cbdService->nodeNotNull($node, '//effectdate'),
                            'EXPIRYDATE' => $cbdService->nodeNotNull($node, '//expirydate'),
                            'UPDATEDATE' => $cbdService->nodeNotNull($node, '//updatedate'),
                            'ICAOLAT3' => $cbdService->nodeNotNull($node, '//icaolat3'),
                        ]);
                    });

                    if(!is_null($exception)) throw new Exception;

                } catch(Exception $e) {Log::debug("$table transaction error", (array) $e->getMessage());}
            });

            Log::info("$table transactions successfully");

        } else Log::info("$table transactions failed");
    }
}
