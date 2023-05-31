<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\FlightDirection\FlightDirectionRequest;
use App\Models\AirlineFlightDirectionApproval;
use App\Models\AirlineFlightDirectionLimit;
use App\Models\FlightDirection;
use App\Models\Season;
use App\RequestHandlers\FlightDirectionFilterHandler;
use App\RequestHandlers\FlightDirectionSortingHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class FlightDirectionController extends Controller
{
    public function detail(): JsonResponse
    {
        $this->calculation();

        /* Направления без авиакомпаний */
        $flightDirections = FlightDirection::query()
            ->select([
                'id',
                'from_airhub_id',
                'to_airhub_id',
                'frequency_limit',
                'begin_date',
                'end_date',
            ])
            ->with('fromAirhub', function ($query) {
                $query
                    ->select([
                        'id',
                        'name',
                    ]);
            })
            ->with('toAirhub', function ($query) {
                $query
                    ->select([
                        'id',
                        'name',
                    ]);
            })
            ->whereNull('end_date')
            ->doesntHave('airlines');

        /* Фильтрация */
        (new FlightDirectionFilterHandler($flightDirections, \request()))->handle();

        /* Сортировка */
        (new FlightDirectionSortingHandler($flightDirections, \request()))->handle();

        $result = [];
        /* Направления общие */
        foreach ($flightDirections->get() as $indexFlightDirection => $flightDirection) {

            /* Направления для авиакомпаний */
            $flightDirectionsAirlines = FlightDirection::query()
                ->where('from_airhub_id', $flightDirection->from_airhub_id)
                ->where('to_airhub_id', $flightDirection->to_airhub_id)
                ->whereDate('begin_date', '>=', $flightDirection->begin_date)
                ->whereDate('end_date', '>=', $flightDirection->begin_date)
                ->select([
                    'id',
                    'begin_date',
                ])
                ->has('airlines')
                ->with('airlines', function ($query) {
                    $query
                        ->select([
                            'AIRLINES_ID',
                            'SHORTNAMELAT',
                            'SHORTNAMERUS',
                        ])
//                        ->has('flightRegularity')
                        ->with('flightRegularity', function ($query) {
                            $query
                                ->select([
                                    'id',
                                    'amount',
                                    'percent',
                                    'airline_id',
                                ]);
                        })
//                        ->has('rpls')
                        ->with('rpls', function ($query) {
                            $query
                                ->select([
                                    'id',
                                    'airline_id',
                                    'status_id',
                                    'rpl_text',
                                    'created_at',
                                    'updated_at',
                                ])
                                ->with('status');
                        });
                });

            $result[$indexFlightDirection]['flightDirection'] = $flightDirection->toArray();
            foreach ($flightDirectionsAirlines->get() as $indexFlightDirectionsAirline => $flightDirectionsAirline) {
                $result[$indexFlightDirection]['flightDirectionsAirlines'][$indexFlightDirectionsAirline] = $flightDirectionsAirline->toArray();
                foreach ($flightDirectionsAirline->airlines as $indexAirline => $airline) {
                    $result[$indexFlightDirection]['flightDirectionsAirlines'][$indexFlightDirectionsAirline]['airlines'][$indexAirline]['limit'] = $airline->pivot->flightDirectionLimit->toArray();
                    $result[$indexFlightDirection]['flightDirectionsAirlines'][$indexFlightDirectionsAirline]['airlines'][$indexAirline]['approval'] = $airline->pivot->flightDirectionApproval->toArray();
                }
            }

//            $result[]['flightDirection'] = $flightDirection->toArray();
//            if ($flightDirectionsAirlines->count() !== 0) {
//                $result[array_key_last($result)]['flightDirectionsAirlines'] = $flightDirectionsAirlines->get()->toArray();
//            }
        }

//        dd($result);

        return response()->json(
            $result
        );
    }

    public function index(): JsonResponse
    {
        $this->calculation();

        $flightDirections = FlightDirection::query()
            ->doesntHave('airlines')
            ->select([
                'id',
                'from_airhub_id',
                'to_airhub_id',
                'begin_date',
                'frequency_limit',
                'NOTAM',
                'status_id',
                'updated_at',
            ])
            ->with('fromAirhub', function ($query) {
                $query->select([
                    'id',
                    'name',
                ]);
            })
            ->with('toAirhub', function ($query) {
                $query->select([
                    'id',
                    'name',
                ]);
            })
            ->with('status');

        /* Фильтрация */
        (new FlightDirectionFilterHandler($flightDirections, \request()))->handle();

        /* Сортировка */
        (new FlightDirectionSortingHandler($flightDirections, \request()))->handle();

        return response()->json(
            $flightDirections->get()
        );
    }

    public function store(Request $request): JsonResponse
    {
        $flightDirection = FlightDirection::create([
            'from_airhub_id' => $request->from_airhub_id,
            'to_airhub_id' => $request->to_airhub_id,
            'begin_date' => $request->begin_date,
            'end_date' => $this->flightDirectionEndDateOfAirline($request, 1, 3),
            'frequency_limit' => $request->frequency_limit,
            'NOTAM' => $request->NOTAM,
        ]);

        if ($request->has('airlines') && !is_null($flightDirection)) {

            $date = $flightDirection->begin_date;
            $season = Season::query()
                ->where(function ($query) use ($date) {
                    $query
                        ->whereDate('begin_date', '<=', $date)
                        ->whereDate('end_date', '>=', $date);
                })
                ->first();

            $syncData = [];
            foreach ($request->input('airlines') as $airline) {

                /* Limit */
                $airlineFlightDirectionLimit = null;
                if (!is_null($airline['airline_flight_direction_limit'])) {
                    $airlineFlightDirectionLimit = AirlineFlightDirectionLimit::create([
                        'limit' => $airline['airline_flight_direction_limit'],
                    ]);
                }

                /* Approval */
                $airlineFlightDirectionApproval = null;
                if (!is_null($airline['airline_flight_direction_approval'])) {
                    $airlineFlightDirectionApproval = AirlineFlightDirectionApproval::create([
                        'approval' => $airline['airline_flight_direction_approval'],
                        'season_id' => $season->id,
                    ]);
                }

                $syncData[$airline['airline_id']] = [
                        'airline_flight_direction_limit_id' => $airlineFlightDirectionLimit->id ?? null,
                        'airline_flight_direction_approval_id' => $airlineFlightDirectionApproval->id ?? null,
                ];
            }
            if (count($syncData) > 0) {
                $flightDirection->airlines()->sync($syncData);
            }
        }

        $this->calculation();

        return response()->json([
            'message' => 'Successfully create flight direction'
        ], 201);
    }

    public function update(Request $request, FlightDirection $flightDirection): JsonResponse
    {
        $flightDirection->update([
            'from_airhub_id' => $request->from_airhub_id,
            'to_airhub_id' => $request->to_airhub_id,
            'begin_date' => $request->begin_date,
            'end_date' => $this->flightDirectionEndDateOfAirline($request, 1, 3),
            'frequency_limit' => $request->frequency_limit,
            'NOTAM' => $request->NOTAM,
        ]);

        if ($request->has('airlines')) {

            $date = $flightDirection->begin_date;
            $season = Season::query()
                ->where(function ($query) use ($date) {
                    $query
                        ->whereDate('begin_date', '<=', $date)
                        ->whereDate('end_date', '>=', $date);
                })
                ->first();

            $newAirlineIds = [];
            foreach ($request->input('airlines') as $airline) {
                $newAirlineIds[] = $airline['airline_id'];
            }

            $oldAirline = $flightDirection
                ->airlines()
                ->get();

//            dump(
//                // attach
//                array_diff($newAirlineIds, $oldAirline->pluck('AIRLINES_ID')->toArray()),
//
//                // detach
//                array_diff($oldAirline->pluck('AIRLINES_ID')->toArray(), $newAirlineIds),
//            );

            // create new
            $compareNewAirline = array_diff($newAirlineIds, $oldAirline->pluck('AIRLINES_ID')->toArray());
            if (count($compareNewAirline) > 0) {
                $flightDirection->airlines()
                    ->attach($compareNewAirline);
            }

            // delete old
            $compareOldAirline = array_diff($oldAirline->pluck('AIRLINES_ID')->toArray(), $newAirlineIds);
            if (count($compareOldAirline) > 0) {
                $flightDirectionLimitIds = [];
                $flightDirectionApprovalIds = [];

                foreach ($oldAirline as $airline) {
                    if (!is_null($airline->pivot->flightDirectionLimit)) {
                        $flightDirectionLimitIds[] = $airline->pivot->flightDirectionLimit->id;
                    }
                    if (!is_null($airline->pivot->flightDirectionApproval)){
                        $flightDirectionApprovalIds[] = $airline->pivot->flightDirectionApproval->id;
                    }
                }

                AirlineFlightDirectionLimit::query()
                    ->whereIn('id', $flightDirectionLimitIds)
                    ->delete();

                AirlineFlightDirectionApproval::query()
                    ->whereIn('id', $flightDirectionApprovalIds)
                    ->delete();

                $flightDirection->airlines()
                    ->detach($compareOldAirline);
            }

            $syncData = [];
            foreach ($request->input('airlines') as $airline) {

                $airlineFlightDirection = $flightDirection
                    ->airlines()
                    ->where('AIRLINES_ID', $airline['airline_id'])
                    ->first();

                if (!is_null($airlineFlightDirection)) {
                    /* Limit */
                    $airlineFlightDirectionLimit = null;
                    if (!is_null($airline['airline_flight_direction_limit'])) {
                        $airlineFlightDirectionLimit = $airlineFlightDirection->pivot->flightDirectionLimit;

                        if (!is_null($airlineFlightDirectionLimit)) {
                            $airlineFlightDirectionLimit->update([
                                'limit' => $airline['airline_flight_direction_limit'],
                            ]);
                        } else {
                            $airlineFlightDirectionLimit = AirlineFlightDirectionLimit::create([
                                'limit' => $airline['airline_flight_direction_limit'],
                            ]);
                        }
                    }

                    /* Approval */
                    $airlineFlightDirectionApproval = null;
                    if (!is_null($airline['airline_flight_direction_approval'])) {
                        $airlineFlightDirectionApproval = $airlineFlightDirection->pivot->flightDirectionApproval;

                        if (!is_null($airlineFlightDirectionApproval)) {
                            $airlineFlightDirectionApproval->update([
                                'approval' => $airline['airline_flight_direction_approval'],
                                'season_id' => $season->id,
                            ]);
                        } else {
                            $airlineFlightDirectionApproval = AirlineFlightDirectionApproval::create([
                                'approval' => $airline['airline_flight_direction_approval'],
                                'season_id' => $season->id,
                            ]);
                        }
                    }

                    $syncData[$airline['airline_id']] = [
                        'airline_flight_direction_limit_id' => $airlineFlightDirectionLimit->id ?? null,
                        'airline_flight_direction_approval_id' => $airlineFlightDirectionApproval->id ?? null,
                    ];
                }
            }
            if (count($syncData) > 0) {
                $flightDirection->airlines()->sync($syncData);
            }
        }

        $this->calculation();

        return response()->json([
            'message' => 'Successfully update flight direction'
        ]);
    }

    public function destroy(FlightDirection $flightDirection): JsonResponse
    {
        $flightDirectionLimitIds = [];
        $flightDirectionApprovalIds = [];

        $airlines = $flightDirection
            ->airlines()
            ->get();

        foreach ($airlines as $airline) {
            if (!is_null($airline->pivot->flightDirectionLimit)) {
                $flightDirectionLimitIds[] = $airline->pivot->flightDirectionLimit->id;
            }
            if (!is_null($airline->pivot->flightDirectionApproval)){
                $flightDirectionApprovalIds[] = $airline->pivot->flightDirectionApproval->id;
            }
        }

        AirlineFlightDirectionLimit::query()
            ->whereIn('id', $flightDirectionLimitIds)
            ->delete();

        AirlineFlightDirectionApproval::query()
            ->whereIn('id', $flightDirectionApprovalIds)
            ->delete();

        $flightDirection->airlines()->detach();
        $flightDirection->delete();

        $this->calculation();

        return response()->json(null, 204);
    }

    /* Направления по аэроузлам */
    private function calculation(): void
    {
        $currentDate = Carbon::now();

        $flightDirections = FlightDirection::query()
            ->orderBy('id', 'desc')
            ->get()
            ->groupBy('from_airhub_id');

        foreach ($flightDirections as $flightDirection) {
            /* статусы для направлений полетов */
            $lastFlightDirectionId = null;
            foreach ($flightDirection as $currentFlightDirection) {

                /* Дата начала дейтвия направления больше текущей даты */
                if (Carbon::parse($currentFlightDirection->begin_date)->gt($currentDate)) {
                    $currentFlightDirection->status_id = 2;
                }

                /* Дата конца действия направления меньше текущей даты */
                if (Carbon::parse($currentFlightDirection->end_date)->lt($currentDate)) {
                    $currentFlightDirection->status_id = 3;
                }

                /* Текущая дата в пределах действия дат направлений */
                if ($currentDate->between($currentFlightDirection->begin_date, Carbon::parse($currentFlightDirection->end_date))) {
                    $compareFlightDirections = FlightDirection::select([ 'id', 'status_id' ])
                        ->where('from_airhub_id', $currentFlightDirection->from_airhub_id)
                        ->where('id', '<', $currentFlightDirection->id)
                        ->where('status_id', '!=', 3)
                        ->where(function($query) use ($currentFlightDirection) {
                            $query
                                ->whereDate('end_date', '>', $currentFlightDirection->begin_date)
                                ->orWhereNull('end_date');
                        })
                        ->orderBy('id', 'desc');

                    foreach ($compareFlightDirections->cursor() as $compareFlightDirection) {
                        $compareFlightDirection->status_id = 2;
                        $compareFlightDirection->save();
                    }

                    /* Последнее направление со статусом активный */
                    $lastFlightDirectionId = !is_null($lastFlightDirectionId) ?: $currentFlightDirection->id;
                    if ($currentFlightDirection->id === $lastFlightDirectionId) {
                        $currentFlightDirection->status_id = 1;
                    }
                }
                $currentFlightDirection->save();
//                dump($currentFlightDirection);
            }
        }
    }

    /* Дата окончания действия направления для авиакомпаний = конец сезона */
    private function flightDirectionEndDateOfAirline(Request $request, int $offsetYear, int $limitYear)
    {
        $endDate = null;
        if ($request->has('airlines')) {
            $beginDate = Carbon::parse($request->input('begin_date'));

            $summerSeasons = Season::query()
                ->where('name_lat', 'Summer')
                ->whereYear('begin_date', '>=', Carbon::now()->year - $offsetYear)
                ->limit($limitYear)
                ->get();

            $winterSeasons = Season::query()
                ->where('name_lat', 'Winter')
                ->whereYear('begin_date', '>=', Carbon::now()->year - $offsetYear)
                ->limit($limitYear)
                ->get();

            /* летнее время, направления для авиакомпаний */
            foreach ($summerSeasons as $summerSeason)
            {
                if ($beginDate->between($summerSeason->begin_date, $summerSeason->end_date)) {
                    $endDate = $summerSeason->end_date;
                }
            }

            /* зимнее время, направления для авиакомпаний */
            foreach ($winterSeasons as $winterSeason)
            {
                if ($beginDate->between($winterSeason->begin_date, $winterSeason->end_date)) {
                    $endDate = $winterSeason->end_date;
                }
            }
        }
        return $endDate;
    }
}
