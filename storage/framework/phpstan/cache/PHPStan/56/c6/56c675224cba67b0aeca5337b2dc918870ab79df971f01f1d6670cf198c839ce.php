<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/custom/plugin/PromotionRules/Domain/Rules/InCollectionsRule.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Plugin\PromotionRules\Domain\Rules\InCollectionsRule
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-6158b9a25dad0935bd5af84825a15f3c21b5806346b6075b528100dd5b409624',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Plugin\\PromotionRules\\Domain\\Rules\\InCollectionsRule',
        'filename' => '/Users/dat/Ecommerce/vanishop/custom/plugin/PromotionRules/Domain/Rules/InCollectionsRule.php',
      ),
    ),
    'namespace' => 'Plugin\\PromotionRules\\Domain\\Rules',
    'name' => 'Plugin\\PromotionRules\\Domain\\Rules\\InCollectionsRule',
    'shortName' => 'InCollectionsRule',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Rule: chỉ áp dụng cho các sản phẩm thuộc một trong các bộ sưu tập chỉ định.
 * Cấu hình: {"slugs": ["he-2026", "giam-gia"]}
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 16,
    'endLine' => 83,
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
        'declaringClassName' => 'Plugin\\PromotionRules\\Domain\\Rules\\InCollectionsRule',
        'implementingClassName' => 'Plugin\\PromotionRules\\Domain\\Rules\\InCollectionsRule',
        'name' => 'TYPE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'in_collections\'',
          'attributes' => 
          array (
            'startLine' => 18,
            'endLine' => 18,
            'startTokenPos' => 57,
            'startFilePos' => 528,
            'endTokenPos' => 57,
            'endFilePos' => 543,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 18,
        'endLine' => 18,
        'startColumn' => 5,
        'endColumn' => 41,
      ),
    ),
    'immediateProperties' => 
    array (
      'collections' => 
      array (
        'declaringClassName' => 'Plugin\\PromotionRules\\Domain\\Rules\\InCollectionsRule',
        'implementingClassName' => 'Plugin\\PromotionRules\\Domain\\Rules\\InCollectionsRule',
        'name' => 'collections',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Modules\\Catalog\\Contracts\\CollectionDirectory',
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
        'endColumn' => 81,
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
          'collections' => 
          array (
            'name' => 'collections',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Catalog\\Contracts\\CollectionDirectory',
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
            'endColumn' => 81,
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
        'endColumn' => 85,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Plugin\\PromotionRules\\Domain\\Rules',
        'declaringClassName' => 'Plugin\\PromotionRules\\Domain\\Rules\\InCollectionsRule',
        'implementingClassName' => 'Plugin\\PromotionRules\\Domain\\Rules\\InCollectionsRule',
        'currentClassName' => 'Plugin\\PromotionRules\\Domain\\Rules\\InCollectionsRule',
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
        'declaringClassName' => 'Plugin\\PromotionRules\\Domain\\Rules\\InCollectionsRule',
        'implementingClassName' => 'Plugin\\PromotionRules\\Domain\\Rules\\InCollectionsRule',
        'currentClassName' => 'Plugin\\PromotionRules\\Domain\\Rules\\InCollectionsRule',
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
        'declaringClassName' => 'Plugin\\PromotionRules\\Domain\\Rules\\InCollectionsRule',
        'implementingClassName' => 'Plugin\\PromotionRules\\Domain\\Rules\\InCollectionsRule',
        'currentClassName' => 'Plugin\\PromotionRules\\Domain\\Rules\\InCollectionsRule',
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
        'endLine' => 46,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Plugin\\PromotionRules\\Domain\\Rules',
        'declaringClassName' => 'Plugin\\PromotionRules\\Domain\\Rules\\InCollectionsRule',
        'implementingClassName' => 'Plugin\\PromotionRules\\Domain\\Rules\\InCollectionsRule',
        'currentClassName' => 'Plugin\\PromotionRules\\Domain\\Rules\\InCollectionsRule',
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
            'startLine' => 48,
            'endLine' => 48,
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
            'startLine' => 48,
            'endLine' => 48,
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
            'startLine' => 48,
            'endLine' => 48,
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
        'startLine' => 48,
        'endLine' => 82,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Plugin\\PromotionRules\\Domain\\Rules',
        'declaringClassName' => 'Plugin\\PromotionRules\\Domain\\Rules\\InCollectionsRule',
        'implementingClassName' => 'Plugin\\PromotionRules\\Domain\\Rules\\InCollectionsRule',
        'currentClassName' => 'Plugin\\PromotionRules\\Domain\\Rules\\InCollectionsRule',
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