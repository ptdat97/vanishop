<?php declare(strict_types = 1);

// phpinternal-PHPStan\BetterReflection\Reflection\ReflectionClass-event
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v3-6.73.0.5-dev-master@e4f5f6c-8.4.25-',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\InternalLocatedSource',
      'data' => 
      array (
        'name' => 'event',
        'filename' => 'phpstorm-stubs:event/event.stub',
        'extensionName' => 'event',
        'aliasName' => NULL,
      ),
    ),
    'namespace' => NULL,
    'name' => 'Event',
    'shortName' => 'Event',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Event.
 * Event class represents and event firing on a file descriptor being ready to read from or write to; a file descriptor becoming ready to read from or write to(edge-triggered I/O only); a timeout expiring; a signal occurring; a user-triggered event.
 * Every event is associated with EventBase . However, event will never fire until it is added (via Event::add() ). An added event remains in pending state until the registered event occurs, thus turning it to active state. To handle events user may register a callback which is called when event becomes active. If event is configured persistent , it remains pending. If it is not persistent, it stops being pending when it\'s callback runs. Event::del() method deletes event, thus making it non-pending. By means of Event::add() method it could be added again.
 *
 * @author Kazuaki MABUCHI
 * @copyright Сopyright (https://php.net/manual/cc.license.php) by the PHP Documentation Group is licensed under [CC by 3.0 or later](https://creativecommons.org/licenses/by/3.0/).
 *
 * @see https://php.net/manual/en/class.event.php
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 14,
    'endLine' => 226,
    'startColumn' => 5,
    'endColumn' => 5,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
      'ET' => 
      array (
        'declaringClassName' => 'Event',
        'implementingClassName' => 'Event',
        'name' => 'ET',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '32',
          'attributes' => 
          array (
            'startLine' => 21,
            'endLine' => 21,
            'startTokenPos' => 35,
            'startFilePos' => 1319,
            'endTokenPos' => 35,
            'endFilePos' => 1320,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 21,
        'endLine' => 21,
        'startColumn' => 9,
        'endColumn' => 29,
      ),
      'PERSIST' => 
      array (
        'declaringClassName' => 'Event',
        'implementingClassName' => 'Event',
        'name' => 'PERSIST',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '16',
          'attributes' => 
          array (
            'startLine' => 22,
            'endLine' => 22,
            'startTokenPos' => 46,
            'startFilePos' => 1354,
            'endTokenPos' => 46,
            'endFilePos' => 1355,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 22,
        'endLine' => 22,
        'startColumn' => 9,
        'endColumn' => 34,
      ),
      'READ' => 
      array (
        'declaringClassName' => 'Event',
        'implementingClassName' => 'Event',
        'name' => 'READ',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '2',
          'attributes' => 
          array (
            'startLine' => 23,
            'endLine' => 23,
            'startTokenPos' => 57,
            'startFilePos' => 1386,
            'endTokenPos' => 57,
            'endFilePos' => 1386,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 23,
        'endLine' => 23,
        'startColumn' => 9,
        'endColumn' => 30,
      ),
      'WRITE' => 
      array (
        'declaringClassName' => 'Event',
        'implementingClassName' => 'Event',
        'name' => 'WRITE',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '4',
          'attributes' => 
          array (
            'startLine' => 24,
            'endLine' => 24,
            'startTokenPos' => 68,
            'startFilePos' => 1418,
            'endTokenPos' => 68,
            'endFilePos' => 1418,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 24,
        'endLine' => 24,
        'startColumn' => 9,
        'endColumn' => 31,
      ),
      'SIGNAL' => 
      array (
        'declaringClassName' => 'Event',
        'implementingClassName' => 'Event',
        'name' => 'SIGNAL',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '8',
          'attributes' => 
          array (
            'startLine' => 25,
            'endLine' => 25,
            'startTokenPos' => 79,
            'startFilePos' => 1451,
            'endTokenPos' => 79,
            'endFilePos' => 1451,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 25,
        'endLine' => 25,
        'startColumn' => 9,
        'endColumn' => 32,
      ),
      'TIMEOUT' => 
      array (
        'declaringClassName' => 'Event',
        'implementingClassName' => 'Event',
        'name' => 'TIMEOUT',
        'modifiers' => 1,
        'type' => NULL,
        'value' => 
        array (
          'code' => '1',
          'attributes' => 
          array (
            'startLine' => 26,
            'endLine' => 26,
            'startTokenPos' => 90,
            'startFilePos' => 1485,
            'endTokenPos' => 90,
            'endFilePos' => 1485,
          ),
        ),
        'docComment' => NULL,
        'attributes' => 
        array (
        ),
        'startLine' => 26,
        'endLine' => 26,
        'startColumn' => 9,
        'endColumn' => 33,
      ),
    ),
    'immediateProperties' => 
    array (
      'pending' => 
      array (
        'declaringClassName' => 'Event',
        'implementingClassName' => 'Event',
        'name' => 'pending',
        'modifiers' => 1,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * @var bool
 */',
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Immutable',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'startLine' => 19,
        'endLine' => 20,
        'startColumn' => 9,
        'endColumn' => 24,
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
          'base' => 
          array (
            'name' => 'base',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'EventBase',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 41,
            'endLine' => 41,
            'startColumn' => 37,
            'endColumn' => 52,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'fd' => 
          array (
            'name' => 'fd',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 41,
            'endLine' => 41,
            'startColumn' => 55,
            'endColumn' => 57,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'what' => 
          array (
            'name' => 'what',
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
            'startLine' => 41,
            'endLine' => 41,
            'startColumn' => 60,
            'endColumn' => 68,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'cb' => 
          array (
            'name' => 'cb',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'callable',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 41,
            'endLine' => 41,
            'startColumn' => 71,
            'endColumn' => 82,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'arg' => 
          array (
            'name' => 'arg',
            'default' => 
            array (
              'code' => '\\null',
              'attributes' => 
              array (
                'startLine' => 41,
                'endLine' => 41,
                'startTokenPos' => 127,
                'startFilePos' => 2215,
                'endTokenPos' => 127,
                'endFilePos' => 2218,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 41,
            'endLine' => 41,
            'startColumn' => 85,
            'endColumn' => 95,
            'parameterIndex' => 4,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
          0 => 
          array (
            'name' => 'JetBrains\\PhpStorm\\Pure',
            'isRepeated' => false,
            'arguments' => 
            array (
            ),
          ),
        ),
        'docComment' => '/**
 * __construct.
 * Constructs Event object.
 *
 * @param EventBase $base The event base to associate with.
 * @param mixed     $fd stream resource, socket resource, or numeric file descriptor. For timer
 * events pass -1. For signal events pass the signal number, e.g. SIGHUP.
 * @param int       $what Event flags. See Event flags.
 * @param callable  $cb The event callback. See Event callbacks.
 * @param mixed     $arg  = null
 *
 * @see https://php.net/manual/en/event.construct.php
 */',
        'startLine' => 40,
        'endLine' => 43,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'Event',
        'implementingClassName' => 'Event',
        'currentClassName' => 'Event',
        'aliasName' => NULL,
      ),
      'add' => 
      array (
        'name' => 'add',
        'parameters' => 
        array (
          'timeout' => 
          array (
            'name' => 'timeout',
            'default' => 
            array (
              'code' => '-1',
              'attributes' => 
              array (
                'startLine' => 54,
                'endLine' => 54,
                'startTokenPos' => 148,
                'startFilePos' => 2560,
                'endTokenPos' => 149,
                'endFilePos' => 2561,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'float',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 54,
            'endLine' => 54,
            'startColumn' => 29,
            'endColumn' => 47,
            'parameterIndex' => 0,
            'isOptional' => true,
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
 * add.
 * Makes event pending.
 *
 * @param float $timeout (optional)
 *
 * @return bool Returns true on success or false on failure.
 *
 * @see https://php.net/manual/en/event.add.php
 */',
        'startLine' => 54,
        'endLine' => 56,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'Event',
        'implementingClassName' => 'Event',
        'currentClassName' => 'Event',
        'aliasName' => NULL,
      ),
      'addSignal' => 
      array (
        'name' => 'addSignal',
        'parameters' => 
        array (
          'timeout' => 
          array (
            'name' => 'timeout',
            'default' => 
            array (
              'code' => '-1',
              'attributes' => 
              array (
                'startLine' => 67,
                'endLine' => 67,
                'startTokenPos' => 173,
                'startFilePos' => 2889,
                'endTokenPos' => 174,
                'endFilePos' => 2890,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'float',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 67,
            'endLine' => 67,
            'startColumn' => 35,
            'endColumn' => 53,
            'parameterIndex' => 0,
            'isOptional' => true,
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
 * addSignal.
 * Makes signal event pending.
 *
 * @param float $timeout (optional)
 *
 * @return bool
 *
 * @see https://php.net/manual/en/event.addsignal.php
 */',
        'startLine' => 67,
        'endLine' => 69,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'Event',
        'implementingClassName' => 'Event',
        'currentClassName' => 'Event',
        'aliasName' => NULL,
      ),
      'addTimer' => 
      array (
        'name' => 'addTimer',
        'parameters' => 
        array (
          'timeout' => 
          array (
            'name' => 'timeout',
            'default' => 
            array (
              'code' => '-1',
              'attributes' => 
              array (
                'startLine' => 80,
                'endLine' => 80,
                'startTokenPos' => 198,
                'startFilePos' => 3214,
                'endTokenPos' => 199,
                'endFilePos' => 3215,
              ),
            ),
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'float',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 80,
            'endLine' => 80,
            'startColumn' => 34,
            'endColumn' => 52,
            'parameterIndex' => 0,
            'isOptional' => true,
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
 * addTimer.
 * Makes timer event pending.
 *
 * @param float $timeout (optional)
 *
 * @return bool
 *
 * @see https://php.net/manual/en/event.addtimer.php
 */',
        'startLine' => 80,
        'endLine' => 82,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'Event',
        'implementingClassName' => 'Event',
        'currentClassName' => 'Event',
        'aliasName' => NULL,
      ),
      'del' => 
      array (
        'name' => 'del',
        'parameters' => 
        array (
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
 * del.
 * Makes event non-pending.
 *
 * @return bool Returns true on success or false on failure.
 *
 * @see https://php.net/manual/en/event.del.php
 */',
        'startLine' => 91,
        'endLine' => 93,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'Event',
        'implementingClassName' => 'Event',
        'currentClassName' => 'Event',
        'aliasName' => NULL,
      ),
      'delSignal' => 
      array (
        'name' => 'delSignal',
        'parameters' => 
        array (
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
 * delSignal.
 * Makes signal event non-pending.
 *
 * @return bool
 *
 * @see https://php.net/manual/en/event.delsignal.php
 */',
        'startLine' => 102,
        'endLine' => 104,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'Event',
        'implementingClassName' => 'Event',
        'currentClassName' => 'Event',
        'aliasName' => NULL,
      ),
      'delTimer' => 
      array (
        'name' => 'delTimer',
        'parameters' => 
        array (
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
 * delTimer.
 * Makes timer event non-pending.
 *
 * @return bool
 *
 * @see https://php.net/manual/en/event.deltimer.php
 */',
        'startLine' => 113,
        'endLine' => 115,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'Event',
        'implementingClassName' => 'Event',
        'currentClassName' => 'Event',
        'aliasName' => NULL,
      ),
      'free' => 
      array (
        'name' => 'free',
        'parameters' => 
        array (
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
 * free.
 * Make event non-pending and free resources allocated for this event.
 *
 * @see https://php.net/manual/en/event.free.php
 */',
        'startLine' => 122,
        'endLine' => 124,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'Event',
        'implementingClassName' => 'Event',
        'currentClassName' => 'Event',
        'aliasName' => NULL,
      ),
      'getSupportedMethods' => 
      array (
        'name' => 'getSupportedMethods',
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
 * getSupportedMethods.
 * Returns array with of the names of the methods supported in this version of Libevent.
 *
 * @return array Returns array.
 *
 * @see https://php.net/manual/en/event.getsupportedmethods.php
 */',
        'startLine' => 133,
        'endLine' => 135,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => NULL,
        'declaringClassName' => 'Event',
        'implementingClassName' => 'Event',
        'currentClassName' => 'Event',
        'aliasName' => NULL,
      ),
      'pending' => 
      array (
        'name' => 'pending',
        'parameters' => 
        array (
          'flags' => 
          array (
            'name' => 'flags',
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
            'startLine' => 147,
            'endLine' => 147,
            'startColumn' => 33,
            'endColumn' => 42,
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
 * pending.
 * Detects whether event is pending or scheduled.
 *
 * @param int $flags One of, or a composition of the following constants: Event::READ,
 * Event::WRITE, Event::TIMEOUT, Event::SIGNAL.
 *
 * @return bool Returns true if event is pending or scheduled. Otherwise false.
 *
 * @see https://php.net/manual/en/event.pending.php
 */',
        'startLine' => 147,
        'endLine' => 149,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'Event',
        'implementingClassName' => 'Event',
        'currentClassName' => 'Event',
        'aliasName' => NULL,
      ),
      'set' => 
      array (
        'name' => 'set',
        'parameters' => 
        array (
          'base' => 
          array (
            'name' => 'base',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'EventBase',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 165,
            'endLine' => 165,
            'startColumn' => 29,
            'endColumn' => 44,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'fd' => 
          array (
            'name' => 'fd',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 165,
            'endLine' => 165,
            'startColumn' => 47,
            'endColumn' => 49,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'what' => 
          array (
            'name' => 'what',
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
            'startLine' => 165,
            'endLine' => 165,
            'startColumn' => 52,
            'endColumn' => 60,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'cb' => 
          array (
            'name' => 'cb',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'callable',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 165,
            'endLine' => 165,
            'startColumn' => 63,
            'endColumn' => 74,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'arg' => 
          array (
            'name' => 'arg',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 165,
            'endLine' => 165,
            'startColumn' => 77,
            'endColumn' => 80,
            'parameterIndex' => 4,
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
 * set.
 * Re-configures event.
 *
 * @param EventBase $base The event base to associate the event with.
 * @param mixed     $fd Stream resource, socket resource, or numeric file descriptor. For timer
 * events pass -1. For signal events pass the signal number, e.g. SIGHUP.
 * @param int       $what (optional)
 * @param callable  $cb   (optional)
 * @param mixed     $arg  (optional)
 *
 * @return bool Returns true on success or false on failure.
 *
 * @see https://php.net/manual/en/event.set.php
 */',
        'startLine' => 165,
        'endLine' => 167,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'Event',
        'implementingClassName' => 'Event',
        'currentClassName' => 'Event',
        'aliasName' => NULL,
      ),
      'setPriority' => 
      array (
        'name' => 'setPriority',
        'parameters' => 
        array (
          'priority' => 
          array (
            'name' => 'priority',
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
            'startLine' => 177,
            'endLine' => 177,
            'startColumn' => 37,
            'endColumn' => 49,
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
 * setPriority.
 * Set event priority.
 *
 * @param int $priority The event priority.
 * @return bool Returns true on success or false on failure.
 *
 * @see https://php.net/manual/en/event.setpriority.php
 */',
        'startLine' => 177,
        'endLine' => 179,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'Event',
        'implementingClassName' => 'Event',
        'currentClassName' => 'Event',
        'aliasName' => NULL,
      ),
      'setTimer' => 
      array (
        'name' => 'setTimer',
        'parameters' => 
        array (
          'base' => 
          array (
            'name' => 'base',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'EventBase',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 192,
            'endLine' => 192,
            'startColumn' => 34,
            'endColumn' => 49,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'cb' => 
          array (
            'name' => 'cb',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'callable',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 192,
            'endLine' => 192,
            'startColumn' => 52,
            'endColumn' => 63,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'arg' => 
          array (
            'name' => 'arg',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 192,
            'endLine' => 192,
            'startColumn' => 66,
            'endColumn' => 69,
            'parameterIndex' => 2,
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
 * setTimer.
 * Re-configures timer event.
 *
 * @param EventBase $base The event base to associate with.
 * @param callable  $cb The timer event callback. See Event callbacks.
 * @param mixed     $arg  (optional)
 *
 * @return bool Returns true on success or false on failure.
 *
 * @see https://php.net/manual/en/event.settimer.php
 */',
        'startLine' => 192,
        'endLine' => 194,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => NULL,
        'declaringClassName' => 'Event',
        'implementingClassName' => 'Event',
        'currentClassName' => 'Event',
        'aliasName' => NULL,
      ),
      'signal' => 
      array (
        'name' => 'signal',
        'parameters' => 
        array (
          'base' => 
          array (
            'name' => 'base',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'EventBase',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 208,
            'endLine' => 208,
            'startColumn' => 39,
            'endColumn' => 54,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'signum' => 
          array (
            'name' => 'signum',
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
            'startLine' => 208,
            'endLine' => 208,
            'startColumn' => 57,
            'endColumn' => 67,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'cb' => 
          array (
            'name' => 'cb',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'callable',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 208,
            'endLine' => 208,
            'startColumn' => 70,
            'endColumn' => 81,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'arg' => 
          array (
            'name' => 'arg',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 208,
            'endLine' => 208,
            'startColumn' => 84,
            'endColumn' => 87,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Event',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * signal.
 * Constructs signal event object.
 *
 * @param EventBase $base The associated event base object.
 * @param int       $signum The signal number.
 * @param callable  $cb The signal event callback. See Event callbacks.
 * @param mixed     $arg    (optional)
 *
 * @return Event Returns Event object on success. Otherwise false.
 *
 * @see https://php.net/manual/en/event.signal.php
 */',
        'startLine' => 208,
        'endLine' => 210,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => NULL,
        'declaringClassName' => 'Event',
        'implementingClassName' => 'Event',
        'currentClassName' => 'Event',
        'aliasName' => NULL,
      ),
      'timer' => 
      array (
        'name' => 'timer',
        'parameters' => 
        array (
          'base' => 
          array (
            'name' => 'base',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'EventBase',
                'isIdentifier' => false,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 223,
            'endLine' => 223,
            'startColumn' => 38,
            'endColumn' => 53,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'cb' => 
          array (
            'name' => 'cb',
            'default' => NULL,
            'type' => 
            array (
              'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
              'data' => 
              array (
                'name' => 'callable',
                'isIdentifier' => true,
              ),
            ),
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 223,
            'endLine' => 223,
            'startColumn' => 56,
            'endColumn' => 67,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'arg' => 
          array (
            'name' => 'arg',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 223,
            'endLine' => 223,
            'startColumn' => 70,
            'endColumn' => 73,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => 
        array (
          'class' => 'PHPStan\\BetterReflection\\Reflection\\ReflectionNamedType',
          'data' => 
          array (
            'name' => 'Event',
            'isIdentifier' => false,
          ),
        ),
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * timer.
 * Constructs timer event object.
 *
 * @param EventBase $base The associated event base object.
 * @param callable  $cb The signal event callback. See Event callbacks.
 * @param mixed     $arg  (optional)
 *
 * @return Event Returns Event object on success. Otherwise false.
 *
 * @see https://php.net/manual/en/event.timer.php
 */',
        'startLine' => 223,
        'endLine' => 225,
        'startColumn' => 9,
        'endColumn' => 9,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => NULL,
        'declaringClassName' => 'Event',
        'implementingClassName' => 'Event',
        'currentClassName' => 'Event',
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