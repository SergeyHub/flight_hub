<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\Airhub\AirhubRequest;
use App\Models\Airhub;

class AirhubController extends Controller
{
    public function store(AirhubRequest $request)
    {
        $airhub = Airhub::create($request->only([
            'name',
        ]));

        $airhub->airports()->sync($request->input('airport_id'));

        return response()->json([
            'message' => 'Successfully create airhub'
        ], 201);
    }

    public function update(AirhubRequest $request, Airhub $airhub)
    {
        $airhub->update($request->only([
            'name',
        ]));

        $airhub->airports()->sync($request->input('airport_id'));

        return response()->json([
            'message' => 'Successfully update airhub'
        ]);
    }

    public function destroy(Airhub $airhub)
    {
        $airhub->airports()->detach();
        $airhub->delete();

        return response()->json(null, 204);
    }
}
