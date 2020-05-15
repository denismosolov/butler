# Мой дворецкий — навык для голосового помощника Алиса

Привет, я дворецкий, если захочешь, то поселюсь у тебя и помогу с работой по дому. Расскажи, какую работу ты обычно делаешь, и я буду напоминать тебе, когда понадобится. Со мной тебе не придётся держать огромный список в голове.

Можешь общаться со мной через Яндекс.Станцию, телефон, часы и другие устройства.

## Установка

Сперва достань мой исходный код.
```
git clone https://github.com/denismosolov/butler
cd butler
```
Дальше есть два пути.

### Короткий путь

Eсли у тебя Ubuntu 20.04, то запусти `./init.sh` и переходи к руководству пользователя.

### Длинный путь

1. Установи php 7.4
2. Установи composer в систему https://getcomposer.org/doc/00-intro.md#installation-linux-unix-macos
3. Установи пакеты из composer.json
```
composer install
```
4. Установи yc cli https://cloud.yandex.ru/docs/cli/operations/install-cli
5. Создай профиль для работы с Яндекс.Облако https://cloud.yandex.ru/docs/cli/operations/profile/profile-create
6. Создай функцию в Яндекс.Облаке
```
yc serverless function create \
    --name="butler" \
    --description="Обработчик навыка Мой Дворецкий"
```
7. Создай файл для своего списка работ.
```
cp conf/jobs.php.dist conf/jobs.php
```
_Надеюсь, в будущем этого не потребуется, и ты будешь управлять списком работ голосом._

## Руководство пользователя

### Список работ

Ты можешь рассказать мне о работах, которые мне нужно запомнить, при помощи [языка PHP](https://www.php.net/manual/en/language.types.array.php).

Открой файл `conf/jobs.php` и добавь свой список работ в соотвествии с форматом.

```php
    [
        'brief' => 'помойте оконную сетку в комнате',
        'question' => 'вы помыли оконую сетку в комнате?'
    ],
```

`brief` — то, что мне следует говорить тебе, когда ты попросишь меня предложить тебе работу.

`question` — то, как мне следует поинтересоваться у тебя о завершении работы.

_Да, со мной сложно работать, но люди, которые меня создали, улучшают меня._

### Деплой в Яндекс.Облако

#### Короткий способ
Если у тебя Ubuntu 20.04, то запусти `./deploy.sh`.

#### Чуть более длинный способ

```
zip butler.zip index.php src/Application.php conf/jobs.php
yc serverless function version create \
    --function-name=butler \
    --runtime php74 \
    --entrypoint index.main \
    --memory 128m \
    --execution-timeout 1s \
    --source-path ./butler.zip
```

## Планы на будущее
1. Хочу в каталог навыков Алисы https://dialogs.yandex.ru/store
2. Хочу побороться за премию Алисы  https://dialogs.yandex.ru/prize
