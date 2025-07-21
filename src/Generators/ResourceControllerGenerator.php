<?php

declare(strict_types=1);

namespace Wink\ControllerGenerator\Generators;

use Illuminate\Support\Str;
use Wink\ControllerGenerator\Analyzers\ModelAnalyzer;

/**
 * Generator for resource (hybrid) controllers.
 * 
 * Creates hybrid controllers supporting both API and web with:
 * - Content negotiation (Accept header detection)
 * - Dual response formats (JSON/HTML)
 * - Shared validation logic
 * - Consistent error handling
 */
class ResourceControllerGenerator extends AbstractControllerGenerator
{
    protected ModelAnalyzer $modelAnalyzer;
    
    public function __construct(
        \Illuminate\Filesystem\Filesystem $filesystem,
        ModelAnalyzer $modelAnalyzer,
        array $config = []
    ) {
        parent::__construct($filesystem, $config);
        $this->modelAnalyzer = $modelAnalyzer;
    }
    
    /**
     * Generate the resource controller file.
     */
    public function generate(string $model, array $options = []): string
    {
        $this->validateModel($model);
        $this->setModel($model);
        
        // Merge options with defaults
        $this->config = array_merge($this->config, $options);
        
        $content = $this->loadTemplate($this->getTemplateName());
        $path = $this->getControllerPath();
        
        $this->writeFile($path, $content);
        
        return $path;
    }

    /**
     * Get the default configuration for resource controllers.
     */
    protected function getDefaultConfig(): array
    {
        return [
            'namespace' => 'App\\Http\\Controllers',
            'middleware' => ['web'],
            'api_middleware' => ['api', 'throttle:60,1'],
            'use_resources' => true,
            'use_form_requests' => true,
            'include_authorization' => true,
            'include_flash_messages' => true,
            'include_content_negotiation' => true,
            'include_swagger_docs' => true,
            'view_path' => null, // Auto-detected from model name
            'redirect_after_store' => 'show',
            'redirect_after_update' => 'show',
            'redirect_after_destroy' => 'index',
            'traits' => [],
            'pagination_limit' => 15,
            'default_format' => 'html', // html or json
        ];
    }

    /**
     * Get the template stub name for resource controllers.
     */
    protected function getTemplateName(): string
    {
        return 'resource-controller.stub';
    }

    /**
     * Get resource controller specific template variables.
     */
    protected function getControllerSpecificVars(): array
    {
        return [
            'resourceClass' => $this->getResourceClassName(),
            'storeRequestClass' => $this->getStoreRequestClassName(),
            'updateRequestClass' => $this->getUpdateRequestClassName(),
            'indexMethod' => $this->generateIndexMethod(),
            'createMethod' => $this->generateCreateMethod(),
            'storeMethod' => $this->generateStoreMethod(),
            'showMethod' => $this->generateShowMethod(),
            'editMethod' => $this->generateEditMethod(),
            'updateMethod' => $this->generateUpdateMethod(),
            'destroyMethod' => $this->generateDestroyMethod(),
            'constructorMethod' => $this->generateConstructorMethod(),
            'contentNegotiationMethods' => $this->generateContentNegotiationMethods(),
            'imports' => $this->formatImports($this->getResourceImports()),
            'viewPath' => $this->getViewPath(),
            'usesResources' => $this->getConfigValue('use_resources', true),
            'usesFormRequests' => $this->getConfigValue('use_form_requests', true),
            'includesAuthorization' => $this->getConfigValue('include_authorization', true),
            'includesFlashMessages' => $this->getConfigValue('include_flash_messages', true),
            'includesContentNegotiation' => $this->getConfigValue('include_content_negotiation', true),
        ];
    }

    /**
     * Get imports specific to resource controllers.
     */
    protected function getResourceImports(): array
    {
        $imports = parent::getImports();
        
        // Add resource-specific imports
        $imports[] = 'Illuminate\\Http\\JsonResponse';
        $imports[] = 'Illuminate\\Http\\RedirectResponse';
        $imports[] = 'Illuminate\\View\\View';
        
        if ($this->getConfigValue('use_resources', true)) {
            $imports[] = "App\\Http\\Resources\\{$this->getResourceClassName()}";
        }
        
        if ($this->getConfigValue('use_form_requests', true)) {
            $imports[] = "App\\Http\\Requests\\{$this->getStoreRequestClassName()}";
            $imports[] = "App\\Http\\Requests\\{$this->getUpdateRequestClassName()}";
        }
        
        return array_unique($imports);
    }

