<?php

declare(strict_types=1);

namespace Wink\ControllerGenerator\Generators;

use Illuminate\Support\Str;
use Wink\ControllerGenerator\Analyzers\ModelAnalyzer;

class ApiControllerGenerator extends AbstractControllerGenerator
{
    protected ModelAnalyzer $modelAnalyzer;
    protected array $modelInfo = [];
    
    public function __construct(
        \Illuminate\Filesystem\Filesystem $filesystem,
        ModelAnalyzer $modelAnalyzer,
        array $config = []
    ) {
        parent::__construct($filesystem, $config);
        $this->modelAnalyzer = $modelAnalyzer;
    }
    
    /**
     * Generate the API controller file.
     */
    public function generate(string $model, array $options = []): string
    {
        $this->validateModel($model);
        $this->setModel($model);
        
        // Merge options with config (recursive to preserve nested arrays)
        $this->config = array_merge_recursive($this->config, $options);
        
        // Analyze the model if it exists
        if ($this->modelExists($model)) {
            $modelClass = $this->getFullModelClass();
            $this->modelInfo = $this->modelAnalyzer->analyze($modelClass);
        }
        
        // Load and process the template
        $content = $this->loadTemplate($this->getTemplateName());
        
        // Generate the controller file
        $path = $this->getControllerPath();
        $this->writeFile($path, $content);
        
        // Generate related files
        $this->generateRelatedFiles();
        
        return $path;
    }
    
    /**
     * Get the default configuration for API controllers.
     */
    protected function getDefaultConfig(): array
    {
        return [
            'namespace' => 'App\\Http\\Controllers\\Api',
            'base_controller' => 'App\\Http\\Controllers\\Controller',
            'use_resources' => true,
            'use_form_requests' => true,
            'pagination' => [
                'enabled' => true,
                'per_page' => 15,
                'max_per_page' => 100,
            ],
            'response_format' => 'json',
            'middleware' => ['api', 'auth:sanctum'],
            'traits' => [],
            'features' => [
                'filtering' => true,
                'sorting' => true,
                'searching' => true,
                'pagination' => true,
                'validation' => true,
                'authorization' => true,
                'rate_limiting' => true,
                'soft_deletes' => 'auto', // auto, true, false
                'bulk_operations' => false,
                'api_versioning' => false,
            ],
        ];
    }
    
    /**
     * Get the template stub name.
     */
    protected function getTemplateName(): string
    {
        return 'api-controller.stub';
    }
    
    /**
     * Get API-specific template variables.
     */
    protected function getControllerSpecificVars(): array
    {
        return [
            'baseController' => $this->getBaseController(),
            'modelNamespace' => $this->getFullModelClass(),
            'modelLower' => strtolower($this->getModelName()),
            'modelLowerPlural' => strtolower($this->getPluralName()),
            'primaryKey' => $this->getPrimaryKey(),
            'resourceClass' => $this->getResourceClass(),
            'resourceCollection' => $this->getResourceCollectionClass(),
            'storeRequest' => $this->getStoreRequestClass(),
            'updateRequest' => $this->getUpdateRequestClass(),
            'usesResources' => $this->config['use_resources'] ? 'true' : 'false',
            'usesFormRequests' => $this->config['use_form_requests'] ? 'true' : 'false',
            'pagination' => $this->config['pagination'] ?? [
                'enabled' => true,
                'per_page' => 15,
                'max_per_page' => 100
            ],
            'paginationEnabled' => ($this->config['pagination']['enabled'] ?? true) ? 'true' : 'false',
            'perPage' => $this->config['pagination']['per_page'] ?? 15,
            'maxPerPage' => $this->config['pagination']['max_per_page'] ?? 100,
            'methods' => $this->generateMethods(),
            'usesSoftDeletes' => $this->shouldUseSoftDeletes() ? 'true' : 'false',
            'searchableFields' => $this->getSearchableFields(),
            'filterableFields' => $this->getFilterableFields(),
            'sortableFields' => $this->getSortableFields(),
            'relationships' => $this->getEagerLoadRelationships(),
            'validationRules' => $this->getValidationRules(),
            
            // Authorization template variables
            'authorizationMiddleware' => $this->getConfigValue('features.authorization', true) 
                ? '$this->authorizeResource(' . $this->getModelName() . '::class, \'' . strtolower($this->getModelName()) . '\');' 
                : '',
            'indexAuthorization' => $this->getConfigValue('features.authorization', true)
                ? '$this->authorize(\'viewAny\', ' . $this->getModelName() . '::class);'
                : '',
            'showAuthorization' => $this->getConfigValue('features.authorization', true)
                ? '$this->authorize(\'view\', $' . strtolower($this->getModelName()) . ');'
                : '',
            'destroyAuthorization' => $this->getConfigValue('features.authorization', true)
                ? '$this->authorize(\'delete\', $' . strtolower($this->getModelName()) . ');'
                : '',
                
            // Search and filtering
            'sortableFieldsList' => implode(',', $this->getSortableFields()),
            'defaultSortField' => $this->getPrimaryKey(),
            'searchFields' => '$query->search($searchTerm);',
            'indexFilters' => '// Add API-specific filters here',
            'indexValidationRules' => '',
            'eagerLoading' => '// $query->with([]);',
            'filterParameters' => '',
            
            // Data processing placeholders
            'storeDataProcessing' => '$data = $request->validated();',
            'updateDataProcessing' => '$data = $request->validated();',
            'postStoreActions' => '// Additional actions after storing',
            'postUpdateActions' => '// Additional actions after updating',
            'preDeleteActions' => '// Actions before deletion',
            'postDeleteActions' => '// Actions after deletion',
            'relationshipChecks' => '// Check for dependent relationships',
            'softDeleteCheck' => '',
            'additionalMethods' => '// Add any additional API methods here',
            
            // Relationships
            'showRelationships' => '',
            'storeRelationships' => '',
            'updateRelationships' => '',
            
            // Primary key type for OpenAPI
            'primaryKeyType' => 'integer',
        ];
    }
    
