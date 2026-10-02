<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Extension/Contracts/Requirement.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Extension\Contracts\Requirement
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-4ab54b145b6755919a7e4faa83c6e72e9b7509548577653bc536c33722bf5a9d',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Extension\\Contracts\\Requirement',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Extension/Contracts/Requirement.php',
      ),
    ),
    'namespace' => 'Modules\\Extension\\Contracts',
    'name' => 'Modules\\Extension\\Contracts\\Requirement',
    'shortName' => 'Requirement',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => true,
    'isBackedEnum' => true,
    'modifiers' => 0,
    'docComment' => '/**
 * Số implementation đang bật mà một extension point bắt buộc phải có để cửa hàng vận hành (ADR-029).
 *
 * Module sở hữu contract đăng ký ở ServiceProvider: `Extensions::requires(PaymentGateway::TAG, Requirement::AtLeastOne, \'…\')`.
 * Không đặt hằng trên interface: một class có thể implement nhiều contract (vd. GHN là carrier + nguồn phí giao).
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 13,
    'endLine' => 20,
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
      'name' => 
      array (
        'declaringClassName' => 'Modules\\Extension\\Contracts\\Requirement',
        'implementingClassName' => 'Modules\\Extension\\Contracts\\Requirement',
        'name' => 'name',
        'modifiers' => 2177,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => NULL,
        'endLine' => NULL,
        'startColumn' => -1,
        'endColumn' => -1,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'value' => 
      array (
        'declaringClassName' => 'Modules\\Extension\\Contracts\\Requirement',
        'implementingClassName' => 'Modules\\Extension\\Contracts\\Requirement',
        'name' => 'value',
        'modifiers' => 2177,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => NULL,
        'endLine' => NULL,
        'startColumn' => -1,
        'endColumn' => -1,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
    ),
    'immediateMethods' => 
    array (
      'cases' => 
      array (
        'name' => 'cases',
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
        'docComment' => NULL,
        'startLine' => NULL,
        'endLine' => NULL,
        'startColumn' => -1,
        'endColumn' => -1,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Modules\\Extension\\Contracts',
        'declaringClassName' => 'Modules\\Extension\\Contracts\\Requirement',
        'implementingClassName' => 'Modules\\Extension\\Contracts\\Requirement',
        'currentClassName' => 'Modules\\Extension\\Contracts\\Requirement',
        'aliasName' => NULL,
      ),
      'from' => 
      array (
        'name' => 'from',
        'parameters' => 
        array (
          'value' => 
          array (
            'name' => 'value',
            'default' => NULL,
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
                      'name' => 'string',
                      'isIdentifier' => true,
                    ),
                  ),
                  1 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'int',
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
            'startLine' => NULL,
            'endLine' => NULL,
            'startColumn' => -1,
            'endColumn' => -1,
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
            'name' => 'static',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => NULL,
        'endLine' => NULL,
        'startColumn' => -1,
        'endColumn' => -1,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Modules\\Extension\\Contracts',
        'declaringClassName' => 'Modules\\Extension\\Contracts\\Requirement',
        'implementingClassName' => 'Modules\\Extension\\Contracts\\Requirement',
        'currentClassName' => 'Modules\\Extension\\Contracts\\Requirement',
        'aliasName' => NULL,
      ),
      'tryFrom' => 
      array (
        'name' => 'tryFrom',
        'parameters' => 
        array (
          'value' => 
          array (
            'name' => 'value',
            'default' => NULL,
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
                      'name' => 'string',
                      'isIdentifier' => true,
                    ),
                  ),
                  1 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'int',
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
            'startLine' => NULL,
            'endLine' => NULL,
            'startColumn' => -1,
            'endColumn' => -1,
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
                  'name' => 'static',
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
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => NULL,
        'endLine' => NULL,
        'startColumn' => -1,
        'endColumn' => -1,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Modules\\Extension\\Contracts',
        'declaringClassName' => 'Modules\\Extension\\Contracts\\Requirement',
        'implementingClassName' => 'Modules\\Extension\\Contracts\\Requirement',
        'currentClassName' => 'Modules\\Extension\\Contracts\\Requirement',
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
    'backingType' => 
    array (
      'name' => 'string',
      'isIdentifier' => true,
    ),
    'cases' => 
    array (
      'AtLeastOne' => 
      array (
        'name' => 'AtLeastOne',
        'value' => 
        array (
          'code' => '\'at_least_one\'',
          'attributes' => 
          array (
            'startLine' => 16,
            'endLine' => 16,
            'startTokenPos' => 34,
            'startFilePos' => 623,
            'endTokenPos' => 34,
            'endFilePos' => 636,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** Ít nhất một implementation đang bật (vd. cổng thanh toán, phí giao). */',
        'startLine' => 16,
        'endLine' => 16,
        'startColumn' => 5,
        'endColumn' => 37,
      ),
      'ExactlyOne' => 
      array (
        'name' => 'ExactlyOne',
        'value' => 
        array (
          'code' => '\'exactly_one\'',
          'attributes' => 
          array (
            'startLine' => 19,
            'endLine' => 19,
            'startTokenPos' => 45,
            'startFilePos' => 798,
            'endTokenPos' => 45,
            'endFilePos' => 810,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** Đúng một implementation có hiệu lực — chọn qua cấu hình khi có nhiều (vd. TaxCalculator, SearchProvider). */',
        'startLine' => 19,
        'endLine' => 19,
        'startColumn' => 5,
        'endColumn' => 36,
      ),
    ),
  ),
));