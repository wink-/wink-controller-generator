<?php

namespace Wink\ControllerGenerator\Analyzers;

use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Facades\Route as RouteFacade;
use Illuminate\Support\Str;

/**
 * Analyzes existing routes to avoid conflicts, suggest route names and patterns,
 * and detect middleware requirements for controller generation.
 */
class RouteAnalyzer
{
    /**
     * The router instance
     */
    private Router $router;

    /**
     * Collection of all registered routes
     */
    private array $routes;

    public function __construct(?Router $router = null)
    {
        $this->router = $router ?? app('router');
        $this->routes = $this->getAllRoutes();
    }

    /**
     * Check if a route name already exists
     */
    public function routeNameExists(string $routeName): bool
    {
        return collect($this->routes)->contains(function ($route) use ($routeName) {
            return $route['name'] === $routeName;
        });
    }

    /**
     * Check if a route URI pattern conflicts with existing routes
     */
    public function routeUriConflicts(string $uri, string $method = 'GET'): bool
    {
        return collect($this->routes)->contains(function ($route) use ($uri, $method) {
            return in_array(strtoupper($method), $route['methods']) && 
                   $this->uriPatternsConflict($route['uri'], $uri);
        });
    }

    /**
     * Get suggested route names for a resource
     */
    public function suggestResourceRouteNames(string $resourceName): array
    {
        $baseName = Str::kebab($resourceName);
        
        return [
            'index' => $this->findAvailableRouteName("{$baseName}.index"),
            'create' => $this->findAvailableRouteName("{$baseName}.create"),
            'store' => $this->findAvailableRouteName("{$baseName}.store"),
            'show' => $this->findAvailableRouteName("{$baseName}.show"),
            'edit' => $this->findAvailableRouteName("{$baseName}.edit"),
            'update' => $this->findAvailableRouteName("{$baseName}.update"),
            'destroy' => $this->findAvailableRouteName("{$baseName}.destroy"),
        ];
    }

    /**
     * Get suggested URI patterns for a resource
     */
    public function suggestResourceUriPatterns(string $resourceName): array
    {
        $resourceSlug = Str::kebab(Str::plural($resourceName));
        $baseUri = $this->findAvailableUriPattern($resourceSlug);
        
        return [
            'index' => $baseUri,
            'create' => "{$baseUri}/create",
            'store' => $baseUri,
            'show' => "{$baseUri}/{{$resourceName}}",
            'edit' => "{$baseUri}/{{$resourceName}}/edit",
            'update' => "{$baseUri}/{{$resourceName}}",
            'destroy' => "{$baseUri}/{{$resourceName}}",
        ];
    }

    /**
     * Detect common middleware patterns used in existing routes
     */
    public function detectCommonMiddleware(): array
    {
        $middlewareUsage = [];
        
        foreach ($this->routes as $route) {
            foreach ($route['middleware'] as $middleware) {
                if (!isset($middlewareUsage[$middleware])) {
                    $middlewareUsage[$middleware] = 0;
                }
                $middlewareUsage[$middleware]++;
            }
        }

        // Sort by usage frequency
        arsort($middlewareUsage);
        
        return $middlewareUsage;
    }

    /**
     * Suggest middleware for a resource based on existing patterns
     */
    public function suggestMiddlewareForResource(string $resourceName): array
    {
        $commonMiddleware = $this->detectCommonMiddleware();
        $suggestions = [];

        // Add common authentication middleware
        if (isset($commonMiddleware['auth']) && $commonMiddleware['auth'] > 0) {
            $suggestions[] = 'auth';
        }

        // Add web middleware for web routes
        if (isset($commonMiddleware['web']) && $commonMiddleware['web'] > 0) {
            $suggestions[] = 'web';
        }

        // Add API middleware for API routes
        if (isset($commonMiddleware['api']) && $commonMiddleware['api'] > 0) {
            $suggestions[] = 'api';
        }

        return array_unique($suggestions);
    }

