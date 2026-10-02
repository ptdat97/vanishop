<?php declare(strict_types = 1);

// odsl-/Users/dat/Ecommerce/vanishop/modules/Fulfillment/Testing/SourcingStrategyContract.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Modules\Fulfillment\Testing\SourcingStrategyContract
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-6.73.0.5-8.4.25-7265506e364831a006bac80607faa2cbce99557935a65af4037a2408792fbe98',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Modules\\Fulfillment\\Testing\\SourcingStrategyContract',
        'filename' => '/Users/dat/Ecommerce/vanishop/modules/Fulfillment/Testing/SourcingStrategyContract.php',
      ),
    ),
    'namespace' => 'Modules\\Fulfillment\\Testing',
    'name' => 'Modules\\Fulfillment\\Testing\\SourcingStrategyContract',
    'shortName' => 'SourcingStrategyContract',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Contract test cho SourcingStrategy. Kiểm tra: mã ổn định; mỗi dòng đơn được phân bổ ĐÚNG số lượng đặt; chỉ dùng
 * location đang giữ hàng và không vượt số đang giữ theo (location, variant); số lượng dương; xác định.
 * (Core cũng từ chối đề xuất lệch hàng đang giữ khi tạo vận đơn.)
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 19,
    'endLine' => 70,
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
    ),
    'immediateProperties' => 
    array (
    ),
    'immediateMethods' => 
    array (
      'define' => 
      array (
        'name' => 'define',
        'parameters' => 
        array (
          'label' => 
          array (
            'name' => 'label',
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
            'startLine' => 25,
            'endLine' => 25,
            'startColumn' => 35,
            'endColumn' => 47,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'strategy' => 
          array (
            'name' => 'strategy',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'Closure',
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
            'startColumn' => 50,
            'endColumn' => 66,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'request' => 
          array (
            'name' => 'request',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 25,
                'endLine' => 25,
                'startTokenPos' => 82,
                'startFilePos' => 986,
                'endTokenPos' => 82,
                'endFilePos' => 989,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionUnionType',
              'data' => 
              array (
                'types' => 
                array (
                  0 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'Closure',
                      'isIdentifier' => false,
                    ),
                  ),
                  1 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'null',
                      'isIdentifier' => true,
                    ),
                  ),
                ),
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
            'startColumn' => 69,
            'endColumn' => 92,
            'parameterIndex' => 2,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'void',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * @param  Closure(): SourcingStrategy  $strategy
 * @param  (Closure(): SourcingRequest)|null  $request
 */',
        'startLine' => 25,
        'endLine' => 69,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Modules\\Fulfillment\\Testing',
        'declaringClassName' => 'Modules\\Fulfillment\\Testing\\SourcingStrategyContract',
        'implementingClassName' => 'Modules\\Fulfillment\\Testing\\SourcingStrategyContract',
        'currentClassName' => 'Modules\\Fulfillment\\Testing\\SourcingStrategyContract',
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