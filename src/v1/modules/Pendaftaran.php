<?php

use Slim\Http\Request;
use Slim\Http\Response;

$app->group('/pendaftaran', function() use($app){
	// Load container
	$container = $app->getContainer();
		
	$app->get('/self/', function(Request $request, Response $response){
		$userdata = $request->getAttribute('userdata');

		$sql = "SELECT * FROM m_pendaftaran WHERE id_user = :userId";
		$stmt = $this->db->prepare($sql);
		$stmt->execute([':userId' => $userdata['id']]);

		$row = $stmt->rowCount();

		if($row == 0) return $response->withJson(['is_ok' => false, 'error_message' => "User belum mendaftar!"]);
		else {
			$pendaftaran = $stmt->fetch();
			// Data pribadi
			$sql = "SELECT nama_lengkap, tanggal_lahir, asal_sekolah, alamat, no_hp, email, instagram, facebook FROM t_data_pribadi WHERE id_pendaftaran = ?";
			$datapribadi = $this->db->prepare($sql);
			$datapribadi->execute([$pendaftaran['id']]);

			// Data Ortu
			$sql = "SELECT nama_ayah, pekerjaan_ayah, nohp_ayah, nama_ibu, pekerjaan_ibu, nohp_ibu FROM t_data_ortu WHERE id_pendaftaran = ?";
			$dataortu = $this->db->prepare($sql);
			$dataortu->execute([$pendaftaran['id']]);

			// Data Pembayaran
			$sql = "SELECT nama_rekening, bank_penerima, bukti_pembayaran,";
			$sql.= ' (CASE WHEN status = 0 THEN "Belum Bayar" WHEN status = 1 THEN "Pembayaran DP" WHEN status = 2 THEN "Sudah Lunas" END) as status';
			$sql.= " FROM t_data_pembayaran WHERE id_pendaftaran = ?";
			$datapembayaran = $this->db->prepare($sql);
			$datapembayaran->execute([$pendaftaran['id']]);

			// Data Program
			$sql = "SELECT program.id, program.nama_program
							FROM m_program program, t_data_program t_program
							WHERE program.id = t_program.id_program AND id_pendaftaran = ?";
			$dataprogram = $this->db->prepare($sql);
			$dataprogram->execute([$pendaftaran['id']]);

			
			$programdata = $dataprogram->fetch();
			$program = [
				'id_program' => intval($programdata['id']),
				'nama_program' => $programdata['nama_program']
			];

			return $response->withJson([
					'is_ok' => true,
					'personal' => $datapribadi->rowCount() > 0 ? $datapribadi->fetch() : null,
					'parent' => $dataortu->rowCount() > 0 ? $dataortu->fetch() : null,
					'program' => $dataprogram->rowCount() > 0 ? $program : null,
					'payment' => $datapembayaran->rowCount() > 0 ? $datapembayaran->fetch() : null,
				], 200);
		}
	})->add(new AuthMiddleware($container));

	$app->post('/datapribadi/', function(Request $request, Response $response){
		$postdata = $request->getParsedBody();

		if($postdata['tanggal_lahir']) {
			$pattern = "/^[0-9]{4}-(0[1-9]|1[0-2])-(0[1-9]|[1-2][0-9]|3[0-1])$/";
			if( ! preg_match($pattern, $postdata['tanggal_lahir'])) 
				return $response->withJson(['is_ok' => false, 'error_message' => "Format tanggal lahir salah. (YYYY-MM-DD)"]);
		}

		$userId = $request->getAttribute('userdata')['id'];
		$sql = "SELECT * FROM m_pendaftaran WHERE id_user = :userId";
		$stmt = $this->db->prepare($sql);
		$stmt->execute([':userId' => $userId]);

		$row = $stmt->rowCount();

		$pendaftaranId = null;

		if($row == 0)
		{
			// Insert Pendafaran
			$sql = "INSERT INTO m_pendaftaran(id_user) VALUES (:userId)";
			$pendaftaran = $this->db->prepare($sql);
			$this->db->beginTransaction();
			$pendaftaran->execute([':userId' => $userId]);
			$this->db->commit();
			$pendaftaranId = $this->db->lastInsertId();
		} else {
			// SELECT pendaftaran
			$sql = "SELECT * FROM m_pendaftaran WHERE id_user = :userId";
			$pendaftaran = $this->db->prepare($sql);
			$pendaftaran->execute([':userId' => $userId]);
			$dataPendaftaran = $pendaftaran->fetch();
			$pendaftaranId = $dataPendaftaran['id'];
		}

		$sql = "SELECT * FROM t_data_pribadi WHERE id_pendaftaran = :id";
		$datapribadi = $this->db->prepare($sql);
		$datapribadi->execute([':id' => $pendaftaranId]);
		$rowDataPribadi = $datapribadi->rowCount();
		
		if($rowDataPribadi > 0) {
			$data = $datapribadi->fetch();
			// Update
			$sql = "UPDATE t_data_pribadi SET nama_lengkap = :namaLengkap, tanggal_lahir = :tglLahir, asal_sekolah = :asalSekolah,";
			$sql.= " alamat = :alamat, no_hp = :noHp, email = :email, instagram = :ig, facebook = :fb";
			$sql.= " WHERE id_pendaftaran = :pendaftaranId";
			$stmt = $this->db->prepare($sql);
			$stmt->execute([
					':namaLengkap' => $postdata['nama_lengkap'] ? $postdata['nama_lengkap'] : $data['nama_lengkap'],
					':tglLahir' => $postdata['tanggal_lahir'] ? $postdata['tanggal_lahir'] : $data['tanggal_lahir'],
					':asalSekolah' => $postdata['asal_sekolah'] ? $postdata['asal_sekolah'] : $data['asal_sekolah'],
					':alamat' => $postdata['alamat'] ? $postdata['alamat'] : $data['alamat'],
					':noHp' => $postdata['no_hp'] ? $postdata['no_hp'] : $data['no_hp'],
					':email' => $postdata['email'] ? $postdata['email'] : $data['email'],
					':ig' => $postdata['instagram'] ? $postdata['instagram'] : $data['instagram'],
					':fb' => $postdata['facebook'] ? $postdata['facebook'] : $data['facebook'],
					':pendaftaranId' => $pendaftaranId
				]);

			$message = "Berhasil mengubah data diri!";
		} else {
			$required = ['nama_lengkap', 'tanggal_lahir', 'asal_sekolah', 'alamat', 'no_hp', 'email'];
			$error = false;
			foreach($postdata as $key => $params) {
				if(!in_array($key,$required)) $error = true;
			}

			if($error && count($postdata) > 0 || count($postdata) == 0) return $response->withJson(['is_ok' => false, 'error_message' => "Parameter masih ada yang kosong!"]);
			else {
				$sql = "INSERT INTO t_data_pribadi (id_pendaftaran, nama_lengkap, tanggal_lahir, asal_sekolah, alamat, no_hp, email, instagram, facebook) VALUES (?,?,?,?,?,?,?,?,?)";

				$stmt = $this->db->prepare($sql);
				$stmt->execute([
						$pendaftaranId, $postdata['nama_lengkap'], $postdata['tanggal_lahir'], $postdata['asal_sekolah'], $postdata['alamat'],
						$postdata['no_hp'], $postdata['email'], @$postdata['instagram'], @$postdata['facebook']
					]);

				$message = "Berhasil menambah data diri!";
			}
		}

		return $response->withJson(['is_ok' => true, "message" => $message]);
	})->add(new AuthMiddleware($container));

	$app->post('/dataortu/', function(Request $request, Response $response){
		$postdata = $request->getParsedBody();

		$userId = $request->getAttribute('userdata')['id'];
		$sql = "SELECT * FROM m_pendaftaran WHERE id_user = :userId";
		$stmt = $this->db->prepare($sql);
		$stmt->execute([':userId' => $userId]);

		$row = $stmt->rowCount();

		$pendaftaranId = null;

		if($row == 0)
		{
			// Insert Pendafaran
			$sql = "INSERT INTO m_pendaftaran(id_user) VALUES (:userId)";
			$pendaftaran = $this->db->prepare($sql);
			$this->db->beginTransaction();
			$pendaftaran->execute([':userId' => $userId]);
			$this->db->commit();
			$pendaftaranId = $this->db->lastInsertId();
		} else {
			// SELECT pendaftaran
			$sql = "SELECT * FROM m_pendaftaran WHERE id_user = :userId";
			$pendaftaran = $this->db->prepare($sql);
			$pendaftaran->execute([':userId' => $userId]);
			$dataPendaftaran = $pendaftaran->fetch();
			$pendaftaranId = $dataPendaftaran['id'];
		}

		$sql = "SELECT * FROM t_data_ortu WHERE id_pendaftaran = :id";
		$dataortu = $this->db->prepare($sql);
		$dataortu->execute([':id' => $pendaftaranId]);
		$rowDataOrtu = $dataortu->rowCount();
		
		if($rowDataOrtu > 0) {
			$data = $dataortu->fetch();
			// Update
			$sql = "UPDATE t_data_ortu SET ";
			$sql.= " nama_ayah = :namaAyah, pekerjaan_ayah = :pekerjaanAyah, nohp_ayah = :noHpAyah,";
			$sql.= " nama_ibu = :namaIbu, pekerjaan_ibu = :pekerjaanIbu, nohp_ibu = :noHpIbu";
			$sql.= " WHERE id_pendaftaran = :pendaftaranId";
			$stmt = $this->db->prepare($sql);
			$stmt->execute([
					':namaAyah' => $postdata['nama_ayah'] ? $postdata['nama_ayah'] : $data['nama_ayah'],
					':pekerjaanAyah' => $postdata['pekerjaan_ayah'] ? $postdata['pekerjaan_ayah'] : $data['pekerjaan_ayah'],
					':noHpAyah' => $postdata['nohp_ayah'] ? $postdata['nohp_ayah'] : $data['nohp_ayah'],
					':namaIbu' => $postdata['nama_ibu'] ? $postdata['nama_ibu'] : $data['nama_ibu'],
					':pekerjaanIbu' => $postdata['pekerjaan_ibu'] ? $postdata['pekerjaan_ibu'] : $data['pekerjaan_ibu'],
					':noHpIbu' => $postdata['nohp_ibu'] ? $postdata['nohp_ibu'] : $data['nohp_ibu'],
					':pendaftaranId' => $pendaftaranId
				]);

			$message = "Berhasil mengubah data orang tua!";
		} else {
			$required = ['nama_ayah', 'nama_ibu', 'pekerjaan_ayah', 'pekerjaan_ibu', 'nohp_ayah', 'nohp_ibu'];
			$error = false;
			foreach($postdata as $key => $params) {
				if(!in_array($key,$required)) $error = true;
			}

			if($error && count($postdata) > 0 || count($postdata) == 0) return $response->withJson(['c' => count($postdata), 'is_ok' => false, 'error_message' => "Parameter masih ada yang kosong!"]);
			else {
				$sql = "INSERT INTO t_data_ortu (id_pendaftaran, nama_ayah, pekerjaan_ayah, nohp_ayah, nama_ibu, pekerjaan_ibu, nohp_ibu) VALUES (?,?,?,?,?,?,?)";

				$stmt = $this->db->prepare($sql);
				$stmt->execute([
						$pendaftaranId, $postdata['nama_ayah'], $postdata['pekerjaan_ayah'], $postdata['nohp_ayah'], $postdata['nama_ibu'],
						$postdata['pekerjaan_ibu'], $postdata['nohp_ibu']
					]);

				$message = "Berhasil menambah data orang tua!";
			}
		}

		return $response->withJson(['is_ok' => true, "message" => $message]);
	})->add(new AuthMiddleware($container));

	
	$app->post('/dataprogram/', function(Request $request, Response $response){
		$postdata = $request->getParsedBody();

		if(!$postdata['id_program']) return $response->withJson(['is_ok' => false, 'error_message' => "Missing id_program parameter."]);
		else {
			// PENDAFTARAN
			$userId = $request->getAttribute('userdata')['id'];
			$sql = "SELECT * FROM m_pendaftaran WHERE id_user = :userId";
			$stmt = $this->db->prepare($sql);
			$stmt->execute([':userId' => $userId]);

			$row = $stmt->rowCount();

			$pendaftaranId = null;

			if($row == 0)
			{
				// Insert Pendafaran
				$sql = "INSERT INTO m_pendaftaran(id_user) VALUES (:userId)";
				$pendaftaran = $this->db->prepare($sql);
				$this->db->beginTransaction();
				$pendaftaran->execute([':userId' => $userId]);
				$this->db->commit();
				$pendaftaranId = $this->db->lastInsertId();
			} else {
				// SELECT pendaftaran
				$sql = "SELECT * FROM m_pendaftaran WHERE id_user = :userId";
				$pendaftaran = $this->db->prepare($sql);
				$pendaftaran->execute([':userId' => $userId]);
				$dataPendaftaran = $pendaftaran->fetch();
				$pendaftaranId = $dataPendaftaran['id'];
			}
			//* PENDAFTARAN
			
		}
	})->add(new AuthMiddleware($container));

	$app->get('/datapembayaran/', function(Request $request, Response $response){
		// PENDAFTARAN
		$userId = $request->getAttribute('userdata')['id'];
		$sql = "SELECT * FROM m_pendaftaran WHERE id_user = :userId";
		$stmt = $this->db->prepare($sql);
		$stmt->execute([':userId' => $userId]);

		$row = $stmt->rowCount();

		if($row == 0) return $response->withJson(['is_ok' => false, 'error_message' => "User belum mendaftar!"]);
		else {
			// Data Pendaftaran
			$pendaftaran = $stmt->fetch();
			$sql = "SELECT * FROM t_data_pembayaran WHERE id_pendaftaran = ?";
			$dataprogram = $this->db->prepare($sql);
			$dataprogram->execute([$pendaftaran['id']]);
			$row = $dataprogram->rowCount();
			$data = $dataprogram->fetch();

			$status = [
				0 => "Belum Bayar",
				1 => "Pembayaran DP",
				2 => "Lunas"
			];

			return $response->withJson([
					'is_ok' => $row > 0 ? true : false,
					$row > 0 ? 'status' : 'error_message' =>
					$row > 0 ? $status[$data['status']] : "Data masih kosong!"
				]);
		}
	})->add(new AuthMiddleware($container));

	$app->post('/datapembayaran/', function(Request $request, Response $response){
		return $response->withJson(['is_ok' => true]);
	})->add(new AuthMiddleware($container));

	$app->post('/buktipembayaran/', function(Request $request, Response $response){
		$uploadedFiles = $request->getUploadedFiles();

		$uploadedFile = $uploadedFiles['foto'];
    if($uploadedFile == null) $result = ['is_ok' => false, 'error_message' => 'Gambar belum dipilih!'];
    else if($uploadedFile->getError() === UPLOAD_ERR_OK) {
    	$acceptedExt = array('jpg','png','gif','mp4');
			$extension = pathinfo($uploadedFile->getClientFilename(), PATHINFO_EXTENSION);
    	if( ! in_array($extension, $acceptedExt)) $result = ['is_ok' => false, 'error_message' => 'Maaf, ektensi tidak diperbolehkan!'];
    	else {
		    // PENDAFTARAN
				$userId = $request->getAttribute('userdata')['id'];
				$sql = "SELECT * FROM m_pendaftaran WHERE id_user = :userId";
				$stmt = $this->db->prepare($sql);
				$stmt->execute([':userId' => $userId]);

				$row = $stmt->rowCount();

				$pendaftaranId = null;

				if($row == 0)
				{
					// Insert Pendafaran
					$sql = "INSERT INTO m_pendaftaran(id_user) VALUES (:userId)";
					$pendaftaran = $this->db->prepare($sql);
					$this->db->beginTransaction();
					$pendaftaran->execute([':userId' => $userId]);
					$this->db->commit();
					$pendaftaranId = $this->db->lastInsertId();
				} else {
					// SELECT pendaftaran
					$sql = "SELECT * FROM m_pendaftaran WHERE id_user = :userId";
					$pendaftaran = $this->db->prepare($sql);
					$pendaftaran->execute([':userId' => $userId]);
					$dataPendaftaran = $pendaftaran->fetch();
					$pendaftaranId = $dataPendaftaran['id'];
				}
				//* PENDAFTARAN

	    	$basename = sha1($uploadedFile->getClientFilename());
	      $filename = sprintf('%s.%0.8s', $basename, $extension);
	      $month = date('m');
	      $directory = $this->get('settings')['upload_directory'] . DIRECTORY_SEPARATOR . $month;
	      if(!is_dir($directory)) mkdir($directory, 0744);
	      $uploadedFile->moveTo($directory. DIRECTORY_SEPARATOR . $filename);
	      
	      $sql = "SELECT * FROM t_data_pembayaran WHERE id_pendaftaran = :id";
	      $datapembayaran = $this->db->prepare($sql);
	      $datapembayaran->execute([':id' => $pendaftaranId]);

	      $row = $datapembayaran->rowCount();

	      if($row > 0) {
		      $sql = "UPDATE t_data_pembayaran SET bukti_pembayaran = :imageUrl WHERE id_pendaftaran = :id";
		      $message = "Berhasil mengubah bukti pembayaran!";
	      } else {
	      	$sql = "INSERT INTO t_data_pembayaran (id_pendaftaran,bukti_pembayaran) VALUES (:id, :imageUrl)";
	      	$message = "Berhasil menambah bukti pembayaran!";
	      }
				$stmt = $this->db->prepare($sql);

				$data = [
					':id' => $pendaftaranId,
					':imageUrl' => $this->baseUrl."images/{$month}/".$filename
				];

				$stmt->execute($data);

	      $result = ['is_ok' => true, 'message' => $message];
    	}
    }

    return $response->withJson($result);
	})->add(new AuthMiddleware($container));

});
