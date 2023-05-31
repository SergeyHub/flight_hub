<?php

namespace App\Http\Controllers\Api\V1;


use App\Models\Airlflt;
use App\Models\Airlhist;
use App\Models\Airline;
use App\Models\AkvsAcftHist;
use App\Models\AkvsAcftmod;
use App\Models\AkvsAircraft;
use App\Models\AkvsAircraftOwner;
use App\Models\AkvsAirlhist;
use App\Models\AkvsAirline;
use App\Models\AkvsArlflt;
use App\Models\AkvsFleet;
use App\Models\AkvsOrganiz;
use App\Models\AkvsPersonInfo;
use App\Models\File;
use App\Models\FileAkvsAircraft;
use App\Models\FileAkvsAirline;
use App\Models\NForm;
use App\Models\PersonInfo;
use App\Models\PersonInfoContact;
use App\Models\Setting;
use App\Models\SettingParameter;
use App\Models\UserFavoriteAkvsAirlines;
use Illuminate\Database\Eloquent\HigherOrderBuilderProxy;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Psy\Util\Json;
use App\Models\AkvsMaxVersion;
use App\Models\Organiz;
use App\Models\Fleet;

class AkvsController extends Controller
{
    //
    private array $headers = ['Content-type' => 'application/json', 'Access-Control-Allow-Origin' => '*'];

