<?php /** @noinspection PhpMultipleClassDeclarationsInspection */

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Classes\JsonExceptions;
use App\Http\Controllers\Controller;
use App\Models\Airline;
use App\Models\ApprovalGroup;
use App\Models\DepartureDate;
use App\Models\File;
use App\Models\NForm;
use App\Models\NFormAircraft;
use App\Models\NFormAircraftOwner;
use App\Models\NFormAirline;
use App\Models\NFormAirnavPayer;
use App\Models\NFormAlternativePoint;
use App\Models\NFormCargo;
use App\Models\NFormCrew;
use App\Models\NFormCrewGroup;
use App\Models\NFormCrewMember;
use App\Models\NFormFlight;
use App\Models\NFormFlightAgreementSign;
use App\Models\NFormPassenger;
use App\Models\NFormPassengersPerson;
use App\Models\NFormPoint;
use App\Models\PeriodDate;
use App\Models\PersonInfo;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\HigherOrderBuilderProxy;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;


class NFormController extends Controller
{
    /**
     * Create a new Form N
     *
     * @param Request $request
     * @return JsonResponse
     * @throws Throwable
     */
    public function save(Request $request): JsonResponse
    {
        $mainFormRequest = json_decode($request->getContent(), true);
        $nForm = null;

        DB::transaction(function () use ($mainFormRequest, &$nForm) {
            $nForm = NForm::create([
                'id_pakus' => time(),
                'id_pp' => 0,
                'version' => 1,
                'permit_num' => "0",
                'comment' => '',
                'is_entire_fleet' => $mainFormRequest['is_entire_fleet'],
                'is_latest' => 1,
                'original_created_at' => now()->toDateTimeString(),
                'author_id' => $mainFormRequest['author_id'],
                'source_id' => $mainFormRequest['source_id'],
                'selected_role_id' => $mainFormRequest['selected_role_id'],
                'is_archive' => 0,
                'is_archive_gc' => 0
            ]);

            if ($this->isFieldNotEmpty($mainFormRequest, 'n_form_remarks')) {
                $nForm->update([
                    'remarks' => $mainFormRequest['n_form_remarks'],
                ]);
            }

            $createdNFormId = $nForm->n_forms_id;

            /* Авиапредприятие */
            if ($this->requestHasNotEmptyArray($mainFormRequest, 'airline')) {
                $nFormAirlineRequest = $mainFormRequest['airline'];

                $nFormAirline = NFormAirline::create([
                    'n_forms_id' => $createdNFormId,
                    'AIRLINES_ID' => $nFormAirlineRequest['AIRLINES_ID'],
                    'STATES_ID' => $nFormAirlineRequest['STATES_ID'],
                    'ano_is_paid' => $nFormAirlineRequest['ano_is_paid'],
                    'AIRLINE_ICAO' => $nFormAirlineRequest['airline_icao'],
                    'airline_namerus' => $nFormAirlineRequest['airline_namerus'],
                    'airline_namelat' => $nFormAirlineRequest['airline_namelat'],
                    'akvs_airlines_id' => $nFormAirlineRequest['akvs_airlines_id'] ?? null,
                    'airline_lock' => $nFormAirlineRequest['airline_lock'] ?? null,
                ]);

                //Проверяем, есть ли ключ с airlines_id в пришедшем json
                if (array_key_exists('AIRLINES_ID', $nFormAirlineRequest)) {
                    $specifiedAirlineCount = Airline::where('AIRLINES_ID', $nFormAirlineRequest['AIRLINES_ID'])->count();

                    if ($specifiedAirlineCount > 0) {
                        Airline::find($nFormAirlineRequest['AIRLINES_ID'])->touch();
                    }
                }

                /* Документы для информации о рейсе */
                if ($this->requestHasNotEmptyArray($nFormAirlineRequest, 'documents')) {
                    foreach ($nFormAirlineRequest['documents'] as $document) {
                        $airlineFileId = $this->saveFile($document);

                        DB::table('file_n_form_airlines')->insert([
                            'file_id' => $airlineFileId,
                            'n_form_airlines_id' => $nFormAirline->n_form_airlines_id,
                        ]);
                    }
                }

                /* Уполномоченный представитель (руководитель) авиапредприятия */
                if ($this->requestHasNotEmptyArray($nFormAirlineRequest, 'airline_represent')) {
                    $airlineRepresentRequest = $nFormAirlineRequest['airline_represent'];

                    $airlineRepresent = PersonInfo::create([
                        'fio' => $airlineRepresentRequest['fio'],
                        'position' => $airlineRepresentRequest['position'],
                        'email' => $airlineRepresentRequest['email'],
                        'tel' => $airlineRepresentRequest['tel'],
                        'fax' => $airlineRepresentRequest['fax'],
                        'sita' => $airlineRepresentRequest['sita'],
                        'aftn' => $airlineRepresentRequest['aftn'],
                    ]);
                    //Добавляем руководителя
                    $nFormAirline->update([
                        'airline_represent_id' => $airlineRepresent->person_info_id,
                    ]);
                }

                /* Штатный или назначенный представитель в России */
                if ($this->requestHasNotEmptyArray($nFormAirlineRequest, 'russia_represent')) {
                    $russiaRepresentRequest = $nFormAirlineRequest['russia_represent'];

                    $russiaRepresent = PersonInfo::create([
                        'fio' => $russiaRepresentRequest['fio'],
                        'email' => $russiaRepresentRequest['email'],
                        'tel' => $russiaRepresentRequest['tel'],
                        'fax' => $russiaRepresentRequest['fax'],
                        'sita' => $russiaRepresentRequest['sita'],
                        'aftn' => $russiaRepresentRequest['aftn'],
                    ]);
                    //Добавляем представителя в России
                    $nFormAirline->update([
                        'russia_represent_id' => $russiaRepresent->person_info_id,
                    ]);
                }
            }

            /* Переборка всех судов */
            if ($this->requestHasNotEmptyArray($mainFormRequest, 'aircrafts')) {
                foreach ($mainFormRequest['aircrafts'] as $aircraft) {
                    $cruisingSpeed = null;
                    $type = null;
                    $typeModel = null;
                    $tacftType = null;
                    $passengerCount = null;

                    if ($this->isFieldNotEmpty($aircraft, 'CRUISINGSPEED')) $cruisingSpeed = $aircraft['CRUISINGSPEED'];
                    if ($this->isFieldNotEmpty($aircraft, 'type')) $type = $aircraft['type'];
                    if ($this->isFieldNotEmpty($aircraft, 'type_model')) $typeModel = $aircraft['type_model'];
                    if ($this->isFieldNotEmpty($aircraft, 'tacft_type')) $tacftType = $aircraft['tacft_type'];
                    if ($this->isFieldNotEmpty($aircraft, 'passenger_count')) $passengerCount = $aircraft['passenger_count'];

                    /* ВС */
                    $nFormAircraft = NFormAircraft::create([
                        'n_forms_id' => $createdNFormId,
                        'is_main' => $aircraft['is_main'],
                        'FLEET_ID' => $aircraft['FLEET_ID'],
                        'registration_number' => $aircraft['regno'],
                        'aircraft_type_icao' => $type,
                        'aircraft_model' => $typeModel,
                        'tacft_type' => $tacftType,
                        'CRUISINGSPEED' => $cruisingSpeed,
                        'passenger_count' => $passengerCount,
                        'akvs_fleet_id' => $aircraft['akvs_fleet_id'] ?? null,
                        'fleet_lock' => $aircraft['akvs_fleet_id'] ?? null,
                        'max_takeoff_weight' => $aircraft['parameters']['max_takeoff_weight'],
                        'max_landing_weight' => $aircraft['parameters']['max_landing_weight'],
                        'empty_equip_weight' => $aircraft['parameters']['empty_equip_weight'],
                    ]);

                    //set global id for each aircraft
                    $nFormAircraft->update([
                        'n_form_aircrafts_global_id' => $nFormAircraft->n_form_aircrafts_id
                    ]);

                    /* Документы ВС */
                    if ($this->requestHasNotEmptyArray($aircraft, 'documents')) {
                        foreach ($aircraft['documents'] as $document) {
                            $aircraftFileId = $this->saveFile($document);

                            DB::table('file_n_form_aircrafts')->insert([
                                'file_id' => $aircraftFileId,
                                'n_form_aircrafts_id' => $nFormAircraft->n_form_aircrafts_id,
                            ]);
                        }
                    }
                    /* Владелец ВС */
                    if ($this->requestHasNotEmptyArray($aircraft, 'aircraft_owner')) {
                        $aircraftOwnerRequest = $aircraft['aircraft_owner'];

                        $nFormAircraftOwner = NFormAircraftOwner::create([
                            'name' => $aircraftOwnerRequest['name'],
                            'full_address' => $aircraftOwnerRequest['full_address'],
                            'contact' => $aircraftOwnerRequest['contact'],
                            'STATES_ID' => $aircraftOwnerRequest['STATES_ID'],
                        ]);
                        //Обновляем, добавляем в модель запись о владельце
                        $nFormAircraft->update([
                            'n_form_aircraft_owner_id' => $nFormAircraftOwner->n_form_aircraft_owner_id,
                        ]);

                        if ($this->requestHasNotEmptyArray($aircraftOwnerRequest, 'documents')) {
                            foreach ($aircraftOwnerRequest['documents'] as $document) {
                                $aircraftOwnerFileId = $this->saveFile($document);

                                DB::table('file_n_form_aircraft_owner')->insert([
                                    'file_id' => $aircraftOwnerFileId,
                                    'n_form_aircraft_owner_id' => $nFormAircraftOwner->n_form_aircraft_owner_id,
                                ]);
                            }
                        }
                    }
                }
            }

            /* Маршрут */
            if (array_key_exists('flights', $mainFormRequest)) {
                foreach ($mainFormRequest['flights'] as $flight) {
                    //Проверяем, заполнен ли маршрут
                    if ($this->requestHasNotEmptyArray($flight, 'flight_information')) {
                        $flightInformationRequest = $flight['flight_information'];
                        $departurePlatformName = null;
                        $landingPlatformName = null;

                        if ($this->isFieldNotEmpty($flightInformationRequest, 'departure_platform_name')) {
                            $departurePlatformName = $flightInformationRequest['departure_platform_name'];
                        }

                        if ($this->isFieldNotEmpty($flightInformationRequest, 'landing_platform_name')) {
                            $landingPlatformName = $flightInformationRequest['landing_platform_name'];
                        }

                        $nFormFlight = NFormFlight::create([
                            'n_forms_id' => $createdNFormId,
                            'dates_is_repeat' => $flight['dates_is_repeat'],
                            'dates_or_periods' => $flight['dates_or_periods'],
                            'flight_num' => $flightInformationRequest['flight_num'],
                            'purpose' => $flightInformationRequest['purpose_is_commercial'],
                            'transportation_categories_id' => $flightInformationRequest['transportation_categories_id'],
                            'departure_airport_id' => $flightInformationRequest['departure_airport_id'],
                            'is_found_departure_airport' => $flightInformationRequest['is_found_departure_airport'],
                            'departure_airport_icao' => $flightInformationRequest['departure_airport_icao'],
                            'departure_airport_namelat' => $flightInformationRequest['departure_airport_namelat'],
                            'departure_airport_namerus' => $flightInformationRequest['departure_airport_namerus'],
                            'departure_platform_name' => $departurePlatformName,
                            'departure_platform_coordinates' => $flightInformationRequest['departure_platform_coordinates'],
                            'departure_time' => $flightInformationRequest['departure_time'],
                            'landing_airport_id' => $flightInformationRequest['landing_airport_id'],
                            'is_found_landing_airport' => $flightInformationRequest['is_found_landing_airport'],
                            'landing_airport_icao' => $flightInformationRequest['landing_airport_icao'],
                            'landing_airport_namelat' => $flightInformationRequest['landing_airport_namelat'],
                            'landing_airport_namerus' => $flightInformationRequest['landing_airport_namerus'],
                            'landing_platform_name' => $landingPlatformName,
                            'landing_platform_coordinates' => $flightInformationRequest['landing_platform_coordinates'],
                            'landing_time' => $flightInformationRequest['landing_time'],
                            'landing_type' => $flightInformationRequest['landing_type'],
                            'status_id' => 1,
                            "status_change_datetime" => now()->toDateTimeString(),
                            'update_datetime' => now('MSK'),
                        ]);

                        //set global id for each flight
                        $nFormFlight->update([
                            'n_form_flight_global_id' => $nFormFlight->n_form_flight_id
                        ]);
                    }

                    /* Точки */
                    if ($this->requestHasNotEmptyArray($flight, 'points')) {
                        foreach ($flight['points'] as $point) {
                            $time = null;

                            if ($this->isFieldNotEmpty($point, 'time')) {
                                if ($point['time'] !== '') {
                                    $time = $point['time'];
                                }
                            }

                            $nFormPoint = NFormPoint::create([
                                'n_form_flight_id' => $nFormFlight->n_form_flight_id ?? 0,
                                'is_found_point' => $point['is_found_point'],
                                'POINTS_ID' => $point['POINTS_ID'],
                                'is_rf_border' => $point['ISGATEWAY'],
                                'ISINOUT' => $point['ISINOUT'],
                                'icao' => $point['icao'],
                                'time' => $time,
                                'coordinates' => $point['coordinates'],
                                'name' => $point['name'],
                                'is_coordinates' => $point['is_coordinates'],
                                'departure_time_error' => $point['departure_time_error'],
                                'landing_time_error' => $point['landing_time_error'],
                            ]);
                            //Проверка на альтернативные точки
                            if ($this->requestHasNotEmptyArray($point, 'alt_points')) {
                                foreach ($point['alt_points'] as $alt_point) {
                                    NFormAlternativePoint::create([
                                        'n_form_points_id' => $nFormPoint->n_form_points_id,
                                        'POINTS_ID' => $alt_point['POINTS_ID'],
                                        'icao' => $alt_point['icao'],
                                        'name' => $alt_point['name'],
                                        'is_found_point' => $alt_point['is_found_point'],
                                        'is_coordinates' => $alt_point['is_coordinates'],
                                        'ISINOUT' => $alt_point['ISINOUT'],
                                        'ISGATEWAY' => $alt_point['ISGATEWAY'],
                                        'coordinates' => $alt_point['coordinates'],
                                    ]);
                                }
                            }
                        }
                    }
                    /* Основная дата */
                    if ($this->requestHasNotEmptyArray($flight, 'main_date')) {
                        $mainDateRequest = $flight['main_date'];

                        $mainDepartureDate = DepartureDate::create([
                            'n_form_flight_id' => $nFormFlight->n_form_flight_id ?? 0,
                            'is_main_date' => 1,
                            'date' => $mainDateRequest['date'],
                            'is_required_dep_slot' => $mainDateRequest['is_required_dep_slot'],
                            'dep_slot_id' => $mainDateRequest['dep_slot_id'],
                            'is_required_land_slot' => $mainDateRequest['is_required_land_slot'],
                            'land_slot_id' => $mainDateRequest['land_slot_id'],
                            'landing_date' => $mainDateRequest['landing_date'],
                        ]);
                        /* Документы для основной даты */
                        if ($this->requestHasNotEmptyArray($mainDateRequest, 'documents')) {
                            foreach ($mainDateRequest['documents'] as $document) {
                                $fileId = $this->saveFile($document);

                                DB::table('file_departure_dates')->insert([
                                    'file_id' => $fileId,
                                    'departure_dates_id' => $mainDepartureDate->departure_dates_id,
                                ]);
                            }
                        }
                    }
                    /* Повторы датами */
                    if ($this->requestHasNotEmptyArray($flight, 'other_dates')) {
                        foreach ($flight['other_dates'] as $otherDate) {
                            DepartureDate::create([
                                'n_form_flight_id' => $nFormFlight->n_form_flight_id ?? 0,
                                'is_main_date' => 0,
                                'date' => $otherDate['date'],
                                'is_required_dep_slot' => $otherDate['is_required_dep_slot'],
                                'dep_slot_id' => $otherDate['dep_slot_id'],
                                'is_required_land_slot' => $otherDate['is_required_land_slot'],
                                'land_slot_id' => $otherDate['land_slot_id'],
                                'landing_date' => $otherDate['landing_date'],
                                'from_period' => 0,
                                'period_date_id' => 0,
                            ]);
                        }
                    }
                    /* Документы для дат повторами */
                    if ($this->requestHasNotEmptyArray($flight, 'dates_documents')) {
                        foreach ($flight['dates_documents'] as $document) {
                            $fileId = $this->saveFile($document);

                            DB::table('file_n_form_flight')->insert([
                                'file_id' => $fileId,
                                'n_form_flight_id' => $nFormFlight->n_form_flight_id ?? 0,
                            ]);
                        }
                    }
                    /* Повторы периодами */
                    if ($this->requestHasNotEmptyArray($flight, 'period_dates')) {
                        foreach ($flight['period_dates'] as $periodDate) {
                            $startDate = Carbon::parse($periodDate['start_date']);
                            $endDate = Carbon::parse($periodDate['end_date']);
                            $requiredDates = [];

                            $daysOfWeekObjects = null;

                            if (array_key_exists('days_of_week_objects', $periodDate)) {
                                $daysOfWeekObjects = $periodDate['days_of_week_objects'];

                                if (is_array($daysOfWeekObjects)) {
                                    $daysOfWeekObjects = json_encode($daysOfWeekObjects, JSON_UNESCAPED_UNICODE);
                                }
                            }

                            $periodDates = PeriodDate::create([
                                'n_form_flight_id' => $nFormFlight->n_form_flight_id ?? 0,
                                'start_date' => $periodDate['start_date'],
                                'end_date' => $periodDate['end_date'],
                                'days_of_week' => json_encode($periodDate['days_of_week']),
                                'days_of_week_objects' => $daysOfWeekObjects
                            ]);
                            //Обходим период и собираем выбранные даты
                            while ($startDate->toDateString() !== $endDate->toDateString()) {
                                if (in_array($startDate->dayOfWeek, $periodDate['days_of_week'])) {
                                    $requiredDates[] = $startDate->toDateString();
                                }

                                $startDate->addDay();
                            }
                            //Сохраняем каждую дату
                            foreach ($requiredDates as $requiredDate) {
                                DepartureDate::create([
                                    'n_form_flight_id' => $nFormFlight->n_form_flight_id ?? 0,
                                    'is_main_date' => 0,
                                    'date' => $requiredDate,
                                    'from_period' => 1,
                                    'period_date_id' => $periodDates->period_date_id,
                                ]);
                            }
                            /* Документы для периодов дат */
                            if ($this->requestHasNotEmptyArray($periodDate, 'documents')) {
                                foreach ($periodDate['documents'] as $document) {
                                    $fileId = $this->saveFile($document);

                                    DB::table('file_n_form_period_dates')->insert([
                                        'file_id' => $fileId,
                                        'period_date_id' => $periodDates->period_date_id,
                                    ]);
                                }
                            }
                        }
                    }
                    /* Экипаж */
                    if ($this->requestHasNotEmptyArray($flight, 'crew')) {
                        $isFPL = $flight['crew']['is_fpl'];

                        if ($isFPL == 0) {
                            $nFormCrew = NFormCrew::create([
                                'n_form_flight_id' => $nFormFlight->n_form_flight_id ?? 0,
                                'is_fpl' => 0,
                            ]);

                            if ($this->requestHasNotEmptyArray($flight['crew'], 'crew_members')) {
                                foreach ($flight['crew']['crew_members'] as $crewMember) {
                                    $nFormCrewMember = NFormCrewMember::create([
                                        'n_form_crew_id' => $nFormCrew->n_form_crew_id,
                                        'fio' => $crewMember['fio'],
                                        'STATES_ID' => $crewMember['state']['STATES_ID'],
                                    ]);
                                    /* Документы для членов экипажа */
                                    if ($this->requestHasNotEmptyArray($crewMember, 'documents')) {
                                        foreach ($crewMember['documents'] as $document) {
                                            $fileId = $this->saveFile($document);

                                            DB::table('file_n_form_crew_member')->insert([
                                                'file_id' => $fileId,
                                                'n_form_crew_member_id' => $nFormCrewMember->n_form_crew_member_id,
                                            ]);
                                        }
                                    }
                                }
                            }
                        }

                        if ($isFPL == 1) {
                            $nFormCrew = NFormCrew::create([
                                'n_form_flight_id' => $nFormFlight->n_form_flight_id ?? 0,
                                'is_fpl' => 1,
                            ]);

                            if ($this->requestHasNotEmptyArray($flight['crew'], 'crew_groups')) {
                                foreach ($flight['crew']['crew_groups'] as $crewGroup) {
                                    NFormCrewGroup::create([
                                        'n_form_crew_id' => $nFormCrew->n_form_crew_id,
                                        'quantity' => $crewGroup['quantity'],
                                        'STATES_ID' => $crewGroup['state']['STATES_ID'],
                                    ]);
                                }
                            }
                        }
                    }
                    /* Пассажиры */
                    if ($this->requestHasNotEmptyArray($flight, 'passengers')) {
                        $nFormPassenger = NFormPassenger::create([
                            'n_form_flight_id' => $nFormFlight->n_form_flight_id ?? 0,
                        ]);

                        foreach ($flight['passengers']['passengers_persons'] as $passengerPerson) {
                            $nFormPassengerPerson = NFormPassengersPerson::create([
                                'n_form_passengers_id' => $nFormPassenger->n_form_passengers_id,
                                'fio' => $passengerPerson['fio'],
                                'STATES_ID' => $passengerPerson['state']['STATES_ID'],
                            ]);
                            /* Документы для пассажиров */
                            if ($this->requestHasNotEmptyArray($passengerPerson, 'documents')) {
                                foreach ($passengerPerson['documents'] as $document) {
                                    $fileId = $this->saveFile($document);

                                    DB::table('file_n_form_passengers_persons')->insert([
                                        'file_id' => $fileId,
                                        'n_form_passengers_persons_id' => $nFormPassengerPerson->n_form_passengers_persons_id,
                                    ]);
                                }
                            }
                        }

                        $passengersQuantity = 0;

                        if ($this->isFieldNotEmpty($flight['passengers'], 'quantity')) {
                            $passengersQuantity = $flight['passengers']['quantity'];
                        } elseif ($this->requestHasNotEmptyArray($flight['passengers'], 'passengers_persons')) {
                            $passengersQuantity = count($flight['passengers']['passengers_persons']);
                        }

                        $nFormPassenger->update([
                            'quantity' => $passengersQuantity
                        ]);

                    }
                    /* Груз */
                    if ($this->requestHasNotEmptyArray($flight, 'cargos')) {
                        foreach ($flight['cargos'] as $cargo) {
                            $nFormCargo = NFormCargo::create([
                                'n_form_flight_id' => $nFormFlight->n_form_flight_id ?? 0,
                                'type_and_characteristics' => $cargo['type_and_characteristics'],
                                'cargo_danger_classes_id' => $cargo['cargo_danger_classes_id'],
                                'weight' => $cargo['weight'],
                                'charterer_name' => $cargo['cargo_charterer'],
                                'charterer_fulladdress' => $cargo['cargo_charterer_fulladdress'],
                                'charterer_contact' => $cargo['cargo_charterer_phone'],
                                'receiving_party_name' => $cargo['receiving_party'],
                                'receiving_party_fulladdress' => $cargo['receiving_party_fulladdress'],
                                'receiving_party_contact' => $cargo['receiving_party_phone'],
                                'consignor_name' => $cargo['consignor'],
                                'consignor_fulladdress' => $cargo['consignor_fulladdress'],
                                'consignor_contact' => $cargo['consignor_phone'],
                                'consignee_name' => $cargo['consignee'],
                                'consignee_fulladdress' => $cargo['consignee_fulladdress'],
                                'consignee_contact' => $cargo['consignee_phone'],
                            ]);

                            //set global id for each cargo
                            $nFormCargo->update([
                                'n_form_cargo_global_id' => $nFormCargo->n_form_cargo_id
                            ]);

                            /* Документы для груза */
                            if ($this->requestHasNotEmptyArray($cargo, 'documents')) {
                                foreach ($cargo['documents'] as $document) {
                                    $fileId = $this->saveFile($document);

                                    DB::table('file_n_form_cargo')->insert([
                                        'file_id' => $fileId,
                                        'n_form_cargo_id' => $nFormCargo->n_form_cargo_id,
                                    ]);
                                }
                            }
                        }
                    }
                }

            }

            /* Лицо, оплачивающее аэронавигационные сборы */
            if ($this->isFieldNotEmpty($mainFormRequest, 'airnav_payer')) {
                $airnavPayerRequest = $mainFormRequest['airnav_payer'];

                NFormAirnavPayer::create([
                    'n_forms_id' => $createdNFormId,
                    'contact_person' => $airnavPayerRequest['contact_person'],
                    'fio' => $airnavPayerRequest['fio'],
                    'organization' => $airnavPayerRequest['organization'],
                    'tel' => $airnavPayerRequest['tel'],
                    'email' => $airnavPayerRequest['email'],
                    'aftn' => $airnavPayerRequest['aftn'],
                    'address' => $airnavPayerRequest['address'],
                    'remarks' => $airnavPayerRequest['remarks'],
                ]);
            }

            /* Прочие документы */
            if ($this->requestHasNotEmptyArray($mainFormRequest, 'documents')) {
                foreach ($mainFormRequest['documents'] as $document) {
                    $fileId = $this->saveFile($document);

                    DB::table('file_n_form')->insert([
                        'file_id' => $fileId,
                        'n_forms_id' => $createdNFormId,
                    ]);
                }
            }
        });

        return response()->json(
            [
                'message' => "Form $nForm->n_forms_id was successfully created",
                'n_forms_id' => $nForm->n_forms_id,
            ], 201, $this->headers);
    }