    /**
     * Get the API resource class name.
     */
    protected function getResourceClassName(): string
    {
        return $this->getModelName() . 'Resource';
    }

    /**
     * Get the store request class name.
     */
    protected function getStoreRequestClassName(): string
    {
        return 'Store' . $this->getModelName() . 'Request';
    }

    /**
     * Get the update request class name.
     */
    protected function getUpdateRequestClassName(): string
    {
        return 'Update' . $this->getModelName() . 'Request';
    }

    /**
     * Get the view path for the controller.
     */
    protected function getViewPath(): string
    {
        return $this->getConfigValue('view_path') ?? $this->getKebabCase();
    }

    /**
     * Generate the constructor method.
     */
    protected function generateConstructorMethod(): string
    {
        $webMiddleware = $this->getMiddleware();
        $apiMiddleware = $this->getConfigValue('api_middleware', []);
        
        $method = "    public function __construct()\n";
        $method .= "    {\n";
        
        if (!empty($webMiddleware)) {
            $webMiddlewareArray = "['" . implode("', '", $webMiddleware) . "']";
            $method .= "        \$this->middleware({$webMiddlewareArray})->except(['index', 'show']);\n";
        }
        
        if (!empty($apiMiddleware)) {
            $apiMiddlewareArray = "['" . implode("', '", $apiMiddleware) . "']";
            $method .= "        \$this->middleware({$apiMiddlewareArray})->only(['index', 'show']);\n";
        }
        
        $method .= "    }";
        
        return $method;
    }

    /**
     * Generate content negotiation helper methods.
     */
    protected function generateContentNegotiationMethods(): string
    {
        if (!$this->getConfigValue('include_content_negotiation', true)) {
            return '';
        }

        $methods = "    /**\n";
        $methods .= "     * Determine if the request expects a JSON response.\n";
        $methods .= "     */\n";
        $methods .= "    protected function expectsJson(Request \$request): bool\n";
        $methods .= "    {\n";
        $methods .= "        return \$request->expectsJson() || \$request->is('api/*');\n";
        $methods .= "    }\n\n";

        $methods .= "    /**\n";
        $methods .= "     * Create a response based on content negotiation.\n";
        $methods .= "     */\n";
        $methods .= "    protected function createResponse(Request \$request, \$data, string \$view = null, int \$status = 200)\n";
        $methods .= "    {\n";
        $methods .= "        if (\$this->expectsJson(\$request)) {\n";
        $methods .= "            return response()->json(\$data, \$status);\n";
        $methods .= "        }\n\n";
        $methods .= "        if (\$view) {\n";
        $methods .= "            return view(\$view, \$data);\n";
        $methods .= "        }\n\n";
        $methods .= "        return response(\$data, \$status);\n";
        $methods .= "    }\n\n";

        $methods .= "    /**\n";
        $methods .= "     * Create a resource response with content negotiation.\n";
        $methods .= "     */\n";
        $methods .= "    protected function createResourceResponse(Request \$request, \$resource, string \$view = null, int \$status = 200)\n";
        $methods .= "    {\n";
        $methods .= "        if (\$this->expectsJson(\$request)) {\n";
        
        if ($this->getConfigValue('use_resources', true)) {
            $resourceClass = $this->getResourceClassName();
            $methods .= "            if (is_iterable(\$resource)) {\n";
            $methods .= "                return {$resourceClass}::collection(\$resource)\n";
            $methods .= "                    ->response()\n";
            $methods .= "                    ->setStatusCode(\$status);\n";
            $methods .= "            }\n\n";
            $methods .= "            return (new {$resourceClass}(\$resource))\n";
            $methods .= "                ->response()\n";
            $methods .= "                ->setStatusCode(\$status);\n";
        } else {
            $methods .= "            return response()->json(\$resource, \$status);\n";
        }
        
        $methods .= "        }\n\n";
        $methods .= "        if (\$view) {\n";
        $methods .= "            return view(\$view, compact('resource'));\n";
        $methods .= "        }\n\n";
        $methods .= "        return response(\$resource, \$status);\n";
        $methods .= "    }";

        return $methods;
    }

