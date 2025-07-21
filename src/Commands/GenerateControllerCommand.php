<?php

namespace Wink\ControllerGenerator\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenerateControllerCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'wink:generate-controllers 
                            {table? : The table name to generate controllers for}
                            {--type=api : The type of controller to generate (api|web|resource)}
                            {--model= : Specify the model class}
                            {--namespace= : Override default namespace}
                            {--middleware= : Custom middleware}
                            {--with-requests : Generate FormRequest classes}
                            {--with-resources : Generate API Resource classes}
                            {--with-tests : Generate controller tests}
                            {--force : Overwrite existing files}
                            {--dry-run : Preview without creating files}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate production-ready Laravel controllers from database schemas and existing models';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $table = $this->argument('table');
        $type = $this->option('type');

        $this->info("Generating {$type} controller" . ($table ? " for table: {$table}" : 's'));

        // Validate options
        if (!in_array($type, ['api', 'web', 'resource'])) {
            $this->error('Invalid controller type. Must be one of: api, web, resource');
            return self::FAILURE;
        }

        // Show configuration being used
        $this->displayConfiguration();

        if ($this->option('dry-run')) {
            $this->warn('DRY RUN MODE: No files will be created');
            $this->previewGeneration($table, $type);
            return self::SUCCESS;
        }

        // TODO: Implement actual generation logic
        $this->error('Command implementation is not complete yet.');
        $this->info('This is a skeleton command showing the structure.');

        return self::SUCCESS;
    }

    /**
     * Display the configuration being used.
     */
    protected function displayConfiguration(): void
    {
        $config = config('wink-controllers');
        
        $this->info('Configuration:');
        $this->table(['Setting', 'Value'], [
            ['Default Namespace', $config['defaults']['namespace']],
            ['API Namespace', $config['defaults']['api_namespace']],
            ['Generate Form Requests', $config['features']['generate_form_requests'] ? 'Yes' : 'No'],
            ['Generate API Resources', $config['features']['generate_api_resources'] ? 'Yes' : 'No'],
            ['Include Authorization', $config['features']['include_authorization'] ? 'Yes' : 'No'],
            ['Template Path', $config['templates']['path']],
        ]);
    }

    /**
     * Preview what would be generated.
     */
    protected function previewGeneration(?string $table, string $type): void
    {
        $modelName = $table ? Str::studly(Str::singular($table)) : 'Example';
        $controllerName = $modelName . 'Controller';

        $this->info("Would generate:");
        $this->line("- Controller: {$controllerName}");

        if (config('wink-controllers.features.generate_form_requests')) {
            $this->line("- Form Requests: Store{$modelName}Request, Update{$modelName}Request");
        }

        if (config('wink-controllers.features.generate_api_resources') && $type !== 'web') {
            $this->line("- API Resource: {$modelName}Resource");
        }

        if (config('wink-controllers.features.generate_tests')) {
            $this->line("- Test: {$controllerName}Test");
        }
    }
}