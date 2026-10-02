<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Catalog/Contracts/CollectionDirectory.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Catalog\Contracts\CollectionDirectory
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-3e07c72b59207646c6c3b7fc7becfca46eb56aef5dbcb973b24a962fb6442a0b',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Catalog\\Contracts\\CollectionDirectory',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Catalog/Contracts/CollectionDirectory.php',
      ),
    ),
    'namespace' => 'Modules\\Catalog\\Contracts',
    'name' => 'Modules\\Catalog\\Contracts\\CollectionDirectory',
    'shortName' => 'CollectionDirectory',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Service contract: bộ sưu tập chứa từng style.
 * Rule khuyến mãi theo bộ sưu tập (`vani.promotion-rules`) dùng contract này.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 11,
    'endLine' => 18,
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
      'slugsForStyles' => 
      array (
        'name' => 'slugsForStyles',
        'parameters' => 
        array (
          'styleIds' => 
          array (
            'name' => 'styleIds',
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
            'startLine' => 17,
            'endLine' => 17,
            'startColumn' => 36,
            'endColumn' => 50,
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
 * @param  list<int>  $styleIds
 * @return array<int, list<string>> style id => slug các bộ sưu tập đang hiển thị chứa style đó
 */',
        'startLine' => 17,
        'endLine' => 17,
        'startColumn' => 5,
        'endColumn' => 59,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Catalog\\Contracts',
        'declaringClassName' => 'Modules\\Catalog\\Contracts\\CollectionDirectory',
        'implementingClassName' => 'Modules\\Catalog\\Contracts\\CollectionDirectory',
        'currentClassName' => 'Modules\\Catalog\\Contracts\\CollectionDirectory',
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