<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Inventory/Domain/StockLevel.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Inventory\Domain\StockLevel
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-39932cd7f00a929e838c38d69245cebae12e6ce8c0e6eff0006b271656242e57',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Inventory\\Domain\\StockLevel',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Inventory/Domain/StockLevel.php',
      ),
    ),
    'namespace' => 'Modules\\Inventory\\Domain',
    'name' => 'Modules\\Inventory\\Domain\\StockLevel',
    'shortName' => 'StockLevel',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 65568,
    'docComment' => '/**
 * Tồn của một variant tại một location. Bất biến, thuần PHP — mọi thay đổi trả về đối tượng mới.
 *
 *   available = on_hand − reserved − safety_stock   (có thể âm khi nguồn ngoài hạ on_hand)
 *   ATS       = max(0, available)
 *
 * @see docs/08-inventory/inventory.md
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 18,
    'endLine' => 129,
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
      'locationId' => 
      array (
        'declaringClassName' => 'Modules\\Inventory\\Domain\\StockLevel',
        'implementingClassName' => 'Modules\\Inventory\\Domain\\StockLevel',
        'name' => 'locationId',
        'modifiers' => 2049,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 21,
        'endLine' => 21,
        'startColumn' => 9,
        'endColumn' => 30,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'variantId' => 
      array (
        'declaringClassName' => 'Modules\\Inventory\\Domain\\StockLevel',
        'implementingClassName' => 'Modules\\Inventory\\Domain\\StockLevel',
        'name' => 'variantId',
        'modifiers' => 2049,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 22,
        'endLine' => 22,
        'startColumn' => 9,
        'endColumn' => 29,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'onHand' => 
      array (
        'declaringClassName' => 'Modules\\Inventory\\Domain\\StockLevel',
        'implementingClassName' => 'Modules\\Inventory\\Domain\\StockLevel',
        'name' => 'onHand',
        'modifiers' => 2049,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'default' => 
        array (
          'code' => '0',
          'attributes' => 
          array (
            'startLine' => 23,
            'endLine' => 23,
            'startTokenPos' => 66,
            'startFilePos' => 606,
            'endTokenPos' => 66,
            'endFilePos' => 606,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 23,
        'endLine' => 23,
        'startColumn' => 9,
        'endColumn' => 30,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'reserved' => 
      array (
        'declaringClassName' => 'Modules\\Inventory\\Domain\\StockLevel',
        'implementingClassName' => 'Modules\\Inventory\\Domain\\StockLevel',
        'name' => 'reserved',
        'modifiers' => 2049,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'default' => 
        array (
          'code' => '0',
          'attributes' => 
          array (
            'startLine' => 24,
            'endLine' => 24,
            'startTokenPos' => 77,
            'startFilePos' => 640,
            'endTokenPos' => 77,
            'endFilePos' => 640,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 24,
        'endLine' => 24,
        'startColumn' => 9,
        'endColumn' => 32,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'safetyStock' => 
      array (
        'declaringClassName' => 'Modules\\Inventory\\Domain\\StockLevel',
        'implementingClassName' => 'Modules\\Inventory\\Domain\\StockLevel',
        'name' => 'safetyStock',
        'modifiers' => 2049,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'int',
            'isIdentifier' => true,
          ),
        ),
        'default' => 
        array (
          'code' => '0',
          'attributes' => 
          array (
            'startLine' => 25,
            'endLine' => 25,
            'startTokenPos' => 88,
            'startFilePos' => 677,
            'endTokenPos' => 88,
            'endFilePos' => 677,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 25,
        'endLine' => 25,
        'startColumn' => 9,
        'endColumn' => 35,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'syncVersion' => 
      array (
        'declaringClassName' => 'Modules\\Inventory\\Domain\\StockLevel',
        'implementingClassName' => 'Modules\\Inventory\\Domain\\StockLevel',
        'name' => 'syncVersion',
        'modifiers' => 2049,
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
        'default' => 
        array (
          'code' => 'null',
          'attributes' => 
          array (
            'startLine' => 26,
            'endLine' => 26,
            'startTokenPos' => 100,
            'startFilePos' => 715,
            'endTokenPos' => 100,
            'endFilePos' => 718,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 26,
        'endLine' => 26,
        'startColumn' => 9,
        'endColumn' => 39,
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
          'locationId' => 
          array (
            'name' => 'locationId',
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
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 21,
            'endLine' => 21,
            'startColumn' => 9,
            'endColumn' => 30,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'variantId' => 
          array (
            'name' => 'variantId',
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
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 22,
            'endLine' => 22,
            'startColumn' => 9,
            'endColumn' => 29,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'onHand' => 
          array (
            'name' => 'onHand',
            'default' => 
            array (
              'code' => '0',
              'attributes' => 
              array (
                'startLine' => 23,
                'endLine' => 23,
                'startTokenPos' => 66,
                'startFilePos' => 606,
                'endTokenPos' => 66,
                'endFilePos' => 606,
              ),
            ),
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
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 23,
            'endLine' => 23,
            'startColumn' => 9,
            'endColumn' => 30,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
          'reserved' => 
          array (
            'name' => 'reserved',
            'default' => 
            array (
              'code' => '0',
              'attributes' => 
              array (
                'startLine' => 24,
                'endLine' => 24,
                'startTokenPos' => 77,
                'startFilePos' => 640,
                'endTokenPos' => 77,
                'endFilePos' => 640,
              ),
            ),
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
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 24,
            'endLine' => 24,
            'startColumn' => 9,
            'endColumn' => 32,
            'parameterIndex' => 3,
            'isOptional' => true,
          ),
          'safetyStock' => 
          array (
            'name' => 'safetyStock',
            'default' => 
            array (
              'code' => '0',
              'attributes' => 
              array (
                'startLine' => 25,
                'endLine' => 25,
                'startTokenPos' => 88,
                'startFilePos' => 677,
                'endTokenPos' => 88,
                'endFilePos' => 677,
              ),
            ),
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
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 25,
            'endLine' => 25,
            'startColumn' => 9,
            'endColumn' => 35,
            'parameterIndex' => 4,
            'isOptional' => true,
          ),
          'syncVersion' => 
          array (
            'name' => 'syncVersion',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 26,
                'endLine' => 26,
                'startTokenPos' => 100,
                'startFilePos' => 715,
                'endTokenPos' => 100,
                'endFilePos' => 718,
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
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 26,
            'endLine' => 26,
            'startColumn' => 9,
            'endColumn' => 39,
            'parameterIndex' => 5,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 20,
        'endLine' => 31,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Inventory\\Domain',
        'declaringClassName' => 'Modules\\Inventory\\Domain\\StockLevel',
        'implementingClassName' => 'Modules\\Inventory\\Domain\\StockLevel',
        'currentClassName' => 'Modules\\Inventory\\Domain\\StockLevel',
        'aliasName' => NULL,
      ),
      'available' => 
      array (
        'name' => 'available',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'int',
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
        'modifiers' => 1,
        'namespace' => 'Modules\\Inventory\\Domain',
        'declaringClassName' => 'Modules\\Inventory\\Domain\\StockLevel',
        'implementingClassName' => 'Modules\\Inventory\\Domain\\StockLevel',
        'currentClassName' => 'Modules\\Inventory\\Domain\\StockLevel',
        'aliasName' => NULL,
      ),
      'ats' => 
      array (
        'name' => 'ats',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'int',
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
        'namespace' => 'Modules\\Inventory\\Domain',
        'declaringClassName' => 'Modules\\Inventory\\Domain\\StockLevel',
        'implementingClassName' => 'Modules\\Inventory\\Domain\\StockLevel',
        'currentClassName' => 'Modules\\Inventory\\Domain\\StockLevel',
        'aliasName' => NULL,
      ),
      'reserve' => 
      array (
        'name' => 'reserve',
        'parameters' => 
        array (
          'quantity' => 
          array (
            'name' => 'quantity',
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
            'startLine' => 43,
            'endLine' => 43,
            'startColumn' => 29,
            'endColumn' => 41,
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
            'name' => 'self',
            'isIdentifier' => false,
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
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Inventory\\Domain',
        'declaringClassName' => 'Modules\\Inventory\\Domain\\StockLevel',
        'implementingClassName' => 'Modules\\Inventory\\Domain\\StockLevel',
        'currentClassName' => 'Modules\\Inventory\\Domain\\StockLevel',
        'aliasName' => NULL,
      ),
      'release' => 
      array (
        'name' => 'release',
        'parameters' => 
        array (
          'quantity' => 
          array (
            'name' => 'quantity',
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
            'startLine' => 54,
            'endLine' => 54,
            'startColumn' => 29,
            'endColumn' => 41,
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
            'name' => 'self',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 54,
        'endLine' => 63,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Inventory\\Domain',
        'declaringClassName' => 'Modules\\Inventory\\Domain\\StockLevel',
        'implementingClassName' => 'Modules\\Inventory\\Domain\\StockLevel',
        'currentClassName' => 'Modules\\Inventory\\Domain\\StockLevel',
        'aliasName' => NULL,
      ),
      'commit' => 
      array (
        'name' => 'commit',
        'parameters' => 
        array (
          'quantity' => 
          array (
            'name' => 'quantity',
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
            'startLine' => 69,
            'endLine' => 69,
            'startColumn' => 28,
            'endColumn' => 40,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'managesOnHand' => 
          array (
            'name' => 'managesOnHand',
            'default' => NULL,
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
            'startLine' => 69,
            'endLine' => 69,
            'startColumn' => 43,
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
            'name' => 'self',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Xuất kho: bỏ giữ hàng; nếu VaniShop là nguồn gốc on-hand của location thì trừ luôn on_hand.
 * Nếu nguồn gốc là hệ thống ngoài, on_hand giảm khi hệ thống đó gửi số mới (tránh trừ hai lần).
 */',
        'startLine' => 69,
        'endLine' => 74,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Inventory\\Domain',
        'declaringClassName' => 'Modules\\Inventory\\Domain\\StockLevel',
        'implementingClassName' => 'Modules\\Inventory\\Domain\\StockLevel',
        'currentClassName' => 'Modules\\Inventory\\Domain\\StockLevel',
        'aliasName' => NULL,
      ),
      'adjust' => 
      array (
        'name' => 'adjust',
        'parameters' => 
        array (
          'delta' => 
          array (
            'name' => 'delta',
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
            'startLine' => 79,
            'endLine' => 79,
            'startColumn' => 28,
            'endColumn' => 37,
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
            'name' => 'self',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Điều chỉnh tay (+ nhập, − hao hụt). on_hand không được âm do điều chỉnh tay.
 */',
        'startLine' => 79,
        'endLine' => 89,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Inventory\\Domain',
        'declaringClassName' => 'Modules\\Inventory\\Domain\\StockLevel',
        'implementingClassName' => 'Modules\\Inventory\\Domain\\StockLevel',
        'currentClassName' => 'Modules\\Inventory\\Domain\\StockLevel',
        'aliasName' => NULL,
      ),
      'sync' => 
      array (
        'name' => 'sync',
        'parameters' => 
        array (
          'onHand' => 
          array (
            'name' => 'onHand',
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
            'startLine' => 94,
            'endLine' => 94,
            'startColumn' => 26,
            'endColumn' => 36,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'version' => 
          array (
            'name' => 'version',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 94,
                'endLine' => 94,
                'startTokenPos' => 558,
                'startFilePos' => 2983,
                'endTokenPos' => 558,
                'endFilePos' => 2986,
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
            'startLine' => 94,
            'endLine' => 94,
            'startColumn' => 39,
            'endColumn' => 58,
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
                  'name' => 'self',
                  'isIdentifier' => false,
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
 * Đặt số tuyệt đối từ nguồn gốc (kiểm kê hoặc đồng bộ). Bản có version cũ hơn → null (bỏ qua).
 */',
        'startLine' => 94,
        'endLine' => 104,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Inventory\\Domain',
        'declaringClassName' => 'Modules\\Inventory\\Domain\\StockLevel',
        'implementingClassName' => 'Modules\\Inventory\\Domain\\StockLevel',
        'currentClassName' => 'Modules\\Inventory\\Domain\\StockLevel',
        'aliasName' => NULL,
      ),
      'withSafetyStock' => 
      array (
        'name' => 'withSafetyStock',
        'parameters' => 
        array (
          'safetyStock' => 
          array (
            'name' => 'safetyStock',
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
            'startLine' => 106,
            'endLine' => 106,
            'startColumn' => 37,
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
            'name' => 'self',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 106,
        'endLine' => 109,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Inventory\\Domain',
        'declaringClassName' => 'Modules\\Inventory\\Domain\\StockLevel',
        'implementingClassName' => 'Modules\\Inventory\\Domain\\StockLevel',
        'currentClassName' => 'Modules\\Inventory\\Domain\\StockLevel',
        'aliasName' => NULL,
      ),
      'with' => 
      array (
        'name' => 'with',
        'parameters' => 
        array (
          'onHand' => 
          array (
            'name' => 'onHand',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 111,
                'endLine' => 111,
                'startTokenPos' => 701,
                'startFilePos' => 3527,
                'endTokenPos' => 701,
                'endFilePos' => 3530,
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
            'startLine' => 111,
            'endLine' => 111,
            'startColumn' => 27,
            'endColumn' => 45,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
          'reserved' => 
          array (
            'name' => 'reserved',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 111,
                'endLine' => 111,
                'startTokenPos' => 711,
                'startFilePos' => 3550,
                'endTokenPos' => 711,
                'endFilePos' => 3553,
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
            'startLine' => 111,
            'endLine' => 111,
            'startColumn' => 48,
            'endColumn' => 68,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'safetyStock' => 
          array (
            'name' => 'safetyStock',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 111,
                'endLine' => 111,
                'startTokenPos' => 721,
                'startFilePos' => 3576,
                'endTokenPos' => 721,
                'endFilePos' => 3579,
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
            'startLine' => 111,
            'endLine' => 111,
            'startColumn' => 71,
            'endColumn' => 94,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
          'syncVersion' => 
          array (
            'name' => 'syncVersion',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 111,
                'endLine' => 111,
                'startTokenPos' => 731,
                'startFilePos' => 3602,
                'endTokenPos' => 731,
                'endFilePos' => 3605,
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
            'startLine' => 111,
            'endLine' => 111,
            'startColumn' => 97,
            'endColumn' => 120,
            'parameterIndex' => 3,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'self',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 111,
        'endLine' => 121,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'Modules\\Inventory\\Domain',
        'declaringClassName' => 'Modules\\Inventory\\Domain\\StockLevel',
        'implementingClassName' => 'Modules\\Inventory\\Domain\\StockLevel',
        'currentClassName' => 'Modules\\Inventory\\Domain\\StockLevel',
        'aliasName' => NULL,
      ),
      'assertPositive' => 
      array (
        'name' => 'assertPositive',
        'parameters' => 
        array (
          'quantity' => 
          array (
            'name' => 'quantity',
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
            'startLine' => 123,
            'endLine' => 123,
            'startColumn' => 37,
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
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 123,
        'endLine' => 128,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => true,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'Modules\\Inventory\\Domain',
        'declaringClassName' => 'Modules\\Inventory\\Domain\\StockLevel',
        'implementingClassName' => 'Modules\\Inventory\\Domain\\StockLevel',
        'currentClassName' => 'Modules\\Inventory\\Domain\\StockLevel',
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