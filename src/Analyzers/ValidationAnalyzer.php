<?php

namespace Wink\ControllerGenerator\Analyzers;

use ReflectionClass;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/**
 * Extracts database constraints for validation rules, analyzes existing FormRequest classes,
 * and generates appropriate validation rules for controller generation.
 */
class ValidationAnalyzer
{
    /**
     * Table name for database constraint analysis
     */
    private ?string $tableName;

    /**
     * Model class for model-based analysis
     */
    private ?string $modelClass;

    public function __construct(?string $tableName = null, ?string $modelClass = null)
    {
        $this->tableName = $tableName;
        $this->modelClass = $modelClass;
    }

    /**
     * Extract validation rules from database constraints
     */
    public function extractDatabaseValidationRules(): array
    {
        if (!$this->tableName || !Schema::hasTable($this->tableName)) {
            return [];
        }

        $rules = [];
        $columns = Schema::getColumnListing($this->tableName);

        foreach ($columns as $column) {
            $columnRules = $this->getColumnValidationRules($column);
            if (!empty($columnRules)) {
                $rules[$column] = $columnRules;
            }
        }

        return $rules;
    }

    /**
     * Analyze existing FormRequest classes to extract patterns
     */
    public function analyzeExistingFormRequests(array $formRequestClasses = []): array
    {
        $analysis = [
            'common_rules' => [],
            'rule_patterns' => [],
            'custom_rules' => [],
            'validation_messages' => [],
        ];

        if (empty($formRequestClasses)) {
            $formRequestClasses = $this->findFormRequestClasses();
        }

        foreach ($formRequestClasses as $className) {
            $formRequestAnalysis = $this->analyzeFormRequest($className);
            $this->mergeFormRequestAnalysis($analysis, $formRequestAnalysis);
        }

        return $analysis;
    }

    /**
     * Generate validation rules for CRUD operations
     */
    public function generateCrudValidationRules(array $fillableFields): array
    {
        return [
            'store' => $this->generateStoreValidationRules($fillableFields),
            'update' => $this->generateUpdateValidationRules($fillableFields),
        ];
    }

    /**
     * Generate store validation rules
     */
    public function generateStoreValidationRules(array $fillableFields): array
    {
        $rules = [];
        $databaseRules = $this->extractDatabaseValidationRules();

        foreach ($fillableFields as $field) {
            $fieldRules = [];

            // Add database-based rules
            if (isset($databaseRules[$field])) {
                $fieldRules = array_merge($fieldRules, $databaseRules[$field]);
            }

            // Add field-specific rules based on naming conventions
            $fieldRules = array_merge($fieldRules, $this->getConventionBasedRules($field));

            if (!empty($fieldRules)) {
                $rules[$field] = array_unique($fieldRules);
            }
        }

        return $rules;
    }

    /**
     * Generate update validation rules (similar to store but with sometimes required)
     */
    public function generateUpdateValidationRules(array $fillableFields): array
    {
        $storeRules = $this->generateStoreValidationRules($fillableFields);
        $updateRules = [];

        foreach ($storeRules as $field => $rules) {
            $updatedRules = [];

            foreach ($rules as $rule) {
                // Convert required to sometimes for updates
                if ($rule === 'required') {
                    $updatedRules[] = 'sometimes';
                    $updatedRules[] = 'required';
                } else {
                    $updatedRules[] = $rule;
                }
            }

            $updateRules[$field] = array_unique($updatedRules);
        }

        return $updateRules;
    }

    /**
     * Suggest validation rules based on field name and type patterns
     */
    public function suggestValidationRules(string $fieldName, ?string $fieldType = null): array
    {
        $rules = [];

        // Email fields
        if (Str::contains($fieldName, 'email')) {
            $rules[] = 'email';
        }

        // Password fields
        if (Str::contains($fieldName, 'password')) {
            $rules[] = 'min:8';
        }

        // URL fields
        if (Str::contains($fieldName, ['url', 'website', 'link'])) {
            $rules[] = 'url';
        }

        // Phone fields
        if (Str::contains($fieldName, ['phone', 'mobile', 'tel'])) {
            $rules[] = 'regex:/^[\+]?[1-9][\d]{0,15}$/';
        }

        // Date fields
        if (Str::contains($fieldName, ['date', 'birth', 'created', 'updated'])) {
            $rules[] = 'date';
        }

        // Numeric fields
        if (Str::contains($fieldName, ['price', 'amount', 'cost', 'fee', 'total', 'count', 'number'])) {
            $rules[] = 'numeric';
        }

        // Boolean fields
        if (Str::contains($fieldName, ['is_', 'has_', 'can_', 'active', 'enabled', 'published'])) {
            $rules[] = 'boolean';
        }

        // Add type-based rules
        if ($fieldType) {
            $rules = array_merge($rules, $this->getTypeBasedRules($fieldType));
        }

        return array_unique($rules);
    }

