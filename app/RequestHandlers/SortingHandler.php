<?php /** @noinspection PhpMultipleClassDeclarationsInspection */


namespace App\RequestHandlers;


use App\Http\Controllers\Api\V1\User\FavoriteFormNController;
use App\Models\Aircraft;
use App\Models\Airline;
use App\Models\DepartureDate;
use App\Models\NFormAircraft;
use App\Models\NFormAirnavPayer;
use App\Models\FavoriteUserNForm;
use App\Models\NFormCrew;
use App\Models\NFormCrewGroup;
use App\Models\NFormFlight;
use App\Models\NForm;
use App\Models\NFormFlightAgreementSign;
use App\Models\Pivot\RoleUser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Api\V1\RegistryController;
use Illuminate\Support\Facades\Auth;

class SortingHandler
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

        /* Users Sorting */

        /* Сортировка по Дате регистрации user */

        if ($this->request->get('users_last_login') !== null) {
            if (array_key_exists($this->sortKey, $this->request->get('users_last_login'))) {
                $order = $this->request->get('users_last_login')[$this->sortKey];

                $this->query->orderBy('last_login', $order);
            }

        }

        /* Сортировка по Дате регистрации user */

        if ($this->request->get('users_created_at') !== null) {
            if (array_key_exists($this->sortKey, $this->request->get('users_created_at'))) {
                $order = $this->request->get('users_created_at')[$this->sortKey];

                $this->query->orderBy('created_at', $order);
            }

        }

        /* Сортировка по inn user */

        if ($this->request->get('users_inn') !== null) {
            if (array_key_exists($this->sortKey, $this->request->get('users_inn'))) {
                $order = $this->request->get('users_inn')[$this->sortKey];

                $this->query->orderBy('inn', $order);
            }

        }

        /* Сортировка по sita user */

        if ($this->request->get('users_sita') !== null) {
            if (array_key_exists($this->sortKey, $this->request->get('users_sita'))) {
                $order = $this->request->get('users_sita')[$this->sortKey];

                $this->query->orderBy('sita', $order);
            }

        }

        /* Сортировка по aftn user */

        if ($this->request->get('users_aftn') !== null) {
            if (array_key_exists($this->sortKey, $this->request->get('users_aftn'))) {
                $order = $this->request->get('users_aftn')[$this->sortKey];

                $this->query->orderBy('aftn', $order);
            }

        }

        /* Сортировка по роли user */

        if ($this->request->get('users_role') !== null) {
            if (array_key_exists($this->sortKey, $this->request->get('users_role'))) {
                $order = $this->request->get('users_role')[$this->sortKey];

                $this->query->orderBy('active_role_id', $order);
            }

        }

        /* Сортировка по email user */

        if ($this->request->get('users_email') !== null) {
            if (array_key_exists($this->sortKey, $this->request->get('users_email'))) {
                $order = $this->request->get('users_email')[$this->sortKey];

                $this->query->orderBy('email', $order);
            }

        }

        /* Сортировка по phone user */

        if ($this->request->get('users_phone') !== null) {
            if (array_key_exists($this->sortKey, $this->request->get('users_phone'))) {
                $order = $this->request->get('users_phone')[$this->sortKey];

                $this->query->orderBy('phone', $order);
            }

        }

        /* Сортировка по surname user */

        if ($this->request->get('users_surname') !== null) {
            if (array_key_exists($this->sortKey, $this->request->get('users_surname'))) {
                $order = $this->request->get('users_surname')[$this->sortKey];

                $this->query->orderBy('surname', $order);
            }

        }

        /* Сортировка по id user */

        if ($this->request->get('user_id') !== null) {
            if (array_key_exists($this->sortKey, $this->request->get('user_id'))) {
                $order = $this->request->get('user_id')[$this->sortKey];

                $this->query->orderBy('id', $order);
            }

        }

        /* Сортировка по status user */

        if ($this->request->get('users_status') !== null) {
            if (array_key_exists($this->sortKey, $this->request->get('users_status'))) {
                $order = $this->request->get('users_status')[$this->sortKey];

                $this->query->orderBy('status', $order);
            }

        }



        /* NForm Sorting */

        /* Сортировка по id формы Н */
        if ($this->request->get('n_forms_id') !== null) {
            if (array_key_exists($this->sortKey, $this->request->get('n_forms_id'))) {
                $order = $this->request->get('n_forms_id')[$this->sortKey];

                $this->query->orderBy('n_forms_id', $order);
            }

        }

        /* Сортировка по номеру разрешения */
        if ($this->request->get('permit_num') !== null) {
            if (array_key_exists($this->sortKey, $this->request->get('permit_num'))) {
                $order = $this->request->get('permit_num')[$this->sortKey];

                $this->query->orderBy('permit_num', $order);
            }
        }

        /* Сортировка по дате создания */
        if ($this->request->get('created_at') !== null) {
            if (array_key_exists($this->sortKey, $this->request->get('created_at'))) {
                $order = $this->request->get('created_at')[$this->sortKey];

                $this->query->orderBy('created_at', $order);
            }
        }

        /* Сортировка по наименованию авиапредприятия */
        if ($this->request->get('name_airline')) {
            if (array_key_exists($this->sortKey, $this->request->get('name_airline'))) {
                $order = $this->request->get('name_airline')[$this->sortKey];

                $this->query->orderBy($this->sortByAirlineFullNameRus(), $order);
            }
        }

        /* Сортировка по регистрационному номеру */
        if ($this->request->get('registration_number') !== null) {
            if (array_key_exists($this->sortKey, $this->request->get('registration_number'))) {
                $order = $this->request->get('registration_number')[$this->sortKey];

                $this->query->orderBy($this->sortByAircraftRegistrationNumber(), $order);
            }
        }

        /* Сортировка по номеру рейса */
        if ($this->request->get('flight_num') !== null) {
            if (array_key_exists($this->sortKey, $this->request->get('flight_num'))) {
                $order = $this->request->get('flight_num')[$this->sortKey];

                $this->query->orderBy($this->sortByFlightNum(), $order);
            }
        }

        /* Сортировка по датам повторов */
        if ($this->request->get('date') !== null) {
            if (array_key_exists($this->sortKey, $this->request->get('date'))) {
                $order = $this->request->get('date')[$this->sortKey];

                $this->query->orderBy($this->sortByDepartureDates(), $order);
            }
        }

        /* Сортировка по времени вылета */
        if ($this->request->get('departure_time') !== null) {
            if (array_key_exists($this->sortKey, $this->request->get('departure_time'))) {
                $order = $this->request->get('departure_time')[$this->sortKey];

                $this->query->orderBy($this->sortByFlightDepartureTime(), $order);
            }
        }

        /* Сортировка по времени прилета */
        if ($this->request->get('landing_time') !== null) {
            if (array_key_exists($this->sortKey, $this->request->get('landing_time'))) {
                $order = $this->request->get('landing_time')[$this->sortKey];

                $this->query->orderBy($this->sortByFlightLandingTime(), $order);
            }
        }

        /* Сортировка по количеству экипажа groups */
        if ($this->request->get('crew_groups_quantity') !== null) {
            if (array_key_exists($this->sortKey, $this->request->get('crew_groups_quantity'))) {
                $order = $this->request->get('crew_groups_quantity')[$this->sortKey];

             $this->query->orderBy($this->sortByCrewGroupsQuantity(), $order);

            }
        }

        /* Сортировка по количеству экипажа members */
        if ($this->request->get('crew_member_count') !== null) {
            if (array_key_exists($this->sortKey, $this->request->get('crew_member_count'))) {
                $order = $this->request->get('crew_member_count')[$this->sortKey];

                $this->query->orderBy($this->sortByCrewMemberQuantity(), $order);

            }
        }

        /* Сортировка по оплате АНО*/
        if ($this->request->get('is_paid') !== null) {
            if (array_key_exists($this->sortKey, $this->request->get('is_paid'))) {
                $order = $this->request->get('is_paid')[$this->sortKey];

                $this->query->orderBy($this->sortByIsPaid(), $order);

            }
        }

        /* Сортировка по цели выполнения перевозки*/
        if ($this->request->get('purpose') !== null) {
            if (array_key_exists($this->sortKey, $this->request->get('purpose'))) {
                $order = $this->request->get('purpose')[$this->sortKey];

                $this->query->orderBy($this->sortByPurpose(), $order);

            }
        }

        /* Сортировка по  Категория перевозки */
        if ($this->request->get('transportation_categories') !== null) {
            if (array_key_exists($this->sortKey, $this->request->get('transportation_categories'))) {
                $order = $this->request->get('transportation_categories')[$this->sortKey];

                $this->query->orderBy($this->sortByTransportationCategories(), $order);

            }
        }

        /* Сортировка по  Вылету */
        if ($this->request->get('departure_airport') !== null) {
            if (array_key_exists($this->sortKey, $this->request->get('departure_airport'))) {
                $order = $this->request->get('departure_airport')[$this->sortKey];

                $this->query->orderBy($this->sortByDepartureAirport($order), $order);

            }
        }

        /* Сортировка по  Прилету */
        if ($this->request->get('landing_airport') !== null) {
            if (array_key_exists($this->sortKey, $this->request->get('landing_airport'))) {
                $order = $this->request->get('landing_airport')[$this->sortKey];

                $this->query->orderBy($this->sortByLandingAirport($order), $order);

            }
        }

        /* Сортировка по времени обновления статуса */
        if ($this->request->get('status_update_datetime') !== null) {
            if (array_key_exists($this->sortKey, $this->request->get('status_update_datetime'))) {
                $order = $this->request->get('status_update_datetime')[$this->sortKey];

                $this->query->orderBy($this->sortByUpdateStatusDateTime(), $order);
            }
        }

        /* Сортировка по общему весу груза */
        if ($this->request->get('weight_sum_weight') !== null) {
            if (array_key_exists($this->sortKey, $this->request->get('weight_sum_weight'))) {
                $order = $this->request->get('weight_sum_weight')[$this->sortKey];

                $this->query->orderBy($this->sortByCargoWeight(), $order);
            }
        }

        /* Сортировка по количеству пассажиров */
        if ($this->request->get('quantity') !== null) {
            if (array_key_exists($this->sortKey, $this->request->get('quantity'))) {
                $order = $this->request->get('quantity')[$this->sortKey];

                $this->query->orderBy($this->sortByPassengersQuantity(), $order);
            }
        }

        /* Сортировка по Типу основного ВС */
        if ($this->request->get('aircraft_type_icao') !== null) {
            if (array_key_exists($this->sortKey, $this->request->get('aircraft_type_icao'))) {
                $order = $this->request->get('aircraft_type_icao')[$this->sortKey];

                $this->query->orderBy($this->sortByAircraftTypeIcao(), $order)
                ->whereHas('aircrafts', function ($query){
                    $query->where('is_main',1);
                });
            }
        }

        /* Сортировка по Типу посадки */
        if ($this->request->get('landing_type') !== null) {
            if (array_key_exists($this->sortKey, $this->request->get('landing_type'))) {
                $order = $this->request->get('landing_type')[$this->sortKey];

                $this->query->orderBy($this->sortByLandingType(), $order);
            }
        }

        /* Сортировка по Владельцу ВС */
        if ($this->request->get('aircraft_owner') !== null) {
            if (array_key_exists($this->sortKey, $this->request->get('aircraft_owner'))) {
                $order = $this->request->get('aircraft_owner')[$this->sortKey];

                $this->query->orderBy($this->sortByAircraftOwner(), $order);
            }
        }

        /* Сортировка по статусу */
        if ($this->request->get('status') !== null) {
            if (array_key_exists($this->sortKey, $this->request->get('status'))) {
                $order = $this->request->get('status')[$this->sortKey];

                $this->query->orderBy($this->sortByStatus(), $order);
            }
        }

        /* Сортировка по наименованию аэропорта вылета */
        if ($this->request->get('departure_airport_name') !== null) {
            if (array_key_exists($this->sortKey, $this->request->get('status'))) {
                $order = $this->request->get('status')[$this->sortKey];

                $this->query->orderBy($this->sortByStatus(), $order);
            }
        }

        /* Сортировка по избранному */
        if ($this->request->get('is_favorite') !== null) {
            if (array_key_exists($this->sortKey, $this->request->get('is_favorite'))) {
                $order = $this->request->get('is_favorite')[$this->sortKey];

                $this->query->orderBy($this->sortByFavoriteForm(), $order);
            }
        }

    }

    /* custom sorts */

    protected function sortByUserRole(){
        return RoleUser::selectRaw('roles.name_rus')
            ->join('roles','roles.id','=','role_user.role_id')
            ->whereColumn('role_user.user_id', 'users.id')
            ->latest('roles.name_rus')
            ->take(1);
    }

    protected function sortByFavoriteForm(){

        return FavoriteUserNForm::selectRaw('count(favorites_user_n_form.*) as favorite_form')
            ->whereColumn('n_forms.id_pakus','favorites_user_n_form.id_pakus')
            ->where('favorites_user_n_form.user_id', Auth::id())
            ->latest('favorite_form')
            ->take(1);
    }

    protected function  sortByStatus(){
        return NFormFlight::select('n_form_flight_statuses.name_rus')
            ->join('n_form_flight_statuses','n_form_flight_statuses.id','=','n_form_flight.status_id')
            ->whereColumn('n_form_flight.n_forms_id', 'n_forms.n_forms_id')
            ->latest('n_form_flight_statuses.name_rus')
            ->take(1);

    }

    protected function  sortByAircraftOwner(){
        return NFormAircraft::select('n_form_aircraft_owner.name')
            ->join('n_form_aircraft_owner','n_form_aircraft_owner.n_form_aircraft_owner_id','=','n_form_aircrafts.n_form_aircraft_owner_id')
            ->whereColumn('n_form_aircrafts.n_forms_id', 'n_forms.n_forms_id')
            ->latest('n_form_aircraft_owner.name')
            ->take(1);
        // решить вопрос с null
    }


    protected function  sortByLandingType(){
        return NFormFlight::select('n_form_flight.landing_type')
            ->whereColumn('n_form_flight.n_forms_id', 'n_forms.n_forms_id')
            ->latest('n_form_flight.landing_type')
            ->take(1);
        // решить вопрос с null
    }


    protected function  sortByAircraftTypeIcao(){
        return NFormAircraft::selectRaw('aircraft_type_icao')
            ->whereColumn('n_form_aircrafts.n_forms_id', 'n_forms.n_forms_id')
            ->latest('aircraft_type_icao')
            ->take(1);

    }

    protected function  sortByPassengersQuantity(){
        return NFormFlight::selectRaw('sum(n_form_passengers.quantity) as passengers_quantity_sum')
            ->join('n_form_passengers','n_form_passengers.n_form_flight_id','=','n_form_flight.n_form_flight_id')
            ->whereColumn('n_form_flight.n_forms_id', 'n_forms.n_forms_id')
            ->latest('passengers_quantity_sum')
            ->take(1);
        // решить вопрос с null
    }

    protected function  sortByCargoWeight(){
        return NFormFlight::selectRaw('sum(n_form_cargo.weight) as cargo_sum')
            ->join('n_form_cargo','n_form_cargo.n_form_flight_id','=','n_form_flight.n_form_flight_id')
            ->whereColumn('n_form_flight.n_forms_id', 'n_forms.n_forms_id')
            ->latest('cargo_sum')
            ->take(1);
        // решить вопрос с null
    }


    protected function  sortByUpdateStatusDateTime(){
        return NFormFlight::select('n_form_flight.status_change_datetime')
            ->whereColumn('n_form_flight.n_forms_id', 'n_forms.n_forms_id')
            ->latest('n_form_flight.status_change_datetime')
            ->take(1);
    }

    protected function  sortByLandingAirport($sort){
        $word = null;
        if($sort == 'asc'){
            $word = "ZZZZ";
        }else{
            $word = "AAAA";
        }
        return NFormFlight::selectRaw('(coalesce(n_form_flight.landing_airport_icao,\''.$word.'\')) as landing_airport_icao')
            ->whereColumn('n_form_flight.n_forms_id', 'n_forms.n_forms_id')
            ->take(1);
    }


    protected function  sortByDepartureAirport($sort){
        $word = null;
        if($sort == 'asc'){
            $word = "ZZZZ";
        }else{
            $word = "AAAA";
        }
        return NFormFlight::selectRaw('(coalesce(n_form_flight.departure_airport_icao,\''.$word.'\')) as departure_airport_icao')
            ->whereColumn('n_form_flight.n_forms_id', 'n_forms.n_forms_id')
            ->take(1);
    }

    protected function  sortByTransportationCategories(){
        return NFormFlight::select('flight_categories.NAMERUS')
            ->join('flight_categories','flight_categories.CATEGORIES_ID','=','n_form_flight.transportation_categories_id')
            ->whereColumn('n_form_flight.n_forms_id', 'n_forms.n_forms_id')
            ->latest('flight_categories.NAMERUS')
            ->take(1);
    }

    protected function  sortByPurpose(){
        return NFormFlight::select('purpose')
            ->whereColumn('n_form_flight.n_forms_id', 'n_forms.n_forms_id')
            ->latest('n_form_flight.purpose')
            ->take(1);
    }

    protected function sortByIsPaid(){
        return NFormAirnavPayer::select('is_paid')
            ->whereColumn('n_form_airnav_payer.n_forms_id', 'n_forms.n_forms_id')
            ->latest('n_form_airnav_payer.is_paid')
            ->take(1);
    }

    protected function sortByCrewGroupsQuantity(){
        return NFormFlight::selectRaw('(coalesce(sum(n_form_crew_group.quantity),0)+count(n_form_crew_member.*)) as sum_quantity')
            ->join('n_form_crew','n_form_crew.n_form_flight_id','=','n_form_flight.n_form_flight_id')
            ->join('n_form_crew_group','n_form_crew_group.n_form_crew_id','=','n_form_crew.n_form_crew_id')
            ->join('n_form_crew_member','n_form_crew_member.n_form_crew_id','=','n_form_crew.n_form_crew_id')
            ->whereColumn('n_form_flight.n_forms_id','n_forms.n_forms_id')
            ->latest('sum_quantity')
            ->take(1);
    }

    protected function sortByCrewMemberQuantity(){
        return NFormFlight::selectRaw('count(n_form_crew_member.*) as count_members')
            ->join('n_form_crew','n_form_crew.n_form_flight_id','=','n_form_flight.n_form_flight_id')
            ->join('n_form_crew_member','n_form_crew_member.n_form_crew_id','=','n_form_crew.n_form_crew_id')
            ->whereColumn('n_form_flight.n_forms_id','n_forms.n_forms_id')
            ->latest('count_members')
            ->take(1);
    }
    protected function sortByAirlineFullNameRus()
    {
        return Airline::select('FULLNAMERUS')
            ->join('n_form_airlines', 'n_form_airlines.AIRLINES_ID', '=', 'AIRLINES.AIRLINES_ID')
            ->whereColumn('n_form_airlines.n_forms_id', 'n_forms.n_forms_id')
            ->latest('AIRLINES.FULLNAMERUS')
            ->take(1);
    }

    protected function sortByAircraftRegistrationNumber()
    {
        return NFormAircraft::select('registration_number')
            ->whereColumn('n_form_aircrafts.n_forms_id', 'n_forms.n_forms_id')
            ->latest()
            ->take(1);
    }

    protected function sortByFlightNum()
    {
        return NFormFlight::select('flight_num')
            ->whereColumn('n_form_flight.n_forms_id', 'n_forms.n_forms_id')
            ->latest()
            ->take(1);
    }

    protected function sortByDepartureDates()
    {
        return DepartureDate::select('date')
            ->join('n_form_flight', 'n_form_flight.n_form_flight_id', '=', 'departure_dates.n_form_flight_id')
            ->whereColumn('n_form_flight.n_forms_id', 'n_forms.n_forms_id')
            ->latest('departure_dates.date')
            ->take(1);
    }

    protected function sortByFlightLandingTime()
    {
        return NFormFlight::select('landing_time')
            ->whereColumn('n_form_flight.n_forms_id', 'n_forms.n_forms_id')
            ->latest()
            ->take(1);
    }

    protected function sortByFlightDepartureTime()
    {
        return NFormFlight::select('departure_time')
            ->whereColumn('n_form_flight.n_forms_id', 'n_forms.n_forms_id')
            ->latest()
            ->take(1);
    }

}
