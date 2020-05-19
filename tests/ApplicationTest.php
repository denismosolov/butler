<?php

declare(strict_types=1);

namespace Butler;

use PHPUnit\Framework\TestCase;
use Butler\Application;

final class ApplicationTest extends TestCase
{
    private const UNKNOWN = 'неизвестная команда';
    /**
     * Default list of jobs for test cases, this can be overwritten in test methods
     */
    private array $jobs = [
        [
            'brief' => 'brief 1',
            'question' => 'question 1',
        ],
        [
            'brief' => 'brief 2',
            'question' => 'question 2',
        ],
        [
            'brief' => 'brief 3',
            'question' => 'question 3',
        ],
    ];

    /**
     * Default event https://yandex.ru/dev/dialogs/alice/doc/protocol-docpage/#request
     */
    private array $event = [
        "meta" => [
            "locale" => "ru-RU",
            "timezone" => "Europe/Moscow",
            "client_id" => "ru.yandex.searchplugin/5.80 (Samsung Galaxy; Android 4.4)",
            "interfaces" => [
                "screen" => [],
                "account_linking" => []
            ]
        ],
        "request" => [
            "command" => "", // override
            "original_utterance" => "", // override
            "type" => "SimpleUtterance",
            "markup" => [
                "dangerous_context" => true
            ],
            "payload" => [],
        ],
        "session" => [
            "message_id" => 0,
            "session_id" => "2eac4854-fce721f3-b845abba-20d60",
            "skill_id" => "3ad36498-f5rd-4079-a14b-788652932056",
            "user_id" => "47C73714B580ED2469056E71081159529FFC676A4E5B059D629A819E857DC2F8",
            "user" => [
                "user_id" => "6C91DA5198D1758C6A9F63A7C5CDDF09359F683B13A18A151FBF4C8B092BB0C2",
                "access_token" => "AgAAAAAB4vpbAAApoR1oaCd5yR6eiXSHqOGT8dT"
            ],
            "application" => [
                "application_id" => "47C73714B580ED2469056E71081159529FFC676A4E5B059D629A819E857DC2F8"
            ],
            "new" => true // override
        ],
        "version" => "1.0"
    ];

    /**
     * Overrides default values
     * Not tested ^^
     */
    private function getEvent(array $mix = []): array
    {
        return array_replace_recursive($this->event, $mix);
    }

    public function testNotAuthorized(): void
    {
        $event = $this->getEvent();
        unset($event['session']['user']);
        $app = new Application();
        $app->setJobs($this->jobs);
        $app->setEvent($event);
        $result = $app->run();

        $this->assertArrayHasKey('response', $result);
        $response = $result['response'];
        $this->assertArrayHasKey('text', $response);
        $this->assertNotEmpty($response['text']);
        $this->assertEquals(Application::MESSAGE_NOT_AUTHORIZED, $response['text']);
        $this->assertArrayHasKey('end_session', $response);
        $this->assertTrue($response['end_session']);
    }

    public function testEmptyJobList(): void
    {
        $event = $this->getEvent();
        $app = new Application();
        $app->setJobs([]);
        $app->setEvent($event);
        $result = $app->run();

        $this->assertArrayHasKey('response', $result);
        $response = $result['response'];
        $this->assertArrayHasKey('text', $response);
        $this->assertNotEmpty($response['text']);
        $this->assertEquals(Application::MESSAGE_EMPTY_LIST, $response['text']);
        $this->assertArrayHasKey('end_session', $response);
        $this->assertTrue($response['end_session']);
    }

