<?php declare(strict_types = 1);

// phpinternal-PHPStan\BetterReflection\Reflection\ReflectionFunction-virtual
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v3-6.73.0.5-dev-master@e4f5f6c-8.4.25-',
   'data' => 
  array (
    'name' => 'virtual',
    'parameters' => 
    array (
      'filename' => 
      array (
        'name' => 'filename',
        'default' => NULL,
        'type' => NULL,
        'isVariadic' => false,
        'byRef' => false,
        'isPromoted' => false,
        'attributes' => 
        array (
        ),
        'startLine' => 15,
        'endLine' => 15,
        'startColumn' => 22,
        'endColumn' => 30,
        'parameterIndex' => 0,
        'isOptional' => false,
      ),
    ),
    'returnsReference' => false,
    'returnType' => NULL,
    'attributes' => 
    array (
    ),
    'docComment' => '/**
 * Perform an Apache sub-request
 * virtual() is an Apache-specific function which is similar to <!--#include virtual...--> in mod_include. It performs an Apache sub-request. It is useful for including CGI scripts or .shtml files, or anything else that you would parse through Apache. Note that for a CGI script, the script must generate valid CGI headers. At the minimum that means it must generate a Content-Type header.
 * To run the sub-request, all buffers are terminated and flushed to the browser, pending headers are sent too.
 * This function is supported when PHP is installed as an Apache module or by the NSAPI server module in Netscape/iPlanet/SunONE webservers.
 * @link https://php.net/manual/en/function.virtual.php
 * @param string $filename <p>
 * The file that the virtual command will be performed on.
 * </p>
 * @return bool Performs the virtual command on success, or returns FALSE on failure.
 */',
    'startLine' => 15,
    'endLine' => 17,
    'startColumn' => 5,
    'endColumn' => 5,
    'couldThrow' => false,
    'isClosure' => false,
    'isGenerator' => false,
    'isVariadic' => false,
    'isStatic' => false,
    'namespace' => NULL,
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\InternalLocatedSource',
      'data' => 
      array (
        'name' => 'virtual',
        'filename' => 'phpstorm-stubs:apache/apache.stub',
        'extensionName' => 'apache',
        'aliasName' => NULL,
      ),
    ),
  ),
));