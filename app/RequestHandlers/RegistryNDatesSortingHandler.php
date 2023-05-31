<?php /** @noinspection PhpMultipleClassDeclarationsInspection */


namespace App\RequestHandlers;


use App\Models\FavoriteUserNForm;
use App\Models\FlightCategory;
use App\Models\NForm;
use App\Models\NFormAircraft;
use App\Models\NFormAircraftOwner;
use App\Models\NFormAirline;
use App\Models\NFormCargo;
use App\Models\NFormCrew;
use App\Models\NFormFlight;
use App\Models\NFormPassenger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;


class RegistryNDatesSortingHandler
{
    private Builder $query;
    private Request $request;
    private string $sortKey;

    public function __construct(Builder $query, Request $request)
    {
        $this->query = $query;
        $this->request = $request;
        $this->sortKey = "'sort'";
    }

    public function handle()
    {
        /* n_forms table */

        //n_forms.created_at
        if ($this->request->has('n_forms_id')) {
            if ($this->requestHasSort('n_forms_id')) {
                $this->query->orderBy($this->sortByNFormsId(), $this->getSortOrder('n_forms_id'));
            }
        }

        //n_forms.permit_num
        if ($this->request->has('permit_num')) {
            if ($this->requestHasSort('permit_num')) {
                $this->query->orderBy($this->sortByNFormsPermitNum(), $this->getSortOrder('permit_num'));
            }
        }

        //n_forms.created_at
        if ($this->request->has('created_at')) {
            if ($this->requestHasSort('created_at')) {
                $this->query->orderBy($this->sortByNFormsCreatedAt(), $this->getSortOrder('created_at'));
            }
        }

        /* departure_dates table */

        //departure_dates.date
        if ($this->request->has('date')) {
            if ($this->requestHasSort('date')) {
                $this->query->orderBy('date', $this->getSortOrder('date'));
            }
        }

        /* n_form_flight table */

        //n_form_flight.departure_time
        if ($this->request->has('departure_time')) {
            if ($this->requestHasSort('departure_time'))
                $this->query->orderBy(
                    $this->sortByNFormFlightDepartureTime(),
                    $this->getSortOrder('departure_time')
                );
        }

        //n_form_flight.purpose
        if ($this->request->has('purpose')) {
            if ($this->requestHasSort('purpose')) {
                $this->query->orderBy(
                    $this->sortByNFormFlightElements('purpose'),
                    $this->getSortOrder('purpose')
                );
            }
        }

        //n_form_flight.departure_airport_namerus
        if ($this->request->has('departure_airport_namerus')) {
            if ($this->requestHasSort('departure_airport_namerus')) {
                $this->query->orderBy(
                    $this->sortByNFormFlightElements('departure_airport_namerus'),
                    $this->getSortOrder('departure_airport_namerus')
                );
            }
        }

        //n_form_flight.landing_airport_namerus
        if ($this->request->has('landing_airport_namerus')) {
            if ($this->requestHasSort('landing_airport_namerus')) {
                $this->query->orderBy(
                    $this->sortByNFormFlightElements('landing_airport_namerus'),
                    $this->getSortOrder('landing_airport_namerus')
                );
            }
        }

        //n_form_flight.landing_time
        if ($this->request->has('landing_time')) {
            if ($this->requestHasSort('landing_time')) {
                $this->query->orderBy(
                    $this->sortByNFormFlightElements('landing_time'),
                    $this->getSortOrder('landing_time')
                );
            }
        }

        //n_form_flight.status_change_datetime
        if ($this->request->has('status_update_datetime')) {
            if ($this->requestHasSort('status_update_datetime')) {
                $this->query->orderBy(
                    $this->sortByNFormFlightElements('status_change_datetime'),
                    $this->getSortOrder('status_update_datetime')
                );
            }
        }

        //n_form_flight.landing_type
        if ($this->request->has('landing_type')) {
            if ($this->requestHasSort('landing_type')) {
                $this->query->orderBy(
                    $this->sortByNFormFlightElements('landing_type'),
                    $this->getSortOrder('landing_type')
                );
            }
        }

        //n_form_aircrafts.aircraft_type_icao
        if ($this->request->has('aircraft_type_icao')) {
            if ($this->requestHasSort('aircraft_type_icao')) {
                $this->query->orderBy(
                    $this->sortByNFormAircraftTypeIcao(),
                    $this->getSortOrder('aircraft_type_icao')
                );
            }
        }

        //n_form_aircraft_owner.name
        if ($this->request->has('aircraft_owner')) {
            if ($this->requestHasSort('aircraft_owner')) {
                $this->query->orderBy(
                    $this->sortByNFormAircraftOwnerName(),
                    $this->getSortOrder('aircraft_owner')
                );
            }
        }

        //n_form_airlines.airline_namelat
        if ($this->request->has('airline_namelat')) {
            if ($this->requestHasSort('airline_namelat')) {
                $this->query->orderBy(
                    $this->sortByNFormAirlineNamelat(),
                    $this->getSortOrder('airline_namelat')
                );
            }
        }

        //n_form_airlines.airline_namerus
        if ($this->request->has('airline_namerus')) {
            if ($this->requestHasSort('airline_namerus')) {
                $this->query->orderBy(
                    $this->sortByNFormAirlineNamerus(),
                    $this->getSortOrder('airline_namerus')
                );
            }
        }

        //n_form_aircrafts.registration_number
        if ($this->request->has('registration_number')) {
            if ($this->requestHasSort('registration_number')) {
                $this->query->orderBy(
                    $this->sortByNFormAircraftRegistrationNumber(),
                    $this->getSortOrder('registration_number')
                );
            }
        }

        //n_form_flight.flight_num
        if ($this->request->has('flight_num')) {
            if ($this->requestHasSort('flight_num')) {
                $this->query->orderBy(
                    $this->sortByNFormFlightElements('flight_num'),
                    $this->getSortOrder('flight_num')
                );
            }
        }

        //n_form_airlines.ano_is_paid
        if ($this->request->has('is_paid')) {
            if ($this->requestHasSort('is_paid')) {
                $this->query->orderBy(
                    $this->sortByNFormAirlineAno(),
                    $this->getSortOrder('is_paid')
                );
            }
        }

        //n_form_flight.status_id
        if ($this->request->has('status')) {
            if ($this->requestHasSort('status')) {
                $this->query->orderBy(
                    $this->sortByNFormFlightElements('status_id'),
                    $this->getSortOrder('status')
                );
            }
        }

        //n_form_flight.cargos_sum_weight
        if ($this->request->has('weight_sum_weight')) {
            if ($this->requestHasSort('weight_sum_weight')) {
                $this->query->orderBy(
                    $this->sortByCargosSumWeight(),
                    $this->getSortOrder('weight_sum_weight')
                );
            }
        }

        if ($this->request->has('quantity')) {
            if ($this->requestHasSort('quantity')) {
                $this->query->orderBy(
                    $this->sortByPassengersCount(),
                    $this->getSortOrder('quantity')
                );
            }
        }

        if ($this->request->has('transportation_categories')) {
            if ($this->requestHasSort('transportation_categories')) {
                $this->query->orderBy(
                    $this->sortByFlightCategoryNamerus(),
                    $this->getSortOrder('transportation_categories')
                );
            }
        }

        if ($this->request->has('crew_member_count')) {
            if ($this->requestHasSort('crew_member_count')) {
                $this->query->orderBy(
                    $this->sortByCrewMembersCount(),
                    $this->getSortOrder('crew_member_count')
                );
            }
        }

        if ($this->request->has('crew_group_count')) {
            if ($this->requestHasSort('crew_group_count')) {
                $this->query->orderBy(
                    $this->sortByCrewGroupsSumQuantity(),
                    $this->getSortOrder('crew_group_count')
                );
            }
        }

        if ($this->request->has('is_favorite')) {
            if ($this->requestHasSort('is_favorite')) {
                $this->query->orderBy(
                    $this->sortByNFormIsFavorite(),
                    $this->getSortOrder('is_favorite')
                );
            }
        }
    }

