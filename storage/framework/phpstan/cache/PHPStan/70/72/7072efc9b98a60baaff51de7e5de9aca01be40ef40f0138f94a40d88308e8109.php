<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Catalog/Persistence/Models/Variant.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Catalog\Persistence\Models\Variant
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-b6460431514a2863d38af54b73fb5ace2f1ab6808c9d95f04bd581d5ea0cccfe',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Catalog\\Persistence\\Models\\Variant',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Catalog/Persistence/Models/Variant.php',
      ),
    ),
    'namespace' => 'Modules\\Catalog\\Persistence\\Models',
    'name' => 'Modules\\Catalog\\Persistence\\Models\\Variant',
    'shortName' => 'Variant',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * @property int $id
 * @property int $style_id
 * @property int $style_color_id
 * @property int $size_id
 * @property string $sku
 * @property string|null $barcode
 * @property VariantStatus $status
 * @property int|null $weight_gram
 * @property int $lock_version
 * @property StyleColor $styleColor
 * @property Size $size
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 24,
    'endLine' => 56,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Illuminate\\Database\\Eloquent\\Model',
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
      'fillable' => 
      array (
        'declaringClassName' => 'Modules\\Catalog\\Persistence\\Models\\Variant',
        'implementingClassName' => 'Modules\\Catalog\\Persistence\\Models\\Variant',
        'name' => 'fillable',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'style_id\', \'style_color_id\', \'size_id\', \'sku\', \'barcode\', \'status\', \'weight_gram\', \'meta\', \'lock_version\']',
          'attributes' => 
          array (
            'startLine' => 26,
            'endLine' => 26,
            'startTokenPos' => 50,
            'startFilePos' => 614,
            'endTokenPos' => 76,
            'endFilePos' => 721,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 26,
        'endLine' => 26,
        'startColumn' => 5,
        'endColumn' => 135,
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
        'startLine' => 28,
        'endLine' => 31,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Modules\\Catalog\\Persistence\\Models',
        'declaringClassName' => 'Modules\\Catalog\\Persistence\\Models\\Variant',
        'implementingClassName' => 'Modules\\Catalog\\Persistence\\Models\\Variant',
        'currentClassName' => 'Modules\\Catalog\\Persistence\\Models\\Variant',
        'aliasName' => NULL,
      ),
      'style' => 
      array (
        'name' => 'style',
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
 * @return BelongsTo<Style, $this>
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
        'namespace' => 'Modules\\Catalog\\Persistence\\Models',
        'declaringClassName' => 'Modules\\Catalog\\Persistence\\Models\\Variant',
        'implementingClassName' => 'Modules\\Catalog\\Persistence\\Models\\Variant',
        'currentClassName' => 'Modules\\Catalog\\Persistence\\Models\\Variant',
        'aliasName' => NULL,
      ),
      'styleColor' => 
      array (
        'name' => 'styleColor',
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
 * @return BelongsTo<StyleColor, $this>
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
        'namespace' => 'Modules\\Catalog\\Persistence\\Models',
        'declaringClassName' => 'Modules\\Catalog\\Persistence\\Models\\Variant',
        'implementingClassName' => 'Modules\\Catalog\\Persistence\\Models\\Variant',
        'currentClassName' => 'Modules\\Catalog\\Persistence\\Models\\Variant',
        'aliasName' => NULL,
      ),
      'size' => 
      array (
        'name' => 'size',
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
 * @return BelongsTo<Size, $this>
 */',
        'startLine' => 52,
        'endLine' => 55,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Catalog\\Persistence\\Models',
        'declaringClassName' => 'Modules\\Catalog\\Persistence\\Models\\Variant',
        'implementingClassName' => 'Modules\\Catalog\\Persistence\\Models\\Variant',
        'currentClassName' => 'Modules\\Catalog\\Persistence\\Models\\Variant',
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