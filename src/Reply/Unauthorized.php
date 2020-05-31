<?php

declare(strict_types=1);

namespace Butler\Reply;

class Unauthorized implements ReplyInterface
{
    public function handle(array $event, array $jobs): array
    {
        $authorized = isset($event['session']['user']);
        if (! $authorized) {
            return [
                'response' => [
                    'text' => 'авторизуйтесь в Яндекс, чтобы продолжить.',
                    'end_session' => true,
                ],
                'version' => '1.0',
            ];
        }

        return [];
    }
}
