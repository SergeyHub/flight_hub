<?php

namespace App\RequestHandlers;

use Auth;
use Illuminate\Database\Eloquent\Builder;

class RegistryNDatesCheckingAccessForFlightsHandler
{
    private Builder $query;
    private $authUserId;
    private $authUserActiveRoleId;

    public function __construct(Builder $query)
    {
        $this->query = $query;
        $this->authUserId = Auth::id();
        $this->authUserActiveRoleId = Auth::user()->active_role_id;
    }

    public function handle()
    {
        if ($this->authUserActiveRoleId !== null) {
            switch ($this->authUserActiveRoleId) {
                //администратор
                case 2:
                    $this->query->whereNotNull('date');
                    break;
                //заявитель
                case 3:
                    $this->query
                        ->whereHas('nFormFlight.nForm', function ($query) {
                            $query->where('author_id', $this->authUserId);
                        });
                    break;
                default:
                    $this->query
                        ->whereHas('nFormFlight.nForm', function ($query) {
                            $query->where('author_id', '!=', $this->authUserId);
                        })
                        ->whereHas('nFormFlight.agreementSigns', function ($query) {
                            $query->where('role_id', $this->authUserActiveRoleId);
                        });
            }
        }
    }
}
