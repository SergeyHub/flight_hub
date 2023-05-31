<?php

namespace App\Http\Controllers\Api\V1;


use App\Models\AkvsAirline;
use App\Models\AkvsMaxVersion;
use App\Models\FileAkvsAirline;
use App\Models\PersonInfo;
use App\Models\UserFavoriteAkvsAirlines;
use App\RequestHandlers\AkvsFilterHandler;
use App\RequestHandlers\AkvsSortingHandler;
use Illuminate\Database\Eloquent\HigherOrderBuilderProxy;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AkvsRegistryController extends Controller
{

    public function getAkvsRegistry(Request $request) : JsonResponse
    {

        $aKvS = AkvsAirline::query()
            // Выборка из AKVS_AIRLINES
            ->select([
                'akvs_airlines_id',
                'akvs_airlines_global_id',
                'akvs_organiz_id',
                'is_verify_latest',
                'created_at',
                'updated_at',
                'status',
                'is_archive',
                'STATES_ID',
                'FULLNAMELAT',
                'FULLNAMERUS',
                'UPDATEDATE',
                'ICAOLAT3',
                'commerce_name',
                'iata_code',
                'legal_address',
                'mailing_address',
                'author_id',
                'AIRLINES_ID',
                'version',
                'correspondence_address',
                'actualization_datetime',
                'is_favorite' => UserFavoriteAkvsAirlines::selectRaw('COUNT(*)')
        ->whereColumn('akvs_airlines_global_id','AKVS_AIRLINES.akvs_airlines_global_id')
                ->where('user_id',\Auth::id())
                ])
            ->where(function($query){
                $query->where('author_id','=',\Auth::id())
                    ->where('status','!=',0);
            })
        ->with('fleet', function($query){
            $query->with('owner')
            ->with('mode', function ($query){
                $query->with('aircraft', function($query){
                    $query->with('acfthist', function ($query){
                        // Выборка из ACFTHIST
                        $query->select(
                            'ACFTHIST_ID',
                            'AIRCRAFT_ID',
                            'TACFT_TYPE',
                            );
                    })
                        // Выборка из AIRCRAFT
                    ->select(
                        'AIRCRAFT_ID',
                        'NAMERUS',
                        'NAMELAT',
                        'CRUISINGSPEED',
                        );
                })
                    // Выборка из ACFTMOD
                    ->select(
                        'ACFTMOD_ID',
                        'AIRCRAFT_ID',
                        'MAXIMUMWEIGHT',
                        'WEIGHTEMPTYPLAN',
                        'MODIFICATION',
                        );;
            })
            ->with('files')
            ->with('airlflt')
                // Выборка из FLEET
                ->select(
                    'AKVS_FLEET.akvs_fleet_id',
                    'AKVS_FLEET.FLEET_ID',
                    'akvs_aircraft_owner_id',
                    'main_color',
                    'cruising_altitude',
                    'minimum_altitude',
                    'flight_rules',
                    'fuel_type',
                    'max_fuel_supply',
                    'ACFTMOD_ID',
                    'REGNO',
                    'SERIALNO',
                    'MAXIMUMWEIGHT',
                    );
        })->with('represent_list', function($query){
                $query->with('contacts');
            })
        ->with('files')
        ->with('organiz', function($query){
            // Выборка из AKVS_ORGANIZ
            $query ->select(
                'akvs_organiz_id',
                'INN',
                'KPP',
                );
        })
        ->with('state')
        ->with('akvs_airlhist', function($query){
            // Выборка из AKVS_AIRLHIST
            $query ->select(
                'akvs_arlhist_id',
                'akvs_airlines_id',
                'NAMERUS',
                'ICAOLAT3',
                );
        });


        (new AkvsFilterHandler($aKvS, $request))->handle();
        (new AkvsSortingHandler($aKvS, $request))->handle();


        /* get all columns with / without pagination */

        if (!is_null($request->get('count'))) {
            if ((int)$request->get('count') !== 0) {
                $aKvS = $aKvS->paginate((int)$request->get('count'));
                return response()->json([
                    'AKVS' => [
                        'data' => $aKvS
                    ]
                ]);
            }
        }


        return response()->json([
            'AKVS' => [
                'data' => $aKvS->get(),
            ],
        ]);
    }

    public function getDraftAkvsRegistry(Request $request)
    {


        $aKvS = AkvsAirline::query()
            // Выборка из AKVS_AIRLINES
            ->select([
                'akvs_airlines_id',
                'akvs_airlines_global_id',
                'akvs_organiz_id',
                'is_verify_latest',
                'created_at',
                'updated_at',
                'status',
                'is_archive',
                'STATES_ID',
                'FULLNAMELAT',
                'FULLNAMERUS',
                'UPDATEDATE',
                'ICAOLAT3',
                'commerce_name',
                'iata_code',
                'legal_address',
                'mailing_address',
                'author_id',
                'AIRLINES_ID',
                'version',
                'correspondence_address',
                'actualization_datetime',
                'is_favorite' => UserFavoriteAkvsAirlines::selectRaw('COUNT(*)')
                    ->whereColumn('akvs_airlines_global_id','AKVS_AIRLINES.akvs_airlines_global_id')
                    ->where('user_id',\Auth::id()),
            ])
            ->whereHas('maxVersion')
            ->where('author_id','=',\Auth::id())
            ->with('fleet', function($query){
                $query->with('owner')
                    ->with('mode', function ($query){
                        $query->with('aircraft', function($query){
                            $query->with('acfthist', function ($query){
                                // Выборка из ACFTHIST
                                $query->select(
                                    'ACFTHIST_ID',
                                    'AIRCRAFT_ID',
                                    'TACFT_TYPE',
                                    );
                            })
                                // Выборка из AIRCRAFT
                                ->select(
                                    'AIRCRAFT_ID',
                                    'NAMERUS',
                                    'NAMELAT',
                                    'CRUISINGSPEED',
                                    );
                        })
                            // Выборка из ACFTMOD
                            ->select(
                                'ACFTMOD_ID',
                                'AIRCRAFT_ID',
                                'MAXIMUMWEIGHT',
                                'WEIGHTEMPTYPLAN',
                                'MODIFICATION',
                                );;
                    })
                    ->with('files')
                    ->with('airlflt')
                    // Выборка из FLEET
                    ->select(
                        'AKVS_FLEET.akvs_fleet_id',
                        'AKVS_FLEET.FLEET_ID',
                        'akvs_aircraft_owner_id',
                        'main_color',
                        'cruising_altitude',
                        'minimum_altitude',
                        'flight_rules',
                        'fuel_type',
                        'max_fuel_supply',
                        'ACFTMOD_ID',
                        'REGNO',
                        'SERIALNO',
                        'MAXIMUMWEIGHT',
                        );
            })->with('represent_list', function($query){
                $query->with('contacts');
            })
            ->with('files')
            ->with('organiz', function($query){
                // Выборка из AKVS_ORGANIZ
                $query ->select(
                    'akvs_organiz_id',
                    'INN',
                    'KPP',
                    );
            })
            ->with('state')
            ->with('akvs_airlhist', function($query){
                // Выборка из AKVS_AIRLHIST
                $query ->select(
                    'akvs_arlhist_id',
                    'akvs_airlines_id',
                    'NAMERUS',
                    'ICAOLAT3',
                    );
            }) ->where('status','=',0);;


        (new AkvsFilterHandler($aKvS, $request))->handle();
        (new AkvsSortingHandler($aKvS, $request))->handle();


        /* get all columns with / without pagination */

        if (!is_null($request->get('count'))) {
            if ((int)$request->get('count') !== 0) {
                $aKvS = $aKvS->paginate((int)$request->get('count'));
                return response()->json([
                    'AKVS' => [
                        'data' => $aKvS
                    ]
                ]);
            }
        }


        return response()->json([
            'AKVS' => [
                'data' => $aKvS->get(),
            ],
        ]);
    }

    public function getAkvsById(Request $request){

        $akvs = AkvsAirline::query()
            // Выборка из AKVS_AIRLINES
            ->select([
                'akvs_airlines_id',
                'akvs_airlines_global_id',
                'akvs_organiz_id',
                'is_verify_latest',
                'created_at',
                'updated_at',
                'status',
                'is_archive',
                'STATES_ID',
                'FULLNAMELAT',
                'FULLNAMERUS',
                'UPDATEDATE',
                'ICAOLAT3',
                'commerce_name',
                'iata_code',
                'legal_address',
                'mailing_address',
                'author_id',
                'AIRLINES_ID',
                'version',
                'correspondence_address',
                'is_favorite' => UserFavoriteAkvsAirlines::selectRaw('COUNT(*)')
                    ->whereColumn('akvs_airlines_global_id','AKVS_AIRLINES.akvs_airlines_global_id')
                    ->where('user_id',\Auth::id())
            ])
            ->where('akvs_airlines_id',$request['akvs_airlines_id'])
            ->with('fleet', function($query){
                $query->with('owner')
                    ->with('mode', function ($query){
                        $query->with('aircraft', function($query){
                            $query->with('acfthist', function ($query){
                                // Выборка из ACFTHIST
                                $query->select(
                                    'ACFTHIST_ID',
                                    'AIRCRAFT_ID',
                                    'TACFT_TYPE',
                                    );
                            })
                                // Выборка из AIRCRAFT
                                ->select(
                                    'AIRCRAFT_ID',
                                    'NAMERUS',
                                    'NAMELAT',
                                    'CRUISINGSPEED',
                                    );
                        })
                            // Выборка из ACFTMOD
                            ->select(
                                'ACFTMOD_ID',
                                'AIRCRAFT_ID',
                                'MAXIMUMWEIGHT',
                                'WEIGHTEMPTYPLAN',
                                'MODIFICATION',
                                );;
                    })
                    ->with('files')
                    ->with('airlflt')
                    // Выборка из FLEET
                    ->select(
                        'AKVS_FLEET.akvs_fleet_id',
                        'AKVS_FLEET.FLEET_ID',
                        'akvs_aircraft_owner_id',
                        'main_color',
                        'cruising_altitude',
                        'minimum_altitude',
                        'flight_rules',
                        'fuel_type',
                        'max_fuel_supply',
                        'ACFTMOD_ID',
                        'REGNO',
                        'SERIALNO',
                        'MAXIMUMWEIGHT',
                        );
            })->with('represent_list', function($query){
                $query->with('contacts');
            })
            ->with('files')
            ->with('organiz', function($query){
                // Выборка из AKVS_ORGANIZ
                $query ->select(
                    'akvs_organiz_id',
                    'INN',
                    'KPP',
                    );
            })
            ->with('state')
            ->with('akvs_airlhist', function($query){
                // Выборка из AKVS_AIRLHIST
                $query ->select(
                    'akvs_arlhist_id',
                    'akvs_airlines_id',
                    'NAMERUS',
                    'ICAOLAT3',
                    );
            });

        return response()->json([
           'AKVS' => [
               'data' => $akvs->get()
           ]
        ]);

    }

}
