<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\FormN\FormNStoreRequest;
use App\Models\Aircraft;
use App\Models\Airline;
use App\Models\Airport;
use App\Models\File;
use App\Models\FlightCategory;
use App\Models\FormN;
use App\Models\From;
use App\Models\FromNLanding;
use App\Models\LandingStatus;
use App\Models\Point;
use App\Models\RouteEntryExitPoint;
use App\Models\Status;
use App\Models\User;
use Database\Seeders\LandingStatusSeeder;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\RequestHandlers\SearchHandler;

class IndexController extends Controller
{
    /**
     * @OA\Get(
     *     path="/index",
     *     operationId="GetFormN",
     *     tags={"FormN"},
     *     summary="Get all FormN data with pagination",
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="The page number",
     *         required=false,
     *         @OA\Schema(
     *              type="integer",
     *         )
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="Successful getting data"
     *     )
     * )
     * @return JsonResponse
     */
    public function show(Request $request): JsonResponse
    {
        $counts = [
            'id_1' => FormN::where('status_id', 1)->count(),
            'id_2' => FormN::where('status_id', 2)->count(),
            'id_3' => FormN::where('status_id', 3)->count(),
            'id_4' => FormN::where('status_id', 4)->count(),
        ];

        $formN = FormN::query()
            ->select([
                'id_pakus',
                'from_id_from',
                'status_id',
                'status_change_datetime',
                'access',
                'airline_id',
                'flight_num',
                'main_aircraft_registration_mark',
                'route_departure_datetime',
                'route_departure_airport_id',
                'route_arrival_datetime',
                'route_arrival_airport'
            ])
            ->with('from', function ($query) {
                $query->select(['id_from', 'author_id', 'create_datetime', 'is_create_inside']);
            })
            ->with('user', function ($query) {
                $query->select([
                    'id',
                    'user_role_id',
                    'email',
                    'name',
                    'patronymic',
                    'surname',
                ])->with('role', function ($query) {
                    $query->select(['id', 'name']);
                });
            })
            ->with('aircrafts', function ($query) {
                $query->select(['AIRCRAFT_ID', 'NAMERUS']);
            })
            ->with('flightCategories', function ($query) {
                $query->select(['id_flight_category', 'flight_category_name', 'is_commercial']);
            })
            ->with('airlines', function ($query) {
                $query->select(['AIRLINES_ID', 'FULLNAMERUS', 'FULLNAMELAT', 'ICAOLAT3']);
            })
            ->with('departureAirports', function ($query) {
                $query->select(['AIRPORTS_ID', 'NAMERUS', 'ICAOLAT4']);
            })
            ->with('arrivalAirports', function ($query) {
                $query->select(['AIRPORTS_ID', 'NAMERUS', 'ICAOLAT4']);
            })
            ->with('routeEntryExitPoints')
            ->with('landings', function ($query) {
                $query->select([
                    'idl_pakus',
                    'form_n_id_pakus',
                    'landing_status_id',
                    'landing_type_id',
                    'arrivial_point_AIRPORT_ID',
                    'departure_datetime',
                    'arrival_datetime'
                ]);
            })
            ->with('airport', function ($query) {
                $query->select(['AIRPORTS_ID', 'NAMERUS']);
            })
            ->with('landingStatus')
            ->with('landingType')
            ->with('files', function ($query) {
                $query->select(['id_pakus', 'file_name']);
            })
            ->with('point', function ($query) {
                $query->select(['POINTS_ID', 'NAMERUS']);
            })
            ->with('statuses');

        $this->filtering($formN);
        (new SearchHandler($formN, \request()))->handle();

        if (!is_null(\request('quantity')) && \request('quantity') == 'all') {
            $formN = $formN->get();

            return response()->json([
                'formN' => [
                    'data' => $formN,
                ],
                'counts' => $counts
            ]);
        }

        $formN = $formN->paginate(50);

        return response()->json([
            'formN' => $formN,
            'counts' => $counts
        ]);
    }

