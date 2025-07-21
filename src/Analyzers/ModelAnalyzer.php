<?php

declare(strict_types=1);

namespace Wink\ControllerGenerator\Analyzers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionUnionType;

class ModelAnalyzer
{
    /**
     * Check if a model class exists.
     */
    public function modelExists(string $modelClass): bool
    {
        return class_exists($modelClass) && is_subclass_of($modelClass, Model::class);
    }

    /**
     * Analyze a model and return comprehensive information about it.
     */
    public function analyze(string $modelClass): array
    {
        if (!$this->modelExists($modelClass)) {
            throw new \InvalidArgumentException("Model class {$modelClass} does not exist or is not an Eloquent model");
        }

        $reflection = new ReflectionClass($modelClass);
        $instance = new $modelClass();

        return [
            'class' => $modelClass,
            'table' => $instance->getTable(),
            'connection' => $instance->getConnectionName(),
            'primaryKey' => $instance->getKeyName(),
            'keyType' => $instance->getKeyType(),
            'incrementing' => $instance->getIncrementing(),
            'timestamps' => $instance->usesTimestamps(),
            'fillable' => $instance->getFillable(),
            'guarded' => $instance->getGuarded(),
            'hidden' => $instance->getHidden(),
            'visible' => $instance->getVisible(),
            'casts' => $instance->getCasts(),
            'dates' => $this->getDates($instance),
            'appends' => $instance->getAppends(),
            'relationships' => $this->analyzeRelationships($reflection, $instance),
            'scopes' => $this->analyzeScopes($reflection),
            'traits' => $this->analyzeTraits($reflection),
            'attributes' => $this->analyzeAttributes($instance),
            'validationRules' => $this->extractValidationRules($instance),
            'usesSoftDeletes' => $this->usesSoftDeletes($reflection),
            'perPage' => $instance->getPerPage(),
        ];
    }

    /**
     * Discover all models in the application.
     */
    public function discoverModels(array $directories = null): array
    {
        $directories = $directories ?? [app_path('Models')];
        $models = [];

        foreach ($directories as $directory) {
            if (!File::exists($directory)) {
                continue;
            }

            $files = File::allFiles($directory);

            foreach ($files as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $namespace = $this->extractNamespace($file->getPathname());
                $className = $file->getFilenameWithoutExtension();
                $fullClass = $namespace . '\\' . $className;

                if ($this->modelExists($fullClass)) {
                    try {
                        $models[] = $this->analyze($fullClass);
                    } catch (\Exception $e) {
                        // Skip models that can't be instantiated
                        continue;
                    }
                }
            }
        }

        return $models;
    }

