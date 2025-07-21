<?php

namespace Wink\ControllerGenerator\Tests\Helpers;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseTestHelper
{
    /**
     * Create a test database table with specific schema.
     */
    public function createTestTable(string $tableName, array $columns = []): void
    {
        if (Schema::hasTable($tableName)) {
            Schema::drop($tableName);
        }

        Schema::create($tableName, function (Blueprint $table) use ($columns) {
            $table->id();

            foreach ($columns as $column) {
                $this->addColumnToTable($table, $column);
            }

            $table->timestamps();
        });
    }

    /**
     * Add a column to a table based on configuration.
     */
    protected function addColumnToTable(Blueprint $table, array $column): void
    {
        $name = $column['name'];
        $type = $column['type'] ?? 'string';
        $options = $column['options'] ?? [];

        switch ($type) {
            case 'string':
                $col = $table->string($name, $options['length'] ?? 255);
                break;
            case 'text':
                $col = $table->text($name);
                break;
            case 'integer':
                $col = $table->integer($name);
                break;
            case 'bigInteger':
                $col = $table->bigInteger($name);
                break;
            case 'decimal':
                $precision = $options['precision'] ?? 8;
                $scale = $options['scale'] ?? 2;
                $col = $table->decimal($name, $precision, $scale);
                break;
            case 'boolean':
                $col = $table->boolean($name);
                break;
            case 'date':
                $col = $table->date($name);
                break;
            case 'datetime':
                $col = $table->dateTime($name);
                break;
            case 'timestamp':
                $col = $table->timestamp($name);
                break;
            case 'json':
                $col = $table->json($name);
                break;
            case 'enum':
                $values = $options['values'] ?? ['active', 'inactive'];
                $col = $table->enum($name, $values);
                break;
            case 'foreignId':
                $col = $table->foreignId($name);
                if (isset($options['references'])) {
                    $col->constrained($options['references']['table'] ?? null)
                        ->onDelete($options['references']['onDelete'] ?? 'cascade');
                }
                break;
            default:
                $col = $table->string($name);
        }

        // Apply common options
        if (isset($options['nullable']) && $options['nullable']) {
            $col->nullable();
        }

        if (isset($options['default'])) {
            $col->default($options['default']);
        }

        if (isset($options['unique']) && $options['unique']) {
            $col->unique();
        }

        if (isset($options['index']) && $options['index']) {
            $col->index();
        }
    }

    /**
     * Get sample table configurations for testing.
     */
    public function getSampleTableConfigurations(): array
    {
        return [
            'users' => [
                ['name' => 'name', 'type' => 'string'],
                ['name' => 'email', 'type' => 'string', 'options' => ['unique' => true]],
                ['name' => 'password', 'type' => 'string'],
                ['name' => 'email_verified_at', 'type' => 'timestamp', 'options' => ['nullable' => true]],
                ['name' => 'is_active', 'type' => 'boolean', 'options' => ['default' => true]],
            ],
            'posts' => [
                ['name' => 'title', 'type' => 'string'],
                ['name' => 'slug', 'type' => 'string', 'options' => ['unique' => true]],
                ['name' => 'content', 'type' => 'text'],
                ['name' => 'excerpt', 'type' => 'text', 'options' => ['nullable' => true]],
                ['name' => 'published_at', 'type' => 'timestamp', 'options' => ['nullable' => true]],
                ['name' => 'user_id', 'type' => 'foreignId', 'options' => ['references' => ['table' => 'users']]],
                ['name' => 'status', 'type' => 'enum', 'options' => ['values' => ['draft', 'published', 'archived'], 'default' => 'draft']],
                ['name' => 'is_featured', 'type' => 'boolean', 'options' => ['default' => false]],
            ],
            'products' => [
                ['name' => 'name', 'type' => 'string'],
                ['name' => 'slug', 'type' => 'string', 'options' => ['unique' => true]],
                ['name' => 'description', 'type' => 'text'],
                ['name' => 'price', 'type' => 'decimal', 'options' => ['precision' => 10, 'scale' => 2]],
                ['name' => 'cost', 'type' => 'decimal', 'options' => ['precision' => 10, 'scale' => 2, 'nullable' => true]],
                ['name' => 'sku', 'type' => 'string', 'options' => ['unique' => true]],
                ['name' => 'stock_quantity', 'type' => 'integer', 'options' => ['default' => 0]],
                ['name' => 'is_active', 'type' => 'boolean', 'options' => ['default' => true]],
                ['name' => 'category_id', 'type' => 'foreignId', 'options' => ['nullable' => true, 'references' => ['table' => 'categories']]],
            ],
            'categories' => [
                ['name' => 'name', 'type' => 'string'],
                ['name' => 'slug', 'type' => 'string', 'options' => ['unique' => true]],
                ['name' => 'description', 'type' => 'text', 'options' => ['nullable' => true]],
                ['name' => 'parent_id', 'type' => 'bigInteger', 'options' => ['nullable' => true]],
                ['name' => 'sort_order', 'type' => 'integer', 'options' => ['default' => 0]],
                ['name' => 'is_active', 'type' => 'boolean', 'options' => ['default' => true]],
            ],
        ];
    }