    public function save(Request $request): JsonResponse
    {
        $akvsRequest = json_decode($request->getContent(), true);

        $akvsAirline = null;

        $authId = Auth::id();

        DB::transaction(function () use ($akvsRequest, &$akvsAirline, $authId){

            // Создание новой строки в таблице akvs_airlines

           if($this->requestHasNotEmptyArray($akvsRequest, 'airline')){

               $akvsAirlineRequest = $akvsRequest['airline'];

               $akvsOrganiz = AkvsOrganiz::create([
                   'INN' => $akvsAirlineRequest['inn'],
                   'KPP' => $akvsAirlineRequest['kpp']
               ]);

               /* Создание новой АК */
               if($akvsAirlineRequest['akvs_airlines_id'] === null){

                   $akvsAirline = AkvsAirline::create([
                       'version' => 1,
                       'status' => $akvsAirlineRequest['status'],
                       'author_id' => $authId,
                       'is_verify_latest' => 0,
                       'ICAOLAT3' => $akvsAirlineRequest['airline_icao'],
                       'FULLNAMELAT' => $akvsAirlineRequest['airline_namelat'],
                       'FULLNAMERUS' => $akvsAirlineRequest['airline_namerus'],
                       'commerce_name' => $akvsAirlineRequest['commerce_name'],
                       'iata_code' => $akvsAirlineRequest['iata_code'],
                       'legal_address' => $akvsAirlineRequest['legal_address'],
                       'mailing_address' => $akvsAirlineRequest['mailing_address'],
                       'correspondence_address' => $akvsAirlineRequest['correspondence_address'],
                       'STATES_ID' => $akvsAirlineRequest['states_id'],
                       'AIRLINES_ID' => $akvsAirlineRequest['AIRLINES_ID'],
                       'akvs_airlines_global_id' => time(),
                       'akvs_organiz_id' => $akvsOrganiz->akvs_organiz_id,
                   ]);


                   $this->saveNotCBDDataARILINES($akvsAirlineRequest, $akvsAirline->akvs_airlines_id,  $akvsAirline->akvs_airlines_global_id);

               }

               /* Сохранение airlines на основе akvs */

               if($akvsAirlineRequest['akvs_airlines_id'] !== null){

                   $akvsOrganiz = AkvsOrganiz::create([
                       'INN' => $akvsAirlineRequest['inn'],
                       'KPP' => $akvsAirlineRequest['kpp']
                   ]);

                   $akvsAirline = AkvsAirline::find($akvsAirlineRequest['akvs_airlines_id'])->replicate()->fill([
                       'version' => 1,
                       'status' => $akvsAirlineRequest['status'],
                       'author_id' => $authId,
                       'is_verify_latest' => 0,
                       'ICAOLAT3' => $akvsAirlineRequest['airline_icao'],
                       'FULLNAMELAT' => $akvsAirlineRequest['airline_namelat'],
                       'FULLNAMERUS' => $akvsAirlineRequest['airline_namerus'],
                       'commerce_name' => $akvsAirlineRequest['commerce_name'],
                       'iata_code' => $akvsAirlineRequest['iata_code'],
                       'legal_address' => $akvsAirlineRequest['legal_address'],
                       'mailing_address' => $akvsAirlineRequest['mailing_address'],
                       'correspondence_address' => $akvsAirlineRequest['correspondence_address'],
                       'STATES_ID' => $akvsAirlineRequest['states_id'],
                       'AIRLINES_ID' => $akvsAirlineRequest['AIRLINES_ID'],
                       'akvs_airlines_global_id' => time(),
                       'akvs_organiz_id' => $akvsOrganiz->akvs_organiz_id,
                   ]);

                   $akvsAirline->save();

                   $this->saveNotCBDDataARILINES($akvsAirlineRequest, $akvsAirline->akvs_airlines_id,  $akvsAirline->akvs_airlines_global_id);
               }

               $akvsArlHist = AkvsAirlhist::create([
                   'akvs_airlines_id' =>  $akvsAirline->akvs_airlines_id,
                   'ICAOLAT3' =>  $akvsAirlineRequest['airline_icao'],
               ]);

               if($akvsAirlineRequest['AIRLINES_ID'] !== null)
               {
                   $this->fillOrganizFromCDB($akvsAirlineRequest['AIRLINES_ID'],$akvsOrganiz->akvs_organiz_id);
                   $this->fillAirlineFromCDB($akvsAirlineRequest['AIRLINES_ID'],$akvsAirline->akvs_airlines_id);
                   $this->fillArlHistFromCBD($akvsAirlineRequest['AIRLINES_ID'],$akvsArlHist->akvs_arlhist_id);
               }

               if($this->requestHasNotEmptyArray($akvsAirlineRequest,'fleet')){

                   $FLEET = $akvsAirlineRequest['fleet'];

                   foreach($FLEET as $aircraft)
                   {
                       /* СОхранение нового ВС и привязка к АК  && $aircraft['is_new'] === 1 */
                       if($aircraft['akvs_fleet_id'] === null  && $aircraft['is_delete'] === 0)
                       {
                           $akvsFleet = AkvsFleet::create([
                               'REGNO' => $aircraft['REGNO'],
                               'SERIALNO' => $aircraft['SERIALNO'],
                               'MAXIMUMWEIGHT' => $aircraft['MAXIMUMWEIGHT'],
                               'ACFTMOD_ID' => $aircraft['ACFTMOD_ID'],
                               'FLEET_ID' => $aircraft['FLEET_ID'],
                           ]);

                           $this->saveNotCBDDataFLEET($aircraft,$akvsFleet->akvs_fleet_id,$akvsAirline->akvs_airlines_id);

                           $akvsAirlFlt = AkvsArlflt::create([
                               'akvs_airlines_id' => $akvsAirline->akvs_airlines_id,
                               'akvs_fleet_id' => $akvsFleet->akvs_fleet_id,
                           ]);

                           /* СОхранение ВС на основе ЦБДшного, и привязка к АК */
                           if($aircraft['FLEET_ID'] !== null)
                           {
                               $this->fillFleetFromCBD($aircraft['FLEET_ID'],$akvsFleet->akvs_fleet_id);

                               if($akvsAirlineRequest['AIRLINES_ID'] !== null){
                                   $this->fillArlfltFromCBD($aircraft['FLEET_ID'],$akvsAirlineRequest['AIRLINES_ID'],$akvsAirlFlt->akvs_arlflt_id);
                               }
                           }
                       }

                       /* СОхранение ВС на основе существующего АКВС && $aircraft['is_new'] === 1 */
                       if($aircraft['akvs_fleet_id'] !== null && $aircraft['is_delete'] === 0)
                       {
                           $akvsFleet = AkvsFleet::find($aircraft['akvs_fleet_id'])
                               ->replicate()->fill([
                                   'REGNO' => $aircraft['REGNO'],
                                   'SERIALNO' => $aircraft['SERIALNO'],
                                   'MAXIMUMWEIGHT' => $aircraft['MAXIMUMWEIGHT'],
                                   'ACFTMOD_ID' => $aircraft['ACFTMOD_ID'],
                               ]);
                           $akvsFleet->save();

                           $this->saveNotCBDDataFLEET($aircraft,$akvsFleet->akvs_fleet_id,$akvsAirline->akvs_airlines_id);

                           $akvsAirlFlt = AkvsArlflt::create([
                               'akvs_airlines_id' => $akvsAirline->akvs_airlines_id,
                               'akvs_fleet_id' => $akvsFleet->akvs_fleet_id,
                           ]);

                           if($akvsAirlineRequest['AIRLINES_ID'] !== null && $aircraft['FLEET_ID'] !== null){
                               $this->fillArlfltFromCBD($aircraft['FLEET_ID'],$akvsAirlineRequest['AIRLINES_ID'],$akvsAirlFlt->akvs_arlflt_id);
                           }

                       }

                   }
               }

           }
        });

        return response()->json(
            [
                'message' => "Akvs_Airlines $akvsAirline->akvs_airlines_id was successfully created",
                'akvs_airlines_id' => $akvsAirline->akvs_airlines_id
            ], 201, $this->headers);
    }

    public function deleteAkvsRegistry()
    {
        DB::table('akvs_person_info')->truncate();
        DB::table('AKVS_AIRLINES')->truncate();
        DB::table('AKVS_FLEET')->truncate();
        DB::table('akvs_aircraft_owner')->truncate();
        DB::table('AKVS_ORGANIZ')->truncate();
        DB::table('AKVS_AIRLHIST')->truncate();
    }

