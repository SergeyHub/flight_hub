<?php

namespace App\RequestHandlers;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RegistryNDatesFiltersHandler
{
    private Builder $query;
    private Request $request;
    private string $paramKey;
    private string $requestKey;

    public function __construct(Builder $query, Request $request)
    {
        $this->query = $query;
        $this->request = $request;
        $this->paramKey = "'params'";

    }

    public function handle()
    {
        //departure_dates.date
        if ($this->request->has('date')) {
            $this->requestKey = 'date';

            if ($this->requestHasParam($this->requestKey)) {
                $this->query->whereBetween('date', $this->getParamValues($this->requestKey));
            }
        }

        //departure_dates->n_form_flight->n_forms.permit_num
        if ($this->request->has('permit_num')) {
            $this->requestKey = 'permit_num';

            if ($this->requestHasParam($this->requestKey)) {
                $this->query->whereHas('nFormFlight', function ($query) {
                    $query->whereHas('nForm', function ($query) {
                        $query->whereIn('permit_num', $this->getParamValues($this->requestKey));
                    });
                });
            }
        }

        //departure_dates->n_form_flight->n_forms.created_at
        if ($this->request->has('created_at')) {
            $this->requestKey = 'created_at';

            if ($this->requestHasParam($this->requestKey)) {
                $this->query->whereHas('nFormFlight', function ($query) {
                    $query->whereHas('nForm', function ($query) {
                        $timeIntervals = $this->getParamValues($this->requestKey);
                        $timeFrom = Carbon::createFromTimestamp($timeIntervals[0])->toDateTimeString();
                        $timeTo = Carbon::createFromTimestamp($timeIntervals[1])->toDateTimeString();

                        $query->whereBetween('created_at', [$timeFrom, $timeTo]);
                    });
                });
            }
        }

        //departure_dates->n_form_flight.flight_num
        if ($this->request->has('flight_num')) {
            $this->requestKey = 'flight_num';

            if ($this->requestHasParam($this->requestKey)) {
                $this->query->whereHas('nFormFlight', function ($query) {
                    $query->whereIn('flight_num', $this->getParamValues($this->requestKey));
                });
            }
        }

        //departure_dates->n_form_flight.purpose
        if ($this->request->has('purpose')) {
            $this->requestKey = 'purpose';

            if ($this->requestHasParam($this->requestKey)) {
                $this->query->whereHas('nFormFlight', function ($query) {
                    $query->whereIn('purpose', $this->getParamValues($this->requestKey));
                });
            }
        }

        //departure_dates->n_form_flight.transportation_categories_id
        if ($this->request->has('transportation_categories')) {
            $this->requestKey = 'transportation_categories';

            if ($this->requestHasParam($this->requestKey)) {
                $this->query->whereHas('nFormFlight', function ($query) {
                    $query->whereIn('transportation_categories_id', $this->getParamValues($this->requestKey));
                });
            }
        }

        //departure_dates->n_form_flight.departure_airport_id
        if ($this->request->has('departure_airport')) {
            $this->requestKey = 'departure_airport';

            if ($this->requestHasParam($this->requestKey)) {
                $this->query->whereHas('nFormFlight', function ($query) {
                    $query->whereIn('departure_airport_id', $this->getParamValues($this->requestKey));
                });
            }
        }

        //departure_dates->n_form_flight.landing_airport_id
        if ($this->request->has('landing_airport')) {
            $this->requestKey = 'landing_airport';

            if ($this->requestHasParam($this->requestKey)) {
                $this->query->whereHas('nFormFlight', function ($query) {
                    $query->whereIn('landing_airport_id', $this->getParamValues($this->requestKey));
                });
            }
        }

        //departure_dates->n_form_flight.landing_type
        if ($this->request->has('landing_type')) {
            $this->requestKey = 'landing_type';

            if ($this->requestHasParam($this->requestKey)) {
                $this->query->whereHas('nFormFlight', function ($query) {
                    $query->whereIn('landing_type', $this->getParamValues($this->requestKey));
                });
            }
        }

        //departure_dates->n_form_flight.status_id
        if ($this->request->has('status')) {
            $this->requestKey = 'status';

            if ($this->requestHasParam($this->requestKey)) {
                $this->query->whereHas('nFormFlight', function ($query) {
                    $query->whereIn('status_id', $this->getParamValues($this->requestKey));
                });
            }
        }

        //departure_dates->n_form_flight.status_change_datetime
        if ($this->request->has('update_datetime')) {
            $this->requestKey = 'update_datetime';

            if ($this->requestHasParam($this->requestKey)) {
                $this->query->whereHas('nFormFlight', function ($query) {
                    $timeIntervals = $this->getParamValues($this->requestKey);
                    $timeFrom = Carbon::createFromTimestamp($timeIntervals[0])->toDateTimeString();
                    $timeTo = Carbon::createFromTimestamp($timeIntervals[1])->toDateTimeString();
                    $query->whereBetween('status_change_datetime', [$timeFrom, $timeTo]);
                });
            }
        }

        //departure_dates->n_form_flight.departure_time
        if ($this->request->has('departure_time')) {
            $this->requestKey = 'departure_time';

            if ($this->requestHasParam($this->requestKey)) {
                $this->query->whereHas('nFormFlight', function ($query) {
                    $query->whereBetween('departure_time', [$this->getParamValues($this->requestKey)]);
                });
            }
        }

        //departure_dates->n_form_flight.landing_time
        if ($this->request->has('landing_time')) {
            $this->requestKey = 'landing_time';

            if ($this->requestHasParam($this->requestKey)) {
                $this->query->whereHas('nFormFlight', function ($query) {
                    $query->whereBetween('landing_time', [$this->getParamValues($this->requestKey)]);
                });
            }
        }

        //departure_dates->n_form_flight->n_forms->n_form_aircrafts.registration_number
        if ($this->request->has('registration_number')) {
            $this->requestKey = 'registration_number';

            if ($this->requestHasParam($this->requestKey)) {
                $this->query->whereHas('nFormFlight', function ($query) {
                    $query->whereHas('nForm', function ($query) {
                        $query->whereHas('aircrafts', function ($query) {
                            $query->where('is_main', 1);
                            $query->whereIn('registration_number', $this->getParamValues($this->requestKey));
                        });
                    });
                });
            }
        }

        //departure_dates->n_form_flight->n_forms->n_form_aircrafts.aircraft_type_icao
        if ($this->request->has('aircraft_type_icao')) {
            $this->requestKey = 'aircraft_type_icao';

            if ($this->requestHasParam($this->requestKey)) {
                $this->query->whereHas('nFormFlight', function ($query) {
                    $query->whereHas('nForm', function ($query) {
                        $query->whereHas('aircrafts', function ($query) {
                            $query->where('is_main', 1);
                            $query->whereIn('aircraft_type_icao', $this->getParamValues($this->requestKey));
                        });
                    });
                });
            }
        }

        //departure_dates->n_form_flight->n_forms->n_form_aircrafts->n_form_aircraft_owner
        if ($this->request->has('aircraft_owner')) {
            $this->requestKey = 'aircraft_owner';

            if ($this->requestHasParam($this->requestKey)) {
                $this->query->whereHas('nFormFlight', function ($query) {
                    $query->whereHas('nForm', function ($query) {
                        $query->whereHas('aircrafts', function ($query) {
                            $query->whereHas('aircraft_owner', function ($query) {
                                $query->whereIn('name', $this->getParamValues($this->requestKey));
                            });
                        });
                    });
                });
            }
        }

        //departure_dates->n_form_flight->n_forms->n_form_airlines.AIRLINES_ID
        if ($this->request->has('name_airline')) {
            $this->requestKey = 'name_airline';

            if ($this->requestHasParam($this->requestKey)) {
                $this->query->whereHas('nFormFlight', function ($query) {
                    $query->whereHas('nForm', function ($query) {
                        $query->whereHas('airline', function ($query) {
                            $query->whereIn('AIRLINES_ID', $this->getParamValues($this->requestKey));
                        });
                    });
                });
            }
        }

        //departure_dates->n_form_flight->n_forms->n_form_airlines.ano_is_paid
        if ($this->request->has('is_paid')) {
            $this->requestKey = 'is_paid';

            if ($this->requestHasParam($this->requestKey)) {
                $this->query->whereHas('nFormFlight', function ($query) {
                    $query->whereHas('nForm', function ($query) {
                        $query->whereHas('airline', function ($query) {
                            $query->whereIn('ano_is_paid', $this->getParamValues($this->requestKey));
                        });
                    });
                });
            }
        }

        if ($this->request->has('weight_sum_weight')) {
            $this->requestKey = 'weight_sum_weight';

            if ($this->requestHasParam($this->requestKey)) {
                $this->query->whereHas('nFormFlight', function ($query) {
                    $query->whereHas('cargos', function ($query) {
                        $query
                            ->select(DB::raw("sum(weight) AS total_weight"))
                            ->havingRaw('sum(weight) >= ? AND sum(weight) <= ?', $this->getParamValues($this->requestKey));
                    });
                });
            }
        }

        if ($this->request->has('quantity')) {
            $this->requestKey = 'quantity';

            if ($this->requestHasParam($this->requestKey)) {
                $this->query->whereHas('nFormFlight', function ($query) {
                    $query->whereHas('passengers', function ($query) {
                        $query
                            ->select(DB::raw('sum(quantity) AS p_q'))
                            ->havingRaw('sum(quantity) >= ? AND sum(quantity) <= ?', [$this->getParamValues($this->requestKey)]);
                    });
                });
            }
        }

        if ($this->request->has('crew_group_count')) {
            $this->requestKey = 'crew_group_count';

            if ($this->requestHasParam($this->requestKey)) {
                $this->query->whereHas('nFormFlight', function ($query) {
                    $query->whereHas('crew', function ($query) {
                        $query->whereHas('crewGroups', function ($query) {
                            $query
                                ->select(DB::raw("sum(quantity) AS total_quantity"))
                                ->havingRaw('sum(quantity) >= ? AND sum(quantity) <= ?', $this->getParamValues($this->requestKey));
                        });
                    });
                });
            }
        }

        if ($this->request->has('crew_member_count')) {
            $this->requestKey = 'crew_member_count';

            if ($this->requestHasParam($this->requestKey)) {
                $this->query->whereHas('nFormFlight', function ($query) {
                    $query->whereHas('crew', function ($query) {
                        $query->whereHas('crewMembers', function ($query) {
                            $query
                                ->select(DB::raw("count(*) AS total_quantity_member"))
                                ->havingRaw('count(*) >= ? AND count(*) <= ?', $this->getParamValues($this->requestKey));
                        });
                    });
                });
            }
        }

        if ($this->request->has('sign_filter')) {
            $this->requestKey = 'sign_filter';

            if ($this->requestHasParam($this->requestKey)) {
                $this->query->whereHas('nFormFlight', function ($query) {
                    $query->whereHas('agreementSigns', function ($query) {
                        $query->whereIn('n_form_flight_sign_id', $this->getParamValues($this->requestKey));
                    });
                });
            }
        }

        if ($this->request->has('route')) {
            $this->requestKey = 'route';

            if ($this->requestHasParam($this->requestKey)) {
                $this->query->whereHas('nFormFlight', function ($query) {
                    $query->whereHas('nForm', function ($query) {
                        $query->whereIn('n_forms_id', $this->getParamValues($this->requestKey));
                    });
                });
            }
        }
    }

    /* helpers */

    private function requestHasParam(string $requestKey): bool
    {
        if ($this->request->has($requestKey) && array_key_exists($this->paramKey, $this->request->get($requestKey)))
            return true;

        return false;
    }

    private function getParamValues(string $requestKey)
    {
        return explode(',', str_replace(['[', ']'], '', $this->request->get($requestKey)[$this->paramKey]));
    }
}
