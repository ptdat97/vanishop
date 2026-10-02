<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Integration/Domain/PayloadMasker.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Integration\Domain\PayloadMasker
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-257a90d0dd325b773e1deb8a012d75ecb26d11b66d28c5b74ce5b0dcaa3caf8b',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Integration\\Domain\\PayloadMasker',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Integration/Domain/PayloadMasker.php',
      ),
    ),
    'namespace' => 'Modules\\Integration\\Domain',
    'name' => 'Modules\\Integration\\Domain\\PayloadMasker',
    'shortName' => 'PayloadMasker',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Che PII trước khi hiển thị payload trên màn hình vận hành (Integration Health).
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 10,
    'endLine' => 40,
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
      'SENSITIVE' => 
      array (
        'declaringClassName' => 'Modules\\Integration\\Domain\\PayloadMasker',
        'implementingClassName' => 'Modules\\Integration\\Domain\\PayloadMasker',
        'name' => 'SENSITIVE',
        'modifiers' => 4,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[\'phone\', \'email\', \'full_name\', \'street_line\', \'address\', \'name_on_card\', \'tax_code\', \'id_number\']',
          'attributes' => 
          array (
            'startLine' => 12,
            'endLine' => 12,
            'startTokenPos' => 33,
            'startFilePos' => 234,
            'endTokenPos' => 56,
            'endFilePos' => 331,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 12,
        'endLine' => 12,
        'startColumn' => 5,
        'endColumn' => 129,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'mask' => 
      array (
        'name' => 'mask',
        'parameters' => 
        array (
          'payload' => 
          array (
            'name' => 'payload',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'array',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 18,
            'endLine' => 18,
            'startColumn' => 33,
            'endColumn' => 46,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
 * @param  array<array-key, mixed>  $payload
 * @return array<array-key, mixed>
 */',
        'startLine' => 18,
        'endLine' => 29,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Modules\\Integration\\Domain',
        'declaringClassName' => 'Modules\\Integration\\Domain\\PayloadMasker',
        'implementingClassName' => 'Modules\\Integration\\Domain\\PayloadMasker',
        'currentClassName' => 'Modules\\Integration\\Domain\\PayloadMasker',
        'aliasName' => NULL,
      ),
      'maskValue' => 
      array (
        'name' => 'maskValue',
        'parameters' => 
        array (
          'value' => 
          array (
            'name' => 'value',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'string',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 31,
            'endLine' => 31,
            'startColumn' => 39,
            'endColumn' => 51,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
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
        'startLine' => 31,
        'endLine' => 39,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 20,
        'namespace' => 'Modules\\Integration\\Domain',
        'declaringClassName' => 'Modules\\Integration\\Domain\\PayloadMasker',
        'implementingClassName' => 'Modules\\Integration\\Domain\\PayloadMasker',
        'currentClassName' => 'Modules\\Integration\\Domain\\PayloadMasker',
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