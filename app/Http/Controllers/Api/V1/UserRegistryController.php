<?php /** @noinspection PhpMultipleClassDeclarationsInspection */

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\MainSettingParameter;
use App\Models\NForm;
use App\Models\Setting;
use App\Models\SettingParameter;
use App\Models\User;
use App\RequestHandlers\FiltersHandler;
use App\RequestHandlers\SearchHandler;
use App\RequestHandlers\SortingHandler;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class UserRegistryController extends Controller
{
    public function getUserRegistry(Request $request): JsonResponse
    {

        $Users = User::query()
        ->select('users.*')
        ->with('roles');


        /* sorting */
        (new SortingHandler($Users, $request))->handle();

        /* filtering */
        (new FiltersHandler($Users, $request))->handle();

       $Users =  $Users->get();


        return response()->json([
            'Users' => [
                'data' => $Users,
            ],
        ]);
    }

    public function getTableColumns($userId)
    {
        $userSetting = Setting::where('user_id', $userId)
                                ->where('name', 'user_registry_columns')
                                ->first();

        if ($userSetting === null){
            $setting = new Setting();
            $setting->name = 'user_registry_columns';
            $setting->user_id = $userId;
            $setting->save();
            $settingParamter = new SettingParameter();
            $settingParamter->key = 'user_registry_columns';
            $settingParamter->value = json_encode($this->getColums(), JSON_UNESCAPED_UNICODE);
            $settingParamter->setting_id = $setting->id;
            $settingParamter->save();
            return response()->json([
                'userRegistryColumns' => json_decode($settingParamter['value']),
            ], 200, [], JSON_UNESCAPED_UNICODE);
        }else{
            $userRegistryColumns = SettingParameter::where('setting_id', $userSetting['id'])->first();

            return response()->json([
                'userRegistryColumns' => json_decode($userRegistryColumns['value']),
            ], 200, [], JSON_UNESCAPED_UNICODE);
        }
    }

    public function changeTableColumns(Request $request)
    {
        $userSetting = Setting::where('user_id', $request['userId'])
                                ->where('name', 'user_registry_columns')
                                ->first();

        $userRegistryColumns = SettingParameter::where('setting_id', $userSetting['id'])->first();
        $userRegistryColumns['value'] = $request->get('value');

        $userRegistryColumns->save();

        return response()->json([
            'statuse' => true,
            'message' => 'Обновлен'
        ], 200, []);
    }

    private function getColums() {
        return [
            ["name"=> 'icon', "name_lat"=> 'icon', "possition"=> 0, "isActive"=> true],
            ["name"=> 'Идентификатор пользователя', "name_lat"=> 'User ID', "possition"=> 1, "isActive"=> true],
            ["name"=> 'ФИО', "name_lat"=> 'Full name', "possition"=> 2, "isActive"=> true],
            ["name"=> 'Телефон', "name_lat"=> 'Phone', "possition"=> 3, "isActive"=> true],
            ["name"=> 'АФТН', "name_lat"=> 'AFTN', "possition"=> 4, "isActive"=> true],
            ["name"=> 'ИНН', "name_lat"=> 'INN', "possition"=> 5, "isActive"=> true],
            ["name"=> 'E-mail', "name_lat"=> 'E-mail', "possition"=> 6, "isActive"=> true],
            ["name"=> 'Роль', "name_lat"=> 'Role', "possition"=> 7, "isActive"=> true],
            ["name"=> 'Дата регистрации', "name_lat"=> 'Registration date', "possition"=> 8, "isActive"=> true],
            ["name"=> 'Дата и время последнего входа', "name_lat"=> 'Date and time of last login', "possition"=> 9, "isActive"=> true],
            ["name"=> 'Статус', "name_lat"=> 'Status', "possition"=> 10, "isActive"=> true],
            ["name"=> 'act', "name_lat"=> 'act', "possition"=> 23, "isActive"=> true],

        ];
    }

}
