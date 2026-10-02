<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Catalog/Persistence/Models/Style.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Catalog\Persistence\Models\Style
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-34c3a96f424af9c6ac0687956e530e3d84b708bfcfed342fb86061b325fa87e2',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Catalog\\Persistence\\Models\\Style',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Catalog/Persistence/Models/Style.php',
      ),
    ),
    'namespace' => 'Modules\\Catalog\\Persistence\\Models',
    'name' => 'Modules\\Catalog\\Persistence\\Models\\Style',
    'shortName' => 'Style',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Style = sản phẩm khách nhìn thấy (một trang PDP). Biến thể màu × size ở StyleColor / Variant.
 *
 * @property int $id
 * @property int|null $brand_id
 * @property string $style_code
 * @property string $slug
 * @property StyleStatus $status
 * @property Carbon|null $published_from
 * @property Carbon|null $published_to
 * @property int|null $primary_category_id
 * @property string $search_text
 * @property array<string, mixed>|null $meta
 * @property int $lock_version
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 34,
    'endLine' => 132,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Illuminate\\Database\\Eloquent\\Model',
    'implementsClassNames' => 
    array (
    ),
    'traitClassNames' => 
    array (
      0 => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
      1 => 'Modules\\Shared\\Persistence\\Concerns\\HasTranslations',
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
      'fillable' => 
      array (
        'declaringClassName' => 'Modules\\Catalog\\Persistence\\Models\\Style',
        'implementingClassName' => 'Modules\\Catalog\\Persistence\\Models\\Style',
        'name' => 'fillable',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'brand_id\', \'style_code\', \'slug\', \'status\', \'published_from\', \'published_to\', \'primary_category_id\', \'search_text\', \'meta\', \'lock_version\']',
          'attributes' => 
          array (
            'startLine' => 41,
            'endLine' => 41,
            'startTokenPos' => 102,
            'startFilePos' => 1248,
            'endTokenPos' => 131,
            'endFilePos' => 1387,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 41,
        'endLine' => 41,
        'startColumn' => 5,
        'endColumn' => 167,
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
      'casts' => 
      array (
        'name' => 'casts',
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
        'startLine' => 43,
        'endLine' => 52,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Modules\\Catalog\\Persistence\\Models',
        'declaringClassName' => 'Modules\\Catalog\\Persistence\\Models\\Style',
        'implementingClassName' => 'Modules\\Catalog\\Persistence\\Models\\Style',
        'currentClassName' => 'Modules\\Catalog\\Persistence\\Models\\Style',
        'aliasName' => NULL,
      ),
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
        'docComment' => NULL,
        'startLine' => 54,
        'endLine' => 57,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Modules\\Catalog\\Persistence\\Models',
        'declaringClassName' => 'Modules\\Catalog\\Persistence\\Models\\Style',
        'implementingClassName' => 'Modules\\Catalog\\Persistence\\Models\\Style',
        'currentClassName' => 'Modules\\Catalog\\Persistence\\Models\\Style',
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
        'docComment' => NULL,
        'startLine' => 59,
        'endLine' => 62,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Catalog\\Persistence\\Models',
        'declaringClassName' => 'Modules\\Catalog\\Persistence\\Models\\Style',
        'implementingClassName' => 'Modules\\Catalog\\Persistence\\Models\\Style',
        'currentClassName' => 'Modules\\Catalog\\Persistence\\Models\\Style',
        'aliasName' => NULL,
      ),
      'publishWindow' => 
      array (
        'name' => 'publishWindow',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Modules\\Catalog\\Domain\\PublishWindow',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 64,
        'endLine' => 70,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Catalog\\Persistence\\Models',
        'declaringClassName' => 'Modules\\Catalog\\Persistence\\Models\\Style',
        'implementingClassName' => 'Modules\\Catalog\\Persistence\\Models\\Style',
        'currentClassName' => 'Modules\\Catalog\\Persistence\\Models\\Style',
        'aliasName' => NULL,
      ),
      'brand' => 
      array (
        'name' => 'brand',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Illuminate\\Database\\Eloquent\\Relations\\BelongsTo',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return BelongsTo<Brand, $this>
 */',
        'startLine' => 75,
        'endLine' => 78,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Catalog\\Persistence\\Models',
        'declaringClassName' => 'Modules\\Catalog\\Persistence\\Models\\Style',
        'implementingClassName' => 'Modules\\Catalog\\Persistence\\Models\\Style',
        'currentClassName' => 'Modules\\Catalog\\Persistence\\Models\\Style',
        'aliasName' => NULL,
      ),
      'colors' => 
      array (
        'name' => 'colors',
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
        'docComment' => '/**
 * @return HasMany<StyleColor, $this>
 */',
        'startLine' => 83,
        'endLine' => 86,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Catalog\\Persistence\\Models',
        'declaringClassName' => 'Modules\\Catalog\\Persistence\\Models\\Style',
        'implementingClassName' => 'Modules\\Catalog\\Persistence\\Models\\Style',
        'currentClassName' => 'Modules\\Catalog\\Persistence\\Models\\Style',
        'aliasName' => NULL,
      ),
      'variants' => 
      array (
        'name' => 'variants',
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
        'docComment' => '/**
 * @return HasMany<Variant, $this>
 */',
        'startLine' => 91,
        'endLine' => 94,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Catalog\\Persistence\\Models',
        'declaringClassName' => 'Modules\\Catalog\\Persistence\\Models\\Style',
        'implementingClassName' => 'Modules\\Catalog\\Persistence\\Models\\Style',
        'currentClassName' => 'Modules\\Catalog\\Persistence\\Models\\Style',
        'aliasName' => NULL,
      ),
      'categories' => 
      array (
        'name' => 'categories',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Illuminate\\Database\\Eloquent\\Relations\\BelongsToMany',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return BelongsToMany<Category, $this>
 */',
        'startLine' => 99,
        'endLine' => 102,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Catalog\\Persistence\\Models',
        'declaringClassName' => 'Modules\\Catalog\\Persistence\\Models\\Style',
        'implementingClassName' => 'Modules\\Catalog\\Persistence\\Models\\Style',
        'currentClassName' => 'Modules\\Catalog\\Persistence\\Models\\Style',
        'aliasName' => NULL,
      ),
      'primaryCategory' => 
      array (
        'name' => 'primaryCategory',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Illuminate\\Database\\Eloquent\\Relations\\BelongsTo',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return BelongsTo<Category, $this>
 */',
        'startLine' => 107,
        'endLine' => 110,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Catalog\\Persistence\\Models',
        'declaringClassName' => 'Modules\\Catalog\\Persistence\\Models\\Style',
        'implementingClassName' => 'Modules\\Catalog\\Persistence\\Models\\Style',
        'currentClassName' => 'Modules\\Catalog\\Persistence\\Models\\Style',
        'aliasName' => NULL,
      ),
      'attributeValues' => 
      array (
        'name' => 'attributeValues',
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
        'docComment' => '/**
 * @return HasMany<StyleAttributeValue, $this>
 */',
        'startLine' => 115,
        'endLine' => 118,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Catalog\\Persistence\\Models',
        'declaringClassName' => 'Modules\\Catalog\\Persistence\\Models\\Style',
        'implementingClassName' => 'Modules\\Catalog\\Persistence\\Models\\Style',
        'currentClassName' => 'Modules\\Catalog\\Persistence\\Models\\Style',
        'aliasName' => NULL,
      ),
      'collections' => 
      array (
        'name' => 'collections',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Illuminate\\Database\\Eloquent\\Relations\\BelongsToMany',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return BelongsToMany<ProductCollection, $this>
 */',
        'startLine' => 123,
        'endLine' => 126,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Catalog\\Persistence\\Models',
        'declaringClassName' => 'Modules\\Catalog\\Persistence\\Models\\Style',
        'implementingClassName' => 'Modules\\Catalog\\Persistence\\Models\\Style',
        'currentClassName' => 'Modules\\Catalog\\Persistence\\Models\\Style',
        'aliasName' => NULL,
      ),
      'newFactory' => 
      array (
        'name' => 'newFactory',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Modules\\Catalog\\Persistence\\Database\\Factories\\StyleFactory',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 128,
        'endLine' => 131,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 18,
        'namespace' => 'Modules\\Catalog\\Persistence\\Models',
        'declaringClassName' => 'Modules\\Catalog\\Persistence\\Models\\Style',
        'implementingClassName' => 'Modules\\Catalog\\Persistence\\Models\\Style',
        'currentClassName' => 'Modules\\Catalog\\Persistence\\Models\\Style',
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