

all:
	grep '^[a-z]' Makefile

.PHONY: all update-php-cs-fixer.phar update-composer.phar

update-php-cs-fixer.phar:
	php app/php-cs-fixer.phar self-update

install-php-cs-fixer.phar:
	wget https://cs.sensiolabs.org/download/php-cs-fixer-v2.phar -O app/php-cs-fixer.phar

update-composer.phar:
	wget https://getcomposer.org/composer.phar -O composer.phar

path:
	@echo "Kjør \`source app/env.sh\`"
