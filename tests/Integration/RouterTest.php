<?php

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;
use Phunkie\Http4p\Method;
use Phunkie\Http4p\Request;
use Phunkie\Http4p\Router;

use function Phunkie\Http4p\Functions\response\Created;
use function Phunkie\Http4p\Functions\routes\DELETE;
use function Phunkie\Http4p\Functions\routes\GET;
use function Phunkie\Http4p\Functions\HttpRoutes;
use function Phunkie\Http4p\Functions\response\Ok;
use function Phunkie\Http4p\Functions\routes\POST;
use function Phunkie\Http4p\Functions\routes\PUT;
use function Phunkie\Http4p\Functions\Request;

class RouterTest extends TestCase
{
    public function test_router_routes_to_matching_handler()
    {
        $routes = HttpRoutes(
            GET('/users', fn() => Ok(['users' => []])),
            GET('/posts', fn() => Ok(['posts' => []]))
        );
        
        $router = new Router($routes);
        $request = Request(Method::GET, '/users');
        
        $response = $router->route($request)->unsafeRun();
        
        $this->assertEquals(200, $response->status->code);
        $this->assertEquals(['{"users":[]}'], $response->body->toArray());
    }

    public function test_router_returns_404_for_unmatched_route()
    {
        $routes = HttpRoutes(
            GET('/users', fn() => Ok([]))
        );
        
        $router = new Router($routes);
        $request = Request(Method::GET, '/nonexistent');
        
        $response = $router->route($request)->unsafeRun();
        
        $this->assertEquals(404, $response->status->code);
    }

    public function test_router_passes_path_params_to_handler()
    {
        $routes = HttpRoutes(
            GET('/users/:id', fn($id) => Ok(['id' => $id]))
        );
        
        $router = new Router($routes);
        $request = Request(Method::GET, '/users/42');
        
        $response = $router->route($request)->unsafeRun();
        
        $this->assertEquals(200, $response->status->code);
        $this->assertEquals(['{"id":42}'], $response->body->toArray());
    }

    public function test_router_passes_multiple_path_params()
    {
        $routes = HttpRoutes(
            GET(
                '/users/:userId/posts/:postId',
                fn($userId, $postId) => 
                Ok(['userId' => $userId, 'postId' => $postId])
            )
        );
        
        $router = new Router($routes);
        $request = Request(Method::GET, '/users/1/posts/99');
        
        $response = $router->route($request)->unsafeRun();
        
        $this->assertEquals(['{"userId":1,"postId":99}'], $response->body->toArray());
    }

    public function test_router_passes_request_when_handler_expects_it()
    {
        $routes = HttpRoutes(
            POST('/users', fn(Request $req) => Created(['name' => $req->body->toArray()[0]['name']]))
        );
        
        $router = new Router($routes);
        $request = Request(Method::POST, '/users', null, ['name' => 'Alice']);
        
        $response = $router->route($request)->unsafeRun();
        
        $this->assertEquals(201, $response->status->code);
        $this->assertEquals(['{"name":"Alice"}'], $response->body->toArray());
    }

    public function test_router_handles_different_http_methods()
    {
        $routes = HttpRoutes(
            GET('/resource', fn() => Ok(['method' => 'GET'])),
            POST('/resource', fn() => Ok(['method' => 'POST'])),
            PUT('/resource', fn() => Ok(['method' => 'PUT'])),
            DELETE('/resource', fn() => Ok(['method' => 'DELETE']))
        );
        
        $router = new Router($routes);
        
        $getResponse = $router->route(Request(Method::GET, '/resource'))->unsafeRun();
        $this->assertEquals(['{"method":"GET"}'], $getResponse->body->toArray());
        
        $postResponse = $router->route(Request(Method::POST, '/resource'))->unsafeRun();
        $this->assertEquals(['{"method":"POST"}'], $postResponse->body->toArray());
        
        $putResponse = $router->route(Request(Method::PUT, '/resource'))->unsafeRun();
        $this->assertEquals(['{"method":"PUT"}'], $putResponse->body->toArray());
        
        $deleteResponse = $router->route(Request(Method::DELETE, '/resource'))->unsafeRun();
        $this->assertEquals(['{"method":"DELETE"}'], $deleteResponse->body->toArray());
    }
}