    /**
     * Get a single Form N from database
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function get(Request $request): JsonResponse
    {
        if ($request->exists('id_pakus')) {
            //Если в запросе приходят id_pakus и version, то делаем запрос в бд по обоим параметрам
            if ($request->exists('version')) {
                $nForm = NForm::where([
                    ['id_pakus', '=', $request->get('id_pakus')],
                    ['version', '=', $request->get('version')]
                ]);
            } else {
                //если не приходит version, то делаем запрос только по id_pakus; получаем последнюю форму
                $nForm = NForm::where([
                    ['id_pakus', '=', $request->get('id_pakus')],
                    ['is_latest', '=', 1]
                ]);
            }
        } else {
            //Чтобы ничего не поломалось, оставим возможность запроса по id
            $nForm = NForm::where('n_forms_id', $request->get('id'));
        }

        //Пишем id через отдельные запросы, чтобы не модифицировать существующий QueryBuilder,
        // т.к. получить из него эти данные мы на этом этапе не можем
        $authorId = null;
        $version = null;

        if ($nForm->count() == 0) {
            return response()->json(['message' => 'Form N not found'], 404, $this->headers);
        }

        if ($nForm->count() > 0) {
            //В случае, если обращение идёт через запрос по id формы
            if ($request->exists('id')) {
                $tempForm = NForm::where([
                    ['n_forms_id', '=', $request->get('id')]
                ])->first();

                $authorId = $tempForm->author_id;
                $version = $tempForm->version;
            }

            //В случае, если обращение идёт через запрос по id_pakus
            if ($request->exists('id_pakus')) {
                $tempForm = NForm::where([
                    ['id_pakus', '=', $request->get('id_pakus')],
                    ['is_latest', '=', 1]
                ])->first();

                $authorId = $tempForm->author_id;

                if ($request->exists('version')) {
                    $version = $request->get('version');
                } else {
                    $version = $tempForm->version;
                }
            }
        }

        $nForm
            ->select([
                'n_forms_id',
                'id_pakus',
                'version',
                'is_latest',
                'permit_num',
                'author_id',
                'source_id',
                'comment',
                'remarks as n_form_remarks',
                'is_entire_fleet',
                'taken_by_id',
                'take_time',
                'selected_role_id',
                'original_created_at',
                'created_at'
            ])
            ->with('airline', function ($query) {
                $query->select([
                    'n_forms_id',
                    'n_form_airlines_id',
                    'AIRLINES_ID',
                    'STATES_ID',
                    'AIRLINE_ICAO as airline_icao',
                    'airline_namelat',
                    'airline_namerus',
                    'ano_is_paid',
                    'airline_represent_id',
                    'russia_represent_id',
                    'akvs_airlines_id',
                    'airline_lock'
                ]);
            })
            ->with('aircrafts', function ($query) {
                $query->select([
                    'n_form_aircrafts_id',
                    'n_forms_id',
                    'n_form_aircraft_owner_id',
                    'FLEET_ID',
                    'is_main',
                    'registration_number as regno',
                    'aircraft_type_icao as type',
                    'aircraft_model as type_model',
                    'tacft_type',
                    'n_form_aircrafts_global_id',
                    'CRUISINGSPEED',
                    'passenger_count',
                    'akvs_fleet_id',
                    'fleet_lock'
                ]);
            })
            ->with('flights', function ($query) {
                $query
                    ->select([
                        'n_forms_id',
                        'n_form_flight_id',
                        'status_id',
                        'dates_is_repeat',
                        'dates_or_periods',
                        'n_form_flight_global_id'
                    ])
                    ->with('agreementSigns', function ($query) {
                        $query->with('sign');
                    });
            })
            ->with('airnavPayer', function ($query) {
                $query->select([
                    'n_form_airnav_payer_id',
                    'n_forms_id',
                    'contact_person',
                    'fio',
                    'organization',
                    'tel',
                    'email',
                    'aftn',
                    'address',
                    'remarks',
                ]);
            })
            ->with('documents')
            ->with('comments', function ($query) use ($version, $authorId) {
                $query
                    ->where(function ($query) use ($version) {
                        $query
                            ->where('create_at_version', '<=', $version)
                            ->where(function ($q) use ($version) {
                                $q
                                    ->where('delete_at_version', '>', $version)
                                    ->orWhere('delete_at_version', '=', 0);
                            });
                    })
                    ->where(function ($query) use ($authorId) {
                        $approvalGroups = $this->getApprovalGroupsIdsFormUser();

                        //Если пользователь является заявителем
                        if (Auth::id() === $authorId) {
                            $query->where([
                                ['comment_type_id', '!=', 4],
                                ['comment_type_id', '!=', 3]
                            ]);
                            //если у заявителя нет роли ГЦ
                        } elseif (!in_array(2, $approvalGroups)) {
                            $query->where('comment_type_id', '!=', 3);
                        }
                    })
                    ->select([
                        'n_forms_id as n_form_id',
                        'id_pakus',
                        'n_form_comment_id', //нужно оригинальное поле для связи
                        'n_form_comment_id as comment_id',
                        'parent_comment_id',
                        'n_form_object_type as object_type',
                        'n_form_object_id as object_id',
                        'created_at',
                        'comment_type_id',
                        'text as comment_text',
                        'user_id',
                        'create_at_version',
                        'delete_at_version'
                    ])
                    ->with('author', function ($query) {
                        $query
                            ->select([
                                'id',
                                'id as author_id',
                                'name as first_name',
                                'surname as last_name'
                            ])
                            ->with('roles');
                    })
                    ->with('documents', function ($query) {
                        $query->select([
                            'id as document_id',
                            'file_type_id',
                            'file_type_name',
                            'filename as file_name',
                            'path as file_path',
                            'created_at',
                            'other_attributes_json',
                        ]);
                    })
                    ->with('childComments');
            });

        if (Auth::id() !== null) {
            //Если пользователь является администратором
            if (in_array($this->getAdminId(), $this->getRolesIdsFromUser())) {
                $nForm->with('flights', function ($query) {
                    $query
                        ->select([
                            'n_forms_id',
                            'n_form_flight_id',
                            'status_id',
                            'dates_is_repeat',
                            'dates_or_periods',
                            'n_form_flight_global_id',
                        ])
                        ->with('agreementSigns', function ($query) {
                            $query->with('sign');
                        });
                });
            } //Если пользователь является заявителем
            elseif ($authorId === Auth::id()) {
                $nForm->with('flights', function ($query) {
                    $query
                        ->select([
                            'n_forms_id',
                            'n_form_flight_id',
                            'status_id',
                            'dates_is_repeat',
                            'dates_or_periods',
                            'n_form_flight_global_id',
                        ])
//                        ->whereHas('agreementSigns', function ($query) {
//                            $query->whereIn('role_id', $this->getAllRolesIdsFromConditionalGroup());
//                        })
                        ->with('agreementSigns', function ($query) {
                            $query
                                ->whereIn('role_id', $this->getAllRolesIdsFromConditionalGroup())
                                ->with('sign');
                        });
                });
            } //Если пользователь не является заявителем, но у него есть соответствующая роль
            elseif ($authorId !== Auth::id()) {
                $nForm->with('flights', function ($relQuery) {
                    $relQuery
                        ->select([
                            'n_forms_id',
                            'n_form_flight_id',
                            'status_id',
                            'dates_is_repeat',
                            'dates_or_periods',
                            'n_form_flight_global_id',
                        ]);

                    //Если пользователь состоит в условной группе, то показываем ему только его знак
                    if (in_array($this->getConditionalGroupId(), $this->getApprovalGroupsIdsFormUser())) {
                        $relQuery
                            ->whereHas('agreementSigns', function ($query) {
                                $query->whereIn('role_id', $this->getRolesIdsFromUser());
                            })
                            ->with('agreementSigns', function ($query) {
                                $query
                                    ->whereIn('role_id', $this->getRolesIdsFromUser())
                                    ->with('sign');
                            });
                    } else {
                        //Если пользователь не состоит в условной группе, то показываем ему все знаки
                        $relQuery
                            ->whereHas('agreementSigns', function ($query) {
                                $query->whereIn('role_id', $this->getRolesIdsFromUser());
                            })
                            ->with('agreementSigns', function ($query) {
                                $query->with('sign');
                            });
                    }
                });
            }
        }

        $nForm = $nForm->first();

        if ($nForm !== null) {
            if ($nForm->author_id !== Auth::id()) {
                if ($nForm->flights->count() == 0) {
                    return response()->json(['message' => 'Access denied'], 401, $this->headers);
                }
            }
        }

        if ($nForm !== null) {
            $takeDateTime = Carbon::now('Europe/Moscow')->addMinutes(10);
            $currantDateTime = Carbon::now('Europe/Moscow')->toDateTimeString();
            $userData = null;

            if (isset($nForm->taken_by_id) && isset($nForm->author_id)) $userData = $this->getUserData($nForm->taken_by_id, $nForm->author_id);

            if ($nForm['taken_by_id'] === null) {
                $nForm['taken_by_id'] = $request->get('user_id');
                $nForm['take_time'] = $takeDateTime->toDateTimeString();
                $nForm->save();
            } else {
                $userId = $request->get('user_id');
                if ($nForm['taken_by_id'] != $userId && $nForm['take_time'] > $currantDateTime) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Заявка занята',
                        'user' => $userData
                    ], 200, $this->headers);
                } elseif ($nForm['taken_by_id'] == $userId && $nForm['take_time'] > $currantDateTime) {
                    return response()->json(['NForm' => $nForm], 200, $this->headers);
                } else {
                    $nForm['taken_by_id'] = $request->get('user_id');
                    $nForm['take_time'] = $takeDateTime->toDateTimeString();
                    $nForm->save();
                }
            }
        }

        if ($nForm === null) {
            return response()->json(['message' => 'Access denied'], 401, $this->headers);
        }

        return response()->json(['NForm' => $nForm], 200, $this->headers);
    }

    public function localGetNForm(int $id_pakus): array
    {
        $form = NForm::where([
            ['id_pakus', '=', $id_pakus],
            ['is_latest', '=', 1]
        ]);

        $form
            ->select([
                'n_forms_id',
                'id_pakus',
                'version',
                'is_latest',
                'permit_num',
                'author_id',
                'source_id',
                'comment',
                'remarks as n_form_remarks',
                'is_entire_fleet',
                'taken_by_id',
                'take_time',
                'selected_role_id',
                'original_created_at',
                'is_archive',
                'is_archive_gc'
            ])
            ->with('airline', function ($query) {
                $query->select([
                    'n_forms_id',
                    'n_form_airlines_id',
                    'AIRLINES_ID',
                    'STATES_ID',
                    'AIRLINE_ICAO as airline_icao',
                    'airline_namelat',
                    'airline_namerus',
                    'ano_is_paid',
                    'airline_represent_id',
                    'russia_represent_id',
                    'akvs_airlines_id',
                    'airline_lock'
                ]);
            })
            ->with('aircrafts', function ($query) {
                $query->select([
                    'n_form_aircrafts_id',
                    'n_forms_id',
                    'n_form_aircraft_owner_id',
                    'FLEET_ID',
                    'is_main',
                    'registration_number as regno',
                    'aircraft_type_icao as type',
                    'aircraft_model as type_model',
                    'tacft_type',
                    'n_form_aircrafts_global_id',
                    'CRUISINGSPEED',
                    'passenger_count',
                    'akvs_fleet_id',
                    'fleet_lock'
                ]);
            })
            ->with('flights', function ($query) {
                $query
                    ->select([
                        'n_forms_id',
                        'n_form_flight_id',
                        'status_id',
                        'dates_is_repeat',
                        'dates_or_periods',
                        'n_form_flight_global_id'
                    ])
                    ->with('agreementSigns', function ($query) {
                        $query->with('sign');
                    });
            })
            ->with('airnavPayer', function ($query) {
                $query->select([
                    'n_form_airnav_payer_id',
                    'n_forms_id',
                    'contact_person',
                    'fio',
                    'organization',
                    'tel',
                    'email',
                    'aftn',
                    'address',
                    'remarks',
                ]);
            })
            ->with('documents')
            ->with('comments', function ($query) {
                $query
                    ->select([
                        'n_forms_id as n_form_id',
                        'id_pakus',
                        'n_form_comment_id', //нужно оригинальное поле для связи
                        'n_form_comment_id as comment_id',
                        'parent_comment_id',
                        'n_form_object_type as object_type',
                        'n_form_object_id as object_id',
                        'created_at',
                        'comment_type_id',
                        'text as comment_text',
                        'user_id',
                        'create_at_version',
                        'delete_at_version'
                    ])
                    ->with('author', function ($query) {
                        $query
                            ->select([
                                'id',
                                'id as author_id',
                                'name as first_name',
                                'surname as last_name'
                            ])
                            ->with('roles');
                    })
                    ->with('documents', function ($query) {
                        $query->select([
                            'id as document_id',
                            'file_type_id',
                            'file_type_name',
                            'filename as file_name',
                            'path as file_path',
                            'created_at',
                            'other_attributes_json',
                        ]);
                    })
                    ->with('childComments');
            });

        $form = $form->first();

//        $form->flights->each(function($flight) {
//            $finalRAStatus = $flight->finalRAStatus($flight->n_form_flight_global_id);
//
//            if($finalRAStatus) {
//                $flight->status_id = $finalRAStatus;
//            }
//        });

        return $form->toArray();
    }

    /**
     * @throws Throwable
     */
    public function update(Request $request): JsonResponse
    {
        $NFormAsArray = json_decode($request->getContent(), true);

        $form = $this->updateFromArray($NFormAsArray);

        return response()->json(
            [
                'message' => "Form N {$form['originalForm']->n_forms_id} was updated into {$form['updatedForm']->n_forms_id}",
                'n_forms_id' => $form['updatedForm']->n_forms_id,
                'id_pakus' => $form['updatedForm']->id_pakus,
            ], 201, $this->headers);
    }

