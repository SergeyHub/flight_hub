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

class Airldoc implements ShouldQueue
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
        $table = 'airlDoc';

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
                        \App\Models\Airldoc::updateOrCreate(['AIRLDOC_ID' => $node->filterXPath("//airldoc_id")->text()], [
                            'AIRLDOC_ID' => $cbdService->nodeNotNull($node, '//airldoc_id'),
                            'AIRLINES_ID' => $cbdService->nodeNotNull($node, '//airlines_id'),
                            'CESTATUS_ID' => $cbdService->nodeNotNull($node, '//cestatus_id'),
                            'AVADMIN_ID' => $cbdService->nodeNotNull($node, '//avadmin_id'),
                            'CUR_CESTATUS_ID' => $cbdService->nodeNotNull($node, '//cur_cestatus_id'),
                            'CESTATDATE' => $cbdService->nodeNotNull($node, '//cestatdate'),
                            'DOCTYPE' => $cbdService->nodeNotNull($node, '//doctype'),
                            'DOCREGNO' => $cbdService->nodeNotNull($node, '//docregno'),
                            'BEGINDATE' => $cbdService->nodeNotNull($node, '//begindate'),
                            'PROLONGDATE' => $cbdService->nodeNotNull($node, '//prolongdate'),
                            'ENDDATE' => $cbdService->nodeNotNull($node, '//enddate'),
                            'INTERCEENDDATE' => $cbdService->nodeNotNull($node, '//interceenddate'),
                            'DOCCOMMENT' => $cbdService->nodeNotNull($node, '//doccomment'),
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
