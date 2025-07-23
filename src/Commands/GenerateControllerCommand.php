<?php

declare(strict_types=1);

namespace Wink\ControllerGenerator\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Wink\ControllerGenerator\Analyzers\ModelAnalyzer;
use Wink\ControllerGenerator\Generators\ApiControllerGenerator;
use Wink\ControllerGenerator\Generators\WebControllerGenerator;
use Wink\ControllerGenerator\Generators\ResourceControllerGenerator;

class GenerateControllerCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'wink:generate-controllers 
                            {model? : The model name to generate controllers for}
                            {--type=api : The type of controller to generate (api|web|resource)}
                            {--namespace= : Override default namespace}
                            {--middleware= : Custom middleware (comma separated)}
                            {--with-requests : Generate FormRequest classes}
                            {--with-resources : Generate API Resource classes}
                            {--with-tests : Generate controller tests}
                            {--no-relationships : Skip relationship handling}
                            {--force : Overwrite existing files}
                            {--dry-run : Preview without creating files}';

    /**
     * The console command description.
     */
    protected $description = 'Generate production-ready Laravel controllers from database schemas and existing models';

    protected Filesystem $filesystem;
    protected ModelAnalyzer $modelAnalyzer;
    protected array $config;

    public function __construct(
        Filesystem $filesystem,
        ModelAnalyzer $modelAnalyzer
    ) {
        parent::__construct();
        $this->filesystem = $filesystem;
        $this->modelAnalyzer = $modelAnalyzer;
        $this->config = config('wink-controllers', []);
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $model = $this->argument('model');
        $type = $this->option('type');

        // If no model specified, discover available models
        if (!$model) {
            return $this->handleDiscovery();
        }

        $this->info("Generating {$type} controller for model: {$model}");

        // Validate options
        if (!in_array($type, ['api', 'web', 'resource'])) {
            $this->error('Invalid controller type. Must be one of: api, web, resource');
            return self::FAILURE;
        }

        // Validate model exists
        $modelClass = $this->resolveModelClass($model);
        if (!$this->modelAnalyzer->modelExists($modelClass)) {
            if (!$this->confirm("Model {$modelClass} does not exist. Continue anyway?")) {
                return self::FAILURE;
            }
        }

        // Show configuration being used
        $this->displayConfiguration();

        // Prepare options
        $options = $this->prepareGenerationOptions();

        if ($this->option('dry-run')) {
            $this->warn('DRY RUN MODE: No files will be created');
            $this->previewGeneration($model, $type, $options);
            return self::SUCCESS;
        }

        // Generate the controller
        try {
            $generator = $this->createGenerator($type);
            $generatedPath = $generator->generate($model, $options);
            
            $this->info("✅ Successfully generated controller at: {$generatedPath}");
            
            return self::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("❌ Failed to generate controller: {$e->getMessage()}");
            if ($this->option('verbose')) {
                $this->line($e->getTraceAsString());
            }
            return self::FAILURE;
        }
    }

    /**
     * Handle model discovery when no model is specified.
     */
    protected function handleDiscovery(): int
    {
        $this->info('Discovering available models...');
        
        $models = $this->modelAnalyzer->discoverModels();
        
        if (empty($models)) {
            $this->warn('No Eloquent models found in app/Models directory.');
            return self::SUCCESS;
        }
        
        $modelNames = array_map(function ($model) {
            return class_basename($model['class']);
        }, $models);
        
        $this->info('Available models:');
        foreach ($modelNames as $name) {
            $this->line("  - {$name}");
        }
        
        if ($this->confirm('Would you like to generate controllers for all models?')) {
            $type = $this->choice('Select controller type:', ['api', 'web', 'resource'], 0);
            
            foreach ($modelNames as $modelName) {
                $this->call('wink:generate-controllers', [
                    'model' => $modelName,
                    '--type' => $type,
                    '--with-requests' => $this->option('with-requests'),
                    '--with-resources' => $this->option('with-resources'),
                    '--force' => $this->option('force'),
                ]);
            }
        }
        
        return self::SUCCESS;
    }

    /**
     * Create the appropriate generator based on type.
     */
    protected function createGenerator(string $type): ApiControllerGenerator|WebControllerGenerator|ResourceControllerGenerator
    {
        return match ($type) {
            'api' => new ApiControllerGenerator($this->filesystem, $this->modelAnalyzer, $this->config),
            'web' => new WebControllerGenerator($this->filesystem, $this->modelAnalyzer, $this->config),
            'resource' => new ResourceControllerGenerator($this->filesystem, $this->modelAnalyzer, $this->config),
            default => throw new \InvalidArgumentException("Invalid generator type: {$type}")
        };
    }

    /**
     * Prepare generation options from command input.
     */
    protected function prepareGenerationOptions(): array
    {
        $options = [];

        if ($namespace = $this->option('namespace')) {
            $options['namespace'] = $namespace;
        }

        if ($middleware = $this->option('middleware')) {
            $options['middleware'] = array_map('trim', explode(',', $middleware));
        }

        if ($this->option('with-requests')) {
            $options['use_form_requests'] = true;
        }

        if ($this->option('with-resources')) {
            $options['use_resources'] = true;
        }

        if ($this->option('with-tests')) {
            $options['generate_tests'] = true;
        }

        if ($this->option('no-relationships')) {
            $options['features']['relationships'] = false;
        }

        $options['force'] = $this->option('force');
        
        return $options;
    }

    /**
     * Display the configuration being used.
     */
    protected function displayConfiguration(): void
    {
        $config = $this->config;
        
        $this->info('Configuration:');
        $this->table(['Setting', 'Value'], [
            ['Default Namespace', $config['defaults']['namespace'] ?? 'App\\Http\\Controllers'],
            ['API Namespace', $config['defaults']['api_namespace'] ?? 'App\\Http\\Controllers\\Api'],
            ['Generate Form Requests', ($config['features']['generate_form_requests'] ?? true) ? 'Yes' : 'No'],
            ['Generate API Resources', ($config['features']['generate_api_resources'] ?? true) ? 'Yes' : 'No'],
            ['Include Authorization', ($config['features']['include_authorization'] ?? true) ? 'Yes' : 'No'],
            ['Template Path', $config['templates']['path'] ?? 'default'],
        ]);
    }

    /**
     * Resolve the fully qualified model class name.
     */
    protected function resolveModelClass(string $model): string
    {
        // Remove .php extension if present
        $model = str_replace('.php', '', $model);
        
        // If already contains namespace separator, assume it's a relative namespace under App\Models
        if (str_contains($model, '\\') || str_contains($model, '/')) {
            // Convert forward slashes to backslashes
            $model = str_replace('/', '\\', $model);
            // If it doesn't start with App\Models, prepend it
            if (!str_starts_with($model, 'App\\Models\\')) {
                return 'App\\Models\\' . $model;
            }
            return $model;
        }
        
        // Simple model name - use default behavior
        return "App\\Models\\" . Str::studly(Str::singular($model));
    }

    /**
     * Preview what would be generated.
     */
    protected function previewGeneration(string $model, string $type, array $options): void
    {
        $modelClass = $this->resolveModelClass($model);
        $modelBaseName = class_basename($modelClass);
        $controllerName = $modelBaseName . 'Controller';
        
        $namespace = match ($type) {
            'api' => $options['namespace'] ?? 'App\\Http\\Controllers\\Api',
            'web' => $options['namespace'] ?? 'App\\Http\\Controllers',
            'resource' => $options['namespace'] ?? 'App\\Http\\Controllers',
        };

        $this->info("Preview for {$modelBaseName} ({$type} controller):");
        $this->line("📁 Namespace: {$namespace}");
        $this->line("📄 Controller: {$controllerName}.php");

        if ($options['use_form_requests'] ?? true) {
            $this->line("📋 Form Requests:");
            $this->line("   - Store{$modelBaseName}Request.php");
            $this->line("   - Update{$modelBaseName}Request.php");
        }

        if (($options['use_resources'] ?? true) && $type === 'api') {
            $this->line("🔄 API Resource: {$modelBaseName}Resource.php");
        }

        if ($options['generate_tests'] ?? false) {
            $this->line("🧪 Test: {$controllerName}Test.php");
        }

        // Show model analysis if model exists
        if ($this->modelAnalyzer->modelExists($modelClass)) {
            try {
                $analysis = $this->modelAnalyzer->analyze($modelClass);
                $this->line("");
                $this->info("Model Analysis:");
                $this->line("📊 Table: {$analysis['table']}");
                $this->line("🔑 Primary Key: {$analysis['primaryKey']}");
                $this->line("⏰ Timestamps: " . ($analysis['timestamps'] ? 'Yes' : 'No'));
                $this->line("🗑️  Soft Deletes: " . ($analysis['usesSoftDeletes'] ? 'Yes' : 'No'));
                $this->line("📝 Fillable: " . count($analysis['fillable']) . " fields");
                $this->line("🔗 Relationships: " . count($analysis['relationships']));
            } catch (\Exception $e) {
                $this->warn("Could not analyze model: {$e->getMessage()}");
            }
        }
    }
}