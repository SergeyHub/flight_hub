<?php

namespace App\RequestHandlers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class FlightDirectionFilterHandler
{
    private Builder $query;
    private Request $request;
    private string $paramsKey;

    public function __construct(Builder $query, Request $request)
    {
        $this->query = $query;
        $this->request = $request;
        $this->paramsKey = "'params'";
    }

    public function handle()
    {
        /* Фильтрация по аэроузлу откуда */
        if ($this->request->has('from_airhub')) {
            if (array_key_exists($this->paramsKey, $this->request->get('from_airhub'))) {
                $params = $this->parseParams($this->request->get('from_airhub')[$this->paramsKey]);
                $this->query->whereIn('from_airhub_id', $params);
            }
        }

        /* Фильтрация по аэроузлу куда */
        if ($this->request->has('to_airhub')) {
            if (array_key_exists($this->paramsKey, $this->request->get('to_airhub'))) {
                $params = $this->parseParams($this->request->get('to_airhub')[$this->paramsKey]);
                $this->query->whereIn('to_airhub_id', $params);
            }
        }

        /* Фильтрация по статусу */
        if ($this->request->has('status')) {
            if (array_key_exists($this->paramsKey, $this->request->get('status'))) {
                $params = $this->parseParams($this->request->get('status')[$this->paramsKey]);
                $this->query->whereIn('status_id', $params);
            }
        }

        /* Фильтрация по дате начала */
        if ($this->request->has('begin_date')) {
            if (array_key_exists($this->paramsKey, $this->request->get('begin_date'))) {
                $params = $this->parseParams($this->request->get('begin_date')[$this->paramsKey]);
                $this->query->whereBetween('begin_date', [$params[0], $params[1]]);
            }
        }

        /* Фильтрация по дате обновления */
        if ($this->request->has('update_date')) {
            if (array_key_exists($this->paramsKey, $this->request->get('update_date'))) {
                $params = $this->parseParams($this->request->get('update_date')[$this->paramsKey]);
                $this->query->whereBetween('updated_at', [$params[0], $params[1]]);
            }
        }

        /* Фильтрация по NOTAM */
        if ($this->request->has('NOTAM')) {
            if (array_key_exists($this->paramsKey, $this->request->get('NOTAM'))) {
                $param = $this->request->get('NOTAM')[$this->paramsKey];
                $this->query->where('NOTAM', 'ilike', '%' . $param . '%');
            }
        }

        /* Фильтрация по пределу частот */
        if ($this->request->has('frequency_limit')) {
            if (array_key_exists($this->paramsKey, $this->request->get('frequency_limit'))) {
                $param = $this->request->get('frequency_limit')[$this->paramsKey];
                $this->query->where('frequency_limit', $param);
            }
        }

        /* Фильтрация по подстроке */
        if ($this->request->has('search')) {
            if (array_key_exists($this->paramsKey, $this->request->get('search'))) {
                $param = $this->request->get('search')[$this->paramsKey];
                $this->query
                    // по имени аэроузла отбытия
                    ->whereHas('fromAirhub', function ($query) use ($param) {
                        $query->where('name', 'ilike', '%' . $param . '%');
                    })
                    // по имени аэроузла прибытия
                    ->orWhereHas('toAirhub', function ($query) use ($param) {
                        $query->where('name', 'ilike', '%' . $param . '%');
                    });
//                    // по имени авиакомпании
//                    ->orWhereHas('airlines', function ($query) use ($param) {
//                        $query
//                            ->where('SHORTNAMELAT', 'ilike', '%' . $param . '%')
//                            ->orWhere('SHORTNAMERUS', 'ilike', '%' . $param . '%');
//                    });
            }
        }
    }

    protected function parseParams(string $str): array
    {
        return explode(',', str_replace(['[', ']'], '', $str));
    }
}
