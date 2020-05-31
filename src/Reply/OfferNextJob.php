<?php

declare(strict_types=1);

namespace Butler\Reply;

class OfferNextJob implements ReplyInterface
{
    public function handle(array $event, array $jobs): array
    {
        $uc2 = $event['state']['user']['job_state'] === self::UC2 &&
                (
                    isset($event['request']['nlu']['intents']['job.accept.no']) ||
                    isset($event['request']['nlu']['intents']['YANDEX.REJECT'])
                );
        $uc4 = $event['state']['user']['job_state'] === self::UC4 &&
                (
                    isset($event['request']['nlu']['intents']['job.done.yes']) ||
                    isset($event['request']['nlu']['intents']['YANDEX.CONFIRM'])
                );
        $uc5 = $event['state']['user']['job_state'] === self::UC5 &&
               $event['request']['command'] === self::COMMAND_NO;
        if ($uc2 || $uc4 || $uc5) {
            $index = (int) $event['state']['user']['job_index'];
            if ($index + 1 >= count($jobs)) {
                $index = 0;
            } else {
                $index = $index + 1;
            }
            $text = $jobs[$index]['brief'];

            $response  = [
                'response' => [],
                'version' => '1.0',
            ];
            $response['response'] = [
                'text' => $text,
                'end_session' => false,
                'buttons' => [
                    [
                        'title' => self::BUTTON_AGREE_NEXT_AGREE,
                        'hide' => true,
                    ],
                    [
                        'title' => self::BUTTON_AGREE_NEXT_NEXT,
                        'hide' => true,
                    ],
                ],
            ];
            $response['session_state'] = [
                'last_response' => [
                    'text' => $text,
                ],
            ];
            $response['user_state_update'] = [
                'job_index' => $index,
                'job_state' => self::UC2,
            ];
            return $response;
        }

        return [];
    }
}