    /**
     * Get the base controller class.
     */
    protected function getBaseController(): string
    {
        return class_basename($this->config['base_controller']);
    }
    
    /**
     * Get the resource class name.
     */
    protected function getResourceClass(): string
    {
        return $this->getModelName() . 'Resource';
    }
    
    /**
     * Get the resource collection class name.
     */
    protected function getResourceCollectionClass(): string
    {
        return $this->getModelName() . 'Collection';
    }
    
    /**
     * Get the store request class name.
     */
    protected function getStoreRequestClass(): string
    {
        return 'Store' . $this->getModelName() . 'Request';
    }
    
    /**
     * Get the update request class name.
     */
    protected function getUpdateRequestClass(): string
    {
        return 'Update' . $this->getModelName() . 'Request';
    }
    
    /**
     * Generate controller methods based on configuration.
     */
    protected function generateMethods(): array
    {
        $methods = ['index', 'store', 'show', 'update', 'destroy'];
        
        if ($this->config['features']['bulk_operations'] ?? false) {
            $methods[] = 'bulkUpdate';
            $methods[] = 'bulkDestroy';
        }
        
        if ($this->shouldUseSoftDeletes()) {
            $methods[] = 'restore';
            $methods[] = 'forceDestroy';
        }
        
        return $methods;
    }
    
    /**
     * Determine if soft deletes should be used.
     */
    protected function shouldUseSoftDeletes(): bool
    {
        $softDeletes = $this->config['features']['soft_deletes'] ?? 'auto';
        
        if ($softDeletes === 'auto') {
            return $this->modelInfo['usesSoftDeletes'] ?? false;
        }
        
        return (bool) $softDeletes;
    }
    
    /**
     * Get searchable fields from model info.
     */
    protected function getSearchableFields(): array
    {
        $fields = [];
        
        // Common searchable fields
        $searchableTypes = ['string', 'text'];
        $commonSearchFields = ['name', 'title', 'email', 'description'];
        
        foreach ($this->modelInfo['attributes'] ?? [] as $field => $info) {
            if (in_array($info['type'], $searchableTypes) && 
                (in_array($field, $commonSearchFields) || 
                 in_array($field, $this->modelInfo['fillable'] ?? []))) {
                $fields[] = $field;
            }
        }
        
        return array_unique($fields);
    }
    
