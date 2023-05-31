<?php /** @noinspection PhpMultipleClassDeclarationsInspection */

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\DepartureDate;
use App\Models\NFormFlightSign;
use App\Models\NFormFlightStatus;
use App\Models\SettingParameter;
use App\Models\Setting;
use App\RequestHandlers\RegistryNDatesCheckingAccessForFlightsHandler;
use App\RequestHandlers\RegistryNDatesFiltersHandler;
use App\RequestHandlers\RegistryNDatesSearchHandler;
use App\RequestHandlers\RegistryNDatesSortingHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Log;

class RegistryNDatesController extends Controller
{
    public function getRegistryAsDates(Request $request): JsonResponse
    {
        $registryAsDates = DepartureDate::query();

        $registryAsDates->whereNotNull('date');

        $registryAsDates->with('nFormFlight', function ($query) {
            //flight selects
            $query
                ->with('crew')
                ->with('departureAirport')
                ->with('landingAirport')
                ->with('points')
                ->with('cargos')
                ->withSum('cargos as cargos_sum_weight', 'weight')
                ->with('passengers')
                ->with('status')
                ->with('datesDocuments')
                ->with('transportationCategory')
                ->with('agreementSigns', function ($query) {
                    $query->with('sign');
                });

            //nForm selects
            $query
                ->with('nForm', function ($query) {
                    $query
                        ->where('is_latest', 1);

                    if (\request()->has('is_archive') && \request()->get('is_archive') == true) {
                        if (Auth::user()->active_role_id == 3) {
                            $query->where('is_archive', '=', 1);
                        }

                        if (Auth::user()->active_role_id != 3 && Auth::user()->active_role_id !== null) {
                            $query->where('is_archive_gc', '=', 1);
                        }
                    } else {
                        if (Auth::user()->active_role_id == 3) {
                            $query->where('is_archive', '=', 0);
                        }

                        if (Auth::user()->active_role_id != 3 && Auth::user()->active_role_id !== null) {
                            $query->where('is_archive_gc', '=', 0);
                        }
                    }

                    $query
                        ->with('airline')
                        ->with('aircrafts')
                        ->with('airnavPayer')
                        ->with('transportationCategories')
                        ->with('passengersQuantity');
                });
        });

        $registryAsDates->whereHas('nFormFlight.nForm', function ($query) {
            $query->where('is_latest', 1);

            if (\request()->has('is_archive') && \request()->get('is_archive') == true) {
                if (Auth::user()->active_role_id == 3) {
                    $query->where('is_archive', '=', 1);
                }

                if (Auth::user()->active_role_id != 3 && Auth::user()->active_role_id !== null) {
                    $query->where('is_archive_gc', '=', 1);
                }
            } else {
                if (Auth::user()->active_role_id == 3) {
                    $query->where('is_archive', '=', 0);
                }

                if (Auth::user()->active_role_id != 3 && Auth::user()->active_role_id !== null) {
                    $query->where('is_archive_gc', '=', 0);
                }
            }
        });

        //поиск
        (new RegistryNDatesSearchHandler($registryAsDates, $request))->handle();

        //фильтры
        (new RegistryNDatesFiltersHandler($registryAsDates, $request))->handle();

        //сортировки
        (new RegistryNDatesSortingHandler($registryAsDates, $request))->handle();

        //выдача рейсов согласно роли
        (new RegistryNDatesCheckingAccessForFlightsHandler($registryAsDates))->handle();

        if ($request->has('count')) {
            $registryAsDates = $registryAsDates->paginate($request->get('count'));
        } else {
            $registryAsDates = $registryAsDates->get();
        }

        //signs count
        $countSigns = NFormFlightSign::withCount(['agreementSigns' => function ($query) use ($request) {
            $query->where('role_id', Auth::user()->active_role_id)
                ->whereHas('flights', function ($query) use ($request) {
                    $query->whereHas('nForm', function ($query) use ($request) {
                        if ($request->has('is_archive') && $request->get('is_archive') == true && Auth::user()->active_role_id == 3 && Auth::user()->active_role_id !== null) {
                            $query->where('is_archive', '=', 1);
                        }
                        if ($request->has('is_archive') && $request->get('is_archive') == true && Auth::user()->active_role_id != 3 && Auth::user()->active_role_id !== null) {
                            $query->where('is_archive_gc', '=', 1);
                        }
                        if (!$request->has('is_archive')) {
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
            $query->where(function ($query) use ($request) {
                $query->whereHas('agreementSigns', function ($query) use ($request) {
                    $query->where('role_id', Auth::user()->active_role_id);
                })->orWhereHas('nForm', function ($query) use ($request) {
                    $query->where('author_id', Auth::id());
                });
            })->whereHas('nForm', function ($query) use ($request) {
                if ($request->has('is_archive') && $request->get('is_archive') == true && Auth::user()->active_role_id == 3 && Auth::user()->active_role_id !== null) {
                    $query->where('is_archive', '=', 1);
                }
                if ($request->has('is_archive') && $request->get('is_archive') == true && Auth::user()->active_role_id != 3 && Auth::user()->active_role_id !== null) {
                    $query->where('is_archive_gc', '=', 1);
                }
                if (!$request->has('is_archive')) {
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

        return response()->json([
            'NForms' => [
                'data' => $registryAsDates,
                'signs' => $countSigns,
                'statuses' => $countStatuses,
            ]
        ]);
    }

    public function getTableColumns($userId): JsonResponse
    {
        $userSetting = Setting::where('user_id', $userId)
            ->where('name', 'n_retry_registry_columns')
            ->first();

        if ($userSetting === null) {
            $setting = new Setting();
            $setting->name = 'n_retry_registry_columns';
            $setting->user_id = $userId;
            $setting->save();
            $settingParamter = new SettingParameter();
            $settingParamter->key = 'n_retry_registry_columns';
            $settingParamter->value = json_encode($this->getColums(), JSON_UNESCAPED_UNICODE);
            $settingParamter->setting_id = $setting->id;
            $settingParamter->save();
            return response()->json([
                'NRetryRegistryColumns' => json_decode($settingParamter['value']),
            ], 200, [], JSON_UNESCAPED_UNICODE);
        } else {
            $NRetryRegistryColumns = SettingParameter::where('setting_id', $userSetting['id'])->first();

            return response()->json([
                'NRetryRegistryColumns' => json_decode($NRetryRegistryColumns['value']),
            ], 200, [], JSON_UNESCAPED_UNICODE);
        }
    }

    public function changeTableColumns(Request $request)
    {
        $userSetting = Setting::where('user_id', $request['userId'])
            ->where('name', 'n_retry_registry_columns')
            ->first();

        $NRetryRegistryColumns = SettingParameter::where('setting_id', $userSetting['id'])->first();
        $NRetryRegistryColumns['value'] = $request->get('value');

        $NRetryRegistryColumns->save();

        return response()->json([
            'statuse' => true,
            'message' => 'Updated!'
        ], 200, []);
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
            ["name" => 'load_total_weight', "possition" => 21, "isActive" => true],
            ["name" => 'status_change_time', "possition" => 22, "isActive" => true],
            ["name" => 'status', "possition" => 23, "isActive" => true],
            ["name" => 'act', "possition" => 24, "isActive" => true],

        ];
    }
}
