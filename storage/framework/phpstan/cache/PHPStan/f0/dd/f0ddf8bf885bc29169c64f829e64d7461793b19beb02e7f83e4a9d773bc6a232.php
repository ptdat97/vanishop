<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Pricing/Persistence/Models/PriceList.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Pricing\Persistence\Models\PriceList
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-67bc0fa4f13de40097f302a9c10c253b2eb73d58f04cc192f9c8ca34e5e0a8b7',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Pricing\\Persistence\\Models\\PriceList',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Pricing/Persistence/Models/PriceList.php',
      ),
    ),
    'namespace' => 'Modules\\Pricing\\Persistence\\Models',
    'name' => 'Modules\\Pricing\\Persistence\\Models\\PriceList',
    'shortName' => 'PriceList',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * @property int $id
 * @property int|null $customer_group_id
 * @property string $code
 * @property string $name
 * @property string $currency_code
 * @property PriceListType $type
 * @property int $priority
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property string $status
 * @property int $lock_version
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 25,
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
        'declaringClassName' => 'Modules\\Pricing\\Persistence\\Models\\PriceList',
        'implementingClassName' => 'Modules\\Pricing\\Persistence\\Models\\PriceList',
        'name' => 'fillable',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'code\', \'name\', \'currency_code\', \'type\', \'customer_group_id\', \'priority\', \'starts_at\', \'ends_at\', \'status\', \'lock_version\']',
          'attributes' => 
          array (
            'startLine' => 27,
            'endLine' => 27,
            'startTokenPos' => 55,
            'startFilePos' => 656,
            'endTokenPos' => 84,
            'endFilePos' => 779,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 27,
        'endLine' => 27,
        'startColumn' => 5,
        'endColumn' => 151,
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
        'endLine' => 32,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Modules\\Pricing\\Persistence\\Models',
        'declaringClassName' => 'Modules\\Pricing\\Persistence\\Models\\PriceList',
        'implementingClassName' => 'Modules\\Pricing\\Persistence\\Models\\PriceList',
        'currentClassName' => 'Modules\\Pricing\\Persistence\\Models\\PriceList',
        'aliasName' => NULL,
      ),
      'prices' => 
      array (
        'name' => 'prices',
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
 * @return HasMany<Price, $this>
 */',
        'startLine' => 37,
        'endLine' => 40,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Pricing\\Persistence\\Models',
        'declaringClassName' => 'Modules\\Pricing\\Persistence\\Models\\PriceList',
        'implementingClassName' => 'Modules\\Pricing\\Persistence\\Models\\PriceList',
        'currentClassName' => 'Modules\\Pricing\\Persistence\\Models\\PriceList',
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