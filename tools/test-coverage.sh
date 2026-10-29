#!/usr/bin/env sh

set -eu

export XDEBUG_MODE=coverage

phpunit \
    --coverage-html=coverage \
    --coverage-text \
    --coverage-xml=coverage \
    --coverage-clover=coverage/clover.xml
