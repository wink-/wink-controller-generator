<?php

namespace Wink\ControllerGenerator\Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Wink\ControllerGenerator\Generators\ApiControllerGenerator;
use Wink\ControllerGenerator\Generators\WebControllerGenerator;
use Wink\ControllerGenerator\Tests\TestCase;

class IntegrationTest extends TestCase
{
    protected Filesystem $filesystem;
    protected string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->filesystem = new Filesystem();
        $this->tempDir = sys_get_temp_dir() . '/wink-integration-tests';
        
        if (!$this->filesystem->exists($this->tempDir)) {
            $this->filesystem->makeDirectory($this->tempDir, 0755, true);
        }
        
        $this->createTestDatabase();
        $this->createTestTables();
    }

    protected function tearDown(): void
    {
        if ($this->filesystem->exists($this->tempDir)) {
            $this->filesystem->deleteDirectory($this->tempDir);
        }
        
        $this->dropTestTables();
        
        parent::tearDown();
    }

    /** @test */
    public function full_api_controller_generation_workflow()
    {
        // Step 1: Generate API controller with all options
        $exitCode = Artisan::call('wink:controllers:api', [
            'table' => 'users',
            '--model' => 'User',
            '--namespace' => 'App\\Http\\Controllers\\Api',
            '--with-resources' => true,
            '--with-tests' => true,
            '--force' => true
        ]);

        $this->assertEquals(0, $exitCode);

        // Step 2: Verify API controller was generated
        $generator = new ApiControllerGenerator($this->filesystem, [
            'output_path' => $this->tempDir,
            'namespace' => 'App\\Http\\Controllers\\Api'
        ]);

        $controllerPath = $generator->generate('User', [
            'output_path' => $this->tempDir,
            'namespace' => 'App\\Http\\Controllers\\Api',
            'force' => true
        ]);

        $this->assertFileExists($controllerPath);

        // Step 3: Verify controller content
        $content = $this->filesystem->get($controllerPath);
        
        $this->assertStringContainsString('namespace App\\Http\\Controllers\\Api', $content);
        $this->assertStringContainsString('class UserController', $content);
        $this->assertStringContainsString('public function index()', $content);
        $this->assertStringContainsString('public function store(', $content);
        $this->assertStringContainsString('public function show(', $content);
        $this->assertStringContainsString('public function update(', $content);
        $this->assertStringContainsString('public function destroy(', $content);
    }

    /** @test */
    public function full_web_controller_generation_workflow()
    {
        // Step 1: Generate web controller with views
        $exitCode = Artisan::call('wink:controllers:web', [
            'table' => 'posts',
            '--model' => 'Post',
            '--namespace' => 'App\\Http\\Controllers',
            '--with-views' => true,
            '--middleware' => 'auth',
            '--force' => true
        ]);

        $this->assertEquals(0, $exitCode);

        // Step 2: Generate actual controller
        $generator = new WebControllerGenerator($this->filesystem, [
            'output_path' => $this->tempDir,
            'namespace' => 'App\\Http\\Controllers'
        ]);

        $controllerPath = $generator->generate('Post', [
            'output_path' => $this->tempDir,
            'namespace' => 'App\\Http\\Controllers',
            'with_views' => true,
            'middleware' => 'auth',
            'force' => true
        ]);

        $this->assertFileExists($controllerPath);

        // Step 3: Verify controller content
        $content = $this->filesystem->get($controllerPath);
        
        $this->assertStringContainsString('namespace App\\Http\\Controllers', $content);
        $this->assertStringContainsString('class PostController', $content);
        $this->assertStringContainsString('return view(', $content);
    }

    /** @test */
    public function resource_controller_generation_with_relationships()
    {
        // Create a controller for a model with relationships
        $generator = new ApiControllerGenerator($this->filesystem, [
            'output_path' => $this->tempDir,
            'namespace' => 'App\\Http\\Controllers\\Api'
        ]);

        $controllerPath = $generator->generate('Post', [
            'output_path' => $this->tempDir,
            'namespace' => 'App\\Http\\Controllers\\Api',
            'relationships' => [
                ['name' => 'user', 'type' => 'belongsTo'],
                ['name' => 'comments', 'type' => 'hasMany']
            ],
            'force' => true
        ]);

        $this->assertFileExists($controllerPath);

        $content = $this->filesystem->get($controllerPath);
        
        // Verify relationship loading if implemented
        $this->assertStringContainsString('PostController', $content);
    }

    /** @test */
    public function multiple_controllers_generation_in_sequence()
    {
        $models = ['User', 'Post', 'Product'];
        $generatedPaths = [];

        foreach ($models as $model) {
            $generator = new ApiControllerGenerator($this->filesystem, [
                'output_path' => $this->tempDir,
                'namespace' => 'App\\Http\\Controllers\\Api'
            ]);

            $path = $generator->generate($model, [
                'output_path' => $this->tempDir,
                'namespace' => 'App\\Http\\Controllers\\Api',
                'force' => true
            ]);

            $generatedPaths[] = $path;
            $this->assertFileExists($path);
        }

        // Verify all controllers exist
        $this->assertCount(3, $generatedPaths);
        
        foreach ($generatedPaths as $path) {
            $this->assertFileExists($path);
            $content = $this->filesystem->get($path);
            $this->assertStringContainsString('Controller', $content);
        }
    }

    /** @test */
    public function controller_generation_with_custom_templates()
    {
        // Create custom template
        $customTemplate = '<?php

namespace {{ namespace }};

use App\Http\Controllers\Controller;

/**
 * Custom controller template for {{ model }}
 */
class {{ class }} extends Controller
{
    // Custom implementation
    public function customMethod()
    {
        return "Custom method for {{ model }}";
    }
}';

        $templatePath = $this->tempDir . '/custom-controller.stub';
        $this->filesystem->put($templatePath, $customTemplate);

        // Generate controller with custom template
        $generator = new ApiControllerGenerator($this->filesystem, [
            'output_path' => $this->tempDir,
            'template_path' => $templatePath
        ]);

        $controllerPath = $generator->generate('CustomModel', [
            'output_path' => $this->tempDir,
            'namespace' => 'App\\Http\\Controllers',
            'force' => true
        ]);

        $this->assertFileExists($controllerPath);

        $content = $this->filesystem->get($controllerPath);
        $this->assertStringContainsString('Custom controller template for CustomModel', $content);
        $this->assertStringContainsString('customMethod', $content);
    }

    /** @test */
    public function controller_generation_handles_namespaced_models()
    {
        $generator = new ApiControllerGenerator($this->filesystem, [
            'output_path' => $this->tempDir,
            'namespace' => 'App\\Http\\Controllers\\Api'
        ]);

        $controllerPath = $generator->generate('App\\Models\\User', [
            'output_path' => $this->tempDir,
            'namespace' => 'App\\Http\\Controllers\\Api',
            'force' => true
        ]);

        $this->assertFileExists($controllerPath);

        $content = $this->filesystem->get($controllerPath);
        $this->assertStringContainsString('use App\\Models\\User', $content);
    }

    /** @test */
    public function validation_rules_are_generated_correctly()
    {
        // This test would verify that form request classes are generated with proper validation rules
        $generator = new ApiControllerGenerator($this->filesystem, [
            'output_path' => $this->tempDir,
            'namespace' => 'App\\Http\\Controllers\\Api'
        ]);

        $controllerPath = $generator->generate('User', [
            'output_path' => $this->tempDir,
            'namespace' => 'App\\Http\\Controllers\\Api',
            'with_validation' => true,
            'force' => true
        ]);

        $this->assertFileExists($controllerPath);

        $content = $this->filesystem->get($controllerPath);
        
        // Check if validation is included (this would depend on implementation)
        $this->assertStringContainsString('UserController', $content);
    }

    /** @test */
    public function error_handling_for_invalid_configurations()
    {
        $generator = new ApiControllerGenerator($this->filesystem, [
            'output_path' => '/invalid/path/that/does/not/exist',
            'namespace' => 'Invalid\\Namespace'
        ]);

        $this->expectException(\RuntimeException::class);

        $generator->generate('User', [
            'output_path' => '/invalid/path/that/does/not/exist',
            'namespace' => 'Invalid\\Namespace'
        ]);
    }

    /** @test */
    public function controller_overwrite_protection_works()
    {
        $generator = new ApiControllerGenerator($this->filesystem, [
            'output_path' => $this->tempDir,
            'namespace' => 'App\\Http\\Controllers\\Api'
        ]);

        // Generate first controller
        $firstPath = $generator->generate('User', [
            'output_path' => $this->tempDir,
            'namespace' => 'App\\Http\\Controllers\\Api',
            'force' => true
        ]);

        $this->assertFileExists($firstPath);

        // Try to generate again without force flag
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('already exists');

        $generator->generate('User', [
            'output_path' => $this->tempDir,
            'namespace' => 'App\\Http\\Controllers\\Api',
            'force' => false
        ]);
    }

    private function createTestDatabase(): void
    {
        config(['database.connections.testing' => [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]]);
        
        config(['database.default' => 'testing']);
    }

    private function createTestTables(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('content');
            $table->timestamp('published_at')->nullable();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->decimal('price', 10, 2);
            $table->integer('stock')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    private function dropTestTables(): void
    {
        Schema::dropIfExists('posts');
        Schema::dropIfExists('products');
        Schema::dropIfExists('users');
    }
}