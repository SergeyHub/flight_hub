<?php



namespace App\RequestHandlers;


use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\AkvsAirline;
use Illuminate\Support\Facades\Auth;

class AkvsSortingHandler
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

        /* Сортировка по Дате актуализации */

        if ($this->request->get('akvs_actualization_datetime') !== null) {
            if (array_key_exists($this->sortKey, $this->request->get('akvs_actualization_datetime'))) {
                $order = $this->request->get('akvs_actualization_datetime')[$this->sortKey];

                $this->query->orderBy('actualization_datetime', $order);
            }

        }

        /* Сортировка по Дате создания */

        if ($this->request->get('akvs_created_at') !== null) {
            if (array_key_exists($this->sortKey, $this->request->get('akvs_created_at'))) {
                $order = $this->request->get('akvs_created_at')[$this->sortKey];

                $this->query->orderBy('created_at', $order);
            }

        }

        /* Сортировка по Icao */

        if ($this->request->get('icao') !== null) {
            if (array_key_exists($this->sortKey, $this->request->get('icao'))) {
                $order = $this->request->get('icao')[$this->sortKey];

                $this->query->orderBy('ICAOLAT3', $order);
            }

        }

        /* Сортировка по FULLNAMERUS */

        if ($this->request->get('airline_name') !== null) {
            if (array_key_exists($this->sortKey, $this->request->get('airline_name'))) {
                $order = $this->request->get('airline_name')[$this->sortKey];

                $this->query->orderBy('FULLNAMERUS', $order);
            }

        }
    }

    /* custom sorts */

    protected function sortByUserRole(){
        return AkvsAirline::selectRaw('roles.name_rus')
            ->join('roles','roles.id','=','role_user.role_id')
            ->whereColumn('role_user.user_id', 'users.id')
            ->latest('roles.name_rus')
            ->take(1);
    }

    protected function parseParams(string $str): array
    {
        return explode(',', str_replace(['[', ']'], '', $str));
    }
}