    /**
     * Update a single Form N
     *
     * @param $array
     * @param bool $isComingFromFront
     * @return array|JsonResponse
     * @throws Throwable
     */
    public function updateFromArray($array, bool $isComingFromFront = true)
    {
        $mainFormRequest = $array;

        //Форма, из которой нужно брать данные для корректировки
        $originalNForm = NForm::find($mainFormRequest['n_forms_id']);

        //Возвращаем ответ с 403, если выбранная версия заявки не является последней
        if ($originalNForm->is_latest !== 1) {
            $originalNForm = NForm::where('id_pakus', $mainFormRequest['id_pakus'])->where('is_latest', 1)->first();
//            return JsonExceptions::exception(['message' => 'This version of Form N is not latest']);
        }

        if ($isComingFromFront) {
            if ($originalNForm->author_id !== Auth::id()) {
                return JsonExceptions::exception(['message' => 'Access denied, user has no access']);
            }
        }

        //Добавляем переменную, чтобы позже передать её по ссылке в транзакцию и получить id
        $updatedNForm = null;

        DB::transaction(function () use (&$updatedNForm, $originalNForm, $mainFormRequest) {
            //Форма, в которую будем сохранять корректировки
            $updatedNForm = NForm::create([
                'id_pakus' => $originalNForm->id_pakus,
                'version' => ($originalNForm->version) + 1,
                'permit_num' => $originalNForm->permit_num,
                'author_id' => $originalNForm->author_id,
                'source_id' => $originalNForm->source_id,
                'selected_role_id' => $mainFormRequest['selected_role_id'],
                'original_created_at' => $originalNForm->original_created_at,
                'comment' => $mainFormRequest['comment'],
                'is_entire_fleet' => $mainFormRequest['is_entire_fleet'],
                'is_latest' => 1,
                'is_archive' => $originalNForm->is_archive,
                'is_archive_gc' => $originalNForm->is_archive_gc
            ]);

            if ($this->isFieldNotEmpty($mainFormRequest, 'n_form_remarks')) {
                $updatedNForm->update([
                    'remarks' => $mainFormRequest['n_form_remarks'],
                ]);
            }

            /* Авиапредприятие */
            if ($this->requestHasNotEmptyArray($mainFormRequest, 'airline')) {
                $nFormAirlineRequest = $mainFormRequest['airline'];

                /* Уполномоченный представитель (руководитель) авиапредприятия */
                if ($this->isFieldNotEmpty($nFormAirlineRequest, 'airline_represent')) {
                    $airlineRepresentRequest = $nFormAirlineRequest['airline_represent'];

                    $updatedAirlineRepresent = PersonInfo::create([
                        'fio' => $airlineRepresentRequest['fio'],
                        'position' => $airlineRepresentRequest['position'],
                        'email' => $airlineRepresentRequest['email'],
                        'tel' => $airlineRepresentRequest['tel'],
                        'fax' => $airlineRepresentRequest['fax'],
                        'sita' => $airlineRepresentRequest['sita'],
                        'aftn' => $airlineRepresentRequest['aftn'],
                    ]);
                }

                /* Штатный или назначенный представитель в России */
                if ($this->isFieldNotEmpty($nFormAirlineRequest, 'russia_represent')) {
                    $russiaRepresentRequest = $nFormAirlineRequest['russia_represent'];

                    $updatedRussiaRepresent = PersonInfo::create([
                        'fio' => $russiaRepresentRequest['fio'],
                        'email' => $russiaRepresentRequest['email'],
                        'tel' => $russiaRepresentRequest['tel'],
                        'fax' => $russiaRepresentRequest['fax'],
                        'sita' => $russiaRepresentRequest['sita'],
                        'aftn' => $russiaRepresentRequest['aftn'],
                    ]);
                }

                $updatedAirline = NFormAirline::create([
                    'n_forms_id' => $updatedNForm->n_forms_id,
                    'AIRLINES_ID' => $nFormAirlineRequest['AIRLINES_ID'],
                    'STATES_ID' => $nFormAirlineRequest['STATES_ID'],
                    'AIRLINE_ICAO' => $nFormAirlineRequest['airline_icao'],
                    'airline_namerus' => $nFormAirlineRequest['airline_namerus'],
                    'airline_namelat' => $nFormAirlineRequest['airline_namelat'],
                    'airline_represent_id' => $updatedAirlineRepresent->person_info_id ?? 0,
                    'russia_represent_id' => $updatedRussiaRepresent->person_info_id ?? 0,
                    'akvs_airlines_id' => $nFormAirlineRequest['akvs_airlines_id'] ?? null,
                    'airline_lock' => $nFormAirlineRequest['airline_lock'] ?? null,
                ]);

                $airlineExist = Airline::find($nFormAirlineRequest['AIRLINES_ID']);
                if (!is_null($airlineExist)) {
                    Airline::find($nFormAirlineRequest['AIRLINES_ID'])->touch();
                }

                /* Документы для информации о рейсе */
                if ($this->requestHasNotEmptyArray($nFormAirlineRequest, 'documents')) {
                    foreach ($nFormAirlineRequest['documents'] as $document) {
                        $this->saveOrUpdateDocument(
                            $document,
                            $updatedAirline->n_form_airlines_id,
                            'file_n_form_airlines',
                            'n_form_airlines_id'
                        );
                    }
                }
            }

            /* Переборка всех судов */
            if ($this->requestHasNotEmptyArray($mainFormRequest, 'aircrafts')) {
                foreach ($mainFormRequest['aircrafts'] as $aircraftRequest) {
                    /* Владелец ВС */
                    if ($this->requestHasNotEmptyArray($aircraftRequest, 'aircraft_owner')) {
                        $aircraftOwnerRequest = $aircraftRequest['aircraft_owner'];

                        $aircraftOwner = NFormAircraftOwner::create([
                            'name' => $aircraftOwnerRequest['name'],
                            'full_address' => $aircraftOwnerRequest['full_address'],
                            'contact' => $aircraftOwnerRequest['contact'],
                            'STATES_ID' => $aircraftOwnerRequest['STATES_ID'],
                        ]);

                        /* Документы владельца ВС */
                        if ($this->requestHasNotEmptyArray($aircraftOwnerRequest, 'documents')) {
                            foreach ($aircraftOwnerRequest['documents'] as $document) {
                                $this->saveOrUpdateDocument(
                                    $document,
                                    $aircraftOwner->n_form_aircraft_owner_id,
                                    'file_n_form_aircraft_owner',
                                    'n_form_aircraft_owner_id'
                                );
                            }
                        }
                    }

                    $cruisingSpeed = null;
                    $type = null;
                    $typeModel = null;
                    $tacftType = null;
                    $passengerCount = null;

                    if ($this->isFieldNotEmpty($aircraftRequest, 'CRUISINGSPEED')) $cruisingSpeed = $aircraftRequest['CRUISINGSPEED'];
                    if ($this->isFieldNotEmpty($aircraftRequest, 'type')) $type = $aircraftRequest['type'];
                    if ($this->isFieldNotEmpty($aircraftRequest, 'type_model')) $typeModel = $aircraftRequest['type_model'];
                    if ($this->isFieldNotEmpty($aircraftRequest, 'tacft_type')) $tacftType = $aircraftRequest['tacft_type'];
                    if ($this->isFieldNotEmpty($aircraftRequest, 'passenger_count')) $passengerCount = $aircraftRequest['passenger_count'];

                    $aircraft = NFormAircraft::create([
                        'n_forms_id' => $updatedNForm->n_forms_id,
                        'n_form_aircraft_owner_id' => $aircraftOwner->n_form_aircraft_owner_id ?? 0,
                        'is_main' => $aircraftRequest['is_main'],
                        'FLEET_ID' => $aircraftRequest['FLEET_ID'],
                        'registration_number' => $aircraftRequest['regno'],
                        'aircraft_type_icao' => $type,
                        'aircraft_model' => $typeModel,
                        'tacft_type' => $tacftType,
                        'CRUISINGSPEED' => $cruisingSpeed,
                        'passenger_count' => $passengerCount,
                        'akvs_fleet_id' => $aircraftRequest['akvs_fleet_id'] ?? null,
                        'fleet_lock' => $aircraftRequest['fleet_lock'] ?? null,
                        'max_takeoff_weight' => $aircraftRequest['parameters']['max_takeoff_weight'],
                        'max_landing_weight' => $aircraftRequest['parameters']['max_landing_weight'],
                        'empty_equip_weight' => $aircraftRequest['parameters']['empty_equip_weight'],
                    ]);

                    //set global id for each aircraft
                    if (array_key_exists('n_form_aircrafts_id', $aircraftRequest) && $aircraftRequest['n_form_aircrafts_id'] !== null) {
                        //Если мы обновляем уже существующий ВС, то переносим global id со старого
                        $previousAircraft = NFormAircraft::find($aircraftRequest['n_form_aircrafts_id']);
                        $aircraft->update([
                            'n_form_aircrafts_global_id' => $previousAircraft->n_form_aircrafts_global_id
                        ]);
                    } else {
                        //Если при редактировании создаётся новый ВС, то пишем туда новый global id
                        $aircraft->update([
                            'n_form_aircrafts_global_id' => $aircraft->n_form_aircrafts_id
                        ]);
                    }

                    /* Документы ВС */
                    if ($this->requestHasNotEmptyArray($aircraftRequest, 'documents')) {
                        foreach ($aircraftRequest['documents'] as $document) {
                            $this->saveOrUpdateDocument(
                                $document,
                                $aircraft->n_form_aircrafts_id,
                                'file_n_form_aircrafts',
                                'n_form_aircrafts_id'
                            );
                        }
                    }

                }
            }

            $flightsArray = [];

            /* Маршрут */
            if (array_key_exists('flights', $mainFormRequest)) {
                foreach ($mainFormRequest['flights'] as $flightRequest) {
                    //Проверяем, заполнен ли маршрут
                    if ($this->requestHasNotEmptyArray($flightRequest, 'flight_information')) {
                        $flightInformationRequest = $flightRequest['flight_information'];
                        $departurePlatformName = null;
                        $landingPlatformName = null;

                        if ($this->isFieldNotEmpty($flightInformationRequest, 'departure_platform_name')) {
                            $departurePlatformName = $flightInformationRequest['departure_platform_name'];
                        }

                        if ($this->isFieldNotEmpty($flightInformationRequest, 'landing_platform_name')) {
                            $landingPlatformName = $flightInformationRequest['landing_platform_name'];
                        }

                        $flight = NFormFlight::create([
                            'n_forms_id' => $updatedNForm->n_forms_id,
                            'dates_is_repeat' => $flightRequest['dates_is_repeat'],
                            'dates_or_periods' => $flightRequest['dates_or_periods'],
                            'flight_num' => $flightInformationRequest['flight_num'],
                            'purpose' => $flightInformationRequest['purpose_is_commercial'],
                            'transportation_categories_id' => $flightInformationRequest['transportation_categories_id'],
                            'departure_airport_id' => $flightInformationRequest['departure_airport_id'],
                            'is_found_departure_airport' => $flightInformationRequest['is_found_departure_airport'],
                            'departure_airport_icao' => $flightInformationRequest['departure_airport_icao'],
                            'departure_airport_namelat' => $flightInformationRequest['departure_airport_namelat'],
                            'departure_airport_namerus' => $flightInformationRequest['departure_airport_namerus'],
                            'departure_platform_name' => $departurePlatformName,
                            'departure_platform_coordinates' => $flightInformationRequest['departure_platform_coordinates'],
                            'departure_time' => $flightInformationRequest['departure_time'],
                            'landing_airport_id' => $flightInformationRequest['landing_airport_id'],
                            'is_found_landing_airport' => $flightInformationRequest['is_found_landing_airport'],
                            'landing_airport_icao' => $flightInformationRequest['landing_airport_icao'],
                            'landing_airport_namelat' => $flightInformationRequest['landing_airport_namelat'],
                            'landing_airport_namerus' => $flightInformationRequest['landing_airport_namerus'],
                            'landing_platform_name' => $landingPlatformName,
                            'landing_platform_coordinates' => $flightInformationRequest['landing_platform_coordinates'],
                            'landing_time' => $flightInformationRequest['landing_time'],
                            'landing_type' => $flightInformationRequest['landing_type'],
                            'status_id' => $flightRequest['status_id'],
                            'update_datetime' => now('MSK'),
                        ]);

                        if ($this->isFieldNotEmpty($flightRequest, 'n_form_flight_id')) {
                            $nFormFlight_old = NFormFlight::firstWhere('n_form_flight_id', $flightRequest['n_form_flight_id']);

                            $agreementSigns_old = $nFormFlight_old->agreementSigns;

                            if (!is_null($agreementSigns_old)) {
                                foreach ($agreementSigns_old as $agreementSign) {

                                    NFormFlightAgreementSign::create([
                                        'n_form_flight_id' => $flight->n_form_flight_id,
                                        'role_id' => $agreementSign->role_id,
                                        'approval_group_id' => $agreementSign->approval_group_id,
                                        'n_form_flight_sign_id' => $agreementSign->n_form_flight_sign_id
                                    ]);
                                }
                            }
                        }

                        $flightsArray[] += $flight->n_form_flight_id;

                        //set global id for each flight
                        if (array_key_exists('n_form_flight_id', $flightRequest) && $flightRequest['n_form_flight_id'] !== null) {
                            //Если мы обновляем уже существующий рейс, то переносим global id со старого
                            $previousFlight = NFormFlight::find($flightRequest['n_form_flight_id']);
                            $flight->update([
                                'n_form_flight_global_id' => $previousFlight->n_form_flight_global_id
                            ]);
                        } else {
                            //Если при редактировании создаётся новый рейс, то пишем туда новый global id
                            $flight->update([
                                'n_form_flight_global_id' => $flight->n_form_flight_id
                            ]);
                        }
                    }
                    /* Точки */
                    if ($this->requestHasNotEmptyArray($flightRequest, 'points')) {
                        foreach ($flightRequest['points'] as $point) {
                            $time = null;

                            if ($this->isFieldNotEmpty($point, 'time')) {
                                if ($point['time'] !== '') {
                                    $time = $point['time'];
                                }
                            }

                            $updatedMainPoint = NFormPoint::create([
                                'n_form_flight_id' => $flight->n_form_flight_id ?? 0,
                                'is_found_point' => $point['is_found_point'],
                                'POINTS_ID' => $point['POINTS_ID'],
                                'is_rf_border' => $point['ISGATEWAY'],
                                'ISINOUT' => $point['ISINOUT'],
                                'icao' => $point['icao'],
                                'time' => $time,
                                'coordinates' => $point['coordinates'],
                                'name' => $point['name'],
                                'is_coordinates' => $point['is_coordinates'],
                                'departure_time_error' => $point['departure_time_error'],
                                'landing_time_error' => $point['landing_time_error'],
                            ]);
                            //Проверка на альтернативные точки
                            if ($this->requestHasNotEmptyArray($point, 'alt_points')) {
                                foreach ($point['alt_points'] as $alt_point) {
                                    NFormAlternativePoint::create([
                                        'n_form_points_id' => $updatedMainPoint->n_form_points_id,
                                        'POINTS_ID' => $alt_point['POINTS_ID'],
                                        'icao' => $alt_point['icao'],
                                        'name' => $alt_point['name'],
                                        'is_found_point' => $alt_point['is_found_point'],
                                        'is_coordinates' => $alt_point['is_coordinates'],
                                        'ISINOUT' => $alt_point['ISINOUT'],
                                        'ISGATEWAY' => $alt_point['ISGATEWAY'],
                                        'coordinates' => $alt_point['coordinates'],
                                    ]);
                                }
                            }
                        }
                    }

                    /* Основная дата */
                    if ($this->requestHasNotEmptyArray($flightRequest, 'main_date')) {
                        $mainDateRequest = $flightRequest['main_date'];

                        $mainDate = DepartureDate::create([
                            'n_form_flight_id' => $flight->n_form_flight_id ?? 0,
                            'is_main_date' => 1,
                            'date' => $mainDateRequest['date'],
                            'is_required_dep_slot' => $mainDateRequest['is_required_dep_slot'],
                            'dep_slot_id' => $mainDateRequest['dep_slot_id'],
                            'is_required_land_slot' => $mainDateRequest['is_required_land_slot'],
                            'land_slot_id' => $mainDateRequest['land_slot_id'],
                            'landing_date' => $mainDateRequest['landing_date'],
                        ]);


                        /* Документы для основной даты */
                        if ($this->requestHasNotEmptyArray($mainDateRequest, 'documents')) {
                            foreach ($mainDateRequest['documents'] as $document) {
                                $this->saveOrUpdateDocument(
                                    $document,
                                    $mainDate->departure_dates_id,
                                    'file_departure_dates',
                                    'departure_dates_id'
                                );
                            }
                        }
                    }

                    /* Повторы датами */
                    if ($this->requestHasNotEmptyArray($flightRequest, 'other_dates')) {
                        foreach ($flightRequest['other_dates'] as $otherDate) {
                            DepartureDate::create([
                                'n_form_flight_id' => $flight->n_form_flight_id ?? 0,
                                'is_main_date' => 0,
                                'date' => $otherDate['date'],
                                'is_required_dep_slot' => $otherDate['is_required_dep_slot'],
                                'dep_slot_id' => $otherDate['dep_slot_id'],
                                'is_required_land_slot' => $otherDate['is_required_land_slot'],
                                'land_slot_id' => $otherDate['land_slot_id'],
                                'landing_date' => $otherDate['landing_date'],
                                'from_period' => 0,
                                'period_date_id' => 0,
                            ]);
                        }
                    }

                    /* Документы для дат повторами */
                    if ($this->requestHasNotEmptyArray($flightRequest, 'dates_documents')) {
                        foreach ($flightRequest['dates_documents'] as $document) {
                            $this->saveOrUpdateDocument(
                                $document,
                                $flight->n_form_flight_id ?? 0,
                                'file_n_form_flight',
                                'n_form_flight_id'
                            );
                        }
                    }

                    /* Повторы периодами */
                    if ($this->requestHasNotEmptyArray($flightRequest, 'period_dates')) {
                        foreach ($flightRequest['period_dates'] as $periodDateRequest) {
                            $startDate = Carbon::parse($periodDateRequest['start_date']);
                            $endDate = Carbon::parse($periodDateRequest['end_date']);
                            $requiredDates = [];

                            $daysOfWeekObjects = null;

                            if (array_key_exists('days_of_week_objects', $periodDateRequest)) {
                                $daysOfWeekObjects = $periodDateRequest['days_of_week_objects'];
                            }

                            $periodDates = PeriodDate::create([
                                'n_form_flight_id' => $flight->n_form_flight_id ?? 0,
                                'start_date' => $periodDateRequest['start_date'],
                                'end_date' => $periodDateRequest['end_date'],
                                'days_of_week' => json_encode($periodDateRequest['days_of_week']),
                                'days_of_week_objects' => $daysOfWeekObjects
                            ]);
                            //Обходим период и собираем выбранные даты
                            while ($startDate->toDateString() !== $endDate->toDateString()) {
                                if (in_array($startDate->dayOfWeek, $periodDateRequest['days_of_week'])) {
                                    $requiredDates[] = $startDate->toDateString();
                                }

                                $startDate->addDay();
                            }
                            //Сохраняем каждую дату
                            foreach ($requiredDates as $requiredDate) {
                                DepartureDate::create([
                                    'n_form_flight_id' => $flight->n_form_flight_id ?? 0,
                                    'is_main_date' => 0,
                                    'date' => $requiredDate,
                                    'from_period' => 1,
                                    'period_date_id' => $periodDates->period_date_id,
                                ]);
                            }

                            /* Документы для периодов дат */
                            if ($this->requestHasNotEmptyArray($periodDateRequest, 'documents')) {
                                foreach ($periodDateRequest['documents'] as $document) {
                                    $this->saveOrUpdateDocument(
                                        $document,
                                        $periodDates->period_date_id,
                                        'file_n_form_period_dates',
                                        'period_date_id'
                                    );
                                }
                            }

                        }
                    }

                    /* Экипаж */
                    if ($this->requestHasNotEmptyArray($flightRequest, 'crew')) {
                        $isFPL = $flightRequest['crew']['is_fpl'];

                        if ($isFPL == 0) {
                            $nFormCrew = NFormCrew::create([
                                'n_form_flight_id' => $flight->n_form_flight_id ?? 0,
                                'is_fpl' => 0,
                            ]);

                            if ($this->requestHasNotEmptyArray($flightRequest['crew'], 'crew_members')) {
                                foreach ($flightRequest['crew']['crew_members'] as $crewMember) {
                                    $nFormCrewMember = NFormCrewMember::create([
                                        'n_form_crew_id' => $nFormCrew->n_form_crew_id,
                                        'fio' => $crewMember['fio'],
                                        'STATES_ID' => $crewMember['state']['STATES_ID'],
                                    ]);
                                    /* Документы для членов экипажа */
                                    if ($this->requestHasNotEmptyArray($crewMember, 'documents')) {
                                        foreach ($crewMember['documents'] as $document) {
                                            $this->saveOrUpdateDocument(
                                                $document,
                                                $nFormCrewMember->n_form_crew_member_id,
                                                'file_n_form_crew_member',
                                                'n_form_crew_member_id'
                                            );
                                        }
                                    }
                                }
                            }
                        }

                        if ($isFPL == 1) {
                            $nFormCrew = NFormCrew::create([
                                'n_form_flight_id' => $flight->n_form_flight_id ?? 0,
                                'is_fpl' => 1,
                            ]);

                            if ($this->requestHasNotEmptyArray($flightRequest['crew'], 'crew_groups')) {
                                foreach ($flightRequest['crew']['crew_groups'] as $crewGroup) {
                                    NFormCrewGroup::create([
                                        'n_form_crew_id' => $nFormCrew->n_form_crew_id,
                                        'quantity' => $crewGroup['quantity'],
                                        'STATES_ID' => $crewGroup['state']['STATES_ID'],
                                    ]);
                                }
                            }
                        }
                    }

                    /* Пассажиры */
                    if ($this->requestHasNotEmptyArray($flightRequest, 'passengers')) {
                        $passengersQuantity = 0;

                        //Если категория перевозки поменялась и можно вручную вводить пассажиров
                        if ($this->requestHasNotEmptyArray($flightRequest['passengers'], 'passengers_persons')) {
                            $passengersQuantity = count($flightRequest['passengers']['passengers_persons']);
                        } else {
                            //Если с фронта пришло только количество пассажиров
                            if ($this->isFieldNotEmpty($flightRequest['passengers'], 'quantity')) {
                                //Переносим количество пассажиров со старого рейса
                                if (
                                    isset($nFormFlight_old) &&
                                    $nFormFlight_old->passengers->quantity > $flightRequest['passengers']['quantity'] &&
                                    $flightRequest['passengers']['quantity'] > 0
                                ) {
                                    $passengersQuantity = $nFormFlight_old->passengers->quantity;
                                } else {
                                    $passengersQuantity = $flightRequest['passengers']['quantity'];
                                }
                            }
                        }

                        $updatedPassenger = NFormPassenger::create([
                            'n_form_flight_id' => $flight->n_form_flight_id ?? 0,
                            'quantity' => $passengersQuantity,
                        ]);

                        foreach ($flightRequest['passengers']['passengers_persons'] as $passengerPerson) {
                            $updatedPassengerPerson = NFormPassengersPerson::create([
                                'n_form_passengers_id' => $updatedPassenger->n_form_passengers_id,
                                'fio' => $passengerPerson['fio'],
                                'STATES_ID' => $passengerPerson['state']['STATES_ID'],
                            ]);
                            /* Документы для пассажиров */
                            if ($this->requestHasNotEmptyArray($passengerPerson, 'documents')) {
                                foreach ($passengerPerson['documents'] as $document) {
                                    $this->saveOrUpdateDocument(
                                        $document,
                                        $updatedPassengerPerson->n_form_passengers_persons_id,
                                        'file_n_form_passengers_persons',
                                        'n_form_passengers_persons_id'
                                    );
                                }
                            }
                        }

                    }

                    /* Груз */
                    if ($this->requestHasNotEmptyArray($flightRequest, 'cargos')) {
                        foreach ($flightRequest['cargos'] as $cargo) {
                            $updatedCargo = NFormCargo::create([
                                'n_form_flight_id' => $flight->n_form_flight_id ?? 0,
                                'type_and_characteristics' => $cargo['type_and_characteristics'],
                                'cargo_danger_classes_id' => $cargo['cargo_danger_classes_id'],
                                'weight' => $cargo['weight'],
                                'charterer_name' => $cargo['cargo_charterer'],
                                'charterer_fulladdress' => $cargo['cargo_charterer_fulladdress'],
                                'charterer_contact' => $cargo['cargo_charterer_phone'],
                                'receiving_party_name' => $cargo['receiving_party'],
                                'receiving_party_fulladdress' => $cargo['receiving_party_fulladdress'],
                                'receiving_party_contact' => $cargo['receiving_party_phone'],
                                'consignor_name' => $cargo['consignor'],
                                'consignor_fulladdress' => $cargo['consignor_fulladdress'],
                                'consignor_contact' => $cargo['consignor_phone'],
                                'consignee_name' => $cargo['consignee'],
                                'consignee_fulladdress' => $cargo['consignee_fulladdress'],
                                'consignee_contact' => $cargo['consignee_phone'],
                            ]);

                            //set global id for each cargo
                            if (array_key_exists('n_form_cargo_id', $cargo) && $cargo['n_form_cargo_id'] !== null) {
                                //Если мы обновляем уже существующий груз, то переносим global id со старого
                                $previousCargo = NFormCargo::find($cargo['n_form_cargo_id']);
                                $updatedCargo->update([
                                    'n_form_cargo_global_id' => $previousCargo->n_form_cargo_global_id
                                ]);
                            } else {
                                //Если при редактировании создаётся новый груз, то пишем туда новый global id
                                $updatedCargo->update([
                                    'n_form_cargo_global_id' => $updatedCargo->n_form_cargo_id
                                ]);
                            }

                            /* Документы для груза */
                            if ($this->requestHasNotEmptyArray($cargo, 'documents')) {
                                foreach ($cargo['documents'] as $document) {
                                    $this->saveOrUpdateDocument(
                                        $document,
                                        $updatedCargo->n_form_cargo_id,
                                        'file_n_form_cargo',
                                        'n_form_cargo_id'
                                    );
                                }
                            }
                        }
                    }
                }
            }

            /* Лицо, оплачивающее аэронавигационные сборы */
            if ($this->isFieldNotEmpty($mainFormRequest, 'airnav_payer')) {
                $airnavPayerRequest = $mainFormRequest['airnav_payer'];

                NFormAirnavPayer::create([
                    'n_forms_id' => $updatedNForm->n_forms_id,
                    'contact_person' => $airnavPayerRequest['contact_person'],
                    'fio' => $airnavPayerRequest['fio'],
                    'organization' => $airnavPayerRequest['organization'],
                    'tel' => $airnavPayerRequest['tel'],
                    'email' => $airnavPayerRequest['email'],
                    'aftn' => $airnavPayerRequest['aftn'],
                    'address' => $airnavPayerRequest['address'],
                    'remarks' => $airnavPayerRequest['remarks'],
                ]);

            }

            /* Прочие документы */
            if ($this->requestHasNotEmptyArray($mainFormRequest, 'documents')) {
                foreach ($mainFormRequest['documents'] as $document) {
                    $this->saveOrUpdateDocument(
                        $document,
                        $updatedNForm->n_forms_id,
                        'file_n_form',
                        'n_forms_id'
                    );
                }
            }

            //Меняем флаг is_latest в конце транзакции
            $originalNForm->is_latest = 0;
            //Обновляем только поле, без обновления timestamps
            $originalNForm->save(['timestamps' => false]);
        });

        return [
            'updatedForm' => $updatedNForm,
            'originalForm' => $originalNForm,
        ];
    }

