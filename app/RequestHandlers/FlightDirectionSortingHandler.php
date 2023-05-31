<?php

namespace App\RequestHandlers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class FlightDirectionSortingHandler
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
        /* Сортировка по аэроузлу откуда */
        if ($this->request->has('from_airhub')) {
            if (array_key_exists($this->sortKey, $this->request->get('from_airhub'))) {
                $param = $this->request->get('from_airhub')[$this->sortKey];
                $this->query->orderBy('from_airhub_id', $param);
            }
        }

        /* Сортировка по аэроузлу куда */
        if ($this->request->has('to_airhub')) {
            if (array_key_exists($this->sortKey, $this->request->get('to_airhub'))) {
                $param = $this->request->get('to_airhub')[$this->sortKey];
                $this->query->orderBy('to_airhub_id', $param);
            }
        }
    }
}