    public function changeAkvsStatus(Request $request){
        AkvsAirline::where('akvs_airlines_id','=',$request['akvs_airlines_id'])->update(
            [
                'is_verify_latest' => (int)$request['is_verify'],
                'status' => $request['status']
            ]
        );
    }

    public function update(Request $request): JsonResponse
    {
        $AkvsArray = json_decode($request->getContent(), true);
        $newAkvs = $this->updateFromArray($AkvsArray);

        return response()->json(
            [
                'message' => "Akvs № ".$newAkvs['message'],
                'akvs_ailines_id' => $newAkvs['akvs_airlines_id'],
            ], 201, $this->headers);
    }

    public function updateFromArray($array, bool $isComingFromFront = true)
    {
        $mainAkvsRequest = $array;

        $updatedAkvs = null;

        DB::transaction(function () use ($mainAkvsRequest, &$updatedAkvs) {

            if ($this->requestHasNotEmptyArray($mainAkvsRequest, 'airline')) {

                $akvsAirlines = $mainAkvsRequest['airline'];

                if($akvsAirlines['akvs_airlines_id'] !== null && $akvsAirlines['akvs_airlines_global_id'] !== null)
                {
                    $akvsOrganiz = AkvsOrganiz::find($akvsAirlines['akvs_organiz_id']);

                    $updatedAkvsOrganiz = $akvsOrganiz->replicate()->fill([
                        'INN' => $akvsAirlines['inn'],
                        'KPP' => $akvsAirlines['kpp'],
                    ]);

                    $updatedAkvsOrganiz->save();

                    $datetime = Carbon::now()->toDateTimeString();

                    $originalAkvs = AkvsAirline::find($akvsAirlines['akvs_airlines_id']);

                    $updatedAkvs = $originalAkvs->replicate()->fill([
                        'version' => (int)AkvsAirline::where('akvs_airlines_global_id','=',$originalAkvs->akvs_airlines_global_id)->max('version') + 1,
                        'status' => $akvsAirlines['status'],
                        'author_id' => Auth::id(),
                        'is_verify_latest' => 0,
                        'ICAOLAT3' => $akvsAirlines['airline_icao'],
                        'FULLNAMELAT' => $akvsAirlines['airline_namelat'],
                        'FULLNAMERUS' => $akvsAirlines['airline_namerus'],
                        'commerce_name' => $akvsAirlines['commerce_name'],
                        'iata_code' => $akvsAirlines['iata_code'],
                        'legal_address' => $akvsAirlines['legal_address'],
                        'mailing_address' => $akvsAirlines['mailing_address'],
                        'correspondence_address' => $akvsAirlines['correspondence_address'],
                        'STATES_ID' => $akvsAirlines['states_id'],
                        'akvs_organiz_id' => $updatedAkvsOrganiz->akvs_organiz_id,
                        'created_at' => $datetime,
                        'updated_at' => $datetime
                    ]);

                    $updatedAkvs->save();

                    $akvsAirlHist = AkvsAirlhist::where('akvs_airlines_id', $akvsAirlines['akvs_airlines_id'])->first();

                    $updatedAirlhist = $akvsAirlHist->replicate()->fill([
                        'akvs_airlines_id' => $updatedAkvs->akvs_airlines_id,
                        'ICAOLAT3' => $akvsAirlines['airline_icao']
                    ]);

                    $updatedAirlhist->save();

                    if($akvsAirlines['AIRLINES_ID'] !== null)
                    {
                        $this->fillOrganizFromCDB($akvsAirlines['AIRLINES_ID'],$updatedAkvsOrganiz->akvs_organiz_id);
                        $this->fillAirlineFromCDB($akvsAirlines['AIRLINES_ID'],$updatedAkvs->akvs_airlines_id);
                        $this->fillArlHistFromCBD($akvsAirlines['AIRLINES_ID'],$updatedAirlhist->akvs_arlhist_id);
                    }

                    $this->saveNotCBDDataARILINES($akvsAirlines,$updatedAkvs->akvs_airlines_id,$updatedAkvs->akvs_airlines_global_id);

                    if($this->requestHasNotEmptyArray($akvsAirlines,'fleet'))
                    {
                        foreach($akvsAirlines['fleet'] as $aircraft){

                            if($aircraft['FLEET_ID'] !== null) {
                                $akvsFleet = null;

                                if ($aircraft['akvs_fleet_id'] !== null) {
                                    $akvsFleet = AkvsFleet::find($aircraft['akvs_fleet_id'])->replicate()->fill([
                                        'REGNO' => $aircraft['REGNO'],
                                        'SERIALNO' => $aircraft['SERIALNO'],
                                        'MAXIMUMWEIGHT' => $aircraft['MAXIMUMWEIGHT'],
                                        'ACFTMOD_ID' => $aircraft['ACFTMOD_ID'],
                                        'FLEET_ID' => $aircraft['FLEET_ID'],
                                    ]);
                                    $akvsFleet->save();

                                } else {
                                    $akvsFleet = AkvsFleet::create([
                                        'REGNO' => $aircraft['REGNO'],
                                        'SERIALNO' => $aircraft['SERIALNO'],
                                        'MAXIMUMWEIGHT' => $aircraft['MAXIMUMWEIGHT'],
                                        'ACFTMOD_ID' => $aircraft['ACFTMOD_ID'],
                                        'FLEET_ID' => $aircraft['FLEET_ID'],
                                    ]);
                                }


                                $this->saveNotCBDDataFLEET($aircraft, $akvsFleet->akvs_fleet_id, $updatedAkvs->akvs_airlines_id);

                                $akvsAirlFlt = AkvsArlflt::create([
                                    'akvs_airlines_id' => $updatedAkvs->akvs_airlines_id,
                                    'akvs_fleet_id' => $akvsFleet->akvs_fleet_id,
                                ]);
                                /* СОхранение ВС на основе ЦБДшного, и привязка к АК */

                                $this->fillFleetFromCBD($aircraft['FLEET_ID'], $akvsFleet->akvs_fleet_id);

                                if ($akvsAirlines['AIRLINES_ID'] !== null) {
                                    $this->fillArlfltFromCBD($aircraft['FLEET_ID'], $akvsAirlines['AIRLINES_ID'], $akvsAirlFlt->akvs_arlflt_id);
                                }

                                if ($aircraft['is_delete'] === 1) {
                                    $akvsAirlFlt->update([
                                        'ISDELETE' => 1
                                    ]);
                                }
                            }else {
                                $akvsFleet = null;

                                if($aircraft['akvs_fleet_id'] !== null)
                                {
                                    $akvsFleet = AkvsFleet::find($aircraft['akvs_fleet_id'])->replicate()->fill([
                                        'REGNO' => $aircraft['REGNO'],
                                        'SERIALNO' => $aircraft['SERIALNO'],
                                        'MAXIMUMWEIGHT' => $aircraft['MAXIMUMWEIGHT'],
                                        'ACFTMOD_ID' => $aircraft['ACFTMOD_ID'],
                                    ]);
                                    $akvsFleet->save();

                                }else{
                                    $akvsFleet = AkvsFleet::create([
                                        'REGNO' => $aircraft['REGNO'],
                                        'SERIALNO' => $aircraft['SERIALNO'],
                                        'MAXIMUMWEIGHT' => $aircraft['MAXIMUMWEIGHT'],
                                        'ACFTMOD_ID' => $aircraft['ACFTMOD_ID'],
                                    ]);
                                }

                                if( $aircraft['FLEET_ID'] !== null)
                                {
                                    $akvsFleet->update([
                                      'FLEET_ID' =>  $aircraft['FLEET_ID']
                                    ]);
                                    $this->fillFleetFromCBD($aircraft['FLEET_ID'],$akvsFleet->akvs_fleet_id);
                                }


                                $this->saveNotCBDDataFLEET($aircraft,$akvsFleet->akvs_fleet_id,$updatedAkvs->akvs_airlines_id);

                                $akvsAirlFlt = AkvsArlflt::create([
                                    'akvs_airlines_id' => $updatedAkvs->akvs_airlines_id,
                                    'akvs_fleet_id' => $akvsFleet->akvs_fleet_id,
                                ]);

                                if($akvsAirlines['AIRLINES_ID'] !== null){
                                    $this->fillArlfltFromCBD($aircraft['FLEET_ID'],$akvsAirlines['AIRLINES_ID'],$akvsAirlFlt->akvs_arlflt_id);
                                }

                            }
                        }
                    }
                }
            }
        });

        return [
            'akvs_airlines_id' => $updatedAkvs->akvs_airlines_id,
            'message' => 'Akvs Updated successfully.',
        ];
    }

