<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Checkout/Contracts/TotalsCalculator.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Checkout\Contracts\TotalsCalculator
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-ec5ab776751ee1d2a4b92e6469ea1fc2af7d05b2edd59dfa8537cd39545cfdc7',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Checkout\\Contracts\\TotalsCalculator',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Checkout/Contracts/TotalsCalculator.php',
      ),
    ),
    'namespace' => 'Modules\\Checkout\\Contracts',
    'name' => 'Modules\\Checkout\\Contracts\\TotalsCalculator',
    'shortName' => 'TotalsCalculator',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Extension point (tag `vani.totals.calculators`). Priority: subtotal 100, promotion 200, plugin 300–399,
 * shipping 500, tax 800, guard 900. Chỉ tính, không ghi DB, không I/O mạng.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 13,
    'endLine' => 23,
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
        'declaringClassName' => 'Modules\\Checkout\\Contracts\\TotalsCalculator',
        'implementingClassName' => 'Modules\\Checkout\\Contracts\\TotalsCalculator',
        'name' => 'TAG',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'vani.totals.calculators\'',
          'attributes' => 
          array (
            'startLine' => 16,
            'endLine' => 16,
            'startTokenPos' => 38,
            'startFilePos' => 474,
            'endTokenPos' => 38,
            'endFilePos' => 498,
          ),
        ),
        'docComment' => '/** Tag extension point: plugin đóng góp qua `contribute(TotalsCalculator::TAG, …)`. */',
        'attributes' => 
        array (
        ),
        'startLine' => 16,
        'endLine' => 16,
        'startColumn' => 5,
        'endColumn' => 49,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'code' => 
      array (
        'name' => 'code',
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
        'startLine' => 18,
        'endLine' => 18,
        'startColumn' => 5,
        'endColumn' => 35,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Checkout\\Contracts',
        'declaringClassName' => 'Modules\\Checkout\\Contracts\\TotalsCalculator',
        'implementingClassName' => 'Modules\\Checkout\\Contracts\\TotalsCalculator',
        'currentClassName' => 'Modules\\Checkout\\Contracts\\TotalsCalculator',
        'aliasName' => NULL,
      ),
      'priority' => 
      array (
        'name' => 'priority',
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
        'startLine' => 20,
        'endLine' => 20,
        'startColumn' => 5,
        'endColumn' => 36,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Checkout\\Contracts',
        'declaringClassName' => 'Modules\\Checkout\\Contracts\\TotalsCalculator',
        'implementingClassName' => 'Modules\\Checkout\\Contracts\\TotalsCalculator',
        'currentClassName' => 'Modules\\Checkout\\Contracts\\TotalsCalculator',
        'aliasName' => NULL,
      ),
      'calculate' => 
      array (
        'name' => 'calculate',
        'parameters' => 
        array (
          'context' => 
          array (
            'name' => 'context',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Checkout\\Contracts\\Data\\TotalsContext',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 22,
            'endLine' => 22,
            'startColumn' => 31,
            'endColumn' => 52,
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
            'name' => 'Modules\\Checkout\\Contracts\\Data\\TotalsContext',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 22,
        'endLine' => 22,
        'startColumn' => 5,
        'endColumn' => 69,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Checkout\\Contracts',
        'declaringClassName' => 'Modules\\Checkout\\Contracts\\TotalsCalculator',
        'implementingClassName' => 'Modules\\Checkout\\Contracts\\TotalsCalculator',
        'currentClassName' => 'Modules\\Checkout\\Contracts\\TotalsCalculator',
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