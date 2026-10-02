<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/custom/plugin/PromotionRules/Domain/Rules/FirstOrderOnlyRule.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Plugin\PromotionRules\Domain\Rules\FirstOrderOnlyRule
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-91533deb17b03bde50d5011c29ab288c22fa425c91f6eabece96142fa5f6554c',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Plugin\\PromotionRules\\Domain\\Rules\\FirstOrderOnlyRule',
        'filename' => '/Users/dat/Ecommerce/vanishop/custom/plugin/PromotionRules/Domain/Rules/FirstOrderOnlyRule.php',
      ),
    ),
    'namespace' => 'Plugin\\PromotionRules\\Domain\\Rules',
    'name' => 'Plugin\\PromotionRules\\Domain\\Rules\\FirstOrderOnlyRule',
    'shortName' => 'FirstOrderOnlyRule',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Rule: chỉ áp dụng cho đơn hàng đầu tiên của khách (khách chưa từng có đơn nào không huỷ).
 * Cấu hình: {}
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 16,
    'endLine' => 48,
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
        'declaringClassName' => 'Plugin\\PromotionRules\\Domain\\Rules\\FirstOrderOnlyRule',
        'implementingClassName' => 'Plugin\\PromotionRules\\Domain\\Rules\\FirstOrderOnlyRule',
        'name' => 'TYPE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'first_order_only\'',
          'attributes' => 
          array (
            'startLine' => 18,
            'endLine' => 18,
            'startTokenPos' => 57,
            'startFilePos' => 505,
            'endTokenPos' => 57,
            'endFilePos' => 522,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 18,
        'endLine' => 18,
        'startColumn' => 5,
        'endColumn' => 43,
      ),
    ),
    'immediateProperties' => 
    array (
      'orders' => 
      array (
        'declaringClassName' => 'Plugin\\PromotionRules\\Domain\\Rules\\FirstOrderOnlyRule',
        'implementingClassName' => 'Plugin\\PromotionRules\\Domain\\Rules\\FirstOrderOnlyRule',
        'name' => 'orders',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Modules\\Ordering\\Contracts\\OrderReader',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 20,
        'endLine' => 20,
        'startColumn' => 33,
        'endColumn' => 68,
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
          'orders' => 
          array (
            'name' => 'orders',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Ordering\\Contracts\\OrderReader',
                'isIdentifier' => false,
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
            'startColumn' => 33,
            'endColumn' => 68,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 20,
        'endLine' => 20,
        'startColumn' => 5,
        'endColumn' => 72,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Plugin\\PromotionRules\\Domain\\Rules',
        'declaringClassName' => 'Plugin\\PromotionRules\\Domain\\Rules\\FirstOrderOnlyRule',
        'implementingClassName' => 'Plugin\\PromotionRules\\Domain\\Rules\\FirstOrderOnlyRule',
        'currentClassName' => 'Plugin\\PromotionRules\\Domain\\Rules\\FirstOrderOnlyRule',
        'aliasName' => NULL,
      ),
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
        'startLine' => 22,
        'endLine' => 25,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Plugin\\PromotionRules\\Domain\\Rules',
        'declaringClassName' => 'Plugin\\PromotionRules\\Domain\\Rules\\FirstOrderOnlyRule',
        'implementingClassName' => 'Plugin\\PromotionRules\\Domain\\Rules\\FirstOrderOnlyRule',
        'currentClassName' => 'Plugin\\PromotionRules\\Domain\\Rules\\FirstOrderOnlyRule',
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
        'startLine' => 27,
        'endLine' => 30,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Plugin\\PromotionRules\\Domain\\Rules',
        'declaringClassName' => 'Plugin\\PromotionRules\\Domain\\Rules\\FirstOrderOnlyRule',
        'implementingClassName' => 'Plugin\\PromotionRules\\Domain\\Rules\\FirstOrderOnlyRule',
        'currentClassName' => 'Plugin\\PromotionRules\\Domain\\Rules\\FirstOrderOnlyRule',
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
            'startLine' => 32,
            'endLine' => 32,
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
        'startLine' => 32,
        'endLine' => 35,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Plugin\\PromotionRules\\Domain\\Rules',
        'declaringClassName' => 'Plugin\\PromotionRules\\Domain\\Rules\\FirstOrderOnlyRule',
        'implementingClassName' => 'Plugin\\PromotionRules\\Domain\\Rules\\FirstOrderOnlyRule',
        'currentClassName' => 'Plugin\\PromotionRules\\Domain\\Rules\\FirstOrderOnlyRule',
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
            'startLine' => 37,
            'endLine' => 37,
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
            'startLine' => 37,
            'endLine' => 37,
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
            'startLine' => 37,
            'endLine' => 37,
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
        'startLine' => 37,
        'endLine' => 47,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Plugin\\PromotionRules\\Domain\\Rules',
        'declaringClassName' => 'Plugin\\PromotionRules\\Domain\\Rules\\FirstOrderOnlyRule',
        'implementingClassName' => 'Plugin\\PromotionRules\\Domain\\Rules\\FirstOrderOnlyRule',
        'currentClassName' => 'Plugin\\PromotionRules\\Domain\\Rules\\FirstOrderOnlyRule',
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