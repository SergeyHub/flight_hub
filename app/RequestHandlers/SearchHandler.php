<?php


namespace App\RequestHandlers;


use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;


/**
 * Class SearchHandler
 * @package App\RequestHandlers
 */
class SearchHandler
{
    /**
     * @var Builder
     */
    private Builder $query;
    /**
     * @var Request
     */
    private Request $request;

    /**
     * SearchHandler constructor.
     * @param Builder $builder
     * @param Request $request
     */
    public function __construct(Builder $builder, Request $request)
    {
        $this->query = $builder;
        $this->request = $request;
    }

    /**
     *Run search
     */
    public function handle()
    {
        if (!is_null($this->request->search)) {
            $search = $this->request->search;

            $this->query->where(function ($query) use ($search) {
                $query
                    /* Номер разрешения */
                    ->where('permit_num', $search)
                    /* Наименование/ИКАО авиапредприятия */
                    ->orWhere(function ($innerQuery) use ($search) {
                        $innerQuery->whereHas('airline', function ($relationQuery) use ($search) {
                            $relationQuery
                                ->where('airline_namelat', 'ilike', "%{$search}%")
                                ->orWhere('airline_namerus', 'ilike', "%{$search}%");
                        });
                    })
                    /* Регистрационный номер основного ВС */
                    ->orWhere(function ($innerQuery) use ($search) {
                        $innerQuery->whereHas('aircrafts', function ($relationQuery) use ($search) {
                            $relationQuery
                                ->where('registration_number', 'ilike', "%{$search}%");
                        });
                    })
                    /* Владелец ВС */
                    ->orWhere(function ($innerQuery) use ($search) {
                        $innerQuery->whereHas('aircrafts', function ($relationQuery) use ($search) {
                            $relationQuery->whereHas('aircraftOwner', function ($query) use ($search) {
                                $query->where('name', 'ilike', "%{$search}%");
                            });
                        });
                    })
                    /* Номер рейса */
                    ->orWhere(function ($innerQuery) use ($search) {
                        $innerQuery->whereHas('flights', function ($relationQuery) use ($search) {
                            $relationQuery
                                ->where('flight_num', 'ilike', "%{$search}%");
                        });
                    })
                    /* Аэропорт отправления (Наименование/ИКАО) */
                    ->orWhere(function ($innerQuery) use ($search) {
                        $innerQuery->whereHas('departureAirport', function ($relationQuery) use ($search) {
                            $relationQuery
                                ->where('ICAOLAT4', 'ilike', "%{$search}%");
                        });
                    })
                    /* Аэропорт прибытия (Наименование/ИКАО) */
                    ->orWhere(function ($innerQuery) use ($search) {
                        $innerQuery->whereHas('landingAirport', function ($relationQuery) use ($search) {
                            $relationQuery
                                ->where('ICAOLAT4', 'ilike', "%{$search}%");
                        });
                    });
            });
        }
    }

    /**
     * @param $str
     * @return string|string[]
     */
    private function clearSearchParam($str)
    {
        return str_replace('\'', '', $str);
    }

}
