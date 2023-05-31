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

class Fleet implements ShouldQueue
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
        $table = 'fleet';

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
            $crawler->filterXPath("//rowset/row")->each(function($node) use ($table, $cbdService) {
                try {
                    $exception = DB::transaction(function() use ($node, $cbdService)
                    {
                        \App\Models\Fleet::updateOrCreate(['FLEET_ID' => $node->filterXPath("//fleet_id")->text()], [
                            'FLEET_ID' => $cbdService->nodeNotNull($node, '//fleet_id'),
                            'ACFTMOD_ID' => $cbdService->nodeNotNull($node, '//acftmod_id'),
                            'REGNO' => $cbdService->nodeNotNull($node, '//regno'),
                            'RSST_TYPE_ID' => $cbdService->nodeNotNull($node, '//rsst_type_id'),
                            'REGNO_OLD' => $cbdService->nodeNotNull($node, '//regno_old'),
                            'SERIALNO' => $cbdService->nodeNotNull($node, '//serialno'),
                            'REGISTRNO' => $cbdService->nodeNotNull($node, '//registrno'),
                            'PRODDATE' => $cbdService->nodeNotNull($node, '//proddate'),
                            'TOTALFLIGHTTIME' => $cbdService->nodeNotNull($node, '//totalflighttime'),
                            'TOTALFLIGHTYEAR' => $cbdService->nodeNotNull($node, '//totalflightyear'),
                            'MAXIMUMWEIGHT' => $cbdService->nodeNotNull($node, '//maximumweight'),
                            'ACFTOWNER' => $cbdService->nodeNotNull($node, '//acftowner'),
                            'ACFTFACTORY' => $cbdService->nodeNotNull($node, '//acftfactory'),
                            'ISINTERFLIGHT' => $cbdService->nodeNotNull($node, '//isinterflight'),
                            'SPECIALREMARK' => $cbdService->nodeNotNull($node, '//specialremark'),
                            'ACFTCOMMENT' => $cbdService->nodeNotNull($node, '//acftcomment'),
                            'MAXIMUMWEIGHT_ORG' => $cbdService->nodeNotNull($node, '//maximumweight_org'),
                            'OBO' => $cbdService->nodeNotNull($node, '//obo'),
                            'ACFTFUNCTION' => $cbdService->nodeNotNull($node, '//acftfunction'),
                            'CERTIFACFTNO' => $cbdService->nodeNotNull($node, '//certifacftno'),
                            'CERTIFACFTENDDATE' => $cbdService->nodeNotNull($node, '//certifacftenddate'),
                            'REGISTRDATE' => $cbdService->nodeNotNull($node, '//registrdate'),
                            'BEGINDATE' => $cbdService->nodeNotNull($node, '//begindate'),
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
