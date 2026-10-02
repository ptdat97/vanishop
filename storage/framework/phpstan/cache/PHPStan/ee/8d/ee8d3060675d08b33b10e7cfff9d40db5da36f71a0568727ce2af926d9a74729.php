<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Promotion/Testing/PromotionRuleContract.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Promotion\Testing\PromotionRuleContract
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-de01f87f6a73f7fada08e016a642eb89cca70dd0f5d505e512cb383650d495b8',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Promotion\\Testing\\PromotionRuleContract',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Promotion/Testing/PromotionRuleContract.php',
      ),
    ),
    'namespace' => 'Modules\\Promotion\\Testing',
    'name' => 'Modules\\Promotion\\Testing\\PromotionRuleContract',
    'shortName' => 'PromotionRuleContract',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Contract test cho PromotionRule. Kiểm tra: type ổn định + nhãn; cấu hình hợp lệ không lỗi, cấu hình sai có lỗi;
 * rule chỉ THU HẸP tập dòng đủ điều kiện (không thêm dòng); không có dòng ứng viên → không có dòng; xác định.
 *
 *   PromotionRuleContract::define(\'min_quantity\', fn () => new MinQuantityRule, valid: [\'min\' => 2], invalid: [\'min\' => -1]);
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 22,
    'endLine' => 64,
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
            'startLine' => 30,
            'endLine' => 30,
            'startColumn' => 35,
            'endColumn' => 47,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'rule' => 
          array (
            'name' => 'rule',
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
            'startLine' => 30,
            'endLine' => 30,
            'startColumn' => 50,
            'endColumn' => 62,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'valid' => 
          array (
            'name' => 'valid',
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
            'startColumn' => 65,
            'endColumn' => 76,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'invalid' => 
          array (
            'name' => 'invalid',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 30,
                'endLine' => 30,
                'startTokenPos' => 97,
                'startFilePos' => 1183,
                'endTokenPos' => 97,
                'endFilePos' => 1186,
              ),
            ),
            'type' => 
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
                      'name' => 'array',
                      'isIdentifier' => true,
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
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 30,
            'endLine' => 30,
            'startColumn' => 79,
            'endColumn' => 100,
            'parameterIndex' => 3,
            'isOptional' => true,
          ),
          'context' => 
          array (
            'name' => 'context',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 30,
                'endLine' => 30,
                'startTokenPos' => 107,
                'startFilePos' => 1209,
                'endTokenPos' => 107,
                'endFilePos' => 1212,
              ),
            ),
            'type' => 
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
                      'name' => 'Closure',
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
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 30,
            'endLine' => 30,
            'startColumn' => 103,
            'endColumn' => 126,
            'parameterIndex' => 4,
            'isOptional' => true,
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
 * @param  Closure(): PromotionRule  $rule
 * @param  array<string, mixed>  $valid
 * @param  array<string, mixed>|null  $invalid
 * @param  (Closure(): PromotionContext)|null  $context
 */',
        'startLine' => 30,
        'endLine' => 56,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Modules\\Promotion\\Testing',
        'declaringClassName' => 'Modules\\Promotion\\Testing\\PromotionRuleContract',
        'implementingClassName' => 'Modules\\Promotion\\Testing\\PromotionRuleContract',
        'currentClassName' => 'Modules\\Promotion\\Testing\\PromotionRuleContract',
        'aliasName' => NULL,
      ),
      'sampleContext' => 
      array (
        'name' => 'sampleContext',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Modules\\Promotion\\Contracts\\Data\\PromotionContext',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 58,
        'endLine' => 63,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Modules\\Promotion\\Testing',
        'declaringClassName' => 'Modules\\Promotion\\Testing\\PromotionRuleContract',
        'implementingClassName' => 'Modules\\Promotion\\Testing\\PromotionRuleContract',
        'currentClassName' => 'Modules\\Promotion\\Testing\\PromotionRuleContract',
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