    /**
     * Get routes that might be related to a specific model/resource
     */
    public function getRelatedRoutes(string $resourceName): array
    {
        $searchTerms = [
            Str::kebab($resourceName),
            Str::kebab(Str::plural($resourceName)),
            Str::snake($resourceName),
            Str::snake(Str::plural($resourceName)),
            strtolower($resourceName),
            strtolower(Str::plural($resourceName)),
        ];

        return collect($this->routes)->filter(function ($route) use ($searchTerms) {
            foreach ($searchTerms as $term) {
                if (Str::contains($route['uri'], $term) || 
                    Str::contains($route['name'] ?? '', $term)) {
                    return true;
                }
            }
            return false;
        })->values()->toArray();
    }

    /**
     * Analyze route patterns to suggest best practices
     */
    public function analyzeRoutePatterns(): array
    {
        $analysis = [
            'total_routes' => count($this->routes),
            'named_routes' => 0,
            'resource_routes' => 0,
            'api_routes' => 0,
            'web_routes' => 0,
            'common_prefixes' => [],
            'middleware_usage' => $this->detectCommonMiddleware(),
        ];

        $prefixes = [];

        foreach ($this->routes as $route) {
            // Count named routes
            if (!empty($route['name'])) {
                $analysis['named_routes']++;
            }

            // Count resource-like routes
            if ($this->looksLikeResourceRoute($route)) {
                $analysis['resource_routes']++;
            }

            // Count API routes
            if (Str::startsWith($route['uri'], 'api/')) {
                $analysis['api_routes']++;
            }

            // Count web routes (routes with web middleware)
            if (in_array('web', $route['middleware'])) {
                $analysis['web_routes']++;
            }

            // Collect prefixes
            $prefix = $this->extractRoutePrefix($route['uri']);
            if ($prefix) {
                $prefixes[] = $prefix;
            }
        }

        $analysis['common_prefixes'] = array_count_values($prefixes);

        return $analysis;
    }

    /**
     * Get all registered routes with their information
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }

    /**
     * Check if a specific route exists by URI and method
     */
    public function routeExists(string $uri, string $method = 'GET'): bool
    {
        return $this->routeUriConflicts($uri, $method);
    }

    /**
     * Get all routes as a structured array
     */
    private function getAllRoutes(): array
    {
        $routes = [];
        
        foreach ($this->router->getRoutes() as $route) {
            $routes[] = [
                'uri' => $route->uri(),
                'name' => $route->getName(),
                'methods' => $route->methods(),
                'action' => $route->getAction(),
                'middleware' => $this->getRouteMiddleware($route),
                'parameters' => $route->parameterNames(),
            ];
        }

        return $routes;
    }

    /**
     * Get middleware for a specific route
     */
    private function getRouteMiddleware(Route $route): array
    {
        return $route->gatherMiddleware();
    }

    /**
     * Check if two URI patterns conflict
     */
    private function uriPatternsConflict(string $existing, string $new): bool
    {
        // Remove parameters for basic comparison
        $existingPattern = preg_replace('/\{[^}]+\}/', '*', $existing);
        $newPattern = preg_replace('/\{[^}]+\}/', '*', $new);
        
        return $existingPattern === $newPattern;
    }

    /**
     * Find an available route name by appending numbers if needed
     */
    private function findAvailableRouteName(string $baseName): string
    {
        if (!$this->routeNameExists($baseName)) {
            return $baseName;
        }

        $counter = 1;
        while ($this->routeNameExists("{$baseName}_{$counter}")) {
            $counter++;
        }

        return "{$baseName}_{$counter}";
    }

    /**
     * Find an available URI pattern by appending numbers if needed
     */
    private function findAvailableUriPattern(string $baseUri): string
    {
        if (!$this->routeUriConflicts($baseUri)) {
            return $baseUri;
        }

        $counter = 1;
        while ($this->routeUriConflicts("{$baseUri}_{$counter}")) {
            $counter++;
        }

        return "{$baseUri}_{$counter}";
    }

    /**
     * Check if a route looks like a resource route
     */
    private function looksLikeResourceRoute(array $route): bool
    {
        $resourceActions = ['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'];
        
        if (!empty($route['name'])) {
            $nameParts = explode('.', $route['name']);
            return count($nameParts) === 2 && in_array(end($nameParts), $resourceActions);
        }

        return false;
    }

    /**
     * Extract route prefix from URI
     */
    private function extractRoutePrefix(string $uri): ?string
    {
        $parts = explode('/', trim($uri, '/'));
        
        if (count($parts) > 1) {
            return $parts[0];
        }

        return null;
    }
}