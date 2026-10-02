<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Catalog/Contracts/VariantDirectory.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Catalog\Contracts\VariantDirectory
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-afdea06051ab0a110f76794ca2540b0c0a808b63e497ccf100bb652bd5955222',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Catalog\\Contracts\\VariantDirectory',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Catalog/Contracts/VariantDirectory.php',
      ),
    ),
    'namespace' => 'Modules\\Catalog\\Contracts',
    'name' => 'Modules\\Catalog\\Contracts\\VariantDirectory',
    'shortName' => 'VariantDirectory',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Service contract: tra variant cho các module khác (Pricing, Inventory, Ordering…).
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 12,
    'endLine' => 34,
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
      'ofStyleCode' => 
      array (
        'name' => 'ofStyleCode',
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
            'startLine' => 19,
            'endLine' => 19,
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
 * Mọi variant (cả ngừng bán) của một style, theo thứ tự màu → size.
 *
 * @return list<VariantData>
 */',
        'startLine' => 19,
        'endLine' => 19,
        'startColumn' => 5,
        'endColumn' => 58,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Catalog\\Contracts',
        'declaringClassName' => 'Modules\\Catalog\\Contracts\\VariantDirectory',
        'implementingClassName' => 'Modules\\Catalog\\Contracts\\VariantDirectory',
        'currentClassName' => 'Modules\\Catalog\\Contracts\\VariantDirectory',
        'aliasName' => NULL,
      ),
      'find' => 
      array (
        'name' => 'find',
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
            'startLine' => 25,
            'endLine' => 25,
            'startColumn' => 26,
            'endColumn' => 42,
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
 * @return array<int, VariantData> id => data (id không tồn tạithì không có mặt)
 */',
        'startLine' => 25,
        'endLine' => 25,
        'startColumn' => 5,
        'endColumn' => 51,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Catalog\\Contracts',
        'declaringClassName' => 'Modules\\Catalog\\Contracts\\VariantDirectory',
        'implementingClassName' => 'Modules\\Catalog\\Contracts\\VariantDirectory',
        'currentClassName' => 'Modules\\Catalog\\Contracts\\VariantDirectory',
        'aliasName' => NULL,
      ),
      'findBySkus' => 
      array (
        'name' => 'findBySkus',
        'parameters' => 
        array (
          'skus' => 
          array (
            'name' => 'skus',
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
            'startLine' => 33,
            'endLine' => 33,
            'startColumn' => 32,
            'endColumn' => 42,
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
 * Tra theo SKU (mã duy nhất toàn hệ thống) — dùng cho đồng bộ từ hệ thống ngoài.
 *
 * @param  list<string>  $skus
 * @return array<string, VariantData> sku => data (SKU không tồn tạithì không có mặt)
 */',
        'startLine' => 33,
        'endLine' => 33,
        'startColumn' => 5,
        'endColumn' => 51,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Catalog\\Contracts',
        'declaringClassName' => 'Modules\\Catalog\\Contracts\\VariantDirectory',
        'implementingClassName' => 'Modules\\Catalog\\Contracts\\VariantDirectory',
        'currentClassName' => 'Modules\\Catalog\\Contracts\\VariantDirectory',
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