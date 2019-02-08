#!/bin/bash

cd /var/www/apisearch \
    && composer install -n --prefer-dist --no-suggest --dev \
    && composer test \
    && composer install -n --prefer-dist --no-suggest --no-dev
