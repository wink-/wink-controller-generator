<?php

namespace Wink\ControllerGenerator\Tests\Helpers;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class ControllerTestHelper
{
    protected Filesystem $filesystem;

    public function __construct(Filesystem $filesystem = null)
    {
        $this->filesystem = $filesystem ?: new Filesystem();
    }

    /**
     * Create a temporary directory for testing.
     */
    public function createTempDirectory(string $prefix = 'wink-test'): string
    {
        $tempDir = sys_get_temp_dir() . '/' . $prefix . '-' . Str::random(8);
        
        if (!$this->filesystem->exists($tempDir)) {
            $this->filesystem->makeDirectory($tempDir, 0755, true);
        }
        
        return $tempDir;
    }

    /**
     * Clean up a temporary directory.
     */
    public function cleanupTempDirectory(string $directory): void
    {
        if ($this->filesystem->exists($directory)) {
            $this->filesystem->deleteDirectory($directory);
        }
    }

    /**
     * Assert that a controller file has been generated correctly.
     */
    public function assertControllerGenerated(string $filePath, array $expectations = []): bool
    {
        if (!$this->filesystem->exists($filePath)) {
            throw new \Exception("Controller file not found: {$filePath}");
        }

        $content = $this->filesystem->get($filePath);

        // Basic PHP syntax check
        if (!$this->isValidPhpSyntax($content)) {
            throw new \Exception("Generated controller has invalid PHP syntax: {$filePath}");
        }

        // Check for expected content
        foreach ($expectations as $expected) {
            if (!str_contains($content, $expected)) {
                throw new \Exception("Expected content '{$expected}' not found in controller: {$filePath}");
            }
        }

        return true;
    }

    /**
     * Check if PHP content has valid syntax.
     */
    public function isValidPhpSyntax(string $phpContent): bool
    {
        // Create a temporary file to check syntax
        $tempFile = tempnam(sys_get_temp_dir(), 'php_syntax_check');
        file_put_contents($tempFile, $phpContent);
        
        $output = [];
        $returnCode = 0;
        exec("php -l {$tempFile} 2>&1", $output, $returnCode);
        
        unlink($tempFile);
        
        return $returnCode === 0;
    }

    /**
     * Extract class name from controller file content.
     */
    public function extractClassName(string $content): ?string
    {
        if (preg_match('/class\s+(\w+)/', $content, $matches)) {
            return $matches[1];
        }
        
        return null;
    }

    /**
     * Extract namespace from controller file content.
     */
    public function extractNamespace(string $content): ?string
    {
        if (preg_match('/namespace\s+([^;]+);/', $content, $matches)) {
            return trim($matches[1]);
        }
        
        return null;
    }

    /**
     * Extract methods from controller file content.
     */
    public function extractMethods(string $content): array
    {
        preg_match_all('/public\s+function\s+(\w+)\s*\(/', $content, $matches);
        
        return $matches[1] ?? [];
    }

    /**
     * Assert that controller has specific methods.
     */
    public function assertControllerHasMethods(string $content, array $expectedMethods): bool
    {
        $actualMethods = $this->extractMethods($content);
        
        foreach ($expectedMethods as $method) {
            if (!in_array($method, $actualMethods)) {
                throw new \Exception("Expected method '{$method}' not found in controller");
            }
        }
        
        return true;
    }

    /**
     * Assert that controller uses specific classes/traits.
     */
    public function assertControllerUses(string $content, array $expectedUses): bool
    {
        foreach ($expectedUses as $use) {
            $pattern = '/use\s+' . preg_quote($use, '/') . '\s*;/';
            if (!preg_match($pattern, $content)) {
                throw new \Exception("Expected use statement '{$use}' not found in controller");
            }
        }
        
        return true;
    }

