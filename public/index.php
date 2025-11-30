<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use DI\Container;
use App\config\settings;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

require __DIR__ . '/../vendor/autoload.php';

$container = new Container();
AppFactory::setContainer($container);
$app = AppFactory::create();

$container->set('settings', fn() => settings::load());
$container->set('logger', function () {
    $settings = settings::load();
    $logger = new Logger('app');
    $logger->pushHandler(new StreamHandler(__DIR__ . '/../logs/app.log', $settings['LOG_LEVEL'] ?? Logger::DEBUG));
    return $logger;
});

$app->addBodyParsingMiddleware(); 
$app->addRoutingMiddleware(); 
$app->add(new App\Middleware\CorsMiddleware()); 
$app->add(new App\Middleware\ErrorHandler($container->get('logger')));
require_once __DIR__ . '/../src/routes/api.php';

$app->run();