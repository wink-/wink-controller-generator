<?php

declare(strict_types=1);

namespace Wink\ControllerGenerator\Tests\Feature;

use Illuminate\Support\Facades\File;
use Wink\ControllerGenerator\Tests\TestCase;

class GenerateApiControllerTest extends TestCase
{
    /** @test */
    public function it_can_generate_api_controller_with_dry_run()
    {
        $this->artisan('wink:generate-controllers', [
            'model' => 'User',
            '--type' => 'api',
            '--dry-run' => true,
        ])
        ->expectsOutput('DRY RUN MODE: No files will be created')
        ->assertExitCode(0);
    }

    /** @test */
    public function it_validates_controller_type()
    {
        $this->artisan('wink:generate-controllers', [
            'model' => 'User',
            '--type' => 'invalid',
        ])
        ->expectsOutput('Invalid controller type. Must be one of: api, web, resource')
        ->assertExitCode(1);
    }

    /** @test */
    public function it_discovers_models_when_no_model_specified()
    {
        $this->artisan('wink:generate-controllers')
        ->expectsOutput('Discovering available models...')
        ->expectsOutput('No Eloquent models found in app/Models directory.')
        ->assertExitCode(0);
    }

    /** @test */
    public function it_shows_configuration_table()
    {
        $this->artisan('wink:generate-controllers', [
            'model' => 'User',
            '--dry-run' => true,
        ])
        ->expectsTable(['Setting', 'Value'], [
            ['Default Namespace', 'App\\Http\\Controllers'],
            ['API Namespace', 'App\\Http\\Controllers\\Api'],
            ['Generate Form Requests', 'Yes'],
            ['Generate API Resources', 'Yes'],
            ['Include Authorization', 'Yes'],
            ['Template Path', 'default'],
        ])
        ->assertExitCode(0);
    }

    /** @test */
    public function it_prompts_to_continue_when_model_does_not_exist()
    {
        $this->artisan('wink:generate-controllers', [
            'model' => 'NonExistentModel',
            '--dry-run' => true,
        ])
        ->expectsQuestion('Model App\\Models\\NonExistentModel does not exist. Continue anyway?', false)
        ->assertExitCode(1);
    }

    /** @test */
    public function it_handles_missing_stub_files_gracefully()
    {
        // This test ensures the package handles missing template files appropriately
        $tempDir = sys_get_temp_dir() . '/wink-test-' . uniqid();
        mkdir($tempDir);

        try {
            // Configure to use non-existent stub directory
            config(['wink-controllers.templates.path' => $tempDir . '/non-existent']);

            $this->artisan('wink:generate-controllers', [
                'model' => 'User',
                '--type' => 'api',
            ])
            ->assertExitCode(1);

        } finally {
            File::deleteDirectory($tempDir);
        }
    }

    /** @test */
    public function it_validates_middleware_option_format()
    {
        $this->artisan('wink:generate-controllers', [
            'model' => 'User',
            '--type' => 'api',
            '--middleware' => 'auth:api,throttle:60,1',
            '--dry-run' => true,
        ])
        ->assertExitCode(0);
    }

    /** @test */
    public function it_supports_namespace_override()
    {
        $this->artisan('wink:generate-controllers', [
            'model' => 'User',
            '--type' => 'api',
            '--namespace' => 'App\\Http\\Controllers\\Admin\\Api',
            '--dry-run' => true,
        ])
        ->assertExitCode(0);
    }

    /** @test */
    public function it_handles_force_option()
    {
        $this->artisan('wink:generate-controllers', [
            'model' => 'User',
            '--type' => 'api',
            '--force' => true,
            '--dry-run' => true,
        ])
        ->assertExitCode(0);
    }

    /** @test */
    public function it_handles_with_requests_option()
    {
        $this->artisan('wink:generate-controllers', [
            'model' => 'User',
            '--type' => 'api',
            '--with-requests' => true,
            '--dry-run' => true,
        ])
        ->expectsOutput('📋 Form Requests:')
        ->assertExitCode(0);
    }

    /** @test */
    public function it_handles_with_resources_option()
    {
        $this->artisan('wink:generate-controllers', [
            'model' => 'User',
            '--type' => 'api',
            '--with-resources' => true,
            '--dry-run' => true,
        ])
        ->expectsOutput('🔄 API Resource: UserResource.php')
        ->assertExitCode(0);
    }
}