# Wink Controller Generator - Commands Summary

## Overview

The Wink Controller Generator provides three specialized Artisan commands for generating Laravel controllers with different purposes and features. Each command follows Laravel conventions and provides comprehensive options for customization.

## Commands Structure

### 1. Main Command: `wink:generate-controllers`

**Purpose**: Universal controller generator that can create any type of controller

**Signature**:
```bash
php artisan wink:generate-controllers {table?}
    {--type=api}              # Controller type: api|web|resource
    {--model=}                # Specify model class
    {--namespace=}            # Override default namespace
    {--middleware=}           # Custom middleware (comma-separated)
    {--with-requests}         # Generate FormRequest classes
    {--with-resources}        # Generate API Resource classes
    {--with-tests}            # Generate controller tests
    {--force}                 # Overwrite existing files
    {--dry-run}               # Preview without creating files
```

**Features**:
- ✅ Universal controller generation
- ✅ Bulk generation for all tables/models
- ✅ Interactive model discovery
- ✅ Comprehensive validation
- ✅ Progress tracking with progress bars
- ✅ Detailed generation plans and summaries
- ✅ Smart model class detection

**Examples**:
```bash
# Generate API controller for users table
php artisan wink:generate-controllers users --type=api --with-resources

# Generate web controller with custom namespace
php artisan wink:generate-controllers posts --type=web --namespace="App\Http\Controllers\Admin"

# Bulk generate controllers for all models
php artisan wink:generate-controllers --type=api --with-requests --with-tests

# Dry run to preview what will be generated
php artisan wink:generate-controllers products --dry-run
```

### 2. API Command: `wink:controllers:api`

**Purpose**: Specialized for RESTful JSON API controllers

**Signature**:
```bash
php artisan wink:controllers:api {table}
    {--model=}                # Specify model class
    {--namespace=}            # Override default API namespace
    {--middleware=}           # Custom middleware (comma-separated)
    {--with-resources}        # Generate API Resource classes
    {--with-requests}         # Generate FormRequest classes
    {--with-tests}            # Generate controller tests
    {--with-swagger}          # Include OpenAPI documentation
    {--rate-limit=60}         # API rate limit per minute
    {--pagination=15}         # Default pagination limit
    {--force}                 # Overwrite existing files
    {--dry-run}               # Preview without creating files
```

**Features**:
- 🚀 API-specific optimizations
- 📊 Rate limiting configuration
- 📄 OpenAPI/Swagger documentation
- 🔄 API Resource transformation
- 📝 JSON response formatting
- 🔒 CORS handling
- 📈 Pagination support

**Generated Endpoints**:
- `GET /api/{resource}` - List all items
- `POST /api/{resource}` - Create new item
- `GET /api/{resource}/{id}` - Show specific item
- `PUT/PATCH /api/{resource}/{id}` - Update item
- `DELETE /api/{resource}/{id}` - Delete item

**Examples**:
```bash
# Generate API controller with resources and swagger docs
php artisan wink:controllers:api users --with-resources --with-swagger

# API controller with custom rate limiting and pagination
php artisan wink:controllers:api products --rate-limit=120 --pagination=25

# Generate with all features enabled
php artisan wink:controllers:api orders --with-resources --with-requests --with-tests --with-swagger
```

### 3. Web Command: `wink:controllers:web`

**Purpose**: Specialized for traditional web applications with view rendering

**Signature**:
```bash
php artisan wink:controllers:web {table}
    {--model=}                # Specify model class
    {--namespace=}            # Override default namespace
    {--middleware=}           # Custom middleware (comma-separated)
    {--with-requests}         # Generate FormRequest classes
    {--with-views}            # Generate Blade view templates
    {--with-tests}            # Generate controller tests
    {--auth}                  # Include authentication middleware
    {--layout=app}            # Default layout for views
    {--route-prefix=}         # Custom route prefix
    {--force}                 # Overwrite existing files
    {--dry-run}               # Preview without creating files
```

**Features**:
- 🌐 Web-specific optimizations
- 👁️ Blade template generation
- 📝 Form handling with CSRF protection
- 🔐 Authentication integration
- 💬 Flash message support
- 🎨 Layout system integration
- 📱 Responsive form generation

**Generated Views**:
- `index.blade.php` - List view with pagination
- `create.blade.php` - Create form
- `show.blade.php` - Detail view
- `edit.blade.php` - Edit form
- `_form.blade.php` - Shared form partial

**Examples**:
```bash
# Generate web controller with views and authentication
php artisan wink:controllers:web users --with-views --auth

# Web controller with custom layout and route prefix
php artisan wink:controllers:web admin/posts --layout=admin --route-prefix=admin/posts

# Generate with form requests and tests
php artisan wink:controllers:web products --with-requests --with-tests --with-views
```

## Common Options Explained

### Global Options (Available in All Commands)