    /**
     * Get filterable fields from model info.
     */
    protected function getFilterableFields(): array
    {
        $fields = [];
        
        // Include foreign keys and enum/boolean fields
        $filterableTypes = ['boolean', 'integer', 'bigint', 'smallint'];
        
        foreach ($this->modelInfo['attributes'] ?? [] as $field => $info) {
            if (in_array($info['type'], $filterableTypes) || 
                Str::endsWith($field, '_id') ||
                in_array($info['cast'], ['boolean', 'integer', 'array'])) {
                $fields[] = $field;
            }
        }
        
        return array_unique($fields);
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
     * Get sortable fields from model info.
     */
    protected function getSortableFields(): array
    {
        $fields = [$this->getPrimaryKey(), 'created_at', 'updated_at'];
        
        // Add numeric and date fields
        $sortableTypes = ['integer', 'bigint', 'decimal', 'float', 'date', 'datetime', 'timestamp'];
        
        foreach ($this->modelInfo['attributes'] ?? [] as $field => $info) {
            if (in_array($info['type'], $sortableTypes)) {
                $fields[] = $field;
            }
        }
        
        return array_unique($fields);
    }
    
    /**
     * Get relationships for eager loading.
     */
    protected function getEagerLoadRelationships(): array
    {
        $relationships = [];
        
        foreach ($this->modelInfo['relationships'] ?? [] as $relation) {
            // Only eager load belongsTo and hasOne by default
            if (in_array($relation['type'], ['BelongsTo', 'HasOne'])) {
                $relationships[] = $relation['name'];
            }
        }
        
        return $relationships;
    }
    
    /**
     * Get validation rules from model or generate them.
     */
    protected function getValidationRules(): array
    {
        // First check if model has validation rules
        if (!empty($this->modelInfo['validationRules'])) {
            return $this->modelInfo['validationRules'];
        }
        
        // Generate rules based on database schema
        $rules = [];
        
        foreach ($this->modelInfo['attributes'] ?? [] as $field => $info) {
            if (in_array($field, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                continue;
            }
            
            if (!in_array($field, $this->modelInfo['fillable'] ?? [])) {
                continue;
            }
            
            $fieldRules = [];
            
            // Add type-based rules
            switch ($info['type']) {
                case 'string':
                case 'text':
                    $fieldRules[] = 'string';
                    if ($info['type'] === 'string') {
                        $fieldRules[] = 'max:255';
                    }
                    break;
                case 'integer':
                case 'bigint':
                case 'smallint':
                    $fieldRules[] = 'integer';
                    break;
                case 'decimal':
                case 'float':
                    $fieldRules[] = 'numeric';
                    break;
                case 'boolean':
                    $fieldRules[] = 'boolean';
                    break;
                case 'date':
                    $fieldRules[] = 'date';
                    break;
                case 'datetime':
                case 'timestamp':
                    $fieldRules[] = 'date_format:Y-m-d H:i:s';
                    break;
                case 'json':
                    $fieldRules[] = 'array';
                    break;
            }
            
            // Add special field rules
            if ($field === 'email') {
                $fieldRules[] = 'email';
            }
            
            if (Str::endsWith($field, '_id')) {
                $table = Str::plural(Str::beforeLast($field, '_id'));
                $fieldRules[] = "exists:{$table},id";
            }
            
            if (!empty($fieldRules)) {
                $rules[$field] = $fieldRules;
            }
        }
        
        return $rules;
    }
    
    /**
     * Get additional imports based on features.
     */
    protected function getImports(): array
    {
        $imports = parent::getImports();
        
        if ($this->config['use_resources']) {
            $imports[] = "App\\Http\\Resources\\{$this->getResourceClass()}";
        }
        
        if ($this->config['use_form_requests']) {
            $imports[] = "App\\Http\\Requests\\{$this->getStoreRequestClass()}";
            $imports[] = "App\\Http\\Requests\\{$this->getUpdateRequestClass()}";
        }
        
        $imports[] = 'Illuminate\\Http\\JsonResponse';
        $imports[] = 'Illuminate\\Database\\Eloquent\\Builder';
        
        if ($this->config['features']['pagination'] ?? true) {
            $imports[] = 'Illuminate\\Pagination\\LengthAwarePaginator';
        }
        
        return array_unique($imports);
    }
    
    /**
     * Generate related files (Resource, FormRequests).
     */
    protected function generateRelatedFiles(): void
    {
        if ($this->config['use_resources']) {
            $this->generateResourceFile();
        }
        
        if ($this->config['use_form_requests']) {
            $this->generateFormRequestFiles();
        }
    }
    
    /**
     * Generate the API Resource file.
     */
    protected function generateResourceFile(): void
    {
        $resourcePath = app_path("Http/Resources/{$this->getResourceClass()}.php");
        
        if ($this->filesystem->exists($resourcePath)) {
            return; // Don't overwrite existing resources
        }
        
        $content = $this->generateResourceContent();
        $this->writeFile($resourcePath, $content);
    }
    
    /**
     * Generate resource file content.
     */
    protected function generateResourceContent(): string
    {
        $namespace = 'App\\Http\\Resources';
        $className = $this->getResourceClass();
        
        $fields = $this->getResourceFields();
        $fieldsString = $this->formatResourceFields($fields);
        
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace};

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class {$className} extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request \$request): array
    {
        return [
{$fieldsString}
        ];
    }
}
PHP;
    }
    
    /**
     * Get fields for the resource.
     */
    protected function getResourceFields(): array
    {
        $primaryKey = $this->getPrimaryKey();
        $fields = [$primaryKey => "\$this->{$primaryKey}"];
        
        // Add fillable fields
        foreach ($this->modelInfo['fillable'] ?? [] as $field) {
            $fields[$field] = "\$this->{$field}";
        }
        
        // Add timestamps
        if ($this->modelInfo['timestamps'] ?? true) {
            $fields['created_at'] = '$this->created_at?->toISOString()';
            $fields['updated_at'] = '$this->updated_at?->toISOString()';
        }
        
        // Add relationships
        foreach ($this->modelInfo['relationships'] ?? [] as $relation) {
            if (in_array($relation['type'], ['BelongsTo', 'HasOne'])) {
                $resourceClass = Str::studly(Str::singular($relation['name'])) . 'Resource';
                $fields[$relation['name']] = "{$resourceClass}::make(\$this->whenLoaded('{$relation['name']}'))";
            } elseif (in_array($relation['type'], ['HasMany', 'BelongsToMany'])) {
                $resourceClass = Str::studly(Str::singular($relation['name'])) . 'Resource';
                $fields[$relation['name']] = "{$resourceClass}::collection(\$this->whenLoaded('{$relation['name']}'))";
            }
        }
        
        return $fields;
    }
    
    /**
     * Format resource fields for output.
     */
    protected function formatResourceFields(array $fields): string
    {
        $lines = [];
        foreach ($fields as $key => $value) {
            $lines[] = "            '{$key}' => {$value},";
        }
        return implode("\n", $lines);
    }
    
    /**
     * Generate form request files.
     */
    protected function generateFormRequestFiles(): void
    {
        $this->generateStoreRequest();
        $this->generateUpdateRequest();
    }
    
    /**
     * Generate store request file.
     */
    protected function generateStoreRequest(): void
    {
        $requestPath = app_path("Http/Requests/{$this->getStoreRequestClass()}.php");
        
        if ($this->filesystem->exists($requestPath)) {
            return;
        }
        
        $rules = $this->getValidationRules();
        $storeRules = $this->addRequiredRules($rules);
        
        $content = $this->generateFormRequestContent(
            $this->getStoreRequestClass(),
            $storeRules,
            'POST'
        );
        
        $this->writeFile($requestPath, $content);
    }
    
    /**
     * Generate update request file.
     */
    protected function generateUpdateRequest(): void
    {
        $requestPath = app_path("Http/Requests/{$this->getUpdateRequestClass()}.php");
        
        if ($this->filesystem->exists($requestPath)) {
            return;
        }
        
        $rules = $this->getValidationRules();
        $updateRules = $this->addSometimesRules($rules);
        
        $content = $this->generateFormRequestContent(
            $this->getUpdateRequestClass(),
            $updateRules,
            'PUT'
        );
        
        $this->writeFile($requestPath, $content);
    }
    
    /**
     * Add required rules for store requests.
     */
    protected function addRequiredRules(array $rules): array
    {
        foreach ($rules as $field => &$fieldRules) {
            array_unshift($fieldRules, 'required');
        }
        return $rules;
    }
    
    /**
     * Add sometimes rules for update requests.
     */
    protected function addSometimesRules(array $rules): array
    {
        foreach ($rules as $field => &$fieldRules) {
            array_unshift($fieldRules, 'sometimes');
        }
        return $rules;
    }
    
    /**
     * Generate form request content.
     */
    protected function generateFormRequestContent(string $className, array $rules, string $method): string
    {
        $namespace = 'App\\Http\\Requests';
        $rulesString = $this->formatValidationRules($rules);
        
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace};

use Illuminate\Foundation\Http\FormRequest;

class {$className} extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
{$rulesString}
        ];
    }
}
PHP;
    }
    
    /**
     * Format validation rules for output.
     */
    protected function formatValidationRules(array $rules): string
    {
        $lines = [];
        foreach ($rules as $field => $fieldRules) {
            $rulesString = "'" . implode('|', $fieldRules) . "'";
            $lines[] = "            '{$field}' => {$rulesString},";
        }
        return implode("\n", $lines);
    }
}