    /**
     * Duplicate a single Form N
     *
     * @param $id
     * @return JsonResponse
     * @throws Throwable
     */
    public function duplicate($id): JsonResponse
    {
        $nForm = NForm::where('n_forms_id', $id);

        if ($nForm->count() == 0) {
            return response()->json(['message' => 'Form N with requested id not found'], 404, $this->headers);
        }

        $nForm->select([
            'n_forms_id',
            'id_pakus',
            'permit_num',
            'author_id',
            'source_id',
            'comment',
            'is_entire_fleet',
            'taken_by_id',
            'take_time',
            'selected_role_id',
            'is_latest',
            'version',
            'remarks',
            'is_archive',
            'is_archive_gc'
        ])
            ->with('airline', function ($query) {
                $query->select([
                    'n_forms_id',
                    'n_form_airlines_id',
                    'AIRLINES_ID',
                    'STATES_ID',
                    'AIRLINE_ICAO as airline_icao',
                    'airline_namelat',
                    'airline_namerus',
                    'ano_is_paid',
                    'airline_represent_id',
                    'russia_represent_id',
                    'akvs_airlines_id',
                    'airline_lock'
                ]);
            })
            ->with('aircrafts', function ($query) {
                $query->select([
                    'n_form_aircrafts_id',
                    'n_forms_id',
                    'n_form_aircraft_owner_id',
                    'FLEET_ID',
                    'is_main',
                    'registration_number as regno',
                    'aircraft_type_icao',
                    'aircraft_model',
                    'tacft_type',
                    'CRUISINGSPEED',
                    'passenger_count',
                    'akvs_fleet_id',
                    'fleet_lock'
                ]);
            })
            ->with('flights', function ($query) {
                $query->select([
                    'n_forms_id',
                    'n_form_flight_id',
                    'status_id',
                    'dates_is_repeat',
                    'dates_or_periods',
                ]);
            })
            ->with('airnavPayer', function ($query) {
                $query->select([
                    'n_form_airnav_payer_id',
                    'n_forms_id',
                    'contact_person',
                    'fio',
                    'organization',
                    'tel',
                    'email',
                    'aftn',
                    'address',
                    'remarks',
                ]);
            })
            ->with('documents');

        $nForm = $nForm->first()->toArray();
        $cloneNForm = null;

        DB::transaction(function () use ($nForm, &$cloneNForm) {
            //Дублируем Форму Н
            $cloneNForm = NForm::create([
                'id_pakus' => time(),
                'permit_num' => 0,
                'author_id' => $nForm['author_id'],
                'source_id' => $nForm['source_id'],
                'comment' => $nForm['comment'],
                'is_entire_fleet' => $nForm['is_entire_fleet'],
                'selected_role_id' => $nForm['selected_role_id'],
                'original_created_at' => now()->toDateTimeString(),
                'is_latest' => 1,
                'version' => 1,
            ]);

            if ($this->isFieldNotEmpty($nForm, 'n_form_remarks')) {
                $cloneNForm->update([
                    'remarks' => $nForm['n_form_remarks'],
                ]);
            }

            //Дублируем файлы для Формы Н
            if ($this->requestHasNotEmptyArray($nForm, 'documents')) {
                foreach ($nForm['documents'] as $document) {
                    DB::table('file_n_form')->insert([
                        'file_id' => $this->duplicateFile($document['document_id']),
                        'n_forms_id' => $cloneNForm->n_forms_id,
                    ]);
                }
            }

            //Дублируем airline
            if ($this->isFieldNotEmpty($nForm, 'airline')) {
                $airlineRequest = $nForm['airline'];

                //Дублируем представителей airline
                if ($this->isFieldNotEmpty($airlineRequest, 'airline_represent')) {
                    $cloneAirlineRepresent = PersonInfo::create($airlineRequest['airline_represent']);
                }
                if ($this->isFieldNotEmpty($airlineRequest, 'russia_represent')) {
                    $cloneRussiaRepresent = PersonInfo::create($airlineRequest['russia_represent']);
                }

                $cloneAirline = NFormAirline::create([
                    'n_forms_id' => $cloneNForm->n_forms_id,
                    "AIRLINES_ID" => $airlineRequest['AIRLINES_ID'],
                    "STATES_ID" => $airlineRequest['STATES_ID'],
                    "AIRLINE_ICAO" => $airlineRequest['airline_icao'],
                    "airline_namelat" => $airlineRequest['airline_namelat'],
                    "airline_namerus" => $airlineRequest['airline_namerus'],
                    "ano_is_paid" => $airlineRequest['ano_is_paid'],
                    "airline_represent_id" => $cloneAirlineRepresent->person_info_id ?? 0,
                    "russia_represent_id" => $cloneRussiaRepresent->person_info_id ?? 0,
                    'akvs_airlines_id' => $airlineRequest['akvs_airlines_id'],
                    'airline_lock' => $airlineRequest['airline_lock']
                ]);

                //Дублируем файлы для airline
                if ($this->requestHasNotEmptyArray($nForm['airline'], 'documents')) {
                    foreach ($nForm['airline']['documents'] as $document) {
                        DB::table('file_n_form_airlines')->insert([
                            'file_id' => $this->duplicateFile($document['document_id']),
                            'n_form_airlines_id' => $cloneAirline->n_form_airlines_id,
                        ]);
                    }
                }
            }

            //Дублируем aircraft
            if ($this->requestHasNotEmptyArray($nForm, 'aircrafts')) {
                foreach ($nForm['aircrafts'] as $aircraft) {
                    //Дублируем aircraft_owner
                    if ($this->isFieldNotEmpty($aircraft, 'aircraft_owner')) {
                        $cloneAircraftOwner = NFormAircraftOwner::create($aircraft['aircraft_owner']);

                        //Дублируем файлы для aircraft_owner
                        if ($this->requestHasNotEmptyArray($aircraft['aircraft_owner'], 'documents')) {
                            foreach ($aircraft['aircraft_owner']['documents'] as $document) {
                                DB::table('file_n_form_aircraft_owner')->insert([
                                    'file_id' => $this->duplicateFile($document['document_id']),
                                    'n_form_aircraft_owner_id' => $cloneAircraftOwner->n_form_aircraft_owner_id,
                                ]);
                            }
                        }
                    }
                    $cruisingSpeed = null;

                    if ($this->isFieldNotEmpty($aircraft, 'CRUISINGSPEED')) {
                        $cruisingSpeed = $aircraft['CRUISINGSPEED'];
                    }

                    $cloneAircraft = NFormAircraft::create([
                        "n_forms_id" => $cloneNForm->n_forms_id,
                        "n_form_aircraft_owner_id" => $cloneAircraftOwner->n_form_aircraft_owner_id ?? 0,
                        "FLEET_ID" => $aircraft['FLEET_ID'],
                        "is_main" => $aircraft['is_main'],
                        "registration_number" => $aircraft['regno'],
                        'aircraft_type_icao' => $aircraft['aircraft_type_icao'],
                        'aircraft_model' => $aircraft['aircraft_model'],
                        'tacft_type' => $aircraft['tacft_type'],
                        'CRUISINGSPEED' => $cruisingSpeed,
                        'passenger_count' => $aircraft['passenger_count'],
                        'akvs_fleet_id' => $aircraft['akvs_fleet_id'],
                        'fleet_lock' => $aircraft['fleet_lock'],
                        "max_takeoff_weight" => $aircraft['parameters']['max_takeoff_weight'],
                        "max_landing_weight" => $aircraft['parameters']['max_landing_weight'],
                        "empty_equip_weight" => $aircraft['parameters']['empty_equip_weight'],
                    ]);

                    //set global id for each aircraft
                    $cloneAircraft->update([
                        'n_form_aircrafts_global_id' => $cloneAircraft->n_form_aircrafts_id
                    ]);

                    //Дублируем файлы для aircraft
                    if ($this->requestHasNotEmptyArray($aircraft, 'documents')) {
                        foreach ($aircraft['documents'] as $document) {
                            DB::table('file_n_form_aircrafts')->insert([
                                'file_id' => $this->duplicateFile($document['document_id']),
                                'n_form_aircrafts_id' => $cloneAircraft->n_form_aircrafts_id,
                            ]);
                        }
                    }
                }
            }

            //Дублируем flights
            if ($this->requestHasNotEmptyArray($nForm, 'flights')) {
                foreach ($nForm['flights'] as $flight) {
                    $cloneFlight = NFormFlight::create([
                        "n_forms_id" => $cloneNForm->n_forms_id,
                        "status_id" => 1,
                        "status_change_datetime" => now()->toDateTimeString(),
                        "dates_is_repeat" => $flight['dates_is_repeat'],
                        "dates_or_periods" => $flight['dates_or_periods'],
                        //Часть данных лежит во flight_information, но сохраняется в таблицу n_form_flight
                        "flight_num" => $flight['flight_information']['flight_num'],
                        "purpose" => $flight['flight_information']['purpose_is_commercial'],
                        "transportation_categories_id" => $flight['flight_information']['transportation_categories_id'],
                        "is_found_departure_airport" => $flight['flight_information']['is_found_departure_airport'],
                        "departure_airport_id" => $flight['flight_information']['departure_airport_id'],
                        "departure_airport_icao" => $flight['flight_information']['departure_airport_icao'],
                        "departure_airport_namelat" => $flight['flight_information']['departure_airport_namelat'],
                        "departure_airport_namerus" => $flight['flight_information']['departure_airport_namerus'],
                        "departure_platform_name" => $flight['flight_information']['departure_platform_name'],
                        "departure_platform_coordinates" => $flight['flight_information']['departure_platform_coordinates'],
                        "departure_time" => $flight['flight_information']['departure_time'],
                        "is_found_landing_airport" => $flight['flight_information']['is_found_landing_airport'],
                        "landing_airport_id" => $flight['flight_information']['landing_airport_id'],
                        "landing_airport_icao" => $flight['flight_information']['landing_airport_icao'],
                        "landing_airport_namelat" => $flight['flight_information']['landing_airport_namelat'],
                        "landing_airport_namerus" => $flight['flight_information']['landing_airport_namerus'],
                        "landing_platform_name" => $flight['flight_information']['landing_platform_name'],
                        "landing_platform_coordinates" => $flight['flight_information']['landing_platform_coordinates'],
                        "landing_time" => $flight['flight_information']['landing_time'],
                        "landing_type" => $flight['flight_information']['landing_type'],
                        'update_datetime' => now('MSK'),
                    ]);

                    //set global id for each flight
                    $cloneFlight->update([
                        'n_form_flight_global_id' => $cloneFlight->n_form_flight_id
                    ]);

                    //Дублируем основную дату
                    if ($this->isFieldNotEmpty($flight, 'main_date')) {
                        $mainDate = DepartureDate::create([
                            "n_form_flight_id" => $cloneFlight->n_form_flight_id,
                            "is_main_date" => 1,
                            "date" => $flight['main_date']['date'],
                            "landing_date" => $flight['main_date']['landing_date'],
                            "is_required_dep_slot" => $flight['main_date']['is_required_dep_slot'],
                            "dep_slot_id" => $flight['main_date']['dep_slot_id'],
                            "is_required_land_slot" => $flight['main_date']['is_required_land_slot'],
                            "land_slot_id" => $flight['main_date']['land_slot_id'],
                        ]);

                        //Дублируем файлы для основной даты
                        if ($this->requestHasNotEmptyArray($flight['main_date'], 'documents')) {
                            foreach ($flight['main_date']['documents'] as $document) {
                                DB::table('file_departure_dates')->insert([
                                    'file_id' => $this->duplicateFile($document['document_id']),
                                    'departure_dates_id' => $mainDate->departure_dates_id,
                                ]);
                            }
                        }
                    }

                    //Дублируем периоды
                    if ($this->requestHasNotEmptyArray($flight, 'period_dates')) {
                        foreach ($flight['period_dates'] as $periodDate) {

                            $daysOfWeekObjects = null;

                            if (array_key_exists('days_of_week_objects', $periodDate)) {
                                $daysOfWeekObjects = $periodDate['days_of_week_objects'];
                            }

                            $period = PeriodDate::create([
                                "n_form_flight_id" => $cloneFlight->n_form_flight_id,
                                "start_date" => $periodDate['start_date'],
                                "end_date" => $periodDate['end_date'],
                                "days_of_week" => $periodDate['days_of_week'],
                                "days_of_week_objects" => $daysOfWeekObjects,
                            ]);

                            //Дублируем файлы для периодов
                            if ($this->requestHasNotEmptyArray($periodDate, 'documents')) {
                                foreach ($periodDate['documents'] as $document) {
                                    DB::table('file_n_form_period_dates')->insert([
                                        'file_id' => $this->duplicateFile($document['document_id']),
                                        'period_date_id' => $period->period_date_id,
                                    ]);
                                }
                            }
                        }
                    }

                    //Дублируем дополнительные даты
                    if ($this->requestHasNotEmptyArray($flight, 'other_dates')) {
                        foreach ($flight['other_dates'] as $otherDate) {
                            DepartureDate::create([
                                "n_form_flight_id" => $cloneFlight->n_form_flight_id,
                                'is_main_date' => 0,
                                "date" => $otherDate['date'],
                                "landing_date" => $otherDate['landing_date'],
                                "is_required_dep_slot" => $otherDate['is_required_dep_slot'],
                                "dep_slot_id" => $otherDate['dep_slot_id'],
                                "is_required_land_slot" => $otherDate['is_required_land_slot'],
                                "land_slot_id" => $otherDate['land_slot_id'],
                                'from_period' => $otherDate['from_period'],
                                'period_date_id' => 0, //Есть проблема с привязкой периодов при дублировании
                            ]);
                        }
                    }

                    //Дублируем файлы для flight
                    if ($this->requestHasNotEmptyArray($flight, 'dates_documents')) {
                        foreach ($flight['dates_documents'] as $document) {
                            DB::table('file_n_form_flight')->insert([
                                'file_id' => $this->duplicateFile($document['document_id']),
                                'n_form_flight_id' => $cloneFlight->n_form_flight_id,
                            ]);
                        }
                    }

                    //Дублируем основные точки
                    if ($this->requestHasNotEmptyArray($flight, 'points')) {
                        foreach ($flight['points'] as $point) {
                            $time = null;

                            if ($this->isFieldNotEmpty($point, 'time')) {
                                if ($point['time'] !== '') {
                                    $time = $point['time'];
                                }
                            }

                            $mainPoint = NFormPoint::create([
                                "n_form_flight_id" => $cloneFlight->n_form_flight_id,
                                "name" => $point['name'],
                                "is_found_point" => $point['is_found_point'],
                                "is_coordinates" => $point['is_coordinates'],
                                "departure_time_error" => $point['departure_time_error'],
                                "landing_time_error" => $point['landing_time_error'],
                                "POINTS_ID" => $point['POINTS_ID'],
                                "is_rf_border" => $point['ISGATEWAY'],
                                'ISINOUT' => $point['ISINOUT'],
                                "icao" => $point['icao'],
                                "time" => $time,
                                "coordinates" => $point['coordinates'],
                            ]);

                            //Дублируем дополнительные точки
                            if ($this->requestHasNotEmptyArray($point, 'alt_points')) {
                                foreach ($point['alt_points'] as $altPoint) {
                                    NFormAlternativePoint::create([
                                        "n_form_points_id" => $mainPoint->n_form_points_id,
                                        "POINTS_ID" => $altPoint['POINTS_ID'],
                                        "icao" => $altPoint['icao'],
                                        'name' => $altPoint['name'],
                                        'is_found_point' => $altPoint['is_found_point'],
                                        'is_coordinates' => $altPoint['is_coordinates'],
                                        'ISINOUT' => $altPoint['ISINOUT'],
                                        'ISGATEWAY' => $altPoint['ISGATEWAY'],
                                        'coordinates' => $altPoint['coordinates'],
                                    ]);
                                }
                            }
                        }
                    }

                    //Дублируем команду
                    if ($this->isFieldNotEmpty($flight, 'crew')) {
                        $cloneCrew = NFormCrew::create([
                            'n_form_flight_id' => $cloneFlight->n_form_flight_id,
                            'is_fpl' => $flight['crew']['is_fpl'],
                        ]);

                        //дублируем crew_groups
                        if ($this->requestHasNotEmptyArray($flight['crew'], 'crew_groups')) {
                            foreach ($flight['crew']['crew_groups'] as $crewGroup) {
                                NFormCrewGroup::create([
                                    'n_form_crew_id' => $cloneCrew->n_form_crew_id,
                                    'quantity' => $crewGroup['quantity'],
                                    'STATES_ID' => $crewGroup['STATES_ID'],
                                ]);
                            }
                        }

                        //дублируем crew_members
                        if ($this->requestHasNotEmptyArray($flight['crew'], 'crew_members')) {
                            foreach ($flight['crew']['crew_members'] as $crewMember) {
                                $cloneCrewMember = NFormCrewMember::create([
                                    'n_form_crew_id' => $cloneCrew->n_form_crew_id,
                                    "fio" => $crewMember['fio'],
                                    "STATES_ID" => $crewMember['STATES_ID'],
                                ]);

                                //Дублируем файлы для crew_members
                                if ($this->requestHasNotEmptyArray($crewMember, 'documents')) {
                                    foreach ($crewMember['documents'] as $document) {
                                        DB::table('file_n_form_crew_member')->insert([
                                            'file_id' => $this->duplicateFile($document['document_id']),
                                            'n_form_crew_member_id' => $cloneCrewMember->n_form_crew_member_id,
                                        ]);
                                    }
                                }
                            }
                        }
                    }

                    //Дублируем passengers
                    if ($this->isFieldNotEmpty($flight, 'passengers')) {
                        $clonePassengers = NFormPassenger::create([
                            "n_form_flight_id" => $cloneFlight->n_form_flight_id,
                            "quantity" => $flight['passengers']['quantity'],
                        ]);

                        //Дублируем passengers_persons
                        if ($this->requestHasNotEmptyArray($flight['passengers'], 'passengers_persons')) {
                            foreach ($flight['passengers']['passengers_persons'] as $passengersPerson) {
                                $clonePassengersPersons = NFormPassengersPerson::create([
                                    'n_form_passengers_id' => $clonePassengers->n_form_passengers_id,
                                    'fio' => $passengersPerson['fio'],
                                    'STATES_ID' => $passengersPerson['STATES_ID'],
                                ]);

                                //Дублируем файлы для passengers_persons
                                if ($this->requestHasNotEmptyArray($passengersPerson, 'documents')) {
                                    foreach ($passengersPerson['documents'] as $document) {
                                        DB::table('file_n_form_passengers_persons')->insert([
                                            'file_id' => $this->duplicateFile($document['document_id']),
                                            'n_form_passengers_persons_id' => $clonePassengersPersons->n_form_passengers_persons_id,
                                        ]);
                                    }
                                }
                            }
                        }
                    }

                    //Дублируем cargos
                    if ($this->requestHasNotEmptyArray($flight, 'cargos')) {
                        foreach ($flight['cargos'] as $cargo) {
                            $cloneCargo = NFormCargo::create([
                                'n_form_flight_id' => $cloneFlight->n_form_flight_id,
                                "type_and_characteristics" => $cargo['type_and_characteristics'],
                                "cargo_danger_classes_id" => $cargo['cargo_danger_classes_id'],
                                "weight" => $cargo['weight'],
                                "charterer_name" => $cargo['cargo_charterer'],
                                "charterer_fulladdress" => $cargo['cargo_charterer_fulladdress'],
                                "charterer_contact" => $cargo['cargo_charterer_phone'],
                                "receiving_party_name" => $cargo['receiving_party'],
                                "receiving_party_fulladdress" => $cargo['receiving_party_fulladdress'],
                                "receiving_party_contact" => $cargo['receiving_party_phone'],
                                "consignor_name" => $cargo['consignor'],
                                "consignor_fulladdress" => $cargo['consignor_fulladdress'],
                                "consignor_contact" => $cargo['consignor_phone'],
                                "consignee_name" => $cargo['consignee'],
                                "consignee_fulladdress" => $cargo['consignee_fulladdress'],
                                "consignee_contact" => $cargo['consignee_phone'],
                            ]);

                            //set global id for each cargo
                            $cloneCargo->update([
                                'n_form_cargo_global_id' => $cloneCargo->n_form_cargo_id
                            ]);

                            //Дублируем файлы для cargos
                            if ($this->requestHasNotEmptyArray($cargo, 'documents')) {
                                foreach ($cargo['documents'] as $document) {
                                    DB::table('file_n_form_cargo')->insert([
                                        'file_id' => $this->duplicateFile($document['document_id']),
                                        'n_form_cargo_id' => $cloneCargo->n_form_cargo_id,
                                    ]);
                                }
                            }
                        }
                    }
                }
            }

            //Дублируем airnav_payer
            if ($this->isFieldNotEmpty($nForm, 'airnav_payer')) {
                NFormAirnavPayer::create([
                    "n_forms_id" => $cloneNForm->n_forms_id,
                    "contact_person" => $nForm['airnav_payer']['contact_person'],
                    "fio" => $nForm['airnav_payer']['fio'],
                    "organization" => $nForm['airnav_payer']['organization'],
                    "tel" => $nForm['airnav_payer']['tel'],
                    "email" => $nForm['airnav_payer']['email'],
                    "aftn" => $nForm['airnav_payer']['aftn'],
                    "address" => $nForm['airnav_payer']['address'],
                    "remarks" => $nForm['airnav_payer']['remarks'],
                ]);
            }
        });

        return response()->json(
            [
                'message' => "Was duplicated new form $cloneNForm->n_forms_id",
                'n_forms_id' => $cloneNForm->n_forms_id,
                'id_pakus' => $cloneNForm->id_pakus,
            ], 200, $this->headers);

    }

