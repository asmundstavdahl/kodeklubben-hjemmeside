<?php

use \Rapd\Router;
use \Rapd\Router\Route;
use \Rapd\View;

Router::add(new Route(
    "home",
    "/",
    [\HomeController::class,"showAction"]
));
