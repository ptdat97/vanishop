<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Catalog/Application/Media/ImageVariants.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Catalog\Application\Media\ImageVariants
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-a84425ade47a3377d97d477fddc36eb3401087705499f99f1503d742eee1a688',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Catalog\\Application\\Media\\ImageVariants',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Catalog/Application/Media/ImageVariants.php',
      ),
    ),
    'namespace' => 'Modules\\Catalog\\Application\\Media',
    'name' => 'Modules\\Catalog\\Application\\Media\\ImageVariants',
    'shortName' => 'ImageVariants',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Bản ảnh thu nhỏ WebP theo chiều rộng cố định (intervention/image, ADR-032) — tạo khi tải ảnh lên, đường dẫn suy ra
 * từ checksum + chiều rộng nên không cần cột DB. Ảnh gốc giữ nguyên. Lỗi xử lý ảnh không chặn việc tải lên (dùng gốc).
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 17,
    'endLine' => 67,
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
      'WIDTHS' => 
      array (
        'declaringClassName' => 'Modules\\Catalog\\Application\\Media\\ImageVariants',
        'implementingClassName' => 'Modules\\Catalog\\Application\\Media\\ImageVariants',
        'name' => 'WIDTHS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[400, 800, 1600]',
          'attributes' => 
          array (
            'startLine' => 20,
            'endLine' => 20,
            'startTokenPos' => 60,
            'startFilePos' => 725,
            'endTokenPos' => 68,
            'endFilePos' => 740,
          ),
        ),
        'docComment' => '/** Chiều rộng: thẻ sản phẩm (400), PDP mobile/desktop (800, 1600). */',
        'attributes' => 
        array (
        ),
        'startLine' => 20,
        'endLine' => 20,
        'startColumn' => 5,
        'endColumn' => 43,
      ),
      'QUALITY' => 
      array (
        'declaringClassName' => 'Modules\\Catalog\\Application\\Media\\ImageVariants',
        'implementingClassName' => 'Modules\\Catalog\\Application\\Media\\ImageVariants',
        'name' => 'QUALITY',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '80',
          'attributes' => 
          array (
            'startLine' => 22,
            'endLine' => 22,
            'startTokenPos' => 79,
            'startFilePos' => 772,
            'endTokenPos' => 79,
            'endFilePos' => 773,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 22,
        'endLine' => 22,
        'startColumn' => 5,
        'endColumn' => 31,
      ),
      'PROCESSABLE' => 
      array (
        'declaringClassName' => 'Modules\\Catalog\\Application\\Media\\ImageVariants',
        'implementingClassName' => 'Modules\\Catalog\\Application\\Media\\ImageVariants',
        'name' => 'PROCESSABLE',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'image/jpeg\', \'image/png\', \'image/webp\']',
          'attributes' => 
          array (
            'startLine' => 24,
            'endLine' => 24,
            'startTokenPos' => 90,
            'startFilePos' => 809,
            'endTokenPos' => 98,
            'endFilePos' => 849,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 24,
        'endLine' => 24,
        'startColumn' => 5,
        'endColumn' => 74,
      ),
    ),
    'immediateProperties' => 
    array (
      'images' => 
      array (
        'declaringClassName' => 'Modules\\Catalog\\Application\\Media\\ImageVariants',
        'implementingClassName' => 'Modules\\Catalog\\Application\\Media\\ImageVariants',
        'name' => 'images',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Intervention\\Image\\Interfaces\\ImageManagerInterface',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 26,
        'endLine' => 26,
        'startColumn' => 33,
        'endColumn' => 78,
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
          'images' => 
          array (
            'name' => 'images',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Intervention\\Image\\Interfaces\\ImageManagerInterface',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 26,
            'endLine' => 26,
            'startColumn' => 33,
            'endColumn' => 78,
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
        'startLine' => 26,
        'endLine' => 26,
        'startColumn' => 5,
        'endColumn' => 82,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Catalog\\Application\\Media',
        'declaringClassName' => 'Modules\\Catalog\\Application\\Media\\ImageVariants',
        'implementingClassName' => 'Modules\\Catalog\\Application\\Media\\ImageVariants',
        'currentClassName' => 'Modules\\Catalog\\Application\\Media\\ImageVariants',
        'aliasName' => NULL,
      ),
      'generate' => 
      array (
        'name' => 'generate',
        'parameters' => 
        array (
          'disk' => 
          array (
            'name' => 'disk',
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
            'startLine' => 31,
            'endLine' => 31,
            'startColumn' => 30,
            'endColumn' => 41,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'sourcePath' => 
          array (
            'name' => 'sourcePath',
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
            'startLine' => 31,
            'endLine' => 31,
            'startColumn' => 44,
            'endColumn' => 61,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'checksum' => 
          array (
            'name' => 'checksum',
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
            'startLine' => 31,
            'endLine' => 31,
            'startColumn' => 64,
            'endColumn' => 79,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'mimeType' => 
          array (
            'name' => 'mimeType',
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
            'startLine' => 31,
            'endLine' => 31,
            'startColumn' => 82,
            'endColumn' => 97,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'width' => 
          array (
            'name' => 'width',
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
                      'name' => 'int',
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
            'startLine' => 31,
            'endLine' => 31,
            'startColumn' => 100,
            'endColumn' => 110,
            'parameterIndex' => 4,
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
 * @return list<int> chiều rộng đã tạo
 */',
        'startLine' => 31,
        'endLine' => 51,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Catalog\\Application\\Media',
        'declaringClassName' => 'Modules\\Catalog\\Application\\Media\\ImageVariants',
        'implementingClassName' => 'Modules\\Catalog\\Application\\Media\\ImageVariants',
        'currentClassName' => 'Modules\\Catalog\\Application\\Media\\ImageVariants',
        'aliasName' => NULL,
      ),
      'widthsFor' => 
      array (
        'name' => 'widthsFor',
        'parameters' => 
        array (
          'width' => 
          array (
            'name' => 'width',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'int',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 58,
            'endLine' => 58,
            'startColumn' => 38,
            'endColumn' => 47,
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
 * Chiều rộng biến thể có cho ảnh rộng $width (chỉ thu nhỏ, không phóng to).
 *
 * @return list<int>
 */',
        'startLine' => 58,
        'endLine' => 61,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Modules\\Catalog\\Application\\Media',
        'declaringClassName' => 'Modules\\Catalog\\Application\\Media\\ImageVariants',
        'implementingClassName' => 'Modules\\Catalog\\Application\\Media\\ImageVariants',
        'currentClassName' => 'Modules\\Catalog\\Application\\Media\\ImageVariants',
        'aliasName' => NULL,
      ),
      'path' => 
      array (
        'name' => 'path',
        'parameters' => 
        array (
          'checksum' => 
          array (
            'name' => 'checksum',
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
            'startLine' => 63,
            'endLine' => 63,
            'startColumn' => 33,
            'endColumn' => 48,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'width' => 
          array (
            'name' => 'width',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'int',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 63,
            'endLine' => 63,
            'startColumn' => 51,
            'endColumn' => 60,
            'parameterIndex' => 1,
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
        'startLine' => 63,
        'endLine' => 66,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Modules\\Catalog\\Application\\Media',
        'declaringClassName' => 'Modules\\Catalog\\Application\\Media\\ImageVariants',
        'implementingClassName' => 'Modules\\Catalog\\Application\\Media\\ImageVariants',
        'currentClassName' => 'Modules\\Catalog\\Application\\Media\\ImageVariants',
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