    protected function filtering(Builder $queryBuilder)
    {
        /* status filters */
        if (!is_null(\request()->get('status_id'))) {
            $statuses = explode(',', \request()->get('status_id'));

            if (!in_array('all', $statuses)) {
                $queryBuilder->whereHas('statuses', function (Builder $query) use ($statuses) {
                    $query->whereIn('id', $statuses);
                });
            }
        }

        /* Номер формы */
        if (!is_null(\request('main_aircraft_registration_mark'))) {
            $request = \request('main_aircraft_registration_mark');
            $sort = $request["'sort'"] ?? '';

            if (array_key_exists("'params'", $request)) {
                $params = $this->parseParams($request["'params'"]);

                $queryBuilder->whereIn('id_pakus', $params);
            }

            if (str_starts_with($sort, 'asc')) {
                $queryBuilder->orderBy('id_pakus');
            }

            if (str_starts_with($sort, '-')) {
                $queryBuilder->orderByDesc('id_pakus');
            }
        }

        /* Дата и время отправки */
        if (!is_null(\request('create_datetime'))) {
            $request = \request('create_datetime');
            $sort = $request["'sort'"] ?? '';

            if (array_key_exists("'params'", $request)) {
                $params = $this->parseParams($request["'params'"]);
                $dateFrom = Carbon::createFromTimestamp($params[0], 'MSK')->toDateTime();
                $dateTo = Carbon::createFromTimestamp($params[1], 'MSK')->toDateTime();

                $queryBuilder->whereHas('from', function ($query) use ($dateFrom, $dateTo) {
                    $query->whereBetween('create_datetime', [$dateFrom, $dateTo]);
                });
            }

            if (str_starts_with($sort, 'asc')) {
                $queryBuilder->orderBy($this->customSortFromByCreateDateTime());
            }
            if (str_starts_with($sort, '-')) {
                $queryBuilder->orderByDesc($this->customSortFromByCreateDateTime());
            }

        }

        /* Эксплуатант */
        if (!is_null(\request('FULLNAMERUS'))) {
            $request = \request('FULLNAMERUS');
            $sort = $request["'sort'"] ?? '';

            if (array_key_exists("'params'", $request)) {
                $params = $this->parseParams($request["'params'"]);

                $queryBuilder->whereIn('id_pakus', $params);
            }

            if (str_starts_with($sort, 'asc')) {
                $queryBuilder->orderBy($this->customSortAirlinesByFullNameRus());
            }

            if (str_starts_with($sort, '-')) {
                $queryBuilder->orderByDesc($this->customSortAirlinesByFullNameRus());
            }
        }

        /* Номер рейса */
        if (!is_null(\request('flight_num'))) {
            $request = \request('flight_num');
            $sort = $request["'sort'"] ?? '';

            if (array_key_exists("'params'", $request)) {
                $params = $this->parseParams($request["'params'"]);

                $queryBuilder->whereIn('id_pakus', $params);
            }

            if (str_starts_with($sort, 'asc')) {
                $queryBuilder->orderBy('flight_num');
            }

            if (str_starts_with($sort, '-')) {
                $queryBuilder->orderByDesc('flight_num');
            }
        }

        /* Время вылета */
        //@TODO Разобраться, что не так с route_departure_datetime
        if (!is_null(\request('route_departure_datetime'))) {
            $request = \request('route_departure_datetime');
            $sort = $request["'sort'"] ?? '';

            if (array_key_exists("'params'", $request)) {
                $params = $this->parseParams($request["'params'"]);
                $timeFrom = Carbon::createFromTimestamp($params[0], 'MSK')->toTimeString();
                $timeTo = Carbon::createFromTimestamp($params[1], 'MSK')->toTimeString();

                $queryBuilder->whereRaw("cast (route_departure_datetime::timestamp as time) BETWEEN ? AND ?", [$timeFrom, $timeTo]);
            }

            if (str_starts_with($sort, 'asc')) {
                $queryBuilder->orderByRaw('route_departure_datetime::timestamp::time ASC');
            }

            if (str_starts_with($sort, '-')) {
                $queryBuilder->orderByRaw('route_departure_datetime::timestamp::time DESC');
            }
        }

        /* Время посадки */
        if (!is_null(\request('route_arrival_datetime'))) {
            $request = \request('route_arrival_datetime');
            $sort = $request["'sort'"] ?? '';

            if (array_key_exists("'params'", $request)) {
                $params = $this->parseParams($request["'params'"]);
                $timeFrom = Carbon::createFromTimestamp($params[0], 'MSK')->toTimeString();
                $timeTo = Carbon::createFromTimestamp($params[1], 'MSK')->toTimeString();

                $queryBuilder->whereRaw("cast (route_arrival_datetime::timestamp as time) BETWEEN ? AND ?", [$timeFrom, $timeTo]);
            }

            if (str_starts_with($sort, 'asc')) {
                $queryBuilder->orderByRaw('route_arrival_datetime::timestamp::time ASC');
            }

            if (str_starts_with($sort, '-')) {
                $queryBuilder->orderByRaw('route_arrival_datetime::timestamp::time DESC');
            }
        }

        /* Точка входа в ВП РФ */
        if (!is_null(\request('point_entry'))) {
            $request = \request('point_entry');
            $sort = $request["'sort'"] ?? '';

            if (array_key_exists("'params'", $request)) {
                $params = $this->parseParams($request["'params'"]);

                $queryBuilder->whereHas('point', function (Builder $query) use ($params) {
                    $query->where('in_out', 0);
                })->whereIn('id_pakus', $params);
            }

            if (str_starts_with($sort, 'asc')) {
                $queryBuilder->orderBy($this->customSortPointsByNameRus());
            }

            if (str_starts_with($sort, '-')) {
                $queryBuilder->orderByDesc($this->customSortPointsByNameRus());

            }
        }

        /* Время входа в ВП РФ */
        if (!is_null(\request('entry_time'))) {
            $request = \request('entry_time');
            $sort = $request["'sort'"] ?? '';

            if (array_key_exists("'params'", $request)) {
                $params = $this->parseParams($request["'params'"]);
                $timeFrom = Carbon::createFromTimestamp($params[0], 'MSK')->toTimeString();
                $timeTo = Carbon::createFromTimestamp($params[1], 'MSK')->toTimeString();

                $queryBuilder->whereHas('routeEntryExitPoints', function ($query) use ($timeFrom, $timeTo) {
                    $query->where('in_out', 0)->whereRaw("cast (datetime::timestamp as time) BETWEEN ? AND ?", [$timeFrom, $timeTo]);
                });
            }

            if (str_starts_with($sort, 'asc')) {
                $this->customSortRouteEntryExitpointsByTimeASC($queryBuilder);
            }

            if (str_starts_with($sort, '-')) {
                $this->customSortRouteEntryExitpointsByTimeDESC($queryBuilder);
            }
        }

        /* Точка выхода из ВП РФ */
        if (!is_null(\request('point_exit'))) {
            $request = \request('point_exit');
            $sort = $request["'sort'"] ?? '';

            if (array_key_exists("'params'", $request)) {
                $params = $this->parseParams($request["'params'"]);

                $queryBuilder->whereHas('point', function (Builder $query) use ($params) {
                    $query->where('in_out', 1);
                })->whereIn('id_pakus', $params);
            }

            if (str_starts_with($sort, 'asc')) {
                $queryBuilder->orderBy($this->customSortPointsByNameRus());
            }

            if (str_starts_with($sort, '-')) {
                $queryBuilder->orderByDesc($this->customSortPointsByNameRus());

            }
        }

        /* Время выхода из ВП РФ */
        if (!is_null(\request('exit_time'))) {
            $request = \request('exit_time');
            $sort = $request["'sort'"] ?? '';

            if (array_key_exists("'params'", $request)) {
                $params = $this->parseParams($request["'params'"]);
                $timeFrom = Carbon::createFromTimestamp($params[0], 'MSK')->toTimeString();
                $timeTo = Carbon::createFromTimestamp($params[1], 'MSK')->toTimeString();

                $queryBuilder->whereHas('routeEntryExitPoints', function ($query) use ($timeFrom, $timeTo) {
                    $query->where('in_out', 1)->whereRaw("cast (datetime::timestamp as time) BETWEEN ? AND ?", [$timeFrom, $timeTo]);
                });
            }

            if (str_starts_with($sort, 'asc')) {
                $this->customSortRouteEntryExitpointsByTimeASC($queryBuilder);
            }

            if (str_starts_with($sort, '-')) {
                $this->customSortRouteEntryExitpointsByTimeDESC($queryBuilder);
            }
        }

        /* Прикреплённые файлы */
        if (!is_null(\request('file_name'))) {
            $request = \request('file_name');
            $sort = $request["'sort'"] ?? '';
            $params = null;

            if (array_key_exists("'params'", $request)) {
                $params = $this->parseParams($request["'params'"]);

                $queryBuilder->whereHas('files', function (Builder $query) use ($params) {
                    $query->whereIn('id_pakus', $params)->orderBy('file_name');
                });
            }

            //если переданы параметры, то возвращаем только искомый файл, иначе выводим все файлы для формы
            if (!is_null($params)) {
                $queryBuilder->with('files', function (HasManyThrough $query) use ($params) {
                    $query->select(['id_pakus', 'file_name'])->whereIn('id_pakus', $params);
                });
            }

            //сортировки
            if (str_starts_with($sort, 'asc')) {
                $queryBuilder->orderBy($this->customSortFilesByFileName());
            }

            if (str_starts_with($sort, '-')) {
                $queryBuilder->orderByDesc($this->customSortFilesByFileName());
            }

        }

        /* Владелец */
        if (!is_null(\request('user_name'))) {
            $request = \request('user_name');
            $sort = $request["'sort'"] ?? '';

            if (array_key_exists("'params'", $request)) {
                $params = $this->parseParams($request["'params'"]);

                $queryBuilder->whereIn('id_pakus', $params);
            }

            //Сортировка по имени т.к. в форме имя идёт первым
            if (str_starts_with($sort, 'asc')) {
                $queryBuilder->orderBy($this->customSortUsersByName());
            }

            if (str_starts_with($sort, '-')) {
                $queryBuilder->orderByDesc($this->customSortUsersByName());
            }
        }

        /* Способ подачи */
        if (!is_null(\request('delivery_method'))) {
            $request = \request('delivery_method');
            $sort = $request["'sort'"] ?? '';

            if (array_key_exists("'params'", $request)) {
                $params = $request["'params'"];

                $queryBuilder->whereHas('from', function (Builder $query) use ($params) {
                    $query->where('is_create_inside', $params);
                });
            }

            if (str_starts_with($sort, 'asc')) {
                $queryBuilder->orderBy($this->customSortFromByIsCreateInside());
            }

            if (str_starts_with($sort, '-')) {
                $queryBuilder->orderByDesc($this->customSortFromByIsCreateInside());
            }
        }

        /* Доступ */
        if (!is_null(\request('access'))) {
            $request = \request('access');
            $sort = $request["'sort'"] ?? '';

            if (array_key_exists("'params'", $request)) {
                $params = $this->parseParams($request["'params'"]);

                $queryBuilder->whereIn('id_pakus', $params);
            }

            if (str_starts_with($sort, 'asc')) {
                $queryBuilder->orderBy('access');
            }

            if (str_starts_with($sort, '-')) {
                $queryBuilder->orderByDesc('access');
            }
        }

        /* SID */
        if (!is_null(\request('id_pakus'))) {
            $request = \request('id_pakus');
            $sort = $request["'sort'"] ?? '';

            if (array_key_exists("'params'", $request)) {
                $params = $this->parseParams($request["'params'"]);

                $queryBuilder->whereBetween('id_pakus', $params);
            }

            if (str_starts_with($sort, 'asc')) {
                $queryBuilder->orderBy('id_pakus');
            }

            if (str_starts_with($sort, '-')) {
                $queryBuilder->orderByDesc('id_pakus');
            }
        }

        /* Время изменения статуса */
        if (!is_null(\request('time_status_change'))) {
            $request = \request('time_status_change');
            $sort = $request["'sort'"] ?? '';

            if (array_key_exists("'params'", $request)) {
                $params = $this->parseParams($request["'params'"]);
                $timeFrom = Carbon::createFromTimestamp($params[0], 'MSK')->toTimeString();
                $timeTo = Carbon::createFromTimestamp($params[1], 'MSK')->toTimeString();

                //По базе поиск с 05:31 по 10:31, ищет значения с 03:31 по 08:31 при tz=MSK.
                //Моя часова зона +5, при добавлении 5 часов при парсинге timestamp ищет в правильном диапазоне.
                $queryBuilder->whereRaw("cast (status_change_datetime::timestamp as time) BETWEEN ? AND ?", [$timeFrom, $timeTo]);
            }

            if (str_starts_with($sort, 'asc')) {
                $queryBuilder->orderByRaw('status_change_datetime::timestamp::time ASC');
            }

            if (str_starts_with($sort, '-')) {
                $queryBuilder->orderByRaw('status_change_datetime::timestamp::time DESC');
            }
        }

        /* Статус */
        if (!is_null(\request('status_id'))) {
            $request = \request('status_id');
            $sort = $request["'sort'"] ?? '';

            if (!is_string($request) && array_key_exists("'params'", $request)) {
                $params = $this->parseParams($request["'params'"]);

                $queryBuilder->whereIn('status_id', $params);

            }

            if (str_starts_with($sort, 'asc')) {
                $queryBuilder->orderBy('status_id');
            }

            if (str_starts_with($sort, '-')) {
                $queryBuilder->orderByDesc('status_id');
            }
        }

        /* Время отправления from_n_landing->departure_datetime */
        if (!is_null(\request('departure_time'))) {
            $request = \request('departure_time');
            $sort = $request["'sort'"] ?? '';

            if (array_key_exists("'params'", $request)) {
                $params = $this->parseParams($request["'params'"]);
                $timeFrom = Carbon::createFromTimestamp($params[0], 'MSK')->toTimeString();
                $timeTo = Carbon::createFromTimestamp($params[1], 'MSK')->toTimeString();

                $queryBuilder->whereHas('landings', function ($query) use ($timeFrom, $timeTo) {
                    $query->whereRaw("cast (departure_datetime::timestamp as time) BETWEEN ? AND ?", [$timeFrom, $timeTo]);
                });
            }

            if (str_starts_with($sort, 'asc')) {
                $this->customSortLandingsByDepartureTimeASC($queryBuilder);
            }

            if (str_starts_with($sort, '-')) {
                $this->customSortLandingsByDepartureTimeDESC($queryBuilder);
            }
        }

        /* Дата отправления from_n_landing->departure_datetime */
        if (!is_null(\request('departure_date'))) {
            $request = \request('departure_date');
            $sort = $request["'sort'"] ?? '';

            if (array_key_exists("'params'", $request)) {
                $params = $this->parseParams($request["'params'"]);
                $timeFrom = Carbon::createFromTimestamp($params[0], 'MSK')->toDateTimeString();
                $timeTo = Carbon::createFromTimestamp($params[1], 'MSK')->toDateTimeString();

                $queryBuilder->whereHas('landings', function ($query) use ($timeFrom, $timeTo) {
                    $query->whereBetween("departure_datetime", [$timeFrom, $timeTo]);
                });
            }

            if (str_starts_with($sort, 'asc')) {
                $queryBuilder->orderBy($this->customSortLandingsByDepartureDateTime());
            }

            if (str_starts_with($sort, '-')) {
                $queryBuilder->orderByDesc($this->customSortLandingsByDepartureDateTime());
            }
        }

        /* Время прибытия from_n_landing->arrival_datetime */
        if (!is_null(\request('arrival_time'))) {
            $request = \request('arrival_time');
            $sort = $request["'sort'"] ?? '';

            if (array_key_exists("'params'", $request)) {
                $params = $this->parseParams($request["'params'"]);
                $timeFrom = Carbon::createFromTimestamp($params[0], 'MSK')->toTimeString();
                $timeTo = Carbon::createFromTimestamp($params[1], 'MSK')->toTimeString();

                $queryBuilder->whereHas('landings', function ($query) use ($timeFrom, $timeTo) {
                    $query->whereRaw("cast (arrival_datetime::timestamp as time) BETWEEN ? AND ?", [$timeFrom, $timeTo]);
                });
            }

            if (str_starts_with($sort, 'asc')) {
                $this->customSortLandingsByArrivalTimeASC($queryBuilder);
            }

            if (str_starts_with($sort, '-')) {
                $this->customSortLandingsByArrivalTimeDESC($queryBuilder);
            }
        }

        /* Дата прибытия from_n_landing->arrival_datetime */
        if (!is_null(\request('arrival_date'))) {
            $request = \request('arrival_date');
            $sort = $request["'sort'"] ?? '';

            if (array_key_exists("'params'", $request)) {
                $params = $this->parseParams($request["'params'"]);
                $timeFrom = Carbon::createFromTimestamp($params[0], 'MSK')->toDateTimeString();
                $timeTo = Carbon::createFromTimestamp($params[1], 'MSK')->toDateTimeString();

                $queryBuilder->whereHas('landings', function ($query) use ($timeFrom, $timeTo) {
                    $query->whereBetween("arrival_datetime", [$timeFrom, $timeTo]);
                });
            }

            if (str_starts_with($sort, 'asc')) {
                $queryBuilder->orderBy($this->customSortLandingsByArrivalDateTime());
            }

            if (str_starts_with($sort, '-')) {
                $queryBuilder->orderBy($this->customSortLandingsByArrivalDateTime());
            }
        }
    }

