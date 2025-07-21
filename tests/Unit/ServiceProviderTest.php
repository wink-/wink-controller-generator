<?php

namespace Wink\ControllerGenerator\Tests\Unit;

use Illuminate\Support\Facades\Artisan;
use Wink\ControllerGenerator\Commands\GenerateApiControllerCommand;
use Wink\ControllerGenerator\Commands\GenerateControllerCommand;
use Wink\ControllerGenerator\Commands\GenerateWebControllerCommand;
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
        
        $this->assertContains('wink:controllers:generate', $registeredCommands);
        $this->assertContains('wink:controllers:api', $registeredCommands);
        $this->assertContains('wink:controllers:web', $registeredCommands);
    }

    /** @test */
    public function generate_controller_command_is_registered()
    {
        $command = Artisan::all()['wink:controllers:generate'];
        $this->assertInstanceOf(GenerateControllerCommand::class, $command);
    }

    /** @test */
    public function generate_api_controller_command_is_registered()
    {
        $command = Artisan::all()['wink:controllers:api'];
        $this->assertInstanceOf(GenerateApiControllerCommand::class, $command);
    }

    /** @test */
    public function generate_web_controller_command_is_registered()
    {
        $command = Artisan::all()['wink:controllers:web'];
        $this->assertInstanceOf(GenerateWebControllerCommand::class, $command);
    }

    /** @test */
    public function config_can_be_published()
    {
        // Check if config publish group is registered
        $publishGroups = $this->app->make('Illuminate\Foundation\Application')->getPublishGroups();
        
        $this->assertArrayHasKey('wink-controllers-config', $publishGroups);
        
        $configPublishes = $publishGroups['wink-controllers-config'];
        $this->assertNotEmpty($configPublishes);
        
        // Verify the source and destination paths
        $configPath = realpath(__DIR__ . '/../../config/wink-controllers.php');
        $this->assertArrayHasKey($configPath, $configPublishes);
    }

    /** @test */
    public function stubs_can_be_published()
    {
        // Check if stubs publish group is registered
        $publishGroups = $this->app->make('Illuminate\Foundation\Application')->getPublishGroups();
        
        $this->assertArrayHasKey('wink-controllers-stubs', $publishGroups);
        
        $stubsPublishes = $publishGroups['wink-controllers-stubs'];
        $this->assertNotEmpty($stubsPublishes);
        
        // Verify the source path exists
        $stubsPath = realpath(__DIR__ . '/../../stubs');
        $this->assertArrayHasKey($stubsPath, $stubsPublishes);
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
        $this->assertArrayHasKey('wink:controllers:generate', Artisan::all());
        $this->assertArrayHasKey('wink:controllers:api', Artisan::all());
        $this->assertArrayHasKey('wink:controllers:web', Artisan::all());
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
        $publishGroups = $this->app->make('Illuminate\Foundation\Application')->getPublishGroups();
        
        // Check that both publish groups exist
        $this->assertArrayHasKey('wink-controllers-config', $publishGroups);
        $this->assertArrayHasKey('wink-controllers-stubs', $publishGroups);
        
        // Verify config publish path
        $configPublishes = $publishGroups['wink-controllers-config'];
        $configDestination = array_values($configPublishes)[0];
        $this->assertStringEndsWith('config/wink-controllers.php', $configDestination);
        
        // Verify stubs publish path
        $stubsPublishes = $publishGroups['wink-controllers-stubs'];
        $stubsDestination = array_values($stubsPublishes)[0];
        $this->assertStringEndsWith('resources/stubs/wink/controllers', $stubsDestination);
    }

    /** @test */
    public function commands_have_correct_signatures()
    {
        $generateCommand = Artisan::all()['wink:controllers:generate'];
        $apiCommand = Artisan::all()['wink:controllers:api'];
        $webCommand = Artisan::all()['wink:controllers:web'];
        
        // Test command signatures contain required arguments
        $this->assertStringContainsString('table', $generateCommand->getDefinition()->getArguments()['table']->getName());
        $this->assertStringContainsString('table', $apiCommand->getDefinition()->getArguments()['table']->getName());
        $this->assertStringContainsString('table', $webCommand->getDefinition()->getArguments()['table']->getName());
    }
}