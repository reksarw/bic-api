<?php

class AuthMiddleware {

  private $container;

  public function __construct($container) {
    $this->container = $container;
  }

  public function __invoke($request, $response, $next){
    // print_r($this->container); exit;
    $token = $request->getQueryParam('token');

    if(!isset($token)) return $response->withJson(['is_ok' => false, 'error_message' => 'Missing token parameter.'], 401);

    $sql = "SELECT user.id, user.nama_lengkap, user.username, user.email ";
    $sql.= "FROM m_users user, api_users auth ";
    $sql.= "WHERE user.id = auth.id_user AND auth.user_token = '{$token}'";

    $stmt = $this->container->db->prepare($sql);
    $stmt->execute();
    $row = $stmt->rowCount();

    if($row > 0)
    {
      $userdata = $stmt->fetch();
      $data = [
        'id' => $userdata['id'],
        'nama_lengkap' => $userdata['nama_lengkap'],
        'username' => $userdata['username'],
        'email' => $userdata['email'],
      ];

      $sendUser = $request->withAttribute('userdata', $data);

      // Update HIT
      $sql = "UPDATE api_users SET hit=hit+1 WHERE user_token=:token";
      $stmt = $this->container->db->prepare($sql);
      $stmt->execute([':token' => $token]);

      return $response = $next($sendUser, $response);
    }

    return $response->withJson(['is_ok' => false, 'error_message' => 'You\'re not authorized!'], 401);
  }
}