    public function get():JsonResponse
    {
        $aKvS = null;


        return response()->json(['AKVS' => $aKvS], 200, $this->headers);
    }

    public function getTableColumns(): JsonResponse
    {
        $userSetting = Setting::where('user_id', \Auth::id())
            ->where('name', 'akvs_registry_columns')
            ->first();

        if ($userSetting === null){
            $setting = new Setting();
            $setting->name = 'akvs_registry_columns';
            $setting->user_id = \Auth::id();
            $setting->save();
            $settingParamter = new SettingParameter();
            $settingParamter->key = 'akvs_registry_columns';
            $settingParamter->value = json_encode($this->getColumns(), JSON_UNESCAPED_UNICODE);
            $settingParamter->setting_id = $setting->id;
            $settingParamter->save();
            return response()->json([
                'userAkvsColumns' => json_decode($settingParamter['value']),
            ], 200, [], JSON_UNESCAPED_UNICODE);
        }else{
            $userRegistryColumns = SettingParameter::where('setting_id', $userSetting['id'])->first();

            if($userRegistryColumns == null){
                $userRegistryColumns = new SettingParameter();
                $userRegistryColumns->key = 'akvs_registry_columns';
                $userRegistryColumns->value = json_encode($this->getColumns(), JSON_UNESCAPED_UNICODE);
                $userRegistryColumns->setting_id = $userSetting->id;
                $userRegistryColumns->save();
            }

            return response()->json([
                'userAkvsColumns' => json_decode($userRegistryColumns['value']),
            ], 200, [], JSON_UNESCAPED_UNICODE);
        }
    }

