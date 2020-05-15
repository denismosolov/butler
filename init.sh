#! /bin/bash

# Создай файл со списком работ
cp -n "$(pwd)/conf/jobs.php.dist" "$(pwd)/conf/jobs.php"

# Проверь наличие wget (нужен для установки composer и yc cli).
if ! [ -x "$(command -v wget)" ]; then
  echo 'Error: wget is not installed. Please, install wget.'
  exit 1
fi

# Проверь наличие php.
if ! [ -x "$(command -v php)" ]; then
  echo 'Error: php is not installed. Please, install php7.4.'
  exit 1
fi

# Установи composer
# https://getcomposer.org/doc/faqs/how-to-install-composer-programmatically.md
if ! [ -x "$(pwd)/composer.phar" ]; then
  EXPECTED_CHECKSUM="$(wget -q -O - https://composer.github.io/installer.sig)"
  php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
  ACTUAL_CHECKSUM="$(php -r "echo hash_file('sha384', 'composer-setup.php');")"
  if [ "$EXPECTED_CHECKSUM" != "$ACTUAL_CHECKSUM" ]; then
      echo 'Error: invalid composer installer checksum.'
      rm composer-setup.php
      exit 1
  fi
  php composer-setup.php --install-dir="$(pwd)" --filename=composer.phar
  rm -f composer-setup.php
fi

# Установи пакеты из composer
./composer.phar install

# Установи yc cli в директорию ./yc.
# https://cloud.yandex.ru/docs/cli/operations/install-cli#non-interactive
if ! [ -x "$(pwd)/yc/bin/yc" ]; then
  wget https://storage.yandexcloud.net/yandexcloud-yc/install.sh --quiet -O - | bash -s -- -i "$(pwd)/yc" -n
fi

# Создай или выбери профиль
"$(pwd)/yc/bin/yc" init

# profile_exists=$("$(pwd)/yc/bin/yc" config profile list | awk 'BEGIN {found=false;} /^butler(\s)*(ACTIVE)*$/ {found=true} END {print found;}')
# if ! $profile_exists ; then
#   # Создай и активируй профиль butler.
#   "$(pwd)/yc/bin/yc" config profile create butler
# else
#   # Активируй профиль butler.
#   "$(pwd)/yc/bin/yc" config profile activate butler
#   # TODO: Верни текущий активный профиль после завершения работы скрипта
# fi
# https://cloud.yandex.ru/docs/iam/concepts/authorization/oauth-token#lifetime
# "$(pwd)/yc/bin/yc" config set token "..." # Внимание! Замени на свой!
# "$(pwd)/yc/bin/yc" config set cloud-id "..." # Внимание! Замени на свой!
# "$(pwd)/yc/bin/yc" config set folder-id "..." # Внимание! Замени на свой!
# "$(pwd)/yc/bin/yc" config set compute-default-zone "ru-central1-a"

# TODO: Создай сервисный аккаунт sa-butler
# https://cloud.yandex.ru/docs/cli/operations/authentication/service-account

# Создай фунцию в Яндекс.Облаке.
# https://cloud.yandex.ru/docs/functions/operations/function/function-create
"$(pwd)/yc/bin/yc" serverless function create \
    --name="butler" \
    --description="Обработчик навыка Мой Дворецкий"