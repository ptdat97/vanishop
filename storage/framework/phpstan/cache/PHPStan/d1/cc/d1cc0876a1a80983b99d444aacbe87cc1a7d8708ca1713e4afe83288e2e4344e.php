<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Returns/Persistence/Models/ReturnRequest.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Returns\Persistence\Models\ReturnRequest
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-fad353a7b1f32f67dabbf50bc0b882c708f07bfb35b7cd30db11a3df59cc6a1e',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Returns\\Persistence\\Models\\ReturnRequest',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Returns/Persistence/Models/ReturnRequest.php',
      ),
    ),
    'namespace' => 'Modules\\Returns\\Persistence\\Models',
    'name' => 'Modules\\Returns\\Persistence\\Models\\ReturnRequest',
    'shortName' => 'ReturnRequest',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * @property int $id
 * @property string $public_id
 * @property string $number
 * @property int $order_id
 * @property ReturnStatus $status
 * @property string $reason_code
 * @property int $refund_amount
 * @property int|null $refunded_amount
 * @property string $currency_code
 * @property int $lock_version
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 23,
    'endLine' => 42,
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
      'guarded' => 
      array (
        'declaringClassName' => 'Modules\\Returns\\Persistence\\Models\\ReturnRequest',
        'implementingClassName' => 'Modules\\Returns\\Persistence\\Models\\ReturnRequest',
        'name' => 'guarded',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'id\']',
          'attributes' => 
          array (
            'startLine' => 25,
            'endLine' => 25,
            'startTokenPos' => 50,
            'startFilePos' => 600,
            'endTokenPos' => 52,
            'endFilePos' => 605,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 25,
        'endLine' => 25,
        'startColumn' => 5,
        'endColumn' => 32,
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
        'startLine' => 27,
        'endLine' => 33,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Modules\\Returns\\Persistence\\Models',
        'declaringClassName' => 'Modules\\Returns\\Persistence\\Models\\ReturnRequest',
        'implementingClassName' => 'Modules\\Returns\\Persistence\\Models\\ReturnRequest',
        'currentClassName' => 'Modules\\Returns\\Persistence\\Models\\ReturnRequest',
        'aliasName' => NULL,
      ),
      'lines' => 
      array (
        'name' => 'lines',
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
 * @return HasMany<ReturnLine, $this>
 */',
        'startLine' => 38,
        'endLine' => 41,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Returns\\Persistence\\Models',
        'declaringClassName' => 'Modules\\Returns\\Persistence\\Models\\ReturnRequest',
        'implementingClassName' => 'Modules\\Returns\\Persistence\\Models\\ReturnRequest',
        'currentClassName' => 'Modules\\Returns\\Persistence\\Models\\ReturnRequest',
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