    public function changeTableColumns(Request $request)
    {
        $userSetting = Setting::where('user_id',\Auth::id())
            ->where('name', 'akvs_registry_columns')
            ->first();

        $userRegistryColumns = SettingParameter::where('setting_id', $userSetting['id'])->first();
        $userRegistryColumns['value'] = $request->getContent();

        $userRegistryColumns->save();

        return response()->json([
            'statuse' => true,
            'message' => 'Обновлен'
        ], 200, []);
    }

    public function deleteTableColumns(Request $request){
        $userSetting = Setting::where('user_id','=',$request['user_id'])
            ->where('name','=','akvs_registry_columns')->first();

       SettingParameter::where('setting_id', $userSetting->id)
            ->where('key','=','akvs_registry_columns')
            ->limit(1)
            ->delete();

       Setting::where('user_id','=',$request['user_id'])
           ->where('name','=','akvs_registry_columns')->limit(1)->delete();
    }

    public function getColumns(){
        return [
            ['position' => 0, 'name' => 'date_of_creation', 'name_lat' => 'date_of_creation', 'isActive' => true],
            ['position' => 1, 'name' => 'update_date', 'name_lat' => 'update_date', 'isActive' => true],
            ['position' => 2, 'name' => 'name', 'name_lat' => 'name', 'isActive' => true],
            ['position' => 3, 'name' => 'ICAO_code', 'name_lat' => 'ICAO_code', 'isActive' => true],
            ['position' => 4, 'name' => 'vs', 'name_lat' => 'vs', 'isActive' => true],
            ['position' => 5, 'name' => 'status', 'name_lat' => 'status', 'isActive' => true],
            ['position' => 6, 'name' => 'act', 'name_lat' => 'act', 'isActive' => true]
        ];
    }

    public function archivedAkvs(Request $request): JsonResponse
    {
        $akvsAirline = AkvsAirline::
        find($request['akvs_airlines_id']);

        $akvsAirline->is_archive = $request['status_archive'];

        $akvsAirline->save();

        return response()->json([
            'message' => 'Status changed successfully'
        ]);

    }

    public function favoriteAkvs(Request $request){

        $is_favorite_count = UserFavoriteAkvsAirlines::where('akvs_airlines_global_id','=',$request['akvs_airlines_global_id'])
            ->where('user_id','=',Auth::id())
            ->get()->toArray();


        if(count($is_favorite_count) == 0){
            UserFavoriteAkvsAirlines::create([
                'user_id' => Auth::id(),
                'akvs_airlines_global_id' => $request['akvs_airlines_global_id'],
            ]);
        }else{
            UserFavoriteAkvsAirlines::
            where('akvs_airlines_global_id',$request['akvs_airlines_global_id'])
                ->where('user_id',Auth::id())
                ->delete();
        }

        return response()->json([
            'message' => 'Status changed successfully to AKVS № '.$request['akvs_airlines_global_id'],
            'count_favorites' => count(UserFavoriteAkvsAirlines::where('user_id','=', Auth::id())->get()->toArray())
        ]);

    }

    private function saveFile(array $document): int
    {
        //Убираем пробелы из имени файла
       $fileName = str_replace(' ', '_', $document['file_name']);

        //Добавляем рандомные символы к имени файла, чтобы не было дублей и перезаписей файлов с одинаковыми именами
        $randomStringForPath = Str::random();
        $path = "documents/{$randomStringForPath}_".$fileName;


        //Вырезаем из fileBody префиксы вида data:text/plain;base64,
        $fileBody = $document['file_body'];

        if (str_starts_with($fileBody, 'data')) {
            $fileBody = preg_replace('/([a-zA-Z]|:|\/|;|[0-9]|\.|-)+,/', '', $fileBody);
        }

        //Сохраняем файл на диск
        Storage::disk('public')->put($path, base64_decode($fileBody));

        //add /api/ to url for access
        $fileSavingPathPrefix = env('APP_URL') . '/api';
        $replacedPath = str_replace(env('APP_URL'), $fileSavingPathPrefix, Storage::disk('public')->url($path));

        $attrs = 'null';

        if (array_key_exists('required_attributes_json', $document)) {
            $attrs = json_encode($document['required_attributes_json'], JSON_UNESCAPED_UNICODE);
        }

        $fileTypeId = 0;

        if (array_key_exists('file_type_id', $document)) {
            $fileTypeId = $document['file_type_id'];
        }

        $file = File::create([
            'file_type_name' => $document['file_type_name'],
            'file_type_id' => $fileTypeId,
            'filename' => $document['file_name'],
            'path' => $replacedPath,
            'other_attributes_json' => $attrs,
        ]);

        return $file->id;
    }

