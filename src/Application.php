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
