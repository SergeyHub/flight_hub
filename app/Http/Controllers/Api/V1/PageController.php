<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Page;
use Illuminate\Http\JsonResponse;

class PageController extends Controller
{
    protected $model = Page::class;

    public function show($uniqueField): JsonResponse
    {
        return response()->json(
            $this->model::where('id', $uniqueField)
                ->orWhere('route', $uniqueField)
                ->firstOrFail()
        );
    }
}
