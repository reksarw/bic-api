<?php

// Version 1
$app->group('/v1', function() use ($app){
	include "v1/modules/UserAuth.php";
});
