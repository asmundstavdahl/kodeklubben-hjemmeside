
.PHONY: all setup server test assets asset-images asset-js asset-css asset-fonts yarn-install

all: setup

setup:
	composer install

server: setup
	php -S localhost:8000 --docroot=public/

test:
	php run-tests.php

assets: asset-images asset-js asset-css
asset-images:
	#cp -r assets/img/* public/images/
asset-js:
	#cp -r assets/js public/
	mkdir -p public/vendor/js
	cp node_modules/jquery/dist/jquery.min.js public/vendor/js
	cp node_modules/bootstrap-sass/assets/javascripts/bootstrap.min.js public/vendor/js
asset-css: asset-fonts
	mkdir -p public/build/css
	php vendor/bin/pscss assets/scss/app.scss > public/build/css/app.css
	php vendor/bin/pscss assets/scss/admin_app.scss > public/build/css/admin_app.css
asset-fonts: yarn-install
	mkdir -p public/vendor/fonts
	cp node_modules/font-awesome/fonts/fontawesome-webfont.woff public/vendor/fonts/
	cp node_modules/font-awesome/fonts/fontawesome-webfont.ttf public/vendor/fonts/
	cp node_modules/font-awesome/fonts/fontawesome-webfont.eot public/vendor/fonts/
	cp node_modules/font-awesome/fonts/fontawesome-webfont.woff2 public/vendor/fonts/
	cp node_modules/font-awesome/fonts/FontAwesome.otf public/vendor/fonts/
	cp node_modules/font-awesome/fonts/fontawesome-webfont.svg public/vendor/fonts/

yarn-install:
	yarn install
