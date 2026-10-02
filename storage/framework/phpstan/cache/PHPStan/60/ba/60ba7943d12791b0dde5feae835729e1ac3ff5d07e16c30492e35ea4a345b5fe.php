<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Catalog/Persistence/Models/Brand.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Catalog\Persistence\Models\Brand
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-31daae898a49af9f44ff4ee8e1cfb55d1dfe049488267782a423dd2f8cb24fd1',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Catalog\\Persistence\\Models\\Brand',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Catalog/Persistence/Models/Brand.php',
      ),
    ),
    'namespace' => 'Modules\\Catalog\\Persistence\\Models',
    'name' => 'Modules\\Catalog\\Persistence\\Models\\Brand',
    'shortName' => 'Brand',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Thương hiệu — thuộc tính của sản phẩm trong catalog (ADR-028), không phải phạm vi dữ liệu.
 *
 * @property int $id
 * @property string $code
 * @property string $slug
 * @property string $name
 * @property string|null $description
 * @property string|null $logo_path
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property string $status
 * @property int $position
 * @property int $lock_version
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 27,
    'endLine' => 55,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Illuminate\\Database\\Eloquent\\Model',
    'implementsClassNames' => 
    array (
    ),
    'traitClassNames' => 
    array (
      0 => 'Illuminate\\Database\\Eloquent\\Factories\\HasFactory',
    ),
    'immediateConstants' => 
    array (
      'ACTIVE' => 
      array (
        'declaringClassName' => 'Modules\\Catalog\\Persistence\\Models\\Brand',
        'implementingClassName' => 'Modules\\Catalog\\Persistence\\Models\\Brand',
        'name' => 'ACTIVE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'active\'',
          'attributes' => 
          array (
            'startLine' => 32,
            'endLine' => 32,
            'startTokenPos' => 64,
            'startFilePos' => 883,
            'endTokenPos' => 64,
            'endFilePos' => 890,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 32,
        'endLine' => 32,
        'startColumn' => 5,
        'endColumn' => 35,
      ),
      'HIDDEN' => 
      array (
        'declaringClassName' => 'Modules\\Catalog\\Persistence\\Models\\Brand',
        'implementingClassName' => 'Modules\\Catalog\\Persistence\\Models\\Brand',
        'name' => 'HIDDEN',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'hidden\'',
          'attributes' => 
          array (
            'startLine' => 34,
            'endLine' => 34,
            'startTokenPos' => 75,
            'startFilePos' => 920,
            'endTokenPos' => 75,
            'endFilePos' => 927,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 34,
        'endLine' => 34,
        'startColumn' => 5,
        'endColumn' => 35,
      ),
    ),
    'immediateProperties' => 
    array (
      'fillable' => 
      array (
        'declaringClassName' => 'Modules\\Catalog\\Persistence\\Models\\Brand',
        'implementingClassName' => 'Modules\\Catalog\\Persistence\\Models\\Brand',
        'name' => 'fillable',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'code\', \'slug\', \'name\', \'description\', \'logo_path\', \'meta_title\', \'meta_description\', \'status\', \'position\', \'lock_version\']',
          'attributes' => 
          array (
            'startLine' => 36,
            'endLine' => 36,
            'startTokenPos' => 84,
            'startFilePos' => 957,
            'endTokenPos' => 113,
            'endFilePos' => 1080,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 36,
        'endLine' => 36,
        'startColumn' => 5,
        'endColumn' => 151,
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
        'startLine' => 38,
        'endLine' => 41,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Modules\\Catalog\\Persistence\\Models',
        'declaringClassName' => 'Modules\\Catalog\\Persistence\\Models\\Brand',
        'implementingClassName' => 'Modules\\Catalog\\Persistence\\Models\\Brand',
        'currentClassName' => 'Modules\\Catalog\\Persistence\\Models\\Brand',
        'aliasName' => NULL,
      ),
      'styles' => 
      array (
        'name' => 'styles',
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
 * @return HasMany<Style, $this>
 */',
        'startLine' => 46,
        'endLine' => 49,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Catalog\\Persistence\\Models',
        'declaringClassName' => 'Modules\\Catalog\\Persistence\\Models\\Brand',
        'implementingClassName' => 'Modules\\Catalog\\Persistence\\Models\\Brand',
        'currentClassName' => 'Modules\\Catalog\\Persistence\\Models\\Brand',
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
            'name' => 'Modules\\Catalog\\Persistence\\Database\\Factories\\BrandFactory',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 51,
        'endLine' => 54,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 18,
        'namespace' => 'Modules\\Catalog\\Persistence\\Models',
        'declaringClassName' => 'Modules\\Catalog\\Persistence\\Models\\Brand',
        'implementingClassName' => 'Modules\\Catalog\\Persistence\\Models\\Brand',
        'currentClassName' => 'Modules\\Catalog\\Persistence\\Models\\Brand',
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