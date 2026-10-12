#!/usr/bin/env bash
# Copies the module into the dev Magento's vendor/ and clears the caches and generated static files.
# The module is copied, not symlinked, because Magento refuses to render templates from outside vendor/ and app/.
# Run it after changing the module. Also run `bin/magento setup:di:compile` after changing PHP classes or di.xml.
set -euo pipefail
cd "$(dirname "$0")"

docker compose exec -T php bash -lc '
  cd /var/www/html
  rm -rf vendor/peerapatfc/magento2-thai-address
  cp -a /module vendor/peerapatfc/magento2-thai-address
  rm -rf vendor/peerapatfc/magento2-thai-address/.git pub/static/frontend var/view_preprocessed/*
  bin/magento cache:flush >/dev/null
  echo synced
'
