<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Customer/Testing/AuthProviderContract.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Customer\Testing\AuthProviderContract
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-6287945be32aab6204f385d6f45f62400979e5dc045ecf940b5d343ff317501e',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Customer\\Testing\\AuthProviderContract',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Customer/Testing/AuthProviderContract.php',
      ),
    ),
    'namespace' => 'Modules\\Customer\\Testing',
    'name' => 'Modules\\Customer\\Testing\\AuthProviderContract',
    'shortName' => 'AuthProviderContract',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Contract test cho AuthProvider: mã ổn định + nhãn; URL mang `state`; tham số hợp lệ → danh tính đúng nhà cung cấp,
 * subject ổn định; tham số sai → AuthProviderFailed (không ném lỗi khác).
 *
 *   AuthProviderContract::define(\'vani.zalo-login\', fn () => app(ZaloAuthProvider::class),
 *       validParams: fn () => [\'code\' => \'ok\'], invalidParams: fn () => [\'code\' => \'expired\']);
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 19,
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
            'startLine' => 26,
            'endLine' => 26,
            'startColumn' => 35,
            'endColumn' => 47,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'provider' => 
          array (
            'name' => 'provider',
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
            'startLine' => 26,
            'endLine' => 26,
            'startColumn' => 50,
            'endColumn' => 66,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'validParams' => 
          array (
            'name' => 'validParams',
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
            'startLine' => 26,
            'endLine' => 26,
            'startColumn' => 69,
            'endColumn' => 88,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'invalidParams' => 
          array (
            'name' => 'invalidParams',
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
            'startLine' => 26,
            'endLine' => 26,
            'startColumn' => 91,
            'endColumn' => 112,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param  Closure(): AuthProvider  $provider
 * @param  Closure(): array<string, string>  $validParams  (có thể giả lập HTTP trước khi trả)
 * @param  Closure(): array<string, string>  $invalidParams
 */',
        'startLine' => 26,
        'endLine' => 48,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Modules\\Customer\\Testing',
        'declaringClassName' => 'Modules\\Customer\\Testing\\AuthProviderContract',
        'implementingClassName' => 'Modules\\Customer\\Testing\\AuthProviderContract',
        'currentClassName' => 'Modules\\Customer\\Testing\\AuthProviderContract',
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