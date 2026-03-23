<?php
namespace App;

/**
 * Simple URL router. Maps URI patterns to controller actions.
 */
class Router
{
    private array $routes = [];

    public function get(string $pattern, string $controller, string $method): void
    {
        $this->routes[] = ['GET', $pattern, $controller, $method];
    }

    public function post(string $pattern, string $controller, string $method): void
    {
        $this->routes[] = ['POST', $pattern, $controller, $method];
    }

    /**
     * Dispatch the current request to the matching route.
     * Returns [controllerClass, method, params] or null.
     */
    public function dispatch(string $httpMethod, string $uri): ?array
    {
        // Strip query string
        $uri = strtok($uri, '?');
        $uri = rtrim($uri, '/') ?: '/';

        foreach ($this->routes as [$routeMethod, $pattern, $controller, $action]) {
            if ($routeMethod !== $httpMethod) {
                continue;
            }
            // Convert route pattern to regex: {id} => named capture
            $regex = preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $pattern);
            $regex = '#^' . $regex . '$#';

            if (preg_match($regex, $uri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                return [$controller, $action, $params];
            }
        }
        return null;
    }
}
