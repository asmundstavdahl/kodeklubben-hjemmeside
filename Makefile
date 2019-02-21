all:
	grep '^[a-z]' Makefile

.PHONY: all update-php-cs-fixer.phar update-composer.phar assets asset-files asset-images asset-scripts asset-vendor

update-php-cs-fixer.phar:
	php app/php-cs-fixer.phar self-update

install-php-cs-fixer.phar:
	wget https://cs.sensiolabs.org/download/php-cs-fixer-v2.phar -O app/php-cs-fixer.phar

update-composer.phar:
	wget https://getcomposer.org/composer.phar -O composer.phar

install-sass:
	@echo "Install sass manually and make sure it's in your PATH"

path:
	@echo "Kjør \`source app/env.sh\`"

dev-db:
	php app/console doctrine:schema:create
	php app/console doctrine:fixtures:load

assets:
	make asset-files
	make asset-images
	make asset-scripts
	make asset-vendor

asset-files:
	test -d assets/files && cp -r assets/files/ web/ || echo "No files"

asset-images:
	cp -r assets/img/ web/

asset-scripts:
	mkdir -p web/css
	sass assets/scss/app.scss web/css/app.css
	sass assets/scss/admin_app.scss web/css/admin_app.css

asset-vendor:
	mkdir -p web/bundles/ivoryckeditor
	rm -r web/bundles/ivoryckeditor
	cp -r vendor/egeloen/ckeditor-bundle/Resources/public/ web/bundles/
	mv web/bundles/public web/bundles/ivoryckeditor
	cat app/Resources/config/ckeditor.js > web/bundles/ivoryckeditor/config.js

	mkdir -p web/js
	cp node_modules/jquery/dist/jquery.min.js web/js/
	cp node_modules/bootstrap-sass/assets/javascripts/bootstrap.min.js web/js/

	mkdir -p web/fonts
	cp -r node_modules/bootstrap-sass/assets/fonts/bootstrap/ web/fonts/
	cp -r node_modules/font-awesome/fonts/* web/fonts/
