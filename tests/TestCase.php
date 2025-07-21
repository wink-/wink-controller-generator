<?php

namespace Wink\ControllerGenerator\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as Orchestra;
use Wink\ControllerGenerator\ControllerGeneratorServiceProvider;
use Wink\ControllerGenerator\Tests\Helpers\ControllerTestHelper;
use Wink\ControllerGenerator\Tests\Helpers\DatabaseTestHelper;
use Wink\ControllerGenerator\Tests\Helpers\StubTestHelper;

class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected ControllerTestHelper $controllerHelper;
    protected DatabaseTestHelper $databaseHelper;
    protected StubTestHelper $stubHelper;

    protected function setUp(): void
    {
        parent::setUp();

        $this->controllerHelper = new ControllerTestHelper();
        $this->databaseHelper = new DatabaseTestHelper();
        $this->stubHelper = new StubTestHelper();

        $this->setUpDatabase();
        $this->setUpFactories();
    }

    protected function tearDown(): void
    {
        $this->cleanupTestFiles();
        parent::tearDown();
    }

    protected function getPackageProviders($app)
    {
        return [
            ControllerGeneratorServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        // Database configuration
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);

        // Package configuration
        $app['config']->set('wink-controllers', [
            'default_namespace' => 'App\\Http\\Controllers',
            'api_namespace' => 'App\\Http\\Controllers\\Api',
            'web_namespace' => 'App\\Http\\Controllers',
            'output_path' => 'app/Http/Controllers',
            'template_path' => __DIR__ . '/../stubs',
            'stubs' => [
                'api_controller' => __DIR__ . '/../stubs/api-controller.stub',
                'web_controller' => __DIR__ . '/../stubs/web-controller.stub',
                'form_request' => __DIR__ . '/../stubs/form-request.stub',
            ],
            'default_middleware' => ['auth'],
            'api_middleware' => ['auth:api'],
            'generate_validation' => true,
            'generate_resources' => true,
            'generate_tests' => false,
            'auto_generate_requests' => true,
            'auto_generate_resources' => true,
            'auto_generate_tests' => false,
            'request_namespace' => 'App\\Http\\Requests',
            'resource_namespace' => 'App\\Http\\Resources',
            'test_namespace' => 'Tests\\Feature',
            'request_suffix' => 'Request',
            'resource_suffix' => 'Resource',
            'test_suffix' => 'ControllerTest',
            'view_path' => 'resources/views',
            'view_extension' => '.blade.php',
            'database_connection' => null,
        ]);

        // File system configuration
        $app['config']->set('filesystems.default', 'local');
        $app['config']->set('filesystems.disks.local.root', storage_path('app'));
    }

    /**
     * Set up the database for testing.
     */
    protected function setUpDatabase(): void
    {
        // Create basic test tables
        $this->createUsersTable();
        $this->createCategoriesTable();
        $this->createPostsTable();
        $this->createProductsTable();
        $this->createUserProfilesTable();
    }

    /**
     * Set up factories for testing.
     */
    protected function setUpFactories(): void
    {
        // Laravel 12 uses automatic factory discovery
        // Factories should be placed in the correct namespace and follow naming conventions
        // This method is kept for any manual factory registration if needed
        $this->registerTestFactories();
    }

    /**
     * Create users table for testing.
     */
    protected function createUsersTable(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->enum('role', ['user', 'admin', 'moderator'])->default('user');
            $table->boolean('is_active')->default(true);
            $table->rememberToken();
            $table->timestamps();

            $table->index(['email', 'is_active']);
        });
    }

    /**
     * Create categories table for testing.
     */
    protected function createCategoriesTable(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->timestamps();

            $table->foreign('parent_id')->references('id')->on('categories')->onDelete('set null');
            $table->index(['parent_id', 'is_active']);
            $table->index('sort_order');
        });
    }

    /**
     * Create posts table for testing.
     */
    protected function createPostsTable(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('content');
            $table->text('excerpt')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->nullable()->constrained()->onDelete('set null');
            $table->string('featured_image')->nullable();
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->boolean('is_featured')->default(false);
            $table->json('meta_data')->nullable();
            $table->timestamps();

            $table->index(['status', 'published_at']);
            $table->index(['user_id', 'status']);
            $table->index('is_featured');
        });
    }

    /**
     * Create products table for testing.
     */
    protected function createProductsTable(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description');
            $table->text('short_description')->nullable();
            $table->decimal('price', 10, 2);
            $table->decimal('cost', 10, 2)->nullable();
            $table->string('sku')->unique();
            $table->integer('stock_quantity')->default(0);
            $table->decimal('weight', 8, 3)->nullable();
            $table->json('dimensions')->nullable();
            $table->foreignId('category_id')->nullable()->constrained()->onDelete('set null');
            $table->unsignedBigInteger('brand_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false);
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->json('attributes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_active', 'is_featured']);
            $table->index(['category_id', 'is_active']);
            $table->index('stock_quantity');
        });
    }

    /**
     * Create user_profiles table for testing.
     */
    protected function createUserProfilesTable(): void
    {
        Schema::create('user_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('phone')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female', 'other', 'prefer_not_to_say'])->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('postal_code')->nullable();
            $table->string('country')->nullable();
            $table->text('bio')->nullable();
            $table->string('avatar')->nullable();
            $table->string('timezone')->default('UTC');
            $table->string('locale')->default('en');
            $table->json('preferences')->nullable();
            $table->timestamps();

            $table->unique('user_id');
            $table->index(['city', 'state', 'country']);
        });
    }

    /**
     * Clean up test files after each test.
     */
    protected function cleanupTestFiles(): void
    {
        // Clean up any temporary directories created during tests
        $tempPaths = [
            sys_get_temp_dir() . '/wink-test-*',
            sys_get_temp_dir() . '/wink-stubs-test',
            sys_get_temp_dir() . '/wink-integration-tests',
            sys_get_temp_dir() . '/wink-config-tests',
        ];

        foreach ($tempPaths as $pattern) {
            foreach (glob($pattern) as $path) {
                if (is_dir($path)) {
                    File::deleteDirectory($path);
                } elseif (is_file($path)) {
                    File::delete($path);
                }
            }
        }
    }

    /**
     * Assert that a file has been generated with expected content.
     */
    protected function assertFileGenerated(string $filePath, array $expectedContent = []): void
    {
        $this->assertFileExists($filePath);
        
        if (!empty($expectedContent)) {
            $content = File::get($filePath);
            
            foreach ($expectedContent as $expected) {
                $this->assertStringContainsString($expected, $content);
            }
        }
    }

    /**
     * Assert that a controller follows Laravel conventions.
     */
    protected function assertValidController(string $filePath): void
    {
        $this->assertFileExists($filePath);
        
        $content = File::get($filePath);
        
        // Basic structure assertions
        $this->assertStringContainsString('<?php', $content);
        $this->assertStringContainsString('namespace ', $content);
        $this->assertStringContainsString('class ', $content);
        $this->assertStringContainsString('extends Controller', $content);
        
        // Ensure valid PHP syntax
        $this->assertTrue($this->controllerHelper->isValidPhpSyntax($content));
    }

    /**
     * Create a temporary directory for testing.
     */
    protected function createTempDirectory(string $prefix = 'wink-test'): string
    {
        return $this->controllerHelper->createTempDirectory($prefix);
    }

    /**
     * Get sample validation rules for testing.
     */
    protected function getSampleValidationRules(): array
    {
        return $this->controllerHelper->getSampleValidationRules();
    }

    /**
     * Get sample template variables for testing.
     */
    protected function getSampleTemplateVariables(): array
    {
        return $this->stubHelper->getSampleTemplateVariables();
    }

    /**
     * Create test tables with specific configuration.
     */
    protected function createTestTable(string $tableName, array $columns = []): void
    {
        $this->databaseHelper->createTestTable($tableName, $columns);
    }

    /**
     * Seed test data into a table.
     */
    protected function seedTestData(string $tableName, array $data): void
    {
        $this->databaseHelper->seedTestData($tableName, $data);
    }

    /**
     * Assert that a table exists and has expected columns.
     */
    protected function assertTableStructure(string $tableName, array $expectedColumns): void
    {
        $this->assertTrue(Schema::hasTable($tableName));
        
        $actualColumns = Schema::getColumnListing($tableName);
        
        foreach ($expectedColumns as $column) {
            $this->assertContains($column, $actualColumns);
        }
    }

    /**
     * Mock Artisan command execution.
     */
    protected function mockArtisanCommand(string $command, array $parameters = [], int $exitCode = 0): void
    {
        $this->artisan($command, $parameters)
             ->assertExitCode($exitCode);
    }

    /**
     * Get the package base path.
     */
    protected function getPackageBasePath(): string
    {
        return realpath(__DIR__ . '/..');
    }

    /**
     * Get the path to test fixtures.
     */
    protected function getFixturesPath(): string
    {
        return __DIR__ . '/Fixtures';
    }

    /**
     * Load test migrations.
     */
    protected function loadTestMigrations(): void
    {
        $migrationPath = $this->getFixturesPath() . '/Migrations';
        
        if (is_dir($migrationPath)) {
            $this->loadMigrationsFrom($migrationPath);
        }
    }

    /**
     * Register test factories.
     */
    protected function registerTestFactories(): void
    {
        $factoryPath = $this->getFixturesPath() . '/Factories';
        
        if (is_dir($factoryPath)) {
            foreach (glob($factoryPath . '/*Factory.php') as $factoryFile) {
                require_once $factoryFile;
            }
        }
    }
}