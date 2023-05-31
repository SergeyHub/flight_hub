<?php /** @noinspection PhpMultipleClassDeclarationsInspection */

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Classes\Headers;
use App\Http\Controllers\Controller;
use App\Models\NFormFlight;
use App\Models\NFormFlightAgreementSign;
use App\Models\NFormFlightSign;
use App\Models\NFormFlightStatus;
use App\Models\SettingParameter;
use App\Models\Setting;
use App\Models\MainSettingParameter;
use App\Models\NForm;
use App\Models\User;
use App\RequestHandlers\CheckingAccessForFlightsHandler;
use App\RequestHandlers\FiltersHandler;
use App\RequestHandlers\SearchHandler;
use App\RequestHandlers\SortingHandler;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class RegistryController extends Controller
{
    public function getRegistry(Request $request): JsonResponse
    {
        $nForms = NForm::query()
            //Получаем только формы с последней версией
            ->where('is_latest', 1);

        if ($request->has('is_archive') && $request->get('is_archive') == true) {
            if (Auth::user()->active_role_id == 3) {
                $nForms->where('is_archive', '=', 1);
            }

            if (Auth::user()->active_role_id != 3 && Auth::user()->active_role_id !== null) {
                $nForms->where('is_archive_gc', '=', 1);
            }
        } else {
            if (Auth::user()->active_role_id == 3) {
                $nForms->where('is_archive', '=', 0);
            }

            if (Auth::user()->active_role_id != 3 && Auth::user()->active_role_id !== null) {
                $nForms->where('is_archive_gc', '=', 0);
            }
        }

        $nForms->select([
            'n_forms_id',
            'id_pakus',
            'permit_num',
            'created_at',
            'updated_at',
            'form_status_id',
            'author_id',
            'is_latest',
            //Для дебага
            'is_archive',
            'is_archive_gc'
        ])
            ->with('aircrafts', function ($query) {
                $query->select([
                    'n_forms_id',
                    'is_main',
                    'registration_number',
                    'aircraft_type_icao',
                    'n_form_aircraft_owner_id'
                ]);
            })
            ->with('airline')
            ->with('flights', function ($query) {
                $query
                    ->with('departureAirport', function ($query) {
                        $query->select('AIRPORTS_ID', 'ICAOLAT4');
                    })
                    ->with('landingAirport', function ($query) {
                        $query->select('AIRPORTS_ID', 'ICAOLAT4');
                    });
            })
            ->with('transportationCategories')
            ->with('airnavPayer', function ($query) {
                $query->select([
                    'n_forms_id',
                    'is_paid',
                ]);
            })
            ->with('departureDates', function ($query) {
                $query->select([
                    'departure_dates_id',
                    'departure_dates.n_form_flight_id',
                    'date',
                ]);
            })
            ->with('passengersQuantity', function ($query) {
                $query->select([
                    'n_form_passengers.n_form_passengers_id',
                    'n_form_passengers.n_form_flight_id',
                    'quantity',
                ]);
            })
            ->withSum('weight', 'weight');

        /* search */
        (new SearchHandler($nForms, $request))->handle();

        /* sorting */
        (new SortingHandler($nForms, $request))->handle();

        /* filtering */
        (new FiltersHandler($nForms, $request))->handle();

        /* show flights by role */
        (new CheckingAccessForFlightsHandler($nForms))->handle();

        //signs count
        $countSigns = NFormFlightSign::withCount(['agreementSigns' => function ($query) use ($request){
            $query->where('role_id', Auth::user()->active_role_id)
                ->whereHas('flights', function ($query) use ($request) {
                    $query->whereHas('nForm', function ($query) use ($request){
                        if ($request->has('is_archive') && $request->get('is_archive') == true && Auth::user()->active_role_id == 3 && Auth::user()->active_role_id !== null) {
                            $query->where('is_archive','=',1);
                        }
                        if ($request->has('is_archive') && $request->get('is_archive') == true && Auth::user()->active_role_id != 3 && Auth::user()->active_role_id !== null) {
                            $query->where('is_archive_gc','=',1);
                        }
                        if(!$request->has('is_archive')){
                            if (Auth::user()->active_role_id == 3) {
                                $query->where('is_archive', '=', 0);
                            }

                            if (Auth::user()->active_role_id != 3 && Auth::user()->active_role_id !== null) {
                                $query->where('is_archive_gc', '=', 0);
                            }
                        }
                        $query
                            ->where('is_latest', '!=', 0)
                            ->where(function ($query) {
                                $query->where('form_status_id', '!=', 0)
                                    ->orWhereNull('form_status_id');
                            });
                    });
                });
        }])->get();

        // statuses count

        $countStatuses = NFormFlightStatus::withCount(['flights' => function ($query) use ($request) {
            $query->where(function($query) use ($request){
                $query->whereHas('agreementSigns', function ($query) use ($request){
                    $query->where('role_id', Auth::user()->active_role_id);
                })->orWhereHas('nForm', function($query) use ($request){
                        $query->where('author_id',Auth::id());
                    });
            })->whereHas('nForm', function ($query) use ($request) {
                    if ($request->has('is_archive') && $request->get('is_archive') == true && Auth::user()->active_role_id == 3 && Auth::user()->active_role_id !== null) {
                        $query->where('is_archive','=',1);
                    }
                    if ($request->has('is_archive') && $request->get('is_archive') == true && Auth::user()->active_role_id != 3 && Auth::user()->active_role_id !== null) {
                        $query->where('is_archive_gc','=',1);
                    }
                    if(!$request->has('is_archive')){
                        if (Auth::user()->active_role_id == 3) {
                            $query->where('is_archive', '=', 0);
                        }

                        if (Auth::user()->active_role_id != 3 && Auth::user()->active_role_id !== null) {
                            $query->where('is_archive_gc', '=', 0);
                        }
                    }
                    $query
                        ->where('is_latest', '!=', 0)
                        ->where(function ($query) {
                            $query->where('form_status_id', '!=', 0)
                                ->orWhereNull('form_status_id');
                        });
            });
        }])->get();



        /* get all columns with / without pagination */
        if (!is_null($request->get('count'))) {
            if ($request->get('count') == 'all') {
                $nForms = $nForms->get();
            }
            if ((int)$request->get('count') !== 0) {
                $nForms = $nForms->paginate($request->get('count'));

                return response()->json([
                    'NForms' => $nForms,
                    'signs' => $countSigns,
                    'statuses' => $countStatuses,
                ]);
            }
        } else {
            $nForms = $nForms->get();
        }


        return response()->json([
            'NForms' => [
                'data' => $nForms,
                'signs' => $countSigns,
                'statuses' => $countStatuses
            ],
        ]);
    }

    public function getArchivedRegistry(Request $request): JsonResponse
    {
        $nForms = NForm::query();

        if (Auth::id() !== null) {
            $nForms->where('author_id', Auth::id());
        }


        $nForms->select([
            'n_forms_id',
            'id_pakus',
            'permit_num',
            'created_at',
            'updated_at',
            'form_status_id',
        ])
            ->where('form_status_id', '=', 0)
            ->with('aircrafts', function ($query) {
                $query->select([
                    'n_forms_id',
                    'is_main',
                    'registration_number',
                    'aircraft_type_icao',
                    'n_form_aircraft_owner_id'
                ]);
            })
            ->with('airline')
            ->with('flights', function ($query) {
                $query
                    ->with('departureAirport', function ($query) {
                        $query->select('AIRPORTS_ID', 'ICAOLAT4');
                    })
                    ->with('landingAirport', function ($query) {
                        $query->select('AIRPORTS_ID', 'ICAOLAT4');
                    });
            })
            ->with('transportationCategories')
            ->with('airnavPayer', function ($query) {
                $query->select([
                    'n_forms_id',
                    'is_paid',
                ]);
            })
            ->with('departureDates', function ($query) {
                $query->select([
                    'departure_dates_id',
                    'departure_dates.n_form_flight_id',
                    'date',
                ]);
            })
            ->with('passengersQuantity', function ($query) {
                $query->select([
                    'n_form_passengers.n_form_passengers_id',
                    'n_form_passengers.n_form_flight_id',
                    'quantity',
                ]);
            })
            ->withSum('weight', 'weight');

        /* search */
        (new SearchHandler($nForms, $request))->handle();

        /* sorting */
        (new SortingHandler($nForms, $request))->handle();

        /* filtering */
        (new FiltersHandler($nForms, $request))->handle();


        //signs count
        $countSigns = NFormFlightSign::withCount(['agreementSigns' => function ($query) {
            if (Auth::id() !== null) {
                $query->whereIn('role_id', $this->getRolesIdsFromUser());
            }
            $query->whereHas('flights', function ($query) {
                $query->whereHas('nForm', function ($query) {
                    $query->where('form_status_id', 0);
                });
            });
        }])->get();

        /* get all columns with / without pagination */
        if (!is_null($request->get('count'))) {
            if ($request->get('count') == 'all') {
                $nForms = $nForms->get();
            }
            if ((int)$request->get('count') !== 0) {
                $nForms = $nForms->paginate($request->get('count'));

                return response()->json([
                    'NForms' => $nForms,
                    'signs' => $countSigns
                ]);
            }
        } else {
            $nForms = $nForms->get();
        }

        return response()->json([
            'NForms' => [
                'data' => $nForms,
                'signs' => $countSigns
            ],
        ]);
    }

    /**
     * Архивируем форму в зависимости от роли пользователя
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function archivedNform(Request $request): JsonResponse
    {
        $decodedRequest = json_decode($request->getContent(), true);
        $nForm = NForm::find($decodedRequest['n_forms_id']);

        if (Auth::user()->active_role_id === null)
            return response()->json(['message' => 'No active role for current user'], 400, Headers::accessControlAllowOrigin());

        //Если пользователь является заявителем
        if (Auth::user()->active_role_id === 3) {
            $nForm->update([
                'is_archive' => $decodedRequest['is_archive'] // 1 для архивации, 0 для разархивации
            ]);
        }

        if (Auth::user()->active_role_id !== 3 && Auth::user()->active_role_id !== null) {
            $nForm->update([
                'is_archive_gc' => $decodedRequest['is_archive']
            ]);
        }

        return response()->json([
            'message' => 'Status changed successfully'
        ]);
    }

    public function getTableColumns($userId): JsonResponse
    {
        $userSetting = Setting::where('user_id', $userId)
            ->where('name', 'NRegistryColumns')
            ->first();

        if ($userSetting === null) {
            $setting = new Setting();
            $setting->name = 'NRegistryColumns';
            $setting->user_id = $userId;
            $setting->save();
            $settingParamter = new SettingParameter();
            $settingParamter->key = 'NRegistryColumns';
            $settingParamter->value = json_encode($this->getColums(), JSON_UNESCAPED_UNICODE);
            $settingParamter->setting_id = $setting->id;
            $settingParamter->save();
            return response()->json([
                'registryNcolumns' => json_decode($settingParamter['value']),
            ], 200, [], JSON_UNESCAPED_UNICODE);
        } else {
            $registryNcolumns = SettingParameter::where('setting_id', $userSetting['id'])->first();

            return response()->json([
                'registryNcolumns' => json_decode($registryNcolumns['value']),
            ], 200, [], JSON_UNESCAPED_UNICODE);
        }
    }

    public function changeTableColumns(Request $request)
    {
        $userSetting = Setting::where('user_id', $request['userId'])
            ->where('name', 'NRegistryColumns')
            ->first();

        $registryNcolumns = SettingParameter::where('setting_id', $userSetting['id'])->first();
        $registryNcolumns['value'] = $request->get('value');

        $registryNcolumns->save();

        return response()->json([
            'statuse' => true,
            'message' => 'Обновлен'
        ], 200, []);
    }

    protected function getRolesIdsFromUser(): array
    {
        $userWithRoles = User::where('id', Auth::id())->with('roles')->first()->toArray()['roles'];
        $roleIds = [];

        foreach ($userWithRoles as $userWithRole) {
            $roleIds[] += $userWithRole['id'];
        }

        return $roleIds;
    }

    private function getColums()
    {
        return [
            ["name" => 'checkbox', "possition" => 0, "isActive" => false],
            ["name" => 'icon', "possition" => 1, "isActive" => true],
            ["name" => 'permit_num', "possition" => 2, "isActive" => true],
            ["name" => 'sent_date_time', "possition" => 3, "isActive" => true],
            ["name" => 'airline', "possition" => 4, "isActive" => true],
            ["name" => 'registration_number', "possition" => 5, "isActive" => true],
            ["name" => 'aircraft_type', "possition" => 6, "isActive" => true],
            ["name" => 'road', "possition" => 7, "isActive" => true],
            ["name" => 'owner', "possition" => 8, "isActive" => true],
            ["name" => 'ano_payment', "possition" => 9, "isActive" => true],
            ["name" => 'landing_type', "possition" => 10, "isActive" => true],
            ["name" => 'flight_number', "possition" => 11, "isActive" => true],
            ["name" => 'purpose_of_transportation', "possition" => 12, "isActive" => true],
            ["name" => 'transportation_categories', "possition" => 13, "isActive" => true],
            ["name" => 'point_exit', "possition" => 14, "isActive" => true],
            ["name" => 'point_entry', "possition" => 15, "isActive" => true],
            ["name" => 'date', "possition" => 16, "isActive" => true],
            ["name" => 'departure_time', "possition" => 17, "isActive" => true],
            ["name" => 'landing_time', "possition" => 18, "isActive" => true],
            ["name" => 'crew_quantity', "possition" => 19, "isActive" => true],
            ["name" => 'quantity', "possition" => 20, "isActive" => true],
            ["name" => 'load_total_weight',"possition" => 21, "isActive" => true],
            ["name" => 'status_change_time', "possition" => 22, "isActive" => true],
            ["name" => 'status', "possition" => 23, "isActive" => true],
            ["name" => 'act', "possition" => 24, "isActive" => true],

        ];
    }

    public function getCountSigns(Request $request): JsonResponse
    {

        //signs count
        $countSigns = NFormFlightSign::withCount(['agreementSigns' => function ($query) use ($request){
            $query->where('role_id', Auth::user()->active_role_id)
                ->whereHas('flights', function ($query) use ($request) {
                    $query->whereHas('nForm', function ($query) use ($request){
                        if ($request->has('is_archive') && $request->get('is_archive') == true && Auth::user()->active_role_id == 3 && Auth::user()->active_role_id !== null) {
                            $query->where('is_archive','=',1);
                        }
                        if ($request->has('is_archive') && $request->get('is_archive') == true && Auth::user()->active_role_id != 3 && Auth::user()->active_role_id !== null) {
                            $query->where('is_archive_gc','=',1);
                        }
                        if(!$request->has('is_archive')){
                            if (Auth::user()->active_role_id == 3) {
                                $query->where('is_archive', '=', 0);
                            }

                            if (Auth::user()->active_role_id != 3 && Auth::user()->active_role_id !== null) {
                                $query->where('is_archive_gc', '=', 0);
                            }
                        }
                        $query
                            ->where('is_latest', '!=', 0)
                            ->where(function ($query) {
                                $query->where('form_status_id', '!=', 0)
                                    ->orWhereNull('form_status_id');
                            });
                    });
                });
        }])->get();

        return response()->json([
            'signs' => [
                'data' => $countSigns,
            ],
        ]);
    }
}
