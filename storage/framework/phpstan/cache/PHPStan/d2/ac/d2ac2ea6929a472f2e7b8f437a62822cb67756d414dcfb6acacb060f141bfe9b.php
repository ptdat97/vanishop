<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Promotion/Application/PromotionRegistry.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Promotion\Application\PromotionRegistry
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-4a03b296b9b7efb125373d19bb88ed43052995ba0c6c65c9e153789b3f5b9999',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Promotion\\Application\\PromotionRegistry',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Promotion/Application/PromotionRegistry.php',
      ),
    ),
    'namespace' => 'Modules\\Promotion\\Application',
    'name' => 'Modules\\Promotion\\Application\\PromotionRegistry',
    'shortName' => 'PromotionRegistry',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Rule/action theo type, chỉ gồm implementation có hiệu lực trong phạm vi hiện tại
 * (plugin tắt thì type của nó không có mặt → khuyến mãi dùng type đó bị bỏ qua).
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 15,
    'endLine' => 48,
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
      'RULES_TAG' => 
      array (
        'declaringClassName' => 'Modules\\Promotion\\Application\\PromotionRegistry',
        'implementingClassName' => 'Modules\\Promotion\\Application\\PromotionRegistry',
        'name' => 'RULES_TAG',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\\Modules\\Promotion\\Contracts\\PromotionRule::TAG',
          'attributes' => 
          array (
            'startLine' => 17,
            'endLine' => 17,
            'startTokenPos' => 48,
            'startFilePos' => 488,
            'endTokenPos' => 50,
            'endFilePos' => 505,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 17,
        'endLine' => 17,
        'startColumn' => 5,
        'endColumn' => 48,
      ),
      'ACTIONS_TAG' => 
      array (
        'declaringClassName' => 'Modules\\Promotion\\Application\\PromotionRegistry',
        'implementingClassName' => 'Modules\\Promotion\\Application\\PromotionRegistry',
        'name' => 'ACTIONS_TAG',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\\Modules\\Promotion\\Contracts\\PromotionAction::TAG',
          'attributes' => 
          array (
            'startLine' => 19,
            'endLine' => 19,
            'startTokenPos' => 61,
            'startFilePos' => 540,
            'endTokenPos' => 63,
            'endFilePos' => 559,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 19,
        'endLine' => 19,
        'startColumn' => 5,
        'endColumn' => 52,
      ),
    ),
    'immediateProperties' => 
    array (
      'extensions' => 
      array (
        'declaringClassName' => 'Modules\\Promotion\\Application\\PromotionRegistry',
        'implementingClassName' => 'Modules\\Promotion\\Application\\PromotionRegistry',
        'name' => 'extensions',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Modules\\Extension\\Contracts\\Extensions',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 21,
        'endLine' => 21,
        'startColumn' => 33,
        'endColumn' => 71,
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
          'extensions' => 
          array (
            'name' => 'extensions',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Extension\\Contracts\\Extensions',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 21,
            'endLine' => 21,
            'startColumn' => 33,
            'endColumn' => 71,
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
        'startLine' => 21,
        'endLine' => 21,
        'startColumn' => 5,
        'endColumn' => 75,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Promotion\\Application',
        'declaringClassName' => 'Modules\\Promotion\\Application\\PromotionRegistry',
        'implementingClassName' => 'Modules\\Promotion\\Application\\PromotionRegistry',
        'currentClassName' => 'Modules\\Promotion\\Application\\PromotionRegistry',
        'aliasName' => NULL,
      ),
      'rule' => 
      array (
        'name' => 'rule',
        'parameters' => 
        array (
          'type' => 
          array (
            'name' => 'type',
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
            'startColumn' => 26,
            'endColumn' => 37,
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
                  'name' => 'Modules\\Promotion\\Contracts\\PromotionRule',
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
        'docComment' => NULL,
        'startLine' => 23,
        'endLine' => 26,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Promotion\\Application',
        'declaringClassName' => 'Modules\\Promotion\\Application\\PromotionRegistry',
        'implementingClassName' => 'Modules\\Promotion\\Application\\PromotionRegistry',
        'currentClassName' => 'Modules\\Promotion\\Application\\PromotionRegistry',
        'aliasName' => NULL,
      ),
      'action' => 
      array (
        'name' => 'action',
        'parameters' => 
        array (
          'type' => 
          array (
            'name' => 'type',
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
            'startLine' => 28,
            'endLine' => 28,
            'startColumn' => 28,
            'endColumn' => 39,
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
                  'name' => 'Modules\\Promotion\\Contracts\\PromotionAction',
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
        'docComment' => NULL,
        'startLine' => 28,
        'endLine' => 31,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Promotion\\Application',
        'declaringClassName' => 'Modules\\Promotion\\Application\\PromotionRegistry',
        'implementingClassName' => 'Modules\\Promotion\\Application\\PromotionRegistry',
        'currentClassName' => 'Modules\\Promotion\\Application\\PromotionRegistry',
        'aliasName' => NULL,
      ),
      'rules' => 
      array (
        'name' => 'rules',
        'parameters' => 
        array (
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
 * @return array<string, PromotionRule>
 */',
        'startLine' => 36,
        'endLine' => 39,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Promotion\\Application',
        'declaringClassName' => 'Modules\\Promotion\\Application\\PromotionRegistry',
        'implementingClassName' => 'Modules\\Promotion\\Application\\PromotionRegistry',
        'currentClassName' => 'Modules\\Promotion\\Application\\PromotionRegistry',
        'aliasName' => NULL,
      ),
      'actions' => 
      array (
        'name' => 'actions',
        'parameters' => 
        array (
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
 * @return array<string, PromotionAction>
 */',
        'startLine' => 44,
        'endLine' => 47,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Promotion\\Application',
        'declaringClassName' => 'Modules\\Promotion\\Application\\PromotionRegistry',
        'implementingClassName' => 'Modules\\Promotion\\Application\\PromotionRegistry',
        'currentClassName' => 'Modules\\Promotion\\Application\\PromotionRegistry',
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