| Option | Description | Example |
|--------|-------------|---------|
| `--model=` | Specify exact model class | `--model="App\Models\BlogPost"` |
| `--namespace=` | Override default namespace | `--namespace="App\Http\Controllers\Api\V2"` |
| `--middleware=` | Custom middleware list | `--middleware="auth,verified,admin"` |
| `--with-requests` | Generate FormRequest classes | Enables validation classes |
| `--with-tests` | Generate controller tests | Creates feature tests |
| `--force` | Overwrite existing files | Skips confirmation prompts |
| `--dry-run` | Preview without creating | Shows what would be generated |

### API-Specific Options

| Option | Description | Default | Example |
|--------|-------------|---------|---------|
| `--with-resources` | Generate API Resource classes | false | Creates transformation classes |
| `--with-swagger` | Include OpenAPI documentation | false | Adds @OA annotations |
| `--rate-limit=` | Requests per minute | 60 | `--rate-limit=120` |
| `--pagination=` | Items per page | 15 | `--pagination=25` |

### Web-Specific Options

| Option | Description | Default | Example |
|--------|-------------|---------|---------|
| `--with-views` | Generate Blade templates | false | Creates view files |
| `--auth` | Include auth middleware | false | Adds authentication |
| `--layout=` | Default layout name | app | `--layout=admin` |
| `--route-prefix=` | Custom route prefix | table name | `--route-prefix=admin/users` |

## Generated File Structure

### API Controller Generation
```
app/Http/Controllers/Api/
├── UserController.php          # RESTful API controller
app/Http/Resources/
├── UserResource.php            # API resource transformation
app/Http/Requests/
├── StoreUserRequest.php        # Store validation
├── UpdateUserRequest.php       # Update validation
tests/Feature/
├── UserControllerTest.php      # Feature tests
```

### Web Controller Generation
```
app/Http/Controllers/
├── UserController.php          # Web controller
app/Http/Requests/
├── StoreUserRequest.php        # Store validation
├── UpdateUserRequest.php       # Update validation
resources/views/users/
├── index.blade.php             # List view
├── create.blade.php            # Create form
├── show.blade.php              # Detail view
├── edit.blade.php              # Edit form
├── _form.blade.php             # Form partial
tests/Feature/
├── UserControllerTest.php      # Feature tests
```

## Integration Features

### Model Analysis
- 🔍 Automatic model discovery
- 🔗 Relationship detection for eager loading
- 📋 Fillable attribute analysis for forms
- ✅ Model existence validation

### Validation Integration
- 📝 Database constraint-based rules
- 🔒 Authorization integration
- 💬 Custom validation messages
- 🛡️ Input sanitization

### Route Integration
- 🛣️ Automatic route suggestions
- 📡 RESTful route patterns
- 🔗 Resource route naming
- 📍 Custom prefix support

### Testing Integration
- 🧪 Feature test generation
- 🔐 Authentication scenarios
- 💾 Database transaction handling
- 🎭 Mock external dependencies

## Command Workflow

### 1. Input Validation
- Validates controller type
- Checks namespace format
- Verifies model existence
- Validates custom options

### 2. Model Analysis
- Discovers model relationships
- Analyzes fillable attributes
- Detects validation requirements
- Checks for existing files

### 3. Generation Planning
- Displays comprehensive plan
- Shows file structure
- Lists endpoints/routes
- Confirms user intentions

### 4. File Generation
- Creates controller files
- Generates supporting classes
- Creates view templates (web)
- Generates test files

### 5. Results Display
- Shows generated files
- Provides next steps
- Lists warnings/issues
- Suggests improvements

## Error Handling and Validation

### Input Validation
- ❌ Invalid controller types
- ❌ Malformed namespaces
- ❌ Invalid rate limits
- ❌ Missing models

### File Validation
- ⚠️ Existing file warnings
- 🔄 Force overwrite options
- 📁 Directory creation
- 🔒 Permission checks

### Progress Feedback
- 📊 Progress bars for bulk operations
- ✅ Success indicators
- ⚠️ Warning messages
- ❌ Error reporting

## Best Practices

### Command Usage
1. **Start with dry-run**: Always use `--dry-run` first
2. **Use specific commands**: Prefer specialized commands for focused needs
3. **Validate models first**: Ensure models exist before generation
4. **Review generated code**: Always review before committing

### Options Selection
1. **API Controllers**: Always use `--with-resources` for consistency
2. **Web Controllers**: Use `--with-views` for complete scaffolding
3. **Testing**: Always include `--with-tests` for quality
4. **Documentation**: Use `--with-swagger` for API documentation

### File Organization
1. **Namespace conventions**: Follow Laravel standards
2. **Directory structure**: Keep organized by type
3. **Naming patterns**: Use consistent naming
4. **Code reviews**: Review generated code before deployment

This command structure provides a comprehensive solution for Laravel controller generation with enterprise-grade features and extensive customization options.