    /**
     * Generate a mock model class content for testing.
     */
    public function generateMockModelContent(string $modelName, array $options = []): string
    {
        $namespace = $options['namespace'] ?? 'App\\Models';
        $fillable = $options['fillable'] ?? ['name', 'email'];
        $relationships = $options['relationships'] ?? [];

        $content = "<?php\n\nnamespace {$namespace};\n\n";
        $content .= "use Illuminate\\Database\\Eloquent\\Model;\n";
        $content .= "use Illuminate\\Database\\Eloquent\\Factories\\HasFactory;\n\n";
        $content .= "class {$modelName} extends Model\n{\n";
        $content .= "    use HasFactory;\n\n";
        $content .= "    protected \$fillable = [\n";
        
        foreach ($fillable as $field) {
            $content .= "        '{$field}',\n";
        }
        
        $content .= "    ];\n";

        // Add relationships
        foreach ($relationships as $relationship) {
            $methodName = $relationship['name'];
            $type = $relationship['type'];
            $relatedModel = $relationship['model'] ?? Str::studly($methodName);
            
            $content .= "\n    public function {$methodName}()\n    {\n";
            $content .= "        return \$this->{$type}({$relatedModel}::class);\n";
            $content .= "    }\n";
        }

        $content .= "}\n";

        return $content;
    }

    /**
     * Create a mock model file for testing.
     */
    public function createMockModel(string $directory, string $modelName, array $options = []): string
    {
        $content = $this->generateMockModelContent($modelName, $options);
        $filePath = $directory . '/' . $modelName . '.php';
        
        $this->filesystem->put($filePath, $content);
        
        return $filePath;
    }

    /**
     * Assert that a file contains valid Laravel controller structure.
     */
    public function assertValidLaravelController(string $content): bool
    {
        $checks = [
            'Has PHP opening tag' => str_starts_with($content, '<?php'),
            'Has namespace declaration' => str_contains($content, 'namespace '),
            'Extends Controller' => str_contains($content, 'extends Controller'),
            'Has class declaration' => preg_match('/class\s+\w+Controller/', $content),
        ];

        foreach ($checks as $check => $passed) {
            if (!$passed) {
                throw new \Exception("Controller validation failed: {$check}");
            }
        }

        return true;
    }

    /**
     * Get expected CRUD methods for a resource controller.
     */
    public function getExpectedCrudMethods(): array
    {
        return ['index', 'create', 'store', 'show', 'edit', 'update', 'destroy'];
    }

    /**
     * Get expected API methods for an API controller.
     */
    public function getExpectedApiMethods(): array
    {
        return ['index', 'store', 'show', 'update', 'destroy'];
    }

    /**
     * Assert that controller has proper API response structure.
     */
    public function assertApiControllerStructure(string $content): bool
    {
        $expectations = [
            'Returns JSON responses' => str_contains($content, 'return ') && 
                                     (str_contains($content, '->json(') || 
                                      str_contains($content, 'response()->json(') ||
                                      str_contains($content, 'JsonResponse')),
            'Has proper HTTP status codes' => str_contains($content, '201') || 
                                            str_contains($content, '204') ||
                                            str_contains($content, 'noContent()'),
        ];

        foreach ($expectations as $check => $passed) {
            if (!$passed) {
                throw new \Exception("API controller validation failed: {$check}");
            }
        }

        return true;
    }

    /**
     * Assert that web controller has proper view returns.
     */
    public function assertWebControllerStructure(string $content): bool
    {
        $expectations = [
            'Returns views' => str_contains($content, 'return view('),
            'Has proper view names' => preg_match('/view\([\'"][^\'"]+(index|create|show|edit)[\'"]/', $content),
        ];

        foreach ($expectations as $check => $passed) {
            if (!$passed) {
                throw new \Exception("Web controller validation failed: {$check}");
            }
        }

        return true;
    }

    /**
     * Create a test stub file.
     */
    public function createTestStub(string $directory, string $stubName, string $content): string
    {
        $stubPath = $directory . '/' . $stubName . '.stub';
        $this->filesystem->put($stubPath, $content);
        
        return $stubPath;
    }

    /**
     * Get sample validation rules for testing.
     */
    public function getSampleValidationRules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'category_id' => ['required', 'exists:categories,id'],
        ];
    }

    /**
     * Format validation rules as PHP array string.
     */
    public function formatValidationRules(array $rules): string
    {
        $formatted = "[\n";
        
        foreach ($rules as $field => $fieldRules) {
            $rulesString = "'" . implode("', '", $fieldRules) . "'";
            $formatted .= "            '{$field}' => [{$rulesString}],\n";
        }
        
        $formatted .= "        ]";
        
        return $formatted;
    }
}