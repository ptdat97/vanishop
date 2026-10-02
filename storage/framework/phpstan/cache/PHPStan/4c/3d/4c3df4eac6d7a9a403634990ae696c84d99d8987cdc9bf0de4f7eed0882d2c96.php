<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Storefront/Testing/StorefrontBlockContract.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Storefront\Testing\StorefrontBlockContract
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-bb94cc837a5efc07d36cc8576f6250407f0129ee464ace333bb42895565e74f0',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Storefront\\Testing\\StorefrontBlockContract',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Storefront/Testing/StorefrontBlockContract.php',
      ),
    ),
    'namespace' => 'Modules\\Storefront\\Testing',
    'name' => 'Modules\\Storefront\\Testing\\StorefrontBlockContract',
    'shortName' => 'StorefrontBlockContract',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Contract test cho StorefrontBlock: mã ổn định + nhãn; fields() là FieldDefinition không trùng khoá; cấu hình mẫu →
 * resolve trả mảng; view tồn tại và render được với dữ liệu đó.
 *
 *   StorefrontBlockContract::define(\'vani.lookbook\', fn () => app(LookbookBlock::class), config: [\'title\' => \'Hè\']);
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 17,
    'endLine' => 44,
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
            'startLine' => 23,
            'endLine' => 23,
            'startColumn' => 35,
            'endColumn' => 47,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'block' => 
          array (
            'name' => 'block',
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
            'startLine' => 23,
            'endLine' => 23,
            'startColumn' => 50,
            'endColumn' => 63,
            'parameterIndex' => 1,
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
            'startLine' => 23,
            'endLine' => 23,
            'startColumn' => 66,
            'endColumn' => 78,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param  Closure(): StorefrontBlock  $block
 * @param  array<string, mixed>  $config
 */',
        'startLine' => 23,
        'endLine' => 43,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Modules\\Storefront\\Testing',
        'declaringClassName' => 'Modules\\Storefront\\Testing\\StorefrontBlockContract',
        'implementingClassName' => 'Modules\\Storefront\\Testing\\StorefrontBlockContract',
        'currentClassName' => 'Modules\\Storefront\\Testing\\StorefrontBlockContract',
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