<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Identity/Persistence/Models/Role.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Identity\Persistence\Models\Role
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-c709621749688e37fb2f09d3c4af50efa215a22400ddbbf38364180fbe554f30',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Identity\\Persistence\\Models\\Role',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Identity/Persistence/Models/Role.php',
      ),
    ),
    'namespace' => 'Modules\\Identity\\Persistence\\Models',
    'name' => 'Modules\\Identity\\Persistence\\Models\\Role',
    'shortName' => 'Role',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * @property int $id
 * @property string $code
 * @property string $name
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 15,
    'endLine' => 41,
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
        'declaringClassName' => 'Modules\\Identity\\Persistence\\Models\\Role',
        'implementingClassName' => 'Modules\\Identity\\Persistence\\Models\\Role',
        'name' => 'fillable',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'code\', \'name\', \'is_system\']',
          'attributes' => 
          array (
            'startLine' => 17,
            'endLine' => 17,
            'startTokenPos' => 45,
            'startFilePos' => 314,
            'endTokenPos' => 53,
            'endFilePos' => 342,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 17,
        'endLine' => 17,
        'startColumn' => 5,
        'endColumn' => 56,
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
        'startLine' => 19,
        'endLine' => 22,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Modules\\Identity\\Persistence\\Models',
        'declaringClassName' => 'Modules\\Identity\\Persistence\\Models\\Role',
        'implementingClassName' => 'Modules\\Identity\\Persistence\\Models\\Role',
        'currentClassName' => 'Modules\\Identity\\Persistence\\Models\\Role',
        'aliasName' => NULL,
      ),
      'permissions' => 
      array (
        'name' => 'permissions',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Illuminate\\Database\\Eloquent\\Relations\\HasMany',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return HasMany<RolePermission, $this>
 */',
        'startLine' => 27,
        'endLine' => 30,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Identity\\Persistence\\Models',
        'declaringClassName' => 'Modules\\Identity\\Persistence\\Models\\Role',
        'implementingClassName' => 'Modules\\Identity\\Persistence\\Models\\Role',
        'currentClassName' => 'Modules\\Identity\\Persistence\\Models\\Role',
        'aliasName' => NULL,
      ),
      'syncPermissions' => 
      array (
        'name' => 'syncPermissions',
        'parameters' => 
        array (
          'permissions' => 
          array (
            'name' => 'permissions',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'array',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 35,
            'endLine' => 35,
            'startColumn' => 37,
            'endColumn' => 54,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param  list<string>  $permissions
 */',
        'startLine' => 35,
        'endLine' => 40,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Identity\\Persistence\\Models',
        'declaringClassName' => 'Modules\\Identity\\Persistence\\Models\\Role',
        'implementingClassName' => 'Modules\\Identity\\Persistence\\Models\\Role',
        'currentClassName' => 'Modules\\Identity\\Persistence\\Models\\Role',
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