    /* helpers */
    protected function parseParams(string $str): array
    {
        return explode(',', str_replace(['[', ']'], '', $str));
    }

    /* sorts */
    protected function customSortRouteEntryExitPointsByFormsNIdPakus(): Builder
    {
        return RouteEntryExitPoint::select('forms_n_id_pakus')
            ->whereColumn('forms_n_id_pakus', 'id_pakus')
            ->latest()
            ->take(1);
    }

    protected function customSortRouteEntryExitPointsByPointsId(): Builder
    {
        return RouteEntryExitPoint::select('POINTS_POINTS_ID')
            ->whereColumn('forms_n_id_pakus', 'id_pakus')
            ->latest()
            ->take(1);
    }

    protected function customSortRouteEntryExitpointsByTimeASC(Builder $builder)
    {
        return $builder->orderByRaw('(
                select "datetime" from "route_entry_exit_points"
                where "id_pakus" = "forms_n_id_pakus"
                order by datetime asc limit 1
                )
                ::timestamp::time ASC');
    }

    protected function customSortRouteEntryExitpointsByTimeDESC(Builder $builder)
    {
        return $builder->orderByRaw('(
                select "datetime" from "route_entry_exit_points"
                where "id_pakus" = "forms_n_id_pakus"
                order by datetime desc limit 1
                )
                ::timestamp::time DESC');
    }

