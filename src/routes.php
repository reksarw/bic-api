<?php
use Slim\Http\Request;
use Slim\Http\Response;


// Version 1
$app->group('/v1', function() use ($app){
	// Custom Middleware
	include "v1/libraries/AuthMiddleware.php";
	// Include Helpers
	include "v1/helpers/customHelper.php";
	// Include Modules
	include "v1/modules/UserAuth.php";
	include "v1/modules/Users.php";
	include "v1/modules/Materi.php";
	include "v1/modules/Pengumuman.php";
	include "v1/modules/Pendaftaran.php";
	include "v1/modules/Information.php";
	include "v1/modules/Program.php";
});
