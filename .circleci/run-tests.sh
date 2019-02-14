#!/bin/bash

cd /var/www/apisearch \
    && php vendor/bin/phpunit $1
