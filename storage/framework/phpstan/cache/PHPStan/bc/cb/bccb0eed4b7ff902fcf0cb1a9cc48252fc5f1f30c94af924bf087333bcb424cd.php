<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Extension/Contracts/PluginHealthCheck.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Extension\Contracts\PluginHealthCheck
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-c4b4ac788754fb2f83557af3498100f20120f41bb9573dbaad1dd42876cdddf5',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Extension\\Contracts\\PluginHealthCheck',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Extension/Contracts/PluginHealthCheck.php',
      ),
    ),
    'namespace' => 'Modules\\Extension\\Contracts',
    'name' => 'Modules\\Extension\\Contracts\\PluginHealthCheck',
    'shortName' => 'PluginHealthCheck',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Extension point (tag `vani.health.checks`, ADR-030 §4.G): plugin tự báo tình trạng kết nối/cấu hình.
 * Chạy theo lịch (`vani:plugin:health`, 15 phút/lần) và trong `vani:plugin:doctor`, **không** chạy trong request
 * của khách. Được gọi mạng nhưng phải có timeout ngắn; lỗi/timeout → Core ghi `error` thay plugin.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 14,
    'endLine' => 19,
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
      'TAG' => 
      array (
        'declaringClassName' => 'Modules\\Extension\\Contracts\\PluginHealthCheck',
        'implementingClassName' => 'Modules\\Extension\\Contracts\\PluginHealthCheck',
        'name' => 'TAG',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'vani.health.checks\'',
          'attributes' => 
          array (
            'startLine' => 16,
            'endLine' => 16,
            'startTokenPos' => 36,
            'startFilePos' => 550,
            'endTokenPos' => 36,
            'endFilePos' => 569,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 16,
        'endLine' => 16,
        'startColumn' => 5,
        'endColumn' => 44,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'check' => 
      array (
        'name' => 'check',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Modules\\Extension\\Contracts\\Data\\HealthStatus',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 18,
        'endLine' => 18,
        'startColumn' => 5,
        'endColumn' => 42,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Extension\\Contracts',
        'declaringClassName' => 'Modules\\Extension\\Contracts\\PluginHealthCheck',
        'implementingClassName' => 'Modules\\Extension\\Contracts\\PluginHealthCheck',
        'currentClassName' => 'Modules\\Extension\\Contracts\\PluginHealthCheck',
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