    /**
     * Generate the index method.
     */
    protected function generateIndexMethod(): string
    {
        $modelName = $this->getModelName();
        $modelVariable = $this->getPluralName();
        $viewPath = $this->getViewPath();
        $paginationLimit = $this->getConfigValue('pagination_limit', 15);
        
        $method = "    /**\n";
        $method .= "     * Display a listing of {$this->getTitleCase()} resources.\n";
        $method .= "     */\n";
        $method .= "    public function index(Request \$request)\n";
        $method .= "    {\n";
        
        if ($this->getConfigValue('include_authorization', true)) {
            $method .= "        \$this->authorize('viewAny', {$modelName}::class);\n\n";
        }
        
        $method .= "        \${$modelVariable} = {$modelName}::query()";
        
        // Add search functionality for API requests
        $method .= "\n            ->when(\$request->search, fn(\$q) => \$q->where('{$this->getSearchField()}', 'like', \"%{\$request->search}%\"))";
        $method .= "\n            ->when(\$request->status, fn(\$q) => \$q->where('status', \$request->status))";
        
        $eagerLoad = $this->getEagerLoadRelationships();
        if (!empty($eagerLoad)) {
            $relations = "['" . implode("', '", $eagerLoad) . "']";
            $method .= "\n            ->with({$relations})";
        }
        
        $method .= "\n            ->paginate(\$request->per_page ?? {$paginationLimit});\n\n";
        
        if ($this->getConfigValue('include_content_negotiation', true)) {
            $method .= "        return \$this->createResourceResponse(\$request, \${$modelVariable}, '{$viewPath}.index');";
        } else {
            $method .= "        if (\$request->expectsJson()) {\n";
            if ($this->getConfigValue('use_resources', true)) {
                $resourceClass = $this->getResourceClassName();
                $method .= "            return {$resourceClass}::collection(\${$modelVariable});\n";
            } else {
                $method .= "            return response()->json(\${$modelVariable});\n";
            }
            $method .= "        }\n\n";
            $method .= "        return view('{$viewPath}.index', compact('{$modelVariable}'));";
        }
        
        $method .= "\n    }";
        
        return $method;
    }

    /**
     * Generate the create method.
     */
    protected function generateCreateMethod(): string
    {
        $modelName = $this->getModelName();
        $viewPath = $this->getViewPath();
        
        $method = "    /**\n";
        $method .= "     * Show the form for creating a new {$this->getTitleCase()} resource.\n";
        $method .= "     */\n";
        $method .= "    public function create(Request \$request): View\n";
        $method .= "    {\n";
        
        if ($this->getConfigValue('include_authorization', true)) {
            $method .= "        \$this->authorize('create', {$modelName}::class);\n\n";
        }
        
        $method .= "        return view('{$viewPath}.create');\n";
        $method .= "    }";
        
        return $method;
    }

    /**
     * Generate the store method.
     */
    protected function generateStoreMethod(): string
    {
        $modelName = $this->getModelName();
        $modelVariable = $this->getModelVariable();
        $requestClass = $this->getConfigValue('use_form_requests', true) 
            ? $this->getStoreRequestClassName() 
            : 'Request';
        $redirectTarget = $this->getConfigValue('redirect_after_store', 'show');
        $viewPath = $this->getViewPath();
        
        $method = "    /**\n";
        $method .= "     * Store a newly created {$this->getTitleCase()} resource.\n";
        $method .= "     */\n";
        $method .= "    public function store({$requestClass} \$request)\n";
        $method .= "    {\n";
        
        if ($this->getConfigValue('include_authorization', true)) {
            $method .= "        \$this->authorize('create', {$modelName}::class);\n\n";
        }
        
        $validationMethod = $this->getConfigValue('use_form_requests', true) 
            ? 'validated()' 
            : 'all()';
            
        $method .= "        \${$modelVariable} = {$modelName}::create(\$request->{$validationMethod});\n\n";
        
        $method .= "        if (\$this->expectsJson(\$request)) {\n";
        if ($this->getConfigValue('use_resources', true)) {
            $resourceClass = $this->getResourceClassName();
            $method .= "            return (new {$resourceClass}(\${$modelVariable}))\n";
            $method .= "                ->response()\n";
            $method .= "                ->setStatusCode(201);\n";
        } else {
            $method .= "            return response()->json(\${$modelVariable}, 201);\n";
        }
        $method .= "        }\n\n";
        
        if ($this->getConfigValue('include_flash_messages', true)) {
            $method .= "        return redirect()->route('{$this->getKebabCase()}.{$redirectTarget}', \${$modelVariable})\n";
            $method .= "            ->with('success', '{$this->getTitleCase()} created successfully.');";
        } else {
            $method .= "        return redirect()->route('{$this->getKebabCase()}.{$redirectTarget}', \${$modelVariable});";
        }
        
        $method .= "\n    }";
        
        return $method;
    }

