<?php

use \Rapd\Router;
use \Rapd\View;
use \Rapd\Environment;

function route(string $name, array $data = []){
	return Router::makeUrlTo($name, $data);
}

function render(string $name, array $data = []){
	return View::render($name, $data);
}

function asset(string $assetPath)
{
	return Environment::get("ASSET_BASE") . "/" . $assetPath;
}

function escape(string $unsafeString)
{
    return htmlentities($unsafeString);
}
