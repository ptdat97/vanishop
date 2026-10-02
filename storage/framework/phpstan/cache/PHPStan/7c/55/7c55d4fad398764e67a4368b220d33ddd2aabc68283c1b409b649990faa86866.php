<?php declare(strict_types = 1);

// ftm-/Users/dat/Ecommerce/vanishop/modules/Integration/Application/OrderEventReconciler.php
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v6-2.3.5',
   'data' => 
  array (
    0 => 
    array (
      'af3290bb689189d9369af7b4a308f693' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Integration\\Application',
         'uses' => 
        array (
          'carbonimmutable' => 'Carbon\\CarbonImmutable',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'log' => 'Illuminate\\Support\\Facades\\Log',
          'integrationevent' => 'Modules\\Integration\\Contracts\\Data\\IntegrationEvent',
          'integrationevents' => 'Modules\\Integration\\Contracts\\IntegrationEvents',
          'integrationeventrecord' => 'Modules\\Integration\\Persistence\\Models\\IntegrationEventRecord',
          'orderdata' => 'Modules\\Ordering\\Contracts\\Data\\OrderData',
          'orderstatus' => 'Modules\\Ordering\\Contracts\\Data\\OrderStatus',
          'orderreader' => 'Modules\\Ordering\\Contracts\\OrderReader',
          'contextscope' => 'Modules\\Shared\\Context\\ContextScope',
          'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
        ),
         'className' => 'Modules\\Integration\\Application\\OrderEventReconciler',
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
      'e8b9f4dc34e46c6236b1965fba818673' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Integration\\Application',
         'uses' => 
        array (
          'carbonimmutable' => 'Carbon\\CarbonImmutable',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'log' => 'Illuminate\\Support\\Facades\\Log',
          'integrationevent' => 'Modules\\Integration\\Contracts\\Data\\IntegrationEvent',
          'integrationevents' => 'Modules\\Integration\\Contracts\\IntegrationEvents',
          'integrationeventrecord' => 'Modules\\Integration\\Persistence\\Models\\IntegrationEventRecord',
          'orderdata' => 'Modules\\Ordering\\Contracts\\Data\\OrderData',
          'orderstatus' => 'Modules\\Ordering\\Contracts\\Data\\OrderStatus',
          'orderreader' => 'Modules\\Ordering\\Contracts\\OrderReader',
          'contextscope' => 'Modules\\Shared\\Context\\ContextScope',
          'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
        ),
         'className' => 'Modules\\Integration\\Application\\OrderEventReconciler',
         'functionName' => '__construct',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'Modules\\Integration\\Application',
           'uses' => 
          array (
            'carbonimmutable' => 'Carbon\\CarbonImmutable',
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'log' => 'Illuminate\\Support\\Facades\\Log',
            'integrationevent' => 'Modules\\Integration\\Contracts\\Data\\IntegrationEvent',
            'integrationevents' => 'Modules\\Integration\\Contracts\\IntegrationEvents',
            'integrationeventrecord' => 'Modules\\Integration\\Persistence\\Models\\IntegrationEventRecord',
            'orderdata' => 'Modules\\Ordering\\Contracts\\Data\\OrderData',
            'orderstatus' => 'Modules\\Ordering\\Contracts\\Data\\OrderStatus',
            'orderreader' => 'Modules\\Ordering\\Contracts\\OrderReader',
            'contextscope' => 'Modules\\Shared\\Context\\ContextScope',
            'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
          ),
           'className' => 'Modules\\Integration\\Application\\OrderEventReconciler',
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
      'ea2c4d0fa22b96ac0b4fd64da149599e' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Integration\\Application',
         'uses' => 
        array (
          'carbonimmutable' => 'Carbon\\CarbonImmutable',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'log' => 'Illuminate\\Support\\Facades\\Log',
          'integrationevent' => 'Modules\\Integration\\Contracts\\Data\\IntegrationEvent',
          'integrationevents' => 'Modules\\Integration\\Contracts\\IntegrationEvents',
          'integrationeventrecord' => 'Modules\\Integration\\Persistence\\Models\\IntegrationEventRecord',
          'orderdata' => 'Modules\\Ordering\\Contracts\\Data\\OrderData',
          'orderstatus' => 'Modules\\Ordering\\Contracts\\Data\\OrderStatus',
          'orderreader' => 'Modules\\Ordering\\Contracts\\OrderReader',
          'contextscope' => 'Modules\\Shared\\Context\\ContextScope',
          'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
        ),
         'className' => 'Modules\\Integration\\Application\\OrderEventReconciler',
         'functionName' => 'run',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'Modules\\Integration\\Application',
           'uses' => 
          array (
            'carbonimmutable' => 'Carbon\\CarbonImmutable',
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'log' => 'Illuminate\\Support\\Facades\\Log',
            'integrationevent' => 'Modules\\Integration\\Contracts\\Data\\IntegrationEvent',
            'integrationevents' => 'Modules\\Integration\\Contracts\\IntegrationEvents',
            'integrationeventrecord' => 'Modules\\Integration\\Persistence\\Models\\IntegrationEventRecord',
            'orderdata' => 'Modules\\Ordering\\Contracts\\Data\\OrderData',
            'orderstatus' => 'Modules\\Ordering\\Contracts\\Data\\OrderStatus',
            'orderreader' => 'Modules\\Ordering\\Contracts\\OrderReader',
            'contextscope' => 'Modules\\Shared\\Context\\ContextScope',
            'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
          ),
           'className' => 'Modules\\Integration\\Application\\OrderEventReconciler',
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
      '5218e2115803d57845ba5b2fc2bb9580' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Integration\\Application',
         'uses' => 
        array (
          'carbonimmutable' => 'Carbon\\CarbonImmutable',
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'log' => 'Illuminate\\Support\\Facades\\Log',
          'integrationevent' => 'Modules\\Integration\\Contracts\\Data\\IntegrationEvent',
          'integrationevents' => 'Modules\\Integration\\Contracts\\IntegrationEvents',
          'integrationeventrecord' => 'Modules\\Integration\\Persistence\\Models\\IntegrationEventRecord',
          'orderdata' => 'Modules\\Ordering\\Contracts\\Data\\OrderData',
          'orderstatus' => 'Modules\\Ordering\\Contracts\\Data\\OrderStatus',
          'orderreader' => 'Modules\\Ordering\\Contracts\\OrderReader',
          'contextscope' => 'Modules\\Shared\\Context\\ContextScope',
          'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
        ),
         'className' => 'Modules\\Integration\\Application\\OrderEventReconciler',
         'functionName' => 'missing',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'Modules\\Integration\\Application',
           'uses' => 
          array (
            'carbonimmutable' => 'Carbon\\CarbonImmutable',
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'log' => 'Illuminate\\Support\\Facades\\Log',
            'integrationevent' => 'Modules\\Integration\\Contracts\\Data\\IntegrationEvent',
            'integrationevents' => 'Modules\\Integration\\Contracts\\IntegrationEvents',
            'integrationeventrecord' => 'Modules\\Integration\\Persistence\\Models\\IntegrationEventRecord',
            'orderdata' => 'Modules\\Ordering\\Contracts\\Data\\OrderData',
            'orderstatus' => 'Modules\\Ordering\\Contracts\\Data\\OrderStatus',
            'orderreader' => 'Modules\\Ordering\\Contracts\\OrderReader',
            'contextscope' => 'Modules\\Shared\\Context\\ContextScope',
            'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
          ),
           'className' => 'Modules\\Integration\\Application\\OrderEventReconciler',
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
      '/Users/dat/Ecommerce/vanishop/modules/Integration/Application/OrderEventReconciler.php' => 'cb0c5b42ab859c12abdfbd9f7ed174f8c6b1132ca283544038e7c85d3c0ce274',
    ),
  ),
));