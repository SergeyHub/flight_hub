<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\FlightRegularity\FlightRegularityRequest;
use App\Models\FlightRegularity;
use Illuminate\Http\JsonResponse;

class FlightRegularityController extends Controller
{
    /**
     * @param FlightRegularityRequest $request
     * @return JsonResponse
     */
    public function store(FlightRegularityRequest $request): JsonResponse
    {
        $flightRegularity = FlightRegularity::create($request->only([
            'amount',
            'percent',
            'airline_id',
        ]));

        return response()->json([
            'message' => 'Successfully create flight regularity'
        ], 201);
    }

    /**
     * @param FlightRegularityRequest $request
     * @param FlightRegularity $flightRegularity
     * @return JsonResponse
     */
    public function update(FlightRegularityRequest $request, FlightRegularity $flightRegularity): JsonResponse
    {
        $flightRegularity->update($request->only([
            'amount',
            'percent',
            'airline_id',
        ]));

        return response()->json([
            'message' => 'Successfully update flight regularity'
        ]);
    }

    /**
     * @param FlightRegularity $flightRegularity
     * @return JsonResponse
     */
    public function destroy(FlightRegularity $flightRegularity): JsonResponse
    {
        $flightRegularity->delete();

        return response()->json(null, 204);
    }
}
