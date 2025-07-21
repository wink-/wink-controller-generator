<?php

namespace Wink\ControllerGenerator\Tests\Helpers;

use Illuminate\Filesystem\Filesystem;

class StubTestHelper
{
    protected Filesystem $filesystem;

    public function __construct(Filesystem $filesystem = null)
    {
        $this->filesystem = $filesystem ?: new Filesystem();
    }

    /**
     * Get sample API controller stub content.
     */
    public function getApiControllerStub(): string
    {
        return '<?php

namespace {{ namespace }};

use App\Http\Controllers\Controller;
use App\Models\{{ model }};
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class {{ class }} extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        ${{ modelVariable }}s = {{ model }}::all();
        
        return response()->json([
            "data" => ${{ modelVariable }}s,
            "message" => "{{ model }}s retrieved successfully"
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $validatedData = $request->validate([
{{ validationRules }}
        ]);

        ${{ modelVariable }} = {{ model }}::create($validatedData);

        return response()->json([
            "data" => ${{ modelVariable }},
            "message" => "{{ model }} created successfully"
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show({{ model }} ${{ modelVariable }}): JsonResponse
    {
        return response()->json([
            "data" => ${{ modelVariable }},
            "message" => "{{ model }} retrieved successfully"
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, {{ model }} ${{ modelVariable }}): JsonResponse
    {
        $validatedData = $request->validate([
{{ validationRules }}
        ]);

        ${{ modelVariable }}->update($validatedData);

        return response()->json([
            "data" => ${{ modelVariable }},
            "message" => "{{ model }} updated successfully"
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy({{ model }} ${{ modelVariable }}): JsonResponse
    {
        ${{ modelVariable }}->delete();

        return response()->json([
            "message" => "{{ model }} deleted successfully"
        ], 204);
    }
}';
    }

    /**
     * Get sample web controller stub content.
     */
    public function getWebControllerStub(): string
    {
        return '<?php

namespace {{ namespace }};

use App\Http\Controllers\Controller;
use App\Models\{{ model }};
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class {{ class }} extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        ${{ modelVariable }}s = {{ model }}::paginate(15);
        
        return view("{{ viewPrefix }}.index", compact("{{ modelVariable }}s"));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view("{{ viewPrefix }}.create");
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validatedData = $request->validate([
{{ validationRules }}
        ]);

        ${{ modelVariable }} = {{ model }}::create($validatedData);

        return redirect()
            ->route("{{ routePrefix }}.show", ${{ modelVariable }})
            ->with("success", "{{ model }} created successfully");
    }

    /**
     * Display the specified resource.
     */
    public function show({{ model }} ${{ modelVariable }}): View
    {
        return view("{{ viewPrefix }}.show", compact("{{ modelVariable }}"));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit({{ model }} ${{ modelVariable }}): View
    {
        return view("{{ viewPrefix }}.edit", compact("{{ modelVariable }}"));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, {{ model }} ${{ modelVariable }}): RedirectResponse
    {
        $validatedData = $request->validate([
{{ validationRules }}
        ]);

        ${{ modelVariable }}->update($validatedData);

        return redirect()
            ->route("{{ routePrefix }}.show", ${{ modelVariable }})
            ->with("success", "{{ model }} updated successfully");
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy({{ model }} ${{ modelVariable }}): RedirectResponse
    {
        ${{ modelVariable }}->delete();

        return redirect()
            ->route("{{ routePrefix }}.index")
            ->with("success", "{{ model }} deleted successfully");
    }
}';
    }

    /**
     * Get sample form request stub content.
     */
    public function getFormRequestStub(): string
    {
        return '<?php

namespace {{ namespace }};

use Illuminate\Foundation\Http\FormRequest;

class {{ class }} extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
{{ validationRules }}
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
{{ validationMessages }}
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
{{ validationAttributes }}
        ];
    }
}';
    }

    /**
     * Get sample API resource stub content.
     */
    public function getApiResourceStub(): string
    {
        return '<?php

namespace {{ namespace }};

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class {{ class }} extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
{{ resourceFields }}
        ];
    }

    /**
     * Get additional data that should be returned with the resource array.
     *
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        return [
            "meta" => [
                "version" => "1.0",
                "api_documentation" => url("/api/docs")
            ]
        ];
    }
}';
    }

    /**
     * Get sample test stub content.
     */
    public function getControllerTestStub(): string
    {
        return '<?php

namespace {{ namespace }};

use App\Models\{{ model }};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class {{ class }} extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_list_{{ modelVariable }}s()
    {
        ${{ modelVariable }}s = {{ model }}::factory()->count(3)->create();

        $response = $this->getJson("{{ routePrefix }}");

        $response->assertOk()
                ->assertJsonCount(3, "data");
    }

    /** @test */
    public function it_can_create_a_{{ modelVariable }}()
    {
        ${{ modelVariable }}Data = {{ model }}::factory()->make()->toArray();

        $response = $this->postJson("{{ routePrefix }}", ${{ modelVariable }}Data);

        $response->assertCreated()
                ->assertJsonFragment(${{ modelVariable }}Data);

        $this->assertDatabaseHas("{{ table }}", ${{ modelVariable }}Data);
    }

    /** @test */
    public function it_can_show_a_{{ modelVariable }}()
    {
        ${{ modelVariable }} = {{ model }}::factory()->create();

        $response = $this->getJson("{{ routePrefix }}/{${{ modelVariable }}->id}");

        $response->assertOk()
                ->assertJsonFragment([
                    "id" => ${{ modelVariable }}->id
                ]);
    }

    /** @test */
    public function it_can_update_a_{{ modelVariable }}()
    {
        ${{ modelVariable }} = {{ model }}::factory()->create();
        $updateData = {{ model }}::factory()->make()->toArray();

        $response = $this->putJson("{{ routePrefix }}/{${{ modelVariable }}->id}", $updateData);

        $response->assertOk()
                ->assertJsonFragment($updateData);

        $this->assertDatabaseHas("{{ table }}", $updateData);
    }

    /** @test */
    public function it_can_delete_a_{{ modelVariable }}()
    {
        ${{ modelVariable }} = {{ model }}::factory()->create();

        $response = $this->deleteJson("{{ routePrefix }}/{${{ modelVariable }}->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing("{{ table }}", [
            "id" => ${{ modelVariable }}->id
        ]);
    }

    /** @test */
    public function it_validates_required_fields_when_creating()
    {
        $response = $this->postJson("{{ routePrefix }}", []);

        $response->assertUnprocessable()
                ->assertJsonValidationErrors([
{{ requiredFields }}
                ]);
    }

    /** @test */
    public function it_validates_required_fields_when_updating()
    {
        ${{ modelVariable }} = {{ model }}::factory()->create();

        $response = $this->putJson("{{ routePrefix }}/{${{ modelVariable }}->id}", []);

        $response->assertUnprocessable()
                ->assertJsonValidationErrors([
{{ requiredFields }}
                ]);
    }
}';
    }

    /**
     * Create a temporary stub file for testing.
     */
    public function createTempStub(string $stubName, string $content): string
    {
        $tempDir = sys_get_temp_dir() . '/wink-stubs-test';
        
        if (!$this->filesystem->exists($tempDir)) {
            $this->filesystem->makeDirectory($tempDir, 0755, true);
        }
        
        $stubPath = $tempDir . '/' . $stubName . '.stub';
        $this->filesystem->put($stubPath, $content);
        
        return $stubPath;
    }

    /**
     * Clean up temporary stub files.
     */
    public function cleanupTempStubs(): void
    {
        $tempDir = sys_get_temp_dir() . '/wink-stubs-test';
        
        if ($this->filesystem->exists($tempDir)) {
            $this->filesystem->deleteDirectory($tempDir);
        }
    }

    /**
     * Process stub template with variables.
     */
    public function processStub(string $stubContent, array $variables): string
    {
        $processed = $stubContent;
        
        foreach ($variables as $key => $value) {
            $placeholder = '{{ ' . $key . ' }}';
            $processed = str_replace($placeholder, $value, $processed);
        }
        
        return $processed;
    }

    /**
     * Get sample template variables for testing.
     */
    public function getSampleTemplateVariables(): array
    {
        return [
            'namespace' => 'App\\Http\\Controllers\\Api',
            'class' => 'UserController',
            'model' => 'User',
            'modelVariable' => 'user',
            'table' => 'users',
            'viewPrefix' => 'users',
            'routePrefix' => '/api/users',
            'validationRules' => $this->formatValidationRules([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'unique:users,email'],
            ]),
            'validationMessages' => $this->formatValidationMessages([
                'name.required' => 'The name field is required.',
                'email.unique' => 'The email has already been taken.',
            ]),
            'validationAttributes' => $this->formatValidationAttributes([
                'name' => 'full name',
                'email' => 'email address',
            ]),
            'resourceFields' => $this->formatResourceFields([
                'id' => '$this->id',
                'name' => '$this->name',
                'email' => '$this->email',
                'created_at' => '$this->created_at',
                'updated_at' => '$this->updated_at',
            ]),
            'requiredFields' => $this->formatRequiredFields(['name', 'email']),
        ];
    }

    /**
     * Format validation rules for stub template.
     */
    protected function formatValidationRules(array $rules): string
    {
        $formatted = '';
        
        foreach ($rules as $field => $fieldRules) {
            $rulesString = "'" . implode("', '", $fieldRules) . "'";
            $formatted .= "            '{$field}' => [{$rulesString}],\n";
        }
        
        return rtrim($formatted);
    }

    /**
     * Format validation messages for stub template.
     */
    protected function formatValidationMessages(array $messages): string
    {
        $formatted = '';
        
        foreach ($messages as $key => $message) {
            $formatted .= "            '{$key}' => '{$message}',\n";
        }
        
        return rtrim($formatted);
    }

    /**
     * Format validation attributes for stub template.
     */
    protected function formatValidationAttributes(array $attributes): string
    {
        $formatted = '';
        
        foreach ($attributes as $key => $attribute) {
            $formatted .= "            '{$key}' => '{$attribute}',\n";
        }
        
        return rtrim($formatted);
    }

    /**
     * Format resource fields for stub template.
     */
    protected function formatResourceFields(array $fields): string
    {
        $formatted = '';
        
        foreach ($fields as $key => $value) {
            $formatted .= "            '{$key}' => {$value},\n";
        }
        
        return rtrim($formatted);
    }

    /**
     * Format required fields for test stub.
     */
    protected function formatRequiredFields(array $fields): string
    {
        $formatted = '';
        
        foreach ($fields as $field) {
            $formatted .= "                    '{$field}',\n";
        }
        
        return rtrim($formatted);
    }

    /**
     * Validate stub syntax.
     */
    public function validateStubSyntax(string $stubContent, array $variables): bool
    {
        $processed = $this->processStub($stubContent, $variables);
        
        // Check for unprocessed placeholders
        if (preg_match('/\{\{\s*\w+\s*\}\}/', $processed)) {
            return false;
        }
        
        // Basic PHP syntax validation
        $tempFile = tempnam(sys_get_temp_dir(), 'stub_validation');
        file_put_contents($tempFile, $processed);
        
        $output = [];
        $returnCode = 0;
        exec("php -l {$tempFile} 2>&1", $output, $returnCode);
        
        unlink($tempFile);
        
        return $returnCode === 0;
    }

    /**
     * Extract placeholders from stub content.
     */
    public function extractPlaceholders(string $stubContent): array
    {
        preg_match_all('/\{\{\s*(\w+)\s*\}\}/', $stubContent, $matches);
        
        return array_unique($matches[1]);
    }

    /**
     * Verify all placeholders are provided in variables.
     */
    public function verifyPlaceholders(string $stubContent, array $variables): array
    {
        $placeholders = $this->extractPlaceholders($stubContent);
        $missing = [];
        
        foreach ($placeholders as $placeholder) {
            if (!array_key_exists($placeholder, $variables)) {
                $missing[] = $placeholder;
            }
        }
        
        return $missing;
    }
}