    public function testLegacyDataFormat(): void
    {
        $event = $this->getEvent([
            'request' => [
                'command' => '',
                'original_utterance' => '',
            ],
            'state' => [
                'session' => [],
                'user' => [
                    'job_index' => 3,
                    'daily_job' => [
                        'date' => '2020-05-17',
                        'index' => 2,
                    ],
                    'weekly_job' => [
                        'index' => 15,
                    ],
                ],
            ],
        ]);
     
        $app = new Application();
        $app->setJobs($this->jobs);
        $app->setEvent($event);
        $result = $app->run();

        $this->assertArrayHasKey('response', $result);
        $response = $result['response'];
        $this->assertArrayHasKey('text', $response);
        $this->assertNotEmpty($response['text']);
        $this->assertEquals($this->jobs[0]['brief'] . ' ' . Application::HINT_AGREE_NEXT, $response['text']);
        $this->assertArrayHasKey('end_session', $response);
        $this->assertFalse($response['end_session']);

        $this->assertArrayHasKey('user_state_update', $result);
        $user_state_update = $result['user_state_update'];
        $this->assertArrayHasKey('job_index', $user_state_update);
        $this->assertEquals(0, $user_state_update['job_index']);
        $this->assertArrayHasKey('job_state', $user_state_update);
        $this->assertEquals(Application::UC2, $user_state_update['job_state']);
    }

    // UC-1
    public function testFirstCall(): void
    {
        $event = $this->getEvent([
            'request' => [
                'command' => '',
                'original_utterance' => '',
            ],
            'session' => [
                'new' => true,
            ],
            'state' => [
                'session' => [],
                'user' => [],
            ],
        ]);

        $app = new Application();
        $app->setJobs($this->jobs);
        $app->setEvent($event);
        $result = $app->run();

        $this->assertArrayHasKey('response', $result);
        $response = $result['response'];
        $this->assertArrayHasKey('text', $response);
        $this->assertNotEmpty($response['text']);
        $this->assertEquals($this->jobs[0]['brief'] . ' ' . Application::HINT_AGREE_NEXT, $response['text']);
        $this->assertArrayHasKey('end_session', $response);
        $this->assertFalse($response['end_session']);

        $this->assertArrayHasKey('user_state_update', $result);
        $user_state_update = $result['user_state_update'];
        $this->assertArrayHasKey('job_index', $user_state_update);
        $this->assertEquals(0, $user_state_update['job_index']);
        $this->assertArrayHasKey('job_state', $user_state_update);
        $this->assertEquals(Application::UC2, $user_state_update['job_state']);
    }

    // UC-2 3a
    public function testAcceptNextJob(): void
    {
        $index = 1;
        $event = $this->getEvent([
            'request' => [
                'command' => Application::COMMAND_ACCEPT,
                'original_utterance' => Application::COMMAND_ACCEPT,
            ],
            'session' => [
                'new' => false,
            ],
            'state' => [
                'user' => [
                    'job_index' => $index,
                    'job_state' => Application::UC2,
                ],
            ],
        ]);
        $app = new Application();
        $app->setJobs($this->jobs);
        $app->setEvent($event);
        $result = $app->run();

        $this->assertArrayHasKey('response', $result);
        $response = $result['response'];
        $this->assertArrayHasKey('text', $response);
        $this->assertNotEmpty($response['text']);
        $this->assertEquals(Application::MESSAGE_HOW_TO_END, $response['text']);
        $this->assertArrayHasKey('end_session', $response);
        $this->assertTrue($response['end_session']);

        $this->assertArrayHasKey('user_state_update', $result);
        $user_state_update = $result['user_state_update'];
        $this->assertArrayHasKey('job_index', $user_state_update);
        $this->assertEquals($index, $user_state_update['job_index']);
        $this->assertArrayHasKey('job_state', $user_state_update);
        $this->assertEquals(Application::UC3, $user_state_update['job_state']);
    }

