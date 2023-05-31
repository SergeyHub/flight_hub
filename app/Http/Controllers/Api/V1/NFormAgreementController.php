<?php /** @noinspection PhpMultipleClassDeclarationsInspection */


namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\V1\Classes\Agreement\SaveSign;
use App\Http\Controllers\Api\V1\Classes\Agreement\SaveHistory;
use App\Http\Controllers\Api\V1\Classes\Agreement\SaveNFormComment;
use App\Http\Controllers\Api\V1\Classes\Headers;
use App\Http\Controllers\Api\V1\Classes\Responses;
use App\Http\Requests\Api\V1\FormNAgreement\NFormAgreementCorrectRequest;
use App\Models\NForm;
use App\Models\NFormFlight;
use App\Models\NFormFlightAgreementSign;
use App\Models\NFormFlightNFormFlightStatus;
use App\Models\NFormFlightStatus;
use App\Models\Role;
use App\UseCases\CustomValidator\NFormAgreementValidator;
use Illuminate\Database\QueryException;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Согласование после сохранения Формы
 *
 * @package App\Http\Controllers\Api\V1
 */
final class NFormAgreementController extends Controller
{

    private SaveSign $sign;
    private SaveHistory $history;
    private SaveNFormComment $comment;

    public function __construct(
        SaveSign         $saveSigns,
        SaveHistory      $history,
        SaveNFormComment $comment
    )
    {
        $this->sign = $saveSigns;
        $this->history = $history;
        $this->comment = $comment;
    }

