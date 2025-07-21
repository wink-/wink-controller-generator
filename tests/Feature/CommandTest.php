<?php

namespace Wink\ControllerGenerator\Tests\Feature;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Wink\ControllerGenerator\Tests\TestCase;

class CommandTest extends TestCase
{
    protected string $testControllerPath;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->testControllerPath = app_path('Http/Controllers');
        
        // Ensure test directory exists
        if (!File::exists($this->testControllerPath)) {
            File::makeDirectory($this->testControllerPath, 0755, true);
        }
        
        $this->createTestDatabase();
    }

    protected function tearDown(): void
    {
        // Clean up generated test files
        $this->cleanupGeneratedFiles();
        
        parent::tearDown();
    }

    /** @test */
    public function generate_controller_command_creates_basic_controller()
    {
        $exitCode = Artisan::call('wink:controllers:generate', [
            'table' => 'users',
            '--model' => 'User',
            '--force' => true
        ]);

        $this->assertEquals(0, $exitCode);
        
        $output = Artisan::output();
        $this->assertStringContainsString('Controller generated successfully', $output);
    }

    /** @test */
    public function generate_controller_command_accepts_model_option()
    {
        $exitCode = Artisan::call('wink:controllers:generate', [
            'table' => 'users',
            '--model' => 'App\\Models\\User',
            '--force' => true
        ]);

        $this->assertEquals(0, $exitCode);
        
        $output = Artisan::output();
        $this->assertStringContainsString('users', $output);
    }

    /** @test */
    public function generate_controller_command_accepts_namespace_option()
    {
        $exitCode = Artisan::call('wink:controllers:generate', [
            'table' => 'users',
            '--namespace' => 'App\\Http\\Controllers\\Admin',
            '--force' => true
        ]);

        $this->assertEquals(0, $exitCode);
        
        $output = Artisan::output();
        $this->assertStringContainsString('Admin', $output);
    }

    /** @test */
    public function generate_api_controller_command_creates_api_controller()
    {
        $exitCode = Artisan::call('wink:controllers:api', [
            'table' => 'users',
            '--model' => 'User',
            '--force' => true
        ]);

        $this->assertEquals(0, $exitCode);
        
        $output = Artisan::output();
        $this->assertStringContainsString('API controller', $output);
    }

    /** @test */
    public function generate_api_controller_command_with_resources_option()
    {
        $exitCode = Artisan::call('wink:controllers:api', [
            'table' => 'users',
            '--model' => 'User',
            '--with-resources' => true,
            '--force' => true
        ]);

        $this->assertEquals(0, $exitCode);
        
        $output = Artisan::output();
        $this->assertStringContainsString('resources', $output);
    }

    /** @test */
    public function generate_api_controller_command_with_tests_option()
    {
        $exitCode = Artisan::call('wink:controllers:api', [
            'table' => 'users',
            '--model' => 'User',
            '--with-tests' => true,
            '--force' => true
        ]);

        $this->assertEquals(0, $exitCode);
        
        $output = Artisan::output();
        $this->assertStringContainsString('tests', $output);
    }

    /** @test */
    public function generate_web_controller_command_creates_web_controller()
    {
        $exitCode = Artisan::call('wink:controllers:web', [
            'table' => 'users',
            '--model' => 'User',
            '--force' => true
        ]);

        $this->assertEquals(0, $exitCode);
        
        $output = Artisan::output();
        $this->assertStringContainsString('Web controller', $output);
    }

    /** @test */
    public function generate_web_controller_command_with_views_option()
    {
        $exitCode = Artisan::call('wink:controllers:web', [
            'table' => 'users',
            '--model' => 'User',
            '--with-views' => true,
            '--force' => true
        ]);

        $this->assertEquals(0, $exitCode);
        
        $output = Artisan::output();
        $this->assertStringContainsString('views', $output);
    }

    /** @test */
    public function generate_web_controller_command_with_middleware_option()
    {
        $exitCode = Artisan::call('wink:controllers:web', [
            'table' => 'users',
            '--model' => 'User',
            '--middleware' => 'auth,verified',
            '--force' => true
        ]);

        $this->assertEquals(0, $exitCode);
        
        $output = Artisan::output();
        $this->assertStringContainsString('middleware', $output);
    }

    /** @test */
    public function commands_fail_gracefully_with_invalid_table()
    {
        $exitCode = Artisan::call('wink:controllers:generate', [
            'table' => 'nonexistent_table',
            '--force' => true
        ]);

        // Should handle gracefully, not crash
        $this->assertIsInt($exitCode);
        
        $output = Artisan::output();
        $this->assertNotEmpty($output);
    }

    /** @test */
    public function commands_respect_force_flag()
    {
        // First, create a controller
        Artisan::call('wink:controllers:generate', [
            'table' => 'users',
            '--model' => 'User',
            '--force' => true
        ]);

        // Try to create again without force flag
        $exitCode = Artisan::call('wink:controllers:generate', [
            'table' => 'users',
            '--model' => 'User',
        ]);

        $output = Artisan::output();
        $this->assertStringContainsString('exists', $output);
    }

    /** @test */
    public function commands_overwrite_with_force_flag()
    {
        // First, create a controller
        Artisan::call('wink:controllers:generate', [
            'table' => 'users',
            '--model' => 'User',
            '--force' => true
        ]);

        // Create again with force flag
        $exitCode = Artisan::call('wink:controllers:generate', [
            'table' => 'users',
            '--model' => 'User',
            '--force' => true
        ]);

        $this->assertEquals(0, $exitCode);
        
        $output = Artisan::output();
        $this->assertStringContainsString('generated', $output);
    }

    /** @test */
    public function commands_display_help_information()
    {
        $exitCode = Artisan::call('wink:controllers:generate', ['--help' => true]);
        
        $output = Artisan::output();
        $this->assertStringContainsString('Usage:', $output);
        $this->assertStringContainsString('Arguments:', $output);
        $this->assertStringContainsString('Options:', $output);
    }

    /** @test */
    public function api_command_displays_help_information()
    {
        $exitCode = Artisan::call('wink:controllers:api', ['--help' => true]);
        
        $output = Artisan::output();
        $this->assertStringContainsString('Generate API controllers', $output);
        $this->assertStringContainsString('--with-resources', $output);
        $this->assertStringContainsString('--with-tests', $output);
    }

    /** @test */
    public function web_command_displays_help_information()
    {
        $exitCode = Artisan::call('wink:controllers:web', ['--help' => true]);
        
        $output = Artisan::output();
        $this->assertStringContainsString('Generate web controllers', $output);
        $this->assertStringContainsString('--with-views', $output);
        $this->assertStringContainsString('--middleware', $output);
    }

    /** @test */
    public function commands_validate_required_arguments()
    {
        $exitCode = Artisan::call('wink:controllers:generate');
        
        // Should fail without required table argument
        $this->assertNotEquals(0, $exitCode);
        
        $output = Artisan::output();
        $this->assertStringContainsString('table', $output);
    }

    /** @test */
    public function commands_handle_complex_table_names()
    {
        $exitCode = Artisan::call('wink:controllers:generate', [
            'table' => 'user_profiles',
            '--model' => 'UserProfile',
            '--force' => true
        ]);

        $this->assertEquals(0, $exitCode);
        
        $output = Artisan::output();
        $this->assertStringContainsString('user_profiles', $output);
    }

    /** @test */
    public function commands_work_with_custom_namespace_paths()
    {
        $exitCode = Artisan::call('wink:controllers:api', [
            'table' => 'products',
            '--namespace' => 'App\\Http\\Controllers\\Api\\V1',
            '--force' => true
        ]);

        $this->assertEquals(0, $exitCode);
        
        $output = Artisan::output();
        $this->assertStringContainsString('V1', $output);
    }

    private function createTestDatabase(): void
    {
        // Create in-memory SQLite database for testing
        config(['database.connections.testing' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]]);
        
        config(['database.default' => 'testing']);
    }

    private function cleanupGeneratedFiles(): void
    {
        $patterns = [
            $this->testControllerPath . '/*Controller.php',
            $this->testControllerPath . '/Api/*Controller.php',
            $this->testControllerPath . '/Admin/*Controller.php',
            app_path('Http/Resources/*.php'),
            app_path('Http/Requests/*.php'),
            base_path('tests/Feature/*ControllerTest.php'),
        ];

        foreach ($patterns as $pattern) {
            $files = glob($pattern);
            foreach ($files as $file) {
                if (File::exists($file) && strpos($file, 'Test') !== false) {
                    File::delete($file);
                }
            }
        }
    }
}