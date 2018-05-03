<?php

use Slim\Http\Request;
use Slim\Http\Response;

$app->group('/programs', function() use($app){
	// Load container
	$container = $app->getContainer();
	
	$app->get('/', function(Request $request, Response $response){
		$sql = "SELECT id, nama_program, title, content, biaya_tunai, biaya_pendaftaran FROM m_program ORDER BY date_added DESC";
		$stmt = $this->db->prepare($sql);
		$stmt->execute();

		foreach($stmt->fetchAll() as $program) {
			$programs[] = [
				'id' => intval($program['id']),
				'nama_program' => $program['nama_program'],
				'title' => $program['title'],
				'content' => $program['content'],
				'biaya_tunai' => intval($program['biaya_tunai']),
				'biaya_pendaftaran' => intval($program['biaya_pendaftaran'])
			];
		}

		return $response->withJson(['is_ok' => true, 'data' => $programs], 200);
	})->add(new AuthMiddleware($container));

	$app->get('/{programId}/', function(Request $request, Response $response, $args){
		$programId = $args['programId'];

		$sql = "SELECT id, nama_program, title, content, biaya_tunai, biaya_pendaftaran FROM m_program WHERE id = :programId";
		$stmt = $this->db->prepare($sql);
		$stmt->execute([':programId' => $programId]);
		$row = $stmt->rowCount();
		$data = $stmt->fetch();

		if($row > 0) {
			$program =  [
				'id' => intval($data['id']),
				'nama_program' => $data['nama_program'],
				'title' => $data['title'],
				'content' => $data['content'],
				'biaya_tunai' => intval($data['biaya_tunai']),
				'biaya_pendaftaran' => intval($data['biaya_pendaftaran'])
			];
		}

		return $response->withJson([
				'is_ok' => $row > 0 ? true : false,
				$row > 0 ? "data" : "error_message" =>
				$row > 0 ? $program : "Data program tidak ditemukan!"
			]);
	});
});