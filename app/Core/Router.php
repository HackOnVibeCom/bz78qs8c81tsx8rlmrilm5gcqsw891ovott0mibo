<?php

declare(strict_types=1);

namespace ChallengeChain\Core;

final class Router
{
    /** @var array<int, array{method:string, regex:string, params:list<string>, handler:array{0:class-string,1:string}}>} */
    private array $routes = [];

    public function get(string $path, string $controller, string $action): void
    {
        $this->add('GET', $path, $controller, $action);
    }

    public function post(string $path, string $controller, string $action): void
    {
        $this->add('POST', $path, $controller, $action);
    }

    public function match(string $method, string $path, string $controller, string $action): void
    {
        $this->add(strtoupper($method), $path, $controller, $action);
    }

    private function add(string $method, string $path, string $controller, string $action): void
    {
        $params = [];
        $parts = preg_split('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', $path, -1, PREG_SPLIT_DELIM_CAPTURE);
        $regex = '#^';
        foreach ($parts as $i => $part) {
            if ($i % 2 === 0) {
                $regex .= preg_quote($part, '#');
            } else {
                $params[] = $part;
                $regex .= '(?P<' . $part . '>[^/]+)';
            }
        }
        $regex .= '$#';

        $this->routes[] = [
            'method' => strtoupper($method),
            'regex' => $regex,
            'params' => $params,
            'handler' => [$controller, $action],
        ];
    }

    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';
        $path = rawurldecode($path);
        $path = rtrim($path, '/') ?: '/';
        $method = strtoupper($method);
        if ($method === 'HEAD') {
            $method = 'GET';
        }

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }
            if (!preg_match($route['regex'], $path, $matches)) {
                continue;
            }
            $args = [];
            foreach ($route['params'] as $param) {
                $args[] = $matches[$param] ?? '';
            }
            [$class, $action] = $route['handler'];
            $controller = new $class();
            $result = $controller->{$action}(...$args);
            if (is_array($result)) {
                json_response($result);
            }
            return;
        }

        throw new HttpException(404, 'This page does not exist.');
    }
}