    /* landings */
    protected function customSortLandingsByDepartureTimeASC(Builder $builder)
    {
        return $builder->orderByRaw('(
                select "departure_datetime" from "from_n_landing"
                where "id_pakus" = "form_n_id_pakus"
                order by departure_datetime asc limit 1
                )
                ::timestamp::time ASC');
    }

    protected function customSortLandingsByDepartureTimeDESC(Builder $builder)
    {
        return $builder->orderByRaw('(
                select "departure_datetime" from "from_n_landing"
                where "id_pakus" = "form_n_id_pakus"
                order by departure_datetime desc limit 1
                )
                ::timestamp::time DESC');
    }

    protected function customSortLandingsByDepartureDateTime()
    {
        return FromNLanding::select('departure_datetime')
            ->whereColumn('form_n_id_pakus', 'id_pakus')
            ->latest()
            ->take(1);
    }

    protected function customSortLandingsByArrivalTimeASC(Builder $builder)
    {
        return $builder->orderByRaw('(
                select "arrival_datetime" from "from_n_landing"
                where "id_pakus" = "form_n_id_pakus"
                order by arrival_datetime asc limit 1
                )
                ::timestamp::time ASC');
    }

    protected function customSortLandingsByArrivalTimeDESC(Builder $builder)
    {
        return $builder->orderByRaw('(
                select "arrival_datetime" from "from_n_landing"
                where "id_pakus" = "form_n_id_pakus"
                order by arrival_datetime desc limit 1
                )
                ::timestamp::time DESC');
    }

