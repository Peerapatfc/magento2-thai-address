#!/usr/bin/env bash
# Builds a local Magento 2.4.8 with this module, the way the module was developed and tested.
# Needs Docker. No Adobe account: Magento Open Source is pulled from the public Mage-OS mirror.
#
#   bash dev/install.sh        # first time, takes a few minutes
#   docker compose -f dev/docker-compose.yml exec php bash   # shell inside
#
# Admin: user "admin", password "Admin123-local" (local only). Store URL: http://localhost:8081/
set -euo pipefail
cd "$(dirname "$0")"

docker compose up -d --build

run() { docker compose exec -T php bash -lc "cd /var/www/html && $*"; }

if ! run "test -f vendor/autoload.php"; then
  run "composer create-project --no-interaction --no-progress --repository-url=https://mirror.mage-os.org/ magento/project-community-edition:2.4.8-p4 ."
fi

if ! run "test -f app/etc/env.php"; then
  run "php -d memory_limit=-1 bin/magento setup:install \
    --base-url=http://localhost:8081/ --db-host=db --db-name=magento --db-user=root --db-password=root \
    --admin-firstname=Admin --admin-lastname=User --admin-email=admin@example.com \
    --admin-user=admin --admin-password='Admin123-local' \
    --language=en_US --currency=THB --timezone=Asia/Bangkok --use-rewrites=1 \
    --search-engine=opensearch --opensearch-host=search --opensearch-port=9200 \
    --opensearch-index-prefix=magento2 --opensearch-timeout=15 --cleanup-database"
fi

run "git config --global --add safe.directory /module"
run "composer config repositories.thai-address '{\"type\":\"path\",\"url\":\"/module\",\"options\":{\"symlink\":true}}'"
run "composer require --no-interaction --no-progress peerapatfc/magento2-thai-address:@dev"
run "php -d memory_limit=-1 bin/magento module:enable Peerapatfc_ThaiAddress"
run "php -d memory_limit=-1 bin/magento module:disable Magento_AdminAdobeImsTwoFactorAuth Magento_TwoFactorAuth"
run "php -d memory_limit=-1 bin/magento setup:upgrade"
run "php -d memory_limit=-1 bin/magento setup:di:compile"

echo
echo "Run the unit tests:"
echo "  docker compose -f dev/docker-compose.yml exec -T php bash -lc 'cd /var/www/html && vendor/bin/phpunit -c dev/tests/unit/phpunit.xml.dist /module/Test/Unit'"
echo "Serve the store on :8081:"
echo "  docker compose -f dev/docker-compose.yml exec -d php bash -lc 'cd /var/www/html && php -S 0.0.0.0:8081 -t pub/ phpserver/router.php'"
