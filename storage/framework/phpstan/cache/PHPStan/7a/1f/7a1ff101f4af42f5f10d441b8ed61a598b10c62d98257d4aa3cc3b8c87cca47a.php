<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Fulfillment/Contracts/SourcingStrategy.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Fulfillment\Contracts\SourcingStrategy
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-ec37b7af5d0468bd25f08d53f350d385cd99790672895ad71b2e542313467e40',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Fulfillment\\Contracts\\SourcingStrategy',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Fulfillment/Contracts/SourcingStrategy.php',
      ),
    ),
    'namespace' => 'Modules\\Fulfillment\\Contracts',
    'name' => 'Modules\\Fulfillment\\Contracts\\SourcingStrategy',
    'shortName' => 'SourcingStrategy',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Extension point (tag `vani.fulfillment.sourcing`): chọn kho giao cho từng dòng.
 * Mặc định `reserved_locations`: giao từ đúng location đã giữ hàng lúc đặt (một shipment mỗi location).
 * Hiện Core chỉ chấp nhận đề xuất khớp với hàng đang giữ (chưa có chuyển giữ hàng giữa kho).
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 15,
    'endLine' => 26,
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
      'TAG' => 
      array (
        'declaringClassName' => 'Modules\\Fulfillment\\Contracts\\SourcingStrategy',
        'implementingClassName' => 'Modules\\Fulfillment\\Contracts\\SourcingStrategy',
        'name' => 'TAG',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'vani.fulfillment.sourcing\'',
          'attributes' => 
          array (
            'startLine' => 18,
            'endLine' => 18,
            'startTokenPos' => 43,
            'startFilePos' => 687,
            'endTokenPos' => 43,
            'endFilePos' => 713,
          ),
        ),
        'docComment' => '/** Tag extension point: plugin đóng góp qua `contribute(SourcingStrategy::TAG, …)`. */',
        'attributes' => 
        array (
        ),
        'startLine' => 18,
        'endLine' => 18,
        'startColumn' => 5,
        'endColumn' => 51,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'code' => 
      array (
        'name' => 'code',
        'parameters' => 
        array (
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
        'startLine' => 20,
        'endLine' => 20,
        'startColumn' => 5,
        'endColumn' => 35,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Fulfillment\\Contracts',
        'declaringClassName' => 'Modules\\Fulfillment\\Contracts\\SourcingStrategy',
        'implementingClassName' => 'Modules\\Fulfillment\\Contracts\\SourcingStrategy',
        'currentClassName' => 'Modules\\Fulfillment\\Contracts\\SourcingStrategy',
        'aliasName' => NULL,
      ),
      'allocate' => 
      array (
        'name' => 'allocate',
        'parameters' => 
        array (
          'request' => 
          array (
            'name' => 'request',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Fulfillment\\Contracts\\Data\\SourcingRequest',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 25,
            'endLine' => 25,
            'startColumn' => 30,
            'endColumn' => 53,
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
 * @return list<AllocationProposal>
 */',
        'startLine' => 25,
        'endLine' => 25,
        'startColumn' => 5,
        'endColumn' => 62,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Fulfillment\\Contracts',
        'declaringClassName' => 'Modules\\Fulfillment\\Contracts\\SourcingStrategy',
        'implementingClassName' => 'Modules\\Fulfillment\\Contracts\\SourcingStrategy',
        'currentClassName' => 'Modules\\Fulfillment\\Contracts\\SourcingStrategy',
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