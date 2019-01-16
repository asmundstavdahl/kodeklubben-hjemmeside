<?php

use \Rapd\Environment;
use \Rapd\Router;
use \Rapd\View;

# Generate a response based on the request and the configured routes
$uri = $_SERVER["REQUEST_URI"];
$uri = preg_replace("`^".Environment::get("ASSET_BASE")."/`", "/", $uri);
$matchedRoute = Router::match($uri);

if($matchedRoute === false){
	header("HTTP/1.1 404 Not Found");
	echo View::render("404");
} else {
	echo $matchedRoute->execute($uri);
}
