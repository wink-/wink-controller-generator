<?php

namespace Wink\ControllerGenerator\Tests\Unit;

use Illuminate\Filesystem\Filesystem;
use Mockery;
use Wink\ControllerGenerator\Generators\AbstractControllerGenerator;
use Wink\ControllerGenerator\Generators\ApiControllerGenerator;
use Wink\ControllerGenerator\Generators\ResourceControllerGenerator;
use Wink\ControllerGenerator\Generators\WebControllerGenerator;
use Wink\ControllerGenerator\Tests\TestCase;

class GeneratorTest extends TestCase
{
    protected Filesystem $filesystem;
    protected string $tempDir;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->filesystem = Mockery::mock(Filesystem::class);
        $this->tempDir = sys_get_temp_dir() . '/wink-controller-tests';
        
        if (!is_dir($this->tempDir)) {
            mkdir($this->tempDir, 0755, true);
        }
    }

    protected function tearDown(): void
    {
        Mockery::close();
        
        if (is_dir($this->tempDir)) {
            $this->removeDirectory($this->tempDir);
        }
        
        parent::tearDown();
    }

    /** @test */
    public function api_controller_generator_creates_controller_with_default_config()
    {
        $generator = new ApiControllerGenerator($this->filesystem);
        
        $this->filesystem
            ->shouldReceive('get')
            ->with(Mockery::pattern('/api-controller\.stub$/'))
            ->andReturn($this->getApiControllerStub());
            
        $this->filesystem
            ->shouldReceive('put')
            ->with(
                Mockery::pattern('/UserController\.php$/'),
                Mockery::type('string')
            )
            ->andReturn(true);
            
        $this->filesystem
            ->shouldReceive('exists')
            ->andReturn(false);
            
        $this->filesystem
            ->shouldReceive('makeDirectory')
            ->andReturn(true);

        $result = $generator->generate('User', [
            'namespace' => 'App\\Http\\Controllers\\Api',
            'output_path' => $this->tempDir
        ]);

        $this->assertStringContainsString('UserController.php', $result);
    }

    /** @test */
    public function web_controller_generator_creates_controller_with_views()
    {
        $generator = new WebControllerGenerator($this->filesystem);
        
        $this->filesystem
            ->shouldReceive('get')
            ->with(Mockery::pattern('/web-controller\.stub$/'))
            ->andReturn($this->getWebControllerStub());
            
        $this->filesystem
            ->shouldReceive('put')
            ->with(
                Mockery::pattern('/UserController\.php$/'),
                Mockery::type('string')
            )
            ->andReturn(true);
            
        $this->filesystem
            ->shouldReceive('exists')
            ->andReturn(false);
            
        $this->filesystem
            ->shouldReceive('makeDirectory')
            ->andReturn(true);

        $result = $generator->generate('User', [
            'namespace' => 'App\\Http\\Controllers',
            'output_path' => $this->tempDir,
            'with_views' => true
        ]);

        $this->assertStringContainsString('UserController.php', $result);
    }

    /** @test */
    public function resource_controller_generator_creates_full_crud_controller()
    {
        $generator = new ResourceControllerGenerator($this->filesystem);
        
        $this->filesystem
            ->shouldReceive('get')
            ->with(Mockery::pattern('/api-controller\.stub$/'))
            ->andReturn($this->getResourceControllerStub());
            
        $this->filesystem
            ->shouldReceive('put')
            ->with(
                Mockery::pattern('/UserController\.php$/'),
                Mockery::type('string')
            )
            ->andReturn(true);
            
        $this->filesystem
            ->shouldReceive('exists')
            ->andReturn(false);
            
        $this->filesystem
            ->shouldReceive('makeDirectory')
            ->andReturn(true);

        $result = $generator->generate('User', [
            'namespace' => 'App\\Http\\Controllers',
            'output_path' => $this->tempDir,
            'resource' => true
        ]);

        $this->assertStringContainsString('UserController.php', $result);
    }

    /** @test */
    public function generator_throws_exception_when_template_not_found()
    {
        $generator = new ApiControllerGenerator($this->filesystem);
        
        $this->filesystem
            ->shouldReceive('get')
            ->andThrow(new \Exception('Template not found'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Template not found');

        $generator->generate('User', ['output_path' => $this->tempDir]);
    }

    /** @test */
    public function generator_overwrites_existing_file_when_force_option_used()
    {
        $generator = new ApiControllerGenerator($this->filesystem);
        
        $this->filesystem
            ->shouldReceive('get')
            ->with(Mockery::pattern('/api-controller\.stub$/'))
            ->andReturn($this->getApiControllerStub());
            
        $this->filesystem
            ->shouldReceive('exists')
            ->andReturn(true);
            
        $this->filesystem
            ->shouldReceive('put')
            ->with(
                Mockery::pattern('/UserController\.php$/'),
                Mockery::type('string')
            )
            ->andReturn(true);
            
        $this->filesystem
            ->shouldReceive('makeDirectory')
            ->andReturn(true);

        $result = $generator->generate('User', [
            'namespace' => 'App\\Http\\Controllers\\Api',
            'output_path' => $this->tempDir,
            'force' => true
        ]);

        $this->assertStringContainsString('UserController.php', $result);
    }

    /** @test */
    public function generator_skips_existing_file_when_force_option_not_used()
    {
        $generator = new ApiControllerGenerator($this->filesystem);
        
        $this->filesystem
            ->shouldReceive('exists')
            ->andReturn(true);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('already exists');

        $generator->generate('User', [
            'namespace' => 'App\\Http\\Controllers\\Api',
            'output_path' => $this->tempDir,
            'force' => false
        ]);
    }

    /** @test */
    public function generator_creates_directory_if_not_exists()
    {
        $generator = new ApiControllerGenerator($this->filesystem);
        
        $this->filesystem
            ->shouldReceive('get')
            ->with(Mockery::pattern('/api-controller\.stub$/'))
            ->andReturn($this->getApiControllerStub());
            
        $this->filesystem
            ->shouldReceive('exists')
            ->with(Mockery::pattern('/Controllers$/'))
            ->andReturn(false);
            
        $this->filesystem
            ->shouldReceive('exists')
            ->with(Mockery::pattern('/UserController\.php$/'))
            ->andReturn(false);
            
        $this->filesystem
            ->shouldReceive('makeDirectory')
            ->with(Mockery::pattern('/Controllers$/'), 0755, true)
            ->andReturn(true);
            
        $this->filesystem
            ->shouldReceive('put')
            ->andReturn(true);

        $result = $generator->generate('User', [
            'namespace' => 'App\\Http\\Controllers\\Api',
            'output_path' => $this->tempDir
        ]);

        $this->assertStringContainsString('UserController.php', $result);
    }

    private function getApiControllerStub(): string
    {
        return '<?php

namespace {{ namespace }};

use App\Http\Controllers\Controller;
use App\Models\{{ model }};
use Illuminate\Http\Request;

class {{ class }} extends Controller
{
    public function index()
    {
        return {{ model }}::all();
    }
}';
    }

    private function getWebControllerStub(): string
    {
        return '<?php

namespace {{ namespace }};

use App\Http\Controllers\Controller;
use App\Models\{{ model }};
use Illuminate\Http\Request;

class {{ class }} extends Controller
{
    public function index()
    {
        ${{ modelVariable }}s = {{ model }}::all();
        return view("{{ viewPrefix }}.index", compact("{{ modelVariable }}s"));
    }
}';
    }

    private function getResourceControllerStub(): string
    {
        return '<?php

namespace {{ namespace }};

use App\Http\Controllers\Controller;
use App\Models\{{ model }};
use Illuminate\Http\Request;

class {{ class }} extends Controller
{
    public function index()
    {
        return {{ model }}::all();
    }

    public function store(Request $request)
    {
        return {{ model }}::create($request->validated());
    }

    public function show({{ model }} ${{ modelVariable }})
    {
        return ${{ modelVariable }};
    }

    public function update(Request $request, {{ model }} ${{ modelVariable }})
    {
        ${{ modelVariable }}->update($request->validated());
        return ${{ modelVariable }};
    }

    public function destroy({{ model }} ${{ modelVariable }})
    {
        ${{ modelVariable }}->delete();
        return response()->noContent();
    }
}';
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}