    /**
     * Generate the show method.
     */
    protected function generateShowMethod(): string
    {
        $modelName = $this->getModelName();
        $modelVariable = $this->getModelVariable();
        $viewPath = $this->getViewPath();
        
        $method = "    /**\n";
        $method .= "     * Display the specified {$this->getTitleCase()} resource.\n";
        $method .= "     */\n";
        $method .= "    public function show(Request \$request, {$modelName} \${$modelVariable})\n";
        $method .= "    {\n";
        
        if ($this->getConfigValue('include_authorization', true)) {
            $method .= "        \$this->authorize('view', \${$modelVariable});\n\n";
        }
        
        $eagerLoad = $this->getEagerLoadRelationships();
        if (!empty($eagerLoad)) {
            $relations = "['" . implode("', '", $eagerLoad) . "']";
            $method .= "        \${$modelVariable}->load({$relations});\n\n";
        }
        
        if ($this->getConfigValue('include_content_negotiation', true)) {
            $method .= "        return \$this->createResourceResponse(\$request, \${$modelVariable}, '{$viewPath}.show');";
        } else {
            $method .= "        if (\$this->expectsJson(\$request)) {\n";
            if ($this->getConfigValue('use_resources', true)) {
                $resourceClass = $this->getResourceClassName();
                $method .= "            return new {$resourceClass}(\${$modelVariable});\n";
            } else {
                $method .= "            return response()->json(\${$modelVariable});\n";
            }
            $method .= "        }\n\n";
            $method .= "        return view('{$viewPath}.show', compact('{$modelVariable}'));";
        }
        
        $method .= "\n    }";
        
        return $method;
    }

    /**
     * Generate the edit method.
     */
    protected function generateEditMethod(): string
    {
        $modelName = $this->getModelName();
        $modelVariable = $this->getModelVariable();
        $viewPath = $this->getViewPath();
        
        $method = "    /**\n";
        $method .= "     * Show the form for editing the specified {$this->getTitleCase()} resource.\n";
        $method .= "     */\n";
        $method .= "    public function edit({$modelName} \${$modelVariable}): View\n";
        $method .= "    {\n";
        
        if ($this->getConfigValue('include_authorization', true)) {
            $method .= "        \$this->authorize('update', \${$modelVariable});\n\n";
        }
        
        $method .= "        return view('{$viewPath}.edit', compact('{$modelVariable}'));\n";
        $method .= "    }";
        
        return $method;
    }

    /**
     * Generate the update method.
     */
    protected function generateUpdateMethod(): string
    {
        $modelName = $this->getModelName();
        $modelVariable = $this->getModelVariable();
        $requestClass = $this->getConfigValue('use_form_requests', true) 
            ? $this->getUpdateRequestClassName() 
            : 'Request';
        $redirectTarget = $this->getConfigValue('redirect_after_update', 'show');
        
        $method = "    /**\n";
        $method .= "     * Update the specified {$this->getTitleCase()} resource.\n";
        $method .= "     */\n";
        $method .= "    public function update({$requestClass} \$request, {$modelName} \${$modelVariable})\n";
        $method .= "    {\n";
        
        if ($this->getConfigValue('include_authorization', true)) {
            $method .= "        \$this->authorize('update', \${$modelVariable});\n\n";
        }
        
        $validationMethod = $this->getConfigValue('use_form_requests', true) 
            ? 'validated()' 
            : 'all()';
            
        $method .= "        \${$modelVariable}->update(\$request->{$validationMethod});\n\n";
        
        $method .= "        if (\$this->expectsJson(\$request)) {\n";
        if ($this->getConfigValue('use_resources', true)) {
            $resourceClass = $this->getResourceClassName();
            $method .= "            return (new {$resourceClass}(\${$modelVariable}->fresh()))\n";
            $method .= "                ->response()\n";
            $method .= "                ->setStatusCode(200);\n";
        } else {
            $method .= "            return response()->json(\${$modelVariable}->fresh());\n";
        }
        $method .= "        }\n\n";
        
        if ($this->getConfigValue('include_flash_messages', true)) {
            $method .= "        return redirect()->route('{$this->getKebabCase()}.{$redirectTarget}', \${$modelVariable})\n";
            $method .= "            ->with('success', '{$this->getTitleCase()} updated successfully.');";
        } else {
            $method .= "        return redirect()->route('{$this->getKebabCase()}.{$redirectTarget}', \${$modelVariable});";
        }
        
        $method .= "\n    }";
        
        return $method;
    }