    private function getSortOrder(string $requestKey)
    {
        return $this->request->get($requestKey)[$this->sortKey];
    }

    private function requestHasSort(string $requestKey): bool
    {
        if ($this->request->has($requestKey) && array_key_exists($this->sortKey, $this->request->get($requestKey)))
            return true;

        return false;
    }

    /* sorting */

    private function sortByNFormsId()
    {
        return NForm::select('n_forms.n_forms_id')
            ->join('n_form_flight', 'n_form_flight.n_forms_id', '=', 'n_forms.n_forms_id')
            ->whereColumn('n_form_flight.n_form_flight_id', 'departure_dates.n_form_flight_id')
            ->latest('n_forms.n_forms_id')
            ->take(1);
    }

    private function sortByNFormsPermitNum()
    {
        return NForm::select('n_forms.permit_num')
            ->join('n_form_flight', 'n_form_flight.n_forms_id', '=', 'n_forms.n_forms_id')
            ->whereColumn('n_form_flight.n_form_flight_id', 'departure_dates.n_form_flight_id')
            ->latest('n_forms.permit_num')
            ->take(1);
    }

    private function sortByNFormsCreatedAt()
    {
        return NForm::select('n_forms.created_at')
            ->join('n_form_flight', 'n_form_flight.n_forms_id', '=', 'n_forms.n_forms_id')
            ->whereColumn('n_form_flight.n_form_flight_id', 'departure_dates.n_form_flight_id')
            ->latest('n_forms.created_at')
            ->take(1);
    }

