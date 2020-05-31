<?php

declare(strict_types=1);

namespace Butler;

use Butler\Reply\AskIfCarryOn;
use Butler\Reply\AskIfJobIsDone;
use Butler\Reply\Error;
use Butler\Reply\HintWhatToSayOnceJobIsDone;
use Butler\Reply\NewUser;
use Butler\Reply\Unauthorized;
use Butler\Reply\OfferJob;
use Butler\Reply\OfferNextJob;

class Application
{
    public const UC1 = 'UC-1';
    public const UC2 = 'UC-2';
    public const UC3 = 'UC-3';
    public const UC4 = 'UC-4';
    public const UC5 = 'UC-5';

    public const HINT_YES_NO = 'скажите да или нет.';
    public const BUTTON_YES_NO_YES = 'да';
    public const BUTTON_YES_NO_NO = 'нет';
    public const HINT_AGREE_NEXT = 'скажите принято или дальше.';
    public const BUTTON_AGREE_NEXT_AGREE = 'принято';
    public const BUTTON_AGREE_NEXT_NEXT = 'дальше';

    public const MESSAGE_HOW_TO_END = 'позовите меня, когда закончите, и я предложу, что делать дальше.';
    public const MESSAGE_CARRY_ON = 'жалаете заняться этим сейчас?';
    public const MESSAGE_NOT_AUTHORIZED = 'авторизуйтесь в Яндекс, чтобы продолжить.';
    public const MESSAGE_EMPTY_LIST = 'список работ пуст. заполните список работ.';

    public const COMMAND_YES = 'да';
    public const COMMAND_NO = 'нет';

    /**
     * list of user-defined jobs
     * @see conf/jobs.php
     */
    private array $jobs;

    /**
     * request data
     * @see https://yandex.ru/dev/dialogs/alice/doc/protocol-docpage/#request
     */
    private array $event;

    public function __construct()
    {
    }

    /**
     * @param array $jobs - each element of array is array with the following keys:
     * 'brief' (required)
     * 'question' (required)
     */
    public function setJobs(array $jobs): void
    {
        $this->jobs = $jobs;
    }

    /**
     * @param array $event
     * @see $event structure at https://yandex.ru/dev/dialogs/alice/doc/protocol-docpage/#request
     */
    public function setEvent(array $event): void
    {
        $this->event = $event;
    }

    /**
     * @see return value structure https://yandex.ru/dev/dialogs/alice/doc/protocol-docpage/#response
     */
    public function run(): array
    {
        // цепочка обработки события
        $replies = [
            new Error(),
            new Unauthorized(),
            new NewUser(),
            new OfferJob(),
            new HintWhatToSayOnceJobIsDone(),
            new OfferNextJob(),
            new AskIfJobIsDone(),
            new AskIfCarryOn(),
        ];

        // обработка события
        foreach ($replies as $reply) {
            $response = $reply->handle($this->event, $this->jobs);
            if ($response) {
                return $response;
            }
        }

        // в цепочке не нашлось обработчика, значит ошибка
        // @todo уведомление для администратора
        return [
            'response' => [
                'text' => 'произошла какая-то ошибка. завершаю работу.',
                'end_session' => true,
            ],
            'version' => '1.0',
        ];
    }
}
