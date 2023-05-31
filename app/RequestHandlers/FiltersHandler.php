<?php


namespace App\RequestHandlers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\NFormAirline;
use Illuminate\Support\Facades\Auth;
class FiltersHandler
{
    private Builder $query;
    private Request $request;
    private string $paramsKey;
    private string $pKey;

    /**
     * FiltersHandler constructor.
     * @param Builder $query
     * @param Request $request
     */
    public function __construct(Builder $query, Request $request)
    {
        $this->query = $query;
        $this->request = $request;
        $this->paramsKey = "'params'";
        $this->pKey = 'n_forms_id'; //primary key of n_forms_table

    }

    public function handle()
    {
        /* Users Filters / Search */


        /* Поиск ИНН */

        if ($this->request->get('general_search') !== null) {
            if (array_key_exists($this->paramsKey, $this->request->get('general_search'))) {
                $params = $this->request->get('general_search')[$this->paramsKey];

                $this->query->where('inn','ilike', '%'.$params.'%')
                ->orWhere('email','ilike', '%'.$params.'%')
                ->orWhere('id','ilike', '%'.$params.'%')
                ->orWhere('name','ilike', '%'.$params.'%')
                ->orWhere('patronymic','ilike', '%'.$params.'%')
                ->orWhere('surname','ilike', '%'.$params.'%')
                ->orWhere('phone','ilike', '%'.$params.'%')
                ->orWhere('aftn','ilike', '%'.$params.'%')
                ->whereHas('roles', function ($query) use ($params){
                    $query->where('name_rus', 'ilike', '%'.$params.'%')
                        ->orWhere('name_lat','ilike', '%'.$params.'%');
                });
            }
        }



        /* Фильтрация Дата последнего входа (интервал даты и времени)*/

        if ($this->request->get('user_last_login') !== null) {
            if (array_key_exists($this->paramsKey, $this->request->get('user_last_login'))) {

                $params = $this->parseParams($this->request->get('user_last_login')[$this->paramsKey]);

                $timeFrom = Carbon::createFromTimestamp($params[0], 'MSK');
                $timeTo = Carbon::createFromTimestamp($params[1], 'MSK');

                $this->query->whereRaw("last_login BETWEEN ? AND ?", [$timeFrom, $timeTo]);
            }
        }

        /* Фильтрация Дата регистрации пользователя (интервал даты и времени)*/

        if ($this->request->get('user_created_at') !== null) {
            if (array_key_exists($this->paramsKey, $this->request->get('user_created_at'))) {

                $params = $this->parseParams($this->request->get('user_created_at')[$this->paramsKey]);

                $timeFrom = Carbon::createFromTimestamp($params[0], 'MSK');
                $timeTo = Carbon::createFromTimestamp($params[1], 'MSK');

                $this->query->whereRaw("created_at BETWEEN ? AND ?", [$timeFrom, $timeTo]);
            }
        }

        /* Фильтрация ИНН */

        if ($this->request->get('inn') !== null) {
            if (array_key_exists($this->paramsKey, $this->request->get('inn'))) {
                $params = $this->parseParams($this->request->get('inn')[$this->paramsKey]);

                $this->query->whereIn('inn',$params);
            }
        }

        /* Поиск ИНН */

        if ($this->request->get('search_inn') !== null) {
            if (array_key_exists($this->paramsKey, $this->request->get('search_inn'))) {
                $params = $this->request->get('search_inn')[$this->paramsKey];

                $this->query->where('inn','ilike', '%'.$params.'%');
            }
        }


        /* Фильтрация SITA */

        if ($this->request->get('sita') !== null) {
            if (array_key_exists($this->paramsKey, $this->request->get('sita'))) {
                $params = $this->parseParams($this->request->get('sita')[$this->paramsKey]);

                $this->query->whereIn('sita',$params);
            }
        }

        /* Поиск SITA */

        if ($this->request->get('search_sita') !== null) {
            if (array_key_exists($this->paramsKey, $this->request->get('search_sita'))) {
                $params = $this->request->get('search_sita')[$this->paramsKey];

                $this->query->where('sita','ilike', '%'.$params.'%');
            }
        }


        /* Фильтрация АФТН */

        if ($this->request->get('aftn') !== null) {
            if (array_key_exists($this->paramsKey, $this->request->get('aftn'))) {
                $params = $this->parseParams($this->request->get('aftn')[$this->paramsKey]);

                $this->query->whereIn('aftn',$params);
            }
        }

        /* Поиск АФТН */

        if ($this->request->get('search_aftn') !== null) {
            if (array_key_exists($this->paramsKey, $this->request->get('search_aftn'))) {
                $params = $this->request->get('search_aftn')[$this->paramsKey];

                $this->query->where('aftn','ilike', '%'.$params.'%');
            }
        }


        /* Фильтрация идентификатора */

        if ($this->request->get('user_id') !== null) {
            if (array_key_exists($this->paramsKey, $this->request->get('user_id'))) {
                $params = $this->parseParams($this->request->get('user_id')[$this->paramsKey]);

                $this->query->whereRaw('users.id BETWEEN ? AND ?', [$params[0],$params[1]]);
            }
        }

        /* Поиск номера идентификатора */

        if ($this->request->get('search_user_id') !== null) {
            if (array_key_exists($this->paramsKey, $this->request->get('search_user_id'))) {
                $params = $this->request->get('search_user_id')[$this->paramsKey];

                $this->query->where('id','ilike', '%'.$params.'%');
            }
        }

        /* Фильтрация ФИО, Email, телефон */

        if ($this->request->get('user_filter') !== null) {
            if (array_key_exists($this->paramsKey, $this->request->get('user_filter'))) {
                $params = $this->parseParams($this->request->get('user_filter')[$this->paramsKey]);

                $this->query->whereIn('users.id', $params);
            }
        }

        /* Поиск ФИО*/

        if ($this->request->get('search_full_name') !== null) {
            if (array_key_exists($this->paramsKey, $this->request->get('search_full_name'))) {
                $params = $this->request->get('search_full_name')[$this->paramsKey];

                $this->query->where(function ($query) use ($params){
                    $query
                        ->where('users.name','ilike', '%'.$params.'%')
                        ->orWhere('users.patronymic','ilike', '%'.$params.'%')
                        ->orWhere('users.surname','ilike', '%'.$params.'%')
                        ->distinct('users.name','users.patronymic','users.surname');
                });
            }
        }



        /* Поиск по телефону */

        if ($this->request->get('search_phone') !== null) {
            if (array_key_exists($this->paramsKey, $this->request->get('search_phone'))) {
                $params = $this->request->get('search_phone')[$this->paramsKey];

                $this->query
                    ->where('users.phone','ilike', '%'.$params.'%');
            }
        }

        /* Поиск по email */

        if ($this->request->get('search_email') !== null) {
            if (array_key_exists($this->paramsKey, $this->request->get('search_email'))) {
                $params = $this->request->get('search_email')[$this->paramsKey];

                $this->query
                    ->where('users.email','ilike', '%'.$params.'%');
            }
        }


        /* Поиск по Роли*/

        if ($this->request->get('search_role') !== null) {
            if (array_key_exists($this->paramsKey, $this->request->get('search_role'))) {
                $params = $this->request->get('search_role')[$this->paramsKey];

                $this->query
                    ->join('role_user','role_user.user_id','=','users.id')
                    ->join('roles','role_user.role_id','=','roles.id')
                    ->select('roles.name_rus','roles.name_lat','roles.id')
                    ->where('roles.name_rus','ilike','%'.$params.'%')
                    ->orWhere('roles.name_lat','ilike','%'.$params.'%')
                    ->distinct('roles.name_rus','roles.name_lat');
            }
        }

        /* Фильтрация по Роли*/

        if ($this->request->get('role') !== null) {
            if (array_key_exists($this->paramsKey, $this->request->get('role'))) {
                $params = $this->parseParams($this->request->get('role')[$this->paramsKey]);

                $this->query->whereIn('active_role_id', $params);
            }
        }

        /* Поиск по Статусу*/

        if ($this->request->get('user_status') !== null) {
            if (array_key_exists($this->paramsKey, $this->request->get('user_status'))) {
                $params = $this->parseParams($this->request->get('user_status')[$this->paramsKey]);

                $this->query
                    ->whereIn('active',$params);
            }
        }

        /* Фильтрация по Статусу*/

        if ($this->request->get('user_status_filter') !== null) {
            if (array_key_exists($this->paramsKey, $this->request->get('user_status_filter'))) {
                $params = $this->parseParams($this->request->get('user_status_filter')[$this->paramsKey]);

                $this->query->whereIn('status', $params);
            }
        }



        /* nForms Filters / Search */

        /* Фильтрация по избранному */

        if ($this->request->get('favorite_filter') !== null) {
            if (array_key_exists($this->paramsKey, $this->request->get('favorite_filter'))) {

                $params = $this->parseParams($this->request->get('favorite_filter')[$this->paramsKey]);

                $this->query->whereHas('flights',function ($query) use ($params){
                    $query->whereHas('agreementSigns', function($query) use ($params){
                        $query->whereIn('role_id',$this->getRolesIdsFromUser())
                            ->whereHas('sign',function($query) use ($params){
                                $query->where('id',$params);
                            });
                    });

                });
            }
        }

        /* Фильтрация по знакам */

        if ($this->request->get('sign_filter') !== null) {
            if (array_key_exists($this->paramsKey, $this->request->get('sign_filter'))) {

                $params = $this->parseParams($this->request->get('sign_filter')[$this->paramsKey]);

                $this->query->whereHas('flights',function ($query) use ($params){
                    $query->whereHas('agreementSigns', function($query) use ($params){
                        $query->whereIn('role_id',$this->getRolesIdsFromUser())
                            ->whereHas('sign',function($query) use ($params){
                                $query->whereIn('id',$params);
                            });
                    });

                });
            }
        }

        /* Поиск уникальных номеров разрешения */
        if ($this->request->get('search_permit_num') !== null) {
            if (array_key_exists($this->paramsKey, $this->request->get('search_permit_num'))) {
                $params = $this->request->get('search_permit_num')[$this->paramsKey];

                $this->query->where('permit_num','ilike', '%'.$params.'%')
                ->distinct('permit_num')
                ->orderBy('permit_num','asc');
            }
        }

        /* Номер разрешения (текстовый)*/
        if ($this->request->get('permit_num') !== null) {
            if (array_key_exists($this->paramsKey, $this->request->get('permit_num'))) {
                $params = $this->parseParams($this->request->get('permit_num')[$this->paramsKey]);

                $this->query->whereIn('permit_num', $params);
            }
        }

        /* Дата и время регистрации формы (интервал времени)*/
        if ($this->request->get('created_at') !== null) {
            if (array_key_exists($this->paramsKey, $this->request->get('created_at'))) {
                $params = $this->parseParams($this->request->get('created_at')[$this->paramsKey]);
                $timeFrom = Carbon::createFromTimestamp($params[0], 'MSK')->toDateTimeString();
                $timeTo = Carbon::createFromTimestamp($params[1], 'MSK')->toDateTimeString();

                $this->query->whereRaw('created_at >= \''.$timeFrom.'\' AND created_at <=\''.$timeTo.'\'');
//                $this->query->whereDate('created_at', '=', [$params[0], $params[1]]);
            }
        }

        /* Регистрационный номер основного ВС (текстовый)*/
        if ($this->request->get('registration_number') !== null) {
            if (array_key_exists($this->paramsKey, $this->request->get('registration_number'))) {

                $params = $this->parseParams($this->request->get('registration_number')[$this->paramsKey]);

                $this->query
                    ->whereHas('aircrafts', function (Builder $innerQuery) use ($params)  {
                        $innerQuery->where('is_main', 1)
                        ->whereIn('registration_number',$params);
                });
            }
        }

        /* Поиск уникальных значений Регистрационный номер основного ВС */
        if ($this->request->get('search_registration_number') !== null) {
            if (array_key_exists($this->paramsKey, $this->request->get('search_registration_number'))) {
                $params = $this->request->get('search_registration_number')[$this->paramsKey];

                $this->query
                    ->join('n_form_aircrafts','n_form_aircrafts.n_forms_id','=','n_forms.n_forms_id')
                    ->where('n_form_aircrafts.is_main','=','1')
                    ->where('registration_number','ilike', '%'.$params.'%')
                    ->select('registration_number','n_forms.*')
                    ->distinct('registration_number')
                    ->orderBy('registration_number','asc');

            }
        }

        /* Поиск по типу основного ВС */
        if ($this->request->get('search_aircraft_type_icao') !== null) {
            if (array_key_exists($this->paramsKey, $this->request->get('search_aircraft_type_icao'))) {
                $params = $this->request->get('search_aircraft_type_icao')[$this->paramsKey];

                $this->query
                    ->join('n_form_aircrafts','n_form_aircrafts.n_forms_id','=','n_forms.n_forms_id')
                    ->where('n_form_aircrafts.is_main','=','1')
                    ->where('aircraft_type_icao','ilike', '%'.$params.'%')
                    ->select('aircraft_type_icao','n_forms.*')
                    ->distinct('aircraft_type_icao')
                    ->orderBy('aircraft_type_icao','asc');
            }
        }

        /* Тип основного ВС */
        if ($this->request->get('aircraft_type_icao') !== null) {
            if (array_key_exists($this->paramsKey, $this->request->get('aircraft_type_icao'))) {
                $params = $this->parseParams($this->request->get('aircraft_type_icao')[$this->paramsKey]);

                $this->query
                    ->whereHas('aircrafts', function ($query) use ($params)  {
                        $query->where('is_main', 1)
                            ->whereIn('aircraft_type_icao',$params);
                    });
            }
        }

        /*  Поиск по уникальным Владелец ВС */
        if ($this->request->get('search_aircraft_owner') !== null) {
            if (array_key_exists($this->paramsKey, $this->request->get('search_aircraft_owner'))) {
                $params = $this->request->get('search_aircraft_owner')[$this->paramsKey];

                $this->query
                    ->join('n_form_aircrafts','n_form_aircrafts.n_forms_id','=','n_forms.n_forms_id')
                    ->join('n_form_aircraft_owner','n_form_aircraft_owner.n_form_aircraft_owner_id','=','n_form_aircrafts.n_form_aircraft_owner_id')
                    ->where('name','ilike', '%'.$params.'%')
                    ->select('name','n_forms.*')
                    ->distinct('n_form_aircraft_owner.name')
                    ->orderBy('n_form_aircraft_owner.name','asc');
            }
        }


        /*  Владелец ВС */
        if ($this->request->get('aircraft_owner') !== null) {
            if (array_key_exists($this->paramsKey, $this->request->get('aircraft_owner'))) {
                $params = $this->parseParams($this->request->get('aircraft_owner')[$this->paramsKey]);

                $this->query->whereHas('aircrafts', function($query) use ($params){
                    $query->whereHas('aircraftOwner', function ($query) use ($params){
                       $query->whereIn('name',$params);
                    });
                });
            }
        }

        /* Поиск уникальных Номер рейса (текстовый)*/
        if ($this->request->get('search_flight_num') !== null) {
            if (array_key_exists($this->paramsKey, $this->request->get('search_flight_num'))) {
                $params = $this->request->get('search_flight_num')[$this->paramsKey];

                $this->query
                    ->join('n_form_flight','n_form_flight.n_forms_id','=','n_forms.n_forms_id')
                    ->where('flight_num','ilike', $params.'%')
                    ->select('flight_num','n_forms.*')
                    ->distinct('n_form_flight.flight_num')
                    ->orderBy('n_form_flight.flight_num','asc');
            }
        }

        /* Номер рейса (текстовый)*/
        if ($this->request->get('flight_num') !== null) {
            if (array_key_exists($this->paramsKey, $this->request->get('flight_num'))) {
                $params = $this->parseParams($this->request->get('flight_num')[$this->paramsKey]);

                $this->query->whereHas('flights', function ($query) use ($params){
                    $query->whereIn('flight_num',$params);
                });
            }
        }

        /* Поиск уникальных Цель выполнения перевозки */
        if ($this->request->get('search_purpose') !== null) {
            if (array_key_exists($this->paramsKey, $this->request->get('search_purpose'))) {
                $params = $this->request->get('search_purpose')[$this->paramsKey];

                $this->query->join('n_form_flight','n_form_flight.n_forms_id','=','n_forms.n_forms_id')
                    ->where('purpose','ilike',$params.'%')
                    ->select('purpose','n_forms.*')
                    ->distinct('purpose')
                    ->orderBy('purpose', 'asc');
            }
        }

        /* Цель выполнения перевозки */
        if ($this->request->get('purpose') !== null) {
            if (array_key_exists($this->paramsKey, $this->request->get('purpose'))) {
                $params = $this->parseParams($this->request->get('purpose')[$this->paramsKey]);

                $this->query->whereHas('flights', function ($query) use ($params){
                    $query->whereIn('purpose',$params);
                });
            }
        }

        if ($this->request->get('search_transportation_categories') !== null) {
            if (array_key_exists($this->paramsKey, $this->request->get('search_transportation_categories'))) {
                $params = $this->request->get('search_transportation_categories')[$this->paramsKey];

                $this->query->join('n_form_flight','n_form_flight.n_forms_id','=','n_forms.n_forms_id')
                    ->join('flight_categories','flight_categories.CATEGORIES_ID','=','n_form_flight.transportation_categories_id')
                    ->where('flight_categories.NAMERUS','ilike','%'.$params.'%')
                    ->orWhere('flight_categories.NAMELAT','ilike','%'.$params.'%')
                    ->select('flight_categories.NAMELAT','flight_categories.NAMERUS','flight_categories.CATEGORIES_ID')
                    ->distinct('flight_categories.NAMELAT','flight_categories.NAMERUS');
            }
        }

        /* Категория перевозки */
        if ($this->request->get('transportation_categories') !== null) {
            if (array_key_exists($this->paramsKey, $this->request->get('transportation_categories'))) {
                $params = $this->parseParams($this->request->get('transportation_categories')[$this->paramsKey]);

                $this->query->whereHas('transportationCategories', function($query) use ($params){
                    $query->whereIn('flight_categories.CATEGORIES_ID',$params);
                });
            }
        }

        /* ПОИСК вылет (место) Название/код(координаты) */
        if ($this->request->get('search_departure_airport') !== null) {
            if (array_key_exists($this->paramsKey, $this->request->get('search_departure_airport'))) {
                $params = $this->request->get('search_departure_airport')[$this->paramsKey];

                $this->query->join('n_form_flight','n_form_flight.n_forms_id','=','n_forms.n_forms_id')
                    ->where('n_form_flight.departure_airport_namerus','ilike','%'.$params.'%')
                    ->orWhere('n_form_flight.departure_airport_namelat','ilike','%'.$params.'%')
                    ->orWhere('n_form_flight.departure_platform_coordinates','ilike',$params.'%')
                    ->orWhere('n_form_flight.departure_airport_icao','ilike','%'.$params.'%')
                    ->select('n_form_flight.departure_airport_namerus','n_form_flight.departure_airport_namelat','n_form_flight.departure_platform_coordinates','n_form_flight.departure_airport_icao','n_form_flight.departure_airport_id','n_forms.*')
                    ->distinct('n_form_flight.departure_airport_id');
            }
        }

        /* Вылет (место) Название/код(координаты) */
        if ($this->request->get('departure_airport') !== null) {
            if (array_key_exists($this->paramsKey, $this->request->get('departure_airport'))) {
                $params = $this->parseParams($this->request->get('departure_airport')[$this->paramsKey]);

                $this->query->whereHas('flights', function ($query) use ($params){
                    $query->whereHas('flightInformation', function ($query) use ($params){
                       $query->whereIn('departure_airport_id',$params);
                    });
                });
            }
        }

        /* ПОИСК прилет (место) Название/код(координаты) */
        if ($this->request->get('search_landing_airport') !== null) {
            if (array_key_exists($this->paramsKey, $this->request->get('search_landing_airport'))) {
                $params = $this->request->get('search_landing_airport')[$this->paramsKey];

                $this->query->join('n_form_flight','n_form_flight.n_forms_id','=','n_forms.n_forms_id')
                    ->where('n_form_flight.landing_airport_namerus','ilike','%'.$params.'%')
                    ->orWhere('n_form_flight.landing_airport_namelat','ilike','%'.$params.'%')
                    ->orWhere('n_form_flight.landing_platform_coordinates','ilike',$params.'%')
                    ->orWhere('n_form_flight.landing_airport_icao','ilike','%'.$params.'%')
                    ->select('n_form_flight.landing_airport_namerus','n_form_flight.landing_airport_namelat','n_form_flight.landing_platform_coordinates','n_form_flight.landing_airport_icao','n_form_flight.landing_airport_id','n_forms.*')
                    ->distinct('n_form_flight.landing_airport_id');
            }
        }

        /* Прилёт (место) Название/код(координаты) */
        if ($this->request->get('landing_airport') !== null) {
            if (array_key_exists($this->paramsKey, $this->request->get('landing_airport'))) {
                $params = $this->parseParams($this->request->get('landing_airport')[$this->paramsKey]);

                $this->query->whereHas('flights', function ($query) use ($params){
                    $query->whereHas('flightInformation', function ($query) use ($params){
                        $query->whereIn('landing_airport_id',$params);
                    });
                });
            }
        }

        /* Даты повторов (массив дат)*/
        if ($this->request->get('date') !== null) {
            if (array_key_exists($this->paramsKey, $this->request->get('date'))) {
                $params = $this->parseParams($this->request->get('date')[$this->paramsKey]);

                $this->query->whereHas('flights', function ($query) use ($params) {
                    $query->whereHas('mainDate', function($query) use ($params){
                        $query->where('is_main_date','=',1)
                            ->whereIn('date', $params);
                    });
                });
            }
        }

        /* Время вылета (фильтр по времени) HH:mm:ss */
        if ($this->request->get('departure_time') !== null) {
            if (array_key_exists($this->paramsKey, $this->request->get('departure_time'))) {
                $params = $this->parseParams($this->request->get('departure_time')[$this->paramsKey]);

//                $timeFrom = date('H:i:s', strtotime($params[0]));
//                $timeTo = date('H:i:s', strtotime($params[1]));

                $timeFrom = Carbon::parse($params[0])->format('H:i:s');
                $timeTo = Carbon::parse($params[1])->format('H:i:s');

                $this->query->whereHas('flights',function(Builder $query) use ($params){
                    $query->whereRaw('departure_time >=\''.$params[0].'\' AND departure_time <=\''.$params[1].'\'');
                });
            }
        }

        /* Время прилёта (фильтр по времени) HH:mm:ss */
        if ($this->request->get('landing_time') !== null) {
            if (array_key_exists($this->paramsKey, $this->request->get('landing_time'))) {
                $params = $this->parseParams($this->request->get('landing_time')[$this->paramsKey]);

                $timeFrom = date('H:i:s', strtotime($params[0]));
                $timeTo = date('H:i:s', strtotime($params[1]));

                $this->query->whereHas('flights',function(Builder $query) use ($timeFrom,$timeTo){
                    $query->whereRaw('cast(landing_time::time as time) BETWEEN ? AND ?', [$timeFrom,$timeTo]);
                },'=',1);
            }
        }

        /* Статус (интервал времени)*/
        if ($this->request->get('update_datetime') !== null) {
            if (array_key_exists($this->paramsKey, $this->request->get('update_datetime'))) {
                $params = $this->parseParams($this->request->get('update_datetime')[$this->paramsKey]);
                $timeFrom = Carbon::createFromTimestamp($params[0], 'MSK')->toTimeString();
                $timeTo = Carbon::createFromTimestamp($params[1], 'MSK')->toTimeString();

                $this->query->whereRaw("cast (created_at::timestamp as time) BETWEEN ? AND ?", [$timeFrom, $timeTo]);
            }
        }

        /* Пассажиры количество */
        if ($this->request->get('quantity') !== null) {
            if (array_key_exists($this->paramsKey, $this->request->get('quantity'))) {
                $params = $this->parseParams($this->request->get('quantity')[$this->paramsKey]);


                $this->query->whereHas('passengersQuantity', function ($innerQuery) use ($params) {
                    $innerQuery->whereIn('quantity', $params);
                });
            }
        }

        /* Груз общий вес */
        if ($this->request->get('weight_sum_weight') !== null) {
            if (array_key_exists($this->paramsKey, $this->request->get('weight_sum_weight'))) {
                $params = $this->parseParams($this->request->get('weight_sum_weight')[$this->paramsKey]);

                $this->query->whereHas('flights', function ($innerQuery) use ($params) {
                    $innerQuery->whereHas('cargos', function($query) use ($params){
                        $query->select(DB::raw("sum(weight) AS total_weight"))
                        ->havingRaw('sum(weight) >='.$params[0].' AND sum(weight) <= '.$params[1]);
                    });
                });

            }
        }

        /* Наименование авиапредприятия */
        if ($this->request->get('airline_namerus') !== null) {
            if (array_key_exists($this->paramsKey, $this->request->get('airline_namerus'))) {
                $params = $this->parseParams($this->request->get('airline_namerus')[$this->paramsKey]);

                $this->query->whereIn($this->pKey, $params);
            }
        }


        /*  ПОИСК Наименование авиапредприятия */
        if ($this->request->get('search_filter_name_airline') !== null) {
            if (array_key_exists($this->paramsKey, $this->request->get('search_filter_name_airline'))) {
                $param = $this->request->get('search_filter_name_airline')[$this->paramsKey];

                $this->query->join('n_form_airlines','n_form_airlines.n_forms_id','=','n_forms.n_forms_id')
                    ->where('AIRLINE_ICAO','ilike','%'.$param.'%')
                        ->orWhere('airline_namelat','ilike','%'.$param.'%')
                        ->orWhere('airline_namerus','ilike','%'.$param.'%')
                            ->select('AIRLINE_ICAO','airline_namerus','airline_namelat','AIRLINES_ID','n_forms.*')
                            ->distinct('AIRLINE_ICAO','airline_namerus','airline_namelat','AIRLINES_ID');
            }
        }

        /* Фильтрация AIRLINES_ID наименование предприятия */
        if ($this->request->get('name_airline') !== null) {
            if (array_key_exists($this->paramsKey, $this->request->get('name_airline'))) {
                $params = $this->parseParams($this->request->get('name_airline')[$this->paramsKey]);

                $this->query->whereHas('airline',function($query) use ($params){
                    $query->whereIn('AIRLINES_ID',$params);
                });
            }
        }

        /* Поиск уникальных типов посадки */
        if ($this->request->get('search_landing_type') !== null) {
            if (array_key_exists($this->paramsKey, $this->request->get('search_landing_type'))) {
                $params = $this->request->get('search_landing_type')[$this->paramsKey];

                $this->query->join('n_form_flight','n_form_flight.n_forms_id','=','n_forms.n_forms_id')
                    ->where('landing_type','ilike',$params.'%')
                    ->select('landing_type','n_forms.*')
                    ->distinct('landing_type')
                ->orderBy('landing_type', 'asc');
            }
        }

        /* Тип посадки */
        if ($this->request->get('landing_type') !== null) {
            if (array_key_exists($this->paramsKey, $this->request->get('landing_type'))) {
                $params = $this->parseParams($this->request->get('landing_type')[$this->paramsKey]);

                $this->query->whereHas('flights', function($query) use ($params){
                    $query->whereIn('landing_type',$params);
                });
            }
        }

        /* Поиск уникальны значений Статус */
        if ($this->request->get('search_status') !== null) {
            if (array_key_exists($this->paramsKey, $this->request->get('search_status'))) {

                $params = $this->request->get('search_status')[$this->paramsKey];

                $this->query->join('n_form_flight','n_form_flight.n_forms_id','=','n_forms.n_forms_id')
                    ->join('n_form_flight_statuses','n_form_flight_statuses.id','=','n_form_flight.status_id')
                    ->where('n_form_flight_statuses.name_rus','ilike','%'.$params.'%')
                    ->orWhere('n_form_flight_statuses.name_lat','ilike','%'.$params.'%')
                    ->select('n_form_flight_statuses.name_rus', 'n_form_flight_statuses.name_lat', 'n_form_flight_statuses.id','n_forms.*')
                    ->distinct('n_form_flight_statuses.id');
            }
        }

        /*  Статус */
        if ($this->request->get('status') !== null) {
            if (array_key_exists($this->paramsKey, $this->request->get('status'))) {
                $params = $this->parseParams($this->request->get('status')[$this->paramsKey]);

                $this->query->whereHas('flights', function($query) use ($params){
                    $query->whereHas('status', function($query) use ($params){
                       $query->whereIn('id',$params);
                    });
                });
            }
        }

        /* Экипаж количество groups */
        if ($this->request->get('crew_group_count') !== null) {
            if (array_key_exists($this->paramsKey, $this->request->get('crew_group_count'))) {
                $params = $this->parseParams($this->request->get('crew_group_count')[$this->paramsKey]);

                $this->query->whereHas('flights', function ($innerQuery) use ($params) {
                    $innerQuery->whereHas('crew', function($query) use ($params){
                            $query->whereHas('crewGroups',function($query) use ($params){
                                $query->select(DB::raw("sum(quantity) AS total_quantity"))
                                    ->havingRaw('sum(quantity) >='.$params[0].' AND sum(quantity) <= '.$params[1]);
                            });
                    });
                });

            }
        }
        /* Экипаж количество members */
        if ($this->request->get('crew_member_count') !== null) {
            if (array_key_exists($this->paramsKey, $this->request->get('crew_member_count'))) {
                $params = $this->parseParams($this->request->get('crew_member_count')[$this->paramsKey]);

                $this->query->whereHas('flights', function ($innerQuery) use ($params) {
                    $innerQuery->whereHas('crew', function($query) use ($params){
                        $query->whereHas('crewMembers',function($query) use ($params){
                            $query->select(DB::raw("count(*) AS total_quantity_member"))
                                ->havingRaw('count(*) >='.$params[0].' AND count(*) <= '.$params[1]);
                        });
                    });
                });

            }
        }

        /* Поиск по маршруту */

        if ($this->request->get('search_route') !== null) {
            if (array_key_exists($this->paramsKey, $this->request->get('search_route'))) {
                $params = $this->request->get('search_route')[$this->paramsKey];
                $this->query->join('n_form_flight','n_form_flight.n_forms_id','=','n_forms.n_forms_id')
                    ->where('n_form_flight.departure_airport_icao','ilike','%'.$params.'%')
                    ->orWhere('n_form_flight.landing_airport_icao','ilike','%'.$params.'%')
                    ->select('n_form_flight.departure_airport_icao', 'n_form_flight.landing_airport_icao','n_forms.n_forms_id')
                    ->distinct();
            }
        }

        /* Маршрут выдача по id формы */

        if ($this->request->get('route') !== null) {
            if (array_key_exists($this->paramsKey, $this->request->get('route'))) {
                $params = $this->parseParams($this->request->get('route')[$this->paramsKey]);

                $this->query->whereIn($this->pKey, $params);
            }
        }


        /* Оплата АНО */
        if ($this->request->get('is_paid') !== null) {
            if (array_key_exists($this->paramsKey, $this->request->get('is_paid'))) {
                $params = $this->parseParams($this->request->get('is_paid')[$this->paramsKey]);

                $this->query->whereHas('airline', function($query) use ($params){
                    $query->whereIn('ano_is_paid',$params);
                });
            }
        }

        /* Оплата АНО */
        if ($this->request->get('is_paid') !== null) {
            if (array_key_exists($this->paramsKey, $this->request->get('is_paid'))) {
                $params = $this->parseParams($this->request->get('is_paid')[$this->paramsKey]);

                $this->query->whereHas('airline', function($query) use ($params){
                    $query->whereIn('ano_is_paid',$params);
                });
            }
        }



    }

    /* helpers */
    protected function parseParams(string $str): array
    {
        return explode(',', str_replace(['[', ']'], '', $str));
    }

    protected function getRolesIdsFromUser(): array
    {
        $userWithRoles = \App\Models\User::where('id', Auth::id())->with('roles')->first()->toArray()['roles'];
        $roleIds = [];

        foreach ($userWithRoles as $userWithRole) {
            $roleIds[] += $userWithRole['id'];
        }

        return $roleIds;
    }


}
