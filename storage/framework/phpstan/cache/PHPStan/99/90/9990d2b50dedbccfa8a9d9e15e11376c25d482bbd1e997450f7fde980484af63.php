<?php declare(strict_types = 1);

// ftm-/Users/dat/Ecommerce/vanishop/modules/Inventory/Application/ReservationService.php
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v6-2.3.5',
   'data' => 
  array (
    0 => 
    array (
      'f2b2fb746f558d51e666bfa488e11c6f' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Inventory\\Application',
         'uses' => 
        array (
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'variantdirectory' => 'Modules\\Catalog\\Contracts\\VariantDirectory',
          'reservationrequest' => 'Modules\\Inventory\\Contracts\\Data\\ReservationRequest',
          'reservedline' => 'Modules\\Inventory\\Contracts\\Data\\ReservedLine',
          'inventoryreservation' => 'Modules\\Inventory\\Contracts\\InventoryReservation',
          'stockunavailable' => 'Modules\\Inventory\\Contracts\\StockUnavailable',
          'allocation' => 'Modules\\Inventory\\Domain\\Allocation',
          'insufficientstock' => 'Modules\\Inventory\\Domain\\InsufficientStock',
          'movementtype' => 'Modules\\Inventory\\Domain\\MovementType',
          'reservationstatus' => 'Modules\\Inventory\\Domain\\ReservationStatus',
          'stocklevel' => 'Modules\\Inventory\\Domain\\StockLevel',
          'availabilitychanged' => 'Modules\\Inventory\\Events\\AvailabilityChanged',
          'stockcommitted' => 'Modules\\Inventory\\Events\\StockCommitted',
          'stockreleased' => 'Modules\\Inventory\\Events\\StockReleased',
          'stockreserved' => 'Modules\\Inventory\\Events\\StockReserved',
          'location' => 'Modules\\Inventory\\Persistence\\Models\\Location',
          'stockreservation' => 'Modules\\Inventory\\Persistence\\Models\\StockReservation',
        ),
         'className' => 'Modules\\Inventory\\Application\\ReservationService',
         'functionName' => NULL,
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => NULL,
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '451a6b90aac9f8a4604c676aeeb8b9ed' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Inventory\\Application',
         'uses' => 
        array (
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'variantdirectory' => 'Modules\\Catalog\\Contracts\\VariantDirectory',
          'reservationrequest' => 'Modules\\Inventory\\Contracts\\Data\\ReservationRequest',
          'reservedline' => 'Modules\\Inventory\\Contracts\\Data\\ReservedLine',
          'inventoryreservation' => 'Modules\\Inventory\\Contracts\\InventoryReservation',
          'stockunavailable' => 'Modules\\Inventory\\Contracts\\StockUnavailable',
          'allocation' => 'Modules\\Inventory\\Domain\\Allocation',
          'insufficientstock' => 'Modules\\Inventory\\Domain\\InsufficientStock',
          'movementtype' => 'Modules\\Inventory\\Domain\\MovementType',
          'reservationstatus' => 'Modules\\Inventory\\Domain\\ReservationStatus',
          'stocklevel' => 'Modules\\Inventory\\Domain\\StockLevel',
          'availabilitychanged' => 'Modules\\Inventory\\Events\\AvailabilityChanged',
          'stockcommitted' => 'Modules\\Inventory\\Events\\StockCommitted',
          'stockreleased' => 'Modules\\Inventory\\Events\\StockReleased',
          'stockreserved' => 'Modules\\Inventory\\Events\\StockReserved',
          'location' => 'Modules\\Inventory\\Persistence\\Models\\Location',
          'stockreservation' => 'Modules\\Inventory\\Persistence\\Models\\StockReservation',
        ),
         'className' => 'Modules\\Inventory\\Application\\ReservationService',
         'functionName' => '__construct',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'Modules\\Inventory\\Application',
           'uses' => 
          array (
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'variantdirectory' => 'Modules\\Catalog\\Contracts\\VariantDirectory',
            'reservationrequest' => 'Modules\\Inventory\\Contracts\\Data\\ReservationRequest',
            'reservedline' => 'Modules\\Inventory\\Contracts\\Data\\ReservedLine',
            'inventoryreservation' => 'Modules\\Inventory\\Contracts\\InventoryReservation',
            'stockunavailable' => 'Modules\\Inventory\\Contracts\\StockUnavailable',
            'allocation' => 'Modules\\Inventory\\Domain\\Allocation',
            'insufficientstock' => 'Modules\\Inventory\\Domain\\InsufficientStock',
            'movementtype' => 'Modules\\Inventory\\Domain\\MovementType',
            'reservationstatus' => 'Modules\\Inventory\\Domain\\ReservationStatus',
            'stocklevel' => 'Modules\\Inventory\\Domain\\StockLevel',
            'availabilitychanged' => 'Modules\\Inventory\\Events\\AvailabilityChanged',
            'stockcommitted' => 'Modules\\Inventory\\Events\\StockCommitted',
            'stockreleased' => 'Modules\\Inventory\\Events\\StockReleased',
            'stockreserved' => 'Modules\\Inventory\\Events\\StockReserved',
            'location' => 'Modules\\Inventory\\Persistence\\Models\\Location',
            'stockreservation' => 'Modules\\Inventory\\Persistence\\Models\\StockReservation',
          ),
           'className' => 'Modules\\Inventory\\Application\\ReservationService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      'fa6deb1f1601f5c9afcb2744ee3a405e' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Inventory\\Application',
         'uses' => 
        array (
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'variantdirectory' => 'Modules\\Catalog\\Contracts\\VariantDirectory',
          'reservationrequest' => 'Modules\\Inventory\\Contracts\\Data\\ReservationRequest',
          'reservedline' => 'Modules\\Inventory\\Contracts\\Data\\ReservedLine',
          'inventoryreservation' => 'Modules\\Inventory\\Contracts\\InventoryReservation',
          'stockunavailable' => 'Modules\\Inventory\\Contracts\\StockUnavailable',
          'allocation' => 'Modules\\Inventory\\Domain\\Allocation',
          'insufficientstock' => 'Modules\\Inventory\\Domain\\InsufficientStock',
          'movementtype' => 'Modules\\Inventory\\Domain\\MovementType',
          'reservationstatus' => 'Modules\\Inventory\\Domain\\ReservationStatus',
          'stocklevel' => 'Modules\\Inventory\\Domain\\StockLevel',
          'availabilitychanged' => 'Modules\\Inventory\\Events\\AvailabilityChanged',
          'stockcommitted' => 'Modules\\Inventory\\Events\\StockCommitted',
          'stockreleased' => 'Modules\\Inventory\\Events\\StockReleased',
          'stockreserved' => 'Modules\\Inventory\\Events\\StockReserved',
          'location' => 'Modules\\Inventory\\Persistence\\Models\\Location',
          'stockreservation' => 'Modules\\Inventory\\Persistence\\Models\\StockReservation',
        ),
         'className' => 'Modules\\Inventory\\Application\\ReservationService',
         'functionName' => 'reserve',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'Modules\\Inventory\\Application',
           'uses' => 
          array (
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'variantdirectory' => 'Modules\\Catalog\\Contracts\\VariantDirectory',
            'reservationrequest' => 'Modules\\Inventory\\Contracts\\Data\\ReservationRequest',
            'reservedline' => 'Modules\\Inventory\\Contracts\\Data\\ReservedLine',
            'inventoryreservation' => 'Modules\\Inventory\\Contracts\\InventoryReservation',
            'stockunavailable' => 'Modules\\Inventory\\Contracts\\StockUnavailable',
            'allocation' => 'Modules\\Inventory\\Domain\\Allocation',
            'insufficientstock' => 'Modules\\Inventory\\Domain\\InsufficientStock',
            'movementtype' => 'Modules\\Inventory\\Domain\\MovementType',
            'reservationstatus' => 'Modules\\Inventory\\Domain\\ReservationStatus',
            'stocklevel' => 'Modules\\Inventory\\Domain\\StockLevel',
            'availabilitychanged' => 'Modules\\Inventory\\Events\\AvailabilityChanged',
            'stockcommitted' => 'Modules\\Inventory\\Events\\StockCommitted',
            'stockreleased' => 'Modules\\Inventory\\Events\\StockReleased',
            'stockreserved' => 'Modules\\Inventory\\Events\\StockReserved',
            'location' => 'Modules\\Inventory\\Persistence\\Models\\Location',
            'stockreservation' => 'Modules\\Inventory\\Persistence\\Models\\StockReservation',
          ),
           'className' => 'Modules\\Inventory\\Application\\ReservationService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '7131dd284a14de277ddfeb3f3c5d5194' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Inventory\\Application',
         'uses' => 
        array (
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'variantdirectory' => 'Modules\\Catalog\\Contracts\\VariantDirectory',
          'reservationrequest' => 'Modules\\Inventory\\Contracts\\Data\\ReservationRequest',
          'reservedline' => 'Modules\\Inventory\\Contracts\\Data\\ReservedLine',
          'inventoryreservation' => 'Modules\\Inventory\\Contracts\\InventoryReservation',
          'stockunavailable' => 'Modules\\Inventory\\Contracts\\StockUnavailable',
          'allocation' => 'Modules\\Inventory\\Domain\\Allocation',
          'insufficientstock' => 'Modules\\Inventory\\Domain\\InsufficientStock',
          'movementtype' => 'Modules\\Inventory\\Domain\\MovementType',
          'reservationstatus' => 'Modules\\Inventory\\Domain\\ReservationStatus',
          'stocklevel' => 'Modules\\Inventory\\Domain\\StockLevel',
          'availabilitychanged' => 'Modules\\Inventory\\Events\\AvailabilityChanged',
          'stockcommitted' => 'Modules\\Inventory\\Events\\StockCommitted',
          'stockreleased' => 'Modules\\Inventory\\Events\\StockReleased',
          'stockreserved' => 'Modules\\Inventory\\Events\\StockReserved',
          'location' => 'Modules\\Inventory\\Persistence\\Models\\Location',
          'stockreservation' => 'Modules\\Inventory\\Persistence\\Models\\StockReservation',
        ),
         'className' => 'Modules\\Inventory\\Application\\ReservationService',
         'functionName' => 'release',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'Modules\\Inventory\\Application',
           'uses' => 
          array (
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'variantdirectory' => 'Modules\\Catalog\\Contracts\\VariantDirectory',
            'reservationrequest' => 'Modules\\Inventory\\Contracts\\Data\\ReservationRequest',
            'reservedline' => 'Modules\\Inventory\\Contracts\\Data\\ReservedLine',
            'inventoryreservation' => 'Modules\\Inventory\\Contracts\\InventoryReservation',
            'stockunavailable' => 'Modules\\Inventory\\Contracts\\StockUnavailable',
            'allocation' => 'Modules\\Inventory\\Domain\\Allocation',
            'insufficientstock' => 'Modules\\Inventory\\Domain\\InsufficientStock',
            'movementtype' => 'Modules\\Inventory\\Domain\\MovementType',
            'reservationstatus' => 'Modules\\Inventory\\Domain\\ReservationStatus',
            'stocklevel' => 'Modules\\Inventory\\Domain\\StockLevel',
            'availabilitychanged' => 'Modules\\Inventory\\Events\\AvailabilityChanged',
            'stockcommitted' => 'Modules\\Inventory\\Events\\StockCommitted',
            'stockreleased' => 'Modules\\Inventory\\Events\\StockReleased',
            'stockreserved' => 'Modules\\Inventory\\Events\\StockReserved',
            'location' => 'Modules\\Inventory\\Persistence\\Models\\Location',
            'stockreservation' => 'Modules\\Inventory\\Persistence\\Models\\StockReservation',
          ),
           'className' => 'Modules\\Inventory\\Application\\ReservationService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      '1e0e09fd15c85e5f07ae3b2c3707fa4d' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Inventory\\Application',
         'uses' => 
        array (
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'variantdirectory' => 'Modules\\Catalog\\Contracts\\VariantDirectory',
          'reservationrequest' => 'Modules\\Inventory\\Contracts\\Data\\ReservationRequest',
          'reservedline' => 'Modules\\Inventory\\Contracts\\Data\\ReservedLine',
          'inventoryreservation' => 'Modules\\Inventory\\Contracts\\InventoryReservation',
          'stockunavailable' => 'Modules\\Inventory\\Contracts\\StockUnavailable',
          'allocation' => 'Modules\\Inventory\\Domain\\Allocation',
          'insufficientstock' => 'Modules\\Inventory\\Domain\\InsufficientStock',
          'movementtype' => 'Modules\\Inventory\\Domain\\MovementType',
          'reservationstatus' => 'Modules\\Inventory\\Domain\\ReservationStatus',
          'stocklevel' => 'Modules\\Inventory\\Domain\\StockLevel',
          'availabilitychanged' => 'Modules\\Inventory\\Events\\AvailabilityChanged',
          'stockcommitted' => 'Modules\\Inventory\\Events\\StockCommitted',
          'stockreleased' => 'Modules\\Inventory\\Events\\StockReleased',
          'stockreserved' => 'Modules\\Inventory\\Events\\StockReserved',
          'location' => 'Modules\\Inventory\\Persistence\\Models\\Location',
          'stockreservation' => 'Modules\\Inventory\\Persistence\\Models\\StockReservation',
        ),
         'className' => 'Modules\\Inventory\\Application\\ReservationService',
         'functionName' => 'commit',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'Modules\\Inventory\\Application',
           'uses' => 
          array (
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'variantdirectory' => 'Modules\\Catalog\\Contracts\\VariantDirectory',
            'reservationrequest' => 'Modules\\Inventory\\Contracts\\Data\\ReservationRequest',
            'reservedline' => 'Modules\\Inventory\\Contracts\\Data\\ReservedLine',
            'inventoryreservation' => 'Modules\\Inventory\\Contracts\\InventoryReservation',
            'stockunavailable' => 'Modules\\Inventory\\Contracts\\StockUnavailable',
            'allocation' => 'Modules\\Inventory\\Domain\\Allocation',
            'insufficientstock' => 'Modules\\Inventory\\Domain\\InsufficientStock',
            'movementtype' => 'Modules\\Inventory\\Domain\\MovementType',
            'reservationstatus' => 'Modules\\Inventory\\Domain\\ReservationStatus',
            'stocklevel' => 'Modules\\Inventory\\Domain\\StockLevel',
            'availabilitychanged' => 'Modules\\Inventory\\Events\\AvailabilityChanged',
            'stockcommitted' => 'Modules\\Inventory\\Events\\StockCommitted',
            'stockreleased' => 'Modules\\Inventory\\Events\\StockReleased',
            'stockreserved' => 'Modules\\Inventory\\Events\\StockReserved',
            'location' => 'Modules\\Inventory\\Persistence\\Models\\Location',
            'stockreservation' => 'Modules\\Inventory\\Persistence\\Models\\StockReservation',
          ),
           'className' => 'Modules\\Inventory\\Application\\ReservationService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      'e34aa3cbfa74c76c93393bf861ef0b55' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Inventory\\Application',
         'uses' => 
        array (
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'variantdirectory' => 'Modules\\Catalog\\Contracts\\VariantDirectory',
          'reservationrequest' => 'Modules\\Inventory\\Contracts\\Data\\ReservationRequest',
          'reservedline' => 'Modules\\Inventory\\Contracts\\Data\\ReservedLine',
          'inventoryreservation' => 'Modules\\Inventory\\Contracts\\InventoryReservation',
          'stockunavailable' => 'Modules\\Inventory\\Contracts\\StockUnavailable',
          'allocation' => 'Modules\\Inventory\\Domain\\Allocation',
          'insufficientstock' => 'Modules\\Inventory\\Domain\\InsufficientStock',
          'movementtype' => 'Modules\\Inventory\\Domain\\MovementType',
          'reservationstatus' => 'Modules\\Inventory\\Domain\\ReservationStatus',
          'stocklevel' => 'Modules\\Inventory\\Domain\\StockLevel',
          'availabilitychanged' => 'Modules\\Inventory\\Events\\AvailabilityChanged',
          'stockcommitted' => 'Modules\\Inventory\\Events\\StockCommitted',
          'stockreleased' => 'Modules\\Inventory\\Events\\StockReleased',
          'stockreserved' => 'Modules\\Inventory\\Events\\StockReserved',
          'location' => 'Modules\\Inventory\\Persistence\\Models\\Location',
          'stockreservation' => 'Modules\\Inventory\\Persistence\\Models\\StockReservation',
        ),
         'className' => 'Modules\\Inventory\\Application\\ReservationService',
         'functionName' => 'reservedLines',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'Modules\\Inventory\\Application',
           'uses' => 
          array (
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'variantdirectory' => 'Modules\\Catalog\\Contracts\\VariantDirectory',
            'reservationrequest' => 'Modules\\Inventory\\Contracts\\Data\\ReservationRequest',
            'reservedline' => 'Modules\\Inventory\\Contracts\\Data\\ReservedLine',
            'inventoryreservation' => 'Modules\\Inventory\\Contracts\\InventoryReservation',
            'stockunavailable' => 'Modules\\Inventory\\Contracts\\StockUnavailable',
            'allocation' => 'Modules\\Inventory\\Domain\\Allocation',
            'insufficientstock' => 'Modules\\Inventory\\Domain\\InsufficientStock',
            'movementtype' => 'Modules\\Inventory\\Domain\\MovementType',
            'reservationstatus' => 'Modules\\Inventory\\Domain\\ReservationStatus',
            'stocklevel' => 'Modules\\Inventory\\Domain\\StockLevel',
            'availabilitychanged' => 'Modules\\Inventory\\Events\\AvailabilityChanged',
            'stockcommitted' => 'Modules\\Inventory\\Events\\StockCommitted',
            'stockreleased' => 'Modules\\Inventory\\Events\\StockReleased',
            'stockreserved' => 'Modules\\Inventory\\Events\\StockReserved',
            'location' => 'Modules\\Inventory\\Persistence\\Models\\Location',
            'stockreservation' => 'Modules\\Inventory\\Persistence\\Models\\StockReservation',
          ),
           'className' => 'Modules\\Inventory\\Application\\ReservationService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
      'f23aa4b9a10480563584dc8f1a1f0f85' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Inventory\\Application',
         'uses' => 
        array (
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'variantdirectory' => 'Modules\\Catalog\\Contracts\\VariantDirectory',
          'reservationrequest' => 'Modules\\Inventory\\Contracts\\Data\\ReservationRequest',
          'reservedline' => 'Modules\\Inventory\\Contracts\\Data\\ReservedLine',
          'inventoryreservation' => 'Modules\\Inventory\\Contracts\\InventoryReservation',
          'stockunavailable' => 'Modules\\Inventory\\Contracts\\StockUnavailable',
          'allocation' => 'Modules\\Inventory\\Domain\\Allocation',
          'insufficientstock' => 'Modules\\Inventory\\Domain\\InsufficientStock',
          'movementtype' => 'Modules\\Inventory\\Domain\\MovementType',
          'reservationstatus' => 'Modules\\Inventory\\Domain\\ReservationStatus',
          'stocklevel' => 'Modules\\Inventory\\Domain\\StockLevel',
          'availabilitychanged' => 'Modules\\Inventory\\Events\\AvailabilityChanged',
          'stockcommitted' => 'Modules\\Inventory\\Events\\StockCommitted',
          'stockreleased' => 'Modules\\Inventory\\Events\\StockReleased',
          'stockreserved' => 'Modules\\Inventory\\Events\\StockReserved',
          'location' => 'Modules\\Inventory\\Persistence\\Models\\Location',
          'stockreservation' => 'Modules\\Inventory\\Persistence\\Models\\StockReservation',
        ),
         'className' => 'Modules\\Inventory\\Application\\ReservationService',
         'functionName' => 'finish',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'Modules\\Inventory\\Application',
           'uses' => 
          array (
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'variantdirectory' => 'Modules\\Catalog\\Contracts\\VariantDirectory',
            'reservationrequest' => 'Modules\\Inventory\\Contracts\\Data\\ReservationRequest',
            'reservedline' => 'Modules\\Inventory\\Contracts\\Data\\ReservedLine',
            'inventoryreservation' => 'Modules\\Inventory\\Contracts\\InventoryReservation',
            'stockunavailable' => 'Modules\\Inventory\\Contracts\\StockUnavailable',
            'allocation' => 'Modules\\Inventory\\Domain\\Allocation',
            'insufficientstock' => 'Modules\\Inventory\\Domain\\InsufficientStock',
            'movementtype' => 'Modules\\Inventory\\Domain\\MovementType',
            'reservationstatus' => 'Modules\\Inventory\\Domain\\ReservationStatus',
            'stocklevel' => 'Modules\\Inventory\\Domain\\StockLevel',
            'availabilitychanged' => 'Modules\\Inventory\\Events\\AvailabilityChanged',
            'stockcommitted' => 'Modules\\Inventory\\Events\\StockCommitted',
            'stockreleased' => 'Modules\\Inventory\\Events\\StockReleased',
            'stockreserved' => 'Modules\\Inventory\\Events\\StockReserved',
            'location' => 'Modules\\Inventory\\Persistence\\Models\\Location',
            'stockreservation' => 'Modules\\Inventory\\Persistence\\Models\\StockReservation',
          ),
           'className' => 'Modules\\Inventory\\Application\\ReservationService',
           'functionName' => NULL,
           'templatePhpDocNodes' => 
          array (
          ),
           'parent' => NULL,
           'typeAliasesMap' => 
          array (
          ),
           'bypassTypeAliases' => false,
           'constUses' => 
          array (
          ),
           'typeAliasClassName' => NULL,
           'traitData' => NULL,
        )),
         'typeAliasesMap' => 
        array (
        ),
         'bypassTypeAliases' => false,
         'constUses' => 
        array (
        ),
         'typeAliasClassName' => NULL,
         'traitData' => NULL,
      )),
    ),
    1 => 
    array (
      '/Users/dat/Ecommerce/vanishop/modules/Inventory/Application/ReservationService.php' => '778b34f096ab31865591ad7106a141b8b980990e3ac7ca0d800e345d2c1c3229',
    ),
  ),
));