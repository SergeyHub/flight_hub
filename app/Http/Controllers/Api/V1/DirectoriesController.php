<?php /** @noinspection PhpMultipleClassDeclarationsInspection */


namespace App\Http\Controllers\Api\V1;


use App\Models\Acftmod;
use App\Models\Aircraft;
use App\Models\Airhub;
use App\Models\Airlhist;
use App\Models\Airline;
use App\Models\Airport;
use App\Models\Aprthist;
use App\Models\AkvsAirline;
use App\Models\AkvsFleet;
use App\Models\CargoDangerClass;
use App\Models\Doctype;
use App\Models\FileType;
use App\Models\Fleet;
use App\Models\FlightCategory;
use App\Models\Point;
use App\Models\Season;
use App\Models\State;
use App\Models\UserFavoriteAkvsAirlines;
use Database\Seeders\SeasonSeeder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class DirectoriesController extends Controller
{
    public function getAkvsDirectory(Request $request):JsonResponse
    {
        $response = ['message' => 'Directory is empty or does not exist'];
        $headers = ['Content-type' => 'application/json', 'Access-Control-Allow-Origin' => '*'];

        if (!is_null($request->name)) {
            /* AkvsAirlinesFull */
            if ($request->name == 'AkvsAirlinesFull') {
                $response = $this->getAkvsAirlinesFull();
            }
            /* AkvsAirlines */
            if ($request->name == 'AkvsAirlines') {
                $response = $this->getAkvsAirlines();
            }
            /* AkvsFleet */
            if ($request->name == 'AkvsFleet') {
                $response = $this->getAkvsFleet();
            }
            /* AkvsFleetFull */
            if ($request->name == 'AkvsFleetFull') {
                $response = $this->getAkvsFleetFull();
            }
            if (!$this->requestHasAllowedDirectory($request)) {
                return response()->json(['message' => 'Directory not found'], 404, $headers);
            }

            return response()->json($response, 200, $headers);

        } else {
            return response()->json(['message' => 'Missed directory name parameter'], 500, $headers);
        }
    }

    public function getDirectory(Request $request): JsonResponse
    {
        $response = ['message' => 'Directory is empty or does not exist'];
        $headers = ['Content-type' => 'application/json', 'Access-Control-Allow-Origin' => '*'];

        if (!is_null($request->name)) {
            /* 2.1.1 FlightCategories */
            if ($request->name == 'FlightCategories') {
                $response = $this->getFlightCategories();
            }
            /* 2.1.2 CargoDangerClass */
            if ($request->name == 'CargoDangerClass') {
                $response = $this->getCargoDangerClass();
            }
            /* 2.1.4 States */
            if ($request->name == 'States') {
                $response = $this->getStates();
            }
            /* 2.1.5 Points */
            if ($request->name == 'Points') {
                $response = $this->getPoints();
            }
            /* 2.1.6 Fleet */
            if ($request->name == 'Fleet') {
                $response = $this->getFleet();
            }
            /* 2.1.7 FleetFull */
            if ($request->name == 'FleetFull') {
                $response = $this->getFleetFull();
            }
            /* 2.1.9 Airlines */
            if ($request->name == 'Airlines') {
                $response = $this->getAirlines();
            }
            /* 2.1.10 AirlinesFull */
            if ($request->name == 'AirlinesFull') {
                $response = $this->getAirlinesFull();
            }
            /* 2.1.11 Airports */
            if ($request->name == 'Airports') {
                $response = $this->getAirports();
            }
            /* 2.1.12 DocTypes */
            if ($request->name == 'DocTypes') {
                $response = $this->getDocTypes();
            }
            /* FileTypes */
            if ($request->name == 'FileTypes') {
                $response = $this->getFileTypes();
            }
            /* Airhubs */
            if ($request->name == 'Airhubs') {
                $response = $this->getAirhubs();
            }
            /* Seasons */
            if ($request->name == 'Seasons') {
                $response = $this->getSeasons();
            }
            /* ACFTMOD */
            if ($request->name == 'ACFTMOD') {
                $response = $this->getACFTMOD();
            }
            /* TYPE_AIRCRAFT */
            if ($request->name == 'TYPE_AIRCRAFT') {
                $response = $this->getTypeAircraft();
            }
            if (!$this->requestHasAllowedDirectory($request)) {
                return response()->json(['message' => 'Directory not found'], 404, $headers);
            }

            return response()->json($response, 200, $headers);

        } else {
            return response()->json(['message' => 'Missed directory name parameter'], 500, $headers);
        }
    }

    /* directories */

    protected function getStates()
    {
        $directory = State::query()->select([
            'STATES_ID',
            'IATALAT2',
            'ICAOLAT3',
            'NAMELAT',
            'NAMERUS',
        ]);

        $this->checkIsEmpty($directory);

        return $directory->get();
    }

    protected function getPoints()
    {
        $search = null;

        if (\request()->exists('search')) {
            $search = $this->checkSearchString(\request('search'));
        }

        $isGateway = \request('ISGATEWAY') ?? null;
        $isInOut = \request('ISINOUT') ?? null;

        $directory = Point::query();

        if (!is_null($isGateway)) {
            $directory->where('ISGATEWAY', '=', $isGateway);
        }

        if (!is_null($isInOut)) {
            $directory->where('ISINOUT', '=', $isInOut);
        }

        $directory->select([
            'POINTS_ID',
            'ISGATEWAY',
            'ISINOUT',
        ])
            ->with('pnthist', function ($query) {
                $query->select('POINTS_ID', 'PNTHIST_ID', 'ICAOLAT5', 'ICAOLAT6');
            });

        if (!is_null($search)) {
            $directory
                ->where(function ($query) use ($search) {
                    $query->whereHas('pnthist', function ($innerQuery) use ($search) {
                        $innerQuery->where('ICAOLAT6', 'ilike', "{$search}%");
                    });
                });
        }

        $this->checkIsEmpty($directory);

        return $directory->get();
    }

    protected function getAirlines()
    {
        $search = null;

        if (\request()->exists('search')) {
            $search = $this->checkSearchString(\request('search'));
        }

        $directory = Airline::query()->select([
            'AIRLINES_ID',
            'STATES_ID',
            'FULLNAMELAT',
            'FULLNAMERUS',
            'IATALAT2'
        ])
            ->with('airlhist', function ($query) {
                $query->select('AIRLHIST_ID', 'AIRLINES_ID', 'ICAOLAT3');
            });

        if (!is_null($search)) {
            $directory
                ->where(function ($query) use ($search) {
                    $query->whereHas('airlhist', function ($innerQuery) use ($search) {
                        $innerQuery->where('ICAOLAT3', 'ilike', "%{$search}%");
                    });
                })
                ->orWhere('FULLNAMELAT', 'ilike', "%{$search}%")
                ->orWhere('FULLNAMERUS', 'ilike', "%{$search}%")
                ->orWhere('IATALAT2', 'ilike', "%{$search}%");
        }

        $directory->orderBy($this->sortByAirlhistIcaolat3());

        $this->checkIsEmpty($directory);

        return $directory->get();
    }

    protected function getAirlinesFull()
    {
        $airlinesId = \request('AIRLINES_ID') ?? null;

        $directory = Airline::query();

        if (!is_null($airlinesId)) {
            $directory->where('AIRLINES_ID', $airlinesId);
        }

        $directory->select([
            'AIRLINES_ID',
            'STATES_ID',
            'ORGANIZ_ID',
            'FULLNAMELAT',
            'FULLNAMERUS',
            'IATALAT2'
        ])
            ->with('airlhist', function ($query) {
                $query->select('AIRLHIST_ID', 'AIRLINES_ID', 'ICAOLAT3');
            })
            ->with('airldoc', function ($query) {
                $query->select([
                    'AIRLDOC_ID',
                    'AIRLDOC.AIRLINES_ID',
                    'CESTATUS_ID',
                    'CUR_CESTATUS_ID',
                    'CESTATDATE',
                    'DOCTYPE',
                    'DOCREGNO',
                    'BEGINDATE',
                    'PROLONGDATE',
                    'ENDDATE',
                    'INTERCEENDDATE',
                ]);
            })
            ->with('organiz', function ($query) {
                $query->select([
                    'ORGANIZ_ID',
                    'ADR1RUS',
                    'ADR2RUS',
                    'MAIL',
                    'TELEX',
                    'ATEL',
                    'INTERNET',
                    'INN',
                    'KPP',
                    'OKONH',
                    'OKPO',
                ]);
            });

        $this->checkIsEmpty($directory);

        return $directory->get();
    }

    protected function getAirports()
    {
        $search = null;

        if (\request()->exists('search')) {
            $search = $this->checkSearchString(\request('search'));
        }

        $directory = Airport::query()->select([
            'AIRPORTS_ID',
            'STATES_ID',
            'NAMELAT',
            'NAMERUS',
            'ISSLOT',
            'IATALAT3'
        ])
            ->with('aprthist', function ($query) {
                $query->select('APRTHIST_ID', 'APRTHIST.AIRPORTS_ID', 'ICAOLAT4', 'ISNATIONCODE');
            });

        if (!is_null($search)) {
            $directory
                ->where(function ($query) use ($search) {
                    $query
                        ->whereHas('aprthist', function ($innerQuery) use ($search) {
                            $innerQuery->where('ICAOLAT4', 'ilike', "%{$search}%");
                        })
                        ->orWhere('IATALAT3', 'ilike', "%{$search}%")
                        ->orWhere('NAMELAT', 'ilike', "%{$search}%")
                        ->orWhere('NAMERUS', 'ilike', "%{$search}%");
                })
                ->where([
                    ['APRTTYPE', '!=', 0],
                    ['APRTTYPE', '!=', 2]
                ]);
        }

        $directory->orderBy($this->sortByAprthistIcaolat4());

        $this->checkIsEmpty($directory);

        return $directory->get();
    }

    protected function getFleet()
    {
        $airlinesId = \request('AIRLINES_ID') ?? null;
        $search = null;

        if (\request()->exists('search')) {
            $search = $this->checkSearchString(\request('search'));
        }

        $directory = Fleet::query()->select([
            'FLEET_ID',
            'REGNO',
            'REGISTRNO',
            'SERIALNO'
        ])
            ->with('airlflt', function ($query) {
                $query->select('AIRLFLT_ID', 'AIRLFLT.FLEET_ID', 'AIRLINES_ID');
            })
            ->whereHas('airlflt', function ($query) use ($airlinesId) {
                if (!is_null($airlinesId)) {
                    $query->where('AIRLINES_ID', $airlinesId);
                }
            });

        if (!is_null($search)) {
            $directory->where('REGNO', 'ilike', "%{$search}%");
        }

        $this->checkIsEmpty($directory);

        return $directory->get();
    }

    protected function getFleetFull()
    {
        $fleetId = \request('FLEET_ID') ?? null;

        $directory = Fleet::query();

        if (!is_null($fleetId)) {
            $directory->where('FLEET_ID', $fleetId);
        }

        $directory->select([
            'FLEET_ID',
            'ACFTMOD_ID',
            'REGNO',        //Регистрационный номер
            'REGISTRNO',
            'ACFTOWNER',
            'SERIALNO',     //Заводской Номер
        ])
            ->with('airlflt', function ($query) {
                $query->select('AIRLFLT_ID', 'AIRLFLT.FLEET_ID', 'AIRLINES_ID');
            })
            ->with('aircraft', function ($query) {
                $query->select([
                    'AIRCRAFT.AIRCRAFT_ID',
                    'NAMERUS as TYPE',
                    'CRUISINGSPEED',
                    'NAMELAT' //Тип ВС
                ]);
            })
            ->with('acftmod', function ($query) {
                $query->select([
                    'ACFTMOD.ACFTMOD_ID',
                    'ACFTMOD.MAXIMUMWEIGHT',
                    'MAXLANDINGWEIGHT',
                    'WEIGHTEMPTYPLAN',
                    'PASSENGERCOUNT',
                    'MODIFICATION' //Модель ВС
                ]);
            });

        $this->checkIsEmpty($directory);

        return $directory->get();
    }

    protected function getFlightCategories()
    {
        $directory = FlightCategory::query()->select([
            'CATEGORIES_ID',
            'NAMERUS',
            'NAMELAT',
            'is_commercial'
        ]);

        $this->checkIsEmpty($directory);

        return $directory->get();
    }

    protected function getDocTypes()
    {
        $directory = Doctype::query()->select([
            'DOCTYPE_ID',
            'NAME',
        ]);

        $this->checkIsEmpty($directory);

        return $directory->get();
    }

    protected function getCargoDangerClass()
    {
        $directory = CargoDangerClass::query()->select([
            'CARGOCLASS_ID',
            'ICAOLAT',
        ]);

        $this->checkIsEmpty($directory);

        return $directory->get();
    }

    protected function getFileTypes()
    {
        $directory = FileType::query()->select([
            'id',
            'name_rus',
            'name_lat',
            'required_attributes_json',
            'doc_object',
        ]);

        $this->checkIsEmpty($directory);

        return $directory->get();
    }

    protected function getAirhubs()
    {
        $search = \request('search') ?? null;
        $aprthistId = \request('aprthistId') ?? null;

        $directory = Airhub::query();

        if (!is_null($search)) {
            $directory->where('name', 'ilike', '%' . $search . '%');
        }

        $directory->select([
            'id',
            'name',
        ]);

        if (!is_null($aprthistId)) {
            $directory->whereHas('airports', function ($query) use ($aprthistId) {
                $query->whereHas('aprthist', function ($query) use ($aprthistId) {
                    $query->where('APRTHIST_ID', $aprthistId);
                });
            });
        } else {
            $directory->with('airports', function ($query) {
                $query->select([
                    'AIRPORTS_ID',
                    'NAMELAT',
                    'NAMERUS',
                ])
                    ->with('aprthist', function ($query) {
                        $query->select([
                            'APRTHIST_ID',
                            'AIRPORTS_ID',
                            'ICAOLAT4',
                        ]);
                    });
            });
        }

        $this->checkIsEmpty($directory);

        return $directory->get();
    }

    protected  function getAkvsAirlinesFull()
    {
        $search = null;

        if (\request()->exists('search')) {
            $search = $this->checkSearchString(\request('search'));
        }

        $AIRLINES_ID = \request('AIRLINES_ID') ?? null;

        $directory = AkvsAirline::query();

        if(!is_null($AIRLINES_ID)){
            $directory->where('AIRLINES_ID',$AIRLINES_ID);
        }

        $directory ->select([
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
            'correspondence_address'
        ])
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
            })
            ->where(function($query){
                $query->where('is_verify_latest','=',1)
                    ->orWhere(function($query){
                        $query->where('author_id','=',\Auth::id())
                            ->whereHas('maxVersion');
                    });
            })->where('status','!=',0);


        if (!is_null($search)) {
            $directory->
            where(function($query) use ($search){
                $query->where(function($query) use ($search){
                    $query->where('FULLNAMELAT', 'ilike', '%'.$search.'%')
                        ->orWhere('FULLNAMERUS', 'ilike', '%'.$search.'%');
                })->orWhereHas('akvs_airlhist', function($query) use ($search){
                    $query->where('ICAOLAT3', 'ilike', '%'.$search.'%');
                });
            });
        }

        $this->checkIsEmpty($directory);

        return $directory->get();
    }

    protected function getAkvsAirlines()
    {
        $search = null;

        if (\request()->exists('search')) {
            $search = $this->checkSearchString(\request('search'));
        }

        $directory = AkvsAirline::query()->select([
            'akvs_airlines_id',
            'STATES_ID',
            'status',
            'FULLNAMELAT',
            'FULLNAMERUS',
            'ICAOLAT3',
            'commerce_name',
            'iata_code',
            'legal_address',
            'mailing_address',
            'akvs_organiz_id',
            'is_verify_latest',
        ])
            ->with('akvs_airlhist', function($query){
                $query->select(
                    'akvs_airlines_id',
                    'akvs_arlhist_id',
                    'AIRLINES_ID',
                    'ICAOLAT3',
                    'NAMERUS',
                    );
            })
            ->with('files')
            ->with('organiz', function($query){
                $query->select(
                    'akvs_organiz_id',
                    'ORGANIZ_ID',
                    'INN',
                    'KPP',
                    );
            })->where(function($query){
                $query->where('is_verify_latest','=',1)
                    ->orWhere(function($query){
                        $query->where('author_id','=',\Auth::id())
                            ->whereHas('maxVersion');
                    });
            })  ->where('status','!=',0);

        if (!is_null($search)) {
            $directory
                ->where(function($query) use ($search){
                    $query->where(function ($query) use ($search) {
                        $query->whereHas('akvs_airlhist', function ($innerQuery) use ($search) {
                            $innerQuery->where('ICAOLAT3', 'ilike', "%{$search}%");
                        });
                    })
                        ->orWhere('FULLNAMELAT', 'ilike', "%{$search}%")
                        ->orWhere('FULLNAMERUS', 'ilike', "%{$search}%");
                });
        }

        $this->checkIsEmpty($directory);

        return $directory->get();
    }

    protected function getAkvsFleet(){

        $search = null;

        if (\request()->exists('search')) {
            $search = $this->checkSearchString(\request('search'));
        }

        $directory = AkvsFleet::query()->select([
            'akvs_fleet_id',
            'akvs_aircraft_owner_id',
            'FLEET_ID',
            'REGNO',
            'REGISTRNO',
            'SERIALNO',
            'MAXIMUMWEIGHT',
            'ACFTMOD_ID',
        ])->with('mode',function($query){
            $query->select(
                'ACFTMOD_ID',
                'AIRCRAFT_ID',
                'MAXIMUMWEIGHT',
                'WEIGHTEMPTYPLAN',
                'PASSENGERCOUNT',
                )
                ->with('aircraft', function($query){
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
                });
        })->with('airlflt',function($query){
                $query->select(
                    'akvs_arlflt_id',
                    'akvs_airlines_id',
                    'akvs_fleet_id',
                    'AIRLFLT_ID',
                    'FLEET_ID',
                    'AIRLINES_ID',
                );
            })->whereHas('airlflt', function($query) {
                $query->whereHas('airlines', function($query) {
                    $query->where(function($query){
                        $query->where('is_verify_latest','=',1)
                            ->orWhere(function($query){
                                $query->where('author_id','=',\Auth::id())
                                    ->whereHas('maxVersion');
                            });
                    })
                       ->where('status','!=',0);

                });
            });


        if (!is_null($search)) {
            $directory->where('REGNO', 'ilike', "%{$search}%");
        }


        $this->checkIsEmpty($directory);

        return $directory->get();

    }

    protected function getAkvsFleetFull()
    {
        $fleetId = \request('FLEET_ID') ?? null;

        $directory = AkvsFleet::query();

        if (!is_null($fleetId)) {
            $directory->where('akvs_fleet_id', $fleetId);
        }

        $directory->select([
            'akvs_fleet_id',
            'akvs_aircraft_owner_id',
            'FLEET_ID',
            'REGNO',
            'REGISTRNO',
            'SERIALNO',
            'MAXIMUMWEIGHT',
            'ACFTMOD_ID',
            'ACFTOWNER',
        ])
            ->with('airlflt',function($query){
                $query->select(
                    'akvs_arlflt_id',
                    'akvs_airlines_id',
                    'akvs_fleet_id',
                    'AIRLFLT_ID',
                    'FLEET_ID',
                    'AIRLINES_ID',
                    );
            })
            ->with('owner')
            ->with('mode',function($query){
                $query->select(
                    'ACFTMOD_ID',
                    'AIRCRAFT_ID',
                    'MAXIMUMWEIGHT',
                    'WEIGHTEMPTYPLAN',
                    'PASSENGERCOUNT',
                    )
                    ->with('aircraft', function($query){
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
                    });
            })
            ->with('files')
        ->whereHas('airlflt', function($query){
            $query->whereHas('airlines', function($query){
                $query->where(function($query){
                    $query->where('is_verify_latest','=',1)
                        ->orWhere(function($query){
                            $query->where('author_id','=',\Auth::id())
                                ->whereHas('maxVersion');
                        });
                })->where('status','!=',0);
            });
        });

        $this->checkIsEmpty($directory);

        return $directory->get();
    }

    protected function getACFTMOD()
    {
        $search = null;

        if (\request()->exists('search')) {
            $search = $this->checkSearchString(\request('search'));
        }


        $directory = Acftmod::query();

        $AIRLINES_ID = \request('AIRLINES_ID') ?? null;

        $tacftType = \request('AIRCRAFT_ID') ?? null;

        if (!is_null($tacftType)) {
            $directory->whereHas('aircraft', function($query) use ($tacftType){
                   $query->where('AIRCRAFT_ID',$tacftType);
            });
        }

        if(!is_null($AIRLINES_ID)){
            $directory->whereHas('fleet',function($query) use ($AIRLINES_ID){
               $query->whereHas('airlflt', function($query) use ($AIRLINES_ID){
                  $query->where('AIRLINES_ID',$AIRLINES_ID);
               });
            });
        }

        $directory->select(
            'ACFTMOD_ID',
            'AIRCRAFT_ID',
            'MAXIMUMWEIGHT',
            'WEIGHTEMPTYPLAN',
            'MODIFICATION',
            'PASSENGERCOUNT'
            )
            ->with('aircraft', function($query){
                $query->with('acfthist', function ($query){
                    // Выборка из ACFTHIST
                    $query->select(
                        'ACFTHIST_ID',
                        'AIRCRAFT_ID',
                        'ICAOLAT4',
                        'TACFT_TYPE', // Код ВС из ТС
                        );
                })
                    // Выборка из AIRCRAFT
                    ->select(
                        'AIRCRAFT_ID',
                        'NAMERUS', //Тип ВС
                        'NAMELAT', //Тип ВС
                        'CRUISINGSPEED'
                        );
            });

        if (!is_null($search)) {
            $directory->where('MODIFICATION', 'ilike', "%{$search}%");
        }

        $this->checkIsEmpty($directory);

        return $directory->get();
    }

    protected function getTypeAircraft()
    {
        $search = null;

        if (\request()->exists('search')) {
            $search = $this->checkSearchString(\request('search'));
        }


        $directory = Aircraft::query();

        $AIRLINES_ID = \request('AIRLINES_ID') ?? null;

        $AIRCRAFT_ID = \request('AIRCRAFT_ID') ?? null;

        if(!is_null($AIRLINES_ID)){
            $directory->whereHas('acftmod', function($query) use ($AIRLINES_ID){
                    $query->whereHas('fleet',function($query) use ($AIRLINES_ID){
                        $query->whereHas('airlflt', function($query) use ($AIRLINES_ID){
                            $query->where('AIRLINES_ID',$AIRLINES_ID);
                        });
                    });
                });
        }

        if(!is_null($AIRCRAFT_ID)){
            $directory->where('AIRCRAFT_ID',$AIRCRAFT_ID);
        }

        $directory ->select(
            'AIRCRAFT_ID',
            'NAMERUS', //Тип ВС
            'NAMELAT', //Тип ВС
            'CRUISINGSPEED',
            )
        ->with('acfthist', function ($query){
                    // Выборка из ACFTHIST
                    $query->select(
                        'ACFTHIST_ID',
                        'AIRCRAFT_ID',
                        'ICAOLAT4',
                        'TACFT_TYPE', // Код ВС из ТС
                    );
                });


        if (!is_null($search)) {

            $directory->where(function($query) use ($search){
                $query->where(function($query) use($search){
                    $query->where('NAMERUS', 'ilike', "%{$search}%")
                        ->orWhere('NAMELAT','ilike',"%{$search}%");
                })->orWhere(function($query) use($search){
                    $query->whereHas('acfthist',function($query) use($search){
                        $query->where('ICAOLAT4','ilike',"%{$search}%");
                    });
                });
            });

        }

        $this->checkIsEmpty($directory);

        return $directory->get();
    }

    protected function getSeasons()
    {
        $year = \request('year') ?? null;

        /* Обновление сезонов [ -5 лет, +5 лет ] */
        $seeder = new SeasonSeeder();
        $seeder->run();

        $directory = Season::query()
            ->select([
                'id',
                'name_lat',
                'name_rus',
                'begin_date',
                'end_date',
            ]);

        if (!is_null($year)) {
            $directory->whereYear('begin_date', $year);
        }

        $this->checkIsEmpty($directory);

        return $directory->get();
    }

    /* helpers */

    private function checkIsEmpty($directory)
    {
        if ($directory->count() == 0) {
            Response::make(['message' => 'No records for requested directory'], 404, ['Content-type' => 'application/json', 'Access-Control-Allow-Origin' => '*'])->send();
        }
    }

    private function requestHasAllowedDirectory($request): bool
    {
        return in_array($request->name, $this->allowedDirectories);
    }

    private function sortByAirlhistIcaolat3()
    {
        return Airlhist::select('ICAOLAT3')
            ->whereColumn('AIRLHIST.AIRLINES_ID', 'AIRLINES.AIRLINES_ID')
            ->latest('AIRLHIST.ICAOLAT3')
            ->take(1);
    }

    private function sortByAprthistIcaolat4()
    {
        return Aprthist::select('ICAOLAT4')
            ->whereColumn('APRTHIST.AIRPORTS_ID', 'AIRPORTS.AIRPORTS_ID')
            ->latest('ICAOLAT4')
            ->take(1);
    }

    /* allowed fields */

    private array $allowedDirectories = [
        'FlightCategories',
        'CargoDangerClass',
        'States',
        'Points',
        'Fleet',
        'FleetFull',
        'Airlines',
        'AirlinesFull',
        'Airports',
        'DocTypes',
        'CargoDangerClass',
        'FileTypes',
        'Airhubs',
        'AkvsAirlinesFull',
        'AkvsAirlines',
        'AkvsFleet',
        'AkvsFleetFull',
        'ACFTMOD',
        'TYPE_AIRCRAFT',
        'Seasons',
        'VERIFY_OR_AUTHOR_AIRLINES',
    ];


    /**
     * Проверяем строку поиска на наличие в ней чего-либо кроме букв и чисел
     * Если есть спецсимвол - кодируем его. К примеру, знак амперсанда & будет преобразован в %26
     *
     * @param $string
     * @return string|null
     */
    private function checkSearchString($string): string
    {
        if ($string === null) {
            $this->sendResponseWhenSearchIsNull();
        }

        if (preg_match('/[a-zA-Zа-яА-ЯёЁ0-9]+/u', $string)) {
            return $string;
        } else {
            return urlencode($string);
        }
    }

    private function sendResponseWhenSearchIsNull()
    {
        Response::make(['message' => 'Empty search field'], 400, $this->headers)->send();
    }

    private array $headers = [
        'Content-type' => 'application/json',
        'Access-Control-Allow-Origin' => '*'
    ];
}

