<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Storefront/Contracts/Data/SlotView.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Storefront\Contracts\Data\SlotView
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-e45d59a5b16b85fe43133afb4f6a6c47daf8f1a29f1130325eb2f2b2fc40ee27',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Storefront\\Contracts\\Data\\SlotView',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Storefront/Contracts/Data/SlotView.php',
      ),
    ),
    'namespace' => 'Modules\\Storefront\\Contracts\\Data',
    'name' => 'Modules\\Storefront\\Contracts\\Data\\SlotView',
    'shortName' => 'SlotView',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 65568,
    'docComment' => '/**
 * Phần tử plugin trả cho slot storefront (ADR-025): view của plugin + dữ liệu. Theme render tại vị trí slot,
 * chỉ nối thêm. Plugin khai báo namespace view của mình (vd. `$this->loadViewsFrom(..., \'vani-reviews\')`).
 *
 *   $this->onSlot(\'vani.storefront.pdp.after_title\', fn (array $product) => new SlotView(\'vani-reviews::stars\', [\'slug\' => $product[\'slug\']]));
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 13,
    'endLine' => 22,
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
      'view' => 
      array (
        'declaringClassName' => 'Modules\\Storefront\\Contracts\\Data\\SlotView',
        'implementingClassName' => 'Modules\\Storefront\\Contracts\\Data\\SlotView',
        'name' => 'view',
        'modifiers' => 2049,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 19,
        'endLine' => 19,
        'startColumn' => 9,
        'endColumn' => 27,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'data' => 
      array (
        'declaringClassName' => 'Modules\\Storefront\\Contracts\\Data\\SlotView',
        'implementingClassName' => 'Modules\\Storefront\\Contracts\\Data\\SlotView',
        'name' => 'data',
        'modifiers' => 2049,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'default' => 
        array (
          'code' => '[]',
          'attributes' => 
          array (
            'startLine' => 20,
            'endLine' => 20,
            'startTokenPos' => 51,
            'startFilePos' => 661,
            'endTokenPos' => 52,
            'endFilePos' => 662,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 20,
        'endLine' => 20,
        'startColumn' => 9,
        'endColumn' => 31,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
    ),
    'immediateMethods' => 
    array (
      '__construct' => 
      array (
        'name' => '__construct',
        'parameters' => 
        array (
          'view' => 
          array (
            'name' => 'view',
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
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 19,
            'endLine' => 19,
            'startColumn' => 9,
            'endColumn' => 27,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'data' => 
          array (
            'name' => 'data',
            'default' => 
            array (
              'code' => '[]',
              'attributes' => 
              array (
                'startLine' => 20,
                'endLine' => 20,
                'startTokenPos' => 51,
                'startFilePos' => 661,
                'endTokenPos' => 52,
                'endFilePos' => 662,
              ),
            ),
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
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 20,
            'endLine' => 20,
            'startColumn' => 9,
            'endColumn' => 31,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param  array<string, mixed>  $data
 */',
        'startLine' => 18,
        'endLine' => 21,
        'startColumn' => 5,
        'endColumn' => 8,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Storefront\\Contracts\\Data',
        'declaringClassName' => 'Modules\\Storefront\\Contracts\\Data\\SlotView',
        'implementingClassName' => 'Modules\\Storefront\\Contracts\\Data\\SlotView',
        'currentClassName' => 'Modules\\Storefront\\Contracts\\Data\\SlotView',
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