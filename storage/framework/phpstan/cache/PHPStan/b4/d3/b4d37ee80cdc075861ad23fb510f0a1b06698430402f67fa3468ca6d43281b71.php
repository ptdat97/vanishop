<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Inventory/Contracts/AvailabilityReader.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Inventory\Contracts\AvailabilityReader
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-ffa31b4194ea3d2536f27212fa6448969cf12dae4be1dd24e68603ba7edb076c',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Inventory\\Contracts\\AvailabilityReader',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Inventory/Contracts/AvailabilityReader.php',
      ),
    ),
    'namespace' => 'Modules\\Inventory\\Contracts',
    'name' => 'Modules\\Inventory\\Contracts\\AvailabilityReader',
    'shortName' => 'AvailabilityReader',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Service contract: số lượng có thể bán (ATS) của variant.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 10,
    'endLine' => 17,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
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
    ),
    'immediateMethods' => 
    array (
      'forVariants' => 
      array (
        'name' => 'forVariants',
        'parameters' => 
        array (
          'variantIds' => 
          array (
            'name' => 'variantIds',
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
            'startLine' => 16,
            'endLine' => 16,
            'startColumn' => 33,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param  list<int>  $variantIds
 * @return array<int, int> variant id => ATS (variant không có tồn → 0)
 */',
        'startLine' => 16,
        'endLine' => 16,
        'startColumn' => 5,
        'endColumn' => 58,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Inventory\\Contracts',
        'declaringClassName' => 'Modules\\Inventory\\Contracts\\AvailabilityReader',
        'implementingClassName' => 'Modules\\Inventory\\Contracts\\AvailabilityReader',
        'currentClassName' => 'Modules\\Inventory\\Contracts\\AvailabilityReader',
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