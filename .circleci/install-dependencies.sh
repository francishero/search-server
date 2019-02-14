#!/bin/bash

cd /var/www/apisearch \
    && composer install -n --prefer-dist --no-suggest --dev
