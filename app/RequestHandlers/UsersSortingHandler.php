<?php

namespace App\RequestHandlers;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class UsersSortingHandler
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
        /* Сортировка по last_login */
        if ($this->requestHasSort('users_last_login')) {
            $this->query->orderBy('last_login', $this->getSortOrder('users_last_login'));
        }

        /* Сортировка по Дате регистрации  */
        if ($this->requestHasSort('users_created_at')) {
            $this->query->orderBy('created_at', $this->getSortOrder('users_created_at'));
        }

        /* Сортировка по inn  */
        if ($this->requestHasSort('users_inn')) {
            $this->query->orderBy('inn', $this->getSortOrder('users_inn'));
        }

        /* Сортировка по sita user */
        if ($this->requestHasSort('users_sita')) {
            $this->query->orderBy('sita', $this->getSortOrder('users_sita'));
        }

        /* Сортировка по aftn  */
        if ($this->requestHasSort('users_aftn')) {
            $this->query->orderBy('aftn', $this->getSortOrder('users_aftn'));
        }

        /* Сортировка по роли  */
        if ($this->requestHasSort('users_role')) {
            $this->query->orderBy('active_role_id', $this->getSortOrder('users_role'));
        }

        /* Сортировка по email  */
        if ($this->requestHasSort('users_email')) {
            $this->query->orderBy('email', $this->getSortOrder('users_email'));
        }

        /* Сортировка по phone  */
        if ($this->requestHasSort('users_phone')) {
            $this->query->orderBy('phone', $this->getSortOrder('users_phone'));
        }

        /* Сортировка по surname  */
        if ($this->requestHasSort('users_surname')) {
            $this->query->orderBy('surname', $this->getSortOrder('users_surname'));
        }

        /* Сортировка по id  */
        if ($this->requestHasSort('user_id')) {
            $this->query->orderBy('id', $this->getSortOrder('user_id'));
        }

        /* Сортировка по status user */
        if ($this->requestHasSort('users_status')) {
            $this->query->orderBy('status', $this->getSortOrder('users_status'));
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
}