    private function sortByNFormFlightDepartureTime()
    {
        return NFormFlight::select('departure_time')
            ->whereColumn('n_form_flight.n_form_flight_id', 'departure_dates.n_form_flight_id')
            ->latest('departure_time')
            ->take(1);
    }

    private function sortByNFormFlightElements(string $placeholder)
    {
        return NFormFlight::select($placeholder)
            ->whereColumn('n_form_flight.n_form_flight_id', 'departure_dates.n_form_flight_id')
            ->latest($placeholder)
            ->take(1);
    }

    //departure_dates->n_form_flight->n_form->n_form_aircrafts.aircraft_type_icao
    private function sortByNFormAircraftTypeIcao()
    {
        return NFormAircraft::select('n_form_aircrafts.aircraft_type_icao')
            ->join('n_forms', 'n_form_aircrafts.n_forms_id', '=', 'n_forms.n_forms_id')
            ->join('n_form_flight', 'n_form_flight.n_forms_id', '=', 'n_forms.n_forms_id')
            ->whereColumn('n_form_flight.n_form_flight_id', 'departure_dates.n_form_flight_id')
            ->latest('n_form_aircrafts.aircraft_type_icao')
            ->take(1);
    }

    //departure_dates->n_form_flight->n_form->n_form_aircrafts->n_form_aircraft_owner.name
    private function sortByNFormAircraftOwnerName()
    {
        return NFormAircraftOwner::select('n_form_aircraft_owner.name')
            ->join('n_form_aircrafts', 'n_form_aircraft_owner.n_form_aircraft_owner_id', '=', 'n_form_aircrafts.n_form_aircraft_owner_id')
            ->join('n_forms', 'n_form_aircrafts.n_forms_id', '=', 'n_forms.n_forms_id')
            ->join('n_form_flight', 'n_form_flight.n_forms_id', '=', 'n_forms.n_forms_id')
            ->whereColumn('n_form_flight.n_form_flight_id', 'departure_dates.n_form_flight_id')
            ->latest('n_form_aircraft_owner.name')
            ->take(1);
    }

    //departure_dates->n_form_flight->n_form->n_form_airlines.airline_namelat
    private function sortByNFormAirlineNamelat()
    {
        return NFormAirline::select('n_form_airlines.airline_namelat')
            ->join('n_forms', 'n_form_airlines.n_forms_id', '=', 'n_forms.n_forms_id')
            ->join('n_form_flight', 'n_form_flight.n_forms_id', '=', 'n_forms.n_forms_id')
            ->whereColumn('n_form_flight.n_form_flight_id', 'departure_dates.n_form_flight_id')
            ->latest('n_form_airlines.airline_namelat')
            ->take(1);
    }

    //departure_dates->n_form_flight->n_form->n_form_airlines.airline_namerus
    private function sortByNFormAirlineNamerus()
    {
        return NFormAirline::select('n_form_airlines.airline_namerus')
            ->join('n_forms', 'n_form_airlines.n_forms_id', '=', 'n_forms.n_forms_id')
            ->join('n_form_flight', 'n_form_flight.n_forms_id', '=', 'n_forms.n_forms_id')
            ->whereColumn('n_form_flight.n_form_flight_id', 'departure_dates.n_form_flight_id')
            ->latest('n_form_airlines.airline_namerus')
            ->take(1);
    }

