<?php
use Slim\Http\Request;
use Slim\Http\Response;


// Version 1
$app->group('/v1', function() use ($app){
	// Custom Middleware
	include "v1/libraries/AuthMiddleware.php";
	include "v1/modules/UserAuth.php";
	include "v1/modules/Users.php";
	include "v1/modules/Materi.php";
});
