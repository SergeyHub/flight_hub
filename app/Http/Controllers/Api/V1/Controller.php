<?php
/**
 * @OA\Info(
 *     title="API documentation",
 *     version="0.1",
 *     @OA\Contact(
 *          email="admin@celado-ai"
 *      )
 * )
 * @OA\Tag(
 *     name="FormN",
 *     description="Взаимодействие с FormN"
 * )
 * @OA\Server(
 *     description="dev server",
 *     url="http://127.0.0.1:8081/api/v1"
 * )
 */

namespace App\Http\Controllers\Api\V1;


use Illuminate\Http\JsonResponse;

class Controller extends \App\Http\Controllers\Controller
{
//    /* return JsonResponse */
//    protected function returnResp($data): JsonResponse
//    {
//        return response()->json($data);
//    }
}
