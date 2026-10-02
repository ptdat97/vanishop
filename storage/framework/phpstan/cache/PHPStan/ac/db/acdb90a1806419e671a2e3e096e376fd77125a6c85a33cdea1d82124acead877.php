<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Shared/Domain/Text/VietnameseText.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Shared\Domain\Text\VietnameseText
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-357996288c854f009dc3e24737ba18815a0190c8c09b5b11e23caa8d2c64e458',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Shared\\Domain\\Text\\VietnameseText',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Shared/Domain/Text/VietnameseText.php',
      ),
    ),
    'namespace' => 'Modules\\Shared\\Domain\\Text',
    'name' => 'Modules\\Shared\\Domain\\Text\\VietnameseText',
    'shortName' => 'VietnameseText',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Chuẩn hoá văn bản tiếng Việt để tìm kiếm: chữ thường, bỏ dấu, "đ" → "d", gộp khoảng trắng.
 * "Áo Sơ Mi Lụa ĐEN" → "ao so mi lua den".
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 13,
    'endLine' => 51,
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
      'normalize' => 
      array (
        'name' => 'normalize',
        'parameters' => 
        array (
          'text' => 
          array (
            'name' => 'text',
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
            'startLine' => 15,
            'endLine' => 15,
            'startColumn' => 38,
            'endColumn' => 49,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 15,
        'endLine' => 26,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Modules\\Shared\\Domain\\Text',
        'declaringClassName' => 'Modules\\Shared\\Domain\\Text\\VietnameseText',
        'implementingClassName' => 'Modules\\Shared\\Domain\\Text\\VietnameseText',
        'currentClassName' => 'Modules\\Shared\\Domain\\Text\\VietnameseText',
        'aliasName' => NULL,
      ),
      'stripDiacritics' => 
      array (
        'name' => 'stripDiacritics',
        'parameters' => 
        array (
          'text' => 
          array (
            'name' => 'text',
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
            'startLine' => 32,
            'endLine' => 32,
            'startColumn' => 44,
            'endColumn' => 55,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Bỏ dấu nhưng giữ hoa/thường và dấu câu (SMS brandname thường yêu cầu nội dung không dấu).
 * "Đơn hàng LU-01 đã giao!" → "Don hang LU-01 da giao!"
 */',
        'startLine' => 32,
        'endLine' => 38,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Modules\\Shared\\Domain\\Text',
        'declaringClassName' => 'Modules\\Shared\\Domain\\Text\\VietnameseText',
        'implementingClassName' => 'Modules\\Shared\\Domain\\Text\\VietnameseText',
        'currentClassName' => 'Modules\\Shared\\Domain\\Text\\VietnameseText',
        'aliasName' => NULL,
      ),
      'tokens' => 
      array (
        'name' => 'tokens',
        'parameters' => 
        array (
          'text' => 
          array (
            'name' => 'text',
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
            'startLine' => 45,
            'endLine' => 45,
            'startColumn' => 35,
            'endColumn' => 46,
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
        'docComment' => '/**
 * Các từ khoá đã chuẩn hoá, bỏ trùng.
 *
 * @return list<string>
 */',
        'startLine' => 45,
        'endLine' => 50,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Modules\\Shared\\Domain\\Text',
        'declaringClassName' => 'Modules\\Shared\\Domain\\Text\\VietnameseText',
        'implementingClassName' => 'Modules\\Shared\\Domain\\Text\\VietnameseText',
        'currentClassName' => 'Modules\\Shared\\Domain\\Text\\VietnameseText',
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