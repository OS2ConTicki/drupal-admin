# OS2ConTicki Drupal admin

An “[admin](https://github.com/OS2ConTicki/OS2ConTicki#implementations)”
implementation for [OS2ConTicki](https://github.com/OS2ConTicki/OS2ConTicki).

## Installation

Create local settings file with database connection:

```sh
cat <<'EOF' > web/sites/default/settings.local.php
<?php
$databases['default']['default'] = [
 'database' => getenv('DATABASE_DATABASE') ?: 'db',
 'username' => getenv('DATABASE_USERNAME') ?: 'db',
 'password' => getenv('DATABASE_PASSWORD') ?: 'db',
 'host' => getenv('DATABASE_HOST') ?: 'mariadb',
 'port' => getenv('DATABASE_PORT') ?: '',
 'driver' => getenv('DATABASE_DRIVER') ?: 'mysql',
 'prefix' => '',
];
EOF
```

```sh
docker-compose up -d
docker-compose exec phpfpm composer install
```

Using `symfony` binary:

```sh
docker-compose up -d
symfony composer install
symfony php vendor/bin/drush --yes site:install minimal --config-dir=../config/sync
symfony local:server:start --port=8888 --daemon
symfony php vendor/bin/drush --uri=https://127.0.0.1:8888 user:login
```

## Fixtures

```sh
symfony php vendor/bin/drush --yes pm:enable conference_fixtures
symfony php vendor/bin/drush content-fixtures:list
symfony php vendor/bin/drush content-fixtures:load
symfony php vendor/bin/drush --yes pm:uninstall content_fixtures
```

## Conference API

```sh
https://127.0.0.1:8888/api/conference
```

## JSON:API

https://www.drupal.org/project/jsonapi
https://www.drupal.org/docs/core-modules-and-themes/core-modules/jsonapi-module/jsonapi

```sh
https://127.0.0.1:8888/jsonapi/node/conference_conference

# https://www.drupal.org/docs/core-modules-and-themes/core-modules/jsonapi-module/filtering
https://127.0.0.1:8888/jsonapi/node/conference_event?filter[event-title][path]=title&filter[event-title][operator]==&filter[event-title][value]=welcome

# Events with speaker data
https://127.0.0.1:8888/jsonapi/node/conference_event?include=field_conference_speakers

# Events for a given speaker
https://127.0.0.1:8888/jsonapi/node/conference_event?filter[field_conference_speakers.id]=7077c4cd-4ab4-4850-aa54-5ec57f82ec69

https://127.0.0.1:8888/jsonapi/node/conference_speaker
```
