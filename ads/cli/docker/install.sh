#!/bin/bash -e

function runAsUser()
{
  local userName="${1}"
  shift
  local command="${1}"
  shift
  local parameters=("$@")
  for parameter in "${parameters[@]}"; do
    command+=" \"${parameter}\""
  done
  sudo -H -u "${userName}" bash -c "if [[ \$(which ini-parse | wc -l) -eq 0 ]]; then if [[ -f ~/.profile ]]; then source ~/.profile; elif [[ -f ~/.bash_profile ]]; then source ~/.bash_profile; fi; fi; ${command}"
}

basePath="/var/www/tofex/curl-ftp"

user=$(stat -L -c "%U" "${basePath}")

cd "${basePath}"

runAsUser "${user}" composer install --ansi
