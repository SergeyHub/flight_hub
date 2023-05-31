<?php /** @noinspection PhpMultipleClassDeclarationsInspection */

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Point;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ChecksController extends Controller
{
    /**
     * Checks if point exists
     * And if exists, check is it has ISINOUT attribute or not
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function checkPoint(Request $request): JsonResponse
    {
        $pointName = $request->get('name');

        if ($pointName === null) {
            return response()->json(['message' => 'Missed name parameter'], 404, $this->responseHeaders);
        }

        $point = Point::query()
            ->select([
                'POINTS_ID',
                'ISGATEWAY',
                'ISINOUT',
            ])
            ->with('pnthist');

        $point->whereHas('pnthist', function ($query) use ($pointName) {
            $query->where('ICAOLAT5', $pointName);
        });

        $point = $point->first();

        if ($point === null) {
            return response()->json(['message' => 'Point not found'], 404, $this->responseHeaders);
        }

        return response()->json(['ISINOUT' => $point->ISINOUT], 200, $this->responseHeaders);
    }

    private array $responseHeaders = ['Content-type' => 'application/json', 'Access-Control-Allow-Origin' => '*'];
}