    // UC-2 4a
    public function testDeclineNextJob(): void
    {
        $index = 1;
        $nextIndex = 2;
        $event = $this->getEvent([
            'request' => [
                'command' => Application::COMMAND_DECLINE,
                'original_utterance' => Application::COMMAND_DECLINE,
            ],
            'session' => [
                'new' => false,
            ],
            'state' => [
                'user' => [
                    'job_index' => $index,
                    'job_state' => Application::UC2,
                ],
            ],
        ]);
        $app = new Application();
        $app->setJobs($this->jobs);
        $app->setEvent($event);
        $result = $app->run();

        $this->assertArrayHasKey('response', $result);
        $response = $result['response'];
        $this->assertArrayHasKey('text', $response);
        $this->assertNotEmpty($response['text']);
        $this->assertEquals($this->jobs[$nextIndex]['brief'] . ' ' . Application::HINT_AGREE_NEXT, $response['text']);
        $this->assertArrayHasKey('end_session', $response);
        $this->assertFalse($response['end_session']);

        $this->assertArrayHasKey('user_state_update', $result);
        $user_state_update = $result['user_state_update'];
        $this->assertArrayHasKey('job_index', $user_state_update);
        $this->assertEquals($nextIndex, $user_state_update['job_index']);
        $this->assertArrayHasKey('job_state', $user_state_update);
        $this->assertEquals(Application::UC2, $user_state_update['job_state']);
    }

    public function testInvalidNextJob(): void
    {
        $index = 1;
        $event = $this->getEvent([
            'request' => [
                'command' => self::UNKNOWN,
                'original_utterance' => self::UNKNOWN,
            ],
            'session' => [
                'new' => false,
            ],
            'state' => [
                'user' => [
                    'job_index' => $index,
                    'job_state' => Application::UC2,
                ],
            ],
        ]);
        $app = new Application();
        $app->setJobs($this->jobs);
        $app->setEvent($event);
        $result = $app->run();

        $this->assertArrayHasKey('response', $result);
        $response = $result['response'];
        $this->assertArrayHasKey('text', $response);
        $this->assertNotEmpty($response['text']);
        $this->assertEquals($this->jobs[$index]['brief'] . ' ' . Application::HINT_AGREE_NEXT, $response['text']);
        $this->assertArrayHasKey('end_session', $response);
        $this->assertFalse($response['end_session']);

        $this->assertArrayHasKey('user_state_update', $result);
        $user_state_update = $result['user_state_update'];
        $this->assertArrayHasKey('job_index', $user_state_update);
        $this->assertEquals($index, $user_state_update['job_index']);
        $this->assertArrayHasKey('job_state', $user_state_update);
        $this->assertEquals(Application::UC2, $user_state_update['job_state']);
    }

    // UC-3
    public function testJobIsDone(): void
    {
        $index = 1;
        $event = $this->getEvent([
            'request' => [
                'command' => '',
                'original_utterance' => '',
            ],
            'session' => [
                'new' => true,
            ],
            'state' => [
                'user' => [
                    'job_index' => $index,
                    'job_state' => Application::UC3,
                ],
            ],
        ]);
        $app = new Application();
        $app->setJobs($this->jobs);
        $app->setEvent($event);
        $result = $app->run();

        $this->assertArrayHasKey('response', $result);
        $response = $result['response'];
        $this->assertArrayHasKey('text', $response);
        $this->assertNotEmpty($response['text']);
        $this->assertEquals($this->jobs[$index]['question'] . ' ' . Application::HINT_YES_NO, $response['text']);
        $this->assertArrayHasKey('end_session', $response);
        $this->assertFalse($response['end_session']);

        $this->assertArrayHasKey('user_state_update', $result);
        $user_state_update = $result['user_state_update'];
        $this->assertArrayHasKey('job_index', $user_state_update);
        $this->assertEquals($index, $user_state_update['job_index']);
        $this->assertArrayHasKey('job_state', $user_state_update);
        $this->assertEquals(Application::UC4, $user_state_update['job_state']);
    }