    public function checkAno(Request $request): JsonResponse
    {
        if ($request->get('n_form_airlines_id') === null) {
            return response()->json(['message' => 'Missed params'], 404, $this->headers);
        }


        try {
            $airline = NFormAirline::findOrFail($request->get('n_form_airlines_id'));

            $responseJson = $this->checkAnoRequest($airline->AIRLINES_ID);
            $xmlString = $responseJson['d'];
            $xml = simplexml_load_string($xmlString);

            /*
             *  API matfmc возвращает код 0 когда нет задолженности
             *  код 1 когда задолженность есть
             *  -5 - exception ( какой эксепшн? )
             *  -6 - нет обязательных параметров ( в случае, когда передаем пустую строку вместо id )
             *  -7 - ID а/к не найден
             */

            switch ($xml->code) {
                case 0:
                    $anoIsPaid = 0;
                    break;
                case 1:
                    $anoIsPaid = 1;
                    break;
                case -7:
                    return response()->json(['message' => 'Airline not found in matfmc API'], 404, $this->headers);
                default:
                    return response()->json(['message' => 'Unexpected error'], 404, $this->headers);
            }

            $airline->update([
                'ano_is_paid' => $anoIsPaid,
            ]);

            return response()->json(['message' => 'ano_is_paid was successfully updated', 'ano_is_paid' => $anoIsPaid], 200, $this->headers);

        } catch (ModelNotFoundException $exception) {
            return response()->json(['message' => 'Airline not found'], 404, $this->headers);
        }

    }

