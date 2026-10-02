<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Integration/Console/ClientCommand.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Integration\Console\ClientCommand
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-45fbf3a5e6d7fbbb89b2a783030f3183a3dd8567f61cecb586d3874348f4dd52',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Integration\\Console\\ClientCommand',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Integration/Console/ClientCommand.php',
      ),
    ),
    'namespace' => 'Modules\\Integration\\Console',
    'name' => 'Modules\\Integration\\Console\\ClientCommand',
    'shortName' => 'ClientCommand',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Tạo/cập nhật Integration Client và cấp key. Secret chỉ hiện MỘT lần.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 15,
    'endLine' => 56,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'Illuminate\\Console\\Command',
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
      'signature' => 
      array (
        'declaringClassName' => 'Modules\\Integration\\Console\\ClientCommand',
        'implementingClassName' => 'Modules\\Integration\\Console\\ClientCommand',
        'name' => 'signature',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'vani:integration:client
        {code : Mã client (vd. erp-main) — cũng là giá trị stock_authority của location do client quản lý}
        {--name= : Tên hiển thị}
        {--scope=* : Scope (orders:read, orders:write, inventory:write, events:read)}
        {--ip=* : IP/CIDR được phép (bỏ trống = không giới hạn)}
        {--rate-limit=600 : Request/phút}
        {--suspend : Tạm dừng client}
        {--new-key : Cấp thêm key (tối đa 2 key còn hiệu lực — thu hồi key cũ bằng --revoke)}
        {--revoke= : key_id cần thu hồi}\'',
          'attributes' => 
          array (
            'startLine' => 17,
            'endLine' => 25,
            'startTokenPos' => 55,
            'startFilePos' => 414,
            'endTokenPos' => 55,
            'endFilePos' => 1003,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 17,
        'endLine' => 25,
        'startColumn' => 5,
        'endColumn' => 46,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'description' => 
      array (
        'declaringClassName' => 'Modules\\Integration\\Console\\ClientCommand',
        'implementingClassName' => 'Modules\\Integration\\Console\\ClientCommand',
        'name' => 'description',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '\'Quản lý Integration Client (ERP/ODO/POS) và key HMAC.\'',
          'attributes' => 
          array (
            'startLine' => 27,
            'endLine' => 27,
            'startTokenPos' => 64,
            'startFilePos' => 1036,
            'endTokenPos' => 64,
            'endFilePos' => 1094,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 27,
        'endLine' => 27,
        'startColumn' => 5,
        'endColumn' => 89,
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
      'handle' => 
      array (
        'name' => 'handle',
        'parameters' => 
        array (
          'provisioning' => 
          array (
            'name' => 'provisioning',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Integration\\Application\\ClientProvisioning',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 29,
            'endLine' => 29,
            'startColumn' => 28,
            'endColumn' => 59,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'context' => 
          array (
            'name' => 'context',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Shared\\Context\\CurrentContext',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 29,
            'endLine' => 29,
            'startColumn' => 62,
            'endColumn' => 84,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
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
        'startLine' => 29,
        'endLine' => 55,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Integration\\Console',
        'declaringClassName' => 'Modules\\Integration\\Console\\ClientCommand',
        'implementingClassName' => 'Modules\\Integration\\Console\\ClientCommand',
        'currentClassName' => 'Modules\\Integration\\Console\\ClientCommand',
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