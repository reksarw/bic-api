<?php

use Slim\Http\Request;
use Slim\Http\Response;

$app->group('/materi', function() use($app){

	$app->get('/', function(Request $request, Response $response){
		$sql = "SELECT id, title, subtitle FROM m_materi ORDER BY date_added DESC";
		$stmt = $this->db->prepare($sql);
		$stmt->execute();
		$row = $stmt->rowCount();

		if($row > 0)
		{
			$data = $stmt->fetchAll();

			$result = null;
			foreach($data as $materi) {
				$result[] = [
					'id' => $materi['id'],
					'title' => $materi['title'],
					'subtitle' => $materi['subtitle']
				];
			}

			return $response->withJson(['is_ok' => true, 'data' => $result]);
		}

		return $response->withJson(['is_ok' => false, 'error_message' => 'Data materi masih kosong.'], 200);
	});

	$app->get('/{materiId}/mapel', function(Request $request, Response $response, $args){
		$materiId	= $args['id'];
		
	});

});