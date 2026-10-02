<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Catalog/Persistence/Models/AttributeValue.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Catalog\Persistence\Models\AttributeValue
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-877341e546287dbf927461827c954d70fa8e03ebb065adc73c1998c38ffb05dd',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Catalog\\Persistence\\Models\\AttributeValue',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Catalog/Persistence/Models/AttributeValue.php',
      ),
    ),
    'namespace' => 'Modules\\Catalog\\Persistence\\Models',
    'name' => 'Modules\\Catalog\\Persistence\\Models\\AttributeValue',
    'shortName' => 'AttributeValue',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Giá trị của thuộc tính kiểu select/multiselect.
 *
 * @property int $id
 * @property int $attribute_id
 * @property string $code
 * @property int $position
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 19,
    'endLine' => 42,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Illuminate\\Database\\Eloquent\\Model',
    'implementsClassNames' => 
    array (
    ),
    'traitClassNames' => 
    array (
      0 => 'Modules\\Shared\\Persistence\\Concerns\\HasTranslations',
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
      'fillable' => 
      array (
        'declaringClassName' => 'Modules\\Catalog\\Persistence\\Models\\AttributeValue',
        'implementingClassName' => 'Modules\\Catalog\\Persistence\\Models\\AttributeValue',
        'name' => 'fillable',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'attribute_id\', \'code\', \'position\']',
          'attributes' => 
          array (
            'startLine' => 23,
            'endLine' => 23,
            'startTokenPos' => 55,
            'startFilePos' => 504,
            'endTokenPos' => 63,
            'endFilePos' => 539,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 23,
        'endLine' => 23,
        'startColumn' => 5,
        'endColumn' => 63,
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
        'startLine' => 28,
        'endLine' => 31,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Catalog\\Persistence\\Models',
        'declaringClassName' => 'Modules\\Catalog\\Persistence\\Models\\AttributeValue',
        'implementingClassName' => 'Modules\\Catalog\\Persistence\\Models\\AttributeValue',
        'currentClassName' => 'Modules\\Catalog\\Persistence\\Models\\AttributeValue',
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
        'startLine' => 33,
        'endLine' => 36,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Modules\\Catalog\\Persistence\\Models',
        'declaringClassName' => 'Modules\\Catalog\\Persistence\\Models\\AttributeValue',
        'implementingClassName' => 'Modules\\Catalog\\Persistence\\Models\\AttributeValue',
        'currentClassName' => 'Modules\\Catalog\\Persistence\\Models\\AttributeValue',
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
        'startLine' => 38,
        'endLine' => 41,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Catalog\\Persistence\\Models',
        'declaringClassName' => 'Modules\\Catalog\\Persistence\\Models\\AttributeValue',
        'implementingClassName' => 'Modules\\Catalog\\Persistence\\Models\\AttributeValue',
        'currentClassName' => 'Modules\\Catalog\\Persistence\\Models\\AttributeValue',
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