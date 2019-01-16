
.PHONY: all server test

all: setup

setup:
	composer install

server: setup
	php -S localhost:8000 --docroot=public/

test:
	php run-tests.php
