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

class Aircraft implements ShouldQueue
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
        $table = 'Aircraft';

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
                        \App\Models\Aircraft::updateOrCreate(['AIRCRAFT_ID' => $node->filterXPath("//aircraft_id")->text()], [
                            'AIRCRAFT_ID' => $cbdService->nodeNotNull($node, '//aircraft_id'),
                            'NAMELAT' => $cbdService->nodeNotNull($node, '//namelat'),
                            'NAMERUS' => $cbdService->nodeNotNull($node, '//namerus'),
                            'DESCRIPTION' => $cbdService->nodeNotNull($node, '//description'),
                            'MAXIMUMWEIGHT' => $cbdService->nodeNotNull($node, '//maximumweight'),
                            'CRUISINGSPEED' => $cbdService->nodeNotNull($node, '//cruisingspeed'),
                            'RANGEAPPLIED' => $cbdService->nodeNotNull($node, '//rangeapplied'),
                            'ALTITUDEMAXCRUISING' => $cbdService->nodeNotNull($node, '//altitudemaxcruising'),
                            'FUELCLOCK' => $cbdService->nodeNotNull($node, '//fuelclock'),
                            'MAXFLYLEVEL' => $cbdService->nodeNotNull($node, '//maxflylevel'),
                            'ICAOCLASS' => $cbdService->nodeNotNull($node, '//icaoclass'),
                            'ACFTCAT' => $cbdService->nodeNotNull($node, '//acftcat'),
                            'ASCENDRATE' => $cbdService->nodeNotNull($node, '//ascendrate'),
                            'DESCENDRATE' => $cbdService->nodeNotNull($node, '//descendrate'),
                            'TURBULENCECAT' => $cbdService->nodeNotNull($node, '//turbulencecat'),
                            'TIMELEADOUT' => $cbdService->nodeNotNull($node, '//timeleadout'),
                            'TIMELEADIN' => $cbdService->nodeNotNull($node, '//timeleadin'),
                            'AIRCRAFTGROUP' => $cbdService->nodeNotNull($node, '//aircraftgroup'),
                            'ACNEMPTYK150' => $cbdService->nodeNotNull($node, '//acnemptyk150'),
                            'ACNEMPTYK20' => $cbdService->nodeNotNull($node, '//acnemptyk20'),
                            'ACNEMPTYK80' => $cbdService->nodeNotNull($node, '//acnemptyk80'),
                            'ACNEMPTYK40' => $cbdService->nodeNotNull($node, '//acnemptyk40'),
                            'ACNEMPTYCBR15' => $cbdService->nodeNotNull($node, '//acnemptycbr15'),
                            'ACNEMPTYCBR10' => $cbdService->nodeNotNull($node, '//acnemptycbr10'),
                            'ACNEMPTYCBR6' => $cbdService->nodeNotNull($node, '//acnemptycbr6'),
                            'ACNEMPTYCBR3' => $cbdService->nodeNotNull($node, '//acnemptycbr3'),
                            'ACNLANDK150' => $cbdService->nodeNotNull($node, '//acnlandk150'),
                            'ACNLANDK80' => $cbdService->nodeNotNull($node, '//acnlandk80'),
                            'ACNLANDK40' => $cbdService->nodeNotNull($node, '//acnlandk40'),
                            'ACNLANDK20' => $cbdService->nodeNotNull($node, '//acnlandk20'),
                            'ACNLANDCBR15' => $cbdService->nodeNotNull($node, '//acnlandcbr15'),
                            'ACNLANDCBR10' => $cbdService->nodeNotNull($node, '//acnlandcbr10'),
                            'ACNLANDCBR6' => $cbdService->nodeNotNull($node, '//acnlandcbr6'),
                            'ACNLANDCBR3' => $cbdService->nodeNotNull($node, '//acnlandcbr3'),
                            'MINLENGTHRUNWAYTAKEOFF' => $cbdService->nodeNotNull($node, '//minlengthrunwaytakeoff'),
                            'MAXLENGTHRUNWAYTAKEOFF' => $cbdService->nodeNotNull($node, '//maxlengthrunwaytakeoff'),
                            'MINLENGTHRUNWAYLANDING' => $cbdService->nodeNotNull($node, '//minlengthrunwaylanding'),
                            'MAXLENGTHRUNWAYLANDING' => $cbdService->nodeNotNull($node, '//maxlengthrunwaylanding'),
                            'LANDINGSPEED' => $cbdService->nodeNotNull($node, '//landingspeed'),
                            'BEGINDATE' => $cbdService->nodeNotNull($node, '//begindate'),
                            'ENDDATE' => $cbdService->nodeNotNull($node, '//enddate'),
                            'UPDATEDATE' => $cbdService->nodeNotNull($node, '//updatedate'),
                            'ISDELETE' => $cbdService->nodeNotNull($node, '//isdelete'),
                        ]);
                    });

                    if(!is_null($exception)) throw new Exception;

                } catch(Exception $e) {Log::debug("$table transaction error", (array) $e->getMessage());}
            });

            Log::info("$table transactions successfully");

        } else Log::info("$table transactions failed");
    }
}
