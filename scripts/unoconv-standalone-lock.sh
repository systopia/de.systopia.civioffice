#!/bin/bash
args="$@"
FILE="~/.config/.uno-lock" # this path can be changed however the default one usually has sufficient access in oder to work with no additional configuration. Follow the command line instructions if there is no access



if ! [ -w "$FILE" ]
then
   echo "### WARNING: Write permission is NOT granted on $FILE"
   echo "### This script needs +x and and the web servers users e.g. www-data in order to work."
   echo "### $FILE needs to be accessible for whe web servers user in order to obtain a lock"
fi


# Makes sure we exit if flock fails.
set -e

(
  # Wait for lock on /var/www/.myscript.exclusivelock (fd 200) for 120 seconds
  flock -x -w 120 200

  echo $$ working with lock
  #echo "debug arguments:" ${args}
  sleep 4
  unoconv ${args}
  echo $$ done with lock

) 200>"$FILE" # this folder needs to be writable for www-data
