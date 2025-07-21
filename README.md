# Wink Controller Generator

Generate production-ready Laravel controllers from database schemas and existing models, focusing on enterprise legacy database modernization with consistent, maintainable code patterns.

## Features

- **Multiple Controller Types**: API, Web, and Resource controllers
- **Enterprise Ready**: Built for legacy database modernization
- **Security First**: CSRF protection, input sanitization, and authorization integration
- **Laravel Standards**: PSR-12 compliant, follows Laravel conventions
- **Comprehensive**: Includes FormRequests, API Resources, and validation
- **Customizable**: Template-based generation with custom stubs support

## Requirements

- PHP 8.1 or higher
- Laravel 10.0 or higher

## Installation

Install the package via Composer:

```bash
composer require wink/controller-generator
```

Publish the configuration file:

```bash
php artisan vendor:publish --tag=wink-controllers-config
```

Optionally, publish the stub templates for customization:

```bash
php artisan vendor:publish --tag=wink-controllers-stubs
```

## Quick Start

Generate an API controller for a User model:

```bash
php artisan wink:generate-controllers users --type=api --with-requests --with-resources
```

Generate a web controller with views integration:

```bash
php artisan wink:controllers:web posts --middleware=auth
```

Generate resource controllers (hybrid API/Web):

```bash
php artisan wink:controllers:resource products --with-requests
```

## Controller Types

### API Controllers
RESTful JSON API endpoints with:
- Standard HTTP status codes (200, 201, 404, 422, 500)
- JSON response formatting
- API resource transformation
- Pagination support
- Rate limiting integration

### Web Controllers
Traditional web applications with:
- View rendering with data passing
- Flash messages and session handling
- Form validation integration
- Redirect responses
- Blade template integration

### Resource Controllers
Hybrid controllers supporting both API and web with:
- Content negotiation (Accept header detection)
- Dual response formats (JSON/HTML)
- Shared validation logic
- Consistent error handling

## Generated Code Features

- **FormRequest Integration**: Automatic validation class generation
- **API Resources**: Laravel API Resource integration
- **Authorization**: Policy integration and middleware setup
- **Documentation**: OpenAPI/Swagger annotations
- **Security**: Input sanitization and CSRF protection
- **Testing**: Feature test generation (coming soon)

## Configuration

The package can be configured via the `config/wink-controllers.php` file:

```php
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
    // ... more configuration options
];
```

## Commands

### Main Command

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

### Specialized Commands

```bash
# Generate API controllers only
php artisan wink:controllers:api users --with-resources --with-tests

# Generate web controllers with views integration
php artisan wink:controllers:web posts --middleware=auth

# Generate resource controllers (hybrid)
php artisan wink:controllers:resource products --with-requests
```

## Testing

Run the package tests:

```bash
composer test
```

Run tests with coverage:

```bash
composer test-coverage
```

Run static analysis:

```bash
composer analyse
```

Format code:

```bash
composer format
```

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## Security

If you discover any security-related issues, please email security@wink.dev instead of using the issue tracker.

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

## Credits

- [Wink Team](https://wink.dev)
- [All Contributors](../../contributors)

## Support

- Documentation: [https://docs.wink.dev/controller-generator](https://docs.wink.dev/controller-generator)
- Issues: [GitHub Issues](https://github.com/wink-dev/controller-generator/issues)
- Discussions: [GitHub Discussions](https://github.com/wink-dev/controller-generator/discussions)