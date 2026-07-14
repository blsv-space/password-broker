#!/usr/bin/env bash

set -e

uid=${UID:-1001}
gid=${GID:-1001}

search_directory="/permission"

find "$search_directory" -type d -exec chmod 775 {} \;
chown -R "$uid":"$gid" "$search_directory"
