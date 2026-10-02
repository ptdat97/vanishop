<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/custom/plugin/TaxVnVat/Infrastructure/VnVatInclusiveTax.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Plugin\TaxVnVat\Infrastructure\VnVatInclusiveTax
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-efc5410f65d56ec705c5d1b1772af3621ee2c6adf15b7a045f397aa8c00e87ea',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Plugin\\TaxVnVat\\Infrastructure\\VnVatInclusiveTax',
        'filename' => '/Users/dat/Ecommerce/vanishop/custom/plugin/TaxVnVat/Infrastructure/VnVatInclusiveTax.php',
      ),
    ),
    'namespace' => 'Plugin\\TaxVnVat\\Infrastructure',
    'name' => 'Plugin\\TaxVnVat\\Infrastructure\\VnVatInclusiveTax',
    'shortName' => 'VnVatInclusiveTax',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * VAT đã gồm trong giá bán (bán lẻ VN): thuế dòng = round_half_up(total × rate / (10000 + rate)).
 * Mã `vn_vat_inclusive` giữ nguyên từ khi còn nằm trong Core (cấu hình `core.tax.calculator` cũ vẫn đúng).
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 16,
    'endLine' => 36,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'Modules\\Checkout\\Contracts\\TaxCalculator',
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
      'settings' => 
      array (
        'declaringClassName' => 'Plugin\\TaxVnVat\\Infrastructure\\VnVatInclusiveTax',
        'implementingClassName' => 'Plugin\\TaxVnVat\\Infrastructure\\VnVatInclusiveTax',
        'name' => 'settings',
        'modifiers' => 132,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Modules\\Tenancy\\Contracts\\Settings',
            'isIdentifier' => false,
          ),
        ),
        'default' => NULL,
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 18,
        'endLine' => 18,
        'startColumn' => 33,
        'endColumn' => 67,
        'isPromoted' => true,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
    ),
    'immediateMethods' => 
    array (
      '__construct' => 
      array (
        'name' => '__construct',
        'parameters' => 
        array (
          'settings' => 
          array (
            'name' => 'settings',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Modules\\Tenancy\\Contracts\\Settings',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => true,
            'attributes' => 
            array (
            ),
            'startLine' => 18,
            'endLine' => 18,
            'startColumn' => 33,
            'endColumn' => 67,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 18,
        'endLine' => 18,
        'startColumn' => 5,
        'endColumn' => 71,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Plugin\\TaxVnVat\\Infrastructure',
        'declaringClassName' => 'Plugin\\TaxVnVat\\Infrastructure\\VnVatInclusiveTax',
        'implementingClassName' => 'Plugin\\TaxVnVat\\Infrastructure\\VnVatInclusiveTax',
        'currentClassName' => 'Plugin\\TaxVnVat\\Infrastructure\\VnVatInclusiveTax',
        'aliasName' => NULL,
      ),
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
        'startLine' => 20,
        'endLine' => 23,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Plugin\\TaxVnVat\\Infrastructure',
        'declaringClassName' => 'Plugin\\TaxVnVat\\Infrastructure\\VnVatInclusiveTax',
        'implementingClassName' => 'Plugin\\TaxVnVat\\Infrastructure\\VnVatInclusiveTax',
        'currentClassName' => 'Plugin\\TaxVnVat\\Infrastructure\\VnVatInclusiveTax',
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
            'startLine' => 25,
            'endLine' => 25,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 25,
        'endLine' => 35,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Plugin\\TaxVnVat\\Infrastructure',
        'declaringClassName' => 'Plugin\\TaxVnVat\\Infrastructure\\VnVatInclusiveTax',
        'implementingClassName' => 'Plugin\\TaxVnVat\\Infrastructure\\VnVatInclusiveTax',
        'currentClassName' => 'Plugin\\TaxVnVat\\Infrastructure\\VnVatInclusiveTax',
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