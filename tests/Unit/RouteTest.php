<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Phunkie\Http4p\Method;

class RouteTest extends TestCase
{
    public function test_route_matches_exact_path()
    {
        $route = GET('/users', fn() => Ok([]));
        $request = Request(Method::GET, '/users');
        
        $this->assertTrue($route->matches($request));
    }

    public function test_route_does_not_match_different_path()
    {
        $route = GET('/users', fn() => Ok([]));
        $request = Request(Method::GET, '/posts');
        
        $this->assertFalse($route->matches($request));
    }

    public function test_route_does_not_match_different_method()
    {
        $route = GET('/users', fn() => Ok([]));
        $request = Request(Method::POST, '/users');
        
        $this->assertFalse($route->matches($request));
    }

    public function test_route_matches_with_path_params()
    {
        $route = GET('/users/:id', fn($id) => Ok(['id' => $id]));
        $request = Request(Method::GET, '/users/123');
        
        $this->assertTrue($route->matches($request));
    }

    public function test_route_extracts_path_params()
    {
        $route = GET('/users/:id', fn($id) => Ok(['id' => $id]));
        $params = $route->extractParams('/users/123');
        
        $this->assertEquals(['id' => 123], $params);
    }

    public function test_route_extracts_multiple_path_params()
    {
        $route = GET('/users/:userId/posts/:postId', fn($userId, $postId) => Ok([]));
        $params = $route->extractParams('/users/42/posts/99');
        
        $this->assertEquals(['userId' => 42, 'postId' => 99], $params);
    }

    public function test_route_coerces_numeric_params_to_int()
    {
        $route = GET('/users/:id', fn($id) => Ok([]));
        $params = $route->extractParams('/users/123');
        
        $this->assertIsInt($params['id']);
        $this->assertEquals(123, $params['id']);
    }

    public function test_route_keeps_non_numeric_params_as_string()
    {
        $route = GET('/users/:username', fn($username) => Ok([]));
        $params = $route->extractParams('/users/alice');
        
        $this->assertIsString($params['username']);
        $this->assertEquals('alice', $params['username']);
    }

    public function test_route_returns_null_for_non_matching_path()
    {
        $route = GET('/users/:id', fn($id) => Ok([]));
        $params = $route->extractParams('/posts/123');
        
        $this->assertNull($params);
    }
}
