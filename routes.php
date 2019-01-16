<?php

foreach(glob(__DIR__."/routes/*.php") as $routeFile){
	include $routeFile;
}
