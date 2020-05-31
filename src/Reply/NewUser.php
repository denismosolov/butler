<?php

declare(strict_types=1);

namespace Butler\Reply;

class NewUser implements ReplyInterface
{
    public function handle(array $event, array $jobs): array
    {
        $newUser = ! isset($event['state']['user']['job_index']) ||
                   ! isset($event['state']['user']['job_state']);
        if ($newUser) {
            $index = 0;
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
            $response['user_state_update'] = [
                'job_index' => $index,
                'job_state' => self::UC2,
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
