#!/bin/bash

path_dist="web"
path_src="app/Resources/assets"

copy(){
    cp $@
}

function styles(){
    for sassFile in $path_src/scss/*.scss
    do
        cssFile=$path_dist/css/${sassFile%.scss}
        node_modules/.bin/sass $sassFile $cssFile
    done
}

function scripts(){
    for jsFile in $path_src/js/*.js
    do
        copy $jsFile $path_dist/js/${jsFile##*/js/}
    done
}

function images(){
    for imageFile in $path_src/img/*
    do
        copy $imageFile $path_dist/img/${imageFile##*/}
    done
}
function vendor(){
    copy -r node_modules/ckeditor $path_dist/js/vendor/
    copy -r node_modules/bootstrap-sass/assets/javascripts/bootstrap.min.js $path_dist/js/
    copy -r node_modules/jquery/dist/jquery.min.js $path_dist/js/
    copy -r node_modules/bootstrap-sass/assets/fonts/bootstrap/* $path_dist/fonts/bootstrap
    copy -r node_modules/font-awesome/fonts/* $path_dist/fonts/
}

function config(){
    copy app/Resources/config/ckeditor.js $path_dist/bundles/ivoryckeditor/config.js
}

function files(){
    copy $path_src/files/* $path_dist/files/
}

function dirs(){
    mkdir -p $path_dist/js
    mkdir -p $path_dist/css
    mkdir -p $path_dist/img
    mkdir -p $path_dist/files
    mkdir -p $path_dist/fonts/bootstrap
    mkdir -p $path_dist/js/vendor
    mkdir -p $path_dist/bundles/ivoryckeditor
}

function build(){
    dirs
    styles
    scripts
    images
    config
    files
    vendor
}

$1
