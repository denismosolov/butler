<?php

declare(strict_types=1);

namespace Butler\Reply;

class HintWhatToSayOnceJobIsDone implements ReplyInterface
{
    public function handle(array $event, array $jobs): array
    {
        $uc2 = $event['state']['user']['job_state'] === self::UC2 &&
                (
                    isset($event['request']['nlu']['intents']['job.accept.yes']) ||
                    isset($event['request']['nlu']['intents']['YANDEX.CONFIRM'])
                );
        $uc5 = $event['state']['user']['job_state'] === self::UC5 &&
               $event['request']['command'] === self::COMMAND_YES;
        if ($uc2 || $uc5) {
            $response  = [
                'response' => [],
                'version' => '1.0',
            ];
            $index = (int) $event['state']['user']['job_index'];
            $response['response'] = [
                'text' => self::MESSAGE_HOW_TO_END,
                'end_session' => true,
            ];
            $response['user_state_update'] = [
                'job_index' => $index,
                'job_state' => self::UC3,
            ];
            return $response;
        }

        return [];
    }
}
