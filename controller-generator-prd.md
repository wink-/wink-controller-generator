# Product Requirements Document: Wink Controller Generator

## 1. Product Overview

### 1.1 Product Name
`wink/controller-generator`

### 1.2 Purpose
Generate production-ready Laravel controllers from database schemas and existing models, focusing on enterprise legacy database modernization with consistent, maintainable code patterns.

### 1.3 Target Users
- Laravel developers working with legacy databases
- Enterprise teams modernizing existing systems
- Agencies building APIs and web applications quickly
- Solo developers scaffolding CRUD operations

### 1.4 Success Metrics
- 80% reduction in controller scaffolding time
- 100% PSR-12 compliant generated code
- Zero security vulnerabilities in generated code
- Support for Laravel 10+ features

## 2. Core Requirements

### 2.1 Controller Types

#### 2.1.1 API Controllers
**Purpose**: RESTful JSON API endpoints
**Features**:
- Standard HTTP status codes (200, 201, 404, 422, 500)
- JSON response formatting
- API resource transformation
- Pagination support
- Rate limiting integration
- CORS handling

**Generated Methods**:
```php
public function index(Request $request): JsonResponse
public function store(StoreUserRequest $request): JsonResponse  
public function show(User $user): JsonResponse
public function update(UpdateUserRequest $request, User $user): JsonResponse
public function destroy(User $user): JsonResponse
```

#### 2.1.2 Web Controllers
**Purpose**: Traditional web applications with view rendering
**Features**:
- View rendering with data passing
- Flash messages and session handling
- Form validation integration
- Redirect responses
- Blade template integration

**Generated Methods**:
```php
public function index(): View
public function create(): View
public function store(StoreUserRequest $request): RedirectResponse
public function show(User $user): View
public function edit(User $user): View
public function update(UpdateUserRequest $request, User $user): RedirectResponse
public function destroy(User $user): RedirectResponse
```

#### 2.1.3 Resource Controllers  
**Purpose**: Hybrid controllers supporting both API and web
**Features**:
- Content negotiation (Accept header detection)
- Dual response formats (JSON/HTML)
- Shared validation logic
- Consistent error handling

### 2.2 Advanced Features

#### 2.2.1 Request Validation Integration
- Automatic FormRequest class generation
- Database constraint-based validation rules
- Custom validation message support
- Authorization integration

#### 2.2.2 Response Transformation
- Laravel API Resource integration
- Custom transformer patterns
- Nested relationship handling
- Conditional field inclusion

#### 2.2.3 Filtering and Search
- Query parameter filtering
- Full-text search integration
- Relationship filtering
- Custom scope integration

#### 2.2.4 Security Features
- CSRF protection (web controllers)
- Authorization policy integration
- Input sanitization
- SQL injection prevention

## 3. Technical Specifications

### 3.1 Package Structure
```
src/
├── ControllerGeneratorServiceProvider.php
├── Commands/
│   ├── GenerateControllerCommand.php
│   ├── GenerateApiControllerCommand.php
│   └── GenerateWebControllerCommand.php
├── Generators/
│   ├── AbstractControllerGenerator.php
│   ├── ApiControllerGenerator.php
│   ├── WebControllerGenerator.php
│   └── ResourceControllerGenerator.php
├── Analyzers/
│   ├── ModelAnalyzer.php
│   ├── RouteAnalyzer.php
│   └── ValidationAnalyzer.php
├── Templates/
│   ├── api-controller.stub
│   ├── web-controller.stub
│   ├── resource-controller.stub
│   └── form-request.stub
└── Config/
    └── ControllerConfig.php
```

### 3.2 Dependencies
- `illuminate/support: ^10.0|^11.0`
- `illuminate/console: ^10.0|^11.0`
- `wink/generator-core: ^1.0` (shared foundation)
- Integration with `wink/model-generator` outputs

### 3.3 Configuration System
```php
// config/wink-controllers.php
return [
    'defaults' => [
        'namespace' => 'App\\Http\\Controllers',
        'api_namespace' => 'App\\Http\\Controllers\\Api',
        'middleware' => ['web'],
        'api_middleware' => ['api', 'throttle:60,1'],
    ],
    'features' => [
        'generate_form_requests' => true,
        'generate_api_resources' => true,
        'include_authorization' => true,
        'include_swagger_docs' => true,
    ],
    'templates' => [
        'path' => 'stubs/controllers',
        'custom_stubs' => true,
    ],
    'response_formats' => [
        'api' => 'json',
        'web' => 'view',
        'pagination_limit' => 50,
    ],
];
```

## 4. User Stories

