<?php declare(strict_types = 1);

// ftm-/Users/dat/Ecommerce/vanishop/modules/Returns/Application/ReturnService.php
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v6-2.3.5',
   'data' => 
  array (
    0 => 
    array (
      '9972b167be524caa00038cabe3fa6c3c' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Returns\\Application',
         'uses' => 
        array (
          'datetimeimmutable' => 'DateTimeImmutable',
          'carbon' => 'Illuminate\\Support\\Carbon',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'str' => 'Illuminate\\Support\\Str',
          'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
          'shipmentreader' => 'Modules\\Fulfillment\\Contracts\\ShipmentReader',
          'auditlogger' => 'Modules\\Identity\\Contracts\\AuditLogger',
          'inventoryreturns' => 'Modules\\Inventory\\Contracts\\InventoryReturns',
          'orderlinedata' => 'Modules\\Ordering\\Contracts\\Data\\OrderLineData',
          'orderreader' => 'Modules\\Ordering\\Contracts\\OrderReader',
          'ordertransitions' => 'Modules\\Ordering\\Contracts\\OrderTransitions',
          'payments' => 'Modules\\Payment\\Contracts\\Payments',
          'returncontext' => 'Modules\\Returns\\Contracts\\Data\\ReturnContext',
          'returnview' => 'Modules\\Returns\\Contracts\\Data\\ReturnView',
          'returnpolicy' => 'Modules\\Returns\\Contracts\\ReturnPolicy',
          'returnrejected' => 'Modules\\Returns\\Contracts\\ReturnRejected',
          'returns' => 'Modules\\Returns\\Contracts\\Returns',
          'refundcalculator' => 'Modules\\Returns\\Domain\\RefundCalculator',
          'returnstatus' => 'Modules\\Returns\\Domain\\ReturnStatus',
          'returnrequested' => 'Modules\\Returns\\Events\\ReturnRequested',
          'returnresolved' => 'Modules\\Returns\\Events\\ReturnResolved',
          'returnline' => 'Modules\\Returns\\Persistence\\Models\\ReturnLine',
          'returnrequest' => 'Modules\\Returns\\Persistence\\Models\\ReturnRequest',
          'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
          'money' => 'Modules\\Shared\\Domain\\Money\\Money',
          'settings' => 'Modules\\Tenancy\\Contracts\\Settings',
        ),
         'className' => 'Modules\\Returns\\Application\\ReturnService',
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
      '7dc3b1e4ed1a5741757949efcf53792e' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Returns\\Application',
         'uses' => 
        array (
          'datetimeimmutable' => 'DateTimeImmutable',
          'carbon' => 'Illuminate\\Support\\Carbon',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'str' => 'Illuminate\\Support\\Str',
          'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
          'shipmentreader' => 'Modules\\Fulfillment\\Contracts\\ShipmentReader',
          'auditlogger' => 'Modules\\Identity\\Contracts\\AuditLogger',
          'inventoryreturns' => 'Modules\\Inventory\\Contracts\\InventoryReturns',
          'orderlinedata' => 'Modules\\Ordering\\Contracts\\Data\\OrderLineData',
          'orderreader' => 'Modules\\Ordering\\Contracts\\OrderReader',
          'ordertransitions' => 'Modules\\Ordering\\Contracts\\OrderTransitions',
          'payments' => 'Modules\\Payment\\Contracts\\Payments',
          'returncontext' => 'Modules\\Returns\\Contracts\\Data\\ReturnContext',
          'returnview' => 'Modules\\Returns\\Contracts\\Data\\ReturnView',
          'returnpolicy' => 'Modules\\Returns\\Contracts\\ReturnPolicy',
          'returnrejected' => 'Modules\\Returns\\Contracts\\ReturnRejected',
          'returns' => 'Modules\\Returns\\Contracts\\Returns',
          'refundcalculator' => 'Modules\\Returns\\Domain\\RefundCalculator',
          'returnstatus' => 'Modules\\Returns\\Domain\\ReturnStatus',
          'returnrequested' => 'Modules\\Returns\\Events\\ReturnRequested',
          'returnresolved' => 'Modules\\Returns\\Events\\ReturnResolved',
          'returnline' => 'Modules\\Returns\\Persistence\\Models\\ReturnLine',
          'returnrequest' => 'Modules\\Returns\\Persistence\\Models\\ReturnRequest',
          'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
          'money' => 'Modules\\Shared\\Domain\\Money\\Money',
          'settings' => 'Modules\\Tenancy\\Contracts\\Settings',
        ),
         'className' => 'Modules\\Returns\\Application\\ReturnService',
         'functionName' => '__construct',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'Modules\\Returns\\Application',
           'uses' => 
          array (
            'datetimeimmutable' => 'DateTimeImmutable',
            'carbon' => 'Illuminate\\Support\\Carbon',
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'str' => 'Illuminate\\Support\\Str',
            'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
            'shipmentreader' => 'Modules\\Fulfillment\\Contracts\\ShipmentReader',
            'auditlogger' => 'Modules\\Identity\\Contracts\\AuditLogger',
            'inventoryreturns' => 'Modules\\Inventory\\Contracts\\InventoryReturns',
            'orderlinedata' => 'Modules\\Ordering\\Contracts\\Data\\OrderLineData',
            'orderreader' => 'Modules\\Ordering\\Contracts\\OrderReader',
            'ordertransitions' => 'Modules\\Ordering\\Contracts\\OrderTransitions',
            'payments' => 'Modules\\Payment\\Contracts\\Payments',
            'returncontext' => 'Modules\\Returns\\Contracts\\Data\\ReturnContext',
            'returnview' => 'Modules\\Returns\\Contracts\\Data\\ReturnView',
            'returnpolicy' => 'Modules\\Returns\\Contracts\\ReturnPolicy',
            'returnrejected' => 'Modules\\Returns\\Contracts\\ReturnRejected',
            'returns' => 'Modules\\Returns\\Contracts\\Returns',
            'refundcalculator' => 'Modules\\Returns\\Domain\\RefundCalculator',
            'returnstatus' => 'Modules\\Returns\\Domain\\ReturnStatus',
            'returnrequested' => 'Modules\\Returns\\Events\\ReturnRequested',
            'returnresolved' => 'Modules\\Returns\\Events\\ReturnResolved',
            'returnline' => 'Modules\\Returns\\Persistence\\Models\\ReturnLine',
            'returnrequest' => 'Modules\\Returns\\Persistence\\Models\\ReturnRequest',
            'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
            'money' => 'Modules\\Shared\\Domain\\Money\\Money',
            'settings' => 'Modules\\Tenancy\\Contracts\\Settings',
          ),
           'className' => 'Modules\\Returns\\Application\\ReturnService',
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
      'de95e56cde8c49b303f7d3bed54911f2' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Returns\\Application',
         'uses' => 
        array (
          'datetimeimmutable' => 'DateTimeImmutable',
          'carbon' => 'Illuminate\\Support\\Carbon',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'str' => 'Illuminate\\Support\\Str',
          'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
          'shipmentreader' => 'Modules\\Fulfillment\\Contracts\\ShipmentReader',
          'auditlogger' => 'Modules\\Identity\\Contracts\\AuditLogger',
          'inventoryreturns' => 'Modules\\Inventory\\Contracts\\InventoryReturns',
          'orderlinedata' => 'Modules\\Ordering\\Contracts\\Data\\OrderLineData',
          'orderreader' => 'Modules\\Ordering\\Contracts\\OrderReader',
          'ordertransitions' => 'Modules\\Ordering\\Contracts\\OrderTransitions',
          'payments' => 'Modules\\Payment\\Contracts\\Payments',
          'returncontext' => 'Modules\\Returns\\Contracts\\Data\\ReturnContext',
          'returnview' => 'Modules\\Returns\\Contracts\\Data\\ReturnView',
          'returnpolicy' => 'Modules\\Returns\\Contracts\\ReturnPolicy',
          'returnrejected' => 'Modules\\Returns\\Contracts\\ReturnRejected',
          'returns' => 'Modules\\Returns\\Contracts\\Returns',
          'refundcalculator' => 'Modules\\Returns\\Domain\\RefundCalculator',
          'returnstatus' => 'Modules\\Returns\\Domain\\ReturnStatus',
          'returnrequested' => 'Modules\\Returns\\Events\\ReturnRequested',
          'returnresolved' => 'Modules\\Returns\\Events\\ReturnResolved',
          'returnline' => 'Modules\\Returns\\Persistence\\Models\\ReturnLine',
          'returnrequest' => 'Modules\\Returns\\Persistence\\Models\\ReturnRequest',
          'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
          'money' => 'Modules\\Shared\\Domain\\Money\\Money',
          'settings' => 'Modules\\Tenancy\\Contracts\\Settings',
        ),
         'className' => 'Modules\\Returns\\Application\\ReturnService',
         'functionName' => 'request',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'Modules\\Returns\\Application',
           'uses' => 
          array (
            'datetimeimmutable' => 'DateTimeImmutable',
            'carbon' => 'Illuminate\\Support\\Carbon',
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'str' => 'Illuminate\\Support\\Str',
            'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
            'shipmentreader' => 'Modules\\Fulfillment\\Contracts\\ShipmentReader',
            'auditlogger' => 'Modules\\Identity\\Contracts\\AuditLogger',
            'inventoryreturns' => 'Modules\\Inventory\\Contracts\\InventoryReturns',
            'orderlinedata' => 'Modules\\Ordering\\Contracts\\Data\\OrderLineData',
            'orderreader' => 'Modules\\Ordering\\Contracts\\OrderReader',
            'ordertransitions' => 'Modules\\Ordering\\Contracts\\OrderTransitions',
            'payments' => 'Modules\\Payment\\Contracts\\Payments',
            'returncontext' => 'Modules\\Returns\\Contracts\\Data\\ReturnContext',
            'returnview' => 'Modules\\Returns\\Contracts\\Data\\ReturnView',
            'returnpolicy' => 'Modules\\Returns\\Contracts\\ReturnPolicy',
            'returnrejected' => 'Modules\\Returns\\Contracts\\ReturnRejected',
            'returns' => 'Modules\\Returns\\Contracts\\Returns',
            'refundcalculator' => 'Modules\\Returns\\Domain\\RefundCalculator',
            'returnstatus' => 'Modules\\Returns\\Domain\\ReturnStatus',
            'returnrequested' => 'Modules\\Returns\\Events\\ReturnRequested',
            'returnresolved' => 'Modules\\Returns\\Events\\ReturnResolved',
            'returnline' => 'Modules\\Returns\\Persistence\\Models\\ReturnLine',
            'returnrequest' => 'Modules\\Returns\\Persistence\\Models\\ReturnRequest',
            'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
            'money' => 'Modules\\Shared\\Domain\\Money\\Money',
            'settings' => 'Modules\\Tenancy\\Contracts\\Settings',
          ),
           'className' => 'Modules\\Returns\\Application\\ReturnService',
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
      '88c8f5ee3f58306e0daa7d214747eece' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Returns\\Application',
         'uses' => 
        array (
          'datetimeimmutable' => 'DateTimeImmutable',
          'carbon' => 'Illuminate\\Support\\Carbon',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'str' => 'Illuminate\\Support\\Str',
          'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
          'shipmentreader' => 'Modules\\Fulfillment\\Contracts\\ShipmentReader',
          'auditlogger' => 'Modules\\Identity\\Contracts\\AuditLogger',
          'inventoryreturns' => 'Modules\\Inventory\\Contracts\\InventoryReturns',
          'orderlinedata' => 'Modules\\Ordering\\Contracts\\Data\\OrderLineData',
          'orderreader' => 'Modules\\Ordering\\Contracts\\OrderReader',
          'ordertransitions' => 'Modules\\Ordering\\Contracts\\OrderTransitions',
          'payments' => 'Modules\\Payment\\Contracts\\Payments',
          'returncontext' => 'Modules\\Returns\\Contracts\\Data\\ReturnContext',
          'returnview' => 'Modules\\Returns\\Contracts\\Data\\ReturnView',
          'returnpolicy' => 'Modules\\Returns\\Contracts\\ReturnPolicy',
          'returnrejected' => 'Modules\\Returns\\Contracts\\ReturnRejected',
          'returns' => 'Modules\\Returns\\Contracts\\Returns',
          'refundcalculator' => 'Modules\\Returns\\Domain\\RefundCalculator',
          'returnstatus' => 'Modules\\Returns\\Domain\\ReturnStatus',
          'returnrequested' => 'Modules\\Returns\\Events\\ReturnRequested',
          'returnresolved' => 'Modules\\Returns\\Events\\ReturnResolved',
          'returnline' => 'Modules\\Returns\\Persistence\\Models\\ReturnLine',
          'returnrequest' => 'Modules\\Returns\\Persistence\\Models\\ReturnRequest',
          'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
          'money' => 'Modules\\Shared\\Domain\\Money\\Money',
          'settings' => 'Modules\\Tenancy\\Contracts\\Settings',
        ),
         'className' => 'Modules\\Returns\\Application\\ReturnService',
         'functionName' => 'forOrder',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'Modules\\Returns\\Application',
           'uses' => 
          array (
            'datetimeimmutable' => 'DateTimeImmutable',
            'carbon' => 'Illuminate\\Support\\Carbon',
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'str' => 'Illuminate\\Support\\Str',
            'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
            'shipmentreader' => 'Modules\\Fulfillment\\Contracts\\ShipmentReader',
            'auditlogger' => 'Modules\\Identity\\Contracts\\AuditLogger',
            'inventoryreturns' => 'Modules\\Inventory\\Contracts\\InventoryReturns',
            'orderlinedata' => 'Modules\\Ordering\\Contracts\\Data\\OrderLineData',
            'orderreader' => 'Modules\\Ordering\\Contracts\\OrderReader',
            'ordertransitions' => 'Modules\\Ordering\\Contracts\\OrderTransitions',
            'payments' => 'Modules\\Payment\\Contracts\\Payments',
            'returncontext' => 'Modules\\Returns\\Contracts\\Data\\ReturnContext',
            'returnview' => 'Modules\\Returns\\Contracts\\Data\\ReturnView',
            'returnpolicy' => 'Modules\\Returns\\Contracts\\ReturnPolicy',
            'returnrejected' => 'Modules\\Returns\\Contracts\\ReturnRejected',
            'returns' => 'Modules\\Returns\\Contracts\\Returns',
            'refundcalculator' => 'Modules\\Returns\\Domain\\RefundCalculator',
            'returnstatus' => 'Modules\\Returns\\Domain\\ReturnStatus',
            'returnrequested' => 'Modules\\Returns\\Events\\ReturnRequested',
            'returnresolved' => 'Modules\\Returns\\Events\\ReturnResolved',
            'returnline' => 'Modules\\Returns\\Persistence\\Models\\ReturnLine',
            'returnrequest' => 'Modules\\Returns\\Persistence\\Models\\ReturnRequest',
            'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
            'money' => 'Modules\\Shared\\Domain\\Money\\Money',
            'settings' => 'Modules\\Tenancy\\Contracts\\Settings',
          ),
           'className' => 'Modules\\Returns\\Application\\ReturnService',
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
      'dbb7eb5e81a824f97f82eea8f11c35ff' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Returns\\Application',
         'uses' => 
        array (
          'datetimeimmutable' => 'DateTimeImmutable',
          'carbon' => 'Illuminate\\Support\\Carbon',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'str' => 'Illuminate\\Support\\Str',
          'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
          'shipmentreader' => 'Modules\\Fulfillment\\Contracts\\ShipmentReader',
          'auditlogger' => 'Modules\\Identity\\Contracts\\AuditLogger',
          'inventoryreturns' => 'Modules\\Inventory\\Contracts\\InventoryReturns',
          'orderlinedata' => 'Modules\\Ordering\\Contracts\\Data\\OrderLineData',
          'orderreader' => 'Modules\\Ordering\\Contracts\\OrderReader',
          'ordertransitions' => 'Modules\\Ordering\\Contracts\\OrderTransitions',
          'payments' => 'Modules\\Payment\\Contracts\\Payments',
          'returncontext' => 'Modules\\Returns\\Contracts\\Data\\ReturnContext',
          'returnview' => 'Modules\\Returns\\Contracts\\Data\\ReturnView',
          'returnpolicy' => 'Modules\\Returns\\Contracts\\ReturnPolicy',
          'returnrejected' => 'Modules\\Returns\\Contracts\\ReturnRejected',
          'returns' => 'Modules\\Returns\\Contracts\\Returns',
          'refundcalculator' => 'Modules\\Returns\\Domain\\RefundCalculator',
          'returnstatus' => 'Modules\\Returns\\Domain\\ReturnStatus',
          'returnrequested' => 'Modules\\Returns\\Events\\ReturnRequested',
          'returnresolved' => 'Modules\\Returns\\Events\\ReturnResolved',
          'returnline' => 'Modules\\Returns\\Persistence\\Models\\ReturnLine',
          'returnrequest' => 'Modules\\Returns\\Persistence\\Models\\ReturnRequest',
          'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
          'money' => 'Modules\\Shared\\Domain\\Money\\Money',
          'settings' => 'Modules\\Tenancy\\Contracts\\Settings',
        ),
         'className' => 'Modules\\Returns\\Application\\ReturnService',
         'functionName' => 'returnable',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'Modules\\Returns\\Application',
           'uses' => 
          array (
            'datetimeimmutable' => 'DateTimeImmutable',
            'carbon' => 'Illuminate\\Support\\Carbon',
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'str' => 'Illuminate\\Support\\Str',
            'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
            'shipmentreader' => 'Modules\\Fulfillment\\Contracts\\ShipmentReader',
            'auditlogger' => 'Modules\\Identity\\Contracts\\AuditLogger',
            'inventoryreturns' => 'Modules\\Inventory\\Contracts\\InventoryReturns',
            'orderlinedata' => 'Modules\\Ordering\\Contracts\\Data\\OrderLineData',
            'orderreader' => 'Modules\\Ordering\\Contracts\\OrderReader',
            'ordertransitions' => 'Modules\\Ordering\\Contracts\\OrderTransitions',
            'payments' => 'Modules\\Payment\\Contracts\\Payments',
            'returncontext' => 'Modules\\Returns\\Contracts\\Data\\ReturnContext',
            'returnview' => 'Modules\\Returns\\Contracts\\Data\\ReturnView',
            'returnpolicy' => 'Modules\\Returns\\Contracts\\ReturnPolicy',
            'returnrejected' => 'Modules\\Returns\\Contracts\\ReturnRejected',
            'returns' => 'Modules\\Returns\\Contracts\\Returns',
            'refundcalculator' => 'Modules\\Returns\\Domain\\RefundCalculator',
            'returnstatus' => 'Modules\\Returns\\Domain\\ReturnStatus',
            'returnrequested' => 'Modules\\Returns\\Events\\ReturnRequested',
            'returnresolved' => 'Modules\\Returns\\Events\\ReturnResolved',
            'returnline' => 'Modules\\Returns\\Persistence\\Models\\ReturnLine',
            'returnrequest' => 'Modules\\Returns\\Persistence\\Models\\ReturnRequest',
            'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
            'money' => 'Modules\\Shared\\Domain\\Money\\Money',
            'settings' => 'Modules\\Tenancy\\Contracts\\Settings',
          ),
           'className' => 'Modules\\Returns\\Application\\ReturnService',
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
      '5ec8130d845ad852b1fb27fd84c7f653' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Returns\\Application',
         'uses' => 
        array (
          'datetimeimmutable' => 'DateTimeImmutable',
          'carbon' => 'Illuminate\\Support\\Carbon',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'str' => 'Illuminate\\Support\\Str',
          'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
          'shipmentreader' => 'Modules\\Fulfillment\\Contracts\\ShipmentReader',
          'auditlogger' => 'Modules\\Identity\\Contracts\\AuditLogger',
          'inventoryreturns' => 'Modules\\Inventory\\Contracts\\InventoryReturns',
          'orderlinedata' => 'Modules\\Ordering\\Contracts\\Data\\OrderLineData',
          'orderreader' => 'Modules\\Ordering\\Contracts\\OrderReader',
          'ordertransitions' => 'Modules\\Ordering\\Contracts\\OrderTransitions',
          'payments' => 'Modules\\Payment\\Contracts\\Payments',
          'returncontext' => 'Modules\\Returns\\Contracts\\Data\\ReturnContext',
          'returnview' => 'Modules\\Returns\\Contracts\\Data\\ReturnView',
          'returnpolicy' => 'Modules\\Returns\\Contracts\\ReturnPolicy',
          'returnrejected' => 'Modules\\Returns\\Contracts\\ReturnRejected',
          'returns' => 'Modules\\Returns\\Contracts\\Returns',
          'refundcalculator' => 'Modules\\Returns\\Domain\\RefundCalculator',
          'returnstatus' => 'Modules\\Returns\\Domain\\ReturnStatus',
          'returnrequested' => 'Modules\\Returns\\Events\\ReturnRequested',
          'returnresolved' => 'Modules\\Returns\\Events\\ReturnResolved',
          'returnline' => 'Modules\\Returns\\Persistence\\Models\\ReturnLine',
          'returnrequest' => 'Modules\\Returns\\Persistence\\Models\\ReturnRequest',
          'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
          'money' => 'Modules\\Shared\\Domain\\Money\\Money',
          'settings' => 'Modules\\Tenancy\\Contracts\\Settings',
        ),
         'className' => 'Modules\\Returns\\Application\\ReturnService',
         'functionName' => 'cancel',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'Modules\\Returns\\Application',
           'uses' => 
          array (
            'datetimeimmutable' => 'DateTimeImmutable',
            'carbon' => 'Illuminate\\Support\\Carbon',
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'str' => 'Illuminate\\Support\\Str',
            'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
            'shipmentreader' => 'Modules\\Fulfillment\\Contracts\\ShipmentReader',
            'auditlogger' => 'Modules\\Identity\\Contracts\\AuditLogger',
            'inventoryreturns' => 'Modules\\Inventory\\Contracts\\InventoryReturns',
            'orderlinedata' => 'Modules\\Ordering\\Contracts\\Data\\OrderLineData',
            'orderreader' => 'Modules\\Ordering\\Contracts\\OrderReader',
            'ordertransitions' => 'Modules\\Ordering\\Contracts\\OrderTransitions',
            'payments' => 'Modules\\Payment\\Contracts\\Payments',
            'returncontext' => 'Modules\\Returns\\Contracts\\Data\\ReturnContext',
            'returnview' => 'Modules\\Returns\\Contracts\\Data\\ReturnView',
            'returnpolicy' => 'Modules\\Returns\\Contracts\\ReturnPolicy',
            'returnrejected' => 'Modules\\Returns\\Contracts\\ReturnRejected',
            'returns' => 'Modules\\Returns\\Contracts\\Returns',
            'refundcalculator' => 'Modules\\Returns\\Domain\\RefundCalculator',
            'returnstatus' => 'Modules\\Returns\\Domain\\ReturnStatus',
            'returnrequested' => 'Modules\\Returns\\Events\\ReturnRequested',
            'returnresolved' => 'Modules\\Returns\\Events\\ReturnResolved',
            'returnline' => 'Modules\\Returns\\Persistence\\Models\\ReturnLine',
            'returnrequest' => 'Modules\\Returns\\Persistence\\Models\\ReturnRequest',
            'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
            'money' => 'Modules\\Shared\\Domain\\Money\\Money',
            'settings' => 'Modules\\Tenancy\\Contracts\\Settings',
          ),
           'className' => 'Modules\\Returns\\Application\\ReturnService',
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
      'f2a21d1debecb3937897423b8847578e' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Returns\\Application',
         'uses' => 
        array (
          'datetimeimmutable' => 'DateTimeImmutable',
          'carbon' => 'Illuminate\\Support\\Carbon',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'str' => 'Illuminate\\Support\\Str',
          'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
          'shipmentreader' => 'Modules\\Fulfillment\\Contracts\\ShipmentReader',
          'auditlogger' => 'Modules\\Identity\\Contracts\\AuditLogger',
          'inventoryreturns' => 'Modules\\Inventory\\Contracts\\InventoryReturns',
          'orderlinedata' => 'Modules\\Ordering\\Contracts\\Data\\OrderLineData',
          'orderreader' => 'Modules\\Ordering\\Contracts\\OrderReader',
          'ordertransitions' => 'Modules\\Ordering\\Contracts\\OrderTransitions',
          'payments' => 'Modules\\Payment\\Contracts\\Payments',
          'returncontext' => 'Modules\\Returns\\Contracts\\Data\\ReturnContext',
          'returnview' => 'Modules\\Returns\\Contracts\\Data\\ReturnView',
          'returnpolicy' => 'Modules\\Returns\\Contracts\\ReturnPolicy',
          'returnrejected' => 'Modules\\Returns\\Contracts\\ReturnRejected',
          'returns' => 'Modules\\Returns\\Contracts\\Returns',
          'refundcalculator' => 'Modules\\Returns\\Domain\\RefundCalculator',
          'returnstatus' => 'Modules\\Returns\\Domain\\ReturnStatus',
          'returnrequested' => 'Modules\\Returns\\Events\\ReturnRequested',
          'returnresolved' => 'Modules\\Returns\\Events\\ReturnResolved',
          'returnline' => 'Modules\\Returns\\Persistence\\Models\\ReturnLine',
          'returnrequest' => 'Modules\\Returns\\Persistence\\Models\\ReturnRequest',
          'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
          'money' => 'Modules\\Shared\\Domain\\Money\\Money',
          'settings' => 'Modules\\Tenancy\\Contracts\\Settings',
        ),
         'className' => 'Modules\\Returns\\Application\\ReturnService',
         'functionName' => 'transition',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'Modules\\Returns\\Application',
           'uses' => 
          array (
            'datetimeimmutable' => 'DateTimeImmutable',
            'carbon' => 'Illuminate\\Support\\Carbon',
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'str' => 'Illuminate\\Support\\Str',
            'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
            'shipmentreader' => 'Modules\\Fulfillment\\Contracts\\ShipmentReader',
            'auditlogger' => 'Modules\\Identity\\Contracts\\AuditLogger',
            'inventoryreturns' => 'Modules\\Inventory\\Contracts\\InventoryReturns',
            'orderlinedata' => 'Modules\\Ordering\\Contracts\\Data\\OrderLineData',
            'orderreader' => 'Modules\\Ordering\\Contracts\\OrderReader',
            'ordertransitions' => 'Modules\\Ordering\\Contracts\\OrderTransitions',
            'payments' => 'Modules\\Payment\\Contracts\\Payments',
            'returncontext' => 'Modules\\Returns\\Contracts\\Data\\ReturnContext',
            'returnview' => 'Modules\\Returns\\Contracts\\Data\\ReturnView',
            'returnpolicy' => 'Modules\\Returns\\Contracts\\ReturnPolicy',
            'returnrejected' => 'Modules\\Returns\\Contracts\\ReturnRejected',
            'returns' => 'Modules\\Returns\\Contracts\\Returns',
            'refundcalculator' => 'Modules\\Returns\\Domain\\RefundCalculator',
            'returnstatus' => 'Modules\\Returns\\Domain\\ReturnStatus',
            'returnrequested' => 'Modules\\Returns\\Events\\ReturnRequested',
            'returnresolved' => 'Modules\\Returns\\Events\\ReturnResolved',
            'returnline' => 'Modules\\Returns\\Persistence\\Models\\ReturnLine',
            'returnrequest' => 'Modules\\Returns\\Persistence\\Models\\ReturnRequest',
            'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
            'money' => 'Modules\\Shared\\Domain\\Money\\Money',
            'settings' => 'Modules\\Tenancy\\Contracts\\Settings',
          ),
           'className' => 'Modules\\Returns\\Application\\ReturnService',
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
      '84497435cf17c0d284de413bbfcf0260' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Returns\\Application',
         'uses' => 
        array (
          'datetimeimmutable' => 'DateTimeImmutable',
          'carbon' => 'Illuminate\\Support\\Carbon',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'str' => 'Illuminate\\Support\\Str',
          'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
          'shipmentreader' => 'Modules\\Fulfillment\\Contracts\\ShipmentReader',
          'auditlogger' => 'Modules\\Identity\\Contracts\\AuditLogger',
          'inventoryreturns' => 'Modules\\Inventory\\Contracts\\InventoryReturns',
          'orderlinedata' => 'Modules\\Ordering\\Contracts\\Data\\OrderLineData',
          'orderreader' => 'Modules\\Ordering\\Contracts\\OrderReader',
          'ordertransitions' => 'Modules\\Ordering\\Contracts\\OrderTransitions',
          'payments' => 'Modules\\Payment\\Contracts\\Payments',
          'returncontext' => 'Modules\\Returns\\Contracts\\Data\\ReturnContext',
          'returnview' => 'Modules\\Returns\\Contracts\\Data\\ReturnView',
          'returnpolicy' => 'Modules\\Returns\\Contracts\\ReturnPolicy',
          'returnrejected' => 'Modules\\Returns\\Contracts\\ReturnRejected',
          'returns' => 'Modules\\Returns\\Contracts\\Returns',
          'refundcalculator' => 'Modules\\Returns\\Domain\\RefundCalculator',
          'returnstatus' => 'Modules\\Returns\\Domain\\ReturnStatus',
          'returnrequested' => 'Modules\\Returns\\Events\\ReturnRequested',
          'returnresolved' => 'Modules\\Returns\\Events\\ReturnResolved',
          'returnline' => 'Modules\\Returns\\Persistence\\Models\\ReturnLine',
          'returnrequest' => 'Modules\\Returns\\Persistence\\Models\\ReturnRequest',
          'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
          'money' => 'Modules\\Shared\\Domain\\Money\\Money',
          'settings' => 'Modules\\Tenancy\\Contracts\\Settings',
        ),
         'className' => 'Modules\\Returns\\Application\\ReturnService',
         'functionName' => 'receive',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'Modules\\Returns\\Application',
           'uses' => 
          array (
            'datetimeimmutable' => 'DateTimeImmutable',
            'carbon' => 'Illuminate\\Support\\Carbon',
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'str' => 'Illuminate\\Support\\Str',
            'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
            'shipmentreader' => 'Modules\\Fulfillment\\Contracts\\ShipmentReader',
            'auditlogger' => 'Modules\\Identity\\Contracts\\AuditLogger',
            'inventoryreturns' => 'Modules\\Inventory\\Contracts\\InventoryReturns',
            'orderlinedata' => 'Modules\\Ordering\\Contracts\\Data\\OrderLineData',
            'orderreader' => 'Modules\\Ordering\\Contracts\\OrderReader',
            'ordertransitions' => 'Modules\\Ordering\\Contracts\\OrderTransitions',
            'payments' => 'Modules\\Payment\\Contracts\\Payments',
            'returncontext' => 'Modules\\Returns\\Contracts\\Data\\ReturnContext',
            'returnview' => 'Modules\\Returns\\Contracts\\Data\\ReturnView',
            'returnpolicy' => 'Modules\\Returns\\Contracts\\ReturnPolicy',
            'returnrejected' => 'Modules\\Returns\\Contracts\\ReturnRejected',
            'returns' => 'Modules\\Returns\\Contracts\\Returns',
            'refundcalculator' => 'Modules\\Returns\\Domain\\RefundCalculator',
            'returnstatus' => 'Modules\\Returns\\Domain\\ReturnStatus',
            'returnrequested' => 'Modules\\Returns\\Events\\ReturnRequested',
            'returnresolved' => 'Modules\\Returns\\Events\\ReturnResolved',
            'returnline' => 'Modules\\Returns\\Persistence\\Models\\ReturnLine',
            'returnrequest' => 'Modules\\Returns\\Persistence\\Models\\ReturnRequest',
            'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
            'money' => 'Modules\\Shared\\Domain\\Money\\Money',
            'settings' => 'Modules\\Tenancy\\Contracts\\Settings',
          ),
           'className' => 'Modules\\Returns\\Application\\ReturnService',
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
      '6e3d3f721e34ef38c2d7e931c4be0021' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Returns\\Application',
         'uses' => 
        array (
          'datetimeimmutable' => 'DateTimeImmutable',
          'carbon' => 'Illuminate\\Support\\Carbon',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'str' => 'Illuminate\\Support\\Str',
          'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
          'shipmentreader' => 'Modules\\Fulfillment\\Contracts\\ShipmentReader',
          'auditlogger' => 'Modules\\Identity\\Contracts\\AuditLogger',
          'inventoryreturns' => 'Modules\\Inventory\\Contracts\\InventoryReturns',
          'orderlinedata' => 'Modules\\Ordering\\Contracts\\Data\\OrderLineData',
          'orderreader' => 'Modules\\Ordering\\Contracts\\OrderReader',
          'ordertransitions' => 'Modules\\Ordering\\Contracts\\OrderTransitions',
          'payments' => 'Modules\\Payment\\Contracts\\Payments',
          'returncontext' => 'Modules\\Returns\\Contracts\\Data\\ReturnContext',
          'returnview' => 'Modules\\Returns\\Contracts\\Data\\ReturnView',
          'returnpolicy' => 'Modules\\Returns\\Contracts\\ReturnPolicy',
          'returnrejected' => 'Modules\\Returns\\Contracts\\ReturnRejected',
          'returns' => 'Modules\\Returns\\Contracts\\Returns',
          'refundcalculator' => 'Modules\\Returns\\Domain\\RefundCalculator',
          'returnstatus' => 'Modules\\Returns\\Domain\\ReturnStatus',
          'returnrequested' => 'Modules\\Returns\\Events\\ReturnRequested',
          'returnresolved' => 'Modules\\Returns\\Events\\ReturnResolved',
          'returnline' => 'Modules\\Returns\\Persistence\\Models\\ReturnLine',
          'returnrequest' => 'Modules\\Returns\\Persistence\\Models\\ReturnRequest',
          'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
          'money' => 'Modules\\Shared\\Domain\\Money\\Money',
          'settings' => 'Modules\\Tenancy\\Contracts\\Settings',
        ),
         'className' => 'Modules\\Returns\\Application\\ReturnService',
         'functionName' => 'resolve',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'Modules\\Returns\\Application',
           'uses' => 
          array (
            'datetimeimmutable' => 'DateTimeImmutable',
            'carbon' => 'Illuminate\\Support\\Carbon',
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'str' => 'Illuminate\\Support\\Str',
            'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
            'shipmentreader' => 'Modules\\Fulfillment\\Contracts\\ShipmentReader',
            'auditlogger' => 'Modules\\Identity\\Contracts\\AuditLogger',
            'inventoryreturns' => 'Modules\\Inventory\\Contracts\\InventoryReturns',
            'orderlinedata' => 'Modules\\Ordering\\Contracts\\Data\\OrderLineData',
            'orderreader' => 'Modules\\Ordering\\Contracts\\OrderReader',
            'ordertransitions' => 'Modules\\Ordering\\Contracts\\OrderTransitions',
            'payments' => 'Modules\\Payment\\Contracts\\Payments',
            'returncontext' => 'Modules\\Returns\\Contracts\\Data\\ReturnContext',
            'returnview' => 'Modules\\Returns\\Contracts\\Data\\ReturnView',
            'returnpolicy' => 'Modules\\Returns\\Contracts\\ReturnPolicy',
            'returnrejected' => 'Modules\\Returns\\Contracts\\ReturnRejected',
            'returns' => 'Modules\\Returns\\Contracts\\Returns',
            'refundcalculator' => 'Modules\\Returns\\Domain\\RefundCalculator',
            'returnstatus' => 'Modules\\Returns\\Domain\\ReturnStatus',
            'returnrequested' => 'Modules\\Returns\\Events\\ReturnRequested',
            'returnresolved' => 'Modules\\Returns\\Events\\ReturnResolved',
            'returnline' => 'Modules\\Returns\\Persistence\\Models\\ReturnLine',
            'returnrequest' => 'Modules\\Returns\\Persistence\\Models\\ReturnRequest',
            'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
            'money' => 'Modules\\Shared\\Domain\\Money\\Money',
            'settings' => 'Modules\\Tenancy\\Contracts\\Settings',
          ),
           'className' => 'Modules\\Returns\\Application\\ReturnService',
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
      '79031ee78589ad5608d82c9bbcfd62ce' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Returns\\Application',
         'uses' => 
        array (
          'datetimeimmutable' => 'DateTimeImmutable',
          'carbon' => 'Illuminate\\Support\\Carbon',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'str' => 'Illuminate\\Support\\Str',
          'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
          'shipmentreader' => 'Modules\\Fulfillment\\Contracts\\ShipmentReader',
          'auditlogger' => 'Modules\\Identity\\Contracts\\AuditLogger',
          'inventoryreturns' => 'Modules\\Inventory\\Contracts\\InventoryReturns',
          'orderlinedata' => 'Modules\\Ordering\\Contracts\\Data\\OrderLineData',
          'orderreader' => 'Modules\\Ordering\\Contracts\\OrderReader',
          'ordertransitions' => 'Modules\\Ordering\\Contracts\\OrderTransitions',
          'payments' => 'Modules\\Payment\\Contracts\\Payments',
          'returncontext' => 'Modules\\Returns\\Contracts\\Data\\ReturnContext',
          'returnview' => 'Modules\\Returns\\Contracts\\Data\\ReturnView',
          'returnpolicy' => 'Modules\\Returns\\Contracts\\ReturnPolicy',
          'returnrejected' => 'Modules\\Returns\\Contracts\\ReturnRejected',
          'returns' => 'Modules\\Returns\\Contracts\\Returns',
          'refundcalculator' => 'Modules\\Returns\\Domain\\RefundCalculator',
          'returnstatus' => 'Modules\\Returns\\Domain\\ReturnStatus',
          'returnrequested' => 'Modules\\Returns\\Events\\ReturnRequested',
          'returnresolved' => 'Modules\\Returns\\Events\\ReturnResolved',
          'returnline' => 'Modules\\Returns\\Persistence\\Models\\ReturnLine',
          'returnrequest' => 'Modules\\Returns\\Persistence\\Models\\ReturnRequest',
          'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
          'money' => 'Modules\\Shared\\Domain\\Money\\Money',
          'settings' => 'Modules\\Tenancy\\Contracts\\Settings',
        ),
         'className' => 'Modules\\Returns\\Application\\ReturnService',
         'functionName' => 'view',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'Modules\\Returns\\Application',
           'uses' => 
          array (
            'datetimeimmutable' => 'DateTimeImmutable',
            'carbon' => 'Illuminate\\Support\\Carbon',
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'str' => 'Illuminate\\Support\\Str',
            'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
            'shipmentreader' => 'Modules\\Fulfillment\\Contracts\\ShipmentReader',
            'auditlogger' => 'Modules\\Identity\\Contracts\\AuditLogger',
            'inventoryreturns' => 'Modules\\Inventory\\Contracts\\InventoryReturns',
            'orderlinedata' => 'Modules\\Ordering\\Contracts\\Data\\OrderLineData',
            'orderreader' => 'Modules\\Ordering\\Contracts\\OrderReader',
            'ordertransitions' => 'Modules\\Ordering\\Contracts\\OrderTransitions',
            'payments' => 'Modules\\Payment\\Contracts\\Payments',
            'returncontext' => 'Modules\\Returns\\Contracts\\Data\\ReturnContext',
            'returnview' => 'Modules\\Returns\\Contracts\\Data\\ReturnView',
            'returnpolicy' => 'Modules\\Returns\\Contracts\\ReturnPolicy',
            'returnrejected' => 'Modules\\Returns\\Contracts\\ReturnRejected',
            'returns' => 'Modules\\Returns\\Contracts\\Returns',
            'refundcalculator' => 'Modules\\Returns\\Domain\\RefundCalculator',
            'returnstatus' => 'Modules\\Returns\\Domain\\ReturnStatus',
            'returnrequested' => 'Modules\\Returns\\Events\\ReturnRequested',
            'returnresolved' => 'Modules\\Returns\\Events\\ReturnResolved',
            'returnline' => 'Modules\\Returns\\Persistence\\Models\\ReturnLine',
            'returnrequest' => 'Modules\\Returns\\Persistence\\Models\\ReturnRequest',
            'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
            'money' => 'Modules\\Shared\\Domain\\Money\\Money',
            'settings' => 'Modules\\Tenancy\\Contracts\\Settings',
          ),
           'className' => 'Modules\\Returns\\Application\\ReturnService',
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
      '8d7a5e2ee790cfa22b0369105f950c28' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Returns\\Application',
         'uses' => 
        array (
          'datetimeimmutable' => 'DateTimeImmutable',
          'carbon' => 'Illuminate\\Support\\Carbon',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'str' => 'Illuminate\\Support\\Str',
          'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
          'shipmentreader' => 'Modules\\Fulfillment\\Contracts\\ShipmentReader',
          'auditlogger' => 'Modules\\Identity\\Contracts\\AuditLogger',
          'inventoryreturns' => 'Modules\\Inventory\\Contracts\\InventoryReturns',
          'orderlinedata' => 'Modules\\Ordering\\Contracts\\Data\\OrderLineData',
          'orderreader' => 'Modules\\Ordering\\Contracts\\OrderReader',
          'ordertransitions' => 'Modules\\Ordering\\Contracts\\OrderTransitions',
          'payments' => 'Modules\\Payment\\Contracts\\Payments',
          'returncontext' => 'Modules\\Returns\\Contracts\\Data\\ReturnContext',
          'returnview' => 'Modules\\Returns\\Contracts\\Data\\ReturnView',
          'returnpolicy' => 'Modules\\Returns\\Contracts\\ReturnPolicy',
          'returnrejected' => 'Modules\\Returns\\Contracts\\ReturnRejected',
          'returns' => 'Modules\\Returns\\Contracts\\Returns',
          'refundcalculator' => 'Modules\\Returns\\Domain\\RefundCalculator',
          'returnstatus' => 'Modules\\Returns\\Domain\\ReturnStatus',
          'returnrequested' => 'Modules\\Returns\\Events\\ReturnRequested',
          'returnresolved' => 'Modules\\Returns\\Events\\ReturnResolved',
          'returnline' => 'Modules\\Returns\\Persistence\\Models\\ReturnLine',
          'returnrequest' => 'Modules\\Returns\\Persistence\\Models\\ReturnRequest',
          'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
          'money' => 'Modules\\Shared\\Domain\\Money\\Money',
          'settings' => 'Modules\\Tenancy\\Contracts\\Settings',
        ),
         'className' => 'Modules\\Returns\\Application\\ReturnService',
         'functionName' => 'move',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'Modules\\Returns\\Application',
           'uses' => 
          array (
            'datetimeimmutable' => 'DateTimeImmutable',
            'carbon' => 'Illuminate\\Support\\Carbon',
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'str' => 'Illuminate\\Support\\Str',
            'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
            'shipmentreader' => 'Modules\\Fulfillment\\Contracts\\ShipmentReader',
            'auditlogger' => 'Modules\\Identity\\Contracts\\AuditLogger',
            'inventoryreturns' => 'Modules\\Inventory\\Contracts\\InventoryReturns',
            'orderlinedata' => 'Modules\\Ordering\\Contracts\\Data\\OrderLineData',
            'orderreader' => 'Modules\\Ordering\\Contracts\\OrderReader',
            'ordertransitions' => 'Modules\\Ordering\\Contracts\\OrderTransitions',
            'payments' => 'Modules\\Payment\\Contracts\\Payments',
            'returncontext' => 'Modules\\Returns\\Contracts\\Data\\ReturnContext',
            'returnview' => 'Modules\\Returns\\Contracts\\Data\\ReturnView',
            'returnpolicy' => 'Modules\\Returns\\Contracts\\ReturnPolicy',
            'returnrejected' => 'Modules\\Returns\\Contracts\\ReturnRejected',
            'returns' => 'Modules\\Returns\\Contracts\\Returns',
            'refundcalculator' => 'Modules\\Returns\\Domain\\RefundCalculator',
            'returnstatus' => 'Modules\\Returns\\Domain\\ReturnStatus',
            'returnrequested' => 'Modules\\Returns\\Events\\ReturnRequested',
            'returnresolved' => 'Modules\\Returns\\Events\\ReturnResolved',
            'returnline' => 'Modules\\Returns\\Persistence\\Models\\ReturnLine',
            'returnrequest' => 'Modules\\Returns\\Persistence\\Models\\ReturnRequest',
            'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
            'money' => 'Modules\\Shared\\Domain\\Money\\Money',
            'settings' => 'Modules\\Tenancy\\Contracts\\Settings',
          ),
           'className' => 'Modules\\Returns\\Application\\ReturnService',
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
      'dc76e748908fd118e3b2a5765fdb5b9a' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Returns\\Application',
         'uses' => 
        array (
          'datetimeimmutable' => 'DateTimeImmutable',
          'carbon' => 'Illuminate\\Support\\Carbon',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'str' => 'Illuminate\\Support\\Str',
          'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
          'shipmentreader' => 'Modules\\Fulfillment\\Contracts\\ShipmentReader',
          'auditlogger' => 'Modules\\Identity\\Contracts\\AuditLogger',
          'inventoryreturns' => 'Modules\\Inventory\\Contracts\\InventoryReturns',
          'orderlinedata' => 'Modules\\Ordering\\Contracts\\Data\\OrderLineData',
          'orderreader' => 'Modules\\Ordering\\Contracts\\OrderReader',
          'ordertransitions' => 'Modules\\Ordering\\Contracts\\OrderTransitions',
          'payments' => 'Modules\\Payment\\Contracts\\Payments',
          'returncontext' => 'Modules\\Returns\\Contracts\\Data\\ReturnContext',
          'returnview' => 'Modules\\Returns\\Contracts\\Data\\ReturnView',
          'returnpolicy' => 'Modules\\Returns\\Contracts\\ReturnPolicy',
          'returnrejected' => 'Modules\\Returns\\Contracts\\ReturnRejected',
          'returns' => 'Modules\\Returns\\Contracts\\Returns',
          'refundcalculator' => 'Modules\\Returns\\Domain\\RefundCalculator',
          'returnstatus' => 'Modules\\Returns\\Domain\\ReturnStatus',
          'returnrequested' => 'Modules\\Returns\\Events\\ReturnRequested',
          'returnresolved' => 'Modules\\Returns\\Events\\ReturnResolved',
          'returnline' => 'Modules\\Returns\\Persistence\\Models\\ReturnLine',
          'returnrequest' => 'Modules\\Returns\\Persistence\\Models\\ReturnRequest',
          'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
          'money' => 'Modules\\Shared\\Domain\\Money\\Money',
          'settings' => 'Modules\\Tenancy\\Contracts\\Settings',
        ),
         'className' => 'Modules\\Returns\\Application\\ReturnService',
         'functionName' => 'event',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'Modules\\Returns\\Application',
           'uses' => 
          array (
            'datetimeimmutable' => 'DateTimeImmutable',
            'carbon' => 'Illuminate\\Support\\Carbon',
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'str' => 'Illuminate\\Support\\Str',
            'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
            'shipmentreader' => 'Modules\\Fulfillment\\Contracts\\ShipmentReader',
            'auditlogger' => 'Modules\\Identity\\Contracts\\AuditLogger',
            'inventoryreturns' => 'Modules\\Inventory\\Contracts\\InventoryReturns',
            'orderlinedata' => 'Modules\\Ordering\\Contracts\\Data\\OrderLineData',
            'orderreader' => 'Modules\\Ordering\\Contracts\\OrderReader',
            'ordertransitions' => 'Modules\\Ordering\\Contracts\\OrderTransitions',
            'payments' => 'Modules\\Payment\\Contracts\\Payments',
            'returncontext' => 'Modules\\Returns\\Contracts\\Data\\ReturnContext',
            'returnview' => 'Modules\\Returns\\Contracts\\Data\\ReturnView',
            'returnpolicy' => 'Modules\\Returns\\Contracts\\ReturnPolicy',
            'returnrejected' => 'Modules\\Returns\\Contracts\\ReturnRejected',
            'returns' => 'Modules\\Returns\\Contracts\\Returns',
            'refundcalculator' => 'Modules\\Returns\\Domain\\RefundCalculator',
            'returnstatus' => 'Modules\\Returns\\Domain\\ReturnStatus',
            'returnrequested' => 'Modules\\Returns\\Events\\ReturnRequested',
            'returnresolved' => 'Modules\\Returns\\Events\\ReturnResolved',
            'returnline' => 'Modules\\Returns\\Persistence\\Models\\ReturnLine',
            'returnrequest' => 'Modules\\Returns\\Persistence\\Models\\ReturnRequest',
            'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
            'money' => 'Modules\\Shared\\Domain\\Money\\Money',
            'settings' => 'Modules\\Tenancy\\Contracts\\Settings',
          ),
           'className' => 'Modules\\Returns\\Application\\ReturnService',
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
      '1710acce1cbda6dbcdfef8220ded4288' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Returns\\Application',
         'uses' => 
        array (
          'datetimeimmutable' => 'DateTimeImmutable',
          'carbon' => 'Illuminate\\Support\\Carbon',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'str' => 'Illuminate\\Support\\Str',
          'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
          'shipmentreader' => 'Modules\\Fulfillment\\Contracts\\ShipmentReader',
          'auditlogger' => 'Modules\\Identity\\Contracts\\AuditLogger',
          'inventoryreturns' => 'Modules\\Inventory\\Contracts\\InventoryReturns',
          'orderlinedata' => 'Modules\\Ordering\\Contracts\\Data\\OrderLineData',
          'orderreader' => 'Modules\\Ordering\\Contracts\\OrderReader',
          'ordertransitions' => 'Modules\\Ordering\\Contracts\\OrderTransitions',
          'payments' => 'Modules\\Payment\\Contracts\\Payments',
          'returncontext' => 'Modules\\Returns\\Contracts\\Data\\ReturnContext',
          'returnview' => 'Modules\\Returns\\Contracts\\Data\\ReturnView',
          'returnpolicy' => 'Modules\\Returns\\Contracts\\ReturnPolicy',
          'returnrejected' => 'Modules\\Returns\\Contracts\\ReturnRejected',
          'returns' => 'Modules\\Returns\\Contracts\\Returns',
          'refundcalculator' => 'Modules\\Returns\\Domain\\RefundCalculator',
          'returnstatus' => 'Modules\\Returns\\Domain\\ReturnStatus',
          'returnrequested' => 'Modules\\Returns\\Events\\ReturnRequested',
          'returnresolved' => 'Modules\\Returns\\Events\\ReturnResolved',
          'returnline' => 'Modules\\Returns\\Persistence\\Models\\ReturnLine',
          'returnrequest' => 'Modules\\Returns\\Persistence\\Models\\ReturnRequest',
          'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
          'money' => 'Modules\\Shared\\Domain\\Money\\Money',
          'settings' => 'Modules\\Tenancy\\Contracts\\Settings',
        ),
         'className' => 'Modules\\Returns\\Application\\ReturnService',
         'functionName' => 'syncOrder',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'Modules\\Returns\\Application',
           'uses' => 
          array (
            'datetimeimmutable' => 'DateTimeImmutable',
            'carbon' => 'Illuminate\\Support\\Carbon',
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'str' => 'Illuminate\\Support\\Str',
            'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
            'shipmentreader' => 'Modules\\Fulfillment\\Contracts\\ShipmentReader',
            'auditlogger' => 'Modules\\Identity\\Contracts\\AuditLogger',
            'inventoryreturns' => 'Modules\\Inventory\\Contracts\\InventoryReturns',
            'orderlinedata' => 'Modules\\Ordering\\Contracts\\Data\\OrderLineData',
            'orderreader' => 'Modules\\Ordering\\Contracts\\OrderReader',
            'ordertransitions' => 'Modules\\Ordering\\Contracts\\OrderTransitions',
            'payments' => 'Modules\\Payment\\Contracts\\Payments',
            'returncontext' => 'Modules\\Returns\\Contracts\\Data\\ReturnContext',
            'returnview' => 'Modules\\Returns\\Contracts\\Data\\ReturnView',
            'returnpolicy' => 'Modules\\Returns\\Contracts\\ReturnPolicy',
            'returnrejected' => 'Modules\\Returns\\Contracts\\ReturnRejected',
            'returns' => 'Modules\\Returns\\Contracts\\Returns',
            'refundcalculator' => 'Modules\\Returns\\Domain\\RefundCalculator',
            'returnstatus' => 'Modules\\Returns\\Domain\\ReturnStatus',
            'returnrequested' => 'Modules\\Returns\\Events\\ReturnRequested',
            'returnresolved' => 'Modules\\Returns\\Events\\ReturnResolved',
            'returnline' => 'Modules\\Returns\\Persistence\\Models\\ReturnLine',
            'returnrequest' => 'Modules\\Returns\\Persistence\\Models\\ReturnRequest',
            'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
            'money' => 'Modules\\Shared\\Domain\\Money\\Money',
            'settings' => 'Modules\\Tenancy\\Contracts\\Settings',
          ),
           'className' => 'Modules\\Returns\\Application\\ReturnService',
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
      'e2af877f16e75c66dd056143df0c0752' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Returns\\Application',
         'uses' => 
        array (
          'datetimeimmutable' => 'DateTimeImmutable',
          'carbon' => 'Illuminate\\Support\\Carbon',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'str' => 'Illuminate\\Support\\Str',
          'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
          'shipmentreader' => 'Modules\\Fulfillment\\Contracts\\ShipmentReader',
          'auditlogger' => 'Modules\\Identity\\Contracts\\AuditLogger',
          'inventoryreturns' => 'Modules\\Inventory\\Contracts\\InventoryReturns',
          'orderlinedata' => 'Modules\\Ordering\\Contracts\\Data\\OrderLineData',
          'orderreader' => 'Modules\\Ordering\\Contracts\\OrderReader',
          'ordertransitions' => 'Modules\\Ordering\\Contracts\\OrderTransitions',
          'payments' => 'Modules\\Payment\\Contracts\\Payments',
          'returncontext' => 'Modules\\Returns\\Contracts\\Data\\ReturnContext',
          'returnview' => 'Modules\\Returns\\Contracts\\Data\\ReturnView',
          'returnpolicy' => 'Modules\\Returns\\Contracts\\ReturnPolicy',
          'returnrejected' => 'Modules\\Returns\\Contracts\\ReturnRejected',
          'returns' => 'Modules\\Returns\\Contracts\\Returns',
          'refundcalculator' => 'Modules\\Returns\\Domain\\RefundCalculator',
          'returnstatus' => 'Modules\\Returns\\Domain\\ReturnStatus',
          'returnrequested' => 'Modules\\Returns\\Events\\ReturnRequested',
          'returnresolved' => 'Modules\\Returns\\Events\\ReturnResolved',
          'returnline' => 'Modules\\Returns\\Persistence\\Models\\ReturnLine',
          'returnrequest' => 'Modules\\Returns\\Persistence\\Models\\ReturnRequest',
          'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
          'money' => 'Modules\\Shared\\Domain\\Money\\Money',
          'settings' => 'Modules\\Tenancy\\Contracts\\Settings',
        ),
         'className' => 'Modules\\Returns\\Application\\ReturnService',
         'functionName' => 'takenQuantities',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'Modules\\Returns\\Application',
           'uses' => 
          array (
            'datetimeimmutable' => 'DateTimeImmutable',
            'carbon' => 'Illuminate\\Support\\Carbon',
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'str' => 'Illuminate\\Support\\Str',
            'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
            'shipmentreader' => 'Modules\\Fulfillment\\Contracts\\ShipmentReader',
            'auditlogger' => 'Modules\\Identity\\Contracts\\AuditLogger',
            'inventoryreturns' => 'Modules\\Inventory\\Contracts\\InventoryReturns',
            'orderlinedata' => 'Modules\\Ordering\\Contracts\\Data\\OrderLineData',
            'orderreader' => 'Modules\\Ordering\\Contracts\\OrderReader',
            'ordertransitions' => 'Modules\\Ordering\\Contracts\\OrderTransitions',
            'payments' => 'Modules\\Payment\\Contracts\\Payments',
            'returncontext' => 'Modules\\Returns\\Contracts\\Data\\ReturnContext',
            'returnview' => 'Modules\\Returns\\Contracts\\Data\\ReturnView',
            'returnpolicy' => 'Modules\\Returns\\Contracts\\ReturnPolicy',
            'returnrejected' => 'Modules\\Returns\\Contracts\\ReturnRejected',
            'returns' => 'Modules\\Returns\\Contracts\\Returns',
            'refundcalculator' => 'Modules\\Returns\\Domain\\RefundCalculator',
            'returnstatus' => 'Modules\\Returns\\Domain\\ReturnStatus',
            'returnrequested' => 'Modules\\Returns\\Events\\ReturnRequested',
            'returnresolved' => 'Modules\\Returns\\Events\\ReturnResolved',
            'returnline' => 'Modules\\Returns\\Persistence\\Models\\ReturnLine',
            'returnrequest' => 'Modules\\Returns\\Persistence\\Models\\ReturnRequest',
            'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
            'money' => 'Modules\\Shared\\Domain\\Money\\Money',
            'settings' => 'Modules\\Tenancy\\Contracts\\Settings',
          ),
           'className' => 'Modules\\Returns\\Application\\ReturnService',
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
      'a455a353e6afcaf344d2d00e199ffe39' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Returns\\Application',
         'uses' => 
        array (
          'datetimeimmutable' => 'DateTimeImmutable',
          'carbon' => 'Illuminate\\Support\\Carbon',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'str' => 'Illuminate\\Support\\Str',
          'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
          'shipmentreader' => 'Modules\\Fulfillment\\Contracts\\ShipmentReader',
          'auditlogger' => 'Modules\\Identity\\Contracts\\AuditLogger',
          'inventoryreturns' => 'Modules\\Inventory\\Contracts\\InventoryReturns',
          'orderlinedata' => 'Modules\\Ordering\\Contracts\\Data\\OrderLineData',
          'orderreader' => 'Modules\\Ordering\\Contracts\\OrderReader',
          'ordertransitions' => 'Modules\\Ordering\\Contracts\\OrderTransitions',
          'payments' => 'Modules\\Payment\\Contracts\\Payments',
          'returncontext' => 'Modules\\Returns\\Contracts\\Data\\ReturnContext',
          'returnview' => 'Modules\\Returns\\Contracts\\Data\\ReturnView',
          'returnpolicy' => 'Modules\\Returns\\Contracts\\ReturnPolicy',
          'returnrejected' => 'Modules\\Returns\\Contracts\\ReturnRejected',
          'returns' => 'Modules\\Returns\\Contracts\\Returns',
          'refundcalculator' => 'Modules\\Returns\\Domain\\RefundCalculator',
          'returnstatus' => 'Modules\\Returns\\Domain\\ReturnStatus',
          'returnrequested' => 'Modules\\Returns\\Events\\ReturnRequested',
          'returnresolved' => 'Modules\\Returns\\Events\\ReturnResolved',
          'returnline' => 'Modules\\Returns\\Persistence\\Models\\ReturnLine',
          'returnrequest' => 'Modules\\Returns\\Persistence\\Models\\ReturnRequest',
          'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
          'money' => 'Modules\\Shared\\Domain\\Money\\Money',
          'settings' => 'Modules\\Tenancy\\Contracts\\Settings',
        ),
         'className' => 'Modules\\Returns\\Application\\ReturnService',
         'functionName' => 'delivered',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'Modules\\Returns\\Application',
           'uses' => 
          array (
            'datetimeimmutable' => 'DateTimeImmutable',
            'carbon' => 'Illuminate\\Support\\Carbon',
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'str' => 'Illuminate\\Support\\Str',
            'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
            'shipmentreader' => 'Modules\\Fulfillment\\Contracts\\ShipmentReader',
            'auditlogger' => 'Modules\\Identity\\Contracts\\AuditLogger',
            'inventoryreturns' => 'Modules\\Inventory\\Contracts\\InventoryReturns',
            'orderlinedata' => 'Modules\\Ordering\\Contracts\\Data\\OrderLineData',
            'orderreader' => 'Modules\\Ordering\\Contracts\\OrderReader',
            'ordertransitions' => 'Modules\\Ordering\\Contracts\\OrderTransitions',
            'payments' => 'Modules\\Payment\\Contracts\\Payments',
            'returncontext' => 'Modules\\Returns\\Contracts\\Data\\ReturnContext',
            'returnview' => 'Modules\\Returns\\Contracts\\Data\\ReturnView',
            'returnpolicy' => 'Modules\\Returns\\Contracts\\ReturnPolicy',
            'returnrejected' => 'Modules\\Returns\\Contracts\\ReturnRejected',
            'returns' => 'Modules\\Returns\\Contracts\\Returns',
            'refundcalculator' => 'Modules\\Returns\\Domain\\RefundCalculator',
            'returnstatus' => 'Modules\\Returns\\Domain\\ReturnStatus',
            'returnrequested' => 'Modules\\Returns\\Events\\ReturnRequested',
            'returnresolved' => 'Modules\\Returns\\Events\\ReturnResolved',
            'returnline' => 'Modules\\Returns\\Persistence\\Models\\ReturnLine',
            'returnrequest' => 'Modules\\Returns\\Persistence\\Models\\ReturnRequest',
            'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
            'money' => 'Modules\\Shared\\Domain\\Money\\Money',
            'settings' => 'Modules\\Tenancy\\Contracts\\Settings',
          ),
           'className' => 'Modules\\Returns\\Application\\ReturnService',
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
      '18207ee2e8acef3aeb27fc0e169a7740' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Returns\\Application',
         'uses' => 
        array (
          'datetimeimmutable' => 'DateTimeImmutable',
          'carbon' => 'Illuminate\\Support\\Carbon',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'str' => 'Illuminate\\Support\\Str',
          'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
          'shipmentreader' => 'Modules\\Fulfillment\\Contracts\\ShipmentReader',
          'auditlogger' => 'Modules\\Identity\\Contracts\\AuditLogger',
          'inventoryreturns' => 'Modules\\Inventory\\Contracts\\InventoryReturns',
          'orderlinedata' => 'Modules\\Ordering\\Contracts\\Data\\OrderLineData',
          'orderreader' => 'Modules\\Ordering\\Contracts\\OrderReader',
          'ordertransitions' => 'Modules\\Ordering\\Contracts\\OrderTransitions',
          'payments' => 'Modules\\Payment\\Contracts\\Payments',
          'returncontext' => 'Modules\\Returns\\Contracts\\Data\\ReturnContext',
          'returnview' => 'Modules\\Returns\\Contracts\\Data\\ReturnView',
          'returnpolicy' => 'Modules\\Returns\\Contracts\\ReturnPolicy',
          'returnrejected' => 'Modules\\Returns\\Contracts\\ReturnRejected',
          'returns' => 'Modules\\Returns\\Contracts\\Returns',
          'refundcalculator' => 'Modules\\Returns\\Domain\\RefundCalculator',
          'returnstatus' => 'Modules\\Returns\\Domain\\ReturnStatus',
          'returnrequested' => 'Modules\\Returns\\Events\\ReturnRequested',
          'returnresolved' => 'Modules\\Returns\\Events\\ReturnResolved',
          'returnline' => 'Modules\\Returns\\Persistence\\Models\\ReturnLine',
          'returnrequest' => 'Modules\\Returns\\Persistence\\Models\\ReturnRequest',
          'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
          'money' => 'Modules\\Shared\\Domain\\Money\\Money',
          'settings' => 'Modules\\Tenancy\\Contracts\\Settings',
        ),
         'className' => 'Modules\\Returns\\Application\\ReturnService',
         'functionName' => 'shipmentLocations',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'Modules\\Returns\\Application',
           'uses' => 
          array (
            'datetimeimmutable' => 'DateTimeImmutable',
            'carbon' => 'Illuminate\\Support\\Carbon',
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'str' => 'Illuminate\\Support\\Str',
            'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
            'shipmentreader' => 'Modules\\Fulfillment\\Contracts\\ShipmentReader',
            'auditlogger' => 'Modules\\Identity\\Contracts\\AuditLogger',
            'inventoryreturns' => 'Modules\\Inventory\\Contracts\\InventoryReturns',
            'orderlinedata' => 'Modules\\Ordering\\Contracts\\Data\\OrderLineData',
            'orderreader' => 'Modules\\Ordering\\Contracts\\OrderReader',
            'ordertransitions' => 'Modules\\Ordering\\Contracts\\OrderTransitions',
            'payments' => 'Modules\\Payment\\Contracts\\Payments',
            'returncontext' => 'Modules\\Returns\\Contracts\\Data\\ReturnContext',
            'returnview' => 'Modules\\Returns\\Contracts\\Data\\ReturnView',
            'returnpolicy' => 'Modules\\Returns\\Contracts\\ReturnPolicy',
            'returnrejected' => 'Modules\\Returns\\Contracts\\ReturnRejected',
            'returns' => 'Modules\\Returns\\Contracts\\Returns',
            'refundcalculator' => 'Modules\\Returns\\Domain\\RefundCalculator',
            'returnstatus' => 'Modules\\Returns\\Domain\\ReturnStatus',
            'returnrequested' => 'Modules\\Returns\\Events\\ReturnRequested',
            'returnresolved' => 'Modules\\Returns\\Events\\ReturnResolved',
            'returnline' => 'Modules\\Returns\\Persistence\\Models\\ReturnLine',
            'returnrequest' => 'Modules\\Returns\\Persistence\\Models\\ReturnRequest',
            'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
            'money' => 'Modules\\Shared\\Domain\\Money\\Money',
            'settings' => 'Modules\\Tenancy\\Contracts\\Settings',
          ),
           'className' => 'Modules\\Returns\\Application\\ReturnService',
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
      '76fdd760154c330a48f01067cda3b0c1' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Returns\\Application',
         'uses' => 
        array (
          'datetimeimmutable' => 'DateTimeImmutable',
          'carbon' => 'Illuminate\\Support\\Carbon',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'str' => 'Illuminate\\Support\\Str',
          'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
          'shipmentreader' => 'Modules\\Fulfillment\\Contracts\\ShipmentReader',
          'auditlogger' => 'Modules\\Identity\\Contracts\\AuditLogger',
          'inventoryreturns' => 'Modules\\Inventory\\Contracts\\InventoryReturns',
          'orderlinedata' => 'Modules\\Ordering\\Contracts\\Data\\OrderLineData',
          'orderreader' => 'Modules\\Ordering\\Contracts\\OrderReader',
          'ordertransitions' => 'Modules\\Ordering\\Contracts\\OrderTransitions',
          'payments' => 'Modules\\Payment\\Contracts\\Payments',
          'returncontext' => 'Modules\\Returns\\Contracts\\Data\\ReturnContext',
          'returnview' => 'Modules\\Returns\\Contracts\\Data\\ReturnView',
          'returnpolicy' => 'Modules\\Returns\\Contracts\\ReturnPolicy',
          'returnrejected' => 'Modules\\Returns\\Contracts\\ReturnRejected',
          'returns' => 'Modules\\Returns\\Contracts\\Returns',
          'refundcalculator' => 'Modules\\Returns\\Domain\\RefundCalculator',
          'returnstatus' => 'Modules\\Returns\\Domain\\ReturnStatus',
          'returnrequested' => 'Modules\\Returns\\Events\\ReturnRequested',
          'returnresolved' => 'Modules\\Returns\\Events\\ReturnResolved',
          'returnline' => 'Modules\\Returns\\Persistence\\Models\\ReturnLine',
          'returnrequest' => 'Modules\\Returns\\Persistence\\Models\\ReturnRequest',
          'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
          'money' => 'Modules\\Shared\\Domain\\Money\\Money',
          'settings' => 'Modules\\Tenancy\\Contracts\\Settings',
        ),
         'className' => 'Modules\\Returns\\Application\\ReturnService',
         'functionName' => 'policy',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'Modules\\Returns\\Application',
           'uses' => 
          array (
            'datetimeimmutable' => 'DateTimeImmutable',
            'carbon' => 'Illuminate\\Support\\Carbon',
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'str' => 'Illuminate\\Support\\Str',
            'extensions' => 'Modules\\Extension\\Contracts\\Extensions',
            'shipmentreader' => 'Modules\\Fulfillment\\Contracts\\ShipmentReader',
            'auditlogger' => 'Modules\\Identity\\Contracts\\AuditLogger',
            'inventoryreturns' => 'Modules\\Inventory\\Contracts\\InventoryReturns',
            'orderlinedata' => 'Modules\\Ordering\\Contracts\\Data\\OrderLineData',
            'orderreader' => 'Modules\\Ordering\\Contracts\\OrderReader',
            'ordertransitions' => 'Modules\\Ordering\\Contracts\\OrderTransitions',
            'payments' => 'Modules\\Payment\\Contracts\\Payments',
            'returncontext' => 'Modules\\Returns\\Contracts\\Data\\ReturnContext',
            'returnview' => 'Modules\\Returns\\Contracts\\Data\\ReturnView',
            'returnpolicy' => 'Modules\\Returns\\Contracts\\ReturnPolicy',
            'returnrejected' => 'Modules\\Returns\\Contracts\\ReturnRejected',
            'returns' => 'Modules\\Returns\\Contracts\\Returns',
            'refundcalculator' => 'Modules\\Returns\\Domain\\RefundCalculator',
            'returnstatus' => 'Modules\\Returns\\Domain\\ReturnStatus',
            'returnrequested' => 'Modules\\Returns\\Events\\ReturnRequested',
            'returnresolved' => 'Modules\\Returns\\Events\\ReturnResolved',
            'returnline' => 'Modules\\Returns\\Persistence\\Models\\ReturnLine',
            'returnrequest' => 'Modules\\Returns\\Persistence\\Models\\ReturnRequest',
            'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
            'money' => 'Modules\\Shared\\Domain\\Money\\Money',
            'settings' => 'Modules\\Tenancy\\Contracts\\Settings',
          ),
           'className' => 'Modules\\Returns\\Application\\ReturnService',
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
      '/Users/dat/Ecommerce/vanishop/modules/Returns/Application/ReturnService.php' => 'ac9eb31ae4775bee97da31c053248ac483f0bf5249baedaa9b994acc07c19213',
    ),
  ),
));