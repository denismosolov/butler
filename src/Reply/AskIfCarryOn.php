<?php

declare(strict_types=1);

namespace Butler\Reply;

class AskIfCarryOn implements ReplyInterface
{
    public function handle(array $event, array $jobs): array
    {
        $uc4 = $event['state']['user']['job_state'] === self::UC4 &&
                ! (
                    isset($event['request']['nlu']['intents']['job.done.yes']) ||
                    isset($event['request']['nlu']['intents']['YANDEX.CONFIRM'])
                ) &&
                isset($event['request']['nlu']['intents']['job.done.no']);
        if ($uc4) {
            $index = (int) $event['state']['user']['job_index'];
            $text = self::MESSAGE_CARRY_ON;
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
                'job_state' => self::UC5,
            ];
            return $response;
        }

        return [];
    }
}
