<?php

namespace App\RequestHandlers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class RegistryNDatesSearchHandler
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
        if ($this->request->has('search_registration_number')) {
            $this->requestKey = 'search_registration_number';

            if ($this->requestHasParam($this->requestKey)) {
                $this->query
                    ->join('n_form_flight', 'n_form_flight.n_form_flight_id', '=', 'departure_dates.n_form_flight_id')
                    ->join('n_forms', 'n_forms.n_forms_id', '=', 'n_form_flight.n_forms_id')
                    ->join('n_form_aircrafts', 'n_form_aircrafts.n_forms_id', '=', 'n_forms.n_forms_id')
                    ->where('n_form_aircrafts.is_main', '=', '1')
                    ->where('registration_number', 'ilike', "%{$this->getParamValue($this->requestKey)}%")
                    ->select('registration_number')
                    ->distinct('registration_number');
            }
        }

        if ($this->request->has('search_aircraft_type_icao')) {
            $this->requestKey = 'search_aircraft_type_icao';

            if ($this->requestHasParam($this->requestKey)) {
                $this->query
                    ->join('n_form_flight', 'n_form_flight.n_form_flight_id', '=', 'departure_dates.n_form_flight_id')
                    ->join('n_forms', 'n_forms.n_forms_id', '=', 'n_form_flight.n_forms_id')
                    ->join('n_form_aircrafts', 'n_form_aircrafts.n_forms_id', '=', 'n_forms.n_forms_id')
                    ->where('n_form_aircrafts.is_main', '=', '1')
                    ->where('aircraft_type_icao', 'ilike', "%{$this->getParamValue($this->requestKey)}%")
                    ->select('aircraft_type_icao')
                    ->distinct('aircraft_type_icao');
            }
        }

        if ($this->request->has('search_permit_num')) {
            $this->requestKey = 'search_permit_num';

            if ($this->requestHasParam($this->requestKey)) {
                $this->query
                    ->join('n_form_flight', 'n_form_flight.n_form_flight_id', '=', 'departure_dates.n_form_flight_id')
                    ->join('n_forms', 'n_forms.n_forms_id', '=', 'n_form_flight.n_forms_id')
                    ->where('permit_num', 'ilike', "%{$this->getParamValue($this->requestKey)}%")
                    ->select('permit_num')
                    ->distinct('permit_num');
            }
        }

        if ($this->request->has('search_flight_num')) {
            $this->requestKey = 'search_flight_num';

            if ($this->requestHasParam($this->requestKey)) {
                $this->query
                    ->join('n_form_flight', 'n_form_flight.n_form_flight_id', '=', 'departure_dates.n_form_flight_id')
                    ->where('flight_num', 'ilike', "%{$this->getParamValue($this->requestKey)}%")
                    ->select('flight_num')
                    ->distinct('flight_num');
            }
        }

        if ($this->request->has('search_purpose')) {
            $this->requestKey = 'search_purpose';

            if ($this->requestHasParam($this->requestKey)) {
                $this->query
                    ->join('n_form_flight', 'n_form_flight.n_form_flight_id', '=', 'departure_dates.n_form_flight_id')
                    ->where('purpose', 'ilike', "%{$this->getParamValue($this->requestKey)}%")
                    ->select('purpose')
                    ->distinct('purpose');
            }
        }

        if ($this->request->has('search_transportation_categories')) {
            $this->requestKey = 'search_transportation_categories';

            if ($this->requestHasParam($this->requestKey)) {
                $this->query
                    ->join('n_form_flight', 'n_form_flight.n_form_flight_id', '=', 'departure_dates.n_form_flight_id')
                    ->join('flight_categories', 'flight_categories.CATEGORIES_ID', '=', 'n_form_flight.transportation_categories_id')
                    ->where('NAMELAT', 'ilike', "%{$this->getParamValue($this->requestKey)}%")
                    ->orWhere('NAMERUS', 'ilike', "%{$this->getParamValue($this->requestKey)}%")
                    ->select('NAMELAT', 'NAMERUS')
                    ->distinct('NAMELAT', 'NAMERUS');
            }
        }

        if ($this->request->has('search_departure_airport')) {
            $this->requestKey = 'search_departure_airport';

            if ($this->requestHasParam($this->requestKey)) {
                $params = $this->getParamValue($this->requestKey);

                $this->query
                    ->join('n_form_flight', 'n_form_flight.n_form_flight_id', '=', 'departure_dates.n_form_flight_id')
                    ->where('n_form_flight.departure_airport_namerus', 'ilike', '%' . $params . '%')
                    ->orWhere('n_form_flight.departure_airport_namelat', 'ilike', '%' . $params . '%')
                    ->orWhere('n_form_flight.departure_platform_coordinates', 'ilike', $params . '%')
                    ->orWhere('n_form_flight.departure_airport_icao', 'ilike', '%' . $params . '%')
                    ->select(
                        'n_form_flight.departure_airport_namerus',
                        'n_form_flight.departure_airport_namelat',
                        'n_form_flight.departure_platform_coordinates',
                        'n_form_flight.departure_airport_icao',
                        'n_form_flight.departure_airport_id',
                    )
                    ->distinct('n_form_flight.departure_airport_id');
            }
        }

        if ($this->request->has('search_landing_airport')) {
            $this->requestKey = 'search_landing_airport';

            if ($this->requestHasParam($this->requestKey)) {
                $params = $this->getParamValue($this->requestKey);

                $this->query
                    ->join('n_form_flight', 'n_form_flight.n_form_flight_id', '=', 'departure_dates.n_form_flight_id')
                    ->where('n_form_flight.landing_airport_namerus', 'ilike', '%' . $params . '%')
                    ->orWhere('n_form_flight.landing_airport_namelat', 'ilike', '%' . $params . '%')
                    ->orWhere('n_form_flight.landing_platform_coordinates', 'ilike', $params . '%')
                    ->orWhere('n_form_flight.landing_airport_icao', 'ilike', '%' . $params . '%')
                    ->select(
                        'n_form_flight.landing_airport_namerus',
                        'n_form_flight.landing_airport_namelat',
                        'n_form_flight.landing_platform_coordinates',
                        'n_form_flight.landing_airport_icao',
                        'n_form_flight.landing_airport_id',
                    )
                    ->distinct('n_form_flight.landing_airport_id');
            }
        }

        if ($this->request->has('search_landing_type')) {
            $this->requestKey = 'search_landing_type';

            if ($this->requestHasParam($this->requestKey)) {
                $this->query
                    ->join('n_form_flight', 'n_form_flight.n_form_flight_id', '=', 'departure_dates.n_form_flight_id')
                    ->where('landing_type', 'ilike', "%{$this->getParamValue($this->requestKey)}%")
                    ->select('landing_type')
                    ->distinct('landing_type');
            }
        }

        if ($this->request->has('search_route')) {
            $this->requestKey = 'search_route';

            if ($this->requestHasParam($this->requestKey)) {
                $this->query
                    ->join('n_form_flight', 'n_form_flight.n_form_flight_id', '=', 'departure_dates.n_form_flight_id')
                    ->where('departure_airport_icao', 'ilike', "%{$this->getParamValue($this->requestKey)}%")
                    ->orWhere('landing_airport_icao', 'ilike', "%{$this->getParamValue($this->requestKey)}%")
                    ->select('departure_airport_icao', 'landing_airport_icao')
                    ->distinct();
            }
        }

        if ($this->request->has('search_filter_name_airline')) {
            $this->requestKey = 'search_filter_name_airline';

            if ($this->requestHasParam($this->requestKey)) {
                $param = $this->getParamValue($this->requestKey);

                $this->query
                    ->join('n_form_flight', 'n_form_flight.n_form_flight_id', '=', 'departure_dates.n_form_flight_id')
                    ->join('n_forms', 'n_forms.n_forms_id', '=', 'n_form_flight.n_forms_id')
                    ->join('n_form_airlines', 'n_form_airlines.n_forms_id', '=', 'n_forms.n_forms_id')
                    ->where('AIRLINE_ICAO', 'ilike', '%' . $param . '%')
                    ->orWhere('airline_namelat', 'ilike', '%' . $param . '%')
                    ->orWhere('airline_namerus', 'ilike', '%' . $param . '%')
                    ->select('AIRLINE_ICAO', 'airline_namerus', 'airline_namelat', 'AIRLINES_ID')
                    ->distinct('AIRLINE_ICAO', 'airline_namerus', 'airline_namelat', 'AIRLINES_ID');
            }
        }

        if ($this->request->has('search_aircraft_owner')) {
            $this->requestKey = 'search_aircraft_owner';

            if ($this->requestHasParam($this->requestKey)) {
                $this->query
                    ->join('n_form_flight', 'n_form_flight.n_form_flight_id', '=', 'departure_dates.n_form_flight_id')
                    ->join('n_forms', 'n_forms.n_forms_id', '=', 'n_form_flight.n_forms_id')
                    ->join('n_form_aircrafts', 'n_form_aircrafts.n_forms_id', '=', 'n_forms.n_forms_id')
                    ->join(
                        'n_form_aircraft_owner',
                        'n_form_aircraft_owner.n_form_aircraft_owner_id',
                        '=',
                        'n_form_aircrafts.n_form_aircraft_owner_id'
                    )
                    ->where('name', 'ilike', "%{$this->getParamValue($this->requestKey)}%")
                    ->select('name')
                    ->distinct('name');
            }
        }

        if ($this->request->has('search_status')) {
            $this->requestKey = 'search_status';

            if ($this->requestHasParam($this->requestKey)) {
                $params = $this->getParamValue($this->requestKey);

                $this->query
                    ->join('n_form_flight', 'n_form_flight.n_form_flight_id', '=', 'departure_dates.n_form_flight_id')
                    ->join('n_form_flight_statuses', 'n_form_flight_statuses.id', '=', 'n_form_flight.status_id')
                    ->where('n_form_flight_statuses.name_rus', 'ilike', '%' . $params . '%')
                    ->orWhere('n_form_flight_statuses.name_lat', 'ilike', '%' . $params . '%')
                    ->select('n_form_flight_statuses.name_rus', 'n_form_flight_statuses.name_lat', 'n_form_flight_statuses.id')
                    ->distinct('n_form_flight_statuses.id');
            }
        }
    }

    /* helpers */

    private function requestHasParam(string $requestKey): bool
    {
        if (array_key_exists($this->paramKey, $this->request->get($requestKey))) return true;

        return false;
    }

    private function getParamValue(string $requestKey)
    {
        return $this->request->get($requestKey)[$this->paramKey];
    }
}