    protected function customSortLandingsByArrivalDateTime()
    {
        return FromNLanding::select('arrival_datetime')
            ->whereColumn('form_n_id_pakus', 'id_pakus')
            ->latest()
            ->take(1);
    }

    protected function customSortStatusesByName(): Builder
    {
        return Status::select('name')
            ->whereColumn('id', 'status_id')
            ->latest()
            ->take(1);
    }

    protected function customSortAirlinesByFullNameRus(): Builder
    {
        return Airline::select('FULLNAMERUS')
            ->whereColumn('AIRLINES_ID', 'airline_id')
            ->latest()
            ->take(1);
    }

    protected function customSortFromByAuthorId(): Builder
    {
        return From::select('author_id')
            ->whereColumn('id_from', 'from_id_from')
            ->latest()
            ->take(1);
    }

    protected function customSortFromByIsCreateInside(): Builder
    {
        return From::select('is_create_inside')
            ->whereColumn('id_from', 'from_id_from')
            ->latest()
            ->take(1);
    }

    protected function customSortFromByCreateDateTime(): Builder
    {
        return From::select('create_datetime')
            ->whereColumn('id_from', 'from_id_from')
            ->latest()
            ->take(1);
    }

    /* AIRCRAFT */
    protected function customSortAircrafts(): Builder
    {
        return Aircraft::select('AIRCRAFT_ID')
            ->join('alternative_aircrafts_types', 'alternative_aircrafts_types.aircraft_type_AIRCRAFT_ID', '=', 'AIRCRAFT.AIRCRAFT_ID')
            ->whereColumn('alternative_aircrafts_types.forms_n_id_pakus', 'forms_n.id_pakus')
            ->latest('alternative_aircrafts_types.id')
            ->take(1);
    }