    /**
     * Generate validation messages based on rules
     */
    public function generateValidationMessages(array $rules): array
    {
        $messages = [];

        foreach ($rules as $field => $fieldRules) {
            foreach ($fieldRules as $rule) {
                $ruleKey = is_string($rule) ? $rule : (is_array($rule) ? key($rule) : $rule);
                $messageKey = "{$field}.{$ruleKey}";
                
                $messages[$messageKey] = $this->getDefaultValidationMessage($field, $ruleKey);
            }
        }

        return $messages;
    }

    /**
     * Get comprehensive validation analysis
     */
    public function analyze(array $fillableFields = []): array
    {
        return [
            'database_rules' => $this->extractDatabaseValidationRules(),
            'crud_rules' => $this->generateCrudValidationRules($fillableFields),
            'existing_patterns' => $this->analyzeExistingFormRequests(),
            'suggested_messages' => $this->generateValidationMessages(
                $this->generateStoreValidationRules($fillableFields)
            ),
        ];
    }

    /**
     * Get validation rules for a specific database column
     */
    private function getColumnValidationRules(string $column): array
    {
        if (!$this->tableName || !Schema::hasTable($this->tableName)) {
            return [];
        }

        $rules = [];
        $columnType = Schema::getColumnType($this->tableName, $column);

        // Check if column is nullable
        if (!$this->isColumnNullable($column)) {
            $rules[] = 'required';
        } else {
            $rules[] = 'nullable';
        }

        // Add type-based rules
        $rules = array_merge($rules, $this->getTypeBasedRules($columnType));

        // Add length constraints
        $maxLength = $this->getColumnMaxLength($column);
        if ($maxLength) {
            $rules[] = "max:{$maxLength}";
        }

        return $rules;
    }

    /**
     * Get validation rules based on data type
     */
    private function getTypeBasedRules(string $type): array
    {
        return match (strtolower($type)) {
            'integer', 'bigint', 'smallint', 'tinyint' => ['integer'],
            'decimal', 'float', 'double', 'real' => ['numeric'],
            'boolean' => ['boolean'],
            'date' => ['date'],
            'datetime', 'timestamp' => ['date'],
            'time' => ['date_format:H:i:s'],
            'json', 'jsonb' => ['json'],
            'uuid' => ['uuid'],
            'email' => ['email'],
            'url' => ['url'],
            default => ['string'],
        };
    }

    /**
     * Get validation rules based on field naming conventions
     */
    private function getConventionBasedRules(string $fieldName): array
    {
        return $this->suggestValidationRules($fieldName);
    }

    /**
     * Check if a database column is nullable
     */
    private function isColumnNullable(string $column): bool
    {
        // This would need actual database introspection
        // For now, return a default value
        return false;
    }

    /**
     * Get maximum length for a column
     */
    private function getColumnMaxLength(string $column): ?int
    {
        // This would need actual database introspection
        // For now, return common defaults
        return null;
    }

    /**
     * Find FormRequest classes in the application
     */
    private function findFormRequestClasses(): array
    {
        // This would need file system scanning to find FormRequest classes
        // For now, return empty array
        return [];
    }

    /**
     * Analyze a specific FormRequest class
     */
    private function analyzeFormRequest(string $className): array
    {
        if (!class_exists($className) || !is_subclass_of($className, FormRequest::class)) {
            return [];
        }

        $reflection = new ReflectionClass($className);
        $instance = new $className;

        return [
            'rules' => method_exists($instance, 'rules') ? $instance->rules() : [],
            'messages' => method_exists($instance, 'messages') ? $instance->messages() : [],
            'attributes' => method_exists($instance, 'attributes') ? $instance->attributes() : [],
        ];
    }

    /**
     * Merge FormRequest analysis into main analysis
     */
    private function mergeFormRequestAnalysis(array &$analysis, array $formRequestAnalysis): void
    {
        if (isset($formRequestAnalysis['rules'])) {
            foreach ($formRequestAnalysis['rules'] as $field => $rules) {
                if (!isset($analysis['common_rules'][$field])) {
                    $analysis['common_rules'][$field] = [];
                }
                $analysis['common_rules'][$field] = array_merge(
                    $analysis['common_rules'][$field],
                    is_array($rules) ? $rules : [$rules]
                );
            }
        }

        if (isset($formRequestAnalysis['messages'])) {
            $analysis['validation_messages'] = array_merge(
                $analysis['validation_messages'],
                $formRequestAnalysis['messages']
            );
        }
    }

    /**
     * Get default validation message for a field and rule
     */
    private function getDefaultValidationMessage(string $field, string $rule): string
    {
        $fieldName = Str::title(str_replace(['_', '-'], ' ', $field));

        return match ($rule) {
            'required' => "The {$fieldName} field is required.",
            'email' => "The {$fieldName} must be a valid email address.",
            'url' => "The {$fieldName} must be a valid URL.",
            'numeric' => "The {$fieldName} must be a number.",
            'integer' => "The {$fieldName} must be an integer.",
            'boolean' => "The {$fieldName} field must be true or false.",
            'date' => "The {$fieldName} is not a valid date.",
            'json' => "The {$fieldName} must be a valid JSON string.",
            'uuid' => "The {$fieldName} must be a valid UUID.",
            default => "The {$fieldName} field is invalid.",
        };
    }
}