<?php

namespace Wink\ControllerGenerator\Generators;

class ApiControllerGenerator
{
    /**
     * Generate an API controller.
     */
    public function generate(string $table, string $modelClass, array $options): array
    {
        // Placeholder implementation
        return [
            'files' => [
                'app/Http/Controllers/Api/UserController.php',
                'app/Http/Resources/UserResource.php',
                'app/Http/Requests/StoreUserRequest.php',
                'app/Http/Requests/UpdateUserRequest.php',
                'tests/Feature/UserControllerTest.php',
            ],
            'controller_class' => 'App\\Http\\Controllers\\Api\\UserController',
            'resource_class' => 'UserResource',
            'warnings' => [],
        ];
    }
}