    protected function customSortAircraftsByNameRus(): Builder
    {
        return Aircraft::select('NAMERUS')
            ->join('alternative_aircrafts_types', 'alternative_aircrafts_types.aircraft_type_AIRCRAFT_ID', '=', 'AIRCRAFT.AIRCRAFT_ID')
            ->whereColumn('alternative_aircrafts_types.forms_n_id_pakus', 'forms_n.id_pakus')
            ->latest('alternative_aircrafts_types.id')
            ->take(1);
    }

    /* flight_categories */
    protected function customSortFlightCategories(): Builder
    {
        return FlightCategory::select('id_flight_category')
            ->join('forms_n_has_flight_categories', 'forms_n_has_flight_categories.flight_categories_id_flight_category', '=', 'flight_categories.id_flight_category')
            ->whereColumn('forms_n_has_flight_categories.forms_n_id_pakus', 'forms_n.id_pakus')
            ->latest('forms_n_has_flight_categories.id')
            ->take(1);
    }

    protected function customSortFlightCategoriesByCategoryName(): Builder
    {
        return FlightCategory::select('flight_category_name')
            ->join('forms_n_has_flight_categories', 'forms_n_has_flight_categories.flight_categories_id_flight_category', '=', 'flight_categories.id_flight_category')
            ->whereColumn('forms_n_has_flight_categories.forms_n_id_pakus', 'forms_n.id_pakus')
            ->latest('forms_n_has_flight_categories.id')
            ->take(1);
    }

