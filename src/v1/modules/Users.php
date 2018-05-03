<?php

use Slim\Http\Request;
use Slim\Http\Response;

$app->group('/users', function() use($app){
	// Load container
	$container = $app->getContainer();
	
	$app->get('/self/', function(Request $request, Response $response, $args){
		$data = $request->getAttribute('userdata');

		$result = [
			'nama_lengkap' => $data['nama_lengkap'],
      'username' => $data['username'],
      'email' => $data['email']
		];

		return $response->withJson(['is_ok' => true, 'data' => $result], 200);

	})->add(new AuthMiddleware($container));
});