<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Promotion/Persistence/Models/Promotion.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Promotion\Persistence\Models\Promotion
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-802061eea064e692226620ed6f3ec9c201f010279e69f054d039cc112f335b21',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Promotion\\Persistence\\Models\\Promotion',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Promotion/Persistence/Models/Promotion.php',
      ),
    ),
    'namespace' => 'Modules\\Promotion\\Persistence\\Models',
    'name' => 'Modules\\Promotion\\Persistence\\Models\\Promotion',
    'shortName' => 'Promotion',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * @property int $id
 * @property string $name
 * @property string $status
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property int $priority
 * @property Stacking $stacking
 * @property bool $requires_voucher
 * @property string $action_type
 * @property array<string, mixed> $action_config
 * @property int|null $usage_limit
 * @property int $usage_count
 * @property int|null $budget_amount
 * @property int $budget_used_amount
 * @property int $lock_version
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 29,
    'endLine' => 65,
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
        'declaringClassName' => 'Modules\\Promotion\\Persistence\\Models\\Promotion',
        'implementingClassName' => 'Modules\\Promotion\\Persistence\\Models\\Promotion',
        'name' => 'fillable',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'name\', \'status\', \'starts_at\', \'ends_at\', \'priority\', \'stacking\', \'requires_voucher\', \'action_type\', \'action_config\', \'usage_limit\', \'usage_count\', \'budget_amount\', \'budget_used_amount\', \'lock_version\']',
          'attributes' => 
          array (
            'startLine' => 31,
            'endLine' => 32,
            'startTokenPos' => 55,
            'startFilePos' => 809,
            'endTokenPos' => 96,
            'endFilePos' => 1019,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 31,
        'endLine' => 32,
        'startColumn' => 5,
        'endColumn' => 93,
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
        'startLine' => 34,
        'endLine' => 41,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Modules\\Promotion\\Persistence\\Models',
        'declaringClassName' => 'Modules\\Promotion\\Persistence\\Models\\Promotion',
        'implementingClassName' => 'Modules\\Promotion\\Persistence\\Models\\Promotion',
        'currentClassName' => 'Modules\\Promotion\\Persistence\\Models\\Promotion',
        'aliasName' => NULL,
      ),
      'rules' => 
      array (
        'name' => 'rules',
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
 * @return HasMany<PromotionRuleRecord, $this>
 */',
        'startLine' => 46,
        'endLine' => 49,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Promotion\\Persistence\\Models',
        'declaringClassName' => 'Modules\\Promotion\\Persistence\\Models\\Promotion',
        'implementingClassName' => 'Modules\\Promotion\\Persistence\\Models\\Promotion',
        'currentClassName' => 'Modules\\Promotion\\Persistence\\Models\\Promotion',
        'aliasName' => NULL,
      ),
      'vouchers' => 
      array (
        'name' => 'vouchers',
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
 * @return HasMany<Voucher, $this>
 */',
        'startLine' => 54,
        'endLine' => 57,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Promotion\\Persistence\\Models',
        'declaringClassName' => 'Modules\\Promotion\\Persistence\\Models\\Promotion',
        'implementingClassName' => 'Modules\\Promotion\\Persistence\\Models\\Promotion',
        'currentClassName' => 'Modules\\Promotion\\Persistence\\Models\\Promotion',
        'aliasName' => NULL,
      ),
      'isRunningAt' => 
      array (
        'name' => 'isRunningAt',
        'parameters' => 
        array (
          'now' => 
          array (
            'name' => 'now',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'int',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 59,
            'endLine' => 59,
            'startColumn' => 33,
            'endColumn' => 40,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 59,
        'endLine' => 64,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Promotion\\Persistence\\Models',
        'declaringClassName' => 'Modules\\Promotion\\Persistence\\Models\\Promotion',
        'implementingClassName' => 'Modules\\Promotion\\Persistence\\Models\\Promotion',
        'currentClassName' => 'Modules\\Promotion\\Persistence\\Models\\Promotion',
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