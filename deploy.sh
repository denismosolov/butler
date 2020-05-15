#! /bin/bash

# Запускать каждый раз при изменениях в коде для заливки новой версии в Яндекс.Облако

# Не заливай, если нет conf/jobs.php
if [ ! -f "$(pwd)/conf/jobs.php" ]; then
  echo 'Error: conf/jobs.php is missing. Create conf/jobs.php from conf/jobs.php.dist.'
  exit 1
fi

# Информируй о несоответствии PSR-12
./vendor/bin/phpcs --standard=PSR12 src/ tests/

# Не создавай версию, если тесты не проходят
./vendor/bin/phpunit

if [ $? -eq 0 ]; then
  rm -f butler.zip
  zip butler.zip index.php src/Application.php conf/jobs.php
  # Справка https://cloud.yandex.ru/docs/functions/operations/function/version-manage
  yc serverless function version create \
      --function-name=butler \
      --runtime php74 \
      --entrypoint index.main \
      --memory 128m \
      --execution-timeout 1s \
      --source-path ./butler.zip
else
  echo "Error: unit tests failed. Fix bugs or tests."
  exit 1
fi