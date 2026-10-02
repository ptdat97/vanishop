<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Catalog/Application/Categories/CategoryTreeQuery.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Catalog\Application\Categories\CategoryTreeQuery
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-34fce0f9c7ffb0f11a9a6f0f6c7066688ebede23c7a26ba456693880c2613623',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Catalog\\Application\\Categories\\CategoryTreeQuery',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Catalog/Application/Categories/CategoryTreeQuery.php',
      ),
    ),
    'namespace' => 'Modules\\Catalog\\Application\\Categories',
    'name' => 'Modules\\Catalog\\Application\\Categories\\CategoryTreeQuery',
    'shortName' => 'CategoryTreeQuery',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Đọc cây danh mục của cửa hàng (1 truy vấn + eager load, dựng cây trong bộ nhớ).
 *
 * @phpstan-type CategoryNode array{id: int, parent_id: int|null, slug: string, name: string|null, description: string|null, status: string, position: int, depth: int, image_url: string|null, children: list<mixed>}
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 17,
    'endLine' => 87,
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
      'tree' => 
      array (
        'name' => 'tree',
        'parameters' => 
        array (
          'activeOnly' => 
          array (
            'name' => 'activeOnly',
            'default' => 
            array (
              'code' => 'false',
              'attributes' => 
              array (
                'startLine' => 22,
                'endLine' => 22,
                'startTokenPos' => 59,
                'startFilePos' => 716,
                'endTokenPos' => 59,
                'endFilePos' => 720,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'bool',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 22,
            'endLine' => 22,
            'startColumn' => 26,
            'endColumn' => 49,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
          'locale' => 
          array (
            'name' => 'locale',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 22,
                'endLine' => 22,
                'startTokenPos' => 69,
                'startFilePos' => 741,
                'endTokenPos' => 69,
                'endFilePos' => 744,
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
            'startLine' => 22,
            'endLine' => 22,
            'startColumn' => 52,
            'endColumn' => 73,
            'parameterIndex' => 1,
            'isOptional' => true,
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
 * @return list<CategoryNode>
 */',
        'startLine' => 22,
        'endLine' => 33,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Catalog\\Application\\Categories',
        'declaringClassName' => 'Modules\\Catalog\\Application\\Categories\\CategoryTreeQuery',
        'implementingClassName' => 'Modules\\Catalog\\Application\\Categories\\CategoryTreeQuery',
        'currentClassName' => 'Modules\\Catalog\\Application\\Categories\\CategoryTreeQuery',
        'aliasName' => NULL,
      ),
      'build' => 
      array (
        'name' => 'build',
        'parameters' => 
        array (
          'categories' => 
          array (
            'name' => 'categories',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Illuminate\\Support\\Collection',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 39,
            'endLine' => 39,
            'startColumn' => 28,
            'endColumn' => 49,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'locale' => 
          array (
            'name' => 'locale',
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
            'startLine' => 39,
            'endLine' => 39,
            'startColumn' => 52,
            'endColumn' => 66,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param  Collection<int, Category>  $categories
 * @return list<CategoryNode>
 */',
        'startLine' => 39,
        'endLine' => 64,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'Modules\\Catalog\\Application\\Categories',
        'declaringClassName' => 'Modules\\Catalog\\Application\\Categories\\CategoryTreeQuery',
        'implementingClassName' => 'Modules\\Catalog\\Application\\Categories\\CategoryTreeQuery',
        'currentClassName' => 'Modules\\Catalog\\Application\\Categories\\CategoryTreeQuery',
        'aliasName' => NULL,
      ),
      'node' => 
      array (
        'name' => 'node',
        'parameters' => 
        array (
          'category' => 
          array (
            'name' => 'category',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Catalog\\Persistence\\Models\\Category',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 69,
            'endLine' => 69,
            'startColumn' => 27,
            'endColumn' => 44,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'locale' => 
          array (
            'name' => 'locale',
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
            'startLine' => 69,
            'endLine' => 69,
            'startColumn' => 47,
            'endColumn' => 61,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @return CategoryNode
 */',
        'startLine' => 69,
        'endLine' => 86,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'Modules\\Catalog\\Application\\Categories',
        'declaringClassName' => 'Modules\\Catalog\\Application\\Categories\\CategoryTreeQuery',
        'implementingClassName' => 'Modules\\Catalog\\Application\\Categories\\CategoryTreeQuery',
        'currentClassName' => 'Modules\\Catalog\\Application\\Categories\\CategoryTreeQuery',
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