    private function saveNotCBDDataARILINES($Airline, $akvs_airlines_id, $akvs_airlines_global_id)
    {

        $akvsMaxVersion = AkvsMaxVersion::where('user_id',Auth::id())
            ->where('akvs_airlines_global_id',$akvs_airlines_global_id);

        if($akvsMaxVersion->count() > 0)
        {
            AkvsMaxVersion::where('user_id',Auth::id())
                ->where('akvs_airlines_global_id',$akvs_airlines_global_id)
                ->update([
                    'akvs_airlines_id' =>  $akvs_airlines_id
                ]);
        }else{
            AkvsMaxVersion::create([
                'akvs_airlines_id' =>   $akvs_airlines_id,
                'akvs_airlines_global_id' =>   $akvs_airlines_global_id,
                'user_id' =>   Auth::id(),
            ]);
        }


        if($this->requestHasNotEmptyArray($Airline, 'documents')){
            foreach ($Airline['documents'] as $document) {
                if(array_key_exists('file_id',$document)){
                    $file = File::find($document['file_id'])->replicate();
                    if(array_key_exists('required_attributes_json',$document)){
                        $file->fill([
                               'other_attributes_json' => $document['required_attributes_json']
                            ]);
                    }
                    $file->save();
                    FileAkvsAirline::create([
                            'file_id' => $file->id,
                            'akvs_airlines_id' => $akvs_airlines_id,
                    ]);
                }else{
                    FileAkvsAirline::create([
                        'file_id' => $this->saveFile($document),
                        'akvs_airlines_id' => $akvs_airlines_id,
                    ]);
                }
            }
        }

        //Если есть уполнамоченый представитель, добавить в person_info
        if($this->requestHasNotEmptyArray($Airline, 'airline_represent')){

            $akvsAirlineRepresentRequest = $Airline['airline_represent'];

            $akvsAirlineRepresent = AkvsPersonInfo::create([
                'fio_rus' => $akvsAirlineRepresentRequest['fio_rus'],
                'fio_lat' => $akvsAirlineRepresentRequest['fio_lat'],
                'akvs_airlines_id' => $akvs_airlines_id,
                'represent_type' => 1,
                'position_rus' => $akvsAirlineRepresentRequest['position_rus'],
                'position_lat' => $akvsAirlineRepresentRequest['position_lat'],
            ]);

            if($this->requestHasNotEmptyArray($akvsAirlineRepresentRequest,'contacts'))
            {

                $akvsRepresentContacts = $akvsAirlineRepresentRequest['contacts'];

                foreach($akvsRepresentContacts as $contacts){

                    PersonInfoContact::create([
                        'akvs_person_info_id' =>  $akvsAirlineRepresent->akvs_person_info_id,
                        'contact_type' =>  $contacts['contact_type'], // type email
                        'value' =>  $contacts['value'],
                        'is_main' =>  $contacts['is_main'],
                    ]);

                }
            }
        }

        //Если есть представитель в России, добавить в person_info
        if($this->requestHasNotEmptyArray($Airline, 'russia_represent')){

            $akvsAirlineRussiaRepresentRequest = $Airline['russia_represent'];

            $akvsRussiaRepresent = AkvsPersonInfo::create([
                'fio_rus' => $akvsAirlineRussiaRepresentRequest['fio_rus'],
                'fio_lat' => $akvsAirlineRussiaRepresentRequest['fio_lat'],
                'akvs_airlines_id' => $akvs_airlines_id,
                'represent_type' => 2
            ]);

            if($this->requestHasNotEmptyArray($akvsAirlineRussiaRepresentRequest,'contacts'))
            {

                $akvsRepresentContacts = $akvsAirlineRussiaRepresentRequest['contacts'];

                foreach($akvsRepresentContacts as $contacts){

                    PersonInfoContact::create([
                        'contact_type' =>  $contacts['contact_type'], // type email
                        'value' =>  $contacts['value'],
                        'is_main' =>  $contacts['is_main'],
                        'akvs_person_info_id' =>  $akvsRussiaRepresent->akvs_person_info_id,
                    ]);

                }

            }

        }
    }

