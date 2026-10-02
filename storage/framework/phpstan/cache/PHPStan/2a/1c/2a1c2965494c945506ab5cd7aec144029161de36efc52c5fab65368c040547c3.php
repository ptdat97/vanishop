<?php declare(strict_types = 1);

// ftm-/Users/dat/Ecommerce/vanishop/modules/Fulfillment/Application/FulfillmentService.php
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v6-2.3.5',
   'data' => 
  array (
    0 => 
    array (
      '99e3b694b7d77c0124803242d4a03427' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Fulfillment\\Application',
         'uses' => 
        array (
          'datetimeimmutable' => 'DateTimeImmutable',
          'uniqueconstraintviolationexception' => 'Illuminate\\Database\\UniqueConstraintViolationException',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'log' => 'Illuminate\\Support\\Facades\\Log',
          'str' => 'Illuminate\\Support\\Str',
          'allocationproposal' => 'Modules\\Fulfillment\\Contracts\\Data\\AllocationProposal',
          'shipmentdata' => 'Modules\\Fulfillment\\Contracts\\Data\\ShipmentData',
          'sourcingrequest' => 'Modules\\Fulfillment\\Contracts\\Data\\SourcingRequest',
          'fulfillmentrejected' => 'Modules\\Fulfillment\\Contracts\\FulfillmentRejected',
          'fulfillmentprogress' => 'Modules\\Fulfillment\\Domain\\FulfillmentProgress',
          'shipmentstatus' => 'Modules\\Fulfillment\\Domain\\ShipmentStatus',
          'shipmentcreated' => 'Modules\\Fulfillment\\Events\\ShipmentCreated',
          'shipmentstatuschanged' => 'Modules\\Fulfillment\\Events\\ShipmentStatusChanged',
          'shipment' => 'Modules\\Fulfillment\\Persistence\\Models\\Shipment',
          'shipmentline' => 'Modules\\Fulfillment\\Persistence\\Models\\ShipmentLine',
          'auditlogger' => 'Modules\\Identity\\Contracts\\AuditLogger',
          'reservedline' => 'Modules\\Inventory\\Contracts\\Data\\ReservedLine',
          'inventoryreservation' => 'Modules\\Inventory\\Contracts\\InventoryReservation',
          'inventoryreturns' => 'Modules\\Inventory\\Contracts\\InventoryReturns',
          'orderlinedata' => 'Modules\\Ordering\\Contracts\\Data\\OrderLineData',
          'orderstatus' => 'Modules\\Ordering\\Contracts\\Data\\OrderStatus',
          'orderreader' => 'Modules\\Ordering\\Contracts\\OrderReader',
          'ordertransitions' => 'Modules\\Ordering\\Contracts\\OrderTransitions',
          'money' => 'Modules\\Shared\\Domain\\Money\\Money',
          'throwable' => 'Throwable',
        ),
         'className' => 'Modules\\Fulfillment\\Application\\FulfillmentService',
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
      '0d73624396bdb582b87c0c5ecee14ce1' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Fulfillment\\Application',
         'uses' => 
        array (
          'datetimeimmutable' => 'DateTimeImmutable',
          'uniqueconstraintviolationexception' => 'Illuminate\\Database\\UniqueConstraintViolationException',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'log' => 'Illuminate\\Support\\Facades\\Log',
          'str' => 'Illuminate\\Support\\Str',
          'allocationproposal' => 'Modules\\Fulfillment\\Contracts\\Data\\AllocationProposal',
          'shipmentdata' => 'Modules\\Fulfillment\\Contracts\\Data\\ShipmentData',
          'sourcingrequest' => 'Modules\\Fulfillment\\Contracts\\Data\\SourcingRequest',
          'fulfillmentrejected' => 'Modules\\Fulfillment\\Contracts\\FulfillmentRejected',
          'fulfillmentprogress' => 'Modules\\Fulfillment\\Domain\\FulfillmentProgress',
          'shipmentstatus' => 'Modules\\Fulfillment\\Domain\\ShipmentStatus',
          'shipmentcreated' => 'Modules\\Fulfillment\\Events\\ShipmentCreated',
          'shipmentstatuschanged' => 'Modules\\Fulfillment\\Events\\ShipmentStatusChanged',
          'shipment' => 'Modules\\Fulfillment\\Persistence\\Models\\Shipment',
          'shipmentline' => 'Modules\\Fulfillment\\Persistence\\Models\\ShipmentLine',
          'auditlogger' => 'Modules\\Identity\\Contracts\\AuditLogger',
          'reservedline' => 'Modules\\Inventory\\Contracts\\Data\\ReservedLine',
          'inventoryreservation' => 'Modules\\Inventory\\Contracts\\InventoryReservation',
          'inventoryreturns' => 'Modules\\Inventory\\Contracts\\InventoryReturns',
          'orderlinedata' => 'Modules\\Ordering\\Contracts\\Data\\OrderLineData',
          'orderstatus' => 'Modules\\Ordering\\Contracts\\Data\\OrderStatus',
          'orderreader' => 'Modules\\Ordering\\Contracts\\OrderReader',
          'ordertransitions' => 'Modules\\Ordering\\Contracts\\OrderTransitions',
          'money' => 'Modules\\Shared\\Domain\\Money\\Money',
          'throwable' => 'Throwable',
        ),
         'className' => 'Modules\\Fulfillment\\Application\\FulfillmentService',
         'functionName' => '__construct',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'Modules\\Fulfillment\\Application',
           'uses' => 
          array (
            'datetimeimmutable' => 'DateTimeImmutable',
            'uniqueconstraintviolationexception' => 'Illuminate\\Database\\UniqueConstraintViolationException',
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'log' => 'Illuminate\\Support\\Facades\\Log',
            'str' => 'Illuminate\\Support\\Str',
            'allocationproposal' => 'Modules\\Fulfillment\\Contracts\\Data\\AllocationProposal',
            'shipmentdata' => 'Modules\\Fulfillment\\Contracts\\Data\\ShipmentData',
            'sourcingrequest' => 'Modules\\Fulfillment\\Contracts\\Data\\SourcingRequest',
            'fulfillmentrejected' => 'Modules\\Fulfillment\\Contracts\\FulfillmentRejected',
            'fulfillmentprogress' => 'Modules\\Fulfillment\\Domain\\FulfillmentProgress',
            'shipmentstatus' => 'Modules\\Fulfillment\\Domain\\ShipmentStatus',
            'shipmentcreated' => 'Modules\\Fulfillment\\Events\\ShipmentCreated',
            'shipmentstatuschanged' => 'Modules\\Fulfillment\\Events\\ShipmentStatusChanged',
            'shipment' => 'Modules\\Fulfillment\\Persistence\\Models\\Shipment',
            'shipmentline' => 'Modules\\Fulfillment\\Persistence\\Models\\ShipmentLine',
            'auditlogger' => 'Modules\\Identity\\Contracts\\AuditLogger',
            'reservedline' => 'Modules\\Inventory\\Contracts\\Data\\ReservedLine',
            'inventoryreservation' => 'Modules\\Inventory\\Contracts\\InventoryReservation',
            'inventoryreturns' => 'Modules\\Inventory\\Contracts\\InventoryReturns',
            'orderlinedata' => 'Modules\\Ordering\\Contracts\\Data\\OrderLineData',
            'orderstatus' => 'Modules\\Ordering\\Contracts\\Data\\OrderStatus',
            'orderreader' => 'Modules\\Ordering\\Contracts\\OrderReader',
            'ordertransitions' => 'Modules\\Ordering\\Contracts\\OrderTransitions',
            'money' => 'Modules\\Shared\\Domain\\Money\\Money',
            'throwable' => 'Throwable',
          ),
           'className' => 'Modules\\Fulfillment\\Application\\FulfillmentService',
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
      '23deb1ce3953e64310206510f4447ce4' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Fulfillment\\Application',
         'uses' => 
        array (
          'datetimeimmutable' => 'DateTimeImmutable',
          'uniqueconstraintviolationexception' => 'Illuminate\\Database\\UniqueConstraintViolationException',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'log' => 'Illuminate\\Support\\Facades\\Log',
          'str' => 'Illuminate\\Support\\Str',
          'allocationproposal' => 'Modules\\Fulfillment\\Contracts\\Data\\AllocationProposal',
          'shipmentdata' => 'Modules\\Fulfillment\\Contracts\\Data\\ShipmentData',
          'sourcingrequest' => 'Modules\\Fulfillment\\Contracts\\Data\\SourcingRequest',
          'fulfillmentrejected' => 'Modules\\Fulfillment\\Contracts\\FulfillmentRejected',
          'fulfillmentprogress' => 'Modules\\Fulfillment\\Domain\\FulfillmentProgress',
          'shipmentstatus' => 'Modules\\Fulfillment\\Domain\\ShipmentStatus',
          'shipmentcreated' => 'Modules\\Fulfillment\\Events\\ShipmentCreated',
          'shipmentstatuschanged' => 'Modules\\Fulfillment\\Events\\ShipmentStatusChanged',
          'shipment' => 'Modules\\Fulfillment\\Persistence\\Models\\Shipment',
          'shipmentline' => 'Modules\\Fulfillment\\Persistence\\Models\\ShipmentLine',
          'auditlogger' => 'Modules\\Identity\\Contracts\\AuditLogger',
          'reservedline' => 'Modules\\Inventory\\Contracts\\Data\\ReservedLine',
          'inventoryreservation' => 'Modules\\Inventory\\Contracts\\InventoryReservation',
          'inventoryreturns' => 'Modules\\Inventory\\Contracts\\InventoryReturns',
          'orderlinedata' => 'Modules\\Ordering\\Contracts\\Data\\OrderLineData',
          'orderstatus' => 'Modules\\Ordering\\Contracts\\Data\\OrderStatus',
          'orderreader' => 'Modules\\Ordering\\Contracts\\OrderReader',
          'ordertransitions' => 'Modules\\Ordering\\Contracts\\OrderTransitions',
          'money' => 'Modules\\Shared\\Domain\\Money\\Money',
          'throwable' => 'Throwable',
        ),
         'className' => 'Modules\\Fulfillment\\Application\\FulfillmentService',
         'functionName' => 'createForOrder',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'Modules\\Fulfillment\\Application',
           'uses' => 
          array (
            'datetimeimmutable' => 'DateTimeImmutable',
            'uniqueconstraintviolationexception' => 'Illuminate\\Database\\UniqueConstraintViolationException',
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'log' => 'Illuminate\\Support\\Facades\\Log',
            'str' => 'Illuminate\\Support\\Str',
            'allocationproposal' => 'Modules\\Fulfillment\\Contracts\\Data\\AllocationProposal',
            'shipmentdata' => 'Modules\\Fulfillment\\Contracts\\Data\\ShipmentData',
            'sourcingrequest' => 'Modules\\Fulfillment\\Contracts\\Data\\SourcingRequest',
            'fulfillmentrejected' => 'Modules\\Fulfillment\\Contracts\\FulfillmentRejected',
            'fulfillmentprogress' => 'Modules\\Fulfillment\\Domain\\FulfillmentProgress',
            'shipmentstatus' => 'Modules\\Fulfillment\\Domain\\ShipmentStatus',
            'shipmentcreated' => 'Modules\\Fulfillment\\Events\\ShipmentCreated',
            'shipmentstatuschanged' => 'Modules\\Fulfillment\\Events\\ShipmentStatusChanged',
            'shipment' => 'Modules\\Fulfillment\\Persistence\\Models\\Shipment',
            'shipmentline' => 'Modules\\Fulfillment\\Persistence\\Models\\ShipmentLine',
            'auditlogger' => 'Modules\\Identity\\Contracts\\AuditLogger',
            'reservedline' => 'Modules\\Inventory\\Contracts\\Data\\ReservedLine',
            'inventoryreservation' => 'Modules\\Inventory\\Contracts\\InventoryReservation',
            'inventoryreturns' => 'Modules\\Inventory\\Contracts\\InventoryReturns',
            'orderlinedata' => 'Modules\\Ordering\\Contracts\\Data\\OrderLineData',
            'orderstatus' => 'Modules\\Ordering\\Contracts\\Data\\OrderStatus',
            'orderreader' => 'Modules\\Ordering\\Contracts\\OrderReader',
            'ordertransitions' => 'Modules\\Ordering\\Contracts\\OrderTransitions',
            'money' => 'Modules\\Shared\\Domain\\Money\\Money',
            'throwable' => 'Throwable',
          ),
           'className' => 'Modules\\Fulfillment\\Application\\FulfillmentService',
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
      '81fc23616ab976c75b9f7fa17c4de7b2' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Fulfillment\\Application',
         'uses' => 
        array (
          'datetimeimmutable' => 'DateTimeImmutable',
          'uniqueconstraintviolationexception' => 'Illuminate\\Database\\UniqueConstraintViolationException',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'log' => 'Illuminate\\Support\\Facades\\Log',
          'str' => 'Illuminate\\Support\\Str',
          'allocationproposal' => 'Modules\\Fulfillment\\Contracts\\Data\\AllocationProposal',
          'shipmentdata' => 'Modules\\Fulfillment\\Contracts\\Data\\ShipmentData',
          'sourcingrequest' => 'Modules\\Fulfillment\\Contracts\\Data\\SourcingRequest',
          'fulfillmentrejected' => 'Modules\\Fulfillment\\Contracts\\FulfillmentRejected',
          'fulfillmentprogress' => 'Modules\\Fulfillment\\Domain\\FulfillmentProgress',
          'shipmentstatus' => 'Modules\\Fulfillment\\Domain\\ShipmentStatus',
          'shipmentcreated' => 'Modules\\Fulfillment\\Events\\ShipmentCreated',
          'shipmentstatuschanged' => 'Modules\\Fulfillment\\Events\\ShipmentStatusChanged',
          'shipment' => 'Modules\\Fulfillment\\Persistence\\Models\\Shipment',
          'shipmentline' => 'Modules\\Fulfillment\\Persistence\\Models\\ShipmentLine',
          'auditlogger' => 'Modules\\Identity\\Contracts\\AuditLogger',
          'reservedline' => 'Modules\\Inventory\\Contracts\\Data\\ReservedLine',
          'inventoryreservation' => 'Modules\\Inventory\\Contracts\\InventoryReservation',
          'inventoryreturns' => 'Modules\\Inventory\\Contracts\\InventoryReturns',
          'orderlinedata' => 'Modules\\Ordering\\Contracts\\Data\\OrderLineData',
          'orderstatus' => 'Modules\\Ordering\\Contracts\\Data\\OrderStatus',
          'orderreader' => 'Modules\\Ordering\\Contracts\\OrderReader',
          'ordertransitions' => 'Modules\\Ordering\\Contracts\\OrderTransitions',
          'money' => 'Modules\\Shared\\Domain\\Money\\Money',
          'throwable' => 'Throwable',
        ),
         'className' => 'Modules\\Fulfillment\\Application\\FulfillmentService',
         'functionName' => 'carrierFor',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'Modules\\Fulfillment\\Application',
           'uses' => 
          array (
            'datetimeimmutable' => 'DateTimeImmutable',
            'uniqueconstraintviolationexception' => 'Illuminate\\Database\\UniqueConstraintViolationException',
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'log' => 'Illuminate\\Support\\Facades\\Log',
            'str' => 'Illuminate\\Support\\Str',
            'allocationproposal' => 'Modules\\Fulfillment\\Contracts\\Data\\AllocationProposal',
            'shipmentdata' => 'Modules\\Fulfillment\\Contracts\\Data\\ShipmentData',
            'sourcingrequest' => 'Modules\\Fulfillment\\Contracts\\Data\\SourcingRequest',
            'fulfillmentrejected' => 'Modules\\Fulfillment\\Contracts\\FulfillmentRejected',
            'fulfillmentprogress' => 'Modules\\Fulfillment\\Domain\\FulfillmentProgress',
            'shipmentstatus' => 'Modules\\Fulfillment\\Domain\\ShipmentStatus',
            'shipmentcreated' => 'Modules\\Fulfillment\\Events\\ShipmentCreated',
            'shipmentstatuschanged' => 'Modules\\Fulfillment\\Events\\ShipmentStatusChanged',
            'shipment' => 'Modules\\Fulfillment\\Persistence\\Models\\Shipment',
            'shipmentline' => 'Modules\\Fulfillment\\Persistence\\Models\\ShipmentLine',
            'auditlogger' => 'Modules\\Identity\\Contracts\\AuditLogger',
            'reservedline' => 'Modules\\Inventory\\Contracts\\Data\\ReservedLine',
            'inventoryreservation' => 'Modules\\Inventory\\Contracts\\InventoryReservation',
            'inventoryreturns' => 'Modules\\Inventory\\Contracts\\InventoryReturns',
            'orderlinedata' => 'Modules\\Ordering\\Contracts\\Data\\OrderLineData',
            'orderstatus' => 'Modules\\Ordering\\Contracts\\Data\\OrderStatus',
            'orderreader' => 'Modules\\Ordering\\Contracts\\OrderReader',
            'ordertransitions' => 'Modules\\Ordering\\Contracts\\OrderTransitions',
            'money' => 'Modules\\Shared\\Domain\\Money\\Money',
            'throwable' => 'Throwable',
          ),
           'className' => 'Modules\\Fulfillment\\Application\\FulfillmentService',
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
      '198ba4bbbbf9cc7890b5e4f1405bca4d' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Fulfillment\\Application',
         'uses' => 
        array (
          'datetimeimmutable' => 'DateTimeImmutable',
          'uniqueconstraintviolationexception' => 'Illuminate\\Database\\UniqueConstraintViolationException',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'log' => 'Illuminate\\Support\\Facades\\Log',
          'str' => 'Illuminate\\Support\\Str',
          'allocationproposal' => 'Modules\\Fulfillment\\Contracts\\Data\\AllocationProposal',
          'shipmentdata' => 'Modules\\Fulfillment\\Contracts\\Data\\ShipmentData',
          'sourcingrequest' => 'Modules\\Fulfillment\\Contracts\\Data\\SourcingRequest',
          'fulfillmentrejected' => 'Modules\\Fulfillment\\Contracts\\FulfillmentRejected',
          'fulfillmentprogress' => 'Modules\\Fulfillment\\Domain\\FulfillmentProgress',
          'shipmentstatus' => 'Modules\\Fulfillment\\Domain\\ShipmentStatus',
          'shipmentcreated' => 'Modules\\Fulfillment\\Events\\ShipmentCreated',
          'shipmentstatuschanged' => 'Modules\\Fulfillment\\Events\\ShipmentStatusChanged',
          'shipment' => 'Modules\\Fulfillment\\Persistence\\Models\\Shipment',
          'shipmentline' => 'Modules\\Fulfillment\\Persistence\\Models\\ShipmentLine',
          'auditlogger' => 'Modules\\Identity\\Contracts\\AuditLogger',
          'reservedline' => 'Modules\\Inventory\\Contracts\\Data\\ReservedLine',
          'inventoryreservation' => 'Modules\\Inventory\\Contracts\\InventoryReservation',
          'inventoryreturns' => 'Modules\\Inventory\\Contracts\\InventoryReturns',
          'orderlinedata' => 'Modules\\Ordering\\Contracts\\Data\\OrderLineData',
          'orderstatus' => 'Modules\\Ordering\\Contracts\\Data\\OrderStatus',
          'orderreader' => 'Modules\\Ordering\\Contracts\\OrderReader',
          'ordertransitions' => 'Modules\\Ordering\\Contracts\\OrderTransitions',
          'money' => 'Modules\\Shared\\Domain\\Money\\Money',
          'throwable' => 'Throwable',
        ),
         'className' => 'Modules\\Fulfillment\\Application\\FulfillmentService',
         'functionName' => 'book',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'Modules\\Fulfillment\\Application',
           'uses' => 
          array (
            'datetimeimmutable' => 'DateTimeImmutable',
            'uniqueconstraintviolationexception' => 'Illuminate\\Database\\UniqueConstraintViolationException',
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'log' => 'Illuminate\\Support\\Facades\\Log',
            'str' => 'Illuminate\\Support\\Str',
            'allocationproposal' => 'Modules\\Fulfillment\\Contracts\\Data\\AllocationProposal',
            'shipmentdata' => 'Modules\\Fulfillment\\Contracts\\Data\\ShipmentData',
            'sourcingrequest' => 'Modules\\Fulfillment\\Contracts\\Data\\SourcingRequest',
            'fulfillmentrejected' => 'Modules\\Fulfillment\\Contracts\\FulfillmentRejected',
            'fulfillmentprogress' => 'Modules\\Fulfillment\\Domain\\FulfillmentProgress',
            'shipmentstatus' => 'Modules\\Fulfillment\\Domain\\ShipmentStatus',
            'shipmentcreated' => 'Modules\\Fulfillment\\Events\\ShipmentCreated',
            'shipmentstatuschanged' => 'Modules\\Fulfillment\\Events\\ShipmentStatusChanged',
            'shipment' => 'Modules\\Fulfillment\\Persistence\\Models\\Shipment',
            'shipmentline' => 'Modules\\Fulfillment\\Persistence\\Models\\ShipmentLine',
            'auditlogger' => 'Modules\\Identity\\Contracts\\AuditLogger',
            'reservedline' => 'Modules\\Inventory\\Contracts\\Data\\ReservedLine',
            'inventoryreservation' => 'Modules\\Inventory\\Contracts\\InventoryReservation',
            'inventoryreturns' => 'Modules\\Inventory\\Contracts\\InventoryReturns',
            'orderlinedata' => 'Modules\\Ordering\\Contracts\\Data\\OrderLineData',
            'orderstatus' => 'Modules\\Ordering\\Contracts\\Data\\OrderStatus',
            'orderreader' => 'Modules\\Ordering\\Contracts\\OrderReader',
            'ordertransitions' => 'Modules\\Ordering\\Contracts\\OrderTransitions',
            'money' => 'Modules\\Shared\\Domain\\Money\\Money',
            'throwable' => 'Throwable',
          ),
           'className' => 'Modules\\Fulfillment\\Application\\FulfillmentService',
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
      '305aa5f0a9e9dcd329717bbd86fc794b' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Fulfillment\\Application',
         'uses' => 
        array (
          'datetimeimmutable' => 'DateTimeImmutable',
          'uniqueconstraintviolationexception' => 'Illuminate\\Database\\UniqueConstraintViolationException',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'log' => 'Illuminate\\Support\\Facades\\Log',
          'str' => 'Illuminate\\Support\\Str',
          'allocationproposal' => 'Modules\\Fulfillment\\Contracts\\Data\\AllocationProposal',
          'shipmentdata' => 'Modules\\Fulfillment\\Contracts\\Data\\ShipmentData',
          'sourcingrequest' => 'Modules\\Fulfillment\\Contracts\\Data\\SourcingRequest',
          'fulfillmentrejected' => 'Modules\\Fulfillment\\Contracts\\FulfillmentRejected',
          'fulfillmentprogress' => 'Modules\\Fulfillment\\Domain\\FulfillmentProgress',
          'shipmentstatus' => 'Modules\\Fulfillment\\Domain\\ShipmentStatus',
          'shipmentcreated' => 'Modules\\Fulfillment\\Events\\ShipmentCreated',
          'shipmentstatuschanged' => 'Modules\\Fulfillment\\Events\\ShipmentStatusChanged',
          'shipment' => 'Modules\\Fulfillment\\Persistence\\Models\\Shipment',
          'shipmentline' => 'Modules\\Fulfillment\\Persistence\\Models\\ShipmentLine',
          'auditlogger' => 'Modules\\Identity\\Contracts\\AuditLogger',
          'reservedline' => 'Modules\\Inventory\\Contracts\\Data\\ReservedLine',
          'inventoryreservation' => 'Modules\\Inventory\\Contracts\\InventoryReservation',
          'inventoryreturns' => 'Modules\\Inventory\\Contracts\\InventoryReturns',
          'orderlinedata' => 'Modules\\Ordering\\Contracts\\Data\\OrderLineData',
          'orderstatus' => 'Modules\\Ordering\\Contracts\\Data\\OrderStatus',
          'orderreader' => 'Modules\\Ordering\\Contracts\\OrderReader',
          'ordertransitions' => 'Modules\\Ordering\\Contracts\\OrderTransitions',
          'money' => 'Modules\\Shared\\Domain\\Money\\Money',
          'throwable' => 'Throwable',
        ),
         'className' => 'Modules\\Fulfillment\\Application\\FulfillmentService',
         'functionName' => 'bookAutomatically',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'Modules\\Fulfillment\\Application',
           'uses' => 
          array (
            'datetimeimmutable' => 'DateTimeImmutable',
            'uniqueconstraintviolationexception' => 'Illuminate\\Database\\UniqueConstraintViolationException',
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'log' => 'Illuminate\\Support\\Facades\\Log',
            'str' => 'Illuminate\\Support\\Str',
            'allocationproposal' => 'Modules\\Fulfillment\\Contracts\\Data\\AllocationProposal',
            'shipmentdata' => 'Modules\\Fulfillment\\Contracts\\Data\\ShipmentData',
            'sourcingrequest' => 'Modules\\Fulfillment\\Contracts\\Data\\SourcingRequest',
            'fulfillmentrejected' => 'Modules\\Fulfillment\\Contracts\\FulfillmentRejected',
            'fulfillmentprogress' => 'Modules\\Fulfillment\\Domain\\FulfillmentProgress',
            'shipmentstatus' => 'Modules\\Fulfillment\\Domain\\ShipmentStatus',
            'shipmentcreated' => 'Modules\\Fulfillment\\Events\\ShipmentCreated',
            'shipmentstatuschanged' => 'Modules\\Fulfillment\\Events\\ShipmentStatusChanged',
            'shipment' => 'Modules\\Fulfillment\\Persistence\\Models\\Shipment',
            'shipmentline' => 'Modules\\Fulfillment\\Persistence\\Models\\ShipmentLine',
            'auditlogger' => 'Modules\\Identity\\Contracts\\AuditLogger',
            'reservedline' => 'Modules\\Inventory\\Contracts\\Data\\ReservedLine',
            'inventoryreservation' => 'Modules\\Inventory\\Contracts\\InventoryReservation',
            'inventoryreturns' => 'Modules\\Inventory\\Contracts\\InventoryReturns',
            'orderlinedata' => 'Modules\\Ordering\\Contracts\\Data\\OrderLineData',
            'orderstatus' => 'Modules\\Ordering\\Contracts\\Data\\OrderStatus',
            'orderreader' => 'Modules\\Ordering\\Contracts\\OrderReader',
            'ordertransitions' => 'Modules\\Ordering\\Contracts\\OrderTransitions',
            'money' => 'Modules\\Shared\\Domain\\Money\\Money',
            'throwable' => 'Throwable',
          ),
           'className' => 'Modules\\Fulfillment\\Application\\FulfillmentService',
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
      '9f44d7f849beb14ef615cf5f4cc9de9c' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Fulfillment\\Application',
         'uses' => 
        array (
          'datetimeimmutable' => 'DateTimeImmutable',
          'uniqueconstraintviolationexception' => 'Illuminate\\Database\\UniqueConstraintViolationException',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'log' => 'Illuminate\\Support\\Facades\\Log',
          'str' => 'Illuminate\\Support\\Str',
          'allocationproposal' => 'Modules\\Fulfillment\\Contracts\\Data\\AllocationProposal',
          'shipmentdata' => 'Modules\\Fulfillment\\Contracts\\Data\\ShipmentData',
          'sourcingrequest' => 'Modules\\Fulfillment\\Contracts\\Data\\SourcingRequest',
          'fulfillmentrejected' => 'Modules\\Fulfillment\\Contracts\\FulfillmentRejected',
          'fulfillmentprogress' => 'Modules\\Fulfillment\\Domain\\FulfillmentProgress',
          'shipmentstatus' => 'Modules\\Fulfillment\\Domain\\ShipmentStatus',
          'shipmentcreated' => 'Modules\\Fulfillment\\Events\\ShipmentCreated',
          'shipmentstatuschanged' => 'Modules\\Fulfillment\\Events\\ShipmentStatusChanged',
          'shipment' => 'Modules\\Fulfillment\\Persistence\\Models\\Shipment',
          'shipmentline' => 'Modules\\Fulfillment\\Persistence\\Models\\ShipmentLine',
          'auditlogger' => 'Modules\\Identity\\Contracts\\AuditLogger',
          'reservedline' => 'Modules\\Inventory\\Contracts\\Data\\ReservedLine',
          'inventoryreservation' => 'Modules\\Inventory\\Contracts\\InventoryReservation',
          'inventoryreturns' => 'Modules\\Inventory\\Contracts\\InventoryReturns',
          'orderlinedata' => 'Modules\\Ordering\\Contracts\\Data\\OrderLineData',
          'orderstatus' => 'Modules\\Ordering\\Contracts\\Data\\OrderStatus',
          'orderreader' => 'Modules\\Ordering\\Contracts\\OrderReader',
          'ordertransitions' => 'Modules\\Ordering\\Contracts\\OrderTransitions',
          'money' => 'Modules\\Shared\\Domain\\Money\\Money',
          'throwable' => 'Throwable',
        ),
         'className' => 'Modules\\Fulfillment\\Application\\FulfillmentService',
         'functionName' => 'updateStatus',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'Modules\\Fulfillment\\Application',
           'uses' => 
          array (
            'datetimeimmutable' => 'DateTimeImmutable',
            'uniqueconstraintviolationexception' => 'Illuminate\\Database\\UniqueConstraintViolationException',
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'log' => 'Illuminate\\Support\\Facades\\Log',
            'str' => 'Illuminate\\Support\\Str',
            'allocationproposal' => 'Modules\\Fulfillment\\Contracts\\Data\\AllocationProposal',
            'shipmentdata' => 'Modules\\Fulfillment\\Contracts\\Data\\ShipmentData',
            'sourcingrequest' => 'Modules\\Fulfillment\\Contracts\\Data\\SourcingRequest',
            'fulfillmentrejected' => 'Modules\\Fulfillment\\Contracts\\FulfillmentRejected',
            'fulfillmentprogress' => 'Modules\\Fulfillment\\Domain\\FulfillmentProgress',
            'shipmentstatus' => 'Modules\\Fulfillment\\Domain\\ShipmentStatus',
            'shipmentcreated' => 'Modules\\Fulfillment\\Events\\ShipmentCreated',
            'shipmentstatuschanged' => 'Modules\\Fulfillment\\Events\\ShipmentStatusChanged',
            'shipment' => 'Modules\\Fulfillment\\Persistence\\Models\\Shipment',
            'shipmentline' => 'Modules\\Fulfillment\\Persistence\\Models\\ShipmentLine',
            'auditlogger' => 'Modules\\Identity\\Contracts\\AuditLogger',
            'reservedline' => 'Modules\\Inventory\\Contracts\\Data\\ReservedLine',
            'inventoryreservation' => 'Modules\\Inventory\\Contracts\\InventoryReservation',
            'inventoryreturns' => 'Modules\\Inventory\\Contracts\\InventoryReturns',
            'orderlinedata' => 'Modules\\Ordering\\Contracts\\Data\\OrderLineData',
            'orderstatus' => 'Modules\\Ordering\\Contracts\\Data\\OrderStatus',
            'orderreader' => 'Modules\\Ordering\\Contracts\\OrderReader',
            'ordertransitions' => 'Modules\\Ordering\\Contracts\\OrderTransitions',
            'money' => 'Modules\\Shared\\Domain\\Money\\Money',
            'throwable' => 'Throwable',
          ),
           'className' => 'Modules\\Fulfillment\\Application\\FulfillmentService',
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
      '1b7dafe82c541c6dfa5d3312f2ba5a2c' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Fulfillment\\Application',
         'uses' => 
        array (
          'datetimeimmutable' => 'DateTimeImmutable',
          'uniqueconstraintviolationexception' => 'Illuminate\\Database\\UniqueConstraintViolationException',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'log' => 'Illuminate\\Support\\Facades\\Log',
          'str' => 'Illuminate\\Support\\Str',
          'allocationproposal' => 'Modules\\Fulfillment\\Contracts\\Data\\AllocationProposal',
          'shipmentdata' => 'Modules\\Fulfillment\\Contracts\\Data\\ShipmentData',
          'sourcingrequest' => 'Modules\\Fulfillment\\Contracts\\Data\\SourcingRequest',
          'fulfillmentrejected' => 'Modules\\Fulfillment\\Contracts\\FulfillmentRejected',
          'fulfillmentprogress' => 'Modules\\Fulfillment\\Domain\\FulfillmentProgress',
          'shipmentstatus' => 'Modules\\Fulfillment\\Domain\\ShipmentStatus',
          'shipmentcreated' => 'Modules\\Fulfillment\\Events\\ShipmentCreated',
          'shipmentstatuschanged' => 'Modules\\Fulfillment\\Events\\ShipmentStatusChanged',
          'shipment' => 'Modules\\Fulfillment\\Persistence\\Models\\Shipment',
          'shipmentline' => 'Modules\\Fulfillment\\Persistence\\Models\\ShipmentLine',
          'auditlogger' => 'Modules\\Identity\\Contracts\\AuditLogger',
          'reservedline' => 'Modules\\Inventory\\Contracts\\Data\\ReservedLine',
          'inventoryreservation' => 'Modules\\Inventory\\Contracts\\InventoryReservation',
          'inventoryreturns' => 'Modules\\Inventory\\Contracts\\InventoryReturns',
          'orderlinedata' => 'Modules\\Ordering\\Contracts\\Data\\OrderLineData',
          'orderstatus' => 'Modules\\Ordering\\Contracts\\Data\\OrderStatus',
          'orderreader' => 'Modules\\Ordering\\Contracts\\OrderReader',
          'ordertransitions' => 'Modules\\Ordering\\Contracts\\OrderTransitions',
          'money' => 'Modules\\Shared\\Domain\\Money\\Money',
          'throwable' => 'Throwable',
        ),
         'className' => 'Modules\\Fulfillment\\Application\\FulfillmentService',
         'functionName' => 'cancel',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'Modules\\Fulfillment\\Application',
           'uses' => 
          array (
            'datetimeimmutable' => 'DateTimeImmutable',
            'uniqueconstraintviolationexception' => 'Illuminate\\Database\\UniqueConstraintViolationException',
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'log' => 'Illuminate\\Support\\Facades\\Log',
            'str' => 'Illuminate\\Support\\Str',
            'allocationproposal' => 'Modules\\Fulfillment\\Contracts\\Data\\AllocationProposal',
            'shipmentdata' => 'Modules\\Fulfillment\\Contracts\\Data\\ShipmentData',
            'sourcingrequest' => 'Modules\\Fulfillment\\Contracts\\Data\\SourcingRequest',
            'fulfillmentrejected' => 'Modules\\Fulfillment\\Contracts\\FulfillmentRejected',
            'fulfillmentprogress' => 'Modules\\Fulfillment\\Domain\\FulfillmentProgress',
            'shipmentstatus' => 'Modules\\Fulfillment\\Domain\\ShipmentStatus',
            'shipmentcreated' => 'Modules\\Fulfillment\\Events\\ShipmentCreated',
            'shipmentstatuschanged' => 'Modules\\Fulfillment\\Events\\ShipmentStatusChanged',
            'shipment' => 'Modules\\Fulfillment\\Persistence\\Models\\Shipment',
            'shipmentline' => 'Modules\\Fulfillment\\Persistence\\Models\\ShipmentLine',
            'auditlogger' => 'Modules\\Identity\\Contracts\\AuditLogger',
            'reservedline' => 'Modules\\Inventory\\Contracts\\Data\\ReservedLine',
            'inventoryreservation' => 'Modules\\Inventory\\Contracts\\InventoryReservation',
            'inventoryreturns' => 'Modules\\Inventory\\Contracts\\InventoryReturns',
            'orderlinedata' => 'Modules\\Ordering\\Contracts\\Data\\OrderLineData',
            'orderstatus' => 'Modules\\Ordering\\Contracts\\Data\\OrderStatus',
            'orderreader' => 'Modules\\Ordering\\Contracts\\OrderReader',
            'ordertransitions' => 'Modules\\Ordering\\Contracts\\OrderTransitions',
            'money' => 'Modules\\Shared\\Domain\\Money\\Money',
            'throwable' => 'Throwable',
          ),
           'className' => 'Modules\\Fulfillment\\Application\\FulfillmentService',
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
      '9a8c34e8e0c3898c16c42fa5304f76f3' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Fulfillment\\Application',
         'uses' => 
        array (
          'datetimeimmutable' => 'DateTimeImmutable',
          'uniqueconstraintviolationexception' => 'Illuminate\\Database\\UniqueConstraintViolationException',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'log' => 'Illuminate\\Support\\Facades\\Log',
          'str' => 'Illuminate\\Support\\Str',
          'allocationproposal' => 'Modules\\Fulfillment\\Contracts\\Data\\AllocationProposal',
          'shipmentdata' => 'Modules\\Fulfillment\\Contracts\\Data\\ShipmentData',
          'sourcingrequest' => 'Modules\\Fulfillment\\Contracts\\Data\\SourcingRequest',
          'fulfillmentrejected' => 'Modules\\Fulfillment\\Contracts\\FulfillmentRejected',
          'fulfillmentprogress' => 'Modules\\Fulfillment\\Domain\\FulfillmentProgress',
          'shipmentstatus' => 'Modules\\Fulfillment\\Domain\\ShipmentStatus',
          'shipmentcreated' => 'Modules\\Fulfillment\\Events\\ShipmentCreated',
          'shipmentstatuschanged' => 'Modules\\Fulfillment\\Events\\ShipmentStatusChanged',
          'shipment' => 'Modules\\Fulfillment\\Persistence\\Models\\Shipment',
          'shipmentline' => 'Modules\\Fulfillment\\Persistence\\Models\\ShipmentLine',
          'auditlogger' => 'Modules\\Identity\\Contracts\\AuditLogger',
          'reservedline' => 'Modules\\Inventory\\Contracts\\Data\\ReservedLine',
          'inventoryreservation' => 'Modules\\Inventory\\Contracts\\InventoryReservation',
          'inventoryreturns' => 'Modules\\Inventory\\Contracts\\InventoryReturns',
          'orderlinedata' => 'Modules\\Ordering\\Contracts\\Data\\OrderLineData',
          'orderstatus' => 'Modules\\Ordering\\Contracts\\Data\\OrderStatus',
          'orderreader' => 'Modules\\Ordering\\Contracts\\OrderReader',
          'ordertransitions' => 'Modules\\Ordering\\Contracts\\OrderTransitions',
          'money' => 'Modules\\Shared\\Domain\\Money\\Money',
          'throwable' => 'Throwable',
        ),
         'className' => 'Modules\\Fulfillment\\Application\\FulfillmentService',
         'functionName' => 'cancelOpenShipments',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'Modules\\Fulfillment\\Application',
           'uses' => 
          array (
            'datetimeimmutable' => 'DateTimeImmutable',
            'uniqueconstraintviolationexception' => 'Illuminate\\Database\\UniqueConstraintViolationException',
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'log' => 'Illuminate\\Support\\Facades\\Log',
            'str' => 'Illuminate\\Support\\Str',
            'allocationproposal' => 'Modules\\Fulfillment\\Contracts\\Data\\AllocationProposal',
            'shipmentdata' => 'Modules\\Fulfillment\\Contracts\\Data\\ShipmentData',
            'sourcingrequest' => 'Modules\\Fulfillment\\Contracts\\Data\\SourcingRequest',
            'fulfillmentrejected' => 'Modules\\Fulfillment\\Contracts\\FulfillmentRejected',
            'fulfillmentprogress' => 'Modules\\Fulfillment\\Domain\\FulfillmentProgress',
            'shipmentstatus' => 'Modules\\Fulfillment\\Domain\\ShipmentStatus',
            'shipmentcreated' => 'Modules\\Fulfillment\\Events\\ShipmentCreated',
            'shipmentstatuschanged' => 'Modules\\Fulfillment\\Events\\ShipmentStatusChanged',
            'shipment' => 'Modules\\Fulfillment\\Persistence\\Models\\Shipment',
            'shipmentline' => 'Modules\\Fulfillment\\Persistence\\Models\\ShipmentLine',
            'auditlogger' => 'Modules\\Identity\\Contracts\\AuditLogger',
            'reservedline' => 'Modules\\Inventory\\Contracts\\Data\\ReservedLine',
            'inventoryreservation' => 'Modules\\Inventory\\Contracts\\InventoryReservation',
            'inventoryreturns' => 'Modules\\Inventory\\Contracts\\InventoryReturns',
            'orderlinedata' => 'Modules\\Ordering\\Contracts\\Data\\OrderLineData',
            'orderstatus' => 'Modules\\Ordering\\Contracts\\Data\\OrderStatus',
            'orderreader' => 'Modules\\Ordering\\Contracts\\OrderReader',
            'ordertransitions' => 'Modules\\Ordering\\Contracts\\OrderTransitions',
            'money' => 'Modules\\Shared\\Domain\\Money\\Money',
            'throwable' => 'Throwable',
          ),
           'className' => 'Modules\\Fulfillment\\Application\\FulfillmentService',
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
      'cc833d3374dfd08bd741dbd3b5ce794b' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Fulfillment\\Application',
         'uses' => 
        array (
          'datetimeimmutable' => 'DateTimeImmutable',
          'uniqueconstraintviolationexception' => 'Illuminate\\Database\\UniqueConstraintViolationException',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'log' => 'Illuminate\\Support\\Facades\\Log',
          'str' => 'Illuminate\\Support\\Str',
          'allocationproposal' => 'Modules\\Fulfillment\\Contracts\\Data\\AllocationProposal',
          'shipmentdata' => 'Modules\\Fulfillment\\Contracts\\Data\\ShipmentData',
          'sourcingrequest' => 'Modules\\Fulfillment\\Contracts\\Data\\SourcingRequest',
          'fulfillmentrejected' => 'Modules\\Fulfillment\\Contracts\\FulfillmentRejected',
          'fulfillmentprogress' => 'Modules\\Fulfillment\\Domain\\FulfillmentProgress',
          'shipmentstatus' => 'Modules\\Fulfillment\\Domain\\ShipmentStatus',
          'shipmentcreated' => 'Modules\\Fulfillment\\Events\\ShipmentCreated',
          'shipmentstatuschanged' => 'Modules\\Fulfillment\\Events\\ShipmentStatusChanged',
          'shipment' => 'Modules\\Fulfillment\\Persistence\\Models\\Shipment',
          'shipmentline' => 'Modules\\Fulfillment\\Persistence\\Models\\ShipmentLine',
          'auditlogger' => 'Modules\\Identity\\Contracts\\AuditLogger',
          'reservedline' => 'Modules\\Inventory\\Contracts\\Data\\ReservedLine',
          'inventoryreservation' => 'Modules\\Inventory\\Contracts\\InventoryReservation',
          'inventoryreturns' => 'Modules\\Inventory\\Contracts\\InventoryReturns',
          'orderlinedata' => 'Modules\\Ordering\\Contracts\\Data\\OrderLineData',
          'orderstatus' => 'Modules\\Ordering\\Contracts\\Data\\OrderStatus',
          'orderreader' => 'Modules\\Ordering\\Contracts\\OrderReader',
          'ordertransitions' => 'Modules\\Ordering\\Contracts\\OrderTransitions',
          'money' => 'Modules\\Shared\\Domain\\Money\\Money',
          'throwable' => 'Throwable',
        ),
         'className' => 'Modules\\Fulfillment\\Application\\FulfillmentService',
         'functionName' => 'data',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'Modules\\Fulfillment\\Application',
           'uses' => 
          array (
            'datetimeimmutable' => 'DateTimeImmutable',
            'uniqueconstraintviolationexception' => 'Illuminate\\Database\\UniqueConstraintViolationException',
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'log' => 'Illuminate\\Support\\Facades\\Log',
            'str' => 'Illuminate\\Support\\Str',
            'allocationproposal' => 'Modules\\Fulfillment\\Contracts\\Data\\AllocationProposal',
            'shipmentdata' => 'Modules\\Fulfillment\\Contracts\\Data\\ShipmentData',
            'sourcingrequest' => 'Modules\\Fulfillment\\Contracts\\Data\\SourcingRequest',
            'fulfillmentrejected' => 'Modules\\Fulfillment\\Contracts\\FulfillmentRejected',
            'fulfillmentprogress' => 'Modules\\Fulfillment\\Domain\\FulfillmentProgress',
            'shipmentstatus' => 'Modules\\Fulfillment\\Domain\\ShipmentStatus',
            'shipmentcreated' => 'Modules\\Fulfillment\\Events\\ShipmentCreated',
            'shipmentstatuschanged' => 'Modules\\Fulfillment\\Events\\ShipmentStatusChanged',
            'shipment' => 'Modules\\Fulfillment\\Persistence\\Models\\Shipment',
            'shipmentline' => 'Modules\\Fulfillment\\Persistence\\Models\\ShipmentLine',
            'auditlogger' => 'Modules\\Identity\\Contracts\\AuditLogger',
            'reservedline' => 'Modules\\Inventory\\Contracts\\Data\\ReservedLine',
            'inventoryreservation' => 'Modules\\Inventory\\Contracts\\InventoryReservation',
            'inventoryreturns' => 'Modules\\Inventory\\Contracts\\InventoryReturns',
            'orderlinedata' => 'Modules\\Ordering\\Contracts\\Data\\OrderLineData',
            'orderstatus' => 'Modules\\Ordering\\Contracts\\Data\\OrderStatus',
            'orderreader' => 'Modules\\Ordering\\Contracts\\OrderReader',
            'ordertransitions' => 'Modules\\Ordering\\Contracts\\OrderTransitions',
            'money' => 'Modules\\Shared\\Domain\\Money\\Money',
            'throwable' => 'Throwable',
          ),
           'className' => 'Modules\\Fulfillment\\Application\\FulfillmentService',
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
      'fd972feca829a3580767001665ab0f63' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Fulfillment\\Application',
         'uses' => 
        array (
          'datetimeimmutable' => 'DateTimeImmutable',
          'uniqueconstraintviolationexception' => 'Illuminate\\Database\\UniqueConstraintViolationException',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'log' => 'Illuminate\\Support\\Facades\\Log',
          'str' => 'Illuminate\\Support\\Str',
          'allocationproposal' => 'Modules\\Fulfillment\\Contracts\\Data\\AllocationProposal',
          'shipmentdata' => 'Modules\\Fulfillment\\Contracts\\Data\\ShipmentData',
          'sourcingrequest' => 'Modules\\Fulfillment\\Contracts\\Data\\SourcingRequest',
          'fulfillmentrejected' => 'Modules\\Fulfillment\\Contracts\\FulfillmentRejected',
          'fulfillmentprogress' => 'Modules\\Fulfillment\\Domain\\FulfillmentProgress',
          'shipmentstatus' => 'Modules\\Fulfillment\\Domain\\ShipmentStatus',
          'shipmentcreated' => 'Modules\\Fulfillment\\Events\\ShipmentCreated',
          'shipmentstatuschanged' => 'Modules\\Fulfillment\\Events\\ShipmentStatusChanged',
          'shipment' => 'Modules\\Fulfillment\\Persistence\\Models\\Shipment',
          'shipmentline' => 'Modules\\Fulfillment\\Persistence\\Models\\ShipmentLine',
          'auditlogger' => 'Modules\\Identity\\Contracts\\AuditLogger',
          'reservedline' => 'Modules\\Inventory\\Contracts\\Data\\ReservedLine',
          'inventoryreservation' => 'Modules\\Inventory\\Contracts\\InventoryReservation',
          'inventoryreturns' => 'Modules\\Inventory\\Contracts\\InventoryReturns',
          'orderlinedata' => 'Modules\\Ordering\\Contracts\\Data\\OrderLineData',
          'orderstatus' => 'Modules\\Ordering\\Contracts\\Data\\OrderStatus',
          'orderreader' => 'Modules\\Ordering\\Contracts\\OrderReader',
          'ordertransitions' => 'Modules\\Ordering\\Contracts\\OrderTransitions',
          'money' => 'Modules\\Shared\\Domain\\Money\\Money',
          'throwable' => 'Throwable',
        ),
         'className' => 'Modules\\Fulfillment\\Application\\FulfillmentService',
         'functionName' => 'apply',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'Modules\\Fulfillment\\Application',
           'uses' => 
          array (
            'datetimeimmutable' => 'DateTimeImmutable',
            'uniqueconstraintviolationexception' => 'Illuminate\\Database\\UniqueConstraintViolationException',
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'log' => 'Illuminate\\Support\\Facades\\Log',
            'str' => 'Illuminate\\Support\\Str',
            'allocationproposal' => 'Modules\\Fulfillment\\Contracts\\Data\\AllocationProposal',
            'shipmentdata' => 'Modules\\Fulfillment\\Contracts\\Data\\ShipmentData',
            'sourcingrequest' => 'Modules\\Fulfillment\\Contracts\\Data\\SourcingRequest',
            'fulfillmentrejected' => 'Modules\\Fulfillment\\Contracts\\FulfillmentRejected',
            'fulfillmentprogress' => 'Modules\\Fulfillment\\Domain\\FulfillmentProgress',
            'shipmentstatus' => 'Modules\\Fulfillment\\Domain\\ShipmentStatus',
            'shipmentcreated' => 'Modules\\Fulfillment\\Events\\ShipmentCreated',
            'shipmentstatuschanged' => 'Modules\\Fulfillment\\Events\\ShipmentStatusChanged',
            'shipment' => 'Modules\\Fulfillment\\Persistence\\Models\\Shipment',
            'shipmentline' => 'Modules\\Fulfillment\\Persistence\\Models\\ShipmentLine',
            'auditlogger' => 'Modules\\Identity\\Contracts\\AuditLogger',
            'reservedline' => 'Modules\\Inventory\\Contracts\\Data\\ReservedLine',
            'inventoryreservation' => 'Modules\\Inventory\\Contracts\\InventoryReservation',
            'inventoryreturns' => 'Modules\\Inventory\\Contracts\\InventoryReturns',
            'orderlinedata' => 'Modules\\Ordering\\Contracts\\Data\\OrderLineData',
            'orderstatus' => 'Modules\\Ordering\\Contracts\\Data\\OrderStatus',
            'orderreader' => 'Modules\\Ordering\\Contracts\\OrderReader',
            'ordertransitions' => 'Modules\\Ordering\\Contracts\\OrderTransitions',
            'money' => 'Modules\\Shared\\Domain\\Money\\Money',
            'throwable' => 'Throwable',
          ),
           'className' => 'Modules\\Fulfillment\\Application\\FulfillmentService',
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
      'c08fd516c6de8da58d2fb0f754d3e9d9' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Fulfillment\\Application',
         'uses' => 
        array (
          'datetimeimmutable' => 'DateTimeImmutable',
          'uniqueconstraintviolationexception' => 'Illuminate\\Database\\UniqueConstraintViolationException',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'log' => 'Illuminate\\Support\\Facades\\Log',
          'str' => 'Illuminate\\Support\\Str',
          'allocationproposal' => 'Modules\\Fulfillment\\Contracts\\Data\\AllocationProposal',
          'shipmentdata' => 'Modules\\Fulfillment\\Contracts\\Data\\ShipmentData',
          'sourcingrequest' => 'Modules\\Fulfillment\\Contracts\\Data\\SourcingRequest',
          'fulfillmentrejected' => 'Modules\\Fulfillment\\Contracts\\FulfillmentRejected',
          'fulfillmentprogress' => 'Modules\\Fulfillment\\Domain\\FulfillmentProgress',
          'shipmentstatus' => 'Modules\\Fulfillment\\Domain\\ShipmentStatus',
          'shipmentcreated' => 'Modules\\Fulfillment\\Events\\ShipmentCreated',
          'shipmentstatuschanged' => 'Modules\\Fulfillment\\Events\\ShipmentStatusChanged',
          'shipment' => 'Modules\\Fulfillment\\Persistence\\Models\\Shipment',
          'shipmentline' => 'Modules\\Fulfillment\\Persistence\\Models\\ShipmentLine',
          'auditlogger' => 'Modules\\Identity\\Contracts\\AuditLogger',
          'reservedline' => 'Modules\\Inventory\\Contracts\\Data\\ReservedLine',
          'inventoryreservation' => 'Modules\\Inventory\\Contracts\\InventoryReservation',
          'inventoryreturns' => 'Modules\\Inventory\\Contracts\\InventoryReturns',
          'orderlinedata' => 'Modules\\Ordering\\Contracts\\Data\\OrderLineData',
          'orderstatus' => 'Modules\\Ordering\\Contracts\\Data\\OrderStatus',
          'orderreader' => 'Modules\\Ordering\\Contracts\\OrderReader',
          'ordertransitions' => 'Modules\\Ordering\\Contracts\\OrderTransitions',
          'money' => 'Modules\\Shared\\Domain\\Money\\Money',
          'throwable' => 'Throwable',
        ),
         'className' => 'Modules\\Fulfillment\\Application\\FulfillmentService',
         'functionName' => 'syncOrder',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'Modules\\Fulfillment\\Application',
           'uses' => 
          array (
            'datetimeimmutable' => 'DateTimeImmutable',
            'uniqueconstraintviolationexception' => 'Illuminate\\Database\\UniqueConstraintViolationException',
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'log' => 'Illuminate\\Support\\Facades\\Log',
            'str' => 'Illuminate\\Support\\Str',
            'allocationproposal' => 'Modules\\Fulfillment\\Contracts\\Data\\AllocationProposal',
            'shipmentdata' => 'Modules\\Fulfillment\\Contracts\\Data\\ShipmentData',
            'sourcingrequest' => 'Modules\\Fulfillment\\Contracts\\Data\\SourcingRequest',
            'fulfillmentrejected' => 'Modules\\Fulfillment\\Contracts\\FulfillmentRejected',
            'fulfillmentprogress' => 'Modules\\Fulfillment\\Domain\\FulfillmentProgress',
            'shipmentstatus' => 'Modules\\Fulfillment\\Domain\\ShipmentStatus',
            'shipmentcreated' => 'Modules\\Fulfillment\\Events\\ShipmentCreated',
            'shipmentstatuschanged' => 'Modules\\Fulfillment\\Events\\ShipmentStatusChanged',
            'shipment' => 'Modules\\Fulfillment\\Persistence\\Models\\Shipment',
            'shipmentline' => 'Modules\\Fulfillment\\Persistence\\Models\\ShipmentLine',
            'auditlogger' => 'Modules\\Identity\\Contracts\\AuditLogger',
            'reservedline' => 'Modules\\Inventory\\Contracts\\Data\\ReservedLine',
            'inventoryreservation' => 'Modules\\Inventory\\Contracts\\InventoryReservation',
            'inventoryreturns' => 'Modules\\Inventory\\Contracts\\InventoryReturns',
            'orderlinedata' => 'Modules\\Ordering\\Contracts\\Data\\OrderLineData',
            'orderstatus' => 'Modules\\Ordering\\Contracts\\Data\\OrderStatus',
            'orderreader' => 'Modules\\Ordering\\Contracts\\OrderReader',
            'ordertransitions' => 'Modules\\Ordering\\Contracts\\OrderTransitions',
            'money' => 'Modules\\Shared\\Domain\\Money\\Money',
            'throwable' => 'Throwable',
          ),
           'className' => 'Modules\\Fulfillment\\Application\\FulfillmentService',
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
      '80fdc0631101e975f90ea9d4dd512278' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Fulfillment\\Application',
         'uses' => 
        array (
          'datetimeimmutable' => 'DateTimeImmutable',
          'uniqueconstraintviolationexception' => 'Illuminate\\Database\\UniqueConstraintViolationException',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'log' => 'Illuminate\\Support\\Facades\\Log',
          'str' => 'Illuminate\\Support\\Str',
          'allocationproposal' => 'Modules\\Fulfillment\\Contracts\\Data\\AllocationProposal',
          'shipmentdata' => 'Modules\\Fulfillment\\Contracts\\Data\\ShipmentData',
          'sourcingrequest' => 'Modules\\Fulfillment\\Contracts\\Data\\SourcingRequest',
          'fulfillmentrejected' => 'Modules\\Fulfillment\\Contracts\\FulfillmentRejected',
          'fulfillmentprogress' => 'Modules\\Fulfillment\\Domain\\FulfillmentProgress',
          'shipmentstatus' => 'Modules\\Fulfillment\\Domain\\ShipmentStatus',
          'shipmentcreated' => 'Modules\\Fulfillment\\Events\\ShipmentCreated',
          'shipmentstatuschanged' => 'Modules\\Fulfillment\\Events\\ShipmentStatusChanged',
          'shipment' => 'Modules\\Fulfillment\\Persistence\\Models\\Shipment',
          'shipmentline' => 'Modules\\Fulfillment\\Persistence\\Models\\ShipmentLine',
          'auditlogger' => 'Modules\\Identity\\Contracts\\AuditLogger',
          'reservedline' => 'Modules\\Inventory\\Contracts\\Data\\ReservedLine',
          'inventoryreservation' => 'Modules\\Inventory\\Contracts\\InventoryReservation',
          'inventoryreturns' => 'Modules\\Inventory\\Contracts\\InventoryReturns',
          'orderlinedata' => 'Modules\\Ordering\\Contracts\\Data\\OrderLineData',
          'orderstatus' => 'Modules\\Ordering\\Contracts\\Data\\OrderStatus',
          'orderreader' => 'Modules\\Ordering\\Contracts\\OrderReader',
          'ordertransitions' => 'Modules\\Ordering\\Contracts\\OrderTransitions',
          'money' => 'Modules\\Shared\\Domain\\Money\\Money',
          'throwable' => 'Throwable',
        ),
         'className' => 'Modules\\Fulfillment\\Application\\FulfillmentService',
         'functionName' => 'recordEvent',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'Modules\\Fulfillment\\Application',
           'uses' => 
          array (
            'datetimeimmutable' => 'DateTimeImmutable',
            'uniqueconstraintviolationexception' => 'Illuminate\\Database\\UniqueConstraintViolationException',
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'log' => 'Illuminate\\Support\\Facades\\Log',
            'str' => 'Illuminate\\Support\\Str',
            'allocationproposal' => 'Modules\\Fulfillment\\Contracts\\Data\\AllocationProposal',
            'shipmentdata' => 'Modules\\Fulfillment\\Contracts\\Data\\ShipmentData',
            'sourcingrequest' => 'Modules\\Fulfillment\\Contracts\\Data\\SourcingRequest',
            'fulfillmentrejected' => 'Modules\\Fulfillment\\Contracts\\FulfillmentRejected',
            'fulfillmentprogress' => 'Modules\\Fulfillment\\Domain\\FulfillmentProgress',
            'shipmentstatus' => 'Modules\\Fulfillment\\Domain\\ShipmentStatus',
            'shipmentcreated' => 'Modules\\Fulfillment\\Events\\ShipmentCreated',
            'shipmentstatuschanged' => 'Modules\\Fulfillment\\Events\\ShipmentStatusChanged',
            'shipment' => 'Modules\\Fulfillment\\Persistence\\Models\\Shipment',
            'shipmentline' => 'Modules\\Fulfillment\\Persistence\\Models\\ShipmentLine',
            'auditlogger' => 'Modules\\Identity\\Contracts\\AuditLogger',
            'reservedline' => 'Modules\\Inventory\\Contracts\\Data\\ReservedLine',
            'inventoryreservation' => 'Modules\\Inventory\\Contracts\\InventoryReservation',
            'inventoryreturns' => 'Modules\\Inventory\\Contracts\\InventoryReturns',
            'orderlinedata' => 'Modules\\Ordering\\Contracts\\Data\\OrderLineData',
            'orderstatus' => 'Modules\\Ordering\\Contracts\\Data\\OrderStatus',
            'orderreader' => 'Modules\\Ordering\\Contracts\\OrderReader',
            'ordertransitions' => 'Modules\\Ordering\\Contracts\\OrderTransitions',
            'money' => 'Modules\\Shared\\Domain\\Money\\Money',
            'throwable' => 'Throwable',
          ),
           'className' => 'Modules\\Fulfillment\\Application\\FulfillmentService',
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
      'fcc3b962b2eede4878bc7b6037275ed1' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Fulfillment\\Application',
         'uses' => 
        array (
          'datetimeimmutable' => 'DateTimeImmutable',
          'uniqueconstraintviolationexception' => 'Illuminate\\Database\\UniqueConstraintViolationException',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'log' => 'Illuminate\\Support\\Facades\\Log',
          'str' => 'Illuminate\\Support\\Str',
          'allocationproposal' => 'Modules\\Fulfillment\\Contracts\\Data\\AllocationProposal',
          'shipmentdata' => 'Modules\\Fulfillment\\Contracts\\Data\\ShipmentData',
          'sourcingrequest' => 'Modules\\Fulfillment\\Contracts\\Data\\SourcingRequest',
          'fulfillmentrejected' => 'Modules\\Fulfillment\\Contracts\\FulfillmentRejected',
          'fulfillmentprogress' => 'Modules\\Fulfillment\\Domain\\FulfillmentProgress',
          'shipmentstatus' => 'Modules\\Fulfillment\\Domain\\ShipmentStatus',
          'shipmentcreated' => 'Modules\\Fulfillment\\Events\\ShipmentCreated',
          'shipmentstatuschanged' => 'Modules\\Fulfillment\\Events\\ShipmentStatusChanged',
          'shipment' => 'Modules\\Fulfillment\\Persistence\\Models\\Shipment',
          'shipmentline' => 'Modules\\Fulfillment\\Persistence\\Models\\ShipmentLine',
          'auditlogger' => 'Modules\\Identity\\Contracts\\AuditLogger',
          'reservedline' => 'Modules\\Inventory\\Contracts\\Data\\ReservedLine',
          'inventoryreservation' => 'Modules\\Inventory\\Contracts\\InventoryReservation',
          'inventoryreturns' => 'Modules\\Inventory\\Contracts\\InventoryReturns',
          'orderlinedata' => 'Modules\\Ordering\\Contracts\\Data\\OrderLineData',
          'orderstatus' => 'Modules\\Ordering\\Contracts\\Data\\OrderStatus',
          'orderreader' => 'Modules\\Ordering\\Contracts\\OrderReader',
          'ordertransitions' => 'Modules\\Ordering\\Contracts\\OrderTransitions',
          'money' => 'Modules\\Shared\\Domain\\Money\\Money',
          'throwable' => 'Throwable',
        ),
         'className' => 'Modules\\Fulfillment\\Application\\FulfillmentService',
         'functionName' => 'assertMatchesReservation',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'Modules\\Fulfillment\\Application',
           'uses' => 
          array (
            'datetimeimmutable' => 'DateTimeImmutable',
            'uniqueconstraintviolationexception' => 'Illuminate\\Database\\UniqueConstraintViolationException',
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'log' => 'Illuminate\\Support\\Facades\\Log',
            'str' => 'Illuminate\\Support\\Str',
            'allocationproposal' => 'Modules\\Fulfillment\\Contracts\\Data\\AllocationProposal',
            'shipmentdata' => 'Modules\\Fulfillment\\Contracts\\Data\\ShipmentData',
            'sourcingrequest' => 'Modules\\Fulfillment\\Contracts\\Data\\SourcingRequest',
            'fulfillmentrejected' => 'Modules\\Fulfillment\\Contracts\\FulfillmentRejected',
            'fulfillmentprogress' => 'Modules\\Fulfillment\\Domain\\FulfillmentProgress',
            'shipmentstatus' => 'Modules\\Fulfillment\\Domain\\ShipmentStatus',
            'shipmentcreated' => 'Modules\\Fulfillment\\Events\\ShipmentCreated',
            'shipmentstatuschanged' => 'Modules\\Fulfillment\\Events\\ShipmentStatusChanged',
            'shipment' => 'Modules\\Fulfillment\\Persistence\\Models\\Shipment',
            'shipmentline' => 'Modules\\Fulfillment\\Persistence\\Models\\ShipmentLine',
            'auditlogger' => 'Modules\\Identity\\Contracts\\AuditLogger',
            'reservedline' => 'Modules\\Inventory\\Contracts\\Data\\ReservedLine',
            'inventoryreservation' => 'Modules\\Inventory\\Contracts\\InventoryReservation',
            'inventoryreturns' => 'Modules\\Inventory\\Contracts\\InventoryReturns',
            'orderlinedata' => 'Modules\\Ordering\\Contracts\\Data\\OrderLineData',
            'orderstatus' => 'Modules\\Ordering\\Contracts\\Data\\OrderStatus',
            'orderreader' => 'Modules\\Ordering\\Contracts\\OrderReader',
            'ordertransitions' => 'Modules\\Ordering\\Contracts\\OrderTransitions',
            'money' => 'Modules\\Shared\\Domain\\Money\\Money',
            'throwable' => 'Throwable',
          ),
           'className' => 'Modules\\Fulfillment\\Application\\FulfillmentService',
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
      '/Users/dat/Ecommerce/vanishop/modules/Fulfillment/Application/FulfillmentService.php' => '22d8dd4a4c1db9bba70ac3266dfcf09e2badc22621a6df8af8d84c0d7a70b7af',
    ),
  ),
));