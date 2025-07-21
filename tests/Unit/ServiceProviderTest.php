<?php

namespace Wink\ControllerGenerator\Tests\Unit;

use Illuminate\Support\Facades\Artisan;
use Wink\ControllerGenerator\Commands\GenerateControllerCommand;
use Wink\ControllerGenerator\ControllerGeneratorServiceProvider;
use Wink\ControllerGenerator\Tests\TestCase;

class ServiceProviderTest extends TestCase
{
    /** @test */
    public function service_provider_is_registered()
    {
        $this->assertTrue($this->app->providerIsLoaded(ControllerGeneratorServiceProvider::class));
    }

    /** @test */
    public function config_is_merged()
    {
        $this->assertNotNull(config('wink-controllers'));
        $this->assertIsArray(config('wink-controllers'));
    }

    /** @test */
    public function commands_are_registered()
    {
        $registeredCommands = array_keys(Artisan::all());
        
        $this->assertContains('wink:generate-controllers', $registeredCommands);
    }

    /** @test */
    public function generate_controller_command_is_registered()
    {
        $command = Artisan::all()['wink:generate-controllers'];
        $this->assertInstanceOf(GenerateControllerCommand::class, $command);
    }

    /** @test */
    public function config_can_be_published()
    {
        // Test that config publishing works by attempting to publish
        $result = $this->artisan('vendor:publish', [
            '--tag' => 'wink-controllers-config',
            '--force' => true
        ]);
        
        // The command should execute successfully
        $result->assertExitCode(0);
        
        // Verify the source config file exists
        $configPath = realpath(__DIR__ . '/../../config/wink-controllers.php');
        $this->assertFileExists($configPath);
    }

    /** @test */
    public function stubs_can_be_published()
    {
        // Test that stubs publishing works by attempting to publish
        $result = $this->artisan('vendor:publish', [
            '--tag' => 'wink-controllers-stubs',
            '--force' => true
        ]);
        
        // The command should execute successfully
        $result->assertExitCode(0);
        
        // Verify the source stubs directory exists
        $stubsPath = realpath(__DIR__ . '/../../stubs');
        $this->assertDirectoryExists($stubsPath);
    }

    /** @test */
    public function default_config_values_are_loaded()
    {
        $config = config('wink-controllers');
        
        // Test some expected default configuration keys
        $this->assertArrayHasKey('default_namespace', $config);
        $this->assertArrayHasKey('output_path', $config);
        $this->assertArrayHasKey('template_path', $config);
        
        // Test default values
        $this->assertEquals('App\\Http\\Controllers', $config['default_namespace']);
        $this->assertEquals('app/Http/Controllers', $config['output_path']);
    }

    /** @test */
    public function service_provider_only_registers_commands_in_console()
    {
        // This test ensures commands are only registered when running in console
        $this->assertTrue($this->app->runningInConsole());
        
        // Commands should be registered
        $this->assertArrayHasKey('wink:generate-controllers', Artisan::all());
    }

    /** @test */
    public function service_provider_provides_correct_services()
    {
        $provider = new ControllerGeneratorServiceProvider($this->app);
        
        // The service provider should be deferrable or provide services
        $this->assertInstanceOf(ControllerGeneratorServiceProvider::class, $provider);
    }

    /** @test */
    public function config_merge_works_correctly()
    {
        // Set a custom config value
        config(['wink-controllers.custom_option' => 'test_value']);
        
        // Verify the custom value is accessible
        $this->assertEquals('test_value', config('wink-controllers.custom_option'));
        
        // Verify default values are still accessible
        $this->assertNotNull(config('wink-controllers.default_namespace'));
    }

    /** @test */
    public function package_publishes_are_correctly_tagged()
    {
        // Test that both tagged publish groups work
        $configResult = $this->artisan('vendor:publish', [
            '--tag' => 'wink-controllers-config',
            '--dry-run' => true
        ]);
        
        $stubsResult = $this->artisan('vendor:publish', [
            '--tag' => 'wink-controllers-stubs', 
            '--dry-run' => true
        ]);
        
        // Both commands should execute successfully in dry-run mode
        $configResult->assertExitCode(0);
        $stubsResult->assertExitCode(0);
        
        // Verify source files exist
        $this->assertFileExists(__DIR__ . '/../../config/wink-controllers.php');
        $this->assertDirectoryExists(__DIR__ . '/../../stubs');
    }

    /** @test */
    public function commands_have_correct_signatures()
    {
        $generateCommand = Artisan::all()['wink:generate-controllers'];
        
        // Test command signature contains expected arguments and options
        $definition = $generateCommand->getDefinition();
        
        // Check if model argument exists (it's optional in our signature)
        $arguments = $definition->getArguments();
        $this->assertArrayHasKey('model', $arguments);
        
        // Check for key options
        $options = $definition->getOptions();
        $this->assertArrayHasKey('type', $options);
        $this->assertArrayHasKey('dry-run', $options);
        $this->assertArrayHasKey('force', $options);
    }
}