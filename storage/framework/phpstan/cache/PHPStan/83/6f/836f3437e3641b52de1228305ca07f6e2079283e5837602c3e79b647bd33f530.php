<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Pricing/Domain/PriceSelection.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Pricing\Domain\PriceSelection
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-3c66f06354711a836cb2807e8997d438b50c170182d553995578bb417f3e734c',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Pricing\\Domain\\PriceSelection',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Pricing/Domain/PriceSelection.php',
      ),
    ),
    'namespace' => 'Modules\\Pricing\\Domain',
    'name' => 'Modules\\Pricing\\Domain\\PriceSelection',
    'shortName' => 'PriceSelection',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Quy tắc chọn giá mặc định (price_list_priority), thuần PHP:
 * 1. Bảng giá priority cao nhất thắng; cùng priority → giá thấp hơn; vẫn trùng → id nhỏ hơn (ổn định).
 * 2. Giá gốc (compare_at): lấy compare_at của mức thắng nếu lớn hơn giá bán; nếu mức thắng không phải
 *    bảng base thì dùng giá base cao nhất khi lớn hơn giá bán. Không có thì null (không hiển thị giảm giá).
 *
 * @see docs/03-domains/catalog-pricing.md §6
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 15,
    'endLine' => 44,
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
      'choose' => 
      array (
        'name' => 'choose',
        'parameters' => 
        array (
          'candidates' => 
          array (
            'name' => 'candidates',
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
            'startLine' => 20,
            'endLine' => 20,
            'startColumn' => 35,
            'endColumn' => 51,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionUnionType',
          'data' => 
          array (
            'types' => 
            array (
              0 => 
              array (
                'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                'data' => 
                array (
                  'name' => 'Modules\\Pricing\\Domain\\SelectedPrice',
                  'isIdentifier' => false,
                ),
              ),
              1 => 
              array (
                'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                'data' => 
                array (
                  'name' => 'null',
                  'isIdentifier' => true,
                ),
              ),
            ),
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param  list<PriceCandidate>  $candidates
 */',
        'startLine' => 20,
        'endLine' => 43,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Modules\\Pricing\\Domain',
        'declaringClassName' => 'Modules\\Pricing\\Domain\\PriceSelection',
        'implementingClassName' => 'Modules\\Pricing\\Domain\\PriceSelection',
        'currentClassName' => 'Modules\\Pricing\\Domain\\PriceSelection',
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