    /**
     * Собирает массивы в общий массив и выводит информацию в массиве $result[]
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function nFormAgreement(Request $request): JsonResponse
    {
        $status = 200;

        // Response statuses
        $statuses = collect([]);

        $result = [];
        foreach ($request->all() as $array) {
            // Obj stdClass to array
            $nFormFlightAgreement = (array)$this->nFormFlightAgreement($array)->getData();

            // Push statuses
            if (isset($nFormFlightAgreement['status']) && !$nFormFlightAgreement['status']) $statuses->push(422);

            $result[] = $nFormFlightAgreement;
        }

        // Isset status 422 all status = 422
        $statuses->contains(function ($value) use ($result, &$status) {
            if ($value === 422) $status = 422;
        });

        return response()
            ->json([
                'result' => $result
            ], $status, [], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Agreement process
     *
     * @param array $data
     * @return JsonResponse
     */
    public function nFormFlightAgreement(array $data): JsonResponse
    {
        $data = (new NFormAgreementValidator($data))->validate();

        // TODO это проверка что рейса нет значит запрос информации нужно доработать

        // передается либо рейс, либо заявка, если заявка то сохраняем коммент под общую информацию
        if (isset($data['n_form_flight_id'])) {

            // Рейс
            $n_form_flight = $this->searchFlight($data['n_form_flight_id']);

            // Форма Н
            $n_form = $this->searchNForm($n_form_flight);

        } else {
            // TODO добавить проверки или вынести этот код отдельно

            // Заявитель ответить на общую информацию
            $comment = $this->comment->saveNFormComment($data); // save comment

            return response()
                ->json([
                    'status' => true,
                    'status_id' => 8,
                    'role_id' => 3,
                    'message' => 'ОК',
                    'n_form_flight_id' => null,
                    'comment_id' => $comment->n_form_comment_id,
                    'created_at' => now()
                ], 200, [], JSON_UNESCAPED_UNICODE);
        }


        /**
         * Процесс согласования
         *
         * Виды согласований происходят последовательно и параллельно
         *
         * Последовательно ГЦ затем Условные группы
         * Параллельно РА может создавать Назначенные группы
         *
         * -----------------------------
         *
         * Структура switch
         *
         * -> Группа согласования - id
         * --> Переданный знак согласования - id
         * ---> Проверка статуса Рейса
         */
        switch ($data['approval_group_id']) {

//******************** Заявитель 1 ********************//
            case 1:

                // Присвоенные Знаки Согласования для Роли
                $saved_signs = $this->findNFormFlightAgreementSign($n_form_flight->n_form_flight_id);

                // Знак Согласования
                switch ($data['n_form_flight_sign_id']) {

                    //******************** Заявитель Отправить ********************//
                    case 1: // Отправить
                        switch ($n_form_flight['status_id']) {
                            case 1: // Черновик

                                NFormFlightAgreementSign::updateOrCreate([
                                    'n_form_flight_id' => $n_form_flight->n_form_flight_id,
                                    'approval_group_id' => 2,
                                    'n_form_flight_sign_id' => 12, // "В обработке" для сотрудника ГЦ
                                    'role_id' => 4
                                ]);
                                NFormFlightAgreementSign::updateOrCreate([
                                    'n_form_flight_id' => $n_form_flight->n_form_flight_id,
                                    'approval_group_id' => 3,
                                    'n_form_flight_sign_id' => 1, // "Отправлено" для сотрудника РА
                                    'role_id' => 5
                                ]);
                                NFormFlightAgreementSign::updateOrCreate([
                                    'n_form_flight_id' => $n_form_flight->n_form_flight_id,
                                    'approval_group_id' => 4,
                                    'n_form_flight_sign_id' => 1, // "Отправлено" для начальника РА
                                    'role_id' => 6
                                ]);

                                // Статус и История
                                $this->history->save(
                                    $n_form->id_pakus,
                                    $n_form->version,
                                    $n_form_flight,
                                    3,
                                    3
                                );

                                $n_form_flight->updateStatus('Отправлено', 3);

                                return response()
                                    ->json([
                                        'status' => true,
                                        'status_id' => $n_form_flight->status_id,
                                        'role_id' => 3, // Заявитель
                                        'n_form_flight_id' => $n_form_flight->n_form_flight_id,
                                        'message' => 'ОК'
                                    ], 200, [], JSON_UNESCAPED_UNICODE);

                            default:
                                return $this->errorMessage('Статус рейса не черновик', 3, $n_form_flight);
                        }

                    //******************** Заявитель Ответить ********************//
                    case 3:
                        switch (true) {
                            case $this->checkStatusFlights($n_form_flight):

                                $comment = $this->comment->saveNFormComment($data); // save comment

                                return response()
                                    ->json([
                                        'status' => true,
                                        'status_id' => 8,
                                        'role_id' => 3,
                                        'message' => 'ОК',
                                        'n_form_flight_id' => $n_form_flight->n_form_flight_id,
                                        'comment_id' => $comment->n_form_comment_id,
                                        'created_at' => $n_form_flight->created_at->format('Y-m-d H:i:s')
                                    ], 200, [], JSON_UNESCAPED_UNICODE);

                            default:
                                return $this->errorMessage('Статус не определен или заявка еще не отправлена Заявителем', 3, $n_form_flight);
                        }

                    //******************** Заявитель Скорректировать ********************//
                    case 4:
                        switch (true) {
                            case $this->checkStatusFlights($n_form_flight):

//                                $this->signIsUsed($n_form, 3, 12);

                                // Все присвоенные знаки делать серыми
                                foreach ($saved_signs as $saved_sign) {
                                    $saved_sign->n_form_flight_sign_id = 4; // Скорректировать
                                    $saved_sign->save();
                                }

                                return response()
                                    ->json([
                                        'status' => true,
                                        'status_id' => 12,
                                        'role_id' => 3, // Заявитель
                                        'n_form_flight_id' => $n_form_flight->n_form_flight_id,
                                        'message' => 'ОК'
                                    ], 200, [], JSON_UNESCAPED_UNICODE);
                            default:
                                return $this->errorMessage('Статус не определен', 3, $n_form_flight);
                        }

                    //******************** Заявитель Аннулировать ********************//
                    case 5:
                        switch (true) {
                            case $this->checkStatusFlights($n_form_flight):

//                                $this->signIsUsed($n_form, 3, 2);

                                //***** Обновление формы, чтобы в истории можно было открыть любую версию с сохранённым состоянием *****//
                                $n_form = $this->getUpdatedNForm($n_form->id_pakus);

                                //Получаем рейс по global_id из обновлённой формы
                                $n_form_flight = $this->getFlightByGlobalObjectId($n_form_flight->n_form_flight_global_id, $n_form);

                                // Присвоенные Знаки Согласования
                                $saved_signs = $this->findNFormFlightAgreementSign($n_form_flight->n_form_flight_id);

                                //Если заявитель отменяет заявку, то знаки "в обработке" должны стать "отправлено"
                                foreach ($saved_signs as $sign) {
                                    if ($sign->n_form_flight_sign_id == 12) {
                                        $this->sign->save($saved_signs, $sign->approval_group_id, 1, $sign->role_id);
                                    }
                                }

                                // Знаки остаются при отменено Заявителем

                                // Статус и История
                                $this->history->save(
                                    $n_form->id_pakus,
                                    $n_form->version,
                                    $n_form_flight,
                                    2,
                                    3
                                );

                                $n_form_flight->updateStatus('Отменено', 2);

                                return response()
                                    ->json([
                                        'status' => true,
                                        'status_id' => 2,
                                        'role_id' => 3, // Заявитель
                                        'n_form_flight_id' => $n_form_flight->n_form_flight_id,
                                        'message' => 'ОК'
                                    ], 200, [], JSON_UNESCAPED_UNICODE);

                            default:
                                return $this->errorMessage('Статус не определен', 3, $n_form_flight);
                        }
                    default:
                        return $this->errorMessage('Знак согласования для группы не определен', 3, $n_form_flight);
                }

//******************** Сотрудник ГЦ 2 ********************//
            case 2:

                // Знак Согласования
                switch ($data['n_form_flight_sign_id']) {

                    //******************** Сотрудник ГЦ Согласовать ********************//
                    case 2: // Согласовать

                        // Статус Рейса
                        switch (true) {
                            case $this->checkStatusFlights($n_form_flight):

//                                $this->signIsUsed($n_form, 4, 4);

                                //******************** Условные группы после согласования ГЦ start ********************//
                                $groups = collect([]);

                                $states_id = $this->getStatesId($n_form_flight->n_form_flight_id);

                                if ($states_id === null) return $this->errorMessage('В заявке не указана страна (STATES_ID) для Авиапредприятия');

                                if($states_id !== 94){
                                    if ($n_form_flight['transportation_categories_id'] === null) return $this->errorMessage('Не указана категория перевозки (transportation_categories_id)');

                                    // Назнач. перевозчик Грузовых чартеров
                                    if ($n_form_flight['transportation_categories_id'] === 2) {
                                        //Перечень российских эксплуатантов ( Назнач. перевозчик Грузовых чартеров )
                                        $groups->push(
                                            [
                                                'role_id' => 17,
                                                'approval_group_id' => 5
                                            ],
                                            [
                                                'role_id' => 18,
                                                'approval_group_id' => 5
                                            ],
                                            [
                                                'role_id' => 19,
                                                'approval_group_id' => 5
                                            ],
                                            [
                                                'role_id' => 20,
                                                'approval_group_id' => 5
                                            ],
                                            [
                                                'role_id' => 21,
                                                'approval_group_id' => 5
                                            ]
                                        );
                                    }

                                    // ВС кресел
                                    $n_form = $n_form_flight->nForm;
                                    if (!is_null($n_form)) {

                                        // для ВС больше или меньше 20 проверяем первый в списке
                                        $n_form_aircraft = $n_form->nFormAircraft()->where('is_main', 1)->first();

                                        if (!is_null($n_form_aircraft)) {

                                            $fleet = $n_form_aircraft->fleet;

                                            if (!is_null($fleet)) {

                                                $acftmod = $fleet->acftmod;

                                                if (!is_null($acftmod)) {

                                                    $passenger_count = $acftmod->PASSENGERCOUNT;

                                                    if (!is_null($passenger_count)) {

                                                        switch (true) {
                                                            case $passenger_count > 20:

                                                                //Перечень российских эксплуатантов ( Назнач. перевозчик ВС>20 кресел )
                                                                $groups->push(
                                                                    [
                                                                        'role_id' => 22,
                                                                        'approval_group_id' => 5
                                                                    ],
                                                                    [
                                                                        'role_id' => 23,
                                                                        'approval_group_id' => 5
                                                                    ],
                                                                    [
                                                                        'role_id' => 24,
                                                                        'approval_group_id' => 5
                                                                    ],
                                                                    [
                                                                        'role_id' => 25,
                                                                        'approval_group_id' => 5
                                                                    ],
                                                                    [
                                                                        'role_id' => 26,
                                                                        'approval_group_id' => 5
                                                                    ],
                                                                    [
                                                                        'role_id' => 27,
                                                                        'approval_group_id' => 5
                                                                    ],
                                                                    [
                                                                        'role_id' => 28,
                                                                        'approval_group_id' => 5
                                                                    ],
                                                                    [
                                                                        'role_id' => 29,
                                                                        'approval_group_id' => 5
                                                                    ]
                                                                );
                                                                break;
                                                            default:

                                                                //Перечень российских эксплуатантов ( Назнач. перевозчик ВС<20 кресел )
                                                                $groups->push(
                                                                    [
                                                                        'role_id' => 30,
                                                                        'approval_group_id' => 5
                                                                    ],
                                                                    [
                                                                        'role_id' => 31,
                                                                        'approval_group_id' => 5
                                                                    ],
                                                                    [
                                                                        'role_id' => 32,
                                                                        'approval_group_id' => 5
                                                                    ],
                                                                    [
                                                                        'role_id' => 33,
                                                                        'approval_group_id' => 5
                                                                    ],
                                                                    [
                                                                        'role_id' => 34,
                                                                        'approval_group_id' => 5
                                                                    ],
                                                                    [
                                                                        'role_id' => 35,
                                                                        'approval_group_id' => 5
                                                                    ],
                                                                    [
                                                                        'role_id' => 36,
                                                                        'approval_group_id' => 5
                                                                    ],
                                                                    [
                                                                        'role_id' => 37,
                                                                        'approval_group_id' => 5
                                                                    ],
                                                                    [
                                                                        'role_id' => 38,
                                                                        'approval_group_id' => 5
                                                                    ],
                                                                    [
                                                                        'role_id' => 39,
                                                                        'approval_group_id' => 5
                                                                    ],
                                                                    [
                                                                        'role_id' => 40,
                                                                        'approval_group_id' => 5
                                                                    ],
                                                                    [
                                                                        'role_id' => 41,
                                                                        'approval_group_id' => 5
                                                                    ],
                                                                    [
                                                                        'role_id' => 42,
                                                                        'approval_group_id' => 5
                                                                    ]
                                                                );
                                                                break;
                                                        }
                                                    } else return $this->errorMessage('ACFTMOD->PASSENGERCOUNT нет данных', 4);
                                                } else return $this->errorMessage('ACFTMOD нет данных', 4);
                                            } else return $this->errorMessage('FLEET нет данных', 4);
                                        } else return $this->errorMessage('ВС условная группа не назначена, n_form_aircraft пустые данные');
                                    } else return $this->errorMessage('n_form_aircraft пустая форма Н', 4, $n_form_flight);
                                }

                                //******************** Условные группы end ********************//

                                //***** Обновление формы, чтобы в истории можно было открыть любую версию с сохранённым состоянием *****//
                                $n_form = $this->getUpdatedNForm($n_form->id_pakus);

                                //Получаем рейс по global_id из обновлённой формы
                                $n_form_flight = $this->getFlightByGlobalObjectId($n_form_flight->n_form_flight_global_id, $n_form);

                                // Присвоенные Знаки Согласования
                                $saved_signs = $this->findNFormFlightAgreementSign($n_form_flight->n_form_flight_id);

                                // Сохранение знака
                                $this->sign->save($saved_signs, 2, 2, 4); // ГЦ | Согласовать | Сотрудник ГЦ

                                $data['n_form_flight_id'] = $n_form_flight->n_form_flight_id;

                                // Сохраняем для Сотрудника РА знак "В обработке"
                                foreach ($saved_signs as $sign) {
                                    if ($sign->n_form_flight_sign_id == 1 && $sign->role_id == 6) {
                                        $this->sign->save($saved_signs, 4, 12, 6); // Сотрудник РА | В обработке | Сотрудник РА
                                        break;
                                    }
                                }

                                // Сохраняем условные группы
                                if (count($groups) > 0) {
                                    foreach ($groups as $group) {
                                        $this->conditionalGroup($data, $group);
                                    }
                                }

                                // Статус и История
                                $this->history->save(
                                    $n_form->id_pakus,
                                    $n_form->version,
                                    $n_form_flight,
                                    4,
                                    4
                                );

                                // Отклонил или согласовал Начальник РА
                                if(!$this->isRaAgreed($n_form_flight)) {
                                    $n_form_flight->updateStatus('Принято', 4);
                                }

                                return response()
                                    ->json([
                                        'status' => true,
                                        'status_id' => 4,
                                        'role_id' => 4, // Сотрудник ГЦ
                                        'n_form_flight_id' => $n_form_flight->n_form_flight_id,
                                        'message' => 'ОК'
                                    ], 200, [], JSON_UNESCAPED_UNICODE);

                            default:
                                return $this->errorMessage('Статус не определен', 4, $n_form_flight);
                        }

                    //******************** Сотрудник ГЦ Отложить ********************//
                    case 7:

                        // Статус Рейса
                        switch (true) {
                            case $this->checkStatusFlights($n_form_flight):

//                                $this->signIsUsed($n_form, 4, 5);

//                                // Отклонил или согласовал Начальник РА
//                                $this->isRaAgreed($n_form_flight);

                                //***** Обновление формы, чтобы в истории можно было открыть любую версию с сохранённым состоянием *****//
                                $n_form = $this->getUpdatedNForm($n_form->id_pakus);
                                $n_form_flight = $this->getFlightByGlobalObjectId($n_form_flight->n_form_flight_global_id, $n_form);

                                // Присвоенные Знаки Согласования
                                $saved_signs = $this->findNFormFlightAgreementSign($n_form_flight->n_form_flight_id);

                                // Сохранение знака
                                $this->sign->save($saved_signs, 2, 7, 4); // ГЦ | Отложить

                                // Статус и История
                                $this->history->save(
                                    $n_form->id_pakus,
                                    $n_form->version,
                                    $n_form_flight,
                                    5,
                                    4
                                );

//                                $n_form_flight->updateStatus('Ожидает обработки', 5);

                                return response()
                                    ->json([
                                        'status' => true,
                                        'status_id' => 5,
                                        'role_id' => 4, // Сотрудник ГЦ
                                        'n_form_flight_id' => $n_form_flight->n_form_flight_id,
                                        'message' => 'ОК'
                                    ], 200, [], JSON_UNESCAPED_UNICODE);

                            default:
                                return $this->errorMessage('Статус не определен', 4, $n_form_flight);
                        }

                    default:
                        return $this->errorMessage('Знак согласования для группы не определен', 4, $n_form_flight);
                }

//******************** Начальник РА 3 ********************//
            case 3:
                switch ($data['n_form_flight_sign_id']) {

                    //******************** Начальник РА Согласовать  ********************//
                    case 8: // Утвердить

                        switch (true) {
                            case $this->checkStatusFlights($n_form_flight):

//                                $this->signIsUsed($n_form, 5, 11);

                                //***** Обновление формы, чтобы в истории можно было открыть любую версию с сохранённым состоянием *****//
                                $n_form = $this->getUpdatedNForm($n_form->id_pakus);
                                $n_form_flight = $this->getFlightByGlobalObjectId($n_form_flight->n_form_flight_global_id, $n_form);

                                // Присвоенные Знаки Согласования
                                $saved_signs = $this->findNFormFlightAgreementSign($n_form_flight->n_form_flight_id);

                                $this->sign->save($saved_signs, 3, 8, 5); // Начальник РА | Утверждено

                                $this->history->save(
                                    $n_form->id_pakus,
                                    $n_form->version,
                                    $n_form_flight,
                                    11,
                                    5
                                );

                                $n_form_flight->updateStatus('Утверждено', 11);

                                return response()
                                    ->json([
                                        'status' => true,
                                        'status_id' => 11,
                                        'role_id' => 5, // Начальник РА
                                        'n_form_flight_id' => $n_form_flight->n_form_flight_id,
                                        'message' => 'ОК'
                                    ], 200, [], JSON_UNESCAPED_UNICODE);

                            default:
                                return $this->errorMessage('Статус не определен', 5, $n_form_flight);
                        }

                    //******************** Начальник РА Отложить  ********************//
                    case 7:

                        // Статус и История
                        switch (true) {
                            case $this->checkStatusFlights($n_form_flight):

//                                $this->signIsUsed($n_form, 5, 5);

//                                // Отклонил или согласовал Начальник РА
//                                $this->isRaAgreed($n_form_flight);

                                //***** Обновление формы, чтобы в истории можно было открыть любую версию с сохранённым состоянием *****//
                                $n_form = $this->getUpdatedNForm($n_form->id_pakus);
                                $n_form_flight = $this->getFlightByGlobalObjectId($n_form_flight->n_form_flight_global_id, $n_form);

                                // Присвоенные Знаки Согласования
                                $saved_signs = $this->findNFormFlightAgreementSign($n_form_flight->n_form_flight_id);

                                // Сохранение знака
                                $this->sign->save($saved_signs, 3, 7, 5); // Начальник РА | Отложить

                                // Статус и История
                                $this->history->save(
                                    $n_form->id_pakus,
                                    $n_form->version,
                                    $n_form_flight,
                                    5,
                                    5
                                );

                                return response()
                                    ->json([
                                        'status' => true,
                                        'status_id' => 5,
                                        'role_id' => 5, // Начальник РА
                                        'n_form_flight_id' => $n_form_flight->n_form_flight_id,
                                        'message' => 'ОК'
                                    ], 200, [], JSON_UNESCAPED_UNICODE);

                            default:
                                return $this->errorMessage('Статус не определен', 5, $n_form_flight);
                        }

                    //******************** Начальник РА Отклонить  ********************//
                    case 9:

                        // Статус Рейса
                        switch (true) {
                            case $this->checkStatusFlights($n_form_flight):

//                                $this->signIsUsed($n_form, 5, 10);

                                //***** Обновление формы, чтобы в истории можно было открыть любую версию с сохранённым состоянием *****//
                                $n_form = $this->getUpdatedNForm($n_form->id_pakus);
                                $n_form_flight = $this->getFlightByGlobalObjectId($n_form_flight->n_form_flight_global_id, $n_form);

                                // Присвоенные Знаки Согласования
                                $saved_signs = $this->findNFormFlightAgreementSign($n_form_flight->n_form_flight_id);

                                // Сохранение знака
                                $this->sign->save($saved_signs, 3, 9, 5); // Начальник РА | Отклонить

                                // Статус и История
                                $this->history->save(
                                    $n_form->id_pakus,
                                    $n_form->version,
                                    $n_form_flight,
                                    10,
                                    5
                                );

                                $n_form_flight->updateStatus('Отклонено', 10);

                                return response()
                                    ->json([
                                        'status' => true,
                                        'status_id' => 10,
                                        'role_id' => 5, // Начальник РА
                                        'n_form_flight_id' => $n_form_flight->n_form_flight_id,
                                        'message' => 'ОК'
                                    ], 200, [], JSON_UNESCAPED_UNICODE);

                            default:
                                return $this->errorMessage('Статус не определен', 5, $n_form_flight);
                        }

                    //******************** Начальник РА Назначить  ********************//
                    case 10: // Назначено

                        // Статус Рейса
                        switch (true) {
                            case $this->checkStatusFlights($n_form_flight):

//                                // Отклонил или согласовал Начальник РА
//                                $this->isRaAgreed($n_form_flight);

                                //***** Обновление формы, чтобы в истории можно было открыть любую версию с сохранённым состоянием *****//
                                $n_form = $this->getUpdatedNForm($n_form->id_pakus);
                                $n_form_flight = $this->getFlightByGlobalObjectId($n_form_flight->n_form_flight_global_id, $n_form);

                                // Присвоенные Знаки Согласования
                                $saved_signs = $this->findNFormFlightAgreementSign($n_form_flight->n_form_flight_id);

                                // Назначить условные группы
                                $this->approvalGroup($data);

                                // Сохранение знака
                                $this->sign->save($saved_signs, 3, 10, 5); // Начальник РА | Назначить


                                // Статус и История
                                $this->history->save(
                                    $n_form->id_pakus,
                                    $n_form->version,
                                    $n_form_flight,
                                    6,
                                    5
                                );

                                //******************** Назначенная группа start ********************//
                                if (isset($data['assigned_role_ids'])) {
                                    NFormFlightAgreementSign::where('n_form_flight_id', $data['n_form_flight_id'])
                                        ->where('approval_group_id', 6)
                                        ->delete();

                                    foreach ($data['assigned_role_ids'] as $assigned_role_id) {
                                        $group = Role::find($assigned_role_id);
                                        if (!is_null($group)) {
                                            if ($group->approval_group->id === 6) {
                                                NFormFlightAgreementSign::create([
                                                    'n_form_flight_id' => $data['n_form_flight_id'],
                                                    'approval_group_id' => 6,
                                                    'n_form_flight_sign_id' => 12, // В обработке
                                                    'role_id' => $assigned_role_id
                                                ]);
                                            } #TODO error Переданная роль не входит в группу назначаемых
                                        } #TODO error Назначаемая роль не существует
                                    }
                                } #TODO error Назначаемые роли не переданы
                                //******************** Назначенная группа end ********************//

                                return response()
                                    ->json([
                                        'status' => true,
                                        'status_id' => 6,
                                        'role_id' => 5,
                                        'n_form_flight_id' => $n_form_flight->n_form_flight_id,
                                        'message' => 'ОК'
                                    ], 200, [], JSON_UNESCAPED_UNICODE);

                            default:
                                return $this->errorMessage('Статус не определен', 5, $n_form_flight);
                        }

                    default:
                        return $this->errorMessage('Знак согласования для группы не определен', 5, $n_form_flight);
                }

//******************** Сотрудник РА 4 ********************//
            case 4:
                // Знак Согласования
                switch ($data['n_form_flight_sign_id']) {


                    //******************** Сотрудник РА Согласовать  ********************//
                    case 2: // Согласовать

                        switch (true) {
                            case $this->checkStatusFlights($n_form_flight):

//                                $this->signIsUsed($n_form, 6, 7);

//                                // Отклонил или согласовал Начальник РА
//                                $this->isRaAgreed($n_form_flight);

                                //***** Обновление формы, чтобы в истории можно было открыть любую версию с сохранённым состоянием *****//
                                $n_form = $this->getUpdatedNForm($n_form->id_pakus);
                                $n_form_flight = $this->getFlightByGlobalObjectId($n_form_flight->n_form_flight_global_id, $n_form);

                                // Присвоенные Знаки Согласования
                                $saved_signs = $this->findNFormFlightAgreementSign($n_form_flight->n_form_flight_id);

                                // Сохранение знака
                                $this->sign->save($saved_signs, 4, 2, 6); // Сотрудник РА | Согласовать

                                // Сохраняем для Начальника РА знак "В обработке"
                                foreach ($saved_signs as $sign) {
                                    if ($sign->n_form_flight_sign_id == 1 && $sign->role_id == 5) {
                                        $this->sign->save($saved_signs, 3, 12, 5); // Начальник РА | В обработке
                                        break;
                                    }
                                }

                                // Статус и История
                                $this->history->save(
                                    $n_form->id_pakus,
                                    $n_form->version,
                                    $n_form_flight,
                                    7,
                                    6
                                );

                                return response()
                                    ->json([
                                        'status' => true,
                                        'status_id' => 7,
                                        'role_id' => 6,
                                        'n_form_flight_id' => $n_form_flight->n_form_flight_id,
                                        'message' => 'ОК'
                                    ], 200, [], JSON_UNESCAPED_UNICODE);

                            default:
                                return $this->errorMessage('Статус не определен', 6, $n_form_flight);
                        }


                    //******************** Сотрудник РА Отложить ********************//
                    case 7: // Отложить

                        // Статус Рейса
                        switch (true) {
                            case $this->checkStatusFlights($n_form_flight):

//                                $this->signIsUsed($n_form, 6, 5);

//                                // Отклонил или согласовал Начальник РА
//                                $this->isRaAgreed($n_form_flight);

                                //***** Обновление формы, чтобы в истории можно было открыть любую версию с сохранённым состоянием *****//
                                $n_form = $this->getUpdatedNForm($n_form->id_pakus);
                                $n_form_flight = $this->getFlightByGlobalObjectId($n_form_flight->n_form_flight_global_id, $n_form);

                                // Присвоенные Знаки Согласования
                                $saved_signs = $this->findNFormFlightAgreementSign($n_form_flight->n_form_flight_id);

                                // Сохранение знака
                                $this->sign->save($saved_signs, 4, 7, 6); // Сотрудник РА | Отложить

                                // Статус и История
                                $this->history->save(
                                    $n_form->id_pakus,
                                    $n_form->version,
                                    $n_form_flight,
                                    5,
                                    6
                                );

                                return response()
                                    ->json([
                                        'status' => true,
                                        'status_id' => 5,
                                        'role_id' => 6,
                                        'n_form_flight_id' => $n_form_flight->n_form_flight_id,
                                        'message' => 'ОК'
                                    ], 200, [], JSON_UNESCAPED_UNICODE);

                            default:
                                return $this->errorMessage('Статус не определен', 6, $n_form_flight);
                        }


                    //******************** Сотрудник РА Отклонить ********************//
                    case 9: // Отклонить

                        // Статус Рейса
                        switch (true) {
                            case $this->checkStatusFlights($n_form_flight):

//                                $this->signIsUsed($n_form, 6, 10);

                                //***** Обновление формы, чтобы в истории можно было открыть любую версию с сохранённым состоянием *****//
                                $n_form = $this->getUpdatedNForm($n_form->id_pakus);
                                $n_form_flight = $this->getFlightByGlobalObjectId($n_form_flight->n_form_flight_global_id, $n_form);

                                // Присвоенные Знаки Согласования
                                $saved_signs = $this->findNFormFlightAgreementSign($n_form_flight->n_form_flight_id);

                                // Сохранение знака
                                $this->sign->save($saved_signs, 4, 9, 6); // Начальник РА | Отклонить

                                // Статус и История
                                $this->history->save(
                                    $n_form->id_pakus,
                                    $n_form->version,
                                    $n_form_flight,
                                    10,
                                    6
                                );

                                $n_form_flight->updateStatus('Отклонено', 10);

                                return response()
                                    ->json([
                                        'status' => true,
                                        'status_id' => 10,
                                        'role_id' => 6,
                                        'n_form_flight_id' => $n_form_flight->n_form_flight_id,
                                        'message' => 'ОК'
                                    ], 200, [], JSON_UNESCAPED_UNICODE);

                            default:
                                return $this->errorMessage('Статус не определен', 6, $n_form_flight);
                        }

                    //******************** Сотрудник РА Назначить  ********************//
                    case 10: // Назначено

                        // Статус Рейса
                        switch (true) {
                            case $this->checkStatusFlights($n_form_flight):

                                if ($this->approvalGroupCheck($data)) {
                                    //***** Обновление формы, чтобы в истории можно было открыть любую версию с сохранённым состоянием *****//
                                    $n_form = $this->getUpdatedNForm($n_form->id_pakus);
                                    $n_form_flight = $this->getFlightByGlobalObjectId($n_form_flight->n_form_flight_global_id, $n_form);
                                    $data['n_form_flight_id'] = $n_form_flight->n_form_flight_id;
                                }

                                // Присвоенные Знаки Согласования
                                $saved_signs = $this->findNFormFlightAgreementSign($n_form_flight->n_form_flight_id);

                                // Назначить условные группы
                                $this->approvalGroup($data);

                                // Сохранение знака
                                $this->sign->save($saved_signs, 4, 10, 6); // Сотрудник РА | Назначить

                                // Статус и История
                                $this->history->save(
                                    $n_form->id_pakus,
                                    $n_form->version,
                                    $n_form_flight,
                                    6,
                                    6
                                );

                                //******************** Назначенная группа start ********************//
                                if (isset($data['assigned_role_ids'])) {
                                    NFormFlightAgreementSign::where('n_form_flight_id', $data['n_form_flight_id'])
                                        ->where('approval_group_id', 6)
                                        ->delete();

                                    foreach ($data['assigned_role_ids'] as $assigned_role_id) {
                                        $group = Role::find($assigned_role_id);
                                        if (!is_null($group)) {
                                            if ($group->approval_group->id === 6) {
                                                NFormFlightAgreementSign::create([
                                                    'n_form_flight_id' => $data['n_form_flight_id'],
                                                    'approval_group_id' => 6,
                                                    'n_form_flight_sign_id' => 12, // В обработке
                                                    'role_id' => $assigned_role_id
                                                ]);
                                            } #TODO error Переданная роль не входит в группу назначаемых
                                        } #TODO error Назначаемая роль не существует
                                    }
                                } #TODO error Назначаемые роли не переданы
                                //******************** Назначенная группа end ********************//

                                return response()
                                    ->json([
                                        'status' => true,
                                        'status_id' => 6,
                                        'role_id' => 6,
                                        'n_form_flight_id' => $n_form_flight->n_form_flight_id,
                                        'message' => 'ОК'
                                    ], 200, [], JSON_UNESCAPED_UNICODE);

                            default:
                                return $this->errorMessage('Статус не определен', 6, $n_form_flight);
                        }

                    default:
                        return $this->errorMessage('Знак согласования для группы не определен', 6, $n_form_flight);
                }

//******************** Условная группа 5 ********************//
            case 5:
                // Знак Согласования
                switch ($data['n_form_flight_sign_id']) {

                    //******************** Условная группа Отложить  ********************//
                    case 7: // Отложить

                        // Статус Рейса
                        switch (true) {
                            case $this->checkStatusFlights($n_form_flight):
                                if (!isset($data['role_id'])) return $this->errorMessage('Роль юзера не передана', $data['role_id'], $n_form_flight);

//                                $this->signIsUsed($n_form, $data['role_id'], 5);

//                                // Отклонил или согласовал Начальник РА
//                                $this->isRaAgreed($n_form_flight);

                                //***** Обновление формы, чтобы в истории можно было открыть любую версию с сохранённым состоянием *****//
                                $n_form = $this->getUpdatedNForm($n_form->id_pakus);
                                $n_form_flight = $this->getFlightByGlobalObjectId($n_form_flight->n_form_flight_global_id, $n_form);

                                // Присвоенные Знаки Согласования
                                $saved_signs = $this->findNFormFlightAgreementSign($n_form_flight->n_form_flight_id);

                                // Сохранение знака
                                $this->sign->save($saved_signs, 5, 7, $data['role_id']); // Условная группа | Отложить

                                // Статус и История
                                $this->history->save(
                                    $n_form->id_pakus,
                                    $n_form->version,
                                    $n_form_flight,
                                    5,
                                    $data['role_id']
                                );

                                return response()
                                    ->json([
                                        'status' => true,
                                        'status_id' => 5,
                                        'role_id' => $data['role_id'],
                                        'n_form_flight_id' => $n_form_flight->n_form_flight_id,
                                        'message' => 'ОК'
                                    ], 200, [], JSON_UNESCAPED_UNICODE);

                            default:
                                return $this->errorMessage('Статус не определен', $data['role_id'], $n_form_flight);
                        }


                    //******************** Условная группа Возражаю  ********************//
                    case 13: // Возражаю

                        // Статус Рейса
                        switch (true) {
                            case $this->checkStatusFlights($n_form_flight):
                                if (!isset($data['role_id'])) return $this->errorMessage('Роль юзера не передана', $data['role_id'], $n_form_flight);

//                                $this->signIsUsed($n_form, $data['role_id'], 14);

//                                // Отклонил или согласовал Начальник РА
//                                $this->isRaAgreed($n_form_flight);

                                //***** Обновление формы, чтобы в истории можно было открыть любую версию с сохранённым состоянием *****//
                                $n_form = $this->getUpdatedNForm($n_form->id_pakus);
                                $n_form_flight = $this->getFlightByGlobalObjectId($n_form_flight->n_form_flight_global_id, $n_form);

                                // Присвоенные Знаки Согласования
                                $saved_signs = $this->findNFormFlightAgreementSign($n_form_flight->n_form_flight_id);

                                // Сохранение знака
                                $this->sign->save($saved_signs, 5, 13, $data['role_id']); // Условная группа | Возражаю

                                // Статус и История
                                $this->history->save(
                                    $n_form->id_pakus,
                                    $n_form->version,
                                    $n_form_flight,
                                    14,
                                    $data['role_id']
                                );

                                return response()
                                    ->json([
                                        'status' => true,
                                        'status_id' => 14,
                                        'role_id' => $data['role_id'],
                                        'n_form_flight_id' => $n_form_flight->n_form_flight_id,
                                        'message' => 'ОК'
                                    ], 200, [], JSON_UNESCAPED_UNICODE);

                            default:
                                return $this->errorMessage('Статус не определен', $data['role_id'], $n_form_flight);
                        }


                    //******************** Условная группа Возражений нет  ********************//
                    case 14: // Возражений нет

                        // Статус Рейса
                        switch (true) {
                            case $this->checkStatusFlights($n_form_flight):
                                if (!isset($data['role_id'])) return $this->errorMessage('Роль юзера не передана', $data['role_id'], $n_form_flight);

//                                $this->signIsUsed($n_form, $data['role_id'], 15);

//                                // Отклонил или согласовал Начальник РА
//                                $this->isRaAgreed($n_form_flight);

                                //***** Обновление формы, чтобы в истории можно было открыть любую версию с сохранённым состоянием *****//
                                $n_form = $this->getUpdatedNForm($n_form->id_pakus);
                                $n_form_flight = $this->getFlightByGlobalObjectId($n_form_flight->n_form_flight_global_id, $n_form);

                                // Присвоенные Знаки Согласования
                                $saved_signs = $this->findNFormFlightAgreementSign($n_form_flight->n_form_flight_id);

                                // Сохранение знака
                                $this->sign->save($saved_signs, 5, 14, $data['role_id']); // Условная группа | Возражений нет

                                // Статус и История
                                $this->history->save(
                                    $n_form->id_pakus,
                                    $n_form->version,
                                    $n_form_flight,
                                    15,
                                    $data['role_id']
                                );

                                return response()
                                    ->json([
                                        'status' => true,
                                        'status_id' => 15,
                                        'role_id' => $data['role_id'],
                                        'n_form_flight_id' => $n_form_flight->n_form_flight_id,
                                        'message' => 'ОК'
                                    ], 200, [], JSON_UNESCAPED_UNICODE);

                            default:
                                return $this->errorMessage('Статус не определен', $data['role_id'], $n_form_flight);
                        }
                }
                return $this->errorMessage('Знак согласования для группы не определен', $data['role_id']);

//******************** Назначенная группа 6 ********************//
            case 6:
                // Знак Согласования
                switch ($data['n_form_flight_sign_id']) {


                    //******************** Назначенная группа Согласовать  ********************//
                    case 2: // Согласовать

                        // Статус Рейса
                        switch (true) {
                            case $this->checkStatusFlights($n_form_flight):
                                if (!isset($data['role_id'])) return $this->errorMessage('Роль юзера не передана', $data['role_id'], $n_form_flight);

//                                $this->signIsUsed($n_form, $data['role_id'], 7);

//                                // Отклонил или согласовал Начальник РА
//                                $this->isRaAgreed($n_form_flight);

                                //***** Обновление формы, чтобы в истории можно было открыть любую версию с сохранённым состоянием *****//
                                $n_form = $this->getUpdatedNForm($n_form->id_pakus);
                                $n_form_flight = $this->getFlightByGlobalObjectId($n_form_flight->n_form_flight_global_id, $n_form);

                                // Присвоенные Знаки Согласования
                                $saved_signs = $this->findNFormFlightAgreementSign($n_form_flight->n_form_flight_id);

                                // Сохранение знака
                                $this->sign->save($saved_signs, 6, 2, $data['role_id']); // Назначенная группа | Согласовать

                                // Статус и История
                                $this->history->save(
                                    $n_form->id_pakus,
                                    $n_form->version,
                                    $n_form_flight,
                                    7,
                                    $data['role_id']
                                );

                                return response()
                                    ->json([
                                        'status' => true,
                                        'status_id' => 7,
                                        'role_id' => $data['role_id'],
                                        'n_form_flight_id' => $n_form_flight->n_form_flight_id,
                                        'message' => 'ОК'
                                    ], 200, [], JSON_UNESCAPED_UNICODE);

                            default:
                                return $this->errorMessage('Статус не определен', $data['role_id'], $n_form_flight);
                        }


                    //******************** Назначенная группа Отложить  ********************//
                    case 7: // Отложить

                        // Статус Рейса
                        switch (true) {
                            case $this->checkStatusFlights($n_form_flight):
                                if (!isset($data['role_id'])) return $this->errorMessage('Роль юзера не передана', $data['role_id'], $n_form_flight);

//                                $this->signIsUsed($n_form, $data['role_id'], 5);

//                                // Отклонил или согласовал Начальник РА
//                                $this->isRaAgreed($n_form_flight);

                                //***** Обновление формы, чтобы в истории можно было открыть любую версию с сохранённым состоянием *****//
                                $n_form = $this->getUpdatedNForm($n_form->id_pakus);
                                $n_form_flight = $this->getFlightByGlobalObjectId($n_form_flight->n_form_flight_global_id, $n_form);

                                // Присвоенные Знаки Согласования
                                $saved_signs = $this->findNFormFlightAgreementSign($n_form_flight->n_form_flight_id);

                                // Сохранение знака
                                $this->sign->save($saved_signs, 6, 7, $data['role_id']); // Назначенная группа | Отложить

                                // Статус и История
                                $this->history->save(
                                    $n_form->id_pakus,
                                    $n_form->version,
                                    $n_form_flight,
                                    5,
                                    $data['role_id']
                                );

                                return response()
                                    ->json([
                                        'status' => true,
                                        'status_id' => 5,
                                        'role_id' => $data['role_id'],
                                        'n_form_flight_id' => $n_form_flight->n_form_flight_id,
                                        'message' => 'ОК'
                                    ], 200, [], JSON_UNESCAPED_UNICODE);

                            default:
                                return $this->errorMessage('Статус не определен', $data['role_id'], $n_form_flight);
                        }


                    //******************** Назначенная группа Вернуть  ********************//
                    case 6: // Вернуть

                        // Статус Рейса
                        switch (true) {
                            case $this->checkStatusFlights($n_form_flight):
                                if (!isset($data['role_id'])) return $this->errorMessage('Роль юзера не передана', $data['role_id'], $n_form_flight);

//                                $this->signIsUsed($n_form, $data['role_id'], 9);

//                                // Отклонил или согласовал Начальник РА
//                                $this->isRaAgreed($n_form_flight);

                                //***** Обновление формы, чтобы в истории можно было открыть любую версию с сохранённым состоянием *****//
                                $n_form = $this->getUpdatedNForm($n_form->id_pakus);
                                $n_form_flight = $this->getFlightByGlobalObjectId($n_form_flight->n_form_flight_global_id, $n_form);

                                // Присвоенные Знаки Согласования
                                $saved_signs = $this->findNFormFlightAgreementSign($n_form_flight->n_form_flight_id);

                                // Сохранение знака
                                $this->sign->save($saved_signs, 6, 6, $data['role_id']); // Назначенная группа | Вернуть

                                // Статус и История
                                $this->history->save(
                                    $n_form->id_pakus,
                                    $n_form->version,
                                    $n_form_flight,
                                    9,
                                    $data['role_id']
                                );

                                return response()
                                    ->json([
                                        'status' => true,
                                        'status_id' => 9,
                                        'role_id' => $data['role_id'],
                                        'n_form_flight_id' => $n_form_flight->n_form_flight_id,
                                        'message' => 'ОК'
                                    ], 200, [], JSON_UNESCAPED_UNICODE);

                            default:
                                return $this->errorMessage('Статус не определен', $data['role_id'], $n_form_flight);
                        }

                    default:
                        return $this->errorMessage('Знак согласования для группы не определен', $data['role_id'], $n_form_flight);
                }
        }
        return $this->errorMessage('Знак согласования для группы не определен', $data['role_id']);
    }

//******************** Рейс Аннулирован (обновление) ********************//
    public function nFormAgreementCorrect(NFormAgreementCorrectRequest $request): JsonResponse
    {
        // Аннулировать
        $data = $request->all();

        $data["n_form_old"] = 3;
        $data["n_form_new"] = 4;

        // FLIGHT NEW
        $n_form_flight_new = $this->searchFlight($data["n_form_flights"][0]);

        // Н NEW
        $n_form_new = $this->searchNForm($n_form_flight_new);

        // Н OLD
        $n_form_old = NForm::where('n_forms_id', $data["n_form_old"])
            ->firstWhere('is_latest', 0);

        if (!$n_form_old) return $this->errorMessage('Заявка прошлой версии не найдена');

        // OLD FLIGHTS
        $flights_old = $n_form_old->flights;
        if (!$flights_old) return $this->errorMessage('К прошлой версии заявки не прикреплены рейсы');


        /****** START COPY ******/


        // OLD FLIGHTS COPY
        $flights_old->each(function ($flight_old) use ($n_form_new) {

            // COPY FLIGHTS
            $flight_new = $flight_old->replicate();

            unset($flight_new['cargos_sum_weight']);

            // new Н id
            $flight_new->n_forms_id = $n_form_new->n_forms_id;

            // save
            $flight_new->push();

            $new_n_form_flight_id = $flight_new->n_form_flight_id;

            // OLD SIGNS COPY
            $old_agreement_signs = $flight_old->agreementSigns;

            if ($old_agreement_signs->count()) {

                $old_agreement_signs->each(function ($old_agreement_sign) use ($new_n_form_flight_id) {
                    $new_sign = $old_agreement_sign->replicate();
                    $new_sign->n_form_flight_id = $new_n_form_flight_id;
                    $new_sign->push();
                });
            }
        });

        $n_form_flight_ids = collect($data['n_form_flights']);

        if ($n_form_flight_ids->count()) {

            $n_form_flight_ids->each(function ($n_form_flight_id) {

                $n_form_flight = $this->searchFlight($n_form_flight_id);

                $n_form_flight->updateStatus('Черновик', 1);
            });
        }

        return response()
            ->json([
                'status' => true,
                'message' => 'ОК'
            ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    private function checkStatusFlights($n_form_flight): bool
    {
        // Знак Заявитель и существует
        if (
            NFormFlightStatus::query()
                ->whereNotIn('id', [1])
                ->where('id', $n_form_flight['status_id'])
                ->exists()
        ) {
            return true;
        } else return false;
    }

//******************** Условные и Назначенные группы start ********************//
    private function approvalGroup(array $data): void
    {
        if (isset($data['assigned_role_ids'])) {
            foreach ($data['assigned_role_ids'] as $assigned_role_id) {
                $group = Role::find($assigned_role_id);
                if ($group && $group->approval_group->id === 6) { // если это условная группа
                    try {
                        NFormFlightAgreementSign::updateOrCreate([
                            'n_form_flight_id' => $data['n_form_flight_id'],
                            'approval_group_id' => 6,
                            'n_form_flight_sign_id' => 12, // В обработке
                            'role_id' => $assigned_role_id
                        ]);
                    } catch (QueryException $queryException) {
                        $this->errorMessage($queryException->getMessage());
                        return;
                    }

                } else $this->errorMessage('Назначаемая роль не существует или Переданная роль не входит в группу назначаемых');
            }
        } else $this->errorMessage('Назначаемые роли не переданы');
    }

    /**
     * Добавление условных групп
     *
     * @param $data - Request
     * @param $group - Approval_group model
     */
    private function conditionalGroup(
        $data,
        $group
    )
    {
        $n_form_flight_agreement_sign = NFormFlightAgreementSign::where('n_form_flight_id', $data['n_form_flight_id'])
            ->where('role_id', $group['role_id'])
            ->count();

        /** Не сохранять больше 0 */
        if ($n_form_flight_agreement_sign === 0) {
            NFormFlightAgreementSign::create([
                'n_form_flight_id' => $data['n_form_flight_id'],
                'role_id' => $group['role_id'],
                'approval_group_id' => $group['approval_group_id'],
                'n_form_flight_sign_id' => 12, // В обработке
            ]);
        }
    }

//******************** Условные и назначенные группы end ********************//

//******************** Поиск по БД ********************//
    /** Рейс */
    private function searchFlight($n_form_flight_id)
    {
        $n_form_flight = NFormFlight::firstWhere('n_form_flight_id', $n_form_flight_id);

        if (is_null($n_form_flight)) $this->exception(['status' => false, 'message' => 'Такого рейса нет']);
        return $n_form_flight;
    }

    /** NForm */
    private function searchNForm($n_form_flight)
    {
        $n_form = $n_form_flight->nForm;

        if (is_null($n_form)) $this->exception(['status' => false, 'message' => 'Рейс к заявке не прикреплен']);
        if (is_null($n_form->version)) $this->exception(['status' => false, 'message' => 'Отсутствует параметр version у заявки']);
        return $n_form;
    }


    // Сохраненные знаки согласования Рейса
    private function findNFormFlightAgreementSign($n_form_flight_id)
    {
        $saved_signs = NFormFlightAgreementSign::where('n_form_flight_id', $n_form_flight_id)->get();

        if (!$saved_signs) $this->exception('Знаки согласования не найдены');
        return $saved_signs;
    }

//******************** Поиск по БД end ********************//


    /**
     * Error JSON message
     *
     * @param $message
     * @param $role_id
     * @param null $flight
     * @return JsonResponse
     */
    private function errorMessage(
        $message,
        $role_id = null,
        $flight = null
    ): JsonResponse
    {
        $response['status'] = false;
        if (!is_null($flight)) {
            $response['status_id'] = $flight->status_id;
            $response['role_id'] = $role_id;
            $response['n_form_flight_id'] = $flight['n_form_flight_id'];
        }

        $response['message'] = $message;

        return Responses::errorMessage($response);
    }

    public function exception($data)
    {
        throw new HttpResponseException(
            response()->json([
                $data
            ], JsonResponse::HTTP_UNPROCESSABLE_ENTITY,
                Headers::accessControlAllowOrigin(),
                JSON_UNESCAPED_UNICODE
            )
        );
    }

    private function getStatesId($flightId): ?int
    {
        $statesId = null;

        try {
            $statesId = NFormFlight::find($flightId)->nForm->airline->STATES_ID;
        } catch (\Exception $exception) {
            $this->exception(['message' => $exception->getMessage()]);
        }

        return $statesId;
    }

    private function getFlightByGlobalObjectId($n_form_flight_global_id, NForm $updatedNForm)
    {
        return $updatedNForm->flights()->firstWhere('n_form_flight_global_id', $n_form_flight_global_id);
    }

    private function getUpdatedNForm($id_pakus)
    {
        return app(NFormController::class)->updateFromArray(app(NFormController::class)->localGetNForm($id_pakus), false)['updatedForm'];
    }

    private function approvalGroupCheck($data): bool
    {
        $checked = false;

        if (isset($data['assigned_role_ids'])) {
            foreach ($data['assigned_role_ids'] as $assigned_role_id) {
                $group = Role::find($assigned_role_id);
                if ($group && $group->approval_group->id === 6) { // если это условная группа
                    $checked = true;
                }
            }
        }

        return $checked;
    }

    private function isRaAgreed($n_form_flight): bool // согласовал ли РА
    {

        $isRAAgreed = $n_form_flight->finalRAStatus($n_form_flight->n_form_flight_global_id);
        if ($isRAAgreed) {
            if ($isRAAgreed->status_id === 10 || $isRAAgreed->status_id === 11) {
                return true;
            }
        }
        return false;
    }

    private function lastRAStatus($n_form_flight) // согласовал ли РА
    {
        $lastRAStatus = NFormFlight::finalRAStatus($n_form_flight->n_form_flight_global_id);
        if (!is_null($lastRAStatus)) {
            return $lastRAStatus->status_id;
        }
        return null;
    }

    private function lastSavedRoleStatus(int $id_pakus, int $role_id) // согласовал ли РА
    {
        $lastSavedRoleStatus = NFormFlightNFormFlightStatus::lastSavedRoleStatus($id_pakus, $role_id);
        if ($lastSavedRoleStatus) {
            return $lastSavedRoleStatus->n_form_flight_status_id;
        }
        return null;
    }

    private function signIsUsed($n_form, int $role_id, int $status_id)
    {
        $lastRoleStatus = $this->lastSavedRoleStatus($n_form->id_pakus, $role_id);
        if($lastRoleStatus === $status_id) {
            $this->exception(['status' => false, 'message' => 'Знак согласования уже присвоен', 'role_id' => 2]);
        }
    }
}
