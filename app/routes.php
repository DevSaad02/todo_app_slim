<?php

declare(strict_types=1);

use Slim\App;
use App\Controllers\TodoController;
use App\Application\Actions\User\ViewUserAction;
use App\Application\Actions\User\ListUsersAction;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;

return function (App $app) {
    $app->options('/{routes:.*}', function (Request $request, Response $response) {
        // CORS Pre-Flight OPTIONS Request Handler
        return $response;
    });

    $app->get('/', function (Request $request, Response $response) {
        $response->getBody()->write('Hello world!');
        return $response;
    });

    $app->group('/users', function (Group $group) {
        $group->get('', ListUsersAction::class);
        $group->get('/{id}', ViewUserAction::class);
    });

    $app->group('/todos', function (Group $group) {
    // Todos
    $group->get('/list', [TodoController::class, 'index']);
    $group->put('/update-positions', [TodoController::class, 'updatePositions']);
    $group->get('', [TodoController::class, 'getTodos']);
    $group->post('', [TodoController::class, 'store']);
    $group->put('/{id}', [TodoController::class, 'update']);
    $group->put('/done/{id}', [TodoController::class, 'markDone']);
    $group->put('/color/{id}', [TodoController::class, 'updateColor']);
    $group->delete('/{id}', [TodoController::class, 'delete']);
});


};