    /**
     * Generate the destroy method.
     */
    protected function generateDestroyMethod(): string
    {
        $modelName = $this->getModelName();
        $modelVariable = $this->getModelVariable();
        $redirectTarget = $this->getConfigValue('redirect_after_destroy', 'index');
        
        $method = "    /**\n";
        $method .= "     * Remove the specified {$this->getTitleCase()} resource.\n";
        $method .= "     */\n";
        $method .= "    public function destroy(Request \$request, {$modelName} \${$modelVariable})\n";
        $method .= "    {\n";
        
        if ($this->getConfigValue('include_authorization', true)) {
            $method .= "        \$this->authorize('delete', \${$modelVariable});\n\n";
        }
        
        $method .= "        \${$modelVariable}->delete();\n\n";
        
        $method .= "        if (\$this->expectsJson(\$request)) {\n";
        $method .= "            return response()->json([\n";
        $method .= "                'message' => '{$this->getTitleCase()} deleted successfully'\n";
        $method .= "            ], 204);\n";
        $method .= "        }\n\n";
        
        if ($this->getConfigValue('include_flash_messages', true)) {
            $method .= "        return redirect()->route('{$this->getKebabCase()}.{$redirectTarget}')\n";
            $method .= "            ->with('success', '{$this->getTitleCase()} deleted successfully.');";
        } else {
            $method .= "        return redirect()->route('{$this->getKebabCase()}.{$redirectTarget}');";
        }
        
        $method .= "\n    }";
        
        return $method;
    }

    /**
     * Get the primary search field for the model.
     */
    protected function getSearchField(): string
    {
        $fillable = $this->getFillableFields();
        
        // Common search field patterns
        $searchFields = ['name', 'title', 'subject', 'description', 'email'];
        
        foreach ($searchFields as $field) {
            if (in_array($field, $fillable)) {
                return $field;
            }
        }
        
        // Fallback to first fillable field
        return $fillable[0] ?? 'id';
    }

    /**
     * Get relationships for eager loading.
     */
    protected function getEagerLoadRelationships(): array
    {
        // This would be enhanced with actual relationship detection
        // For now, return common relationship patterns
        return $this->getModelRelationships();
    }

    /**
     * Generate route suggestions for the controller.
     */
    public function generateRouteDefinitions(): array
    {
        $controllerClass = $this->getClassName();
        $routeName = $this->getKebabCase();
        
        return [
            'web' => "Route::resource('{$routeName}', {$controllerClass}::class);",
            'api' => "Route::apiResource('{$routeName}', {$controllerClass}::class);",
        ];
    }

    /**
     * Generate API route definitions specifically.
     */
    public function generateApiRouteDefinitions(): string
    {
        $controllerClass = $this->getClassName();
        $routeName = $this->getKebabCase();
        
        return "Route::apiResource('{$routeName}', {$controllerClass}::class);";
    }

    /**
     * Generate web route definitions specifically.
     */
    public function generateWebRouteDefinitions(): string
    {
        $controllerClass = $this->getClassName();
        $routeName = $this->getKebabCase();
        
        return "Route::resource('{$routeName}', {$controllerClass}::class);";
    }

    /**
     * Get required view files for web functionality.
     */
    public function getRequiredViews(): array
    {
        $viewPath = $this->getViewPath();
        
        return [
            "{$viewPath}/index.blade.php",
            "{$viewPath}/create.blade.php",
            "{$viewPath}/show.blade.php",
            "{$viewPath}/edit.blade.php",
        ];
    }

    /**
     * Check if content negotiation is enabled.
     */
    public function usesContentNegotiation(): bool
    {
        return $this->getConfigValue('include_content_negotiation', true);
    }

    /**
     * Get supported response formats.
     */
    public function getSupportedFormats(): array
    {
        return ['html', 'json'];
    }

    /**
     * Get the default response format.
     */
    public function getDefaultFormat(): string
    {
        return $this->getConfigValue('default_format', 'html');
    }
}