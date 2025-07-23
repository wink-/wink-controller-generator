<?php

declare(strict_types=1);

namespace Wink\ControllerGenerator\Generators;

use Illuminate\Support\Str;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use RuntimeException;

/**
 * Abstract base class for all controller generators.
 * 
 * Provides common functionality for template loading, processing,
 * file writing, and namespace/class name handling.
 */
abstract class AbstractControllerGenerator
{
    protected Filesystem $filesystem;
    protected array $config;
    protected string $model;
    protected string $table;
    protected array $templateVars;

    public function __construct(Filesystem $filesystem, array $config = [])
    {
        $this->filesystem = $filesystem;
        $this->config = array_merge($this->getDefaultConfig(), $config);
        $this->templateVars = [];
    }

    /**
     * Generate the controller file.
     */
    abstract public function generate(string $model, array $options = []): string;

    /**
     * Get the default configuration for this generator.
     */
    abstract protected function getDefaultConfig(): array;

    /**
     * Get the template stub name for this generator.
     */
    abstract protected function getTemplateName(): string;

    /**
     * Get controller-specific template variables.
     */
    abstract protected function getControllerSpecificVars(): array;

    /**
     * Set the model name and derive related properties.
     */
    protected function setModel(string $model): void
    {
        $this->model = $model;
        $this->table = $this->getTableName($model);
        $this->initializeTemplateVars();
    }

    /**
     * Initialize common template variables.
     */
    protected function initializeTemplateVars(): void
    {
        $this->templateVars = [
            'namespace' => $this->getNamespace(),
            'class' => $this->getClassName(),
            'className' => $this->getClassName(),
            'model' => $this->getModelName(),
            'modelName' => $this->getModelName(),
            'modelVariable' => $this->getModelVariable(),
            'modelLowerPlural' => strtolower($this->getPluralName()),
            'tableName' => $this->table,
            'routeKey' => $this->getRouteKey(),
            'pluralName' => $this->getPluralName(),
            'singularName' => $this->getSingularName(),
            'titleCase' => $this->getTitleCase(),
            'camelCase' => $this->getCamelCase(),
            'snakeCase' => $this->getSnakeCase(),
            'kebabCase' => $this->getKebabCase(),
            'imports' => $this->getImports(),
            'middleware' => $this->formatMiddleware(),
            'traits' => $this->getTraits(),
        ];
    }

    /**
     * Load and process a template file.
     */
    protected function loadTemplate(string $templateName): string
    {
        $templatePath = $this->getTemplatePath($templateName);
        
        if (!$this->filesystem->exists($templatePath)) {
            throw new RuntimeException("Template file not found: {$templatePath}");
        }

        $content = $this->filesystem->get($templatePath);
        
        return $this->processTemplate($content);
    }

    /**
     * Process template content by replacing placeholders.
     */
    protected function processTemplate(string $content): string
    {
        $vars = array_merge($this->templateVars, $this->getControllerSpecificVars());
        
        foreach ($vars as $key => $value) {
            if (is_array($value)) {
                $value = $this->formatArrayValue($value);
            } elseif (is_bool($value)) {
                $value = $value ? 'true' : 'false';
            } elseif (is_null($value)) {
                $value = '';
            }
            
            $content = str_replace("{{ {$key} }}", (string) $value, $content);
        }

        return $content;
    }

    /**
     * Format array values for template replacement.
     */
    protected function formatArrayValue(array $value): string
    {
        if (empty($value)) {
            return '';
        }

        // Handle different array types
        if ($this->isAssociativeArray($value)) {
            return $this->formatAssociativeArray($value);
        }

        return implode(', ', array_map(function ($item) {
            return is_string($item) ? "'{$item}'" : $item;
        }, $value));
    }

    /**
     * Check if array is associative.
     */
    protected function isAssociativeArray(array $array): bool
    {
        return array_keys($array) !== range(0, count($array) - 1);
    }

    /**
     * Format associative array for template.
     */
    protected function formatAssociativeArray(array $array): string
    {
        $formatted = [];
        foreach ($array as $key => $value) {
            $formatted[] = "'{$key}' => '{$value}'";
        }
        return implode(', ', $formatted);
    }

    /**
     * Write the generated content to a file.
     */
    protected function writeFile(string $path, string $content): void
    {
        $directory = dirname($path);
        
        if (!$this->filesystem->isDirectory($directory)) {
            $this->filesystem->makeDirectory($directory, 0755, true);
        }

        $this->filesystem->put($path, $content);
    }

    /**
     * Get the full file path for the controller.
     */
    protected function getControllerPath(): string
    {
        $namespace = $this->getNamespace();
        $className = $this->getClassName();
        
        $basePath = base_path('app');
        $namespacePath = str_replace(['App\\', '\\'], ['', '/'], $namespace);
        
        return "{$basePath}/{$namespacePath}/{$className}.php";
    }

    /**
     * Get the template file path.
     */
    protected function getTemplatePath(string $templateName): string
    {
        $customPath = $this->config['templates']['path'] ?? null;
        
        if ($customPath && $this->filesystem->exists("{$customPath}/{$templateName}")) {
            return "{$customPath}/{$templateName}";
        }

        return __DIR__ . "/../../stubs/{$templateName}";
    }

    /**
     * Get the controller namespace.
     */
    protected function getNamespace(): string
    {
        return $this->config['namespace'] ?? 'App\\Http\\Controllers';
    }

    /**
     * Get the controller class name.
     */
    protected function getClassName(): string
    {
        return $this->getModelName() . 'Controller';
    }

    /**
     * Get the model name from the input.
     */
    protected function getModelName(): string
    {
        // Extract just the class name from namespaced models
        $model = str_replace(['/', '\\'], '\\', $this->model);
        $parts = explode('\\', $model);
        $className = array_pop($parts);
        
        return Str::studly(Str::singular($className));
    }

