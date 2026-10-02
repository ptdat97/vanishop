<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Identity/Persistence/Models/StaffRoleAssignment.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Identity\Persistence\Models\StaffRoleAssignment
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-3f840fe13d1ac1a8b4f6636e78b7921627a8a522be2bf122d33c52cfe26bc899',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Identity\\Persistence\\Models\\StaffRoleAssignment',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Identity/Persistence/Models/StaffRoleAssignment.php',
      ),
    ),
    'namespace' => 'Modules\\Identity\\Persistence\\Models',
    'name' => 'Modules\\Identity\\Persistence\\Models\\StaffRoleAssignment',
    'shortName' => 'StaffRoleAssignment',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * @property int $id
 * @property int $staff_user_id
 * @property int $role_id
 * @property ScopeType $scope_type
 * @property int|null $scope_id
 * @property Role $role
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 19,
    'endLine' => 38,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Illuminate\\Database\\Eloquent\\Model',
    'implementsClassNames' => 
    array (
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
      'fillable' => 
      array (
        'declaringClassName' => 'Modules\\Identity\\Persistence\\Models\\StaffRoleAssignment',
        'implementingClassName' => 'Modules\\Identity\\Persistence\\Models\\StaffRoleAssignment',
        'name' => 'fillable',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'staff_user_id\', \'role_id\', \'scope_type\', \'scope_id\']',
          'attributes' => 
          array (
            'startLine' => 21,
            'endLine' => 21,
            'startTokenPos' => 50,
            'startFilePos' => 467,
            'endTokenPos' => 61,
            'endFilePos' => 520,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 21,
        'endLine' => 21,
        'startColumn' => 5,
        'endColumn' => 81,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
    ),
    'immediateMethods' => 
    array (
      'casts' => 
      array (
        'name' => 'casts',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 23,
        'endLine' => 29,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Modules\\Identity\\Persistence\\Models',
        'declaringClassName' => 'Modules\\Identity\\Persistence\\Models\\StaffRoleAssignment',
        'implementingClassName' => 'Modules\\Identity\\Persistence\\Models\\StaffRoleAssignment',
        'currentClassName' => 'Modules\\Identity\\Persistence\\Models\\StaffRoleAssignment',
        'aliasName' => NULL,
      ),
      'role' => 
      array (
        'name' => 'role',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Illuminate\\Database\\Eloquent\\Relations\\BelongsTo',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return BelongsTo<Role, $this>
 */',
        'startLine' => 34,
        'endLine' => 37,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Identity\\Persistence\\Models',
        'declaringClassName' => 'Modules\\Identity\\Persistence\\Models\\StaffRoleAssignment',
        'implementingClassName' => 'Modules\\Identity\\Persistence\\Models\\StaffRoleAssignment',
        'currentClassName' => 'Modules\\Identity\\Persistence\\Models\\StaffRoleAssignment',
        'aliasName' => NULL,
      ),
    ),
    'traitsData' => 
    array (
      'aliases' => 
      array (
      ),
      'modifiers' => 
      array (
      ),
      'precedences' => 
      array (
      ),
      'hashes' => 
      array (
      ),
    ),
  ),
));