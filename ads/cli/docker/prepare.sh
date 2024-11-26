#!/bin/bash -e

wget --no-cache -q -O - https://raw.githubusercontent.com/cosyses/app/master/setup.sh | bash

phpVersion=$(php -v 2>/dev/null | grep --only-matching --perl-regexp "(PHP )\d+\.\\d+\.\\d+" | cut -c 5-7)

if [[ "${phpVersion}" == "7.1" ]]; then
  composerVersion="2.2"
elif [[ "${phpVersion}" == "7.2" ]]; then
  composerVersion="2.6"
elif [[ "${phpVersion}" == "7.3" ]]; then
  composerVersion="2.6"
elif [[ "${phpVersion}" == "7.4" ]]; then
  composerVersion="2.6"
elif [[ "${phpVersion}" == "8.0" ]]; then
  composerVersion="2.6"
elif [[ "${phpVersion}" == "8.1" ]]; then
  composerVersion="2.6"
elif [[ "${phpVersion}" == "8.2" ]]; then
  composerVersion="2.6"
elif [[ "${phpVersion}" == "8.3" ]]; then
  composerVersion="2.6"
else
  >&2 echo "Unsupported PHP version: ${phpVersion}"
  exit 1
fi

install-package "php${phpVersion}-curl"

cosyses Composer "${composerVersion}"

rm -rf /var/www/tofex/curl-ftp/composer.lock
rm -rf /var/www/tofex/curl-ftp/vendor
