<?php
// DIC configuration

$container = $app->getContainer();

// view renderer
$container['renderer'] = function ($c) {
    $settings = $c->get('settings')['renderer'];
    return new Slim\Views\PhpRenderer($settings['template_path']);
};

// monolog
$container['logger'] = function ($c) {
    $settings = $c->get('settings')['logger'];
    $logger = new Monolog\Logger($settings['name']);
    $logger->pushProcessor(new Monolog\Processor\UidProcessor());
    $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'], $settings['level']));
    return $logger;
};

// database
$container['db'] = function ($c){
    $settings = $c->get('settings')['db'];
    $server = $settings['driver'].":host=".$settings['host'].";dbname=".$settings['dbname'];
    $conn = new PDO($server, $settings["user"], $settings["pass"]);  
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $conn;
};

$container['errorHandler'] = function ($c) {
  return function ($request, $response, $exception) use ($c) {
    $result = [
      'is_ok' => false,
      'error_message' => 'Someting went wrong!',
      'exceptions' => $exception
    ];
    return $response->withJson($result, 500);
  };
};

// Override the default Not Found Handler
$container['notFoundHandler'] = function ($c) {
  return function ($request, $response) use ($c) {
    $result = [
      'is_ok' => false,
      'error_message' => 'URL not found!'
    ];
    return $response->withJson($result, 404);
  };
};

// Override the default Not Allowed Method handler
$container['notAllowedHandler'] = function ($c) {
  return function ($request, $response, $methods) use ($c) {
    $result = [
      'is_ok' => false,
      'error_message' => 'Method not allowed!'
    ];
    return $response->withJson($result, 405);
  };
};

// Base URL
$container['baseUrl'] = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == "on") ? "https" : "http");
$container['baseUrl'] .= "://".$_SERVER['HTTP_HOST'];
$container['baseUrl'] .= str_replace(basename($_SERVER['SCRIPT_NAME']),"",$_SERVER['SCRIPT_NAME']);