    /**
     * Get the fully qualified model class name.
     */
    protected function getFullModelClass(): string
    {
        // Handle namespaced models
        $model = str_replace(['/', '\\'], '\\', $this->model);
        
        // If already contains namespace, just prepend App\Models if needed
        if (str_contains($model, '\\')) {
            if (!str_starts_with($model, 'App\\Models\\')) {
                return 'App\\Models\\' . $model;
            }
            return $model;
        }
        
        // Simple model name
        return "App\\Models\\{$this->getModelName()}";
    }

    /**
     * Get the model variable name (camelCase).
     */
    protected function getModelVariable(): string
    {
        // Get just the class name from namespaced models
        return Str::camel(Str::singular($this->getModelName()));
    }

    /**
     * Get the table name from the model.
     */
    protected function getTableName(string $model): string
    {
        return Str::snake(Str::plural($model));
    }

    /**
     * Get the route key for the model.
     */
    protected function getRouteKey(): string
    {
        return Str::singular($this->table);
    }

    /**
     * Get the plural name.
     */
    protected function getPluralName(): string
    {
        return Str::plural($this->getModelVariable());
    }

    /**
     * Get the singular name.
     */
    protected function getSingularName(): string
    {
        return Str::singular($this->getModelVariable());
    }

    /**
     * Get title case name.
     */
    protected function getTitleCase(): string
    {
        return Str::title(str_replace('_', ' ', $this->getSingularName()));
    }

    /**
     * Get camel case name.
     */
    protected function getCamelCase(): string
    {
        return Str::camel($this->getSingularName());
    }

    /**
     * Get snake case name.
     */
    protected function getSnakeCase(): string
    {
        return Str::snake($this->getSingularName());
    }

    /**
     * Get kebab case name.
     */
    protected function getKebabCase(): string
    {
        return Str::kebab($this->getSingularName());
    }

    /**
     * Get the imports for the controller.
     */
    protected function getImports(): array
    {
        return [
            'Illuminate\\Http\\Request',
            'App\\Http\\Controllers\\Controller',
            $this->getFullModelClass(),
        ];
    }

    /**
     * Get middleware configuration.
     */
    protected function getMiddleware(): array
    {
        return $this->config['middleware'] ?? [];
    }

    /**
     * Format middleware for template replacement.
     */
    protected function formatMiddleware(): string
    {
        $middleware = $this->getMiddleware();
        return "'" . implode("', '", $middleware) . "'";
    }

    /**
     * Get traits to be used by the controller.
     */
    protected function getTraits(): array
    {
        return $this->config['traits'] ?? [];
    }

    /**
     * Validate the model name.
     */
    protected function validateModel(string $model): void
    {
        if (empty($model)) {
            throw new InvalidArgumentException('Model name cannot be empty');
        }

        // Allow namespaced models (e.g., GeneratedModels\pacsys\Action)
        if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_\\\\\/]*$/', $model)) {
            throw new InvalidArgumentException('Model name contains invalid characters');
        }
    }

    /**
     * Check if a model class exists.
     */
    protected function modelExists(string $model): bool
    {
        $modelClass = $this->getFullModelClass();
        return class_exists($modelClass);
    }

    /**
     * Get validation rules from model if available.
     */
    protected function getModelValidationRules(): array
    {
        $modelClass = $this->getFullModelClass();
        
        if (!class_exists($modelClass)) {
            return [];
        }

        $instance = new $modelClass();
        
        if (method_exists($instance, 'getValidationRules')) {
            return $instance->getValidationRules();
        }

        return [];
    }

    /**
     * Get model relationships for eager loading.
     */
    protected function getModelRelationships(): array
    {
        $modelClass = $this->getFullModelClass();
        
        if (!class_exists($modelClass)) {
            return [];
        }

        // This would be enhanced with actual relationship detection
        // For now, return empty array
        return [];
    }

    /**
     * Check if the model uses soft deletes.
     */
    protected function usesSoftDeletes(): bool
    {
        $modelClass = $this->getFullModelClass();
        
        if (!class_exists($modelClass)) {
            return false;
        }

        $instance = new $modelClass();
        return method_exists($instance, 'bootSoftDeletes');
    }

    /**
     * Get the fillable fields from the model.
     */
    protected function getFillableFields(): array
    {
        $modelClass = $this->getFullModelClass();
        
        if (!class_exists($modelClass)) {
            return [];
        }

        $instance = new $modelClass();
        return $instance->getFillable();
    }

    /**
     * Generate method documentation.
     */
    protected function generateMethodDoc(string $method, string $description): string
    {
        $modelName = $this->getTitleCase();
        
        return "    /**\n" .
               "     * {$description}.\n" .
               "     *\n" .
               "     * @param  \\Illuminate\\Http\\Request  \$request\n" .
               "     * @return mixed\n" .
               "     */";
    }

    /**
     * Format imports for template.
     */
    protected function formatImports(array $imports): string
    {
        if (empty($imports)) {
            return '';
        }

        $formatted = array_map(function ($import) {
            return "use {$import};";
        }, $imports);

        return implode("\n", $formatted);
    }

    /**
     * Get configuration value with fallback.
     */
    protected function getConfigValue(string $key, $default = null)
    {
        return data_get($this->config, $key, $default);
    }

    /**
     * Apply naming conventions from config.
     */
    protected function applyNamingConventions(string $name): string
    {
        $conventions = $this->getConfigValue('naming_conventions', []);
        
        foreach ($conventions as $pattern => $replacement) {
            $name = preg_replace($pattern, $replacement, $name);
        }

        return $name;
    }
}