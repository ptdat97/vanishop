<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Promotion/Domain/DiscountMath.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Promotion\Domain\DiscountMath
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-228c0b144102c03f6d1b1c67f00ade38b9d48ab0874bb18227883860b5a5775f',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Promotion\\Domain\\DiscountMath',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Promotion/Domain/DiscountMath.php',
      ),
    ),
    'namespace' => 'Modules\\Promotion\\Domain',
    'name' => 'Modules\\Promotion\\Domain\\DiscountMath',
    'shortName' => 'DiscountMath',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Phép tính giảm giá thuần: theo %, theo số tiền (phân bổ largest remainder), kẹp theo giá sàn.
 * Khoá mảng là khoá dòng (variant id); Money luôn >= 0 (độ lớn của giảm giá).
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 13,
    'endLine' => 63,
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
      'percentOff' => 
      array (
        'name' => 'percentOff',
        'parameters' => 
        array (
          'remaining' => 
          array (
            'name' => 'remaining',
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
            'startLine' => 19,
            'endLine' => 19,
            'startColumn' => 39,
            'endColumn' => 54,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'basisPoints' => 
          array (
            'name' => 'basisPoints',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'int',
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
            'startColumn' => 57,
            'endColumn' => 72,
            'parameterIndex' => 1,
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
 * @param  array<int, Money>  $remaining  số tiền còn lại của từng dòng đủ điều kiện
 * @return array<int, Money>
 */',
        'startLine' => 19,
        'endLine' => 22,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Modules\\Promotion\\Domain',
        'declaringClassName' => 'Modules\\Promotion\\Domain\\DiscountMath',
        'implementingClassName' => 'Modules\\Promotion\\Domain\\DiscountMath',
        'currentClassName' => 'Modules\\Promotion\\Domain\\DiscountMath',
        'aliasName' => NULL,
      ),
      'amountOff' => 
      array (
        'name' => 'amountOff',
        'parameters' => 
        array (
          'remaining' => 
          array (
            'name' => 'remaining',
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
            'startLine' => 30,
            'endLine' => 30,
            'startColumn' => 38,
            'endColumn' => 53,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'amount' => 
          array (
            'name' => 'amount',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Shared\\Domain\\Money\\Money',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 30,
            'endLine' => 30,
            'startColumn' => 56,
            'endColumn' => 68,
            'parameterIndex' => 1,
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
 * Giảm một khoản cố định cho nhóm dòng, chia theo tỷ trọng số tiền còn lại; không vượt tổng còn lại.
 *
 * @param  array<int, Money>  $remaining
 * @return array<int, Money>
 */',
        'startLine' => 30,
        'endLine' => 42,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Modules\\Promotion\\Domain',
        'declaringClassName' => 'Modules\\Promotion\\Domain\\DiscountMath',
        'implementingClassName' => 'Modules\\Promotion\\Domain\\DiscountMath',
        'currentClassName' => 'Modules\\Promotion\\Domain\\DiscountMath',
        'aliasName' => NULL,
      ),
      'capToFloor' => 
      array (
        'name' => 'capToFloor',
        'parameters' => 
        array (
          'proposed' => 
          array (
            'name' => 'proposed',
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
            'startLine' => 52,
            'endLine' => 52,
            'startColumn' => 39,
            'endColumn' => 53,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'lineSubtotals' => 
          array (
            'name' => 'lineSubtotals',
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
            'startLine' => 52,
            'endLine' => 52,
            'startColumn' => 56,
            'endColumn' => 75,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'alreadyDiscounted' => 
          array (
            'name' => 'alreadyDiscounted',
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
            'startLine' => 52,
            'endLine' => 52,
            'startColumn' => 78,
            'endColumn' => 101,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'maxBasisPoints' => 
          array (
            'name' => 'maxBasisPoints',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'int',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 52,
            'endLine' => 52,
            'startColumn' => 104,
            'endColumn' => 122,
            'parameterIndex' => 3,
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
 * Kẹp giảm giá để tổng giảm của mỗi dòng không vượt maxBasisPoints × tổng dòng (giá sàn).
 *
 * @param  array<int, Money>  $proposed  giảm giá mới đề xuất
 * @param  array<int, Money>  $lineSubtotals
 * @param  array<int, Money>  $alreadyDiscounted
 * @return array<int, Money>
 */',
        'startLine' => 52,
        'endLine' => 62,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Modules\\Promotion\\Domain',
        'declaringClassName' => 'Modules\\Promotion\\Domain\\DiscountMath',
        'implementingClassName' => 'Modules\\Promotion\\Domain\\DiscountMath',
        'currentClassName' => 'Modules\\Promotion\\Domain\\DiscountMath',
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