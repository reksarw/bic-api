<?php

use Slim\Http\Request;
use Slim\Http\Response;

$app->group('/pengumuman', function() use($app){
	// Load container
	$container = $app->getContainer();
	
	$app->get('/', function(Request $request, Response $response, $args){
		$sql = "SELECT id, title, content, dilihat, date_added, last_modified FROM m_pengumuman ORDER BY date_added DESC";
		$stmt = $this->db->prepare($sql);
		$stmt->execute();
		$row = $stmt->rowCount();

		if($row > 0)
		{
			$result = [];

			foreach($stmt->fetchAll() as $data) {
				$result[] = [
					'id' => $data['id'],
					'title' => $data['title'],
					'content' => $data['content'],
					'dilihat' => $data['dilihat'],
					'ditambahkan' => date('Y-m-d H:i:s', strtotime($data['date_added'])),
					'terakhir_diubah' => date('Y-m-d H:i:s', strtotime($data['last_modified']))
				];
			}
		}
			
		return $response->withJson([
				'is_ok' => $row > 0 ? true : false,
				$row > 0 ? 'data' : 'error_message' =>
				$row > 0 ? $result : "Data pengumuman masih kosong."
			], 200);
	})->add(new AuthMiddleware($container));

	$app->get('/{id}/', function(Request $request, Response $response, $args){
		$id = $args['id'];

		$sql = "SELECT id, title, content, dilihat, date_added, last_modified FROM m_pengumuman ";
		$sql.= "WHERE id = :id";
		$stmt = $this->db->prepare($sql);
		$stmt->execute([':id' => $id]);
		$row = $stmt->rowCount();

		if($row > 0)
		{
			$sql = "UPDATE m_pengumuman SET dilihat=dilihat+1 WHERE id=:id";
      $update = $this->db->prepare($sql);
      $update->execute([':id' => $id]);

      $data = $stmt->fetch();
			$result = [
				'id' => $data['id'],
				'title' => $data['title'],
				'content' => $data['content'],
				'dilihat' => ($data['dilihat']) + 1,
				'ditambahkan' => date('Y-m-d H:i:s', strtotime($data['date_added'])),
				'terakhir_diubah' => date('Y-m-d H:i:s', strtotime($data['last_modified']))
			];
		}

		return $response->withJson([
				'is_ok' => $row > 0 ? true : false,
				$row > 0 ? 'data' : 'error_message' =>
				$row > 0 ? $result : "Data pengumuman tidak ditemukan."
			], 200);
	})->add(new AuthMiddleware($container));
});
