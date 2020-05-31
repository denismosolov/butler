<?php

declare(strict_types=1);

namespace Butler\Reply;

class OfferJob implements ReplyInterface
{
    public function handle(array $event, array $jobs): array
    {
        $uc1 = ! isset($event['state']['user']['job_index']) ||
               ! isset($event['state']['user']['job_state']);
        $uc2 = $event['state']['user']['job_state'] === self::UC2 &&
                ! (
                    isset($event['request']['nlu']['intents']['job.accept.yes']) ||
                    isset($event['request']['nlu']['intents']['YANDEX.CONFIRM'])
                ) &&
                ! (
                    isset($event['request']['nlu']['intents']['job.accept.no']) ||
                    isset($event['request']['nlu']['intents']['YANDEX.REJECT'])
                );
        if ($uc1 || $uc2) {
            $response  = [
                'response' => [],
                'version' => '1.0',
            ];
            $index = (int) $event['state']['user']['job_index'];
            $text = $jobs[$index]['brief'] . ' ' . self::HINT_AGREE_NEXT;
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
