<?php

declare(strict_types=1);

namespace Butler\Reply;

interface ReplyInterface
{
    public const UC1 = 'UC-1';
    public const UC2 = 'UC-2';
    public const UC3 = 'UC-3';
    public const UC4 = 'UC-4';
    public const UC5 = 'UC-5';

    public const HINT_YES_NO = 'скажите да или нет.';
    public const BUTTON_YES_NO_YES = 'да';
    public const BUTTON_YES_NO_NO = 'нет';
    public const HINT_AGREE_NEXT = 'скажите принято или дальше.';
    public const BUTTON_AGREE_NEXT_AGREE = 'принято';
    public const BUTTON_AGREE_NEXT_NEXT = 'дальше';

    public const MESSAGE_HOW_TO_END = 'позовите меня, когда закончите, и я предложу, что делать дальше.';
    public const MESSAGE_CARRY_ON = 'жалаете заняться этим сейчас?';
    public const MESSAGE_NOT_AUTHORIZED = 'авторизуйтесь в Яндекс, чтобы продолжить.';
    public const MESSAGE_EMPTY_LIST = 'список работ пуст. заполните список работ.';

    public const COMMAND_YES = 'да';
    public const COMMAND_NO = 'нет';

    public function handle(array $event, array $jobs): array;
}
