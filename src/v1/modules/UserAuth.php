<?php

use Slim\Http\Request;
use Slim\Http\Response;

$app->group('/auth', function() use($app){

	$app->post('/login', function(Request $request, Response $response){
		$postdata = $request->getParsedBody();

		$required = ['username', 'password'];
		$error = false;
		foreach($postdata as $key => $params) {
			if(!in_array($key,$required)) $error = true;
		}

		if(!$error)
		{
			$sql = "SELECT * FROM m_users WHERE username = :user AND password = :pwd";
			$stmt = $this->db->prepare($sql);
			$data = [
				':user' => $postdata['username'],
				':pwd' => md5($postdata['password'])
			];
			
			$stmt->execute($data);
			$row = $stmt->rowCount();

			$result = [
				'is_ok' => $row > 0 ? true : false,
				$row > 0 ? 'message' : 'error_message' =>
				$row > 0 ? "Berhasil masuk!" : "Username atau Password salah!"
			];

			if($row > 0)
			{
				$user = $stmt->fetch();
				$sql = "SELECT * FROM api_users WHERE id_user = :id";
				$userdata = $this->db->prepare($sql);
				$userdata->execute([':id' => $user['id']]);
				$token = $userdata->fetch()['user_token'];
				$result['user_token'] = $token;
			}
		}
		else
		{
			$result = [
				'is_ok' => false,
				'error_message' => "Parameter masih ada yang kosong!"
			];
		}
		
		return $response->withJson($result, 200);
	});

	$app->post('/register', function(Request $request, Response $response){
		$postdata = $request->getParsedBody();

		$required = ['nama_lengkap', 'username', 'password', 'email'];
		$error = false;
		foreach($postdata as $key => $params) {
			if(!in_array($key,$required)) $error = true;
		}

		if(!$error && count($postdata) > 0)
		{
			$sql = "SELECT * FROM m_users WHERE email = :email OR username = :username";
			$stmt = $this->db->prepare($sql);
			$data = [
				':email' => $postdata['email'],
				':username' => $postdata['username']
			];
			$stmt->execute($data);
			$row = $stmt->rowCount();

			if($row == 0) {
				$sql = "INSERT INTO m_users (nama_lengkap, username, password, email) VALUES (:nama, :user, :pwd, :email)";
				$stmt = $this->db->prepare($sql);

				$data = [
					':nama' => $postdata['nama_lengkap'],
					':user' => $postdata['username'],
					':pwd' => md5($postdata['password']),
					':email' => $postdata['email']
				];

				$stmt->execute($data);
			}

			$result = [
				'is_ok' => $row == 0 ? true : false,
				$row == 0 ? 'message' : 'error_message' =>
				$row == 0 ? "Berhasil menambah user!" : "Username atau Email sudah digunakan!"
			];
		}
		else
		{
			$result = [
				'is_ok' => false,
				'error_message' => "Parameter masih ada yang kosong!"
			];
		}

		return $response->withJson($result, 200);
	});

});