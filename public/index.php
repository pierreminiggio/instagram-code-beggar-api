<?php

use App\App;
use PierreMiniggio\ConfigProvider\ConfigProvider;

$projectDirectory = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR;

require $projectDirectory . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';

$configProvider = new ConfigProvider($projectDirectory);
$config = $configProvider->get();

if (! isset($config['token'])) {
    throw new Exception('Missing token config');
}

if (! isset($config['bot'])) {
    throw new Exception('Missing bot config');
}

if (! isset($config['channel'])) {
    throw new Exception('Missing channel config');
}

$dbConfig = $config['db'];

$app = new App(
    $config['token'],
    $config['bot'],
    $config['channel']
);

/** @var string $requestUrl */
$requestUrl = $_SERVER['REQUEST_URI'];

/** @var string|null $queryParameters */
$queryParameters = ! empty($_SERVER['QUERY_STRING']) ? ('?' . $_SERVER['QUERY_STRING']) : null;

/** @var string $calledEndPoint */
$calledEndPoint = $queryParameters
    ? str_replace($queryParameters, '', $requestUrl)
    : $requestUrl
;

if (strlen($calledEndPoint) > 1 && substr($calledEndPoint, -1) === '/') {
    /** @var string $calledEndPoint */
    $calledEndPoint = substr($calledEndPoint, 0, -1);
}

$app->run($requestUrl, $queryParameters, $_SERVER['HTTP_AUTHORIZATION'] ?? null);

exit;
