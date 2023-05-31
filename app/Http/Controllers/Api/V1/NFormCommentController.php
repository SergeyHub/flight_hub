<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Classes\Agreement\SaveNFormComment;
use App\Http\Controllers\Controller;
use App\Models\NFormComment;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NFormCommentController extends Controller
{

    private SaveNFormComment $comment;

    public function __construct(SaveNFormComment $comment)
    {
        $this->comment = $comment;
        $this->middleware('guest');
    }

    /**
     * Create Comment NForm
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function save(Request $request): JsonResponse
    {
        $data = $request->all();
        $comment = $this->comment->saveNFormComment($data);
        if (is_string($comment)) return $this->errorMessage($comment);

        return response()->json([
            'status' => true,
            'message' => 'OK',
            'n_form_comment_id' => $comment->n_form_comment_id,
            'created_at' => $comment->created_at->format('Y-m-d H:i:s')
        ], 201, $this->headers);
    }

    /**
     * Delete comment NForm
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function delete(Request $request): JsonResponse
    {
        $data = $request->all();

        /** Exists */
        if (isset($data['n_form_comment_id'])) {

            $n_form_comment = NFormComment::where('n_form_comment_id', $data['n_form_comment_id'])->first();
            if (!$n_form_comment) return $this->errorMessage('Комментарий отсутствует');

            $n_form = $n_form_comment->nForm;
            if (is_null($n_form)) return $this->errorMessage('Заявка с таким комментарием отсутствует');

            $n_version = $n_form->version;
            if (is_null($n_version)) return $this->errorMessage('NForm не заполнено поле version');

        } else return $this->errorMessage('Не передан n_form_comment_id');

        $n_form_comment->update([
            'delete_at_version' => $n_form->version,
        ]);

        //delete child comments
        $childComments = $n_form_comment->childComments();

        foreach ($childComments as $childComment) {
            $childComment->update([
                'delete_at_version' => $n_form->version
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'OK',
            'n_form_comment_id' => $n_form_comment->n_form_comment_id
        ], 200, $this->headers);
    }

    /**
     * Error JSON message
     *
     * From NFormAgreementController
     *
     * @param $message
     * @return JsonResponse
     */
    private function errorMessage(
        $message
    ): JsonResponse
    {
        $response['status'] = false;
        $response['message'] = $message;

        return response()
            ->json($response, 422, $this->headers, JSON_UNESCAPED_UNICODE);
    }

    private array $headers = [
        'Content-type' => 'application/json',
        'Access-Control-Allow-Origin' => '*'
    ];
}
