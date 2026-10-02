<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/custom/plugin/PromotionRules/Domain/Rules/MinOrderSubtotalRule.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Plugin\PromotionRules\Domain\Rules\MinOrderSubtotalRule
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-d14840722e16df899fb78d236f79b6969802ef1d917b7e5957ef4d0b046cf4a7',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Plugin\\PromotionRules\\Domain\\Rules\\MinOrderSubtotalRule',
        'filename' => '/Users/dat/Ecommerce/vanishop/custom/plugin/PromotionRules/Domain/Rules/MinOrderSubtotalRule.php',
      ),
    ),
    'namespace' => 'Plugin\\PromotionRules\\Domain\\Rules',
    'name' => 'Plugin\\PromotionRules\\Domain\\Rules\\MinOrderSubtotalRule',
    'shortName' => 'MinOrderSubtotalRule',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Rule: giá trị giỏ hàng (tổng tiền các dòng ứng viên) phải đạt tối thiểu min_subtotal.
 * Cấu hình: {"min_subtotal": 500000}
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 15,
    'endLine' => 56,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'Modules\\Promotion\\Contracts\\PromotionRule',
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
      'TYPE' => 
      array (
        'declaringClassName' => 'Plugin\\PromotionRules\\Domain\\Rules\\MinOrderSubtotalRule',
        'implementingClassName' => 'Plugin\\PromotionRules\\Domain\\Rules\\MinOrderSubtotalRule',
        'name' => 'TYPE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'min_order_subtotal\'',
          'attributes' => 
          array (
            'startLine' => 17,
            'endLine' => 17,
            'startTokenPos' => 52,
            'startFilePos' => 479,
            'endTokenPos' => 52,
            'endFilePos' => 498,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 17,
        'endLine' => 17,
        'startColumn' => 5,
        'endColumn' => 45,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'type' => 
      array (
        'name' => 'type',
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
        'startLine' => 19,
        'endLine' => 22,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Plugin\\PromotionRules\\Domain\\Rules',
        'declaringClassName' => 'Plugin\\PromotionRules\\Domain\\Rules\\MinOrderSubtotalRule',
        'implementingClassName' => 'Plugin\\PromotionRules\\Domain\\Rules\\MinOrderSubtotalRule',
        'currentClassName' => 'Plugin\\PromotionRules\\Domain\\Rules\\MinOrderSubtotalRule',
        'aliasName' => NULL,
      ),
      'label' => 
      array (
        'name' => 'label',
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
        'startLine' => 24,
        'endLine' => 27,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Plugin\\PromotionRules\\Domain\\Rules',
        'declaringClassName' => 'Plugin\\PromotionRules\\Domain\\Rules\\MinOrderSubtotalRule',
        'implementingClassName' => 'Plugin\\PromotionRules\\Domain\\Rules\\MinOrderSubtotalRule',
        'currentClassName' => 'Plugin\\PromotionRules\\Domain\\Rules\\MinOrderSubtotalRule',
        'aliasName' => NULL,
      ),
      'validateConfig' => 
      array (
        'name' => 'validateConfig',
        'parameters' => 
        array (
          'config' => 
          array (
            'name' => 'config',
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
            'startLine' => 29,
            'endLine' => 29,
            'startColumn' => 36,
            'endColumn' => 48,
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
        'docComment' => NULL,
        'startLine' => 29,
        'endLine' => 37,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Plugin\\PromotionRules\\Domain\\Rules',
        'declaringClassName' => 'Plugin\\PromotionRules\\Domain\\Rules\\MinOrderSubtotalRule',
        'implementingClassName' => 'Plugin\\PromotionRules\\Domain\\Rules\\MinOrderSubtotalRule',
        'currentClassName' => 'Plugin\\PromotionRules\\Domain\\Rules\\MinOrderSubtotalRule',
        'aliasName' => NULL,
      ),
      'evaluate' => 
      array (
        'name' => 'evaluate',
        'parameters' => 
        array (
          'context' => 
          array (
            'name' => 'context',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Promotion\\Contracts\\Data\\PromotionContext',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 39,
            'endLine' => 39,
            'startColumn' => 30,
            'endColumn' => 54,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'config' => 
          array (
            'name' => 'config',
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
            'startLine' => 39,
            'endLine' => 39,
            'startColumn' => 57,
            'endColumn' => 69,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'candidates' => 
          array (
            'name' => 'candidates',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Promotion\\Contracts\\Data\\Eligibility',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 39,
            'endLine' => 39,
            'startColumn' => 72,
            'endColumn' => 94,
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
            'name' => 'Modules\\Promotion\\Contracts\\Data\\Eligibility',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 39,
        'endLine' => 55,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Plugin\\PromotionRules\\Domain\\Rules',
        'declaringClassName' => 'Plugin\\PromotionRules\\Domain\\Rules\\MinOrderSubtotalRule',
        'implementingClassName' => 'Plugin\\PromotionRules\\Domain\\Rules\\MinOrderSubtotalRule',
        'currentClassName' => 'Plugin\\PromotionRules\\Domain\\Rules\\MinOrderSubtotalRule',
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