    /**
     * Create all sample tables for testing.
     */
    public function createAllSampleTables(): array
    {
        $configurations = $this->getSampleTableConfigurations();
        $createdTables = [];

        foreach ($configurations as $tableName => $columns) {
            $this->createTestTable($tableName, $columns);
            $createdTables[] = $tableName;
        }

        return $createdTables;
    }

    /**
     * Drop test tables.
     */
    public function dropTestTables(array $tableNames): void
    {
        // Drop in reverse order to handle foreign key constraints
        $tableNames = array_reverse($tableNames);

        foreach ($tableNames as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::drop($tableName);
            }
        }
    }

    /**
     * Get column information for a table.
     */
    public function getTableColumns(string $tableName): array
    {
        if (!Schema::hasTable($tableName)) {
            return [];
        }

        return Schema::getColumnListing($tableName);
    }

    /**
     * Get detailed column information for a table.
     */
    public function getTableColumnDetails(string $tableName): array
    {
        if (!Schema::hasTable($tableName)) {
            return [];
        }

        $columns = [];
        
        // This is a simplified version - in a real implementation,
        // you'd use database-specific queries to get detailed column info
        foreach (Schema::getColumnListing($tableName) as $columnName) {
            $columns[$columnName] = [
                'name' => $columnName,
                'type' => $this->getColumnType($tableName, $columnName),
                'nullable' => $this->isColumnNullable($tableName, $columnName),
                'default' => $this->getColumnDefault($tableName, $columnName),
            ];
        }

        return $columns;
    }

    /**
     * Check if a column is nullable (simplified implementation).
     */
    protected function isColumnNullable(string $tableName, string $columnName): bool
    {
        // This is a simplified implementation
        // In practice, you'd query the information_schema or use database-specific methods
        return !in_array($columnName, ['id', 'created_at', 'updated_at']);
    }

    /**
     * Get column type (simplified implementation).
     */
    protected function getColumnType(string $tableName, string $columnName): string
    {
        // This is a simplified implementation
        // In practice, you'd query the database schema
        $typeMap = [
            'id' => 'bigint',
            'created_at' => 'timestamp',
            'updated_at' => 'timestamp',
            'email' => 'string',
            'password' => 'string',
            'name' => 'string',
            'title' => 'string',
            'content' => 'text',
            'description' => 'text',
            'price' => 'decimal',
            'cost' => 'decimal',
            'stock_quantity' => 'integer',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ];

        return $typeMap[$columnName] ?? 'string';
    }

    /**
     * Get column default value (simplified implementation).
     */
    protected function getColumnDefault(string $tableName, string $columnName): mixed
    {
        // This is a simplified implementation
        $defaultMap = [
            'is_active' => true,
            'is_featured' => false,
            'stock_quantity' => 0,
            'sort_order' => 0,
        ];

        return $defaultMap[$columnName] ?? null;
    }

    /**
     * Get foreign key relationships for a table.
     */
    public function getForeignKeys(string $tableName): array
    {
        // This is a simplified implementation
        // In practice, you'd query the database schema for foreign key constraints
        $foreignKeys = [];

        $columns = $this->getTableColumns($tableName);
        
        foreach ($columns as $column) {
            if (str_ends_with($column, '_id') && $column !== 'id') {
                $referencedTable = str_replace('_id', '', $column);
                if ($referencedTable === 'user') {
                    $referencedTable = 'users';
                } else {
                    $referencedTable = str_plural($referencedTable);
                }
                
                $foreignKeys[] = [
                    'column' => $column,
                    'referenced_table' => $referencedTable,
                    'referenced_column' => 'id',
                ];
            }
        }

        return $foreignKeys;
    }

    /**
     * Seed test data into tables.
     */
    public function seedTestData(string $tableName, array $data): void
    {
        DB::table($tableName)->insert($data);
    }

    /**
     * Get sample data for seeding.
     */
    public function getSampleData(string $tableName): array
    {
        $sampleData = [
            'users' => [
                [
                    'name' => 'John Doe',
                    'email' => 'john@example.com',
                    'password' => bcrypt('password'),
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Jane Smith',
                    'email' => 'jane@example.com',
                    'password' => bcrypt('password'),
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ],
            'categories' => [
                [
                    'name' => 'Technology',
                    'slug' => 'technology',
                    'description' => 'Technology related content',
                    'is_active' => true,
                    'sort_order' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Lifestyle',
                    'slug' => 'lifestyle',
                    'description' => 'Lifestyle content',
                    'is_active' => true,
                    'sort_order' => 2,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ],
        ];

        return $sampleData[$tableName] ?? [];
    }

    /**
     * Clear all data from test tables.
     */
    public function clearTestData(array $tableNames): void
    {
        foreach ($tableNames as $tableName) {
            if (Schema::hasTable($tableName)) {
                DB::table($tableName)->truncate();
            }
        }
    }
}