### 4.1 As a Developer Working with Legacy Database
**Story**: I need to quickly generate API controllers for 50+ existing tables
**Acceptance Criteria**:
- Generate controllers for all tables with single command
- Respect existing model relationships
- Include proper validation based on database constraints
- Generate OpenAPI documentation comments

### 4.2 As an Enterprise Developer
**Story**: I need controllers that follow company coding standards
**Acceptance Criteria**:
- Use custom stub templates
- Include company-specific middleware
- Generate authorization policies
- Include proper logging and error handling

### 4.3 As an API Developer
**Story**: I need consistent JSON API responses across all endpoints
**Acceptance Criteria**:
- Standard response format with meta information
- Proper HTTP status codes
- Pagination metadata
- Error response consistency

## 5. Commands Specification

### 5.1 Main Command
```bash
php artisan wink:generate-controllers {table?}
    {--type=api}              # api|web|resource
    {--model=}                # Specify model class
    {--namespace=}            # Override default namespace
    {--middleware=}           # Custom middleware
    {--with-requests}         # Generate FormRequest classes
    {--with-resources}        # Generate API Resource classes
    {--with-tests}            # Generate controller tests
    {--force}                 # Overwrite existing files
    {--dry-run}               # Preview without creating files
```

### 5.2 Specialized Commands
```bash
# Generate API controllers only
php artisan wink:controllers:api users --with-resources --with-tests

# Generate web controllers with views integration
php artisan wink:controllers:web posts --middleware=auth

# Generate resource controllers (hybrid)
php artisan wink:controllers:resource products --with-requests

# Generate all controllers from schema
php artisan wink:controllers:generate-all --connection=legacy_db
```

## 6. Generated Code Examples

### 6.1 API Controller Example
```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:api', 'throttle:60,1']);
    }

    /**
     * Display a listing of users.
     *
     * @OA\Get(
     *     path="/api/users",
     *     summary="Get users list",
     *     tags={"Users"},
     *     @OA\Response(response=200, description="Success")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $users = User::query()
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%"))
            ->paginate($request->per_page ?? 15);

        return UserResource::collection($users)
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Store a newly created user.
     */
    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = User::create($request->validated());

        return (new UserResource($user))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Display the specified user.
     */
    public function show(User $user): JsonResponse
    {
        return (new UserResource($user->load(['posts', 'profile'])))
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Update the specified user.
     */
    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $user->update($request->validated());

        return (new UserResource($user->fresh()))
            ->response()
            ->setStatusCode(200);
    }

    /**
     * Remove the specified user.
     */
    public function destroy(User $user): JsonResponse
    {
        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully'
        ], 204);
    }
}
```

## 7. Integration Requirements

### 7.1 Model Generator Integration
- Read generated model information from registry
- Detect relationships for eager loading
- Use model validation rules for FormRequests
- Respect model configuration (soft deletes, timestamps)

### 7.2 Route Integration
- Generate route definitions
- Register API routes in `routes/api.php`
- Register web routes in `routes/web.php`
- Resource route naming conventions

### 7.3 Testing Integration
- Generate feature tests for each controller method
- Mock external dependencies
- Database transaction handling
- Authentication test scenarios

## 8. Quality Requirements

### 8.1 Code Quality
- PSR-12 compliance
- Laravel coding conventions
- Static analysis (PHPStan level 9)
- 100% test coverage of generated code

### 8.2 Performance
- Efficient query generation
- Pagination best practices
- N+1 query prevention
- Response caching support

### 8.3 Security
- Input validation and sanitization
- Authorization integration
- CSRF protection
- Rate limiting support

## 9. Documentation Requirements

### 9.1 API Documentation
- OpenAPI 3.0 annotation generation
- Postman collection export
- Interactive documentation (Swagger UI)
- Request/response examples

### 9.2 Code Documentation
- PHPDoc comments for all methods
- Usage examples in docblocks
- Configuration documentation
- Best practices guide

## 10. Success Criteria

### 10.1 Functional Success
- Generate working controllers for any Laravel model
- Support all major Laravel controller patterns
- Integration with existing Laravel features (policies, resources, etc.)

### 10.2 Quality Success
- Zero security vulnerabilities in generated code
- 100% PSR-12 compliance
- Passes Laravel coding standards
- Comprehensive test coverage

### 10.3 Usability Success
- Intuitive command-line interface
- Clear error messages and validation
- Comprehensive documentation
- Easy integration with existing projects

## 11. Future Enhancements

### 11.1 Phase 2 Features
- GraphQL controller generation
- Event sourcing patterns
- CQRS implementation
- Microservice patterns

### 11.2 Phase 3 Features
- Real-time controller patterns (WebSockets)
- Queue job integration
- Multi-tenant support
- Advanced caching strategies