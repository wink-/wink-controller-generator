<?php

declare(strict_types=1);

namespace Wink\ControllerGenerator\Generators;

use Illuminate\Support\Str;
use Wink\ControllerGenerator\Analyzers\ModelAnalyzer;

/**
 * Generator for web controllers.
 * 
 * Creates traditional web application controllers with:
 * - View rendering with data passing
 * - Flash messages and session handling
 * - Form validation integration
 * - Redirect responses
 * - Blade template integration
 */
class WebControllerGenerator extends AbstractControllerGenerator
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
     * Generate the web controller file.
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
     * Get the default configuration for web controllers.
     */
    protected function getDefaultConfig(): array
    {
        return [
            'namespace' => 'App\\Http\\Controllers',
            'middleware' => ['web'],
            'use_form_requests' => true,
            'include_authorization' => true,
            'include_flash_messages' => true,
            'include_csrf_protection' => true,
            'view_path' => null, // Auto-detected from model name
            'redirect_after_store' => 'index',
            'redirect_after_update' => 'show',
            'redirect_after_destroy' => 'index',
            'traits' => [],
            'pagination_limit' => 15,
        ];
    }

    /**
     * Get the template stub name for web controllers.
     */
    protected function getTemplateName(): string
    {
        return 'web-controller.stub';
    }

    /**
     * Get web controller specific template variables.
     */
    protected function getControllerSpecificVars(): array
    {
        $modelName = $this->getModelName();
        $modelLower = strtolower($modelName);
        $modelLowerPlural = strtolower($this->getPluralName());
        
        return [
            // Model variations
            'modelLower' => $modelLower,
            'modelLowerPlural' => $modelLowerPlural,
            'modelNamespace' => $this->getFullModelClass(),
            'primaryKey' => $this->getPrimaryKey(),
            
            // Authorization placeholders
            'authorizationMiddleware' => $this->getConfigValue('include_authorization', true) 
                ? '$this->authorizeResource(' . $modelName . '::class, \'' . $modelLower . '\');' 
                : '',
            'indexAuthorization' => $this->getConfigValue('include_authorization', true)
                ? '$this->authorize(\'viewAny\', ' . $modelName . '::class);'
                : '',
            'createAuthorization' => $this->getConfigValue('include_authorization', true)
                ? '$this->authorize(\'create\', ' . $modelName . '::class);'
                : '',
            'showAuthorization' => $this->getConfigValue('include_authorization', true)
                ? '$this->authorize(\'view\', $' . $modelLower . ');'
                : '',
            'editAuthorization' => $this->getConfigValue('include_authorization', true)
                ? '$this->authorize(\'update\', $' . $modelLower . ');'
                : '',
            'destroyAuthorization' => $this->getConfigValue('include_authorization', true)
                ? '$this->authorize(\'delete\', $' . $modelLower . ');'
                : '',
                
            // Index method variables
            'sortableFieldsList' => $this->getPrimaryKey() . ',created_at,updated_at',
            'indexValidationRules' => '',
            'searchFields' => '$query->where(\'id\', \'like\', "%{$searchTerm}%");',
            'indexFilters' => '// Add custom filters here',
            'defaultSortField' => $this->getPrimaryKey(),
            'eagerLoading' => '// $query->with([]);',
            'additionalIndexData' => '',
            
            // Create/Edit view data
            'createViewData' => '// Add data for create form',
            'editViewData' => '// Add data for edit form',
            'editRelationships' => '',
            
            // Store/Update processing
            'storeDataProcessing' => '$data = $request->validated();',
            'updateDataProcessing' => '$data = $request->validated();',
            'postStoreActions' => '// Additional actions after storing',
            'postUpdateActions' => '// Additional actions after updating',
            
            // Show method
            'showRelationships' => '',
            'additionalShowData' => '',
            
            // Delete method
            'relationshipChecks' => '// Check for dependent relationships',
            'preDeleteActions' => '// Actions before deletion',
            'postDeleteActions' => '// Actions after deletion',
            'softDeleteCheck' => '',
            
            // Additional methods
            'additionalMethods' => '// Add any additional controller methods here',
        ];
    }

    /**
     * Get imports specific to web controllers.
     */
    protected function getWebImports(): array
    {
        $imports = parent::getImports();
        
        // Add web-specific imports
        $imports[] = 'Illuminate\\Http\\RedirectResponse';
        $imports[] = 'Illuminate\\View\\View';
        
        if ($this->getConfigValue('use_form_requests', true)) {
            $imports[] = "App\\Http\\Requests\\{$this->getStoreRequestClassName()}";
            $imports[] = "App\\Http\\Requests\\{$this->getUpdateRequestClassName()}";
        }
        
        return array_unique($imports);
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
        $middleware = $this->getMiddleware();
        
        if (empty($middleware)) {
            return '';
        }

        $middlewareArray = "['" . implode("', '", $middleware) . "']";
        
        return "    public function __construct()\n" .
               "    {\n" .
               "        \$this->middleware({$middlewareArray});\n" .
               "    }";
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
        $method .= "    public function index(): View\n";
        $method .= "    {\n";
        
        if ($this->getConfigValue('include_authorization', true)) {
            $method .= "        \$this->authorize('viewAny', {$modelName}::class);\n\n";
        }
        
        $method .= "        \${$modelVariable} = {$modelName}::query()";
        
        $eagerLoad = $this->getEagerLoadRelationships();
        if (!empty($eagerLoad)) {
            $relations = "['" . implode("', '", $eagerLoad) . "']";
            $method .= "\n            ->with({$relations})";
        }
        
        $method .= "\n            ->paginate({$paginationLimit});\n\n";
        $method .= "        return view('{$viewPath}.index', compact('{$modelVariable}'));\n";
        $method .= "    }";
        
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
        $method .= "    public function create(): View\n";
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
        $redirectTarget = $this->getConfigValue('redirect_after_store', 'index');
        
        $method = "    /**\n";
        $method .= "     * Store a newly created {$this->getTitleCase()} resource.\n";
        $method .= "     */\n";
        $method .= "    public function store({$requestClass} \$request): RedirectResponse\n";
        $method .= "    {\n";
        
        if ($this->getConfigValue('include_authorization', true)) {
            $method .= "        \$this->authorize('create', {$modelName}::class);\n\n";
        }
        
        $validationMethod = $this->getConfigValue('use_form_requests', true) 
            ? 'validated()' 
            : 'all()';
            
        $method .= "        \${$modelVariable} = {$modelName}::create(\$request->{$validationMethod});\n\n";
        
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
        $method .= "    public function show({$modelName} \${$modelVariable}): View\n";
        $method .= "    {\n";
        
        if ($this->getConfigValue('include_authorization', true)) {
            $method .= "        \$this->authorize('view', \${$modelVariable});\n\n";
        }
        
        $eagerLoad = $this->getEagerLoadRelationships();
        if (!empty($eagerLoad)) {
            $relations = "['" . implode("', '", $eagerLoad) . "']";
            $method .= "        \${$modelVariable}->load({$relations});\n\n";
        }
        
        $method .= "        return view('{$viewPath}.show', compact('{$modelVariable}'));\n";
        $method .= "    }";
        
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
        $method .= "    public function update({$requestClass} \$request, {$modelName} \${$modelVariable}): RedirectResponse\n";
        $method .= "    {\n";
        
        if ($this->getConfigValue('include_authorization', true)) {
            $method .= "        \$this->authorize('update', \${$modelVariable});\n\n";
        }
        
        $validationMethod = $this->getConfigValue('use_form_requests', true) 
            ? 'validated()' 
            : 'all()';
            
        $method .= "        \${$modelVariable}->update(\$request->{$validationMethod});\n\n";
        
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
        $method .= "    public function destroy({$modelName} \${$modelVariable}): RedirectResponse\n";
        $method .= "    {\n";
        
        if ($this->getConfigValue('include_authorization', true)) {
            $method .= "        \$this->authorize('delete', \${$modelVariable});\n\n";
        }
        
        $method .= "        \${$modelVariable}->delete();\n\n";
        
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
    public function generateRouteDefinitions(): string
    {
        $controllerClass = $this->getClassName();
        $routeName = $this->getKebabCase();
        
        return "Route::resource('{$routeName}', {$controllerClass}::class);";
    }

    /**
     * Generate view file suggestions.
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
     * Get the primary key for the model.
     */
    protected function getPrimaryKey(): string
    {
        $modelClass = $this->getFullModelClass();
        
        if (!class_exists($modelClass)) {
            return 'id'; // Default fallback
        }
        
        try {
            $instance = new $modelClass();
            return $instance->getKeyName();
        } catch (\Exception $e) {
            return 'id'; // Default fallback
        }
    }

    /**
     * Generate view content suggestions.
     */
    public function generateViewContent(string $viewType): string
    {
        $modelName = $this->getTitleCase();
        $modelVariable = $this->getModelVariable();
        $pluralVariable = $this->getPluralName();
        
        switch ($viewType) {
            case 'index':
                return $this->generateIndexView($modelName, $pluralVariable);
            case 'create':
                return $this->generateCreateView($modelName);
            case 'show':
                return $this->generateShowView($modelName, $modelVariable);
            case 'edit':
                return $this->generateEditView($modelName, $modelVariable);
            default:
                return '';
        }
    }

    /**
     * Generate index view content.
     */
    protected function generateIndexView(string $modelName, string $pluralVariable): string
    {
        return "@extends('layouts.app')\n\n" .
               "@section('title', '{$modelName} List')\n\n" .
               "@section('content')\n" .
               "<div class=\"container\">\n" .
               "    <div class=\"row\">\n" .
               "        <div class=\"col-md-12\">\n" .
               "            <h1>{$modelName} List</h1>\n" .
               "            <a href=\"{{ route('{$this->getKebabCase()}.create') }}\" class=\"btn btn-primary mb-3\">Create New</a>\n" .
               "            \n" .
               "            <div class=\"table-responsive\">\n" .
               "                <table class=\"table table-striped\">\n" .
               "                    <thead>\n" .
               "                        <tr>\n" .
               "                            <th>ID</th>\n" .
               "                            <th>Actions</th>\n" .
               "                        </tr>\n" .
               "                    </thead>\n" .
               "                    <tbody>\n" .
               "                        @forelse(\${$pluralVariable} as \${$this->getModelVariable()})\n" .
               "                            <tr>\n" .
               "                                <td>{{ \${$this->getModelVariable()}->id }}</td>\n" .
               "                                <td>\n" .
               "                                    <a href=\"{{ route('{$this->getKebabCase()}.show', \${$this->getModelVariable()}) }}\" class=\"btn btn-sm btn-info\">View</a>\n" .
               "                                    <a href=\"{{ route('{$this->getKebabCase()}.edit', \${$this->getModelVariable()}) }}\" class=\"btn btn-sm btn-warning\">Edit</a>\n" .
               "                                </td>\n" .
               "                            </tr>\n" .
               "                        @empty\n" .
               "                            <tr>\n" .
               "                                <td colspan=\"2\" class=\"text-center\">No {$modelName} found.</td>\n" .
               "                            </tr>\n" .
               "                        @endforelse\n" .
               "                    </tbody>\n" .
               "                </table>\n" .
               "            </div>\n" .
               "            \n" .
               "            {{ \${$pluralVariable}->links() }}\n" .
               "        </div>\n" .
               "    </div>\n" .
               "</div>\n" .
               "@endsection";
    }

    /**
     * Generate create view content.
     */
    protected function generateCreateView(string $modelName): string
    {
        return "@extends('layouts.app')\n\n" .
               "@section('title', 'Create {$modelName}')\n\n" .
               "@section('content')\n" .
               "<div class=\"container\">\n" .
               "    <div class=\"row\">\n" .
               "        <div class=\"col-md-8\">\n" .
               "            <h1>Create {$modelName}</h1>\n" .
               "            \n" .
               "            <form method=\"POST\" action=\"{{ route('{$this->getKebabCase()}.store') }}\">\n" .
               "                @csrf\n" .
               "                \n" .
               "                {{-- Add your form fields here --}}\n" .
               "                \n" .
               "                <div class=\"mb-3\">\n" .
               "                    <button type=\"submit\" class=\"btn btn-primary\">Create</button>\n" .
               "                    <a href=\"{{ route('{$this->getKebabCase()}.index') }}\" class=\"btn btn-secondary\">Cancel</a>\n" .
               "                </div>\n" .
               "            </form>\n" .
               "        </div>\n" .
               "    </div>\n" .
               "</div>\n" .
               "@endsection";
    }

    /**
     * Generate show view content.
     */
    protected function generateShowView(string $modelName, string $modelVariable): string
    {
        return "@extends('layouts.app')\n\n" .
               "@section('title', 'View {$modelName}')\n\n" .
               "@section('content')\n" .
               "<div class=\"container\">\n" .
               "    <div class=\"row\">\n" .
               "        <div class=\"col-md-8\">\n" .
               "            <h1>{$modelName} Details</h1>\n" .
               "            \n" .
               "            <div class=\"card\">\n" .
               "                <div class=\"card-body\">\n" .
               "                    <p><strong>ID:</strong> {{ \${$modelVariable}->id }}</p>\n" .
               "                    {{-- Add other fields here --}}\n" .
               "                </div>\n" .
               "            </div>\n" .
               "            \n" .
               "            <div class=\"mt-3\">\n" .
               "                <a href=\"{{ route('{$this->getKebabCase()}.edit', \${$modelVariable}) }}\" class=\"btn btn-warning\">Edit</a>\n" .
               "                <a href=\"{{ route('{$this->getKebabCase()}.index') }}\" class=\"btn btn-secondary\">Back to List</a>\n" .
               "            </div>\n" .
               "        </div>\n" .
               "    </div>\n" .
               "</div>\n" .
               "@endsection";
    }

    /**
     * Generate edit view content.
     */
    protected function generateEditView(string $modelName, string $modelVariable): string
    {
        return "@extends('layouts.app')\n\n" .
               "@section('title', 'Edit {$modelName}')\n\n" .
               "@section('content')\n" .
               "<div class=\"container\">\n" .
               "    <div class=\"row\">\n" .
               "        <div class=\"col-md-8\">\n" .
               "            <h1>Edit {$modelName}</h1>\n" .
               "            \n" .
               "            <form method=\"POST\" action=\"{{ route('{$this->getKebabCase()}.update', \${$modelVariable}) }}\">\n" .
               "                @csrf\n" .
               "                @method('PUT')\n" .
               "                \n" .
               "                {{-- Add your form fields here --}}\n" .
               "                \n" .
               "                <div class=\"mb-3\">\n" .
               "                    <button type=\"submit\" class=\"btn btn-primary\">Update</button>\n" .
               "                    <a href=\"{{ route('{$this->getKebabCase()}.show', \${$modelVariable}) }}\" class=\"btn btn-secondary\">Cancel</a>\n" .
               "                </div>\n" .
               "            </form>\n" .
               "        </div>\n" .
               "    </div>\n" .
               "</div>\n" .
               "@endsection";
    }
}