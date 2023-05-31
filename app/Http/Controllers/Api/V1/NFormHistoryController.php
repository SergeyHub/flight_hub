<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Classes\Headers;
use App\Http\Controllers\Api\V1\Classes\Responses;
use App\Http\Controllers\Controller;
use App\Models\NForm;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NFormHistoryController extends Controller
{
    /**
     * Get Form N histories from database
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function get(Request $request): JsonResponse
    {
        $nForm = NForm::where('id_pakus', $request['id_pakus'])->first();

        if (!$nForm){
            return Responses::errorMessage(
                [
                    'status' => false,
                    'message' => 'Такой заявки не существует'
                ]
            );
        }

        $nForm = NForm::query()
            ->where('id_pakus', $request['id_pakus'])

            ->select([
                'n_forms_id',
                'id_pakus',
                'id_pp',
                'version',
                'author_id',
                'is_latest'
            ])
            ->with('histories', function($query) // Рейсы
            {
                $query
                    ->orderByDesc('id')

                    ->with('flight')
                    ->with('status')
                    ->with('role')
                    ->with('comment', function($query) {
                        $query
                            ->with('childComments', function ($query) {
                                $query
                                    ->with('author', function ($query) {
                                        $query
                                            ->select([
                                                'id',
                                                'active',
                                                'active_role_id',
                                                'login',
                                                'email',
                                                'name',
                                                'patronymic',
                                                'surname',
                                                'phone',
                                                'status'
                                            ])
                                            ->with('roles', function ($query) {
                                                $query->select([
                                                    'role_id as id',
                                                    'name_rus',
                                                    'name_lat',
                                                    'approval_group_id',
                                                ]);
                                            });
                                    })
                                    ->with('documents', function ($query) {
                                        $query->select([
                                            'id as document_id',
                                            'file_type_id',
                                            'file_type_name',
                                            'filename as file_name',
                                            'path as file_path',
                                            'created_at',
                                            'other_attributes_json as required_attributes_json',
                                        ]);
                                    })
                                    ->orderByDesc('n_form_comment_id');
                            })
                            ->with('author', function ($query) {
                                $query
                                    ->select([
                                        'id',
                                        'active',
                                        'active_role_id',
                                        'login',
                                        'email',
                                        'name',
                                        'patronymic',
                                        'surname',
                                        'phone',
                                        'status'
                                    ])
                                    ->with('roles', function ($query) {
                                        $query->select([
                                            'role_id as id',
                                            'name_rus',
                                            'name_lat',
                                            'approval_group_id',
                                        ]);
                                    });
                            })
                            ->with('documents', function ($query) {
                                $query->select([
                                    'id as document_id',
                                    'file_type_id',
                                    'file_type_name',
                                    'filename as file_name',
                                    'path as file_path',
                                    'created_at',
                                    'other_attributes_json as required_attributes_json',
                                ]);
                            });
                    });
            })
            ->first();

        return response()->json([
            'status' => true,
            'nForm' => $nForm
        ], 200, Headers::accessControlAllowOrigin(), JSON_UNESCAPED_UNICODE);
    }
}
