#!/bin/sh

msg_file=$1
subject_line=$(head -n1 "$msg_file")

if [ 72 -lt ${#subject_line} ]
then
  echo "subject line is too long (over 72 characters)"
  exit 1
fi

max_len=$(awk 'max_len<length { max_len=length } END { print max_len }' "$msg_file")

if [ 80 -lt "$max_len" ]
then
  echo "body line is too long (over 80 characters)"
  exit 1
fi
