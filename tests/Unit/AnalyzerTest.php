<?php

namespace Wink\ControllerGenerator\Tests\Unit;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Wink\ControllerGenerator\Analyzers\ModelAnalyzer;
use Wink\ControllerGenerator\Analyzers\RouteAnalyzer;
use Wink\ControllerGenerator\Analyzers\ValidationAnalyzer;
use Wink\ControllerGenerator\Tests\TestCase;
use Wink\ControllerGenerator\Tests\Fixtures\Models\User;
use Wink\ControllerGenerator\Tests\Fixtures\Models\Post;
use Wink\ControllerGenerator\Tests\Fixtures\Models\Product;

class AnalyzerTest extends TestCase
{
    protected ModelAnalyzer $modelAnalyzer;
    protected RouteAnalyzer $routeAnalyzer;
    protected ValidationAnalyzer $validationAnalyzer;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->modelAnalyzer = new ModelAnalyzer();
        $this->routeAnalyzer = new RouteAnalyzer();
        $this->validationAnalyzer = new ValidationAnalyzer();
        
        $this->createTestTables();
    }

    protected function tearDown(): void
    {
        $this->dropTestTables();
        parent::tearDown();
    }

    /** @test */
    public function model_analyzer_detects_existing_model()
    {
        $this->assertTrue($this->modelAnalyzer->modelExists(User::class));
        $this->assertFalse($this->modelAnalyzer->modelExists('App\\Models\\NonExistentModel'));
    }

    /** @test */
    public function model_analyzer_returns_correct_model_information()
    {
        $analysis = $this->modelAnalyzer->analyze(User::class);

        $this->assertArrayHasKey('class', $analysis);
        $this->assertArrayHasKey('table', $analysis);
        $this->assertArrayHasKey('fillable', $analysis);
        $this->assertArrayHasKey('relationships', $analysis);
        
        $this->assertEquals(User::class, $analysis['class']);
        $this->assertIsArray($analysis['fillable']);
        $this->assertIsArray($analysis['relationships']);
    }

    /** @test */
    public function model_analyzer_discovers_model_relationships()
    {
        $analysis = $this->modelAnalyzer->analyze(User::class);
        
        $relationshipNames = array_column($analysis['relationships'], 'name');
        $relationshipTypes = array_column($analysis['relationships'], 'type');
        
        $this->assertContains('posts', $relationshipNames);
        $this->assertContains('hasMany', $relationshipTypes);
    }

    /** @test */
    public function model_analyzer_discovers_all_models()
    {
        $models = $this->modelAnalyzer->discoverModels();
        
        $this->assertIsArray($models);
        $this->assertNotEmpty($models);
        
        $modelClasses = array_column($models, 'class');
        $this->assertContains('App\\Models\\User', $modelClasses);
    }

    /** @test */
    public function route_analyzer_detects_existing_routes()
    {
        // Mock some routes
        app('router')->get('/users', function () {});
        app('router')->post('/users', function () {});
        
        $routes = $this->routeAnalyzer->getExistingRoutes('users');
        
        $this->assertIsArray($routes);
        $this->assertNotEmpty($routes);
    }

    /** @test */
    public function route_analyzer_suggests_route_patterns()
    {
        $suggestions = $this->routeAnalyzer->suggestRoutes('User', 'api');
        
        $this->assertIsArray($suggestions);
        $this->assertArrayHasKey('index', $suggestions);
        $this->assertArrayHasKey('store', $suggestions);
        $this->assertArrayHasKey('show', $suggestions);
        $this->assertArrayHasKey('update', $suggestions);
        $this->assertArrayHasKey('destroy', $suggestions);
    }

    /** @test */
    public function route_analyzer_detects_route_conflicts()
    {
        // Add existing route
        app('router')->get('/api/users', function () {});
        
        $conflicts = $this->routeAnalyzer->detectConflicts('User', 'api');
        
        $this->assertIsArray($conflicts);
        
        if (!empty($conflicts)) {
            $this->assertArrayHasKey('method', $conflicts[0]);
            $this->assertArrayHasKey('uri', $conflicts[0]);
        }
    }

    /** @test */
    public function validation_analyzer_generates_rules_from_database_schema()
    {
        $rules = $this->validationAnalyzer->generateRulesFromSchema('users');
        
        $this->assertIsArray($rules);
        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('email', $rules);
        
        $this->assertContains('required', $rules['name']);
        $this->assertContains('string', $rules['name']);
        $this->assertContains('required', $rules['email']);
        $this->assertContains('email', $rules['email']);
    }

    /** @test */
    public function validation_analyzer_generates_rules_from_model()
    {
        $rules = $this->validationAnalyzer->generateRulesFromModel(User::class);
        
        $this->assertIsArray($rules);
        $this->assertNotEmpty($rules);
        
        // Should include rules for fillable fields
        foreach ((new User())->getFillable() as $field) {
            $this->assertArrayHasKey($field, $rules);
        }
    }

    /** @test */
    public function validation_analyzer_detects_unique_constraints()
    {
        $rules = $this->validationAnalyzer->generateRulesFromSchema('users');
        
        $this->assertContains('unique:users,email', $rules['email']);
    }

    /** @test */
    public function validation_analyzer_handles_nullable_fields()
    {
        $rules = $this->validationAnalyzer->generateRulesFromSchema('posts');
        
        // Assuming published_at is nullable
        if (isset($rules['published_at'])) {
            $this->assertContains('nullable', $rules['published_at']);
        }
    }

    /** @test */
    public function validation_analyzer_generates_update_rules()
    {
        $rules = $this->validationAnalyzer->generateUpdateRules('users', 1);
        
        $this->assertIsArray($rules);
        
        // Email should be unique except for current record
        if (isset($rules['email'])) {
            $this->assertContains('unique:users,email,1', $rules['email']);
        }
    }

    /** @test */
    public function validation_analyzer_handles_foreign_keys()
    {
        $rules = $this->validationAnalyzer->generateRulesFromSchema('posts');
        
        $this->assertArrayHasKey('user_id', $rules);
        $this->assertContains('exists:users,id', $rules['user_id']);
    }

    /** @test */
    public function validation_analyzer_respects_field_types()
    {
        $rules = $this->validationAnalyzer->generateRulesFromSchema('products');
        
        // Price should be numeric
        $this->assertArrayHasKey('price', $rules);
        $this->assertContains('numeric', $rules['price']);
        
        // Description should be string
        $this->assertArrayHasKey('description', $rules);
        $this->assertContains('string', $rules['description']);
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