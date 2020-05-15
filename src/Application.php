<?php

declare(strict_types=1);

namespace Butler;

class Application
{
    public const UC1 = 'UC-1';
    public const UC2 = 'UC-2';
    public const UC3 = 'UC-3';
    public const UC4 = 'UC-4';
    public const UC5 = 'UC-5';

    public const HINT_YES_NO = 'скажите да или нет.';
    public const HINT_AGREE_NEXT = 'скажите принято или дальше.';

    public const MESSAGE_HOW_TO_END = 'позовите меня, когда закончите, и я предложу, что делать дальше.';
    public const MESSAGE_CARRY_ON = 'жалаете заняться этим сейчас?';
    public const MESSAGE_NOT_AUTHORIZED = 'авторизуйтесь в Яндекс, чтобы продолжить.';
    public const MESSAGE_EMPTY_LIST = 'список работ пуст. заполните список работ.';

    public const COMMAND_DECLINE = 'дальше';
    public const COMMAND_ACCEPT = 'принято';
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
        $response  = [
            'response' => [],
            'version' => '1.0',
        ];
        if (! isset($this->event['session']['user'])) {
            // not authorized
            $response['response'] = [
                'text' => self::MESSAGE_NOT_AUTHORIZED,
                'end_session' => true,
            ];
        } elseif (count($this->jobs) < 2) {
            // empty job list
            $response['response'] = [
                'text' => self::MESSAGE_EMPTY_LIST,
                'end_session' => true,
            ];
        } elseif (
            ! isset($this->event['state']['user']['job_index']) &&
                   ! isset($this->event['state']['user']['job_state'])
        ) {
            if ($this->event['session']['new']) {
                // UC-1
                $response['response'] = [
                    'text' => $this->jobs[0]['brief'] . ' ' . self::HINT_AGREE_NEXT,
                    'end_session' => false,
                ];
                $response['user_state_update'] = [
                    'job_index' => 0,
                    'job_state' => self::UC2,
                ];
            } else {
                // @todo: uknown error
            }
        } elseif ($this->event['state']['user']['job_state'] === self::UC2) {
            if ($this->event['request']['command'] === self::COMMAND_ACCEPT) {
                // accept
                $index = (int) $this->event['state']['user']['job_index'];
                $response['response'] = [
                    'text' => self::MESSAGE_HOW_TO_END,
                    'end_session' => true,
                ];
                $response['user_state_update'] = [
                    'job_index' => $index,
                    'job_state' => self::UC3,
                ];
            } elseif ($this->event['request']['command'] === self::COMMAND_DECLINE) {
                // UC-2
                // next job index
                $index = (int) $this->event['state']['user']['job_index'];
                if ($index + 1 >= count($this->jobs)) {
                    $index = 0;
                } else {
                    $index = $index + 1;
                }
                $response['response'] = [
                    'text' => $this->jobs[$index]['brief'] . ' ' . self::HINT_AGREE_NEXT,
                    'end_session' => false,
                ];
                $response['user_state_update'] = [
                    'job_index' => $index,
                    'job_state' => self::UC2,
                ];
            } else {
                // reply with a hint
                $index = (int) $this->event['state']['user']['job_index'];
                $response['response'] = [
                    'text' => $this->jobs[$index]['brief'] . ' ' . self::HINT_AGREE_NEXT,
                    'end_session' => false,
                ];
                $response['user_state_update'] = [
                    'job_index' => $index,
                    'job_state' => self::UC2,
                ];
            }
        } elseif ($this->event['state']['user']['job_state'] === self::UC3) {
            // reply with the question
            $index = (int) $this->event['state']['user']['job_index'];
            $response['response'] = [
                'text' => $this->jobs[$index]['question'] . ' ' . self::HINT_YES_NO,
                'end_session' => false,
            ];
            $response['user_state_update'] = [
                'job_index' => $index,
                'job_state' => self::UC4,
            ];
        } elseif ($this->event['state']['user']['job_state'] === self::UC4) {
            if ($this->event['request']['command'] === self::COMMAND_YES) {
                // next job index
                $index = (int) $this->event['state']['user']['job_index'];
                if ($index + 1 >= count($this->jobs)) {
                    $index = 0;
                } else {
                    $index = $index + 1;
                }
                $response['response'] = [
                    'text' => $this->jobs[$index]['brief'] . ' ' . self::HINT_AGREE_NEXT,
                    'end_session' => false,
                ];
                $response['user_state_update'] = [
                    'job_index' => $index,
                    'job_state' => self::UC2,
                ];
            } elseif ($this->event['request']['command'] === self::COMMAND_NO) {
                // ask to proceed the current job
                $index = (int) $this->event['state']['user']['job_index'];
                $response['response'] = [
                    'text' => self::MESSAGE_CARRY_ON,
                    'end_session' => false,
                ];
                $response['user_state_update'] = [
                    'job_index' => $index,
                    'job_state' => self::UC5,
                ];
            } else {
                // reply with a hint
                $index = (int) $this->event['state']['user']['job_index'];
                $response['response'] = [
                    'text' => $this->jobs[$index]['question'] . ' ' . self::HINT_YES_NO,
                    'end_session' => false,
                ];
                $response['user_state_update'] = [
                    'job_index' => $index,
                    'job_state' => self::UC4,
                ];
            }
        } elseif ($this->event['state']['user']['job_state'] === self::UC5) {
            if ($this->event['request']['command'] === self::COMMAND_YES) {
                $index = (int) $this->event['state']['user']['job_index'];
                $response['response'] = [
                    'text' => self::MESSAGE_HOW_TO_END,
                    'end_session' => true,
                ];
                $response['user_state_update'] = [
                    'job_index' => $index,
                    'job_state' => self::UC3,
                ];
            } elseif ($this->event['request']['command'] === self::COMMAND_NO) {
                // next job index
                $index = (int) $this->event['state']['user']['job_index'];
                if ($index + 1 >= count($this->jobs)) {
                    $index = 0;
                } else {
                    $index = $index + 1;
                }
                $response['response'] = [
                    'text' => $this->jobs[$index]['brief'] . ' ' . self::HINT_AGREE_NEXT,
                    'end_session' => false,
                ];
                $response['user_state_update'] = [
                    'job_index' => $index,
                    'job_state' => self::UC2,
                ];
            } else {
                // UC-2 anyway
                $index = (int) $this->event['state']['user']['job_index'];
                $response['response'] = [
                    'text' => $this->jobs[$index]['question'] . ' ' . self::HINT_YES_NO,
                    'end_session' => false,
                ];
                $response['user_state_update'] = [
                    'job_index' => $index,
                    'job_state' => self::UC4,
                ];
            }
        }
        return $response;
    }
}
