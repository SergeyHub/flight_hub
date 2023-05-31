<?php

use App\Http\Controllers\Api\V1\NFormAgreementController;
use App\Http\Controllers\Api\V1\RegistryNDatesController;
use App\Http\Controllers\Api\V1\RRegistry\RegistryRController;
use App\Http\Controllers\Api\V1\RForm\RFormAgreementController;
use App\Http\Controllers\Api\V1\RForm\RFormCommentController;
use App\Http\Controllers\Api\V1\RForm\RFormController;
use App\Http\Controllers\Api\V1\User\FavoriteFormNController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group([
//    'middleware' => ['cors']

], function () {
    Route::group([
        'namespace' => 'Api'
    ], function () {
        Route::group([
            'namespace' => 'V1',
            'prefix' => 'v1'
        ], function () {

            /* User Registry*/
            Route::get('getUserRegistry', 'UserRegistryController@getUserRegistry')->middleware('auth:api'); //old
            Route::get('getUserTableColumns/{userId}', 'UserRegistryController@getTableColumns');
            Route::post('changeUserTableColumns', 'UserRegistryController@changeTableColumns');

            /* Registry */
            Route::get('getRegistry', 'RegistryController@getRegistry')->middleware('auth:api');
            Route::get('getCountSigns', 'RegistryController@getCountSigns')->middleware('auth:api');
            Route::get('getArchivedRegistry', 'RegistryController@getArchivedRegistry');
            Route::post('archivedNform', 'RegistryController@archivedNform')->middleware('auth:api');
            Route::get('getTableColumns/{userId}', 'RegistryController@getTableColumns');
            Route::post('changeTableColumns', 'RegistryController@changeTableColumns');
            Route::get('getRegistryAsDates', [RegistryNDatesController::class, 'getRegistryAsDates'])->middleware('auth:api');
            Route::get('getRetryRegistryTableColumns/{userId}',[RegistryNDatesController::class, 'getTableColumns'])->middleware('auth:api');
            Route::post('changeRetryRegistryTableColumns', [RegistryNDatesController::class, 'changeTableColumns']);
            /* Registry R */
            Route::get('getRRegistry', [RegistryRController::class, 'getRRegistry'])->middleware('auth:api');
//            Route::get('getCountRSigns', [RegistryRController::class, 'getRCountSigns'])->middleware('auth:api');
//            Route::get('getArchivedRRegistry', [RegistryRController::class, 'getRArchivedRegistry']);
//            Route::post('archivedRForm', [RegistryRController::class, archivedRform']->middleware('auth:api');
            Route::get('getRegistryRTableColumns/{userId}', [RegistryRController::class, 'getTableColumns']);
            Route::post('changeRegistryRTableColumns', [RegistryRController::class, 'changeTableColumns']);
//            Route::get('getRRegistryAsDates', [RegistryRController::class, 'getRRegistryAsDates'])->middleware('auth:api');

            /* АКВС */

            Route::get('deleteAkvsRegistry', 'AkvsController@deleteAkvsRegistry')->middleware('auth:api');
            Route::get('getAkvsRegistry', 'AkvsRegistryController@getAkvsRegistry')->middleware('auth:api');
            Route::get('getDraftAkvsRegistry', 'AkvsRegistryController@getDraftAkvsRegistry')->middleware('auth:api');
            Route::get('getAkvsById', 'AkvsRegistryController@getAkvsById')->middleware('auth:api');

            Route::post('saveAkvs', 'AkvsController@save')->middleware('auth:api');
            Route::post('updateAkvs', 'AkvsController@update')->middleware('auth:api');
            Route::post('getAKVSTableColumns', 'AkvsController@getTableColumns')->middleware('auth:api');
            Route::post('changeAKVSTableColumns', 'AkvsController@changeTableColumns')->middleware('auth:api');
            Route::post('archivedAkvs', 'AkvsController@archivedAkvs');
            Route::post('changeAkvsVerify', 'AkvsController@changeAkvsStatus')->middleware('auth:api');
            Route::post('deleteColumns', 'AkvsController@deleteTableColumns')->middleware('auth:api');
            Route::post('favoriteAkvs', 'AkvsController@favoriteAkvs')->middleware('auth:api');

            /* SOAP */

            Route::post('soapindex', 'SoapController@soapIndex');
            Route::get('soaptest', 'SoapController@soapTest');
            Route::get('soapurl', 'SoapController@soapFun');
            Route::get('wsdl', 'SoapController@wsdl');

            /* NForm */
            Route::post('saveNForm', 'NFormController@save');
            Route::post('updateNForm', 'NFormController@update')->middleware('auth:api');
            Route::get('GetFormN', 'NFormController@get')->middleware('auth:api');
            Route::post('checkingForms', 'NFormController@checkingAvailabilityForms')->middleware('auth:api');
            Route::get('uniteForms', 'NFormController@uniteForms')->middleware('auth:api');
            Route::get('duplicateNForm/{id}', 'NFormController@duplicate')->middleware('auth:api');
            Route::get('checkAno', 'NFormController@checkAno');
            Route::get('checkPoint', 'ChecksController@checkPoint');

            /* RForm */
            Route::post('saveRForm', [RFormController::class, 'save']);
            Route::post('updateRForm', [RFormController::class, 'update'])->middleware('auth:api');
            Route::get('GetFormR', [RFormController::class, 'get'])->middleware('auth:api');
//            Route::post('checkingForms', [RFormController::class,'checkingAvailabilityForms'])->middleware('auth:api');
//            Route::get('uniteForms', [RFormController::class, 'uniteForms'])->middleware('auth:api');
            Route::get('duplicateRForm/{id}', [RFormController::class, 'duplicate'])->middleware('auth:api');
//            Route::get('checkAno', [RFormController::class, 'checkAno']);
//            Route::get('checkPoint', [ChecksController::class, 'checkPoint']);

            /* History */
            Route::get('getNFormHistory/{id_pakus}', 'NFormHistoryController@get');
            Route::get('extendTimeNForm', 'NFormController@extendTimeNForm');

            /* NFormComments */
            Route::post('saveNFormComment', 'NFormCommentController@save');
            Route::post('deleteNFormComment', 'NFormCommentController@delete');
            Route::post('saveRFormComment', [RFormCommentController::class, 'save']);
            Route::post('deleteRFormComment', [RFormCommentController::class,'delete']);

            /* Сохранение файлов */
            Route::post('saveFile', 'FilesController@save');

            /* Согласование форм */
            Route::post('nFormAgreement', [NFormAgreementController::class, 'nFormAgreement']);
            Route::post('nFormAgreementCorrect', [NFormAgreementController::class, 'nFormAgreementCorrect']);
            Route::post('rFormAgreement', [RFormAgreementController::class, 'rFormAgreement']);
            Route::post('rFormAgreementCorrect', [RFormAgreementController::class, 'rFormAgreementCorrect']);

            /* Directories */
            Route::get('GetDirectory', 'DirectoriesController@getDirectory');
            Route::get('GetAkvsDirectory', 'DirectoriesController@getAkvsDirectory')->middleware('auth:api');

            /* CBD */
//            Route::get('cbd/example', 'Admin\CBD\ExampleController@example');

            Route::get('page/{uniqueField}', 'PageController@show');
//            Route::get('settings', 'SettingsController@index');

            /* Airhubs */
            Route::post('airhubs', [\App\Http\Controllers\Api\V1\AirhubController::class, 'store'])
                ->name('airhubs.store');
            Route::match(['put', 'patch'], 'airhubs/{airhub}', [\App\Http\Controllers\Api\V1\AirhubController::class, 'update'])
                ->name('airhubs.update');
            Route::delete('airhubs/{airhub}', [\App\Http\Controllers\Api\V1\AirhubController::class, 'destroy'])
                ->name('airhubs.destroy');

            /* FlightDirections */
            Route::get('flight_directions_detail', [\App\Http\Controllers\Api\V1\FlightDirectionController::class, 'detail'])
                ->name('flight_directions.detail');
            Route::get('flight_directions', [\App\Http\Controllers\Api\V1\FlightDirectionController::class, 'index'])
                ->name('flight_directions.index');
            Route::post('flight_directions', [\App\Http\Controllers\Api\V1\FlightDirectionController::class, 'store'])
                ->name('flight_directions.store');
            Route::match(['put', 'patch'], 'flight_directions/{flight_direction}', [\App\Http\Controllers\Api\V1\FlightDirectionController::class, 'update'])
                ->name('flight_directions.update');
            Route::delete('flight_directions/{flight_direction}', [\App\Http\Controllers\Api\V1\FlightDirectionController::class, 'destroy'])
                ->name('flight_directions.destroy');

            /* RPL */
            Route::get('rpl_generate', [\App\Http\Controllers\Api\V1\RplController::class, 'generate'])
                ->name('rpl.generate');

            Route::post('rpl_automatic_processing', [\App\Http\Controllers\Api\V1\RplController::class, 'automaticProcessing'])
                ->name('rpl.automatic_processing');
            Route::post('rpl_manual_processing', [\App\Http\Controllers\Api\V1\RplController::class, 'manualProcessing'])
                ->name('rpl.manual_processing');

            Route::get('rpl', [\App\Http\Controllers\Api\V1\RplController::class, 'index'])
                ->name('rpl.index');
            Route::post('rpl', [\App\Http\Controllers\Api\V1\RplController::class, 'store'])
                ->name('rpl.store');
            Route::match(['put', 'patch'], 'rpl/{rpl}', [\App\Http\Controllers\Api\V1\RplController::class, 'update'])
                ->name('rpl.update');
            Route::delete('rpl/{rpl}', [\App\Http\Controllers\Api\V1\RplController::class, 'destroy'])
                ->name('rpl.destroy');

            /* FlightRegularities */
            Route::post('flight_regularities', [\App\Http\Controllers\Api\V1\FlightRegularityController::class, 'store'])
                ->name('flight_regularities.store');
            Route::match(['put', 'patch'], 'flight_regularities/{flightRegularity}', [\App\Http\Controllers\Api\V1\FlightRegularityController::class, 'update'])
                ->name('flight_regularities.update');
            Route::delete('flight_regularities/{flightRegularity}', [\App\Http\Controllers\Api\V1\FlightRegularityController::class, 'destroy'])
                ->name('flight_regularities.destroy');

            /* AdminController */
            Route::group([
                'namespace' => 'Admin',
                'middleware' => 'auth:api',
            ], function () {
                Route::apiResource('users', 'UserController'); // users
                Route::get('usersRegistry', 'UserController@usersRegistry'); // actual users registry
                Route::get('getArchivedUsers', 'UserController@archivedUsers'); // old
                Route::get('getuser', 'UserController@getUser'); // getAuthuser
                Route::post('changeStatus/{id}/{status}', 'UserController@updateUserStatus'); // changeStatus

                /* RoleController */
                Route::get('roles', 'RoleController@index')->name('roles');
                Route::post('roles/set-role/{user_id}/{role_id}', 'RoleController@setRole')->name('setRole');
                Route::post('roles/unset-role/{user_id}/{role_id}', 'RoleController@unsetRole')->name('unsetRole');
                Route::get('roles/get-active-role/{user_id}', 'RoleController@getActiveRole')->name('getActiveRole');
                Route::get('roles/set-active-role/{user_id}/{role_id}', 'RoleController@setActiveRole')->name('setActiveRole');

                /* User Settings*/
                Route::get('getLang/{userId}/{language}', 'UserSettingController@getSiteLang')->name('getLang');
                Route::post('changeLang/{userId}/{language}', 'UserSettingController@changeSiteLang')->name('changeLang');
                Route::get('getNotification/{userId}/{type}', 'UserSettingController@getNotification')->name('getNotification');
                Route::post('changeNotification/{userId}/{type}', 'UserSettingController@changeNotification')->name('changeNotification');
                Route::get('getViewPresentation/{userId}', 'UserSettingController@getViewPresentation')->name('getViewPresentation');
                Route::post('changeViewPresentation/{userId}/{type}', 'UserSettingController@changeViewPresentation')->name('changeViewPresentation');
            });

            /* UserController */
            Route::group([
                'namespace' => 'User',
                'middleware' => 'auth:api',
            ], function () {

                /* UserFilterController */
                Route::get('filter', 'UserFilterController@index')->name('filter.index');
                Route::post('filter/create', 'UserFilterController@store')->name('filter.store');
                Route::post('filter/change', 'UserFilterController@update')->name('filter.update');
                Route::post('filter/remove', 'UserFilterController@destroy')->name('filter.destroy');

                Route::get('/form-n/addToFavorites/{id_pakus}', [FavoriteFormNController::class, 'create'])->name('n-form-favorites.store');
                Route::get('/form-n/removeFromFavorites/{id_pakus}', [FavoriteFormNController::class, 'delete'])->name('n-form-favorites.destroy');
            });
        });
    });


    /* Auth */
    Route::group([
        'namespace' => 'Auth\Api',
        'middleware' => 'api',
        'prefix' => 'auth',
    ], function () {
        /* Login */
        Route::post('login', 'LoginController@login')->name('login');
        Route::get('login/{uuid}/{token}', 'LoginController@verify');
        // auth/authentication/{token} // front приложения APP

        /* Register */
        Route::post('register', 'RegisterController@register')->name('register');
        Route::get('register/{token}', 'RegisterController@verify'); // activation token
        // auth/verify/{token} // front приложения APP

        /* ResetPassword */
        Route::group([
            'prefix' => 'password',
        ], function () {
            Route::post('email', 'PasswordResetController@email'); // send
            Route::get('email/{token}', 'PasswordResetController@verify'); // use link
            // auth/access-recovery/{token} // front приложения APP

            Route::post('reset', 'PasswordResetController@reset'); // new password
        });

        /* Logout */
        Route::group([
            'middleware' => 'auth:api',
        ], function () {
            Route::get('logout', 'LoginController@logout')->name('logout');
        });
    });

//    Route::group([
//        'namespace' => 'Api',
//        'middleware' => 'auth:api'
//    ], function ()
//    {
//        Route::get('users/{uniqueField}', 'Admin\UserController@show');
//        Route::put('users/{uniqueField}', 'Admin\UserController@update');
//        Route::get('has-permissions/{id}', 'Admin\UserController@hasPermissions');
//    });
});