    private function saveNotCBDDataFLEET($Fleet, $akvs_fleet_id, $akvs_airlines_id)
    {
        if($this->requestHasNotEmptyArray($Fleet, 'aircraft_owner'))
        {
            $aircraftOwner = $Fleet['aircraft_owner'];

            $akvsAircraftOwner = AkvsAircraftOwner::create([
                'name' => $aircraftOwner['name'],
                'gen_info' => $aircraftOwner['full_address'],
                'contact' => $aircraftOwner['contact_name'],
                'inn' => $aircraftOwner['inn'],
                'STATES_ID' => $aircraftOwner['states_id'],
                'email' => $aircraftOwner['email'],
                'tel' => $aircraftOwner['tel'],
                'fax' => $aircraftOwner['fax'],
                'sita' => $aircraftOwner['sita'],
                'aftn' => $aircraftOwner['aftn'],
            ]);


            AkvsFleet::where('akvs_fleet_id','=',$akvs_fleet_id)
                ->update([
                    'akvs_aircraft_owner_id' => $akvsAircraftOwner->akvs_aircraft_owner_id
                ]);

        }

        if($this->requestHasNotEmptyArray($Fleet, 'documents')){

            $document = $Fleet['documents'];

            $file_id  = null;

            foreach ($document as $doc) {
                if(array_key_exists('file_id',$doc)){
                    $file = File::find($doc['file_id'])->replicate();
                    if(array_key_exists('required_attributes_json',$doc)){
                        $file->fill([
                            'other_attributes_json' => $doc['required_attributes_json']
                        ]);
                    }
                    $file->save();
                    FileAkvsAircraft::create([
                        'file_id' => $file->id,
                        'akvs_fleet_id' => $akvs_fleet_id,
                    ]);
                }else{
                    FileAkvsAircraft::create([
                        'file_id' => $this->saveFile($doc),
                        'akvs_fleet_id' => $akvs_fleet_id,
                    ]);
                }
            }

        }

    }

    private function fillFleetFromCBD($FLEET_ID, $akvs_fleet_id)
    {
        $Fleet = Fleet::where('FLEET_ID','=',$FLEET_ID)->first();

        AkvsFleet::where('akvs_fleet_id','=',$akvs_fleet_id)
            ->update([
                'ACFTMOD_ID' => $Fleet->ACFTMOD_ID,
                'RSST_TYPE_ID' => $Fleet->RSST_TYPE_ID,
                'REGNO_OLD' => $Fleet->REGNO_OLD,
                'REGISTRNO' => $Fleet->REGISTRNO,
                'PRODDATE' => $Fleet->PRODDATE,
                'TOTALFLIGHTTIME' => $Fleet->TOTALFLIGHTTIME,
                'TOTALFLIGHTYEAR' => $Fleet->TOTALFLIGHTYEAR,
                'ACFTOWNER' => $Fleet->ACFTOWNER,
                'ACFTFACTORY' => $Fleet->ACFTFACTORY,
                'ISINTERFLIGHT' => $Fleet->ISINTERFLIGHT,
                'SPECIALREMARK' => $Fleet->SPECIALREMARK,
                'ACFTCOMMENT' => $Fleet->ACFTCOMMENT,
                'MAXIMUMWEIGHT_ORG' => $Fleet->MAXIMUMWEIGHT_ORG,
                'OBO' => $Fleet->OBO,
                'ACFTFUNCTION' => $Fleet->ACFTFUNCTION,
                'CERTIFACFTNO' => $Fleet->CERTIFACFTNO,
                'CERTIFACFTENDDATE' => $Fleet->CERTIFACFTENDDATE,
                'REGISTRDATE' => $Fleet->REGISTRDATE,
                'BEGINDATE' => $Fleet->BEGINDATE,
                'ENDDATE' => $Fleet->ENDDATE,
                'ISDELETE' => $Fleet->ISDELETE,
                'UPDATEDATE' => $Fleet->UPDATEDATE,
                'AIRLINES_ID' => $Fleet->AIRLINES_ID,
                'TYPE_ICAOLAT4' => $Fleet->TYPE_ICAOLAT4,
                'TYPE_NAMELAT' => $Fleet->TYPE_NAMELAT,
                'TYPE_NAMERUS' => $Fleet->TYPE_NAMERUS,
                'DESCRIPTION' => $Fleet->DESCRIPTION,
                'MAXLANDINGWEIGHT' => $Fleet->MAXLANDINGWEIGHT,
                'WEIGHTEMPTYPLAN' => $Fleet->WEIGHTEMPTYPLAN,
            ]);
    }

    private function fillArlfltFromCBD($FLEET_ID,$AIRLINES_ID,$akvs_airlflt_id)
    {
        $ArlFlt = Airlflt::where('AIRLINES_ID','=',$AIRLINES_ID)
            ->where('FLEET_ID','=',$FLEET_ID)->first();

        if($ArlFlt){
            AkvsArlflt::where('akvs_arlflt_id','=',$akvs_airlflt_id)
                ->update([
                    'AIRLFLT_ID' => $ArlFlt->AIRLFLT_ID,
                    'AIRLINES_ID' => $ArlFlt->AIRLINES_ID,
                    'OWNERTYPE_ID' => $ArlFlt->OWNERTYPE_ID,
                    'FLEET_ID' => $ArlFlt->FLEET_ID,
                    'ISDELETE' => $ArlFlt->ISDELETE,
                    'BEGINDATE' => $ArlFlt->BEGINDATE,
                    'ENDDATE' => $ArlFlt->ENDDATE,
                    'UPDATEDATE' => $ArlFlt->UPDATEDATE,
                ]);
        }

    }