    /* departure airports */
    protected function customSortDepartureAirportsByNameRus(): Builder
    {
        return Airport::select('NAMERUS')
            ->whereColumn('AIRPORTS_ID', 'route_departure_airport_id')
            ->latest()
            ->take(1);
    }

    /* arrival airports */
    protected function customSortArrivalAirportsByNameRus(): Builder
    {
        return Airport::select('NAMERUS')
            ->whereColumn('AIRPORTS_ID', 'route_arrival_airport')
            ->latest()
            ->take(1);
    }

    /* files */
    protected function customSortFilesByFileName(): Builder
    {
        return File::select('file_name')
            ->join('froms', 'froms.id_from', '=', 'files.id_from')
            ->whereColumn('froms.id_from', 'forms_n.from_id_from')
            ->latest('files.file_name')
            ->take(1);
    }

    /* routeEntryExitPoints->points */
    protected function customSortPointsByNameRus()
    {
        return Point::select('NAMERUS')
            ->join('route_entry_exit_points', 'route_entry_exit_points.POINTS_POINTS_ID', '=', 'POINTS.POINTS_ID')
            ->whereColumn('route_entry_exit_points.forms_n_id_pakus', 'forms_n.id_pakus')
            ->latest('POINTS.NAMERUS')
            ->take(1);
    }

    /* users */
    protected function customSortUsersByName()
    {
        return User::select('name')
            ->join('froms', 'froms.author_id', '=', 'users.id')
            ->whereColumn('froms.id_from', 'forms_n.from_id_from')
            ->latest('users.name')
            ->take(1);
    }
}
