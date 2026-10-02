<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Shared/Domain/BusinessRuleViolation.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Shared\Domain\BusinessRuleViolation
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-ac36b3e9aa443021172323c87233b8156cf2fed44972d3058f546173d1862445',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Shared\\Domain\\BusinessRuleViolation',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Shared/Domain/BusinessRuleViolation.php',
      ),
    ),
    'namespace' => 'Modules\\Shared\\Domain',
    'name' => 'Modules\\Shared\\Domain\\BusinessRuleViolation',
    'shortName' => 'BusinessRuleViolation',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 64,
    'docComment' => '/**
 * Lỗi nghiệp vụ công khai: có mã ổn định (vd. "cart.insufficient_stock") để client xử lý.
 * API trả {"error": {"code", "message", "details"}} với HTTP status của lỗi (mặc định 409).
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 13,
    'endLine' => 31,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => 'RuntimeException',
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
    ),
    'immediateMethods' => 
    array (
      'errorCode' => 
      array (
        'name' => 'errorCode',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 15,
        'endLine' => 15,
        'startColumn' => 5,
        'endColumn' => 49,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 65,
        'namespace' => 'Modules\\Shared\\Domain',
        'declaringClassName' => 'Modules\\Shared\\Domain\\BusinessRuleViolation',
        'implementingClassName' => 'Modules\\Shared\\Domain\\BusinessRuleViolation',
        'currentClassName' => 'Modules\\Shared\\Domain\\BusinessRuleViolation',
        'aliasName' => NULL,
      ),
      'status' => 
      array (
        'name' => 'status',
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
        'startLine' => 17,
        'endLine' => 20,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Shared\\Domain',
        'declaringClassName' => 'Modules\\Shared\\Domain\\BusinessRuleViolation',
        'implementingClassName' => 'Modules\\Shared\\Domain\\BusinessRuleViolation',
        'currentClassName' => 'Modules\\Shared\\Domain\\BusinessRuleViolation',
        'aliasName' => NULL,
      ),
      'details' => 
      array (
        'name' => 'details',
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
        'docComment' => '/**
 * Chi tiết an toàn để trả cho client (không lộ dữ liệu nội bộ như số tồn chính xác).
 *
 * @return array<string, mixed>
 */',
        'startLine' => 27,
        'endLine' => 30,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Shared\\Domain',
        'declaringClassName' => 'Modules\\Shared\\Domain\\BusinessRuleViolation',
        'implementingClassName' => 'Modules\\Shared\\Domain\\BusinessRuleViolation',
        'currentClassName' => 'Modules\\Shared\\Domain\\BusinessRuleViolation',
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