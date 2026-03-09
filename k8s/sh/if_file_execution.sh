#! /bin/bash

set -ex

# $? - last command status code
# &>/dev/null - stdout (1) и stderr (2) вывести в /dev/null == >/dev/null 2>&1
if [ $(./sh/cwd.sh &>/dev/null; echo $?) == 0 ]; then
  echo 'SUCCESS STATUS CODE'
else
  >&2 echo 'FAILURE STATUS CODE'
  exit 1
fi

exit 0