    /* helpers */

    /**
     * Helper to easy saving attached document
     *
     * @param array $document
     * @return int
     */
    public function saveFile(array $document): int
    {
        //Убираем пробелы из имени файла
        $fileName = str_replace(' ', '_', $document['file_name']);

        //Добавляем рандомные символы к имени файла, чтобы не было дублей и перезаписей файлов с одинаковыми именами
        $randomStringForPath = Str::random();
        $path = "documents/{$randomStringForPath}_$fileName";


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

    /**
     * Save attached document as a new one or make duplicate of existing document
     *
     * @param $document
     * @param $modelPrimaryKey
     * @param $pivotTableName
     * @param $foreignPivotKeyName
     * @throws Throwable
     */
    public function saveOrUpdateDocument($document, $modelPrimaryKey, $pivotTableName, $foreignPivotKeyName)
    {
        /*
         * modelPrimaryKey - первичный ключ родительской модели ( от которой строится связь )
         * pivotTableName - имя промежуточной модели
         * foreignPivotKeyName - имя поля таблицы, которое ссылается на родительскую модель из промежуточной таблицы
         */

        //Если документ прикреплён, то создаем новые связи с сохранением ссылки на документ
        if (array_key_exists('document_id', $document) && $document['document_id'] !== null) {
            DB::table($pivotTableName)->insert([
                'file_id' => $this->updateFile($document),
                $foreignPivotKeyName => $modelPrimaryKey,
            ]);
            //Иначе - сохраняем новый прикреплённый документ
        } else {
            $fileId = $this->saveFile($document);

            DB::table($pivotTableName)->insert([
                'file_id' => $fileId,
                $foreignPivotKeyName => $modelPrimaryKey,
            ]);
        }
    }

    /**
     * Helper to clone the existing file
     *
     * @param $fileID
     * @return int
     */
    public function duplicateFile($fileID): int
    {
        $file = File::find($fileID);
        $cloneFile = $file->replicate();
        $cloneFile->save();

        return $cloneFile->id;
    }

    /**
     * Helper to update(re-create) the existing file
     *
     * @param array $document
     * @return int
     * @throws Throwable
     */
    public function updateFile(array $document): int
    {
        $fileID = 0;

        DB::transaction(function () use ($document, &$fileID) {
            $attrs = null;

            if (array_key_exists('required_attributes_json', $document)) {
                if ($document['required_attributes_json'] !== null) {
                    $attrs = $document['required_attributes_json'];

                    if (is_array($attrs)) {
                        $attrs = json_encode($attrs);
                    }
                }
            }

            $file = File::create([
                'file_type_id' => $document['file_type_id'],
                'file_type_name' => $document['file_type_name'],
                'filename' => $document['file_name'],
                'path' => $document['file_path'],
                'other_attributes_json' => $attrs,
            ]);

            $fileID = $file->id;
        });

        return $fileID;
    }

    public function requestHasNotEmptyArray($formSegment, $field): bool
    {
        if (!is_array($formSegment)) {
            return false;
        } else {
            return array_key_exists($field, $formSegment) && count($formSegment[$field]) > 0;
        }

    }

    public function isFieldNotEmpty($formSegment, $field): bool
    {
        return array_key_exists($field, $formSegment) && !is_null($formSegment[$field]);
    }

    /**
     * Do request to check ANO status
     *
     * @param $airline_id
     * @return array|mixed
     */
    private function checkAnoRequest($airline_id)
    {
        $url = 'https://app.matfmc.ru/DataValidityService/DataValidity.asmx/CheckAirllim';

        $data = [
            "Acft_ident" => "",
            "Regno" => "",
            "Airlines_id" => $airline_id,
            "pDateTime" => Carbon::now(),
        ];

        $checkAno = Http::post($url, $data);

        return $checkAno->json();
    }

    /**
     * Get all user roles as array
     *
     * @return array
     */
    private function getUserRolesAsArray(): array
    {
        return User::where('id', Auth::id())->with('roles')->first()->toArray()['roles'];
    }

    /**
     * Get all user roles ids
     *
     * @return array
     */
    private function getRolesIdsFromUser(): array
    {
        $userWithRoles = $this->getUserRolesAsArray();
        $roleIds = [];

        foreach ($userWithRoles as $userWithRole) {
            $roleIds[] += $userWithRole['id'];
        }

        return $roleIds;
    }

    /**
     * Get all approval groups ids from authenticated user
     *
     * @return array
     */
    private function getApprovalGroupsIdsFormUser(): array
    {
        $roles = $this->getUserRolesAsArray();
        $ids = [];

        foreach ($roles as $role) {
            $ids[] += $role['approval_group_id'];
        }

        return $ids;
    }

    /**
     * Get id of conditional group
     *
     * @return HigherOrderBuilderProxy|int|mixed
     */
    private function getConditionalGroupId()
    {
        return ApprovalGroup::where('name_lat', 'Conditional Group')->first()->id;
    }

    /**
     * Get all ids for conditional group
     * @return array
     */
    private function getAllRolesIdsFromConditionalGroup(): array
    {

        return Role::where('approval_group_id', $this->getConditionalGroupId())->get()->pluck('id')->toArray();
    }

    private function getAdminId(): int
    {
        return Role::where('name_lat', 'Administrator')->first()->id;
    }

    private function getUserData($takenById, $authorId): ?array
    {
        //Если форму пытается открыть заявитель
        if (Auth::id() === $authorId && Auth::id() !== $takenById) return null;

        $user = User::find($takenById);
        $userData = null;

        if ($user !== null) {
            $userData = [
                'user_id' => $user->id,
                'name' => $user->name,
                'patronymic' => $user->patronymic,
                'surname' => $user->surname
            ];
        }

        return $userData;
    }

    public function extendTimeNForm(Request $request): JsonResponse
    {

        $nForm = NForm::where('n_forms_id', $request->get('id'))->first();
        $userData = null;

        if (isset($nForm->taken_by_id) && isset($nForm->author_id)) $userData = $this->getUserData($nForm->taken_by_id, $nForm->author_id);

        if ($nForm !== null) {
            if ($nForm['taken_by_id'] == $request->get('userId')) {


                if ($request->get('exempt') == true) {
                    $nForm['taken_by_id'] = null;
                    $nForm['take_time'] = null;
                    $nForm->save();
                    return response()->json(['status' => true, 'message' => 'Заявка освобождена']);
                }

                $takeDateTime = Carbon::now('Europe/Moscow')->addMinutes(10);
                $nForm['take_time'] = $takeDateTime;
                $nForm->save();
                return response()->json(['status' => true, 'message' => 'Время продлено']);
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Заявка занята',
            'user' => $userData
        ]);
    }

    public function checkingAvailabilityForms(Request $request)
    {
        $formsIdx = explode(',', $request['formsIdx']);
        $r = $request->all();
        $accessForms = [];
        foreach ($formsIdx as $formId) {
            $nForm = NForm::where('n_forms_id', $formId)->first();

            if ($nForm !== null) {
                $takeDateTime = Carbon::now('Europe/Moscow')->addMinutes(10);
                $currantDateTime = Carbon::now('Europe/Moscow')->toDateTimeString();
                $userData = null;

                if ($nForm['taken_by_id'] === null) {
                    $nForm['taken_by_id'] = $request['user_id'];
                    $nForm['take_time'] = $takeDateTime->toDateTimeString();
                    $nForm->save();
                    $accessForms[] = [$formId, true];
                } else {
                    $userId = $request['user_id'];
                    if ($nForm['taken_by_id'] != $userId && $nForm['take_time'] > $currantDateTime) {
                        $accessForms[] = [$formId, false];
                    } elseif ($nForm['taken_by_id'] == $userId && $nForm['take_time'] > $currantDateTime) {
                        $accessForms[] = [$formId, true];
                    } else {
                        $accessForms[] = [$formId, true];
                    }
                }
            } else {
                $accessForms[] = [$formId, false];
            }

        }
        return response()->json([
            'status' => true,
            'checkResult' => $accessForms
        ]);
    }

    public function uniteForms(Request $request)
    {
        $formsIdx = explode(',', $request['formsIdx']);
        $uniteForms = [];
        foreach ($formsIdx as $formId) {
            $nForm = NForm::where('n_forms_id', $formId)->first();
            if ($nForm !== null) {
                if ($nForm['taken_by_id'] == $request['user_id']) {
                    $nForm['taken_by_id'] = null;
                    $nForm['take_time'] = null;
                    $nForm->save();
                    $uniteForms[] = [$formId, true];
                } else {
                    $uniteForms[] = [$formId, false];
                }
            } else {
                $uniteForms[] = [$formId, false];
            }

        }
        return response()->json([
            'status' => true,
            'uniteResult' => $uniteForms
        ]);
    }

    private array $headers = ['Content-type' => 'application/json', 'Access-Control-Allow-Origin' => '*'];
}