    /**
     * Analyze model relationships.
     */
    protected function analyzeRelationships(ReflectionClass $reflection, Model $instance): array
    {
        $relationships = [];
        
        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            // Skip magic methods, getters, and non-relationship methods
            if ($method->getName() === '__construct' ||
                Str::startsWith($method->getName(), 'get') ||
                Str::startsWith($method->getName(), 'set') ||
                Str::startsWith($method->getName(), 'scope') ||
                $method->getNumberOfParameters() > 0 ||
                $method->isStatic() ||
                $method->getDeclaringClass()->getName() !== $reflection->getName()) {
                continue;
            }

            try {
                $returnType = $method->getReturnType();
                
                if ($returnType instanceof ReflectionNamedType || $returnType instanceof ReflectionUnionType) {
                    $types = $returnType instanceof ReflectionUnionType 
                        ? $returnType->getTypes() 
                        : [$returnType];
                    
                    foreach ($types as $type) {
                        if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                            $typeName = $type->getName();
                            
                            if (is_subclass_of($typeName, Relation::class)) {
                                $relationInstance = $method->invoke($instance);
                                
                                $relationships[] = [
                                    'name' => $method->getName(),
                                    'type' => class_basename($typeName),
                                    'related' => get_class($relationInstance->getRelated()),
                                    'foreignKey' => $this->extractForeignKey($relationInstance),
                                    'ownerKey' => $this->extractOwnerKey($relationInstance),
                                ];
                                
                                break;
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                // Skip methods that throw exceptions
                continue;
            }
        }

        return $relationships;
    }

    /**
     * Extract foreign key from relationship.
     */
    protected function extractForeignKey(Relation $relation): ?string
    {
        try {
            if (method_exists($relation, 'getForeignKeyName')) {
                return $relation->getForeignKeyName();
            }
            if (method_exists($relation, 'getForeignKey')) {
                return $relation->getForeignKey();
            }
        } catch (\Exception $e) {
            // Ignore
        }
        
        return null;
    }

    /**
     * Extract owner key from relationship.
     */
    protected function extractOwnerKey(Relation $relation): ?string
    {
        try {
            if (method_exists($relation, 'getOwnerKeyName')) {
                return $relation->getOwnerKeyName();
            }
            if (method_exists($relation, 'getOwnerKey')) {
                return $relation->getOwnerKey();
            }
        } catch (\Exception $e) {
            // Ignore
        }
        
        return null;
    }

    /**
     * Analyze model scopes.
     */
    protected function analyzeScopes(ReflectionClass $reflection): array
    {
        $scopes = [];
        
        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if (Str::startsWith($method->getName(), 'scope') && 
                strlen($method->getName()) > 5 &&
                $method->getDeclaringClass()->getName() === $reflection->getName()) {
                
                $scopeName = lcfirst(substr($method->getName(), 5));
                $parameters = [];
                
                foreach ($method->getParameters() as $param) {
                    if ($param->getName() === 'query') {
                        continue;
                    }
                    
                    $parameters[] = [
                        'name' => $param->getName(),
                        'type' => $param->getType() ? $param->getType()->getName() : 'mixed',
                        'optional' => $param->isOptional(),
                        'default' => $param->isDefaultValueAvailable() ? $param->getDefaultValue() : null,
                    ];
                }
                
                $scopes[] = [
                    'name' => $scopeName,
                    'method' => $method->getName(),
                    'parameters' => $parameters,
                ];
            }
        }
        
        return $scopes;
    }

    /**
     * Analyze model traits.
     */
    protected function analyzeTraits(ReflectionClass $reflection): array
    {
        $traits = [];
        
        foreach ($reflection->getTraits() as $trait) {
            $traits[] = $trait->getName();
        }
        
        return $traits;
    }

    /**
     * Analyze model attributes.
     */
    protected function analyzeAttributes(Model $instance): array
    {
        $attributes = [];
        
        try {
            // Get columns from the database schema
            $connection = $instance->getConnection();
            $table = $instance->getTable();
            
            $columns = $connection->getSchemaBuilder()->getColumnListing($table);
            
            foreach ($columns as $column) {
                $type = $connection->getDoctrineSchemaManager()
                    ->listTableDetails($table)
                    ->getColumn($column)
                    ->getType()
                    ->getName();
                
                $attributes[$column] = [
                    'type' => $type,
                    'cast' => $instance->getCasts()[$column] ?? null,
                ];
            }
        } catch (\Exception $e) {
            // If we can't get schema info, return empty array
        }
        
        return $attributes;
    }

    /**
     * Extract validation rules from model.
     */
    protected function extractValidationRules(Model $instance): array
    {
        // Check for common validation rule method names
        $methods = ['rules', 'validationRules', 'getValidationRules', 'getRules'];
        
        foreach ($methods as $method) {
            if (method_exists($instance, $method)) {
                try {
                    $rules = $instance->$method();
                    if (is_array($rules)) {
                        return $rules;
                    }
                } catch (\Exception $e) {
                    // Method might require parameters, skip
                }
            }
        }
        
        // Check for a static rules property
        if (property_exists($instance, 'rules')) {
            $reflection = new ReflectionClass($instance);
            $property = $reflection->getProperty('rules');
            $property->setAccessible(true);
            $rules = $property->getValue($instance);
            
            if (is_array($rules)) {
                return $rules;
            }
        }
        
        return [];
    }

    /**
     * Check if model uses soft deletes.
     */
    protected function usesSoftDeletes(ReflectionClass $reflection): bool
    {
        foreach ($reflection->getTraits() as $trait) {
            if ($trait->getName() === 'Illuminate\Database\Eloquent\SoftDeletes') {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Get date attributes.
     */
    protected function getDates(Model $instance): array
    {
        $dates = [];
        
        // Add default timestamp columns if used
        if ($instance->usesTimestamps()) {
            $dates[] = $instance->getCreatedAtColumn();
            $dates[] = $instance->getUpdatedAtColumn();
        }
        
        // Add deleted_at if using soft deletes
        if (method_exists($instance, 'getDeletedAtColumn')) {
            $dates[] = $instance->getDeletedAtColumn();
        }
        
        // Get dates from casts
        foreach ($instance->getCasts() as $attribute => $cast) {
            if (in_array($cast, ['date', 'datetime', 'timestamp'])) {
                $dates[] = $attribute;
            }
        }
        
        return array_unique($dates);
    }

    /**
     * Extract namespace from file.
     */
    protected function extractNamespace(string $path): string
    {
        $content = file_get_contents($path);
        
        if (preg_match('/namespace\s+([^;]+);/', $content, $matches)) {
            return trim($matches[1]);
        }
        
        return '';
    }
}