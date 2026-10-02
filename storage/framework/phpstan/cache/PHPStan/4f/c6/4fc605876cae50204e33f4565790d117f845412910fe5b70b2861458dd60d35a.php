<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Identity/Persistence/Models/StaffUser.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Identity\Persistence\Models\StaffUser
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-eecca73402db72726c6530f4969567837e55a98db537e116d2dfab96c4e8bd14',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Identity\\Persistence\\Models\\StaffUser',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Identity/Persistence/Models/StaffUser.php',
      ),
    ),
    'namespace' => 'Modules\\Identity\\Persistence\\Models',
    'name' => 'Modules\\Identity\\Persistence\\Models\\StaffUser',
    'shortName' => 'StaffUser',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Nhân viên đăng nhập Admin (guard "staff"), tách khỏi khách hàng.
 *
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $status
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 20,
    'endLine' => 54,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Illuminate\\Foundation\\Auth\\User',
    'implementsClassNames' => 
    array (
    ),
    'traitClassNames' => 
    array (
      0 => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
      'fillable' => 
      array (
        'declaringClassName' => 'Modules\\Identity\\Persistence\\Models\\StaffUser',
        'implementingClassName' => 'Modules\\Identity\\Persistence\\Models\\StaffUser',
        'name' => 'fillable',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'name\', \'email\', \'password\', \'status\', \'last_login_at\']',
          'attributes' => 
          array (
            'startLine' => 25,
            'endLine' => 25,
            'startTokenPos' => 66,
            'startFilePos' => 647,
            'endTokenPos' => 80,
            'endFilePos' => 702,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 25,
        'endLine' => 25,
        'startColumn' => 5,
        'endColumn' => 83,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'hidden' => 
      array (
        'declaringClassName' => 'Modules\\Identity\\Persistence\\Models\\StaffUser',
        'implementingClassName' => 'Modules\\Identity\\Persistence\\Models\\StaffUser',
        'name' => 'hidden',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'password\', \'remember_token\']',
          'attributes' => 
          array (
            'startLine' => 27,
            'endLine' => 27,
            'startTokenPos' => 89,
            'startFilePos' => 730,
            'endTokenPos' => 94,
            'endFilePos' => 759,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 27,
        'endLine' => 27,
        'startColumn' => 5,
        'endColumn' => 55,
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
        'startLine' => 29,
        'endLine' => 35,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Modules\\Identity\\Persistence\\Models',
        'declaringClassName' => 'Modules\\Identity\\Persistence\\Models\\StaffUser',
        'implementingClassName' => 'Modules\\Identity\\Persistence\\Models\\StaffUser',
        'currentClassName' => 'Modules\\Identity\\Persistence\\Models\\StaffUser',
        'aliasName' => NULL,
      ),
      'roleAssignments' => 
      array (
        'name' => 'roleAssignments',
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
 * @return HasMany<StaffRoleAssignment, $this>
 */',
        'startLine' => 40,
        'endLine' => 43,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Identity\\Persistence\\Models',
        'declaringClassName' => 'Modules\\Identity\\Persistence\\Models\\StaffUser',
        'implementingClassName' => 'Modules\\Identity\\Persistence\\Models\\StaffUser',
        'currentClassName' => 'Modules\\Identity\\Persistence\\Models\\StaffUser',
        'aliasName' => NULL,
      ),
      'isActive' => 
      array (
        'name' => 'isActive',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 45,
        'endLine' => 48,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Identity\\Persistence\\Models',
        'declaringClassName' => 'Modules\\Identity\\Persistence\\Models\\StaffUser',
        'implementingClassName' => 'Modules\\Identity\\Persistence\\Models\\StaffUser',
        'currentClassName' => 'Modules\\Identity\\Persistence\\Models\\StaffUser',
        'aliasName' => NULL,
      ),
      'newFactory' => 
      array (
        'name' => 'newFactory',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Modules\\Identity\\Persistence\\Database\\Factories\\StaffUserFactory',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 50,
        'endLine' => 53,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 18,
        'namespace' => 'Modules\\Identity\\Persistence\\Models',
        'declaringClassName' => 'Modules\\Identity\\Persistence\\Models\\StaffUser',
        'implementingClassName' => 'Modules\\Identity\\Persistence\\Models\\StaffUser',
        'currentClassName' => 'Modules\\Identity\\Persistence\\Models\\StaffUser',
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