<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Cart/Contracts/CartLineOption.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Cart\Contracts\CartLineOption
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-8f2ab36bf3439c8712015e7a90f4a2f553e8aa9bd1532d6db26ade795681c5e1',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Cart\\Contracts\\CartLineOption',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Cart/Contracts/CartLineOption.php',
      ),
    ),
    'namespace' => 'Modules\\Cart\\Contracts',
    'name' => 'Modules\\Cart\\Contracts\\CartLineOption',
    'shortName' => 'CartLineOption',
    'isInterface' => true,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Extension point (tag `vani.cart.line_options`, ADR-030 W4): plugin nhận tuỳ chọn trên dòng giỏ (lời chúc gói quà,
 * chữ khắc…). Khách gửi `options.<plugin-id>.<field>` khi thêm dòng; Core gọi implementation của đúng plugin đó,
 * lưu giá trị đã chuẩn hoá trên dòng giỏ và chụp sang dòng đơn (bất biến).
 *
 * Cùng variant khác tuỳ chọn là hai dòng. Tuỳ chọn **không đổi giá** (phụ thu: Designed — cần dòng phí trong totals).
 * Không I/O mạng (chạy khi khoá giỏ).
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 15,
    'endLine' => 28,
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
        'declaringClassName' => 'Modules\\Cart\\Contracts\\CartLineOption',
        'implementingClassName' => 'Modules\\Cart\\Contracts\\CartLineOption',
        'name' => 'TAG',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '\'vani.cart.line_options\'',
          'attributes' => 
          array (
            'startLine' => 17,
            'endLine' => 17,
            'startTokenPos' => 31,
            'startFilePos' => 684,
            'endTokenPos' => 31,
            'endFilePos' => 707,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 17,
        'endLine' => 17,
        'startColumn' => 5,
        'endColumn' => 48,
      ),
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'normalize' => 
      array (
        'name' => 'normalize',
        'parameters' => 
        array (
          'variantId' => 
          array (
            'name' => 'variantId',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'int',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 27,
            'endLine' => 27,
            'startColumn' => 31,
            'endColumn' => 44,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'values' => 
          array (
            'name' => 'values',
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
            'startLine' => 27,
            'endLine' => 27,
            'startColumn' => 47,
            'endColumn' => 59,
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
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Kiểm tra + chuẩn hoá tuỳ chọn của plugin cho variant. Trả mảng rỗng = coi như không có tuỳ chọn.
 *
 * @param  array<string, mixed>  $values  dữ liệu khách gửi dưới `options.<plugin-id>`
 * @return array<string, scalar|null> giá trị lưu trên dòng (JSON được)
 *
 * @throws InvalidCartLineOption
 */',
        'startLine' => 27,
        'endLine' => 27,
        'startColumn' => 5,
        'endColumn' => 68,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Modules\\Cart\\Contracts',
        'declaringClassName' => 'Modules\\Cart\\Contracts\\CartLineOption',
        'implementingClassName' => 'Modules\\Cart\\Contracts\\CartLineOption',
        'currentClassName' => 'Modules\\Cart\\Contracts\\CartLineOption',
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