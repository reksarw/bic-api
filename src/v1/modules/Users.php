<?php

use Slim\Http\Request;
use Slim\Http\Response;

$app->group('/users', function() use($app){
	$container = $app->getContainer();
	$app->get('/self/', function(Request $request, Response $response, $args){
		return $response->withJson(['is_ok' => true, 'data' => $request->getAttribute('userdata')], 200);

	})->add(new AuthMiddleware($container));
});