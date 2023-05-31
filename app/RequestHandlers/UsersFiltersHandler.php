<?php

namespace App\RequestHandlers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class UsersFiltersHandler
{
    private Builder $query;
    private Request $request;
    private string $paramKey;

    public function __construct(Builder $query, Request $request)
    {
        $this->query = $query;
        $this->request = $request;
        $this->paramKey = "'params'";
    }

    public function handle()
    {
        /* Глобальный поиск */
        if ($this->requestHasParam('general_search')) {
            $param = $this->getSingleParam('general_search');

            $this->query
                ->where('inn', 'ilike', '%' . $param . '%')
                ->orWhere('email', 'ilike', '%' . $param . '%')
                ->orWhere('id', 'ilike', '%' . $param . '%')
                ->orWhere('name', 'ilike', '%' . $param . '%')
                ->orWhere('patronymic', 'ilike', '%' . $param . '%')
                ->orWhere('surname', 'ilike', '%' . $param . '%')
                ->orWhere('phone', 'ilike', '%' . $param . '%')
                ->orWhere('aftn', 'ilike', '%' . $param . '%')
                ->whereHas('roles', function ($query) use ($param) {
                    $query
                        ->where('name_rus', 'ilike', '%' . $param . '%')
                        ->orWhere('name_lat', 'ilike', '%' . $param . '%');
                });
        }

        /* Фильтрация Дата последнего входа (интервал даты и времени)*/
        if ($this->requestHasParam('user_last_login')) {
            $param = $this->getParamValues('user_last_login');
            $timeFrom = Carbon::createFromTimestamp($param[0]);
            $timeTo = Carbon::createFromTimestamp($param[1]);

            $this->query->whereRaw("last_login BETWEEN ? AND ?", [$timeFrom, $timeTo]);
        }

        /* Фильтрация Дата регистрации пользователя (интервал даты и времени)*/
        if ($this->requestHasParam('user_created_at')) {
            $param = $this->getParamValues('user_created_at');
            $timeFrom = Carbon::createFromTimestamp($param[0], 'MSK');
            $timeTo = Carbon::createFromTimestamp($param[1], 'MSK');

            $this->query->whereRaw("created_at BETWEEN ? AND ?", [$timeFrom, $timeTo]);
        }

        /* Фильтрация ИНН */
        if ($this->requestHasParam('inn')) {
            $this->query->whereIn('inn', $this->getParamValues('inn'));
        }

        /* Поиск ИНН */
        if ($this->requestHasParam('search_inn')) {
            $this->query->where('inn', 'ilike', "%{$this->getSingleParam('search_inn')}%");
        }

        /* Фильтрация SITA */
        if ($this->requestHasParam('sita')) {
            $this->query->whereIn('sita', $this->getParamValues('sita'));
        }

        /* Поиск SITA */
        if ($this->requestHasParam('search_sita')) {
            $this->query->where('sita', 'ilike', "%{$this->getSingleParam('search_sita')}%");
        }

        /* Фильтрация АФТН */
        if ($this->requestHasParam('aftn')) {
            $this->query->whereIn('aftn', $this->getParamValues('aftn'));
        }

        /* Поиск АФТН */
        if ($this->requestHasParam('search_aftn')) {
            $this->query->where('aftn', 'ilike', "%{$this->getSingleParam('search_aftn')}%");
        }

        /* Фильтрация по user_id */
        if ($this->requestHasParam('user_id')) {
            $this->query->whereRaw('users.id BETWEEN ? AND ?', $this->getParamValues('user_id'));
        }

        /* Поиск по user_id */
        if ($this->requestHasParam('search_user_id')) {
            $this->query->where('id', 'ilike', "%{$this->getSingleParam('search_user_id')}%");
        }

        /* Фильтрация ФИО, Email, телефон */
        if ($this->requestHasParam('user_filter')) {
            $this->query->whereIn('users.id', $this->getParamValues('user_filter'));
        }

        /* Поиск ФИО */
        if ($this->requestHasParam('search_full_name')) {
            $param = $this->getSingleParam('search_full_name');

            $this->query->where(function ($query) use ($param) {
                $query
                    ->where('users.name', 'ilike', '%' . $param . '%')
                    ->orWhere('users.patronymic', 'ilike', '%' . $param . '%')
                    ->orWhere('users.surname', 'ilike', '%' . $param . '%')
                    ->distinct('users.name', 'users.patronymic', 'users.surname');
            });
        }

        /* Поиск по телефону */
        if ($this->requestHasParam('search_phone')) {
            $this->query->where('users.phone', 'ilike', "%{$this->getSingleParam('search_phone')}%");
        }

        /* Поиск по email */
        if ($this->requestHasParam('search_email')) {
            $this->query->where('users.email', "%{$this->getSingleParam('search_email')}%");
        }

        /* Поиск по Роли*/
        if ($this->requestHasParam('search_role')) {
            $param = $this->getSingleParam('search_role');

            $this->query
                ->join('role_user', 'role_user.user_id', '=', 'users.id')
                ->join('roles', 'role_user.role_id', '=', 'roles.id')
                ->select('roles.name_rus', 'roles.name_lat', 'roles.id')
                ->where('roles.name_rus', 'ilike', '%' . $param . '%')
                ->orWhere('roles.name_lat', 'ilike', '%' . $param . '%')
                ->distinct('roles.name_rus', 'roles.name_lat');
        }

        /* Фильтрация по Роли*/
        if ($this->requestHasParam('role')) {
            $this->query->whereIn('active_role_id', $this->getParamValues('role'));
        }

        /* Поиск по Статусу*/
        if ($this->requestHasParam('user_status')) {
            $this->query->whereIn('active', $this->getParamValues('user_status'));
        }

        /* Фильтрация по Статусу*/
        if ($this->requestHasParam('user_status_filter')) {
            $this->query->whereIn('status', $this->getParamValues('user_status_filter'));
        }
    }

    private function requestHasParam(string $requestKey): bool
    {
        if ($this->request->has($requestKey) && array_key_exists($this->paramKey, $this->request->get($requestKey)))
            return true;

        return false;
    }

    private function getParamValues(string $requestKey): array
    {
        return explode(',', str_replace(['[', ']'], '', $this->request->get($requestKey)[$this->paramKey]));
    }

    private function getSingleParam(string $requestKey)
    {
        return $this->request->get($requestKey)[$this->paramKey];
    }
}
