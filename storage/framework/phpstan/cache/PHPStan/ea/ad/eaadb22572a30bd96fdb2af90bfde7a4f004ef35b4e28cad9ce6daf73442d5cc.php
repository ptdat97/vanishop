<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Catalog/Domain/SkuPattern.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Catalog\Domain\SkuPattern
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-3755229fa2bb0426c007a9ddd1f0732fed83c7318b6e3c8c90304f0b74ab3cef',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Catalog\\Domain\\SkuPattern',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Catalog/Domain/SkuPattern.php',
      ),
    ),
    'namespace' => 'Modules\\Catalog\\Domain',
    'name' => 'Modules\\Catalog\\Domain\\SkuPattern',
    'shortName' => 'SkuPattern',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * SKU mặc định khi sinh variant: {STYLE}-{COLOR}-{SIZE}, chữ in hoa, ký tự ngoài [A-Z0-9-] thành "-".
 * "LM24-SH012" + "IVR" + "38.5" → "LM24-SH012-IVR-38-5".
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 11,
    'endLine' => 24,
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
      'MAX_LENGTH' => 
      array (
        'declaringClassName' => 'Modules\\Catalog\\Domain\\SkuPattern',
        'implementingClassName' => 'Modules\\Catalog\\Domain\\SkuPattern',
        'name' => 'MAX_LENGTH',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '64',
          'attributes' => 
          array (
            'startLine' => 13,
            'endLine' => 13,
            'startTokenPos' => 33,
            'startFilePos' => 306,
            'endTokenPos' => 33,
            'endFilePos' => 307,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 13,
        'endLine' => 13,
        'startColumn' => 5,
        'endColumn' => 33,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'make' => 
      array (
        'name' => 'make',
        'parameters' => 
        array (
          'styleCode' => 
          array (
            'name' => 'styleCode',
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
            'startLine' => 15,
            'endLine' => 15,
            'startColumn' => 33,
            'endColumn' => 49,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'colorCode' => 
          array (
            'name' => 'colorCode',
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
            'startLine' => 15,
            'endLine' => 15,
            'startColumn' => 52,
            'endColumn' => 68,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'sizeCode' => 
          array (
            'name' => 'sizeCode',
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
            'startLine' => 15,
            'endLine' => 15,
            'startColumn' => 71,
            'endColumn' => 86,
            'parameterIndex' => 2,
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
        'startLine' => 15,
        'endLine' => 23,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Modules\\Catalog\\Domain',
        'declaringClassName' => 'Modules\\Catalog\\Domain\\SkuPattern',
        'implementingClassName' => 'Modules\\Catalog\\Domain\\SkuPattern',
        'currentClassName' => 'Modules\\Catalog\\Domain\\SkuPattern',
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