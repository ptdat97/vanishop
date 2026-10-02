<?php declare(strict_types = 1);

// ftm-/Users/dat/Ecommerce/vanishop/modules/Customer/Application/AccountLifecycle.php
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v6-2.3.5',
   'data' => 
  array (
    0 => 
    array (
      'e81b0a3b1abd9c1ce7d4afe64e5c219c' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Customer\\Application',
         'uses' => 
        array (
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'customerrejected' => 'Modules\\Customer\\Contracts\\CustomerRejected',
          'customerstatus' => 'Modules\\Customer\\Domain\\CustomerStatus',
          'customeranonymized' => 'Modules\\Customer\\Events\\CustomerAnonymized',
          'customermerged' => 'Modules\\Customer\\Events\\CustomerMerged',
          'customer' => 'Modules\\Customer\\Persistence\\Models\\Customer',
          'customeraddress' => 'Modules\\Customer\\Persistence\\Models\\CustomerAddress',
          'customeridentity' => 'Modules\\Customer\\Persistence\\Models\\CustomerIdentity',
          'auditlogger' => 'Modules\\Identity\\Contracts\\AuditLogger',
          'customerorders' => 'Modules\\Ordering\\Contracts\\CustomerOrders',
          'orderwriter' => 'Modules\\Ordering\\Contracts\\OrderWriter',
          'contextscope' => 'Modules\\Shared\\Context\\ContextScope',
          'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
        ),
         'className' => 'Modules\\Customer\\Application\\AccountLifecycle',
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
      '40c1933e1f892eb1b9c0dd9e670aa004' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Customer\\Application',
         'uses' => 
        array (
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'customerrejected' => 'Modules\\Customer\\Contracts\\CustomerRejected',
          'customerstatus' => 'Modules\\Customer\\Domain\\CustomerStatus',
          'customeranonymized' => 'Modules\\Customer\\Events\\CustomerAnonymized',
          'customermerged' => 'Modules\\Customer\\Events\\CustomerMerged',
          'customer' => 'Modules\\Customer\\Persistence\\Models\\Customer',
          'customeraddress' => 'Modules\\Customer\\Persistence\\Models\\CustomerAddress',
          'customeridentity' => 'Modules\\Customer\\Persistence\\Models\\CustomerIdentity',
          'auditlogger' => 'Modules\\Identity\\Contracts\\AuditLogger',
          'customerorders' => 'Modules\\Ordering\\Contracts\\CustomerOrders',
          'orderwriter' => 'Modules\\Ordering\\Contracts\\OrderWriter',
          'contextscope' => 'Modules\\Shared\\Context\\ContextScope',
          'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
        ),
         'className' => 'Modules\\Customer\\Application\\AccountLifecycle',
         'functionName' => '__construct',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'Modules\\Customer\\Application',
           'uses' => 
          array (
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'customerrejected' => 'Modules\\Customer\\Contracts\\CustomerRejected',
            'customerstatus' => 'Modules\\Customer\\Domain\\CustomerStatus',
            'customeranonymized' => 'Modules\\Customer\\Events\\CustomerAnonymized',
            'customermerged' => 'Modules\\Customer\\Events\\CustomerMerged',
            'customer' => 'Modules\\Customer\\Persistence\\Models\\Customer',
            'customeraddress' => 'Modules\\Customer\\Persistence\\Models\\CustomerAddress',
            'customeridentity' => 'Modules\\Customer\\Persistence\\Models\\CustomerIdentity',
            'auditlogger' => 'Modules\\Identity\\Contracts\\AuditLogger',
            'customerorders' => 'Modules\\Ordering\\Contracts\\CustomerOrders',
            'orderwriter' => 'Modules\\Ordering\\Contracts\\OrderWriter',
            'contextscope' => 'Modules\\Shared\\Context\\ContextScope',
            'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
          ),
           'className' => 'Modules\\Customer\\Application\\AccountLifecycle',
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
      '4403ccd81b03599d08d33ca64fe972d4' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Customer\\Application',
         'uses' => 
        array (
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'customerrejected' => 'Modules\\Customer\\Contracts\\CustomerRejected',
          'customerstatus' => 'Modules\\Customer\\Domain\\CustomerStatus',
          'customeranonymized' => 'Modules\\Customer\\Events\\CustomerAnonymized',
          'customermerged' => 'Modules\\Customer\\Events\\CustomerMerged',
          'customer' => 'Modules\\Customer\\Persistence\\Models\\Customer',
          'customeraddress' => 'Modules\\Customer\\Persistence\\Models\\CustomerAddress',
          'customeridentity' => 'Modules\\Customer\\Persistence\\Models\\CustomerIdentity',
          'auditlogger' => 'Modules\\Identity\\Contracts\\AuditLogger',
          'customerorders' => 'Modules\\Ordering\\Contracts\\CustomerOrders',
          'orderwriter' => 'Modules\\Ordering\\Contracts\\OrderWriter',
          'contextscope' => 'Modules\\Shared\\Context\\ContextScope',
          'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
        ),
         'className' => 'Modules\\Customer\\Application\\AccountLifecycle',
         'functionName' => 'merge',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'Modules\\Customer\\Application',
           'uses' => 
          array (
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'customerrejected' => 'Modules\\Customer\\Contracts\\CustomerRejected',
            'customerstatus' => 'Modules\\Customer\\Domain\\CustomerStatus',
            'customeranonymized' => 'Modules\\Customer\\Events\\CustomerAnonymized',
            'customermerged' => 'Modules\\Customer\\Events\\CustomerMerged',
            'customer' => 'Modules\\Customer\\Persistence\\Models\\Customer',
            'customeraddress' => 'Modules\\Customer\\Persistence\\Models\\CustomerAddress',
            'customeridentity' => 'Modules\\Customer\\Persistence\\Models\\CustomerIdentity',
            'auditlogger' => 'Modules\\Identity\\Contracts\\AuditLogger',
            'customerorders' => 'Modules\\Ordering\\Contracts\\CustomerOrders',
            'orderwriter' => 'Modules\\Ordering\\Contracts\\OrderWriter',
            'contextscope' => 'Modules\\Shared\\Context\\ContextScope',
            'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
          ),
           'className' => 'Modules\\Customer\\Application\\AccountLifecycle',
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
      'b94b3cd6cac025d9f7c47795614839a0' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Customer\\Application',
         'uses' => 
        array (
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'customerrejected' => 'Modules\\Customer\\Contracts\\CustomerRejected',
          'customerstatus' => 'Modules\\Customer\\Domain\\CustomerStatus',
          'customeranonymized' => 'Modules\\Customer\\Events\\CustomerAnonymized',
          'customermerged' => 'Modules\\Customer\\Events\\CustomerMerged',
          'customer' => 'Modules\\Customer\\Persistence\\Models\\Customer',
          'customeraddress' => 'Modules\\Customer\\Persistence\\Models\\CustomerAddress',
          'customeridentity' => 'Modules\\Customer\\Persistence\\Models\\CustomerIdentity',
          'auditlogger' => 'Modules\\Identity\\Contracts\\AuditLogger',
          'customerorders' => 'Modules\\Ordering\\Contracts\\CustomerOrders',
          'orderwriter' => 'Modules\\Ordering\\Contracts\\OrderWriter',
          'contextscope' => 'Modules\\Shared\\Context\\ContextScope',
          'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
        ),
         'className' => 'Modules\\Customer\\Application\\AccountLifecycle',
         'functionName' => 'anonymize',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'Modules\\Customer\\Application',
           'uses' => 
          array (
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'customerrejected' => 'Modules\\Customer\\Contracts\\CustomerRejected',
            'customerstatus' => 'Modules\\Customer\\Domain\\CustomerStatus',
            'customeranonymized' => 'Modules\\Customer\\Events\\CustomerAnonymized',
            'customermerged' => 'Modules\\Customer\\Events\\CustomerMerged',
            'customer' => 'Modules\\Customer\\Persistence\\Models\\Customer',
            'customeraddress' => 'Modules\\Customer\\Persistence\\Models\\CustomerAddress',
            'customeridentity' => 'Modules\\Customer\\Persistence\\Models\\CustomerIdentity',
            'auditlogger' => 'Modules\\Identity\\Contracts\\AuditLogger',
            'customerorders' => 'Modules\\Ordering\\Contracts\\CustomerOrders',
            'orderwriter' => 'Modules\\Ordering\\Contracts\\OrderWriter',
            'contextscope' => 'Modules\\Shared\\Context\\ContextScope',
            'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
          ),
           'className' => 'Modules\\Customer\\Application\\AccountLifecycle',
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
      '712904bef7e5b911a79267934574fc7f' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Customer\\Application',
         'uses' => 
        array (
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'customerrejected' => 'Modules\\Customer\\Contracts\\CustomerRejected',
          'customerstatus' => 'Modules\\Customer\\Domain\\CustomerStatus',
          'customeranonymized' => 'Modules\\Customer\\Events\\CustomerAnonymized',
          'customermerged' => 'Modules\\Customer\\Events\\CustomerMerged',
          'customer' => 'Modules\\Customer\\Persistence\\Models\\Customer',
          'customeraddress' => 'Modules\\Customer\\Persistence\\Models\\CustomerAddress',
          'customeridentity' => 'Modules\\Customer\\Persistence\\Models\\CustomerIdentity',
          'auditlogger' => 'Modules\\Identity\\Contracts\\AuditLogger',
          'customerorders' => 'Modules\\Ordering\\Contracts\\CustomerOrders',
          'orderwriter' => 'Modules\\Ordering\\Contracts\\OrderWriter',
          'contextscope' => 'Modules\\Shared\\Context\\ContextScope',
          'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
        ),
         'className' => 'Modules\\Customer\\Application\\AccountLifecycle',
         'functionName' => 'export',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'Modules\\Customer\\Application',
           'uses' => 
          array (
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'customerrejected' => 'Modules\\Customer\\Contracts\\CustomerRejected',
            'customerstatus' => 'Modules\\Customer\\Domain\\CustomerStatus',
            'customeranonymized' => 'Modules\\Customer\\Events\\CustomerAnonymized',
            'customermerged' => 'Modules\\Customer\\Events\\CustomerMerged',
            'customer' => 'Modules\\Customer\\Persistence\\Models\\Customer',
            'customeraddress' => 'Modules\\Customer\\Persistence\\Models\\CustomerAddress',
            'customeridentity' => 'Modules\\Customer\\Persistence\\Models\\CustomerIdentity',
            'auditlogger' => 'Modules\\Identity\\Contracts\\AuditLogger',
            'customerorders' => 'Modules\\Ordering\\Contracts\\CustomerOrders',
            'orderwriter' => 'Modules\\Ordering\\Contracts\\OrderWriter',
            'contextscope' => 'Modules\\Shared\\Context\\ContextScope',
            'currentcontext' => 'Modules\\Shared\\Context\\CurrentContext',
          ),
           'className' => 'Modules\\Customer\\Application\\AccountLifecycle',
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
      '/Users/dat/Ecommerce/vanishop/modules/Customer/Application/AccountLifecycle.php' => '319e2a1c710b79d0f80aeb12b6fbcbe30d3c7a242347700510239a8b54e7c8fe',
    ),
  ),
));