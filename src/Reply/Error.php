<?php

declare(strict_types=1);

namespace Butler\Reply;

class Error implements ReplyInterface
{
    public function handle(array $event, array $jobs): array
    {
        if (count($jobs) < 2) {
            return [
                'response' => [
                    'text' => 'список работ пуст. заполните список работ.',
                    'end_session' => true,
                ],
                'version' => '1.0',
            ];
        }

        return [];
    }
}
