<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Pricing/Testing/PricingStrategyContract.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Pricing\Testing\PricingStrategyContract
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-92f6eb87a1b34697329a0f5b2b916f5b9587c6ad6ab0cb2fe5f86abcdd87faeb',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Pricing\\Testing\\PricingStrategyContract',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Pricing/Testing/PricingStrategyContract.php',
      ),
    ),
    'namespace' => 'Modules\\Pricing\\Testing',
    'name' => 'Modules\\Pricing\\Testing\\PricingStrategyContract',
    'shortName' => 'PricingStrategyContract',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Contract test cho PricingStrategy. Plugin cung cấp `scenario` (tạo dữ liệu giá, trả [variantIds, PricingContext]).
 * Kiểm tra: mã ổn định; chỉ trả variant được hỏi, khoá khớp variantId; giá dương cùng tiền tệ; giá gốc (nếu có)
 * lớn hơn giá bán; % giảm 1–99; variant không tồn tại bị bỏ qua; xác định.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 17,
    'endLine' => 49,
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
      'define' => 
      array (
        'name' => 'define',
        'parameters' => 
        array (
          'label' => 
          array (
            'name' => 'label',
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
            'startLine' => 23,
            'endLine' => 23,
            'startColumn' => 35,
            'endColumn' => 47,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'strategy' => 
          array (
            'name' => 'strategy',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Closure',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 23,
            'endLine' => 23,
            'startColumn' => 50,
            'endColumn' => 66,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'scenario' => 
          array (
            'name' => 'scenario',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Closure',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 23,
            'endLine' => 23,
            'startColumn' => 69,
            'endColumn' => 85,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param  Closure(): PricingStrategy  $strategy
 * @param  Closure(): array{0: list<int>, 1: PricingContext}  $scenario
 */',
        'startLine' => 23,
        'endLine' => 48,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Modules\\Pricing\\Testing',
        'declaringClassName' => 'Modules\\Pricing\\Testing\\PricingStrategyContract',
        'implementingClassName' => 'Modules\\Pricing\\Testing\\PricingStrategyContract',
        'currentClassName' => 'Modules\\Pricing\\Testing\\PricingStrategyContract',
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