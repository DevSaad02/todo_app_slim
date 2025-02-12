<?php

namespace App\Controllers;

use App\Models\Todo;
use Slim\Views\Twig;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Illuminate\Database\Capsule\Manager as DB;


class TodoController extends HomeController
{
    protected $view;

    public function __construct(Twig $view)
    {
        $this->view = $view;
    }

    public function index(Request $request, Response $response)
    {
        return $this->view->render($response, 'todo.html.twig');
    }

    public function getTodos(Request $request, Response $response)
    {
        $todos = Todo::all(); // Fetch all todos from database
        $data = [
            'status' => 'success',
            'message' => 'All Todos List',
            'todos' => $todos
        ];
        // return $response->withHeader('Content-Type', 'application/json');
        return $this->response($response, $data);
    }

    public function store(Request $request, Response $response)
    {
        $data = $request->getParsedBody();

        $description = trim($data['new-list-item-text'] ?? '');

        // Validate input
        if (empty($description)) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'Description cannot be empty'
            ]), 400);
            return $response->withHeader('Content-Type', 'application/json');
        }

        // Get the max position from the existing records
        $maxPosition = Todo::max('item_position') ?? 0;
        $newPosition = $maxPosition + 1;

        // Create a new Todo item using ORM
        $todo = Todo::create([
            'description' => $description,
            'item_position' => $newPosition
        ]);

        $response->getBody()->write(json_encode([
            'status' => 'success',
            'message' => 'Item added successfully',
            'todo' => $todo
        ]), 201);
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function update(Request $request, Response $response, $id)
    {
        // Get request data
        $data = $request->getParsedBody();
        $newText = trim($data['description'] ?? '');

        // Validate input
        if (empty($newText)) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'Description cannot be empty'
            ]), 400);
            return $response->withHeader('Content-Type', 'application/json');
        }

        // Find the todo item
        $todo = Todo::find($id)->first();
        if (!$todo) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'Todo not found'
            ]), 404);
            return $response->withHeader('Content-Type', 'application/json');
        }

        // Update the description
        $todo->description = $newText;

        if ($todo->save()) {
            $response->getBody()->write(json_encode([
                'status' => 'success',
                'message' => 'Item updated successfully',
                'todo' => $todo
            ]), 200);
            return $response->withHeader('Content-Type', 'application/json');
        } else {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'Failed to update item'
            ]), 500);
            return $response->withHeader('Content-Type', 'application/json');
        }
    }

    public function markDone(Request $request, Response $response, $id)
    {
        // Find the todo item
        $todo = Todo::find($id)->first();

        if (!$todo) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'Todo not found'
            ]), 404);
            return $response->withHeader('Content-Type', 'application/json');
        }

        // Mark as done
        $todo->is_done = 1;

        if ($todo->save()) {
            $response->getBody()->write(json_encode([
                'status' => 'success',
                'message' => 'Item marked as done'
            ]), 200);
            return $response->withHeader('Content-Type', 'application/json');
        } else {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'Failed to update'
            ]), 500);
            return $response->withHeader('Content-Type', 'application/json');
        }
    }

    public function updateColor(Request $request, Response $response, $id)
    {
        $data = $request->getParsedBody();
        $color = trim($data['color'] ?? '');

        if (empty($color)) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'Color cannot be empty'
            ]), 400);
            return $response->withHeader('Content-Type', 'application/json');
        }

        $todo = Todo::find($id)->first();

        if (!$todo) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'Todo not found'
            ]), 404);
            return $response->withHeader('Content-Type', 'application/json');
        }

        $todo->list_color = $color;

        if ($todo->save()) {
            $response->getBody()->write(json_encode([
                'status' => 'success',
                'message' => 'Color updated successfully'
            ]), 200);
            return $response->withHeader('Content-Type', 'application/json');
        } else {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'Failed to update color'
            ]), 500);
            return $response->withHeader('Content-Type', 'application/json');
        }
    }

    public function updatePositions(Request $request, Response $response)
    {
        $data = json_decode($request->getBody()->getContents(), true);
        if (!isset($data["order"]) || !is_array($data["order"])) {
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'Invalid request'
            ]), 400);
            return $response->withHeader('Content-Type', 'application/json');
        }

        try {
            // Start transaction
            DB::beginTransaction();

            foreach ($data["order"] as $item) {
                Todo::where('id', $item["id"])->update(['item_position' => $item["position"]]);
            }

            // Commit transaction
            DB::commit();

            $response->getBody()->write(json_encode([
                'status' => 'success',
                'message' => 'Positions updated'
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            // Rollback transaction on error
            DB::rollBack();

            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => 'Error: ' . $e->getMessage()
            ]), 500);
            return $response->withHeader('Content-Type', 'application/json');
        }
    }

    public function delete(Request $request, Response $response, $id)
    {
        try {
            // Start transaction
            DB::beginTransaction();

            // Find the todo item
            $todo = Todo::find($id)->first();

            if (!$todo) {
                $response->getBody()->write(json_encode([
                    'status' => 'error',
                    'message' => 'Item not found'
                ]), 404);
                return $response->withHeader('Content-Type', 'application/json');
            }

            // Get the deleted item's position
            $deletedPosition = $todo->item_position;

            // Delete the item
            if (!$todo->delete()) {
                throw new \Exception("Error deleting task");
            }

            // Shift positions down for items that had a higher position than the deleted item
            Todo::where('item_position', '>', $deletedPosition)
                ->decrement('item_position');

            DB::commit();

            $response->getBody()->write(json_encode([
                'status' => 'success',
                'message' => 'Task deleted and positions updated'
            ]), 200);
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            DB::rollback();
            $response->getBody()->write(json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]), 500);
            return $response->withHeader('Content-Type', 'application/json');
        }
    }
}
