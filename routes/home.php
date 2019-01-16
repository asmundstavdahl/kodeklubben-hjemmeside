<?php

Router::add(new Route(
    "home",
    "/",
    [\HomeController::class,"showAction"]
));
