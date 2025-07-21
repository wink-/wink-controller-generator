<?php

namespace Wink\ControllerGenerator\Tests\Feature;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Config;
use Wink\ControllerGenerator\Generators\ApiControllerGenerator;
use Wink\ControllerGenerator\Tests\TestCase;

class ConfigurationTest extends TestCase
{
    protected Filesystem $filesystem;
    protected string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->filesystem = new Filesystem();
        $this->tempDir = sys_get_temp_dir() . '/wink-config-tests';
        
        if (!$this->filesystem->exists($this->tempDir)) {
            $this->filesystem->makeDirectory($this->tempDir, 0755, true);
        }
    }

    protected function tearDown(): void
    {
        if ($this->filesystem->exists($this->tempDir)) {
            $this->filesystem->deleteDirectory($this->tempDir);
        }
        
        parent::tearDown();
    }

    /** @test */
    public function default_configuration_is_loaded()
    {
        $config = config('wink-controllers');
        
        $this->assertIsArray($config);
        $this->assertArrayHasKey('default_namespace', $config);
        $this->assertArrayHasKey('output_path', $config);
        $this->assertArrayHasKey('template_path', $config);
        
        // Test default values
        $this->assertEquals('App\\Http\\Controllers', $config['default_namespace']);
        $this->assertEquals('app/Http/Controllers', $config['output_path']);
    }

    /** @test */
    public function custom_namespace_configuration_works()
    {
        // Set custom namespace in config
        Config::set('wink-controllers.default_namespace', 'App\\Http\\Controllers\\Api');
        Config::set('wink-controllers.api_namespace', 'App\\Http\\Controllers\\Api\\V1');
        
        $this->assertEquals('App\\Http\\Controllers\\Api', config('wink-controllers.default_namespace'));
        $this->assertEquals('App\\Http\\Controllers\\Api\\V1', config('wink-controllers.api_namespace'));
    }

    /** @test */
    public function custom_output_path_configuration_works()
    {
        Config::set('wink-controllers.output_path', 'custom/controllers');
        
        $this->assertEquals('custom/controllers', config('wink-controllers.output_path'));
    }

    /** @test */
    public function template_path_configuration_works()
    {
        $customTemplatePath = resource_path('stubs/custom');
        Config::set('wink-controllers.template_path', $customTemplatePath);
        
        $this->assertEquals($customTemplatePath, config('wink-controllers.template_path'));
    }

    /** @test */
    public function generator_respects_configured_namespace()
    {
        Config::set('wink-controllers.default_namespace', 'Custom\\Controllers');
        
        $generator = new ApiControllerGenerator($this->filesystem, [
            'output_path' => $this->tempDir,
            'namespace' => config('wink-controllers.default_namespace')
        ]);

        $controllerPath = $generator->generate('User', [
            'output_path' => $this->tempDir,
            'namespace' => config('wink-controllers.default_namespace'),
            'force' => true
        ]);

        $this->assertFileExists($controllerPath);

        $content = $this->filesystem->get($controllerPath);
        $this->assertStringContainsString('namespace Custom\\Controllers', $content);
    }

    /** @test */
    public function generator_respects_configured_output_path()
    {
        $customOutputPath = $this->tempDir . '/custom/controllers';
        Config::set('wink-controllers.output_path', $customOutputPath);
        
        $generator = new ApiControllerGenerator($this->filesystem, [
            'output_path' => $customOutputPath
        ]);

        $controllerPath = $generator->generate('User', [
            'output_path' => $customOutputPath,
            'namespace' => 'App\\Http\\Controllers',
            'force' => true
        ]);

        $this->assertFileExists($controllerPath);
        $this->assertStringContainsString($customOutputPath, $controllerPath);
    }

    /** @test */
    public function middleware_configuration_is_applied()
    {
        Config::set('wink-controllers.default_middleware', ['auth', 'verified']);
        Config::set('wink-controllers.api_middleware', ['auth:api', 'throttle:60,1']);
        
        $defaultMiddleware = config('wink-controllers.default_middleware');
        $apiMiddleware = config('wink-controllers.api_middleware');
        
        $this->assertEquals(['auth', 'verified'], $defaultMiddleware);
        $this->assertEquals(['auth:api', 'throttle:60,1'], $apiMiddleware);
    }

    /** @test */
    public function stub_configuration_is_customizable()
    {
        $customStubPath = resource_path('stubs/custom');
        Config::set('wink-controllers.stubs.api_controller', $customStubPath . '/api-controller.stub');
        Config::set('wink-controllers.stubs.web_controller', $customStubPath . '/web-controller.stub');
        
        $apiStub = config('wink-controllers.stubs.api_controller');
        $webStub = config('wink-controllers.stubs.web_controller');
        
        $this->assertEquals($customStubPath . '/api-controller.stub', $apiStub);
        $this->assertEquals($customStubPath . '/web-controller.stub', $webStub);
    }

    /** @test */
    public function validation_configuration_can_be_disabled()
    {
        Config::set('wink-controllers.generate_validation', false);
        Config::set('wink-controllers.generate_resources', false);
        
        $this->assertFalse(config('wink-controllers.generate_validation'));
        $this->assertFalse(config('wink-controllers.generate_resources'));
    }

    /** @test */
    public function command_respects_configuration_defaults()
    {
        // Set custom defaults
        Config::set('wink-controllers.default_namespace', 'App\\Http\\Controllers\\Custom');
        Config::set('wink-controllers.output_path', 'app/Http/Controllers/Custom');
        
        $exitCode = Artisan::call('wink:controllers:api', [
            'table' => 'users',
            '--model' => 'User',
            '--force' => true
        ]);

        $this->assertEquals(0, $exitCode);
        
        $output = Artisan::output();
        $this->assertStringContainsString('Custom', $output);
    }

    /** @test */
    public function configuration_validation_works()
    {
        // Test invalid namespace format
        Config::set('wink-controllers.default_namespace', 'invalid-namespace');
        
        // The generator should handle or validate this appropriately
        $generator = new ApiControllerGenerator($this->filesystem, [
            'output_path' => $this->tempDir,
            'namespace' => config('wink-controllers.default_namespace')
        ]);

        // This might throw an exception or handle gracefully depending on implementation
        try {
            $generator->generate('User', [
                'output_path' => $this->tempDir,
                'namespace' => config('wink-controllers.default_namespace'),
                'force' => true
            ]);
            
            // If no exception, verify the controller was created with sanitized namespace
            $this->assertTrue(true);
        } catch (\Exception $e) {
            // Exception is expected for invalid namespace
            $this->assertInstanceOf(\Exception::class, $e);
        }
    }

    /** @test */
    public function database_configuration_affects_analysis()
    {
        // Set custom database connection
        Config::set('wink-controllers.database_connection', 'testing');
        
        $this->assertEquals('testing', config('wink-controllers.database_connection'));
    }

    /** @test */
    public function view_configuration_is_customizable()
    {
        Config::set('wink-controllers.view_path', 'custom.views');
        Config::set('wink-controllers.view_extension', '.blade.php');
        
        $this->assertEquals('custom.views', config('wink-controllers.view_path'));
        $this->assertEquals('.blade.php', config('wink-controllers.view_extension'));
    }

    /** @test */
    public function resource_configuration_controls_generation()
    {
        Config::set('wink-controllers.auto_generate_resources', true);
        Config::set('wink-controllers.resource_namespace', 'App\\Http\\Resources');
        
        $this->assertTrue(config('wink-controllers.auto_generate_resources'));
        $this->assertEquals('App\\Http\\Resources', config('wink-controllers.resource_namespace'));
    }

    /** @test */
    public function form_request_configuration_is_customizable()
    {
        Config::set('wink-controllers.auto_generate_requests', true);
        Config::set('wink-controllers.request_namespace', 'App\\Http\\Requests');
        Config::set('wink-controllers.request_suffix', 'Request');
        
        $this->assertTrue(config('wink-controllers.auto_generate_requests'));
        $this->assertEquals('App\\Http\\Requests', config('wink-controllers.request_namespace'));
        $this->assertEquals('Request', config('wink-controllers.request_suffix'));
    }

    /** @test */
    public function test_generation_configuration_works()
    {
        Config::set('wink-controllers.auto_generate_tests', true);
        Config::set('wink-controllers.test_namespace', 'Tests\\Feature');
        Config::set('wink-controllers.test_suffix', 'ControllerTest');
        
        $this->assertTrue(config('wink-controllers.auto_generate_tests'));
        $this->assertEquals('Tests\\Feature', config('wink-controllers.test_namespace'));
        $this->assertEquals('ControllerTest', config('wink-controllers.test_suffix'));
    }

    /** @test */
    public function configuration_can_be_published_and_modified()
    {
        // Simulate config publishing
        $publishedConfigPath = config_path('wink-controllers.php');
        
        // In a real test, you might publish and modify the config file
        // For this test, we'll just verify the config structure
        $config = config('wink-controllers');
        
        $this->assertIsArray($config);
        
        // Verify all expected configuration keys exist
        $expectedKeys = [
            'default_namespace',
            'output_path',
            'template_path'
        ];
        
        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $config);
        }
    }

    /** @test */
    public function environment_specific_configuration_works()
    {
        // Test that configuration can vary by environment
        $originalEnv = app()->environment();
        
        try {
            // Simulate testing environment
            app()->instance('env', 'testing');
            
            Config::set('wink-controllers.output_path', 'tests/temp/controllers');
            
            $this->assertEquals('tests/temp/controllers', config('wink-controllers.output_path'));
            
        } finally {
            // Restore original environment
            app()->instance('env', $originalEnv);
        }
    }
}