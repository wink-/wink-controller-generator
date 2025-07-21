# Test Suite Documentation

This directory contains a comprehensive test suite for the Wink Controller Generator package. The test suite is designed to ensure reliable code generation, proper validation, and robust error handling.

## Test Structure

### 📁 Unit Tests (`tests/Unit/`)

#### GeneratorTest.php
Tests all generator classes including:
- **ApiControllerGenerator** - Tests API controller generation with JSON responses
- **WebControllerGenerator** - Tests web controller generation with view returns  
- **ResourceControllerGenerator** - Tests full CRUD resource controller generation
- **AbstractControllerGenerator** - Tests base generator functionality

**Key Test Cases:**
- Controller creation with default configurations
- Custom namespace and output path handling
- Template processing and variable substitution
- File overwrite protection and force flag behavior
- Directory creation for nested namespaces
- Error handling for invalid configurations
- PHP syntax validation of generated code

#### AnalyzerTest.php
Tests all analyzer classes including:
- **ModelAnalyzer** - Analyzes Eloquent models and their relationships
- **RouteAnalyzer** - Analyzes existing routes and suggests new ones
- **ValidationAnalyzer** - Generates validation rules from database schemas

**Key Test Cases:**
- Model existence detection and class analysis
- Relationship discovery (hasMany, belongsTo, etc.)
- Database schema analysis for validation rules
- Foreign key constraint detection
- Unique index and nullable field handling
- Route conflict detection and suggestions
- Update vs create validation rule differences

#### ServiceProviderTest.php
Tests the Laravel service provider registration:
- Command registration verification
- Configuration merging and publishing
- Package service binding
- Console-only command loading
- Publish group configuration (config and stubs)

### 📁 Feature Tests (`tests/Feature/`)

#### CommandTest.php
Tests all Artisan commands end-to-end:
- **wink:controllers:generate** - Basic controller generation command
- **wink:controllers:api** - API controller with resources and tests
- **wink:controllers:web** - Web controller with views and middleware

**Key Test Cases:**
- Command argument and option validation
- File generation verification
- Force flag behavior
- Help documentation display
- Error handling for invalid inputs
- Complex table name support
- Custom namespace path handling

#### IntegrationTest.php
Tests complete workflow scenarios:
- Full API controller generation pipeline
- Web controller with view generation
- Resource controller with relationships
- Multiple controller generation sequences
- Custom template usage
- Namespaced model handling
- Validation rule integration

#### ConfigurationTest.php
Tests configuration system:
- Default configuration loading
- Custom configuration overrides
- Environment-specific settings
- Template and stub path configuration
- Middleware and namespace customization
- Database connection settings

### 📁 Fixtures (`tests/Fixtures/`)

#### Models
Sample Eloquent models for testing:
- **User** - Basic user model with relationships
- **Post** - Content model with categories and users
- **Product** - E-commerce model with complex attributes
- **Category** - Hierarchical category model
- **UserProfile** - One-to-one relationship example

#### Migrations
Database schema definitions:
- Foreign key relationships
- Index optimization
- Constraint definitions
- JSON columns and enums
- Soft deletes support

#### Factories
Model factories for test data generation:
- Realistic fake data generation
- Relationship factory states
- Edge case data scenarios
- Performance-optimized factories

### 📁 Helpers (`tests/Helpers/`)

#### ControllerTestHelper.php
Utilities for controller testing:
- Temporary directory management
- PHP syntax validation
- Controller structure assertions
- Method and namespace extraction
- Mock model generation

#### DatabaseTestHelper.php
Database testing utilities:
- Dynamic table creation
- Schema analysis tools
- Foreign key detection
- Test data seeding
- Column type mapping

#### StubTestHelper.php
Template and stub utilities:
- Sample stub content generation
- Template variable processing
- Placeholder validation
- Syntax checking for processed templates

## Test Database Setup

The test suite uses SQLite in-memory database for fast execution:

```php
'testing' => [
    'driver' => 'sqlite',
    'database' => ':memory:',
    'prefix' => '',
    'foreign_key_constraints' => true,
]
```

Alternative configurations available for:
- File-based SQLite for debugging
- MySQL for integration testing
- PostgreSQL for production-like testing

## Running Tests

### Full Test Suite
```bash
composer test
```

### Unit Tests Only
```bash
vendor/bin/phpunit tests/Unit
```

### Feature Tests Only
```bash
vendor/bin/phpunit tests/Feature
```

### Specific Test Class
```bash
vendor/bin/phpunit tests/Unit/GeneratorTest.php
```

### With Coverage Report
```bash
vendor/bin/phpunit --coverage-html coverage
```

## Test Coverage Areas

### ✅ Code Generation
- API controller templates
- Web controller templates
- Form request classes
- API resource classes
- Controller test generation

### ✅ File Operations
- Directory creation
- File overwrite protection
- Template processing
- Stub customization
- Path resolution

### ✅ Database Analysis
- Schema introspection
- Relationship detection
- Validation rule generation
- Foreign key analysis
- Index optimization

### ✅ Configuration Management
- Default settings
- Custom overrides
- Environment handling
- Template paths
- Namespace configuration

### ✅ Error Handling
- Invalid table names
- Missing models
- File permission issues
- Template syntax errors
- Configuration validation

### ✅ Laravel Integration
- Service provider registration
- Artisan command integration
- Configuration publishing
- Factory registration
- Migration loading

## Test Data Scenarios

### Model Relationships
- One-to-one (User → UserProfile)
- One-to-many (User → Posts, Category → Products)
- Many-to-many (Posts ↔ Tags)
- Self-referencing (Category → Parent Category)
- Polymorphic relationships

### Database Schemas
- Simple tables (users, categories)
- Complex tables (products with JSON columns)
- Relationship tables with foreign keys
- Tables with unique constraints
- Soft delete enabled tables

### Edge Cases
- Empty tables
- Tables without primary keys
- Models without fillable attributes
- Circular relationship dependencies
- Very long table/column names

## Performance Considerations

### Fast Test Execution
- In-memory SQLite database
- Minimal fixture data
- Efficient factory patterns
- Parallel test capability

### Memory Management
- Automatic cleanup after each test
- Temporary file removal
- Database truncation
- Factory state reset

## Continuous Integration

The test suite is designed for CI/CD pipelines:

### GitHub Actions Compatible
```yaml
- name: Run Tests
  run: |
    php artisan test --parallel
    vendor/bin/phpunit --coverage-clover coverage.xml
```

### Test Matrix Support
- PHP 8.1, 8.2, 8.3
- Laravel 10.x, 11.x
- Multiple database engines
- Different operating systems

## Best Practices

### Test Organization
- Clear test method naming
- Comprehensive setup/teardown
- Isolated test scenarios
- Meaningful assertions

### Data Management
- Use factories for test data
- Clean state between tests
- Realistic test scenarios
- Edge case coverage

### Assertion Quality
- Specific error messages
- Multiple assertion types
- Expected vs actual comparisons
- Proper exception testing

## Debugging Tests

### Common Issues
1. **File Permission Errors** - Check temp directory permissions
2. **Database Locks** - Ensure proper cleanup in tearDown
3. **Template Syntax** - Validate stub placeholders
4. **Factory Errors** - Check model relationships

### Debug Techniques
```php
// Enable verbose output
vendor/bin/phpunit --verbose

// Stop on first failure
vendor/bin/phpunit --stop-on-failure

// Filter specific tests
vendor/bin/phpunit --filter="test_controller_generation"
```

## Contributing to Tests

### Adding New Tests
1. Follow existing naming conventions
2. Include both positive and negative test cases
3. Test error conditions and edge cases
4. Update this documentation

### Test Requirements
- Must pass on all supported PHP/Laravel versions
- Include proper setup and cleanup
- Use meaningful test names and descriptions
- Follow PSR standards