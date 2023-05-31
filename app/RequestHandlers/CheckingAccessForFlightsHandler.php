<?php /** @noinspection PhpMultipleClassDeclarationsInspection */


namespace App\RequestHandlers;


use Illuminate\Database\Eloquent\Builder;

class CheckingAccessForFlightsHandler
{
    private Builder $queryBuilder;
    private $authUserId;

    /**
     * CheckingAccessForFlightsHandler constructor.
     * @param Builder $queryBuilder
     */
    public function __construct(Builder $queryBuilder)
    {
        $this->queryBuilder = $queryBuilder;
        $this->authUserId = \Auth::id();
    }

    public function handle()
    {
        if ($this->isUserAuthenticated()) {
            if (in_array($this->getAdminId(), $this->getRolesIdsFromUser())) {
                $this->queryBuilder->with('flights', function ($query) {
                    $query
                        ->with('departureAirport', function ($query) {
                            $query->select('AIRPORTS_ID', 'ICAOLAT4');
                        })
                        ->with('landingAirport', function ($query) {
                            $query->select('AIRPORTS_ID', 'ICAOLAT4');
                        })
                        ->with('agreementSigns', function ($query) {
                            $query->with('sign');
                        });
                });
            } else {
                $this->queryBuilder
                    ->where(function ($query) {
                        $query
                            //Получаем только формы, относящиеся к определённому пользователю
                            ->where('author_id', $this->authUserId)
                            //Получаем формы, которые может видеть пользователь с определённой ролью
                            ->orWhereHas('flights.agreementSigns', function ($relQuery) {
                                $relQuery->whereIn('role_id', $this->getRolesIdsFromUser());
                            });
                    })
                    ->with('flights', function ($query) {
                        if (!in_array(3, $this->getRolesIdsFromUser())) {
                            $query
                                ->whereHas('agreementSigns', function ($query) {
                                    $query->whereIn('role_id', $this->getRolesIdsFromUser());
                                })
                                ->with('agreementSigns', function ($query) {
                                    $query->whereIn('role_id', $this->getRolesIdsFromUser());
                                })
                                ->with('departureAirport', function ($query) {
                                    $query->select('AIRPORTS_ID', 'ICAOLAT4');
                                })
                                ->with('landingAirport', function ($query) {
                                    $query->select('AIRPORTS_ID', 'ICAOLAT4');
                                });
                        } else {
                            $query
                                ->with('departureAirport', function ($query) {
                                    $query->select('AIRPORTS_ID', 'ICAOLAT4');
                                })
                                ->with('landingAirport', function ($query) {
                                    $query->select('AIRPORTS_ID', 'ICAOLAT4');
                                });
                        }

                    });
            }
        }
    }

    private function isUserAuthenticated(): bool
    {
        if (\Auth::id() !== null) return true;

        return false;
    }

    /**
     * Get all user roles as array of ids
     *
     * @return array
     */
    protected function getRolesIdsFromUser(): array
    {
        $userWithRoles = \App\Models\User::where('id', $this->authUserId)->with('roles')->first()->toArray()['roles'];
        $roleIds = [];

        foreach ($userWithRoles as $userWithRole) {
            $roleIds[] += $userWithRole['id'];
        }

        return $roleIds;
    }

    protected function getAdminId(): int
    {
        return \App\Models\Role::where('name_lat', 'Administrator')->first()->id;
    }


}
