<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Settings
    |--------------------------------------------------------------------------
    |
    | These settings define the default behavior when generating controllers.
    | You can override these on a per-command basis using command options.
    |
    */
    'defaults' => [
        /*
        |--------------------------------------------------------------------------
        | Default Namespace
        |--------------------------------------------------------------------------
        |
        | The default namespace for generated controllers. This will be used
        | unless overridden by the --namespace option.
        |
        */
        'namespace' => 'App\\Http\\Controllers',

        /*
        |--------------------------------------------------------------------------
        | API Controllers Namespace
        |--------------------------------------------------------------------------
        |
        | The default namespace for API controllers. API controllers are typically
        | organized separately from web controllers.
        |
        */
        'api_namespace' => 'App\\Http\\Controllers\\Api',

        /*
        |--------------------------------------------------------------------------
        | Default Middleware
        |--------------------------------------------------------------------------
        |
        | The default middleware to apply to web controllers. These will be
        | included in the constructor unless overridden.
        |
        */
        'middleware' => ['web'],

        /*
        |--------------------------------------------------------------------------
        | API Middleware
        |--------------------------------------------------------------------------
        |
        | The default middleware to apply to API controllers. Rate limiting
        | and authentication are commonly used for API endpoints.
        |
        */
        'api_middleware' => ['api', 'throttle:60,1'],

        /*
        |--------------------------------------------------------------------------
        | Base Controller Class
        |--------------------------------------------------------------------------
        |
        | The base controller class that generated controllers should extend.
        | This should typically be your application's base controller.
        |
        */
        'base_controller' => 'App\\Http\\Controllers\\Controller',
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Toggles
    |--------------------------------------------------------------------------
    |
    | These settings control which additional files and features are generated
    | alongside your controllers.
    |
    */
    'features' => [
        /*
        |--------------------------------------------------------------------------
        | Generate Form Request Classes
        |--------------------------------------------------------------------------
        |
        | When enabled, the generator will create FormRequest classes for
        | store and update operations with validation rules.
        |
        */
        'generate_form_requests' => true,

        /*
        |--------------------------------------------------------------------------
        | Generate API Resource Classes
        |--------------------------------------------------------------------------
        |
        | When enabled, the generator will create API Resource classes for
        | transforming model data in API responses.
        |
        */
        'generate_api_resources' => true,

        /*
        |--------------------------------------------------------------------------
        | Include Authorization
        |--------------------------------------------------------------------------
        |
        | When enabled, generated controllers will include authorization
        | checks using Laravel's Gate system or model policies.
        |
        */
        'include_authorization' => true,

        /*
        |--------------------------------------------------------------------------
        | Include Swagger/OpenAPI Documentation
        |--------------------------------------------------------------------------
        |
        | When enabled, controllers will include OpenAPI 3.0 annotations
        | for automatic API documentation generation.
        |
        */
        'include_swagger_docs' => true,

        /*
        |--------------------------------------------------------------------------
        | Generate Route Definitions
        |--------------------------------------------------------------------------
        |
        | When enabled, the generator will create route definitions and
        | optionally register them in your route files.
        |
        */
        'generate_routes' => true,

        /*
        |--------------------------------------------------------------------------
        | Generate Controller Tests
        |--------------------------------------------------------------------------
        |
        | When enabled, the generator will create feature tests for the
        | generated controllers with basic CRUD test scenarios.
        |
        */
        'generate_tests' => false,

        /*
        |--------------------------------------------------------------------------
        | Include Soft Delete Support
        |--------------------------------------------------------------------------
        |
        | When enabled and the model uses soft deletes, additional methods
        | will be generated for handling trashed records.
        |
        */
        'soft_delete_support' => true,

        /*
        |--------------------------------------------------------------------------
        | Include Search and Filtering
        |--------------------------------------------------------------------------
        |
        | When enabled, index methods will include query parameter handling
        | for searching and filtering records.
        |
        */
        'include_search_filtering' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Template Configuration
    |--------------------------------------------------------------------------
    |
    | These settings control how the stub templates are located and used
    | during controller generation.
    |
    */
    'templates' => [
        /*
        |--------------------------------------------------------------------------
        | Template Path
        |--------------------------------------------------------------------------
        |
        | The path where custom stub templates are stored. This is relative
        | to the Laravel resources directory.
        |
        */
        'path' => 'stubs/wink/controllers',

        /*
        |--------------------------------------------------------------------------
        | Allow Custom Stubs
        |--------------------------------------------------------------------------
        |
        | When enabled, the generator will look for custom stub templates
        | in the specified path before falling back to default templates.
        |
        */
        'custom_stubs' => true,

        /*
        |--------------------------------------------------------------------------
        | Template Files
        |--------------------------------------------------------------------------
        |
        | The names of the stub template files for different controller types.
        | These can be customized if you prefer different naming conventions.
        |
        */
        'stubs' => [
            'api_controller' => 'api-controller.stub',
            'web_controller' => 'web-controller.stub',
            'resource_controller' => 'resource-controller.stub',
            'form_request' => 'form-request.stub',
            'api_resource' => 'api-resource.stub',
            'test' => 'controller-test.stub',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Response Format Configuration
    |--------------------------------------------------------------------------
    |
    | These settings control how responses are formatted in generated
    | controllers.
    |
    */
    'response_formats' => [
        /*
        |--------------------------------------------------------------------------
        | API Response Format
        |--------------------------------------------------------------------------
        |
        | The default response format for API controllers. Typically 'json'
        | but could be customized for other formats.
        |
        */
        'api' => 'json',

        /*
        |--------------------------------------------------------------------------
        | Web Response Format
        |--------------------------------------------------------------------------
        |
        | The default response format for web controllers. Typically 'view'
        | for rendering Blade templates.
        |
        */
        'web' => 'view',

        /*
        |--------------------------------------------------------------------------
        | Pagination Limit
        |--------------------------------------------------------------------------
        |
        | The default number of records to return per page in paginated
        | responses. Can be overridden by query parameters.
        |
        */
        'pagination_limit' => 50,

        /*
        |--------------------------------------------------------------------------
        | Success Status Codes
        |--------------------------------------------------------------------------
        |
        | HTTP status codes to use for successful operations.
        |
        */
        'status_codes' => [
            'index' => 200,
            'store' => 201,
            'show' => 200,
            'update' => 200,
            'destroy' => 204,
        ],

        /*
        |--------------------------------------------------------------------------
        | Response Headers
        |--------------------------------------------------------------------------
        |
        | Default headers to include in API responses.
        |
        */
        'headers' => [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for generating validation rules in FormRequest classes.
    |
    */
    'validation' => [
        /*
        |--------------------------------------------------------------------------
        | Auto-generate Rules from Database
        |--------------------------------------------------------------------------
        |
        | When enabled, validation rules will be automatically generated
        | based on database column constraints and types.
        |
        */
        'auto_generate_rules' => true,

        /*
        |--------------------------------------------------------------------------
        | Include Custom Messages
        |--------------------------------------------------------------------------
        |
        | When enabled, FormRequest classes will include custom validation
        | messages for better user experience.
        |
        */
        'include_custom_messages' => true,

        /*
        |--------------------------------------------------------------------------
        | Strict Mode
        |--------------------------------------------------------------------------
        |
        | When enabled, only fillable attributes will be included in
        | validation rules, providing additional security.
        |
        */
        'strict_mode' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | Security-related settings for generated controllers.
    |
    */
    'security' => [
        /*
        |--------------------------------------------------------------------------
        | Mass Assignment Protection
        |--------------------------------------------------------------------------
        |
        | When enabled, only validated and fillable attributes will be
        | used in create/update operations.
        |
        */
        'mass_assignment_protection' => true,

        /*
        |--------------------------------------------------------------------------
        | CSRF Protection
        |--------------------------------------------------------------------------
        |
        | When enabled, web controllers will include CSRF protection
        | for state-changing operations.
        |
        */
        'csrf_protection' => true,

        /*
        |--------------------------------------------------------------------------
        | Rate Limiting
        |--------------------------------------------------------------------------
        |
        | Default rate limiting configuration for API endpoints.
        |
        */
        'rate_limiting' => [
            'enabled' => true,
            'requests_per_minute' => 60,
            'by_user' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Configuration
    |--------------------------------------------------------------------------
    |
    | Settings to optimize performance of generated controllers.
    |
    */
    'performance' => [
        /*
        |--------------------------------------------------------------------------
        | Eager Loading
        |--------------------------------------------------------------------------
        |
        | When enabled, commonly accessed relationships will be eager loaded
        | to prevent N+1 query problems.
        |
        */
        'auto_eager_load' => true,

        /*
        |--------------------------------------------------------------------------
        | Query Optimization
        |--------------------------------------------------------------------------
        |
        | When enabled, queries will be optimized with select statements
        | and other performance improvements.
        |
        */
        'optimize_queries' => true,

        /*
        |--------------------------------------------------------------------------
        | Response Caching
        |--------------------------------------------------------------------------
        |
        | When enabled, appropriate cache headers and caching logic
        | will be included in generated controllers.
        |
        */
        'response_caching' => false,
    ],
];