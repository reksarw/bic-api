<?php

use Slim\Http\Request;
use Slim\Http\Response;

$app->group('/materi', function() use($app){
	// Load container
	$container = $app->getContainer();

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
	})->add(new AuthMiddleware($container));

	$app->get('/{materiId}/mapel/', function(Request $request, Response $response, $args){
		$materiId	= $args['materiId'];
		$sql = "SELECT title FROM m_materi WHERE id = {$materiId}";
		$stmt = $this->db->prepare($sql);
		$stmt->execute();
		$numMapel = $stmt->rowCount();
		
		$materi = $stmt->fetch()['title'];
		$result = [];
		if($numMapel > 0 ) {
			$sql = "SELECT mapel.id, mapel.title, mapel.subtitle ";
			$sql.= "FROM m_materi materi, m_materi_mapel mapel ";
			$sql.= "WHERE materi.id = mapel.id_materi AND mapel.id_materi = {$materiId}";
			$stmt = $this->db->prepare($sql);
			$stmt->execute();

			foreach($stmt->fetchAll() as $data) {
				$result[] = array(
						'id' => $data['id'],
						'title' => $data['title'],
						'subtitle' => $data['subtitle'],
					);
			}
		} else {
			return $response->withJson(['is_ok' => false, 'error_message' => "Materi tidak ditemukan!"], 404);
		}

		return $response->withJson([
			'is_ok' => true, 
			'materi' => $materi,
			'mapel' => $result
			], 200);
	})->add(new AuthMiddleware($container));

	$app->get('/{materiId}/mapel/{mapelId}/', function(Request $request, Response $response, $args){
		$mapelId = $args['mapelId'];
		$materiId	= $args['materiId'];
		$sql = "SELECT id, title, subtitle, content FROM m_materi_mapel WHERE id = :id AND id_materi = :materi";
		$stmt = $this->db->prepare($sql);
		$stmt->execute([':id' => $mapelId, ':materi' => $materiId]);
		$row = $stmt->rowCount();

		if($row == 0) {
			return $response->withJson(['is_ok' => false, 'error_message' => "Materi atau Mata Pelajaran tidak ditemukan."], 404);
		} 

		$data = $stmt->fetch();

		$result = [
			'id' => $data['id'],
			'title' => $data['title'],
			'subtitle' => $data['subtitle'],
			'content' => $data['content']
		];

		return $response->withJson(['is_ok' => true, 'data' => $result], 200);
	})->add(new AuthMiddleware($container));
});