    // UC-4 3a
    public function testJobIsDoneYes(): void
    {
        $index = 1;
        $nextIndex = 2;
        $event = $this->getEvent([
            'request' => [
                'command' => 'помыл',
                'original_utterance' => 'помыл',
                'nlu' => [
                    'tokens' => [
                        'помыл',
                    ],
                    'entities' => [],
                    'intents' => [
                      'job.done.yes' => [
                        'slots' => [],
                      ],
                    ]
                ],
            ],
            'session' => [
                'new' => false,
            ],
            'state' => [
                'user' => [
                    'job_index' => $index,
                    'job_state' => Application::UC4,
                ],
            ],
        ]);
        $app = new Application();
        $app->setJobs($this->jobs);
        $app->setEvent($event);
        $result = $app->run();

        $this->assertArrayHasKey('response', $result);
        $response = $result['response'];
        $this->assertArrayHasKey('text', $response);
        $this->assertNotEmpty($response['text']);
        $this->assertEquals($this->jobs[$nextIndex]['brief'] . ' ' . Application::HINT_AGREE_NEXT, $response['text']);
        $this->assertArrayHasKey('end_session', $response);
        $this->assertFalse($response['end_session']);

        $this->assertArrayHasKey('user_state_update', $result);
        $user_state_update = $result['user_state_update'];
        $this->assertArrayHasKey('job_index', $user_state_update);
        $this->assertEquals($nextIndex, $user_state_update['job_index']);
        $this->assertArrayHasKey('job_state', $user_state_update);
        $this->assertEquals(Application::UC2, $user_state_update['job_state']);
    }

    // UC-4 4a
    public function testJobIsDoneNo(): void
    {
        $index = 1;
        $event = $this->getEvent([
            'request' => [
                'command' => Application::COMMAND_NO,
                'original_utterance' => Application::COMMAND_NO,
            ],
            'session' => [
                'new' => false,
            ],
            'state' => [
                'user' => [
                    'job_index' => $index,
                    'job_state' => Application::UC4,
                ],
            ],
        ]);
        $app = new Application();
        $app->setJobs($this->jobs);
        $app->setEvent($event);
        $result = $app->run();

        $this->assertArrayHasKey('response', $result);
        $response = $result['response'];
        $this->assertArrayHasKey('text', $response);
        $this->assertNotEmpty($response['text']);
        $this->assertEquals(Application::MESSAGE_CARRY_ON, $response['text']);
        $this->assertArrayHasKey('end_session', $response);
        $this->assertFalse($response['end_session']);

        $this->assertArrayHasKey('user_state_update', $result);
        $user_state_update = $result['user_state_update'];
        $this->assertArrayHasKey('job_index', $user_state_update);
        $this->assertEquals($index, $user_state_update['job_index']);
        $this->assertArrayHasKey('job_state', $user_state_update);
        $this->assertEquals(Application::UC5, $user_state_update['job_state']);
    }

    // UC-4 5a
    public function testJobIsDoneInvalid(): void
    {
        $index = 1;
        $event = $this->getEvent([
            'request' => [
                'command' => self::UNKNOWN,
                'original_utterance' => self::UNKNOWN,
            ],
            'session' => [
                'new' => false,
            ],
            'state' => [
                'user' => [
                    'job_index' => $index,
                    'job_state' => Application::UC4,
                ],
            ],
        ]);
        $app = new Application();
        $app->setJobs($this->jobs);
        $app->setEvent($event);
        $result = $app->run();

        $this->assertArrayHasKey('response', $result);
        $response = $result['response'];
        $this->assertArrayHasKey('text', $response);
        $this->assertNotEmpty($response['text']);
        $this->assertEquals($this->jobs[$index]['question'] . ' ' . Application::HINT_YES_NO, $response['text']);
        $this->assertArrayHasKey('end_session', $response);
        $this->assertFalse($response['end_session']);

        $this->assertArrayHasKey('user_state_update', $result);
        $user_state_update = $result['user_state_update'];
        $this->assertArrayHasKey('job_index', $user_state_update);
        $this->assertEquals($index, $user_state_update['job_index']);
        $this->assertArrayHasKey('job_state', $user_state_update);
        $this->assertEquals(Application::UC4, $user_state_update['job_state']);
    }

