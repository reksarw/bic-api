<?php

use Slim\Http\Request;
use Slim\Http\Response;

$app->group('/info', function() use($app){
	// Load container
	$container = $app->getContainer();

	$app->get('/rekening/', function(Request $request, Response $response){
		$sql = "SELECT nama_rekening, no_rekening, bank FROM m_rekening";
		$stmt = $this->db->prepare($sql);
		$stmt->execute();

		$data = $stmt->fetchAll();
		return $response->withJson(['is_ok' => true, 'data' => $data]);
	})->add(new AuthMiddleware($container));
});