    //departure_dates->n_form_flight->n_form->n_form_aircrafts.registration_number
    private function sortByNFormAircraftRegistrationNumber()
    {
        return NFormAircraft::select('n_form_aircrafts.registration_number')
            ->join('n_forms', 'n_form_aircrafts.n_forms_id', '=', 'n_forms.n_forms_id')
            ->join('n_form_flight', 'n_form_flight.n_forms_id', '=', 'n_forms.n_forms_id')
            ->whereColumn('n_form_flight.n_form_flight_id', 'departure_dates.n_form_flight_id')
            ->latest('n_form_aircrafts.registration_number')
            ->take(1);
    }

    //departure_dates->n_form_flight->n_form->n_form_airlines.ano_is_paid
    private function sortByNFormAirlineAno()
    {
        return NFormAirline::select('n_form_airlines.ano_is_paid')
            ->join('n_forms', 'n_form_airlines.n_forms_id', '=', 'n_forms.n_forms_id')
            ->join('n_form_flight', 'n_form_flight.n_forms_id', '=', 'n_forms.n_forms_id')
            ->whereColumn('n_form_flight.n_form_flight_id', 'departure_dates.n_form_flight_id')
            ->latest('n_form_airlines.ano_is_paid')
            ->take(1);
    }

    private function sortByCargosSumWeight()
    {
        return NFormFlight::selectRaw('sum(n_form_cargo.weight) as cargo_sum')
            ->join('n_form_cargo', 'n_form_cargo.n_form_flight_id', '=', 'n_form_flight.n_form_flight_id')
            ->whereColumn('n_form_flight.n_form_flight_id', 'departure_dates.n_form_flight_id')
            ->latest('cargo_sum')
            ->take(1);
    }

    private function sortByPassengersCount()
    {
        return NFormPassenger::selectRaw('count(n_form_passengers_persons.*) as pass_count')
            ->join('n_form_passengers_persons', 'n_form_passengers_persons.n_form_passengers_id', '=', 'n_form_passengers.n_form_passengers_id')
            ->join('n_form_flight', 'n_form_passengers.n_form_flight_id', '=', 'n_form_flight.n_form_flight_id')
            ->whereColumn('n_form_flight.n_form_flight_id', 'departure_dates.n_form_flight_id')
            ->latest('pass_count')
            ->take(1);
    }

    private function sortByFlightCategoryNamerus()
    {
        return FlightCategory::select('NAMERUS')
            ->join('n_form_flight', 'flight_categories.CATEGORIES_ID', '=', 'n_form_flight.transportation_categories_id')
            ->whereColumn('n_form_flight.n_form_flight_id', 'departure_dates.n_form_flight_id')
            ->latest('flight_categories.NAMERUS')
            ->take(1);
    }

    private function sortByCrewMembersCount()
    {
        return NFormCrew::selectRaw('count(n_form_crew_member.*) as c_m_count')
            ->join('n_form_crew_member', 'n_form_crew_member.n_form_crew_id', '=', 'n_form_crew.n_form_crew_id')
            ->join('n_form_flight', 'n_form_crew.n_form_flight_id', '=', 'n_form_flight.n_form_flight_id')
            ->whereColumn('n_form_flight.n_form_flight_id', 'departure_dates.n_form_flight_id')
            ->latest('c_m_count')
            ->take(1);
    }

    private function sortByCrewGroupsSumQuantity()
    {
        return NFormCrew::selectRaw('sum(n_form_crew_group.quantity) as c_g_sum')
            ->join('n_form_crew_group', 'n_form_crew_group.n_form_crew_id', '=', 'n_form_crew.n_form_crew_id')
            ->join('n_form_flight', 'n_form_crew.n_form_flight_id', '=', 'n_form_flight.n_form_flight_id')
            ->whereColumn('n_form_flight.n_form_flight_id', 'departure_dates.n_form_flight_id')
            ->latest('c_g_sum')
            ->take(1);
    }

    private function sortByNFormIsFavorite()
    {
        return FavoriteUserNForm::selectRaw('count(*) as c')
            ->join('n_forms', 'n_forms.id_pakus', '=', 'favorites_user_n_form.id_pakus')
            ->join('n_form_flight', 'n_form_flight.n_forms_id', '=', 'n_forms.n_forms_id')
            ->where('favorites_user_n_form.user_id', \Auth::id())
            ->whereColumn('n_forms.id_pakus', 'favorites_user_n_form.id_pakus')
            ->whereColumn('n_form_flight.n_form_flight_id', 'departure_dates.n_form_flight_id')
            ->latest('c')
            ->take(1);
    }
}
