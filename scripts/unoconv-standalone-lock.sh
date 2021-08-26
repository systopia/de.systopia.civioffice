#!/bin/bash
args="$@"

# Makes sure we exit if flock fails.
set -e

(
  # Wait for lock on /var/www/.myscript.exclusivelock (fd 200) for 120 seconds
  flock -x -w 120 200

  echo $$ working with lock
  #echo "debug arguments:" ${args}
  unoconv ${args}
  echo $$ done with lock

) 200>~/.config/.uno-lock # this folder needs to be writable for www-data
