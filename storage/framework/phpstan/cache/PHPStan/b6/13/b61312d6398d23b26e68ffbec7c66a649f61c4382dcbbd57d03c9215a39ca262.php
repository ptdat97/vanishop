<?php declare(strict_types = 1);

// osfsl-/Users/dat/Ecommerce/vanishop/vendor/composer/../symfony/http-foundation/IpUtils.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Symfony\Component\HttpFoundation\IpUtils
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-ad97192d425d45204456f8f9702a5d799179de52a3e2703a41e4b5d6ed52be42-8.4.25-6.73.0.5',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Symfony\\Component\\HttpFoundation\\IpUtils',
        'filename' => '/Users/dat/Ecommerce/vanishop/vendor/composer/../symfony/http-foundation/IpUtils.php',
      ),
    ),
    'namespace' => 'Symfony\\Component\\HttpFoundation',
    'name' => 'Symfony\\Component\\HttpFoundation\\IpUtils',
    'shortName' => 'IpUtils',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => '/**
 * Http utility functions.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 19,
    'endLine' => 288,
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
      'PRIVATE_SUBNETS' => 
      array (
        'declaringClassName' => 'Symfony\\Component\\HttpFoundation\\IpUtils',
        'implementingClassName' => 'Symfony\\Component\\HttpFoundation\\IpUtils',
        'name' => 'PRIVATE_SUBNETS',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '[
    \'127.0.0.0/8\',
    // RFC1700 (Loopback)
    \'10.0.0.0/8\',
    // RFC1918
    \'192.168.0.0/16\',
    // RFC1918
    \'192.0.2.0/24\',
    // Documentation Ranges TEST-NET-1 (RFC 5737)
    \'198.51.100.0/24\',
    // Documentation Ranges TEST-NET-2 (RFC 5737)
    \'203.0.113.0/24\',
    // Documentation Ranges TEST-NET-3 (RFC 5737)
    \'172.16.0.0/12\',
    // RFC1918
    \'169.254.0.0/16\',
    // RFC3927
    \'198.18.0.0/15\',
    // IPv4 Benchmarking (RFC 2544)
    \'0.0.0.0/8\',
    // RFC5735
    \'240.0.0.0/4\',
    // RFC1112
    \'100.64.0.0/10\',
    // RFC6598
    \'::1/128\',
    // Loopback
    \'fc00::/7\',
    // Unique Local Address
    \'fe80::/10\',
    // Link Local Address
    \'::ffff:0:0/96\',
    // IPv4-mapped IPv6 addresses (RFC 4291 section 2.5.5.2)
    \'::/128\',
    // Unspecified address
    \'::/96\',
    // IPv4-compatible IPv6 addresses (RFC 4291 section 2.5.5.1)
    \'2002::/16\',
    // 6to4 (RFC 3056)
    \'2001::/32\',
    // Teredo tunneling (RFC 4380)
    \'2001:db8::/32\',
    // Documentation Ranges (RFC 3849)
    \'2001:0002::/48\',
    // IPv6 Benchmarking (RFC 5180 and corrections)
    \'64:ff9b::/96\',
    // NAT64 well-known prefix (RFC 6052)
    \'64:ff9b:1::/48\',
]',
          'attributes' => 
          array (
            'startLine' => 21,
            'endLine' => 46,
            'startTokenPos' => 25,
            'startFilePos' => 421,
            'endTokenPos' => 147,
            'endFilePos' => 1751,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 21,
        'endLine' => 46,
        'startColumn' => 5,
        'endColumn' => 6,
      ),
    ),
    'immediateProperties' => 
    array (
      'checkedIps' => 
      array (
        'declaringClassName' => 'Symfony\\Component\\HttpFoundation\\IpUtils',
        'implementingClassName' => 'Symfony\\Component\\HttpFoundation\\IpUtils',
        'name' => 'checkedIps',
        'modifiers' => 20,
        'type' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'array',
            'isIdentifier' => true,
          ),
        ),
        'default' => 
        array (
          'code' => '[]',
          'attributes' => 
          array (
            'startLine' => 48,
            'endLine' => 48,
            'startTokenPos' => 160,
            'startFilePos' => 1794,
            'endTokenPos' => 161,
            'endFilePos' => 1795,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 48,
        'endLine' => 48,
        'startColumn' => 5,
        'endColumn' => 42,
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
      '__construct' => 
      array (
        'name' => '__construct',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * This class should not be instantiated.
 */',
        'startLine' => 53,
        'endLine' => 55,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 4,
        'namespace' => 'Symfony\\Component\\HttpFoundation',
        'declaringClassName' => 'Symfony\\Component\\HttpFoundation\\IpUtils',
        'implementingClassName' => 'Symfony\\Component\\HttpFoundation\\IpUtils',
        'currentClassName' => 'Symfony\\Component\\HttpFoundation\\IpUtils',
        'aliasName' => NULL,
      ),
      'checkIp' => 
      array (
        'name' => 'checkIp',
        'parameters' => 
        array (
          'requestIp' => 
          array (
            'name' => 'requestIp',
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
            'startLine' => 62,
            'endLine' => 62,
            'startColumn' => 36,
            'endColumn' => 52,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'ips' => 
          array (
            'name' => 'ips',
            'default' => NULL,
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
                      'name' => 'string',
                      'isIdentifier' => true,
                    ),
                  ),
                  1 => 
                  array (
                    'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
                    'data' => 
                    array (
                      'name' => 'array',
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
            'startLine' => 62,
            'endLine' => 62,
            'startColumn' => 55,
            'endColumn' => 71,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Checks if an IPv4 or IPv6 address is contained in the list of given IPs or subnets.
 *
 * @param string|array $ips List of IPs or subnets (can be a string if only a single one)
 */',
        'startLine' => 62,
        'endLine' => 77,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Symfony\\Component\\HttpFoundation',
        'declaringClassName' => 'Symfony\\Component\\HttpFoundation\\IpUtils',
        'implementingClassName' => 'Symfony\\Component\\HttpFoundation\\IpUtils',
        'currentClassName' => 'Symfony\\Component\\HttpFoundation\\IpUtils',
        'aliasName' => NULL,
      ),
      'checkIp4' => 
      array (
        'name' => 'checkIp4',
        'parameters' => 
        array (
          'requestIp' => 
          array (
            'name' => 'requestIp',
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
            'startLine' => 87,
            'endLine' => 87,
            'startColumn' => 37,
            'endColumn' => 53,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'ip' => 
          array (
            'name' => 'ip',
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
            'startLine' => 87,
            'endLine' => 87,
            'startColumn' => 56,
            'endColumn' => 65,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Compares two IPv4 addresses.
 * In case a subnet is given, it checks if it contains the request IP.
 *
 * @param string $ip IPv4 address or subnet in CIDR notation
 *
 * @return bool Whether the request IP matches the IP, or whether the request IP is within the CIDR subnet
 */',
        'startLine' => 87,
        'endLine' => 118,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Symfony\\Component\\HttpFoundation',
        'declaringClassName' => 'Symfony\\Component\\HttpFoundation\\IpUtils',
        'implementingClassName' => 'Symfony\\Component\\HttpFoundation\\IpUtils',
        'currentClassName' => 'Symfony\\Component\\HttpFoundation\\IpUtils',
        'aliasName' => NULL,
      ),
      'checkIp6' => 
      array (
        'name' => 'checkIp6',
        'parameters' => 
        array (
          'requestIp' => 
          array (
            'name' => 'requestIp',
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
            'startLine' => 132,
            'endLine' => 132,
            'startColumn' => 37,
            'endColumn' => 53,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'ip' => 
          array (
            'name' => 'ip',
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
            'startLine' => 132,
            'endLine' => 132,
            'startColumn' => 56,
            'endColumn' => 65,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Compares two IPv6 addresses.
 * In case a subnet is given, it checks if it contains the request IP.
 *
 * @author David Soria Parra <dsp at php dot net>
 *
 * @see https://github.com/dsp/v6tools
 *
 * @param string $ip IPv6 address or subnet in CIDR notation
 *
 * @throws \\RuntimeException When IPV6 support is not enabled
 */',
        'startLine' => 132,
        'endLine' => 188,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Symfony\\Component\\HttpFoundation',
        'declaringClassName' => 'Symfony\\Component\\HttpFoundation\\IpUtils',
        'implementingClassName' => 'Symfony\\Component\\HttpFoundation\\IpUtils',
        'currentClassName' => 'Symfony\\Component\\HttpFoundation\\IpUtils',
        'aliasName' => NULL,
      ),
      'anonymize' => 
      array (
        'name' => 'anonymize',
        'parameters' => 
        array (
          'ip' => 
          array (
            'name' => 'ip',
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
            'startLine' => 198,
            'endLine' => 198,
            'startColumn' => 38,
            'endColumn' => 47,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'v4Bytes' => 
          array (
            'name' => 'v4Bytes',
            'default' => 
            array (
              'code' => '1',
              'attributes' => 
              array (
                'startLine' => 198,
                'endLine' => 198,
                'startTokenPos' => 1183,
                'startFilePos' => 6764,
                'endTokenPos' => 1183,
                'endFilePos' => 6764,
              ),
            ),
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
            'startLine' => 198,
            'endLine' => 198,
            'startColumn' => 50,
            'endColumn' => 65,
            'parameterIndex' => 1,
            'isOptional' => true,
          ),
          'v6Bytes' => 
          array (
            'name' => 'v6Bytes',
            'default' => 
            array (
              'code' => '8',
              'attributes' => 
              array (
                'startLine' => 198,
                'endLine' => 198,
                'startTokenPos' => 1192,
                'startFilePos' => 6782,
                'endTokenPos' => 1192,
                'endFilePos' => 6782,
              ),
            ),
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
            'startLine' => 198,
            'endLine' => 198,
            'startColumn' => 68,
            'endColumn' => 83,
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
            'name' => 'string',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Anonymizes an IP/IPv6.
 *
 * Removes the last bytes of IPv4 and IPv6 addresses (1 byte for IPv4 and 8 bytes for IPv6 by default).
 *
 * @param int<0, 4>  $v4Bytes
 * @param int<0, 16> $v6Bytes
 */',
        'startLine' => 198,
        'endLine' => 249,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Symfony\\Component\\HttpFoundation',
        'declaringClassName' => 'Symfony\\Component\\HttpFoundation\\IpUtils',
        'implementingClassName' => 'Symfony\\Component\\HttpFoundation\\IpUtils',
        'currentClassName' => 'Symfony\\Component\\HttpFoundation\\IpUtils',
        'aliasName' => NULL,
      ),
      'isPrivateIp' => 
      array (
        'name' => 'isPrivateIp',
        'parameters' => 
        array (
          'requestIp' => 
          array (
            'name' => 'requestIp',
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
            'startLine' => 256,
            'endLine' => 256,
            'startColumn' => 40,
            'endColumn' => 56,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Checks if an IPv4 or IPv6 address is contained in the list of private IP subnets.
 *
 * @throws \\ValueError When $requestIp is not a valid IP address
 */',
        'startLine' => 256,
        'endLine' => 263,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Symfony\\Component\\HttpFoundation',
        'declaringClassName' => 'Symfony\\Component\\HttpFoundation\\IpUtils',
        'implementingClassName' => 'Symfony\\Component\\HttpFoundation\\IpUtils',
        'currentClassName' => 'Symfony\\Component\\HttpFoundation\\IpUtils',
        'aliasName' => NULL,
      ),
      'getCacheResult' => 
      array (
        'name' => 'getCacheResult',
        'parameters' => 
        array (
          'cacheKey' => 
          array (
            'name' => 'cacheKey',
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
            'startLine' => 265,
            'endLine' => 265,
            'startColumn' => 44,
            'endColumn' => 59,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
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
                  'name' => 'bool',
                  'isIdentifier' => true,
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
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 265,
        'endLine' => 277,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 20,
        'namespace' => 'Symfony\\Component\\HttpFoundation',
        'declaringClassName' => 'Symfony\\Component\\HttpFoundation\\IpUtils',
        'implementingClassName' => 'Symfony\\Component\\HttpFoundation\\IpUtils',
        'currentClassName' => 'Symfony\\Component\\HttpFoundation\\IpUtils',
        'aliasName' => NULL,
      ),
      'setCacheResult' => 
      array (
        'name' => 'setCacheResult',
        'parameters' => 
        array (
          'cacheKey' => 
          array (
            'name' => 'cacheKey',
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
            'startLine' => 279,
            'endLine' => 279,
            'startColumn' => 44,
            'endColumn' => 59,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'result' => 
          array (
            'name' => 'result',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'bool',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 279,
            'endLine' => 279,
            'startColumn' => 62,
            'endColumn' => 73,
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
            'name' => 'bool',
            'isIdentifier' => true,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => NULL,
        'startLine' => 279,
        'endLine' => 287,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 20,
        'namespace' => 'Symfony\\Component\\HttpFoundation',
        'declaringClassName' => 'Symfony\\Component\\HttpFoundation\\IpUtils',
        'implementingClassName' => 'Symfony\\Component\\HttpFoundation\\IpUtils',
        'currentClassName' => 'Symfony\\Component\\HttpFoundation\\IpUtils',
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