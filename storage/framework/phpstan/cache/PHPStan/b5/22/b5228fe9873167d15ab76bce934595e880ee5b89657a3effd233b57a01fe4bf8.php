<?php declare(strict_types = 1);

// ftm-/Users/dat/Ecommerce/vanishop/modules/Notification/Application/Listeners/SendOrderNotifications.php
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v6-2.3.5',
   'data' => 
  array (
    0 => 
    array (
      '8cf5b3c9b8198317ac9340eb2d1f2595' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Notification\\Application\\Listeners',
         'uses' => 
        array (
          'log' => 'Illuminate\\Support\\Facades\\Log',
          'shipmentreader' => 'Modules\\Fulfillment\\Contracts\\ShipmentReader',
          'shipmentstatuschanged' => 'Modules\\Fulfillment\\Events\\ShipmentStatusChanged',
          'notificationrequest' => 'Modules\\Notification\\Contracts\\Data\\NotificationRequest',
          'recipient' => 'Modules\\Notification\\Contracts\\Data\\Recipient',
          'notifier' => 'Modules\\Notification\\Contracts\\Notifier',
          'orderdata' => 'Modules\\Ordering\\Contracts\\Data\\OrderData',
          'orderreader' => 'Modules\\Ordering\\Contracts\\OrderReader',
          'ordercancelled' => 'Modules\\Ordering\\Events\\OrderCancelled',
          'orderplaced' => 'Modules\\Ordering\\Events\\OrderPlaced',
          'contextscope' => 'Modules\\Shared\\Context\\ContextScope',
          'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
          'money' => 'Modules\\Shared\\Domain\\Money\\Money',
          'moneyformatter' => 'Modules\\Shared\\Support\\MoneyFormatter',
          'throwable' => 'Throwable',
        ),
         'className' => 'Modules\\Notification\\Application\\Listeners\\SendOrderNotifications',
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
      '45ba49dd617bd0c242980a14bc828fc7' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Notification\\Application\\Listeners',
         'uses' => 
        array (
          'log' => 'Illuminate\\Support\\Facades\\Log',
          'shipmentreader' => 'Modules\\Fulfillment\\Contracts\\ShipmentReader',
          'shipmentstatuschanged' => 'Modules\\Fulfillment\\Events\\ShipmentStatusChanged',
          'notificationrequest' => 'Modules\\Notification\\Contracts\\Data\\NotificationRequest',
          'recipient' => 'Modules\\Notification\\Contracts\\Data\\Recipient',
          'notifier' => 'Modules\\Notification\\Contracts\\Notifier',
          'orderdata' => 'Modules\\Ordering\\Contracts\\Data\\OrderData',
          'orderreader' => 'Modules\\Ordering\\Contracts\\OrderReader',
          'ordercancelled' => 'Modules\\Ordering\\Events\\OrderCancelled',
          'orderplaced' => 'Modules\\Ordering\\Events\\OrderPlaced',
          'contextscope' => 'Modules\\Shared\\Context\\ContextScope',
          'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
          'money' => 'Modules\\Shared\\Domain\\Money\\Money',
          'moneyformatter' => 'Modules\\Shared\\Support\\MoneyFormatter',
          'throwable' => 'Throwable',
        ),
         'className' => 'Modules\\Notification\\Application\\Listeners\\SendOrderNotifications',
         'functionName' => '__construct',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'Modules\\Notification\\Application\\Listeners',
           'uses' => 
          array (
            'log' => 'Illuminate\\Support\\Facades\\Log',
            'shipmentreader' => 'Modules\\Fulfillment\\Contracts\\ShipmentReader',
            'shipmentstatuschanged' => 'Modules\\Fulfillment\\Events\\ShipmentStatusChanged',
            'notificationrequest' => 'Modules\\Notification\\Contracts\\Data\\NotificationRequest',
            'recipient' => 'Modules\\Notification\\Contracts\\Data\\Recipient',
            'notifier' => 'Modules\\Notification\\Contracts\\Notifier',
            'orderdata' => 'Modules\\Ordering\\Contracts\\Data\\OrderData',
            'orderreader' => 'Modules\\Ordering\\Contracts\\OrderReader',
            'ordercancelled' => 'Modules\\Ordering\\Events\\OrderCancelled',
            'orderplaced' => 'Modules\\Ordering\\Events\\OrderPlaced',
            'contextscope' => 'Modules\\Shared\\Context\\ContextScope',
            'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
            'money' => 'Modules\\Shared\\Domain\\Money\\Money',
            'moneyformatter' => 'Modules\\Shared\\Support\\MoneyFormatter',
            'throwable' => 'Throwable',
          ),
           'className' => 'Modules\\Notification\\Application\\Listeners\\SendOrderNotifications',
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
      'ad8136067e3ab508e89566bc10fe91ab' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Notification\\Application\\Listeners',
         'uses' => 
        array (
          'log' => 'Illuminate\\Support\\Facades\\Log',
          'shipmentreader' => 'Modules\\Fulfillment\\Contracts\\ShipmentReader',
          'shipmentstatuschanged' => 'Modules\\Fulfillment\\Events\\ShipmentStatusChanged',
          'notificationrequest' => 'Modules\\Notification\\Contracts\\Data\\NotificationRequest',
          'recipient' => 'Modules\\Notification\\Contracts\\Data\\Recipient',
          'notifier' => 'Modules\\Notification\\Contracts\\Notifier',
          'orderdata' => 'Modules\\Ordering\\Contracts\\Data\\OrderData',
          'orderreader' => 'Modules\\Ordering\\Contracts\\OrderReader',
          'ordercancelled' => 'Modules\\Ordering\\Events\\OrderCancelled',
          'orderplaced' => 'Modules\\Ordering\\Events\\OrderPlaced',
          'contextscope' => 'Modules\\Shared\\Context\\ContextScope',
          'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
          'money' => 'Modules\\Shared\\Domain\\Money\\Money',
          'moneyformatter' => 'Modules\\Shared\\Support\\MoneyFormatter',
          'throwable' => 'Throwable',
        ),
         'className' => 'Modules\\Notification\\Application\\Listeners\\SendOrderNotifications',
         'functionName' => 'orderPlaced',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'Modules\\Notification\\Application\\Listeners',
           'uses' => 
          array (
            'log' => 'Illuminate\\Support\\Facades\\Log',
            'shipmentreader' => 'Modules\\Fulfillment\\Contracts\\ShipmentReader',
            'shipmentstatuschanged' => 'Modules\\Fulfillment\\Events\\ShipmentStatusChanged',
            'notificationrequest' => 'Modules\\Notification\\Contracts\\Data\\NotificationRequest',
            'recipient' => 'Modules\\Notification\\Contracts\\Data\\Recipient',
            'notifier' => 'Modules\\Notification\\Contracts\\Notifier',
            'orderdata' => 'Modules\\Ordering\\Contracts\\Data\\OrderData',
            'orderreader' => 'Modules\\Ordering\\Contracts\\OrderReader',
            'ordercancelled' => 'Modules\\Ordering\\Events\\OrderCancelled',
            'orderplaced' => 'Modules\\Ordering\\Events\\OrderPlaced',
            'contextscope' => 'Modules\\Shared\\Context\\ContextScope',
            'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
            'money' => 'Modules\\Shared\\Domain\\Money\\Money',
            'moneyformatter' => 'Modules\\Shared\\Support\\MoneyFormatter',
            'throwable' => 'Throwable',
          ),
           'className' => 'Modules\\Notification\\Application\\Listeners\\SendOrderNotifications',
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
      'e797d54f41230b1240cfd6c9ce38a8b1' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Notification\\Application\\Listeners',
         'uses' => 
        array (
          'log' => 'Illuminate\\Support\\Facades\\Log',
          'shipmentreader' => 'Modules\\Fulfillment\\Contracts\\ShipmentReader',
          'shipmentstatuschanged' => 'Modules\\Fulfillment\\Events\\ShipmentStatusChanged',
          'notificationrequest' => 'Modules\\Notification\\Contracts\\Data\\NotificationRequest',
          'recipient' => 'Modules\\Notification\\Contracts\\Data\\Recipient',
          'notifier' => 'Modules\\Notification\\Contracts\\Notifier',
          'orderdata' => 'Modules\\Ordering\\Contracts\\Data\\OrderData',
          'orderreader' => 'Modules\\Ordering\\Contracts\\OrderReader',
          'ordercancelled' => 'Modules\\Ordering\\Events\\OrderCancelled',
          'orderplaced' => 'Modules\\Ordering\\Events\\OrderPlaced',
          'contextscope' => 'Modules\\Shared\\Context\\ContextScope',
          'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
          'money' => 'Modules\\Shared\\Domain\\Money\\Money',
          'moneyformatter' => 'Modules\\Shared\\Support\\MoneyFormatter',
          'throwable' => 'Throwable',
        ),
         'className' => 'Modules\\Notification\\Application\\Listeners\\SendOrderNotifications',
         'functionName' => 'orderCancelled',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'Modules\\Notification\\Application\\Listeners',
           'uses' => 
          array (
            'log' => 'Illuminate\\Support\\Facades\\Log',
            'shipmentreader' => 'Modules\\Fulfillment\\Contracts\\ShipmentReader',
            'shipmentstatuschanged' => 'Modules\\Fulfillment\\Events\\ShipmentStatusChanged',
            'notificationrequest' => 'Modules\\Notification\\Contracts\\Data\\NotificationRequest',
            'recipient' => 'Modules\\Notification\\Contracts\\Data\\Recipient',
            'notifier' => 'Modules\\Notification\\Contracts\\Notifier',
            'orderdata' => 'Modules\\Ordering\\Contracts\\Data\\OrderData',
            'orderreader' => 'Modules\\Ordering\\Contracts\\OrderReader',
            'ordercancelled' => 'Modules\\Ordering\\Events\\OrderCancelled',
            'orderplaced' => 'Modules\\Ordering\\Events\\OrderPlaced',
            'contextscope' => 'Modules\\Shared\\Context\\ContextScope',
            'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
            'money' => 'Modules\\Shared\\Domain\\Money\\Money',
            'moneyformatter' => 'Modules\\Shared\\Support\\MoneyFormatter',
            'throwable' => 'Throwable',
          ),
           'className' => 'Modules\\Notification\\Application\\Listeners\\SendOrderNotifications',
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
      '411d42882e62409288657aea637855b9' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Notification\\Application\\Listeners',
         'uses' => 
        array (
          'log' => 'Illuminate\\Support\\Facades\\Log',
          'shipmentreader' => 'Modules\\Fulfillment\\Contracts\\ShipmentReader',
          'shipmentstatuschanged' => 'Modules\\Fulfillment\\Events\\ShipmentStatusChanged',
          'notificationrequest' => 'Modules\\Notification\\Contracts\\Data\\NotificationRequest',
          'recipient' => 'Modules\\Notification\\Contracts\\Data\\Recipient',
          'notifier' => 'Modules\\Notification\\Contracts\\Notifier',
          'orderdata' => 'Modules\\Ordering\\Contracts\\Data\\OrderData',
          'orderreader' => 'Modules\\Ordering\\Contracts\\OrderReader',
          'ordercancelled' => 'Modules\\Ordering\\Events\\OrderCancelled',
          'orderplaced' => 'Modules\\Ordering\\Events\\OrderPlaced',
          'contextscope' => 'Modules\\Shared\\Context\\ContextScope',
          'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
          'money' => 'Modules\\Shared\\Domain\\Money\\Money',
          'moneyformatter' => 'Modules\\Shared\\Support\\MoneyFormatter',
          'throwable' => 'Throwable',
        ),
         'className' => 'Modules\\Notification\\Application\\Listeners\\SendOrderNotifications',
         'functionName' => 'shipmentStatusChanged',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'Modules\\Notification\\Application\\Listeners',
           'uses' => 
          array (
            'log' => 'Illuminate\\Support\\Facades\\Log',
            'shipmentreader' => 'Modules\\Fulfillment\\Contracts\\ShipmentReader',
            'shipmentstatuschanged' => 'Modules\\Fulfillment\\Events\\ShipmentStatusChanged',
            'notificationrequest' => 'Modules\\Notification\\Contracts\\Data\\NotificationRequest',
            'recipient' => 'Modules\\Notification\\Contracts\\Data\\Recipient',
            'notifier' => 'Modules\\Notification\\Contracts\\Notifier',
            'orderdata' => 'Modules\\Ordering\\Contracts\\Data\\OrderData',
            'orderreader' => 'Modules\\Ordering\\Contracts\\OrderReader',
            'ordercancelled' => 'Modules\\Ordering\\Events\\OrderCancelled',
            'orderplaced' => 'Modules\\Ordering\\Events\\OrderPlaced',
            'contextscope' => 'Modules\\Shared\\Context\\ContextScope',
            'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
            'money' => 'Modules\\Shared\\Domain\\Money\\Money',
            'moneyformatter' => 'Modules\\Shared\\Support\\MoneyFormatter',
            'throwable' => 'Throwable',
          ),
           'className' => 'Modules\\Notification\\Application\\Listeners\\SendOrderNotifications',
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
      '0411069afe95f9b4bf636e4dd42e4d99' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Notification\\Application\\Listeners',
         'uses' => 
        array (
          'log' => 'Illuminate\\Support\\Facades\\Log',
          'shipmentreader' => 'Modules\\Fulfillment\\Contracts\\ShipmentReader',
          'shipmentstatuschanged' => 'Modules\\Fulfillment\\Events\\ShipmentStatusChanged',
          'notificationrequest' => 'Modules\\Notification\\Contracts\\Data\\NotificationRequest',
          'recipient' => 'Modules\\Notification\\Contracts\\Data\\Recipient',
          'notifier' => 'Modules\\Notification\\Contracts\\Notifier',
          'orderdata' => 'Modules\\Ordering\\Contracts\\Data\\OrderData',
          'orderreader' => 'Modules\\Ordering\\Contracts\\OrderReader',
          'ordercancelled' => 'Modules\\Ordering\\Events\\OrderCancelled',
          'orderplaced' => 'Modules\\Ordering\\Events\\OrderPlaced',
          'contextscope' => 'Modules\\Shared\\Context\\ContextScope',
          'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
          'money' => 'Modules\\Shared\\Domain\\Money\\Money',
          'moneyformatter' => 'Modules\\Shared\\Support\\MoneyFormatter',
          'throwable' => 'Throwable',
        ),
         'className' => 'Modules\\Notification\\Application\\Listeners\\SendOrderNotifications',
         'functionName' => 'send',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'Modules\\Notification\\Application\\Listeners',
           'uses' => 
          array (
            'log' => 'Illuminate\\Support\\Facades\\Log',
            'shipmentreader' => 'Modules\\Fulfillment\\Contracts\\ShipmentReader',
            'shipmentstatuschanged' => 'Modules\\Fulfillment\\Events\\ShipmentStatusChanged',
            'notificationrequest' => 'Modules\\Notification\\Contracts\\Data\\NotificationRequest',
            'recipient' => 'Modules\\Notification\\Contracts\\Data\\Recipient',
            'notifier' => 'Modules\\Notification\\Contracts\\Notifier',
            'orderdata' => 'Modules\\Ordering\\Contracts\\Data\\OrderData',
            'orderreader' => 'Modules\\Ordering\\Contracts\\OrderReader',
            'ordercancelled' => 'Modules\\Ordering\\Events\\OrderCancelled',
            'orderplaced' => 'Modules\\Ordering\\Events\\OrderPlaced',
            'contextscope' => 'Modules\\Shared\\Context\\ContextScope',
            'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
            'money' => 'Modules\\Shared\\Domain\\Money\\Money',
            'moneyformatter' => 'Modules\\Shared\\Support\\MoneyFormatter',
            'throwable' => 'Throwable',
          ),
           'className' => 'Modules\\Notification\\Application\\Listeners\\SendOrderNotifications',
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
      '/Users/dat/Ecommerce/vanishop/modules/Notification/Application/Listeners/SendOrderNotifications.php' => 'ccce6ee201398b4d1fe0fcc16c9e10f3af3f68827e0eccc36c3e2646c2508d7f',
    ),
  ),
));