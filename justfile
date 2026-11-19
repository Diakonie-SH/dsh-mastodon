name := 'dsh-mastodon'

_list:
    just -l

ddev-clean:
    if ddev ls -j | jq '.raw | map(select(.name == "{{name}}"))[0].name' -e; then \
      ddev stop --unlist --remove-data --omit-snapshot {{name}}; \
    fi
    rm -rf .ddev .Build
    rm -f composer.lock

_ddev-dirs:
    mkdir .ddev

_install-typo3:
    ddev exec vendor/bin/typo3 install:setup -vvv \
      --no-interaction \
      --database-driver=pdo_mysql \
      --database-user-name=db \
      --database-user-password=db \
      --database-host-name=db \
      --database-port=3306 \
      --database-name=db \
      --use-existing-database \
      --admin-user-name=admin \
      --admin-password=Admin%123 \
      --site-setup-type=site \
      --site-base-url=https://{{name}}.ddev.site/ \
      --site-name="DSH Mastodon Test"

_ddev-config php-version env:
    ddev config \
      --php-version={{php-version}} \
      --project-type=php \
      --project-name={{name}} \
      --docroot=.Build/public \
      --web-working-dir="/var/www/html/.Build" \
      --web-environment="{{env}}"

_create-typo3-ddev php-version typo3-version: ddev-clean _ddev-dirs
    just _ddev-config {{php-version}} TYPO3_CONTEXT=Development,TYPO3_PATH_APP=/var/www/html/.Build
    ddev start
    mkdir -p .Build/config/system
    cp Build/composer.json .Build/composer.json
    # narrow TYPO3 version
    ddev exec composer require typo3/cms-core:{{typo3-version}}
    cp Build/config/system/additional.php .Build/config/system/additional.php
    just _install-typo3

ddev-13-4:
    just _create-typo3-ddev 8.3 13.4.40
ddev-14-3:
    just _create-typo3-ddev 8.4 14.3.12
