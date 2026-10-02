<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Checkout/Contracts/TaxCalculator.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Checkout\Contracts\TaxCalculator
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-f9abce8db8de9c1476b6e126c9d05816f266b8c15864653686075e7712a5760d',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Checkout\\Contracts\\TaxCalculator',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Checkout/Contracts/TaxCalculator.php',
      ),
    ),
    'namespace' => 'Modules\\Checkout\\Contracts',
    'name' => 'Modules\\Checkout\\Contracts\\TaxCalculator',
    'shortName' => 'TaxCalculator',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Extension point (tag `vani.tax.calculators`, chọn theo `vanishop.tax.calculator`). Tách thuế theo từng dòng
 * SAU khi đã trừ giảm giá phân bổ. Chọn qua `core.tax.calculator` (mặc định `vn_vat_inclusive` — plugin hệ thống `vani.tax-vn-vat`); Core giữ `none` làm dự phòng.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 13,
    'endLine' => 24,
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
        'declaringClassName' => 'Modules\\Checkout\\Contracts\\TaxCalculator',
        'implementingClassName' => 'Modules\\Checkout\\Contracts\\TaxCalculator',
        'name' => 'TAG',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'vani.tax.calculators\'',
          'attributes' => 
          array (
            'startLine' => 16,
            'endLine' => 16,
            'startTokenPos' => 38,
            'startFilePos' => 587,
            'endTokenPos' => 38,
            'endFilePos' => 608,
          ),
        ),
        'docComment' => '/** Tag extension point: plugin đóng góp qua `contribute(TaxCalculator::TAG, …)`. */',
        'attributes' => 
        array (
        ),
        'startLine' => 16,
        'endLine' => 16,
        'startColumn' => 5,
        'endColumn' => 46,
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
        'declaringClassName' => 'Modules\\Checkout\\Contracts\\TaxCalculator',
        'implementingClassName' => 'Modules\\Checkout\\Contracts\\TaxCalculator',
        'currentClassName' => 'Modules\\Checkout\\Contracts\\TaxCalculator',
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
            'startLine' => 23,
            'endLine' => 23,
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
        'docComment' => '/**
 * @return array<int, array{rate_bp: int, amount: int}> khoá dòng => thuế suất và tiền thuế (minor unit)
 */',
        'startLine' => 23,
        'endLine' => 23,
        'startColumn' => 5,
        'endColumn' => 61,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Checkout\\Contracts',
        'declaringClassName' => 'Modules\\Checkout\\Contracts\\TaxCalculator',
        'implementingClassName' => 'Modules\\Checkout\\Contracts\\TaxCalculator',
        'currentClassName' => 'Modules\\Checkout\\Contracts\\TaxCalculator',
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