    // UC-5 3a
    public function testCarryOnYes(): void
    {
        $index = 1;
        $event = $this->getEvent([
            'request' => [
                'command' => Application::COMMAND_YES,
                'original_utterance' => Application::COMMAND_YES,
            ],
            'session' => [
                'new' => false,
            ],
            'state' => [
                'user' => [
                    'job_index' => $index,
                    'job_state' => Application::UC5,
                ],
            ],
        ]);
        $app = new Application();
        $app->setJobs($this->jobs);
        $app->setEvent($event);
        $result = $app->run();

        $this->assertArrayHasKey('response', $result);
        $response = $result['response'];
        $this->assertArrayHasKey('text', $response);
        $this->assertNotEmpty($response['text']);
        $this->assertEquals(Application::MESSAGE_HOW_TO_END, $response['text']);
        $this->assertArrayHasKey('end_session', $response);
        $this->assertTrue($response['end_session']);

        $this->assertArrayHasKey('user_state_update', $result);
        $user_state_update = $result['user_state_update'];
        $this->assertArrayHasKey('job_index', $user_state_update);
        $this->assertEquals($index, $user_state_update['job_index']);
        $this->assertArrayHasKey('job_state', $user_state_update);
        $this->assertEquals(Application::UC3, $user_state_update['job_state']);
    }

    // UC-5 4a
    public function testCarryOnNo(): void
    {
        $index = 1;
        $nextIndex = 2;
        $event = $this->getEvent([
            'request' => [
                'command' => Application::COMMAND_NO,
                'original_utterance' => Application::COMMAND_NO,
            ],
            'session' => [
                'new' => false,
            ],
            'state' => [
                'user' => [
                    'job_index' => $index,
                    'job_state' => Application::UC5,
                ],
            ],
        ]);
        $app = new Application();
        $app->setJobs($this->jobs);
        $app->setEvent($event);
        $result = $app->run();

        $this->assertArrayHasKey('response', $result);
        $response = $result['response'];
        $this->assertArrayHasKey('text', $response);
        $this->assertNotEmpty($response['text']);
        $this->assertEquals($this->jobs[$nextIndex]['brief'] . ' ' . Application::HINT_AGREE_NEXT, $response['text']);
        $this->assertArrayHasKey('end_session', $response);
        $this->assertFalse($response['end_session']);

        $this->assertArrayHasKey('user_state_update', $result);
        $user_state_update = $result['user_state_update'];
        $this->assertArrayHasKey('job_index', $user_state_update);
        $this->assertEquals($nextIndex, $user_state_update['job_index']);
        $this->assertArrayHasKey('job_state', $user_state_update);
        $this->assertEquals(Application::UC2, $user_state_update['job_state']);
    }

    // UC-5 5a
    public function testCarryOnInvalid(): void
    {
        $index = 1;
        $event = $this->getEvent([
            'request' => [
                'command' => self::UNKNOWN,
                'original_utterance' => self::UNKNOWN,
            ],
            'session' => [
                'new' => false,
            ],
            'state' => [
                'user' => [
                    'job_index' => $index,
                    'job_state' => Application::UC5,
                ],
            ],
        ]);
        $app = new Application();
        $app->setJobs($this->jobs);
        $app->setEvent($event);
        $result = $app->run();

        $this->assertArrayHasKey('response', $result);
        $response = $result['response'];
        $this->assertArrayHasKey('text', $response);
        $this->assertNotEmpty($response['text']);
        $this->assertEquals($this->jobs[$index]['question'] . ' ' . Application::HINT_YES_NO, $response['text']);
        $this->assertArrayHasKey('end_session', $response);
        $this->assertFalse($response['end_session']);

        $this->assertArrayHasKey('user_state_update', $result);
        $user_state_update = $result['user_state_update'];
        $this->assertArrayHasKey('job_index', $user_state_update);
        $this->assertEquals($index, $user_state_update['job_index']);
        $this->assertArrayHasKey('job_state', $user_state_update);
        $this->assertEquals(Application::UC4, $user_state_update['job_state']);
    }
}
