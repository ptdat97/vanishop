<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Cart/Console/DetectAbandonedCartsCommand.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Cart\Console\DetectAbandonedCartsCommand
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-87b4bd987c7f27fe6e4dd15b6b6e52dca99fa7b0e75bcb18939f92eadf8aca20',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Cart\\Console\\DetectAbandonedCartsCommand',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Cart/Console/DetectAbandonedCartsCommand.php',
      ),
    ),
    'namespace' => 'Modules\\Cart\\Console',
    'name' => 'Modules\\Cart\\Console\\DetectAbandonedCartsCommand',
    'shortName' => 'DetectAbandonedCartsCommand',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Phát CartAbandoned cho giỏ của khách còn hàng, không hoạt động quá ngưỡng (chạy 5 phút/lần).
 * Giỏ vãng lai không có thông tin liên hệ → bỏ qua.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 17,
    'endLine' => 70,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Illuminate\\Console\\Command',
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
      'signature' => 
      array (
        'declaringClassName' => 'Modules\\Cart\\Console\\DetectAbandonedCartsCommand',
        'implementingClassName' => 'Modules\\Cart\\Console\\DetectAbandonedCartsCommand',
        'name' => 'signature',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'vani:cart:detect-abandoned {--minutes= : Số phút không hoạt động (mặc định theo cấu hình)}\'',
          'attributes' => 
          array (
            'startLine' => 19,
            'endLine' => 19,
            'startTokenPos' => 60,
            'startFilePos' => 529,
            'endTokenPos' => 60,
            'endFilePos' => 637,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 19,
        'endLine' => 19,
        'startColumn' => 5,
        'endColumn' => 137,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'description' => 
      array (
        'declaringClassName' => 'Modules\\Cart\\Console\\DetectAbandonedCartsCommand',
        'implementingClassName' => 'Modules\\Cart\\Console\\DetectAbandonedCartsCommand',
        'name' => 'description',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'Phát sự kiện giỏ hàng bị bỏ quên cho plugin (nhắc giỏ hàng)\'',
          'attributes' => 
          array (
            'startLine' => 21,
            'endLine' => 21,
            'startTokenPos' => 69,
            'startFilePos' => 670,
            'endTokenPos' => 69,
            'endFilePos' => 748,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 21,
        'endLine' => 21,
        'startColumn' => 5,
        'endColumn' => 109,
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
      'handle' => 
      array (
        'name' => 'handle',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 23,
        'endLine' => 43,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Cart\\Console',
        'declaringClassName' => 'Modules\\Cart\\Console\\DetectAbandonedCartsCommand',
        'implementingClassName' => 'Modules\\Cart\\Console\\DetectAbandonedCartsCommand',
        'currentClassName' => 'Modules\\Cart\\Console\\DetectAbandonedCartsCommand',
        'aliasName' => NULL,
      ),
      'notify' => 
      array (
        'name' => 'notify',
        'parameters' => 
        array (
          'cart' => 
          array (
            'name' => 'cart',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Cart\\Persistence\\Models\\Cart',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 45,
            'endLine' => 45,
            'startColumn' => 29,
            'endColumn' => 38,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 45,
        'endLine' => 69,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'Modules\\Cart\\Console',
        'declaringClassName' => 'Modules\\Cart\\Console\\DetectAbandonedCartsCommand',
        'implementingClassName' => 'Modules\\Cart\\Console\\DetectAbandonedCartsCommand',
        'currentClassName' => 'Modules\\Cart\\Console\\DetectAbandonedCartsCommand',
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