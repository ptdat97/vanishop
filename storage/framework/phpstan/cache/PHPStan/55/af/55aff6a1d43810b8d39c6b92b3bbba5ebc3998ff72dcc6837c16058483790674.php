<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Catalog/Persistence/Models/Mediable.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Catalog\Persistence\Models\Mediable
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-7de535a3493569cf2648b711f1e558cbfd65bafffd14416ba8190ce7fef36a17',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Catalog\\Persistence\\Models\\Mediable',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Catalog/Persistence/Models/Mediable.php',
      ),
    ),
    'namespace' => 'Modules\\Catalog\\Persistence\\Models',
    'name' => 'Modules\\Catalog\\Persistence\\Models\\Mediable',
    'shortName' => 'Mediable',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * @property int $id
 * @property int $media_id
 * @property string $role
 * @property int $position
 * @property string|null $alt
 * @property Media $media
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 18,
    'endLine' => 29,
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
        'declaringClassName' => 'Modules\\Catalog\\Persistence\\Models\\Mediable',
        'implementingClassName' => 'Modules\\Catalog\\Persistence\\Models\\Mediable',
        'name' => 'fillable',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[\'media_id\', \'mediable_type\', \'mediable_id\', \'role\', \'position\', \'alt\']',
          'attributes' => 
          array (
            'startLine' => 20,
            'endLine' => 20,
            'startTokenPos' => 45,
            'startFilePos' => 403,
            'endTokenPos' => 62,
            'endFilePos' => 473,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 20,
        'endLine' => 20,
        'startColumn' => 5,
        'endColumn' => 98,
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
      'media' => 
      array (
        'name' => 'media',
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
 * @return BelongsTo<Media, $this>
 */',
        'startLine' => 25,
        'endLine' => 28,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Catalog\\Persistence\\Models',
        'declaringClassName' => 'Modules\\Catalog\\Persistence\\Models\\Mediable',
        'implementingClassName' => 'Modules\\Catalog\\Persistence\\Models\\Mediable',
        'currentClassName' => 'Modules\\Catalog\\Persistence\\Models\\Mediable',
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