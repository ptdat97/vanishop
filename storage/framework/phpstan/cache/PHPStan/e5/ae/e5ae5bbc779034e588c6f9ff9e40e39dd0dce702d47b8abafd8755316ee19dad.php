<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Returns/Domain/ReturnStatus.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Returns\Domain\ReturnStatus
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-5b9963f7be7c1b9482e57d8d35b79eac2df77f3073b3e38e300acf3f5ffb7fb9',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Returns\\Domain\\ReturnStatus',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Returns/Domain/ReturnStatus.php',
      ),
    ),
    'namespace' => 'Modules\\Returns\\Domain',
    'name' => 'Modules\\Returns\\Domain\\ReturnStatus',
    'shortName' => 'ReturnStatus',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => true,
    'isBackedEnum' => true,
    'modifiers' => 0,
    'docComment' => '/**
 * Trạng thái yêu cầu đổi/trả (docs/09-order/order.md §7). "inspected" gộp vào "received": nhân viên ghi
 * tình trạng từng dòng ngay khi nhận hàng.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 11,
    'endLine' => 46,
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
      'TRANSITIONS' => 
      array (
        'declaringClassName' => 'Modules\\Returns\\Domain\\ReturnStatus',
        'implementingClassName' => 'Modules\\Returns\\Domain\\ReturnStatus',
        'name' => 'TRANSITIONS',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'requested\' => [\'approved\', \'rejected\', \'cancelled\'], \'approved\' => [\'in_transit\', \'received\', \'cancelled\'], \'in_transit\' => [\'received\'], \'received\' => [\'resolved\', \'rejected\'], \'resolved\' => [], \'rejected\' => [], \'cancelled\' => []]',
          'attributes' => 
          array (
            'startLine' => 21,
            'endLine' => 29,
            'startTokenPos' => 97,
            'startFilePos' => 543,
            'endTokenPos' => 174,
            'endFilePos' => 839,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 21,
        'endLine' => 29,
        'startColumn' => 5,
        'endColumn' => 6,
      ),
    ),
    'immediateProperties' => 
    array (
      'name' => 
      array (
        'declaringClassName' => 'Modules\\Returns\\Domain\\ReturnStatus',
        'implementingClassName' => 'Modules\\Returns\\Domain\\ReturnStatus',
        'name' => 'name',
        'modifiers' => 2177,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => NULL,
        'endLine' => NULL,
        'startColumn' => -1,
        'endColumn' => -1,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'value' => 
      array (
        'declaringClassName' => 'Modules\\Returns\\Domain\\ReturnStatus',
        'implementingClassName' => 'Modules\\Returns\\Domain\\ReturnStatus',
        'name' => 'value',
        'modifiers' => 2177,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => NULL,
        'endLine' => NULL,
        'startColumn' => -1,
        'endColumn' => -1,
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
      'canMoveTo' => 
      array (
        'name' => 'canMoveTo',
        'parameters' => 
        array (
          'to' => 
          array (
            'name' => 'to',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'self',
                'isIdentifier' => false,
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
            'startColumn' => 31,
            'endColumn' => 38,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 31,
        'endLine' => 34,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Returns\\Domain',
        'declaringClassName' => 'Modules\\Returns\\Domain\\ReturnStatus',
        'implementingClassName' => 'Modules\\Returns\\Domain\\ReturnStatus',
        'currentClassName' => 'Modules\\Returns\\Domain\\ReturnStatus',
        'aliasName' => NULL,
      ),
      'isOpen' => 
      array (
        'name' => 'isOpen',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 36,
        'endLine' => 39,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Returns\\Domain',
        'declaringClassName' => 'Modules\\Returns\\Domain\\ReturnStatus',
        'implementingClassName' => 'Modules\\Returns\\Domain\\ReturnStatus',
        'currentClassName' => 'Modules\\Returns\\Domain\\ReturnStatus',
        'aliasName' => NULL,
      ),
      'countsTowardsLimit' => 
      array (
        'name' => 'countsTowardsLimit',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/** Số lượng của yêu cầu còn "chiếm chỗ" trong giới hạn trả của dòng. */',
        'startLine' => 42,
        'endLine' => 45,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Returns\\Domain',
        'declaringClassName' => 'Modules\\Returns\\Domain\\ReturnStatus',
        'implementingClassName' => 'Modules\\Returns\\Domain\\ReturnStatus',
        'currentClassName' => 'Modules\\Returns\\Domain\\ReturnStatus',
        'aliasName' => NULL,
      ),
      'cases' => 
      array (
        'name' => 'cases',
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
        'startLine' => NULL,
        'endLine' => NULL,
        'startColumn' => -1,
        'endColumn' => -1,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Modules\\Returns\\Domain',
        'declaringClassName' => 'Modules\\Returns\\Domain\\ReturnStatus',
        'implementingClassName' => 'Modules\\Returns\\Domain\\ReturnStatus',
        'currentClassName' => 'Modules\\Returns\\Domain\\ReturnStatus',
        'aliasName' => NULL,
      ),
      'from' => 
      array (
        'name' => 'from',
        'parameters' => 
        array (
          'value' => 
          array (
            'name' => 'value',
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
                      'name' => 'int',
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
            'startLine' => NULL,
            'endLine' => NULL,
            'startColumn' => -1,
            'endColumn' => -1,
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
            'name' => 'static',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => NULL,
        'endLine' => NULL,
        'startColumn' => -1,
        'endColumn' => -1,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Modules\\Returns\\Domain',
        'declaringClassName' => 'Modules\\Returns\\Domain\\ReturnStatus',
        'implementingClassName' => 'Modules\\Returns\\Domain\\ReturnStatus',
        'currentClassName' => 'Modules\\Returns\\Domain\\ReturnStatus',
        'aliasName' => NULL,
      ),
      'tryFrom' => 
      array (
        'name' => 'tryFrom',
        'parameters' => 
        array (
          'value' => 
          array (
            'name' => 'value',
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
                      'name' => 'int',
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
            'startLine' => NULL,
            'endLine' => NULL,
            'startColumn' => -1,
            'endColumn' => -1,
            'parameterIndex' => 0,
            'isOptional' => false,
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
                  'name' => 'static',
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
        'startLine' => NULL,
        'endLine' => NULL,
        'startColumn' => -1,
        'endColumn' => -1,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Modules\\Returns\\Domain',
        'declaringClassName' => 'Modules\\Returns\\Domain\\ReturnStatus',
        'implementingClassName' => 'Modules\\Returns\\Domain\\ReturnStatus',
        'currentClassName' => 'Modules\\Returns\\Domain\\ReturnStatus',
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
    'backingType' => 
    array (
      'name' => 'string',
      'isIdentifier' => true,
    ),
    'cases' => 
    array (
      'Requested' => 
      array (
        'name' => 'Requested',
        'value' => 
        array (
          'code' => '\'requested\'',
          'attributes' => 
          array (
            'startLine' => 13,
            'endLine' => 13,
            'startTokenPos' => 32,
            'startFilePos' => 300,
            'endTokenPos' => 32,
            'endFilePos' => 310,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 13,
        'endLine' => 13,
        'startColumn' => 5,
        'endColumn' => 33,
      ),
      'Approved' => 
      array (
        'name' => 'Approved',
        'value' => 
        array (
          'code' => '\'approved\'',
          'attributes' => 
          array (
            'startLine' => 14,
            'endLine' => 14,
            'startTokenPos' => 41,
            'startFilePos' => 333,
            'endTokenPos' => 41,
            'endFilePos' => 342,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 14,
        'endLine' => 14,
        'startColumn' => 5,
        'endColumn' => 31,
      ),
      'InTransit' => 
      array (
        'name' => 'InTransit',
        'value' => 
        array (
          'code' => '\'in_transit\'',
          'attributes' => 
          array (
            'startLine' => 15,
            'endLine' => 15,
            'startTokenPos' => 50,
            'startFilePos' => 366,
            'endTokenPos' => 50,
            'endFilePos' => 377,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 15,
        'endLine' => 15,
        'startColumn' => 5,
        'endColumn' => 34,
      ),
      'Received' => 
      array (
        'name' => 'Received',
        'value' => 
        array (
          'code' => '\'received\'',
          'attributes' => 
          array (
            'startLine' => 16,
            'endLine' => 16,
            'startTokenPos' => 59,
            'startFilePos' => 400,
            'endTokenPos' => 59,
            'endFilePos' => 409,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 16,
        'endLine' => 16,
        'startColumn' => 5,
        'endColumn' => 31,
      ),
      'Resolved' => 
      array (
        'name' => 'Resolved',
        'value' => 
        array (
          'code' => '\'resolved\'',
          'attributes' => 
          array (
            'startLine' => 17,
            'endLine' => 17,
            'startTokenPos' => 68,
            'startFilePos' => 432,
            'endTokenPos' => 68,
            'endFilePos' => 441,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 17,
        'endLine' => 17,
        'startColumn' => 5,
        'endColumn' => 31,
      ),
      'Rejected' => 
      array (
        'name' => 'Rejected',
        'value' => 
        array (
          'code' => '\'rejected\'',
          'attributes' => 
          array (
            'startLine' => 18,
            'endLine' => 18,
            'startTokenPos' => 77,
            'startFilePos' => 464,
            'endTokenPos' => 77,
            'endFilePos' => 473,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 18,
        'endLine' => 18,
        'startColumn' => 5,
        'endColumn' => 31,
      ),
      'Cancelled' => 
      array (
        'name' => 'Cancelled',
        'value' => 
        array (
          'code' => '\'cancelled\'',
          'attributes' => 
          array (
            'startLine' => 19,
            'endLine' => 19,
            'startTokenPos' => 86,
            'startFilePos' => 497,
            'endTokenPos' => 86,
            'endFilePos' => 507,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 19,
        'endLine' => 19,
        'startColumn' => 5,
        'endColumn' => 33,
      ),
    ),
  ),
));