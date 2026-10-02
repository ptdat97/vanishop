<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Catalog/Persistence/Models/StyleAttributeValue.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Catalog\Persistence\Models\StyleAttributeValue
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-11a5675fc81d0caf1661a8a7fb05ff73b8ed169c8fe5796ef774793288f46610',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Catalog\\Persistence\\Models\\StyleAttributeValue',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Catalog/Persistence/Models/StyleAttributeValue.php',
      ),
    ),
    'namespace' => 'Modules\\Catalog\\Persistence\\Models',
    'name' => 'Modules\\Catalog\\Persistence\\Models\\StyleAttributeValue',
    'shortName' => 'StyleAttributeValue',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * @property int $style_id
 * @property int $attribute_id
 * @property int|null $attribute_value_id
 * @property string|null $value_text
 * @property bool|null $value_bool
 * @property Attribute $attribute
 * @property AttributeValue|null $value
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 19,
    'endLine' => 45,
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
      'timestamps' => 
      array (
        'declaringClassName' => 'Modules\\Catalog\\Persistence\\Models\\StyleAttributeValue',
        'implementingClassName' => 'Modules\\Catalog\\Persistence\\Models\\StyleAttributeValue',
        'name' => 'timestamps',
        'modifiers' => 1,
        'type' => NULL,
        'default' => 
        array (
          'code' => 'false',
          'attributes' => 
          array (
            'startLine' => 21,
            'endLine' => 21,
            'startTokenPos' => 45,
            'startFilePos' => 502,
            'endTokenPos' => 45,
            'endFilePos' => 506,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 21,
        'endLine' => 21,
        'startColumn' => 5,
        'endColumn' => 31,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'fillable' => 
      array (
        'declaringClassName' => 'Modules\\Catalog\\Persistence\\Models\\StyleAttributeValue',
        'implementingClassName' => 'Modules\\Catalog\\Persistence\\Models\\StyleAttributeValue',
        'name' => 'fillable',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'style_id\', \'attribute_id\', \'attribute_value_id\', \'value_text\', \'value_bool\']',
          'attributes' => 
          array (
            'startLine' => 23,
            'endLine' => 23,
            'startTokenPos' => 54,
            'startFilePos' => 536,
            'endTokenPos' => 68,
            'endFilePos' => 613,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 23,
        'endLine' => 23,
        'startColumn' => 5,
        'endColumn' => 105,
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
        'startLine' => 25,
        'endLine' => 28,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Modules\\Catalog\\Persistence\\Models',
        'declaringClassName' => 'Modules\\Catalog\\Persistence\\Models\\StyleAttributeValue',
        'implementingClassName' => 'Modules\\Catalog\\Persistence\\Models\\StyleAttributeValue',
        'currentClassName' => 'Modules\\Catalog\\Persistence\\Models\\StyleAttributeValue',
        'aliasName' => NULL,
      ),
      'attribute' => 
      array (
        'name' => 'attribute',
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
 * @return BelongsTo<Attribute, $this>
 */',
        'startLine' => 33,
        'endLine' => 36,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Catalog\\Persistence\\Models',
        'declaringClassName' => 'Modules\\Catalog\\Persistence\\Models\\StyleAttributeValue',
        'implementingClassName' => 'Modules\\Catalog\\Persistence\\Models\\StyleAttributeValue',
        'currentClassName' => 'Modules\\Catalog\\Persistence\\Models\\StyleAttributeValue',
        'aliasName' => NULL,
      ),
      'value' => 
      array (
        'name' => 'value',
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
 * @return BelongsTo<AttributeValue, $this>
 */',
        'startLine' => 41,
        'endLine' => 44,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Catalog\\Persistence\\Models',
        'declaringClassName' => 'Modules\\Catalog\\Persistence\\Models\\StyleAttributeValue',
        'implementingClassName' => 'Modules\\Catalog\\Persistence\\Models\\StyleAttributeValue',
        'currentClassName' => 'Modules\\Catalog\\Persistence\\Models\\StyleAttributeValue',
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