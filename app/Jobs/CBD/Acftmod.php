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

class Acftmod implements ShouldQueue
{
    use Batchable;
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

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
        $table = 'acftMod';

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
            $crawler->filterXPath("//rowset/row")->each(function($node) use ($table, $cbdService) {
                try {
                    $exception = DB::transaction(function() use ($node, $cbdService)
                    {
                        \App\Models\Acftmod::updateOrCreate(['ACFTMOD_ID' => $node->filterXPath("//acftmod_id")->text()], [
                            'ACFTMOD_ID' => $cbdService->nodeNotNull($node, "//acftmod_id"),
                            'AIRCRAFT_ID' => $cbdService->nodeNotNull($node, "//aircraft_id"),
                            'MAXIMUMWEIGHT' => $cbdService->nodeNotNull($node, "//maximumweight"),
                            'MAXLANDINGWEIGHT' => $cbdService->nodeNotNull($node, "//maxlandingweight"),
                            'WEIGHTEMPTYPLAN' => $cbdService->nodeNotNull($node, "//weightemptyplan"),
                            'PASSENGERCOUNT' => $cbdService->nodeNotNull($node, "//passengercount"),
                            'MODIFICATION' => $cbdService->nodeNotNull($node, "//modification"),
                        ]);
                    });

                    if(!is_null($exception)) throw new Exception();

                } catch(Exception $e) {Log::debug("$table transaction error", (array) $e->getMessage());}
            });

            Log::info("$table transactions successfully");

        } else Log::info("$table transactions failed");
    }
}
