<?php

declare(strict_types=1);

namespace Butler\Reply;

class AskIfJobIsDone implements ReplyInterface
{
    public function handle(array $event, array $jobs): array
    {
        $uc3 = $event['state']['user']['job_state'] === self::UC3;
        $uc4 = $event['state']['user']['job_state'] === self::UC4 &&
        ! (
            isset($event['request']['nlu']['intents']['job.done.yes']) ||
            isset($event['request']['nlu']['intents']['YANDEX.CONFIRM'])
        ) &&
        ! isset($event['request']['nlu']['intents']['job.done.no']);
        $uc5 = $event['state']['user']['job_state'] === self::UC5 &&
               $event['request']['command'] !== self::COMMAND_YES &&
               $event['request']['command'] !== self::COMMAND_NO;
        if ($uc3 || $uc4 || $uc5) {
            $index = (int) $event['state']['user']['job_index'];
            $text = $jobs[$index]['question'];
            if ($uc4) {
                $text .= ' ' . self::HINT_YES_NO;
            }
            if ($uc5) {
                $text .= ' ' . self::HINT_YES_NO;
            }
            $response = [
                'version' => '1.0',
            ];
            $response['response'] = [
                'text' => $text,
                'end_session' => false,
                'buttons' => [
                    [
                        'title' => self::BUTTON_YES_NO_YES,
                        'hide' => true,
                    ],
                    [
                        'title' => self::BUTTON_YES_NO_NO,
                        'hide' => true,
                    ],
                ],
            ];
            $response['user_state_update'] = [
                'job_index' => $index,
                'job_state' => self::UC4,
            ];
            $response['session_state'] = [
                'last_response' => [
                    'text' => $text,
                ],
            ];
            return $response;
        }

        return [];
    }
}
