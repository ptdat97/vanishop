<?php declare(strict_types = 1);

// ftm-/Users/dat/Ecommerce/vanishop/modules/Customer/Application/AuthService.php
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v6-2.3.5',
   'data' => 
  array (
    0 => 
    array (
      '9fd2cef583779bd0aafeb6368533ef8a' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Customer\\Application',
         'uses' => 
        array (
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'hash' => 'Illuminate\\Support\\Facades\\Hash',
          'ratelimiter' => 'Illuminate\\Support\\Facades\\RateLimiter',
          'str' => 'Illuminate\\Support\\Str',
          'customerrejected' => 'Modules\\Customer\\Contracts\\CustomerRejected',
          'customerdata' => 'Modules\\Customer\\Contracts\\Data\\CustomerData',
          'externalidentity' => 'Modules\\Customer\\Contracts\\Data\\ExternalIdentity',
          'otppurpose' => 'Modules\\Customer\\Contracts\\Data\\OtpPurpose',
          'customerstatus' => 'Modules\\Customer\\Domain\\CustomerStatus',
          'customerregistered' => 'Modules\\Customer\\Events\\CustomerRegistered',
          'customer' => 'Modules\\Customer\\Persistence\\Models\\Customer',
          'customeridentity' => 'Modules\\Customer\\Persistence\\Models\\CustomerIdentity',
          'customertoken' => 'Modules\\Customer\\Persistence\\Models\\CustomerToken',
          'phonenumber' => 'Modules\\Shared\\Domain\\Phone\\PhoneNumber',
        ),
         'className' => 'Modules\\Customer\\Application\\AuthService',
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
      'fe9ee2458e88e830e512bce18a5afe7d' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Customer\\Application',
         'uses' => 
        array (
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'hash' => 'Illuminate\\Support\\Facades\\Hash',
          'ratelimiter' => 'Illuminate\\Support\\Facades\\RateLimiter',
          'str' => 'Illuminate\\Support\\Str',
          'customerrejected' => 'Modules\\Customer\\Contracts\\CustomerRejected',
          'customerdata' => 'Modules\\Customer\\Contracts\\Data\\CustomerData',
          'externalidentity' => 'Modules\\Customer\\Contracts\\Data\\ExternalIdentity',
          'otppurpose' => 'Modules\\Customer\\Contracts\\Data\\OtpPurpose',
          'customerstatus' => 'Modules\\Customer\\Domain\\CustomerStatus',
          'customerregistered' => 'Modules\\Customer\\Events\\CustomerRegistered',
          'customer' => 'Modules\\Customer\\Persistence\\Models\\Customer',
          'customeridentity' => 'Modules\\Customer\\Persistence\\Models\\CustomerIdentity',
          'customertoken' => 'Modules\\Customer\\Persistence\\Models\\CustomerToken',
          'phonenumber' => 'Modules\\Shared\\Domain\\Phone\\PhoneNumber',
        ),
         'className' => 'Modules\\Customer\\Application\\AuthService',
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
            'hash' => 'Illuminate\\Support\\Facades\\Hash',
            'ratelimiter' => 'Illuminate\\Support\\Facades\\RateLimiter',
            'str' => 'Illuminate\\Support\\Str',
            'customerrejected' => 'Modules\\Customer\\Contracts\\CustomerRejected',
            'customerdata' => 'Modules\\Customer\\Contracts\\Data\\CustomerData',
            'externalidentity' => 'Modules\\Customer\\Contracts\\Data\\ExternalIdentity',
            'otppurpose' => 'Modules\\Customer\\Contracts\\Data\\OtpPurpose',
            'customerstatus' => 'Modules\\Customer\\Domain\\CustomerStatus',
            'customerregistered' => 'Modules\\Customer\\Events\\CustomerRegistered',
            'customer' => 'Modules\\Customer\\Persistence\\Models\\Customer',
            'customeridentity' => 'Modules\\Customer\\Persistence\\Models\\CustomerIdentity',
            'customertoken' => 'Modules\\Customer\\Persistence\\Models\\CustomerToken',
            'phonenumber' => 'Modules\\Shared\\Domain\\Phone\\PhoneNumber',
          ),
           'className' => 'Modules\\Customer\\Application\\AuthService',
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
      '6c5735ad88eba9a5e4e2cb5c4a9cc5ee' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Customer\\Application',
         'uses' => 
        array (
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'hash' => 'Illuminate\\Support\\Facades\\Hash',
          'ratelimiter' => 'Illuminate\\Support\\Facades\\RateLimiter',
          'str' => 'Illuminate\\Support\\Str',
          'customerrejected' => 'Modules\\Customer\\Contracts\\CustomerRejected',
          'customerdata' => 'Modules\\Customer\\Contracts\\Data\\CustomerData',
          'externalidentity' => 'Modules\\Customer\\Contracts\\Data\\ExternalIdentity',
          'otppurpose' => 'Modules\\Customer\\Contracts\\Data\\OtpPurpose',
          'customerstatus' => 'Modules\\Customer\\Domain\\CustomerStatus',
          'customerregistered' => 'Modules\\Customer\\Events\\CustomerRegistered',
          'customer' => 'Modules\\Customer\\Persistence\\Models\\Customer',
          'customeridentity' => 'Modules\\Customer\\Persistence\\Models\\CustomerIdentity',
          'customertoken' => 'Modules\\Customer\\Persistence\\Models\\CustomerToken',
          'phonenumber' => 'Modules\\Shared\\Domain\\Phone\\PhoneNumber',
        ),
         'className' => 'Modules\\Customer\\Application\\AuthService',
         'functionName' => 'loginWithOtp',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'Modules\\Customer\\Application',
           'uses' => 
          array (
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'hash' => 'Illuminate\\Support\\Facades\\Hash',
            'ratelimiter' => 'Illuminate\\Support\\Facades\\RateLimiter',
            'str' => 'Illuminate\\Support\\Str',
            'customerrejected' => 'Modules\\Customer\\Contracts\\CustomerRejected',
            'customerdata' => 'Modules\\Customer\\Contracts\\Data\\CustomerData',
            'externalidentity' => 'Modules\\Customer\\Contracts\\Data\\ExternalIdentity',
            'otppurpose' => 'Modules\\Customer\\Contracts\\Data\\OtpPurpose',
            'customerstatus' => 'Modules\\Customer\\Domain\\CustomerStatus',
            'customerregistered' => 'Modules\\Customer\\Events\\CustomerRegistered',
            'customer' => 'Modules\\Customer\\Persistence\\Models\\Customer',
            'customeridentity' => 'Modules\\Customer\\Persistence\\Models\\CustomerIdentity',
            'customertoken' => 'Modules\\Customer\\Persistence\\Models\\CustomerToken',
            'phonenumber' => 'Modules\\Shared\\Domain\\Phone\\PhoneNumber',
          ),
           'className' => 'Modules\\Customer\\Application\\AuthService',
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
      '021a4ee2e7f5bc0c0584cbd8492f6c25' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Customer\\Application',
         'uses' => 
        array (
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'hash' => 'Illuminate\\Support\\Facades\\Hash',
          'ratelimiter' => 'Illuminate\\Support\\Facades\\RateLimiter',
          'str' => 'Illuminate\\Support\\Str',
          'customerrejected' => 'Modules\\Customer\\Contracts\\CustomerRejected',
          'customerdata' => 'Modules\\Customer\\Contracts\\Data\\CustomerData',
          'externalidentity' => 'Modules\\Customer\\Contracts\\Data\\ExternalIdentity',
          'otppurpose' => 'Modules\\Customer\\Contracts\\Data\\OtpPurpose',
          'customerstatus' => 'Modules\\Customer\\Domain\\CustomerStatus',
          'customerregistered' => 'Modules\\Customer\\Events\\CustomerRegistered',
          'customer' => 'Modules\\Customer\\Persistence\\Models\\Customer',
          'customeridentity' => 'Modules\\Customer\\Persistence\\Models\\CustomerIdentity',
          'customertoken' => 'Modules\\Customer\\Persistence\\Models\\CustomerToken',
          'phonenumber' => 'Modules\\Shared\\Domain\\Phone\\PhoneNumber',
        ),
         'className' => 'Modules\\Customer\\Application\\AuthService',
         'functionName' => 'loginWithIdentity',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'Modules\\Customer\\Application',
           'uses' => 
          array (
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'hash' => 'Illuminate\\Support\\Facades\\Hash',
            'ratelimiter' => 'Illuminate\\Support\\Facades\\RateLimiter',
            'str' => 'Illuminate\\Support\\Str',
            'customerrejected' => 'Modules\\Customer\\Contracts\\CustomerRejected',
            'customerdata' => 'Modules\\Customer\\Contracts\\Data\\CustomerData',
            'externalidentity' => 'Modules\\Customer\\Contracts\\Data\\ExternalIdentity',
            'otppurpose' => 'Modules\\Customer\\Contracts\\Data\\OtpPurpose',
            'customerstatus' => 'Modules\\Customer\\Domain\\CustomerStatus',
            'customerregistered' => 'Modules\\Customer\\Events\\CustomerRegistered',
            'customer' => 'Modules\\Customer\\Persistence\\Models\\Customer',
            'customeridentity' => 'Modules\\Customer\\Persistence\\Models\\CustomerIdentity',
            'customertoken' => 'Modules\\Customer\\Persistence\\Models\\CustomerToken',
            'phonenumber' => 'Modules\\Shared\\Domain\\Phone\\PhoneNumber',
          ),
           'className' => 'Modules\\Customer\\Application\\AuthService',
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
      'f8b21b1f03bd142a1831e4aa29ccc987' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Customer\\Application',
         'uses' => 
        array (
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'hash' => 'Illuminate\\Support\\Facades\\Hash',
          'ratelimiter' => 'Illuminate\\Support\\Facades\\RateLimiter',
          'str' => 'Illuminate\\Support\\Str',
          'customerrejected' => 'Modules\\Customer\\Contracts\\CustomerRejected',
          'customerdata' => 'Modules\\Customer\\Contracts\\Data\\CustomerData',
          'externalidentity' => 'Modules\\Customer\\Contracts\\Data\\ExternalIdentity',
          'otppurpose' => 'Modules\\Customer\\Contracts\\Data\\OtpPurpose',
          'customerstatus' => 'Modules\\Customer\\Domain\\CustomerStatus',
          'customerregistered' => 'Modules\\Customer\\Events\\CustomerRegistered',
          'customer' => 'Modules\\Customer\\Persistence\\Models\\Customer',
          'customeridentity' => 'Modules\\Customer\\Persistence\\Models\\CustomerIdentity',
          'customertoken' => 'Modules\\Customer\\Persistence\\Models\\CustomerToken',
          'phonenumber' => 'Modules\\Shared\\Domain\\Phone\\PhoneNumber',
        ),
         'className' => 'Modules\\Customer\\Application\\AuthService',
         'functionName' => 'loginWithPassword',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'Modules\\Customer\\Application',
           'uses' => 
          array (
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'hash' => 'Illuminate\\Support\\Facades\\Hash',
            'ratelimiter' => 'Illuminate\\Support\\Facades\\RateLimiter',
            'str' => 'Illuminate\\Support\\Str',
            'customerrejected' => 'Modules\\Customer\\Contracts\\CustomerRejected',
            'customerdata' => 'Modules\\Customer\\Contracts\\Data\\CustomerData',
            'externalidentity' => 'Modules\\Customer\\Contracts\\Data\\ExternalIdentity',
            'otppurpose' => 'Modules\\Customer\\Contracts\\Data\\OtpPurpose',
            'customerstatus' => 'Modules\\Customer\\Domain\\CustomerStatus',
            'customerregistered' => 'Modules\\Customer\\Events\\CustomerRegistered',
            'customer' => 'Modules\\Customer\\Persistence\\Models\\Customer',
            'customeridentity' => 'Modules\\Customer\\Persistence\\Models\\CustomerIdentity',
            'customertoken' => 'Modules\\Customer\\Persistence\\Models\\CustomerToken',
            'phonenumber' => 'Modules\\Shared\\Domain\\Phone\\PhoneNumber',
          ),
           'className' => 'Modules\\Customer\\Application\\AuthService',
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
      '3d93ff8780f8adc2d39fff37ebdf931f' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Customer\\Application',
         'uses' => 
        array (
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'hash' => 'Illuminate\\Support\\Facades\\Hash',
          'ratelimiter' => 'Illuminate\\Support\\Facades\\RateLimiter',
          'str' => 'Illuminate\\Support\\Str',
          'customerrejected' => 'Modules\\Customer\\Contracts\\CustomerRejected',
          'customerdata' => 'Modules\\Customer\\Contracts\\Data\\CustomerData',
          'externalidentity' => 'Modules\\Customer\\Contracts\\Data\\ExternalIdentity',
          'otppurpose' => 'Modules\\Customer\\Contracts\\Data\\OtpPurpose',
          'customerstatus' => 'Modules\\Customer\\Domain\\CustomerStatus',
          'customerregistered' => 'Modules\\Customer\\Events\\CustomerRegistered',
          'customer' => 'Modules\\Customer\\Persistence\\Models\\Customer',
          'customeridentity' => 'Modules\\Customer\\Persistence\\Models\\CustomerIdentity',
          'customertoken' => 'Modules\\Customer\\Persistence\\Models\\CustomerToken',
          'phonenumber' => 'Modules\\Shared\\Domain\\Phone\\PhoneNumber',
        ),
         'className' => 'Modules\\Customer\\Application\\AuthService',
         'functionName' => 'authenticate',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'Modules\\Customer\\Application',
           'uses' => 
          array (
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'hash' => 'Illuminate\\Support\\Facades\\Hash',
            'ratelimiter' => 'Illuminate\\Support\\Facades\\RateLimiter',
            'str' => 'Illuminate\\Support\\Str',
            'customerrejected' => 'Modules\\Customer\\Contracts\\CustomerRejected',
            'customerdata' => 'Modules\\Customer\\Contracts\\Data\\CustomerData',
            'externalidentity' => 'Modules\\Customer\\Contracts\\Data\\ExternalIdentity',
            'otppurpose' => 'Modules\\Customer\\Contracts\\Data\\OtpPurpose',
            'customerstatus' => 'Modules\\Customer\\Domain\\CustomerStatus',
            'customerregistered' => 'Modules\\Customer\\Events\\CustomerRegistered',
            'customer' => 'Modules\\Customer\\Persistence\\Models\\Customer',
            'customeridentity' => 'Modules\\Customer\\Persistence\\Models\\CustomerIdentity',
            'customertoken' => 'Modules\\Customer\\Persistence\\Models\\CustomerToken',
            'phonenumber' => 'Modules\\Shared\\Domain\\Phone\\PhoneNumber',
          ),
           'className' => 'Modules\\Customer\\Application\\AuthService',
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
      '9ef8ce8045674ae0995a01ea4ad58959' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Customer\\Application',
         'uses' => 
        array (
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'hash' => 'Illuminate\\Support\\Facades\\Hash',
          'ratelimiter' => 'Illuminate\\Support\\Facades\\RateLimiter',
          'str' => 'Illuminate\\Support\\Str',
          'customerrejected' => 'Modules\\Customer\\Contracts\\CustomerRejected',
          'customerdata' => 'Modules\\Customer\\Contracts\\Data\\CustomerData',
          'externalidentity' => 'Modules\\Customer\\Contracts\\Data\\ExternalIdentity',
          'otppurpose' => 'Modules\\Customer\\Contracts\\Data\\OtpPurpose',
          'customerstatus' => 'Modules\\Customer\\Domain\\CustomerStatus',
          'customerregistered' => 'Modules\\Customer\\Events\\CustomerRegistered',
          'customer' => 'Modules\\Customer\\Persistence\\Models\\Customer',
          'customeridentity' => 'Modules\\Customer\\Persistence\\Models\\CustomerIdentity',
          'customertoken' => 'Modules\\Customer\\Persistence\\Models\\CustomerToken',
          'phonenumber' => 'Modules\\Shared\\Domain\\Phone\\PhoneNumber',
        ),
         'className' => 'Modules\\Customer\\Application\\AuthService',
         'functionName' => 'logout',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'Modules\\Customer\\Application',
           'uses' => 
          array (
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'hash' => 'Illuminate\\Support\\Facades\\Hash',
            'ratelimiter' => 'Illuminate\\Support\\Facades\\RateLimiter',
            'str' => 'Illuminate\\Support\\Str',
            'customerrejected' => 'Modules\\Customer\\Contracts\\CustomerRejected',
            'customerdata' => 'Modules\\Customer\\Contracts\\Data\\CustomerData',
            'externalidentity' => 'Modules\\Customer\\Contracts\\Data\\ExternalIdentity',
            'otppurpose' => 'Modules\\Customer\\Contracts\\Data\\OtpPurpose',
            'customerstatus' => 'Modules\\Customer\\Domain\\CustomerStatus',
            'customerregistered' => 'Modules\\Customer\\Events\\CustomerRegistered',
            'customer' => 'Modules\\Customer\\Persistence\\Models\\Customer',
            'customeridentity' => 'Modules\\Customer\\Persistence\\Models\\CustomerIdentity',
            'customertoken' => 'Modules\\Customer\\Persistence\\Models\\CustomerToken',
            'phonenumber' => 'Modules\\Shared\\Domain\\Phone\\PhoneNumber',
          ),
           'className' => 'Modules\\Customer\\Application\\AuthService',
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
      '0f3727e369488ed53d1b0a4b12936d01' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Customer\\Application',
         'uses' => 
        array (
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'hash' => 'Illuminate\\Support\\Facades\\Hash',
          'ratelimiter' => 'Illuminate\\Support\\Facades\\RateLimiter',
          'str' => 'Illuminate\\Support\\Str',
          'customerrejected' => 'Modules\\Customer\\Contracts\\CustomerRejected',
          'customerdata' => 'Modules\\Customer\\Contracts\\Data\\CustomerData',
          'externalidentity' => 'Modules\\Customer\\Contracts\\Data\\ExternalIdentity',
          'otppurpose' => 'Modules\\Customer\\Contracts\\Data\\OtpPurpose',
          'customerstatus' => 'Modules\\Customer\\Domain\\CustomerStatus',
          'customerregistered' => 'Modules\\Customer\\Events\\CustomerRegistered',
          'customer' => 'Modules\\Customer\\Persistence\\Models\\Customer',
          'customeridentity' => 'Modules\\Customer\\Persistence\\Models\\CustomerIdentity',
          'customertoken' => 'Modules\\Customer\\Persistence\\Models\\CustomerToken',
          'phonenumber' => 'Modules\\Shared\\Domain\\Phone\\PhoneNumber',
        ),
         'className' => 'Modules\\Customer\\Application\\AuthService',
         'functionName' => 'revokeAll',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'Modules\\Customer\\Application',
           'uses' => 
          array (
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'hash' => 'Illuminate\\Support\\Facades\\Hash',
            'ratelimiter' => 'Illuminate\\Support\\Facades\\RateLimiter',
            'str' => 'Illuminate\\Support\\Str',
            'customerrejected' => 'Modules\\Customer\\Contracts\\CustomerRejected',
            'customerdata' => 'Modules\\Customer\\Contracts\\Data\\CustomerData',
            'externalidentity' => 'Modules\\Customer\\Contracts\\Data\\ExternalIdentity',
            'otppurpose' => 'Modules\\Customer\\Contracts\\Data\\OtpPurpose',
            'customerstatus' => 'Modules\\Customer\\Domain\\CustomerStatus',
            'customerregistered' => 'Modules\\Customer\\Events\\CustomerRegistered',
            'customer' => 'Modules\\Customer\\Persistence\\Models\\Customer',
            'customeridentity' => 'Modules\\Customer\\Persistence\\Models\\CustomerIdentity',
            'customertoken' => 'Modules\\Customer\\Persistence\\Models\\CustomerToken',
            'phonenumber' => 'Modules\\Shared\\Domain\\Phone\\PhoneNumber',
          ),
           'className' => 'Modules\\Customer\\Application\\AuthService',
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
      '922904ecf26263d8f14e1e9d68d12f95' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Customer\\Application',
         'uses' => 
        array (
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'hash' => 'Illuminate\\Support\\Facades\\Hash',
          'ratelimiter' => 'Illuminate\\Support\\Facades\\RateLimiter',
          'str' => 'Illuminate\\Support\\Str',
          'customerrejected' => 'Modules\\Customer\\Contracts\\CustomerRejected',
          'customerdata' => 'Modules\\Customer\\Contracts\\Data\\CustomerData',
          'externalidentity' => 'Modules\\Customer\\Contracts\\Data\\ExternalIdentity',
          'otppurpose' => 'Modules\\Customer\\Contracts\\Data\\OtpPurpose',
          'customerstatus' => 'Modules\\Customer\\Domain\\CustomerStatus',
          'customerregistered' => 'Modules\\Customer\\Events\\CustomerRegistered',
          'customer' => 'Modules\\Customer\\Persistence\\Models\\Customer',
          'customeridentity' => 'Modules\\Customer\\Persistence\\Models\\CustomerIdentity',
          'customertoken' => 'Modules\\Customer\\Persistence\\Models\\CustomerToken',
          'phonenumber' => 'Modules\\Shared\\Domain\\Phone\\PhoneNumber',
        ),
         'className' => 'Modules\\Customer\\Application\\AuthService',
         'functionName' => 'setPassword',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'Modules\\Customer\\Application',
           'uses' => 
          array (
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'hash' => 'Illuminate\\Support\\Facades\\Hash',
            'ratelimiter' => 'Illuminate\\Support\\Facades\\RateLimiter',
            'str' => 'Illuminate\\Support\\Str',
            'customerrejected' => 'Modules\\Customer\\Contracts\\CustomerRejected',
            'customerdata' => 'Modules\\Customer\\Contracts\\Data\\CustomerData',
            'externalidentity' => 'Modules\\Customer\\Contracts\\Data\\ExternalIdentity',
            'otppurpose' => 'Modules\\Customer\\Contracts\\Data\\OtpPurpose',
            'customerstatus' => 'Modules\\Customer\\Domain\\CustomerStatus',
            'customerregistered' => 'Modules\\Customer\\Events\\CustomerRegistered',
            'customer' => 'Modules\\Customer\\Persistence\\Models\\Customer',
            'customeridentity' => 'Modules\\Customer\\Persistence\\Models\\CustomerIdentity',
            'customertoken' => 'Modules\\Customer\\Persistence\\Models\\CustomerToken',
            'phonenumber' => 'Modules\\Shared\\Domain\\Phone\\PhoneNumber',
          ),
           'className' => 'Modules\\Customer\\Application\\AuthService',
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
      '330f717ea97a8e190ee855cceafaeb8d' => 
      \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
         'namespace' => 'Modules\\Customer\\Application',
         'uses' => 
        array (
          'db' => 'Illuminate\\Support\\Facades\\DB',
          'hash' => 'Illuminate\\Support\\Facades\\Hash',
          'ratelimiter' => 'Illuminate\\Support\\Facades\\RateLimiter',
          'str' => 'Illuminate\\Support\\Str',
          'customerrejected' => 'Modules\\Customer\\Contracts\\CustomerRejected',
          'customerdata' => 'Modules\\Customer\\Contracts\\Data\\CustomerData',
          'externalidentity' => 'Modules\\Customer\\Contracts\\Data\\ExternalIdentity',
          'otppurpose' => 'Modules\\Customer\\Contracts\\Data\\OtpPurpose',
          'customerstatus' => 'Modules\\Customer\\Domain\\CustomerStatus',
          'customerregistered' => 'Modules\\Customer\\Events\\CustomerRegistered',
          'customer' => 'Modules\\Customer\\Persistence\\Models\\Customer',
          'customeridentity' => 'Modules\\Customer\\Persistence\\Models\\CustomerIdentity',
          'customertoken' => 'Modules\\Customer\\Persistence\\Models\\CustomerToken',
          'phonenumber' => 'Modules\\Shared\\Domain\\Phone\\PhoneNumber',
        ),
         'className' => 'Modules\\Customer\\Application\\AuthService',
         'functionName' => 'startSession',
         'templatePhpDocNodes' => 
        array (
        ),
         'parent' => 
        \PHPStan\Analyser\IntermediaryNameScope::__set_state(array(
           'namespace' => 'Modules\\Customer\\Application',
           'uses' => 
          array (
            'db' => 'Illuminate\\Support\\Facades\\DB',
            'hash' => 'Illuminate\\Support\\Facades\\Hash',
            'ratelimiter' => 'Illuminate\\Support\\Facades\\RateLimiter',
            'str' => 'Illuminate\\Support\\Str',
            'customerrejected' => 'Modules\\Customer\\Contracts\\CustomerRejected',
            'customerdata' => 'Modules\\Customer\\Contracts\\Data\\CustomerData',
            'externalidentity' => 'Modules\\Customer\\Contracts\\Data\\ExternalIdentity',
            'otppurpose' => 'Modules\\Customer\\Contracts\\Data\\OtpPurpose',
            'customerstatus' => 'Modules\\Customer\\Domain\\CustomerStatus',
            'customerregistered' => 'Modules\\Customer\\Events\\CustomerRegistered',
            'customer' => 'Modules\\Customer\\Persistence\\Models\\Customer',
            'customeridentity' => 'Modules\\Customer\\Persistence\\Models\\CustomerIdentity',
            'customertoken' => 'Modules\\Customer\\Persistence\\Models\\CustomerToken',
            'phonenumber' => 'Modules\\Shared\\Domain\\Phone\\PhoneNumber',
          ),
           'className' => 'Modules\\Customer\\Application\\AuthService',
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
      '/Users/dat/Ecommerce/vanishop/modules/Customer/Application/AuthService.php' => 'b69c4f913fe87a2a9172ade6e26dcf5c2c7d3f891d44bc20338facd5fa60f972',
    ),
  ),
));