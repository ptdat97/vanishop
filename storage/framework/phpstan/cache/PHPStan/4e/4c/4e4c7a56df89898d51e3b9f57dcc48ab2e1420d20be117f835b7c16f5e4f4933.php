<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Fulfillment/Domain/ShipmentStatus.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Fulfillment\Domain\ShipmentStatus
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-3191be905412f1e0c5da429f715788eadf7bf33267336413bb263df7feb81462',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Fulfillment\\Domain\\ShipmentStatus',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Fulfillment/Domain/ShipmentStatus.php',
      ),
    ),
    'namespace' => 'Modules\\Fulfillment\\Domain',
    'name' => 'Modules\\Fulfillment\\Domain\\ShipmentStatus',
    'shortName' => 'ShipmentStatus',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => true,
    'isBackedEnum' => true,
    'modifiers' => 0,
    'docComment' => '/**
 * Trạng thái vận đơn chuẩn hoá (docs/09-order/fulfillment.md §2). Chỉ đi tiến theo `rank`
 * (webhook đến trễ/sai thứ tự không làm lùi trạng thái); `failed_attempt` ↔ `out_for_delivery` được lặp.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 11,
    'endLine' => 69,
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
      'name' => 
      array (
        'declaringClassName' => 'Modules\\Fulfillment\\Domain\\ShipmentStatus',
        'implementingClassName' => 'Modules\\Fulfillment\\Domain\\ShipmentStatus',
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
        'declaringClassName' => 'Modules\\Fulfillment\\Domain\\ShipmentStatus',
        'implementingClassName' => 'Modules\\Fulfillment\\Domain\\ShipmentStatus',
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
      'rank' => 
      array (
        'name' => 'rank',
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
        'startLine' => 25,
        'endLine' => 36,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Fulfillment\\Domain',
        'declaringClassName' => 'Modules\\Fulfillment\\Domain\\ShipmentStatus',
        'implementingClassName' => 'Modules\\Fulfillment\\Domain\\ShipmentStatus',
        'currentClassName' => 'Modules\\Fulfillment\\Domain\\ShipmentStatus',
        'aliasName' => NULL,
      ),
      'isTerminal' => 
      array (
        'name' => 'isTerminal',
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
        'startLine' => 38,
        'endLine' => 41,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Fulfillment\\Domain',
        'declaringClassName' => 'Modules\\Fulfillment\\Domain\\ShipmentStatus',
        'implementingClassName' => 'Modules\\Fulfillment\\Domain\\ShipmentStatus',
        'currentClassName' => 'Modules\\Fulfillment\\Domain\\ShipmentStatus',
        'aliasName' => NULL,
      ),
      'hasLeftWarehouse' => 
      array (
        'name' => 'hasLeftWarehouse',
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
        'docComment' => '/** Hàng đã rời kho. */',
        'startLine' => 44,
        'endLine' => 47,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Fulfillment\\Domain',
        'declaringClassName' => 'Modules\\Fulfillment\\Domain\\ShipmentStatus',
        'implementingClassName' => 'Modules\\Fulfillment\\Domain\\ShipmentStatus',
        'currentClassName' => 'Modules\\Fulfillment\\Domain\\ShipmentStatus',
        'aliasName' => NULL,
      ),
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
            'startLine' => 49,
            'endLine' => 49,
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
        'startLine' => 49,
        'endLine' => 68,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Fulfillment\\Domain',
        'declaringClassName' => 'Modules\\Fulfillment\\Domain\\ShipmentStatus',
        'implementingClassName' => 'Modules\\Fulfillment\\Domain\\ShipmentStatus',
        'currentClassName' => 'Modules\\Fulfillment\\Domain\\ShipmentStatus',
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
        'namespace' => 'Modules\\Fulfillment\\Domain',
        'declaringClassName' => 'Modules\\Fulfillment\\Domain\\ShipmentStatus',
        'implementingClassName' => 'Modules\\Fulfillment\\Domain\\ShipmentStatus',
        'currentClassName' => 'Modules\\Fulfillment\\Domain\\ShipmentStatus',
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
        'namespace' => 'Modules\\Fulfillment\\Domain',
        'declaringClassName' => 'Modules\\Fulfillment\\Domain\\ShipmentStatus',
        'implementingClassName' => 'Modules\\Fulfillment\\Domain\\ShipmentStatus',
        'currentClassName' => 'Modules\\Fulfillment\\Domain\\ShipmentStatus',
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
        'namespace' => 'Modules\\Fulfillment\\Domain',
        'declaringClassName' => 'Modules\\Fulfillment\\Domain\\ShipmentStatus',
        'implementingClassName' => 'Modules\\Fulfillment\\Domain\\ShipmentStatus',
        'currentClassName' => 'Modules\\Fulfillment\\Domain\\ShipmentStatus',
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
      'PendingBooking' => 
      array (
        'name' => 'PendingBooking',
        'value' => 
        array (
          'code' => '\'pending_booking\'',
          'attributes' => 
          array (
            'startLine' => 13,
            'endLine' => 13,
            'startTokenPos' => 32,
            'startFilePos' => 372,
            'endTokenPos' => 32,
            'endFilePos' => 388,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 13,
        'endLine' => 13,
        'startColumn' => 5,
        'endColumn' => 44,
      ),
      'BookingFailed' => 
      array (
        'name' => 'BookingFailed',
        'value' => 
        array (
          'code' => '\'booking_failed\'',
          'attributes' => 
          array (
            'startLine' => 14,
            'endLine' => 14,
            'startTokenPos' => 41,
            'startFilePos' => 416,
            'endTokenPos' => 41,
            'endFilePos' => 431,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 14,
        'endLine' => 14,
        'startColumn' => 5,
        'endColumn' => 42,
      ),
      'Created' => 
      array (
        'name' => 'Created',
        'value' => 
        array (
          'code' => '\'created\'',
          'attributes' => 
          array (
            'startLine' => 15,
            'endLine' => 15,
            'startTokenPos' => 50,
            'startFilePos' => 453,
            'endTokenPos' => 50,
            'endFilePos' => 461,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 15,
        'endLine' => 15,
        'startColumn' => 5,
        'endColumn' => 29,
      ),
      'PickedUp' => 
      array (
        'name' => 'PickedUp',
        'value' => 
        array (
          'code' => '\'picked_up\'',
          'attributes' => 
          array (
            'startLine' => 16,
            'endLine' => 16,
            'startTokenPos' => 59,
            'startFilePos' => 484,
            'endTokenPos' => 59,
            'endFilePos' => 494,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 16,
        'endLine' => 16,
        'startColumn' => 5,
        'endColumn' => 32,
      ),
      'InTransit' => 
      array (
        'name' => 'InTransit',
        'value' => 
        array (
          'code' => '\'in_transit\'',
          'attributes' => 
          array (
            'startLine' => 17,
            'endLine' => 17,
            'startTokenPos' => 68,
            'startFilePos' => 518,
            'endTokenPos' => 68,
            'endFilePos' => 529,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 17,
        'endLine' => 17,
        'startColumn' => 5,
        'endColumn' => 34,
      ),
      'OutForDelivery' => 
      array (
        'name' => 'OutForDelivery',
        'value' => 
        array (
          'code' => '\'out_for_delivery\'',
          'attributes' => 
          array (
            'startLine' => 18,
            'endLine' => 18,
            'startTokenPos' => 77,
            'startFilePos' => 558,
            'endTokenPos' => 77,
            'endFilePos' => 575,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 18,
        'endLine' => 18,
        'startColumn' => 5,
        'endColumn' => 45,
      ),
      'FailedAttempt' => 
      array (
        'name' => 'FailedAttempt',
        'value' => 
        array (
          'code' => '\'failed_attempt\'',
          'attributes' => 
          array (
            'startLine' => 19,
            'endLine' => 19,
            'startTokenPos' => 86,
            'startFilePos' => 603,
            'endTokenPos' => 86,
            'endFilePos' => 618,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 19,
        'endLine' => 19,
        'startColumn' => 5,
        'endColumn' => 42,
      ),
      'Delivered' => 
      array (
        'name' => 'Delivered',
        'value' => 
        array (
          'code' => '\'delivered\'',
          'attributes' => 
          array (
            'startLine' => 20,
            'endLine' => 20,
            'startTokenPos' => 95,
            'startFilePos' => 642,
            'endTokenPos' => 95,
            'endFilePos' => 652,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 20,
        'endLine' => 20,
        'startColumn' => 5,
        'endColumn' => 33,
      ),
      'Returning' => 
      array (
        'name' => 'Returning',
        'value' => 
        array (
          'code' => '\'returning\'',
          'attributes' => 
          array (
            'startLine' => 21,
            'endLine' => 21,
            'startTokenPos' => 104,
            'startFilePos' => 676,
            'endTokenPos' => 104,
            'endFilePos' => 686,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 21,
        'endLine' => 21,
        'startColumn' => 5,
        'endColumn' => 33,
      ),
      'Returned' => 
      array (
        'name' => 'Returned',
        'value' => 
        array (
          'code' => '\'returned\'',
          'attributes' => 
          array (
            'startLine' => 22,
            'endLine' => 22,
            'startTokenPos' => 113,
            'startFilePos' => 709,
            'endTokenPos' => 113,
            'endFilePos' => 718,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 22,
        'endLine' => 22,
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
            'startLine' => 23,
            'endLine' => 23,
            'startTokenPos' => 122,
            'startFilePos' => 742,
            'endTokenPos' => 122,
            'endFilePos' => 752,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 23,
        'endLine' => 23,
        'startColumn' => 5,
        'endColumn' => 33,
      ),
    ),
  ),
));