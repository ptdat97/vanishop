<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Promotion/Persistence/Models/Voucher.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Promotion\Persistence\Models\Voucher
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-7bf2b7591691a2056acf29581bd4ac36e6ad96f9b781ed281caf1b5dd1fdb90f',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Promotion\\Persistence\\Models\\Voucher',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Promotion/Persistence/Models/Voucher.php',
      ),
    ),
    'namespace' => 'Modules\\Promotion\\Persistence\\Models',
    'name' => 'Modules\\Promotion\\Persistence\\Models\\Voucher',
    'shortName' => 'Voucher',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * @property int $id
 * @property int $promotion_id
 * @property string $code
 * @property int|null $usage_limit
 * @property int $used_count
 * @property Carbon|null $expires_at
 * @property string $status
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 20,
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
        'declaringClassName' => 'Modules\\Promotion\\Persistence\\Models\\Voucher',
        'implementingClassName' => 'Modules\\Promotion\\Persistence\\Models\\Voucher',
        'name' => 'fillable',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'promotion_id\', \'code\', \'usage_limit\', \'used_count\', \'expires_at\', \'status\']',
          'attributes' => 
          array (
            'startLine' => 22,
            'endLine' => 22,
            'startTokenPos' => 50,
            'startFilePos' => 485,
            'endTokenPos' => 67,
            'endFilePos' => 561,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 22,
        'endLine' => 22,
        'startColumn' => 5,
        'endColumn' => 104,
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
        'startLine' => 24,
        'endLine' => 27,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Modules\\Promotion\\Persistence\\Models',
        'declaringClassName' => 'Modules\\Promotion\\Persistence\\Models\\Voucher',
        'implementingClassName' => 'Modules\\Promotion\\Persistence\\Models\\Voucher',
        'currentClassName' => 'Modules\\Promotion\\Persistence\\Models\\Voucher',
        'aliasName' => NULL,
      ),
      'promotion' => 
      array (
        'name' => 'promotion',
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
 * @return BelongsTo<Promotion, $this>
 */',
        'startLine' => 32,
        'endLine' => 35,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Promotion\\Persistence\\Models',
        'declaringClassName' => 'Modules\\Promotion\\Persistence\\Models\\Voucher',
        'implementingClassName' => 'Modules\\Promotion\\Persistence\\Models\\Voucher',
        'currentClassName' => 'Modules\\Promotion\\Persistence\\Models\\Voucher',
        'aliasName' => NULL,
      ),
      'normalize' => 
      array (
        'name' => 'normalize',
        'parameters' => 
        array (
          'code' => 
          array (
            'name' => 'code',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 37,
            'endLine' => 37,
            'startColumn' => 38,
            'endColumn' => 49,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 37,
        'endLine' => 40,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Modules\\Promotion\\Persistence\\Models',
        'declaringClassName' => 'Modules\\Promotion\\Persistence\\Models\\Voucher',
        'implementingClassName' => 'Modules\\Promotion\\Persistence\\Models\\Voucher',
        'currentClassName' => 'Modules\\Promotion\\Persistence\\Models\\Voucher',
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