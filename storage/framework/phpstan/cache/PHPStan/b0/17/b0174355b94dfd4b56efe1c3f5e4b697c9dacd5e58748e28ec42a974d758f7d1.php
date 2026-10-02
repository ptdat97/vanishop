<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Returns/Domain/RefundCalculator.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Returns\Domain\RefundCalculator
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-d83fce3d1407bea1984d4b924b8dfe8f6d5edfe02718a4bbaabbc10a28b247ae',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Returns\\Domain\\RefundCalculator',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Returns/Domain/RefundCalculator.php',
      ),
    ),
    'namespace' => 'Modules\\Returns\\Domain',
    'name' => 'Modules\\Returns\\Domain\\RefundCalculator',
    'shortName' => 'RefundCalculator',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Tiền hoàn cho k sản phẩm của một dòng = phần của k đơn vị kế tiếp khi chia thành tiền dòng (đã trừ giảm giá
 * phân bổ) đều cho số lượng bằng largest remainder. Trả nhiều lần không bao giờ vượt thành tiền dòng.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 13,
    'endLine' => 29,
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
      'forUnits' => 
      array (
        'name' => 'forUnits',
        'parameters' => 
        array (
          'lineTotal' => 
          array (
            'name' => 'lineTotal',
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
            'startLine' => 15,
            'endLine' => 15,
            'startColumn' => 37,
            'endColumn' => 52,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'lineQuantity' => 
          array (
            'name' => 'lineQuantity',
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
            'startLine' => 15,
            'endLine' => 15,
            'startColumn' => 55,
            'endColumn' => 71,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'alreadyReturned' => 
          array (
            'name' => 'alreadyReturned',
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
            'startLine' => 15,
            'endLine' => 15,
            'startColumn' => 74,
            'endColumn' => 93,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'quantity' => 
          array (
            'name' => 'quantity',
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
            'startLine' => 15,
            'endLine' => 15,
            'startColumn' => 96,
            'endColumn' => 108,
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
            'name' => 'Modules\\Shared\\Domain\\Money\\Money',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 15,
        'endLine' => 28,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Modules\\Returns\\Domain',
        'declaringClassName' => 'Modules\\Returns\\Domain\\RefundCalculator',
        'implementingClassName' => 'Modules\\Returns\\Domain\\RefundCalculator',
        'currentClassName' => 'Modules\\Returns\\Domain\\RefundCalculator',
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