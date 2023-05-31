<?php



namespace App\RequestHandlers;


use App\Models\AkvsAirline;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\NFormAirline;
use Illuminate\Support\Facades\Auth;

class AkvsFilterHandler
{
    private Builder $query;
    private Request $request;
    private string $paramsKey;
    private string $pKey;

    /**
     * CheckingAccessForFlightsHandler constructor.
     * @param Builder $queryBuilder
     */
    public function __construct(Builder $query, Request $request)
    {
        $this->query = $query;
        $this->request = $request;
        $this->paramsKey = "'params'";
        $this->pKey = 'n_forms_id'; //primary key of n_forms_table
    }


    public function handle(){

        /* states filter */

        if ($this->request->get('search') !== null) {
            if (array_key_exists($this->paramsKey, $this->request->get('search'))) {

                $params =  $this->request->get('search')[$this->paramsKey];

                $this->query
                    ->where(function($query) use ($params){
                        $query->where(function($query) use ($params){
                            $query->where('FULLNAMELAT','ilike','%'.$params.'%')
                                ->orWhere('FULLNAMERUS','ilike','%'.$params.'%');
                        })->orWhere('ICAOLAT3','ilike', '%'.$params.'%')
                            ->orWhere(function($query) use ($params){
                                $query->whereHas('represent_list', function($query) use ($params){
                                    $query->where('fio_rus','ilike','%'.$params.'%')
                                        ->orWhere('fio_lat','ilike','%'.$params.'%');
                                });
                    });
                });
            }
        }

        /* states filter */

        if ($this->request->get('state') !== null) {
            if (array_key_exists($this->paramsKey, $this->request->get('state'))) {

                $params = $this->parseParams($this->request->get('state')[$this->paramsKey]);

                $this->query->whereIn('STATES_ID',$params);

            }
        }

        /* states search distinct */

        if ($this->request->get('search_state') !== null) {
            if (array_key_exists($this->paramsKey, $this->request->get('search_state'))) {

                $params =  $this->request->get('search_state')[$this->paramsKey];

                $this->query->join('STATES','STATES.STATES_ID','=','AKVS_AIRLINES.STATES_ID')
                    ->where('STATES.NAMELAT','ilike','%'.$params.'%')
                    ->orWhere('STATES.NAMERUS','ilike','%'.$params.'%')
                    ->select('STATES.NAMELAT','STATES.NAMERUS','STATES.STATES_ID')
                    ->distinct('STATES.NAMERUS','STATES.NAMELAT');

            }
        }

        /* airlines represent filter */

        if ($this->request->get('russia_represent') !== null) {
            if (array_key_exists($this->paramsKey, $this->request->get('russia_represent'))) {

                $params =  $this->request->get('russia_represent')[$this->paramsKey];

                $this->query->whereHas('represent_list', function($query) use ($params){
                    $query->where('represent_type','=',2)
                        ->where(function($query) use ($params){
                            $query->where('fio_rus','ilike','%'.$params.'%')
                                ->orWhere('fio_lat','ilike','%'.$params.'%');
                        });
                });
            }
        }

        /* airlines represent search distinct */

        if ($this->request->get('search_russia_represent') !== null) {
            if (array_key_exists($this->paramsKey, $this->request->get('search_russia_represent'))) {

                $params =  $this->request->get('search_russia_represent')[$this->paramsKey];

                $this->query->join('akvs_person_info','akvs_person_info.akvs_airlines_id','=','AKVS_AIRLINES.akvs_airlines_id')
                    ->where(function($query) use ($params){
                        $query->where('akvs_person_info.fio_lat','ilike','%'.$params.'%')
                            ->orWhere('akvs_person_info.fio_rus','ilike','%'.$params.'%');
                    })
                    ->where('akvs_person_info.represent_type','=',2)
                    ->select('akvs_person_info.fio_rus','akvs_person_info.fio_lat','akvs_person_info.represent_type')
                    ->distinct('akvs_person_info.fio_rus','akvs_person_info.fio_lat');

            }
        }


        /* airlines represent filter */

        if ($this->request->get('airline_represent') !== null) {
            if (array_key_exists($this->paramsKey, $this->request->get('airline_represent'))) {

                $params =  $this->request->get('airline_represent')[$this->paramsKey];

                $this->query->whereHas('represent_list', function($query) use ($params){
                    $query->where('represent_type','=',1)
                        ->where(function($query) use ($params){
                            $query->where('fio_rus','ilike','%'.$params.'%')
                                ->orWhere('fio_lat','ilike','%'.$params.'%');
                        });
                });
            }
        }

        /* airlines represent search distinct */

        if ($this->request->get('search_airline_represent') !== null) {
            if (array_key_exists($this->paramsKey, $this->request->get('search_airline_represent'))) {

                $params =  $this->request->get('search_airline_represent')[$this->paramsKey];

                $this->query->join('akvs_person_info','akvs_person_info.akvs_airlines_id','=','AKVS_AIRLINES.akvs_airlines_id')
                    ->where(function($query) use ($params){
                        $query->where('akvs_person_info.fio_lat','ilike','%'.$params.'%')
                            ->orWhere('akvs_person_info.fio_rus','ilike','%'.$params.'%');
                    })
                    ->where('akvs_person_info.represent_type','=',1)
                ->select('akvs_person_info.fio_rus','akvs_person_info.fio_lat','akvs_person_info.represent_type')
                ->distinct('akvs_person_info.fio_rus','akvs_person_info.fio_lat');

            }
        }

        /* Фильтрация Дата актуализации АКВС (интервал даты и времени)*/

        if ($this->request->get('akvs_actualization_datetime') !== null) {
            if (array_key_exists($this->paramsKey, $this->request->get('akvs_actualization_datetime'))) {

                $params = $this->parseParams($this->request->get('akvs_actualization_datetime')[$this->paramsKey]);


                $timeFrom = Carbon::createFromTimestamp($params[0], 'MSK')->toDateTimeString();
                $timeTo = Carbon::createFromTimestamp($params[1], 'MSK')->toDateTimeString();

                $this->query->whereRaw('actualization_datetime >=\''.$timeFrom.'\' AND created_at <=\''.$timeTo.'\'');
            }
        }

        /* Фильтрация Дата создания АКВС (интервал даты и времени)*/

        if ($this->request->get('akvs_created_at') !== null) {
            if (array_key_exists($this->paramsKey, $this->request->get('akvs_created_at'))) {

                $params = $this->parseParams($this->request->get('akvs_created_at')[$this->paramsKey]);

                $timeFrom = Carbon::createFromTimestamp($params[0], 'MSK')->toDateTimeString();
                $timeTo = Carbon::createFromTimestamp($params[1], 'MSK')->toDateTimeString();

                $this->query->whereRaw('created_at >= \''.$timeFrom.'\' AND created_at <=\''.$timeTo.'\'');
            }
        }

        /* Фильтрация ID */

        if ($this->request->get('is_draft') !== null) {
            if (array_key_exists($this->paramsKey, $this->request->get('is_draft'))) {
                $params = $this->request->get('is_draft')[$this->paramsKey];

                $this->query->where('status','=',0)
                    ->where('version','=',AkvsAirline::whereColumn('akvs_airlines_global_id','AKVS_AIRLINES.akvs_airlines_global_id')
                        ->where('author_id','=', \Auth::id())
                        ->latest()
                        ->limit(1)
                        ->value('version'));
            }
        }

        /* Фильтрация ID */

        if ($this->request->get('akvs_id') !== null) {
            if (array_key_exists($this->paramsKey, $this->request->get('akvs_id'))) {
                $params = $this->request->get('akvs_id')[$this->paramsKey];

                $this->query->where('akvs_airlines_id','=', $params);
            }
        }

        /* Выдача по icaolat3 АК*/

        if ($this->request->get('icao') !== null) {
            if (array_key_exists($this->paramsKey, $this->request->get('icao'))) {
                $params = $this->parseParams($this->request->get('icao')[$this->paramsKey]);

                $this->query->where('ICAOLAT3','ilike', '%'.$params.'%');
            }
        }

        /* Поиск по icaolat3 АК*/

        if ($this->request->get('search_icao') !== null) {
            if (array_key_exists($this->paramsKey, $this->request->get('search_icao'))) {
                $params = $this->request->get('search_icao')[$this->paramsKey];

                $this->query->where('ICAOLAT3','ilike', '%'.$params.'%')
                ->distinct('ICAOLAT3');
            }
        }

        /* Поиск уникальных по имени АК */

        if ($this->request->get('search_airline_name') !== null) {
            if (array_key_exists($this->paramsKey, $this->request->get('search_airline_name'))) {
                $params = $this->request->get('search_airline_name')[$this->paramsKey];

                $this->query->where('FULLNAMELAT','ilike', '%'.$params.'%')
                    ->orWhere('FULLNAMERUS','ilike', '%'.$params.'%')
                    ->distinct('FULLNAMELAT','FULLNAMERUS');
            }
        }

        /* Выдача по имени АК */

        if ($this->request->get('airline_name') !== null) {
            if (array_key_exists($this->paramsKey, $this->request->get('airline_name'))) {
                $params = $this->parseParams($this->request->get('airline_name')[$this->paramsKey]);

                $this->query->whereIn('akvs_airlines_global_id',$params);
            }
        }
    }

    protected function parseParams(string $str): array
    {
        return explode(',', str_replace(['[', ']'], '', $str));
    }
}