    private function fillOrganizFromCDB($AIRLINES_ID, $akvs_organiz_id)
    {
        $akvsAirline = Airline::where('AIRLINES_ID','=',$AIRLINES_ID)->first();

        if(is_object($akvsAirline->organiz)){
            AkvsOrganiz::where('akvs_organiz_id','=',$akvs_organiz_id)
                ->update([
                    'ORGANIZ_ID' => $akvsAirline->organiz->ORGANIZ_ID,
                    'ADR1RUS' => $akvsAirline->organiz->ADR1RUS,
                    'ADR2RUS' => $akvsAirline->organiz->ADR2RUS,
                    'MAIL' => $akvsAirline->organiz->MAIL,
                    'TELEX' => $akvsAirline->organiz->TELEX,
                    'ATEL' => $akvsAirline->organiz->ATEL,
                    'INTERNET' => $akvsAirline->organiz->INTERNET,
                    'OKONH' => $akvsAirline->organiz->OKONH,
                    'OKPO' => $akvsAirline->organiz->OKPO,
                    'UPDATEDATE' => $akvsAirline->organiz->UPDATEDATE
                ]);
        }

    }

    private function fillAirlineFromCDB($AIRLINES_ID,$akvs_airlines_id)
    {
        $akvsAirline= Airline::where('AIRLINES_ID','=',$AIRLINES_ID)->first();

        AkvsAirline::where('akvs_airlines_id','=',$akvs_airlines_id)
            ->update([
               'AIR_AIRLINES_ID' =>  $akvsAirline->AIR_AIRLINES_ID,
               'AVST_TYPE_ID' =>  $akvsAirline->AVST_TYPE_ID,
               'ORGFORM_ID' =>  $akvsAirline->ORGFORM_ID,
               'ORGANIZ_ID' =>  $akvsAirline->ORGANIZ_ID,
               'REGCTRL_ID' =>  $akvsAirline->REGCTRL_ID,
               'AVADMIN_ID' =>  $akvsAirline->AVADMIN_ID,
               'IATALAT2' =>  $akvsAirline->IATALAT2,
               'NAMELAT' =>  $akvsAirline->NAMELAT,
               'SHORTNAMELAT' =>  $akvsAirline->SHORTNAMELAT,
               'SHORTNAMERUS' =>  $akvsAirline->SHORTNAMERUS,
               'ISUSEFULLNAMELAT' =>  $akvsAirline->ISUSEFILLNAMERUS,
               'NAMEJP' =>  $akvsAirline->NAMEJP,
               'ISCHARTERONLY' =>  $akvsAirline->ISCHARTERONLY,
               'ISINTERCIS' =>  $akvsAirline->ISINTERCIS,
               'OPERTYPE' =>  $akvsAirline->OPERTYPE,
               'ISAON' =>  $akvsAirline->ISAON,
               'ISBUSINESS' =>  $akvsAirline->ISBUSINESS,
               'BUSINESSTYPE' =>  $akvsAirline->BUSINESSTYPE,
               'FAS_ISN' =>  $akvsAirline->FAS_ISN,
               'ISMUSTCE' =>  $akvsAirline->ISMUSTCE,
               'ISUSEFULLNAMELAT' =>  $akvsAirline->ISUSEFULLNAMELAT,
               'ISSBORNIK' =>  $akvsAirline->ISSBORNIK,
               'ISLANGRUSSTAFF' =>  $akvsAirline->ISLANGRUSSTAFF,
               'ISANCCONTROL' =>  $akvsAirline->ISANCCONTROL,
               'ISDECLARANT' =>  $akvsAirline->ISDECLARANT,
               'EXPIRYTYPE' =>  $akvsAirline->EXPIRYTYPE,
               'TYPEAIRLINES' =>  $akvsAirline->TYPEAIRLINES,
               'EFFECTDATE' =>  $akvsAirline->EFFECTDATE,
               'EXPIRYDATE' =>  $akvsAirline->EXPIRYDATE,
               'UPDATEDATE' =>  $akvsAirline->UPDATEDATE
            ]);
    }

    private function fillArlHistFromCBD($AIRLINES_ID,$akvs_arlhist_id)
    {
        $Arlhist= Airlhist::where('AIRLINES_ID','=',$AIRLINES_ID)->first();

        AkvsAirlhist::where('akvs_arlhist_id','=',$akvs_arlhist_id)
            ->update([
                'AIRLHIST_ID' => $Arlhist->AIRLHIST_ID ,
                'NAMERUS' => $Arlhist->NAMERUS ,
                'CISCODE' => $Arlhist->CISCODE ,
                'ICAORUS3' => $Arlhist->ICAORUS3 ,
                'AIRLBEGINDATE' => $Arlhist->AIRLBEGINDATE ,
                'ENDDATE' => $Arlhist->ENDDATE ,
                'ISDELETE' => $Arlhist->ISDELETE ,
                'UPDATEDATE' => $Arlhist->UPDATEDATE
            ]);
    }


    private function requestHasNotEmptyArray($formSegment, $field): bool
    {
        if (!is_array($formSegment)) {
            return false;
        } else {
            return array_key_exists($field, $formSegment) && count($formSegment[$field]) > 0;
        }
    }
}
