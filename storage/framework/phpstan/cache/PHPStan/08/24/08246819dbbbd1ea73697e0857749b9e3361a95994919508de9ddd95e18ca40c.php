<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Shared/Persistence/Concerns/HasTranslations.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Shared\Persistence\Concerns\HasTranslations
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-37aa80d935cabda925a3b6244b2603ab4819d425c9eacd75ef7d9e4c1409fa03',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Shared\\Persistence\\Concerns\\HasTranslations',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Shared/Persistence/Concerns/HasTranslations.php',
      ),
    ),
    'namespace' => 'Modules\\Shared\\Persistence\\Concerns',
    'name' => 'Modules\\Shared\\Persistence\\Concerns\\HasTranslations',
    'shortName' => 'HasTranslations',
    'isInterface' => false,
    'isTrait' => true,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Nội dung đa ngôn ngữ lưu ở bảng <entity>_translations(…, locale, fields).
 * Đọc theo locale hiện tại, fallback về \'vi\' rồi bản dịch đầu tiên.
 *
 * Model dùng trait phải khai báo translationModel() và translatableFields().
 *
 * @mixin Model
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 18,
    'endLine' => 91,
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
      'FALLBACK_LOCALE' => 
      array (
        'declaringClassName' => 'Modules\\Shared\\Persistence\\Concerns\\HasTranslations',
        'implementingClassName' => 'Modules\\Shared\\Persistence\\Concerns\\HasTranslations',
        'name' => 'FALLBACK_LOCALE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'vi\'',
          'attributes' => 
          array (
            'startLine' => 20,
            'endLine' => 20,
            'startTokenPos' => 41,
            'startFilePos' => 524,
            'endTokenPos' => 41,
            'endFilePos' => 527,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 20,
        'endLine' => 20,
        'startColumn' => 5,
        'endColumn' => 40,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'translationModel' => 
      array (
        'name' => 'translationModel',
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
        'docComment' => '/**
 * @return class-string<Model>
 */',
        'startLine' => 25,
        'endLine' => 25,
        'startColumn' => 5,
        'endColumn' => 59,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 66,
        'namespace' => 'Modules\\Shared\\Persistence\\Concerns',
        'declaringClassName' => 'Modules\\Shared\\Persistence\\Concerns\\HasTranslations',
        'implementingClassName' => 'Modules\\Shared\\Persistence\\Concerns\\HasTranslations',
        'currentClassName' => 'Modules\\Shared\\Persistence\\Concerns\\HasTranslations',
        'aliasName' => NULL,
      ),
      'translatableFields' => 
      array (
        'name' => 'translatableFields',
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
 * @return list<string>
 */',
        'startLine' => 30,
        'endLine' => 30,
        'startColumn' => 5,
        'endColumn' => 57,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 65,
        'namespace' => 'Modules\\Shared\\Persistence\\Concerns',
        'declaringClassName' => 'Modules\\Shared\\Persistence\\Concerns\\HasTranslations',
        'implementingClassName' => 'Modules\\Shared\\Persistence\\Concerns\\HasTranslations',
        'currentClassName' => 'Modules\\Shared\\Persistence\\Concerns\\HasTranslations',
        'aliasName' => NULL,
      ),
      'translations' => 
      array (
        'name' => 'translations',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Illuminate\\Database\\Eloquent\\Relations\\HasMany',
            'isIdentifier' => false,
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
        'namespace' => 'Modules\\Shared\\Persistence\\Concerns',
        'declaringClassName' => 'Modules\\Shared\\Persistence\\Concerns\\HasTranslations',
        'implementingClassName' => 'Modules\\Shared\\Persistence\\Concerns\\HasTranslations',
        'currentClassName' => 'Modules\\Shared\\Persistence\\Concerns\\HasTranslations',
        'aliasName' => NULL,
      ),
      'translationForeignKey' => 
      array (
        'name' => 'translationForeignKey',
        'parameters' => 
        array (
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
                  'name' => 'string',
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
        'docComment' => '/**
 * Khoá ngoại trên bảng bản dịch; null = theo quy ước Laravel (<model>_id).
 */',
        'startLine' => 40,
        'endLine' => 43,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Modules\\Shared\\Persistence\\Concerns',
        'declaringClassName' => 'Modules\\Shared\\Persistence\\Concerns\\HasTranslations',
        'implementingClassName' => 'Modules\\Shared\\Persistence\\Concerns\\HasTranslations',
        'currentClassName' => 'Modules\\Shared\\Persistence\\Concerns\\HasTranslations',
        'aliasName' => NULL,
      ),
      'translate' => 
      array (
        'name' => 'translate',
        'parameters' => 
        array (
          'field' => 
          array (
            'name' => 'field',
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
            'startColumn' => 31,
            'endColumn' => 43,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'locale' => 
          array (
            'name' => 'locale',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 45,
                'endLine' => 45,
                'startTokenPos' => 153,
                'startFilePos' => 1155,
                'endTokenPos' => 153,
                'endFilePos' => 1158,
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
                      'name' => 'string',
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
            'startLine' => 45,
            'endLine' => 45,
            'startColumn' => 46,
            'endColumn' => 67,
            'parameterIndex' => 1,
            'isOptional' => true,
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
                  'name' => 'string',
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
        'startLine' => 45,
        'endLine' => 57,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Shared\\Persistence\\Concerns',
        'declaringClassName' => 'Modules\\Shared\\Persistence\\Concerns\\HasTranslations',
        'implementingClassName' => 'Modules\\Shared\\Persistence\\Concerns\\HasTranslations',
        'currentClassName' => 'Modules\\Shared\\Persistence\\Concerns\\HasTranslations',
        'aliasName' => NULL,
      ),
      'syncTranslations' => 
      array (
        'name' => 'syncTranslations',
        'parameters' => 
        array (
          'byLocale' => 
          array (
            'name' => 'byLocale',
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
            'startLine' => 64,
            'endLine' => 64,
            'startColumn' => 38,
            'endColumn' => 52,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Ghi đè toàn bộ bản dịch: locale không có trong $byLocale sẽ bị xoá.
 *
 * @param  array<string, array<string, mixed>>  $byLocale  locale => [field => value]
 */',
        'startLine' => 64,
        'endLine' => 78,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Shared\\Persistence\\Concerns',
        'declaringClassName' => 'Modules\\Shared\\Persistence\\Concerns\\HasTranslations',
        'implementingClassName' => 'Modules\\Shared\\Persistence\\Concerns\\HasTranslations',
        'currentClassName' => 'Modules\\Shared\\Persistence\\Concerns\\HasTranslations',
        'aliasName' => NULL,
      ),
      'translationsByLocale' => 
      array (
        'name' => 'translationsByLocale',
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
 * @return array<string, array<string, mixed>>
 */',
        'startLine' => 83,
        'endLine' => 90,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Shared\\Persistence\\Concerns',
        'declaringClassName' => 'Modules\\Shared\\Persistence\\Concerns\\HasTranslations',
        'implementingClassName' => 'Modules\\Shared\\Persistence\\Concerns\\HasTranslations',
        'currentClassName' => 'Modules\\Shared\\Persistence\\Concerns\\HasTranslations',
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