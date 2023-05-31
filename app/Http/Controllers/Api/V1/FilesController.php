<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FilesController extends Controller
{
    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function save(Request $request): JsonResponse
    {
        $basePath = 'public/media/documents';
        $files = $request->allFiles();
        $id = $request->get('id') ?? 1;

        foreach ($files as $file) {
            Storage::putFileAs($basePath . "/{$id}/", $file, $file->getClientOriginalName());
        }

        return response()->json(['message' => 'files successfully saved'], 201, $this->headers);
    }

    private array $headers = ['Access-Control-Allow-Origin' => '*'];
}
