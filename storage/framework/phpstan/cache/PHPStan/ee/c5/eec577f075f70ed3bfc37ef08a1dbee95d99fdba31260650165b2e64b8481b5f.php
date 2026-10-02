<?php declare(strict_types = 1);

// osfsl-/Users/dat/Ecommerce/vanishop/vendor/composer/../laravel/framework/src/Illuminate/Validation/Rules/File.php-PHPStan\BetterReflection\Reflection\ReflectionClass-Illuminate\Validation\Rules\File
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-80c390e99433da0b4ac54a0f1d0f9955651b8ed0bffd8d0c03b1162f1d5e8bf7-8.4.25-6.73.0.5',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'Illuminate\\Validation\\Rules\\File',
        'filename' => '/Users/dat/Ecommerce/vanishop/vendor/composer/../laravel/framework/src/Illuminate/Validation/Rules/File.php',
      ),
    ),
    'namespace' => 'Illuminate\\Validation\\Rules',
    'name' => 'Illuminate\\Validation\\Rules\\File',
    'shortName' => 'File',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 0,
    'docComment' => NULL,
    'attributes' => 
    array (
    ),
    'startLine' => 15,
    'endLine' => 403,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
      0 => 'Illuminate\\Contracts\\Validation\\Rule',
      1 => 'Illuminate\\Contracts\\Validation\\DataAwareRule',
      2 => 'Illuminate\\Contracts\\Validation\\ValidatorAwareRule',
    ),
    'traitClassNames' => 
    array (
      0 => 'Illuminate\\Support\\Traits\\Conditionable',
      1 => 'Illuminate\\Support\\Traits\\Macroable',
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
      'allowedMimetypes' => 
      array (
        'declaringClassName' => 'Illuminate\\Validation\\Rules\\File',
        'implementingClassName' => 'Illuminate\\Validation\\Rules\\File',
        'name' => 'allowedMimetypes',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[]',
          'attributes' => 
          array (
            'startLine' => 24,
            'endLine' => 24,
            'startTokenPos' => 84,
            'startFilePos' => 686,
            'endTokenPos' => 85,
            'endFilePos' => 687,
          ),
        ),
        'docComment' => '/**
 * The MIME types that the given file should match. This array may also contain file extensions.
 *
 * @var array
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 24,
        'endLine' => 24,
        'startColumn' => 5,
        'endColumn' => 37,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'allowedExtensions' => 
      array (
        'declaringClassName' => 'Illuminate\\Validation\\Rules\\File',
        'implementingClassName' => 'Illuminate\\Validation\\Rules\\File',
        'name' => 'allowedExtensions',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[]',
          'attributes' => 
          array (
            'startLine' => 31,
            'endLine' => 31,
            'startTokenPos' => 96,
            'startFilePos' => 823,
            'endTokenPos' => 97,
            'endFilePos' => 824,
          ),
        ),
        'docComment' => '/**
 * The extensions that the given file should match.
 *
 * @var array
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 31,
        'endLine' => 31,
        'startColumn' => 5,
        'endColumn' => 38,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'minimumFileSize' => 
      array (
        'declaringClassName' => 'Illuminate\\Validation\\Rules\\File',
        'implementingClassName' => 'Illuminate\\Validation\\Rules\\File',
        'name' => 'minimumFileSize',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => 'null',
          'attributes' => 
          array (
            'startLine' => 38,
            'endLine' => 38,
            'startTokenPos' => 108,
            'startFilePos' => 964,
            'endTokenPos' => 108,
            'endFilePos' => 967,
          ),
        ),
        'docComment' => '/**
 * The minimum size in kilobytes that the file can be.
 *
 * @var null|int
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 38,
        'endLine' => 38,
        'startColumn' => 5,
        'endColumn' => 38,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'maximumFileSize' => 
      array (
        'declaringClassName' => 'Illuminate\\Validation\\Rules\\File',
        'implementingClassName' => 'Illuminate\\Validation\\Rules\\File',
        'name' => 'maximumFileSize',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => 'null',
          'attributes' => 
          array (
            'startLine' => 45,
            'endLine' => 45,
            'startTokenPos' => 119,
            'startFilePos' => 1107,
            'endTokenPos' => 119,
            'endFilePos' => 1110,
          ),
        ),
        'docComment' => '/**
 * The maximum size in kilobytes that the file can be.
 *
 * @var null|int
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 45,
        'endLine' => 45,
        'startColumn' => 5,
        'endColumn' => 38,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'encoding' => 
      array (
        'declaringClassName' => 'Illuminate\\Validation\\Rules\\File',
        'implementingClassName' => 'Illuminate\\Validation\\Rules\\File',
        'name' => 'encoding',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => 'null',
          'attributes' => 
          array (
            'startLine' => 52,
            'endLine' => 52,
            'startTokenPos' => 130,
            'startFilePos' => 1222,
            'endTokenPos' => 130,
            'endFilePos' => 1225,
          ),
        ),
        'docComment' => '/**
 * The required file encoding.
 *
 * @var string|null
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 52,
        'endLine' => 52,
        'startColumn' => 5,
        'endColumn' => 31,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'customRules' => 
      array (
        'declaringClassName' => 'Illuminate\\Validation\\Rules\\File',
        'implementingClassName' => 'Illuminate\\Validation\\Rules\\File',
        'name' => 'customRules',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[]',
          'attributes' => 
          array (
            'startLine' => 59,
            'endLine' => 59,
            'startTokenPos' => 141,
            'startFilePos' => 1378,
            'endTokenPos' => 142,
            'endFilePos' => 1379,
          ),
        ),
        'docComment' => '/**
 * An array of custom rules that will be merged into the validation rules.
 *
 * @var array
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 59,
        'endLine' => 59,
        'startColumn' => 5,
        'endColumn' => 32,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'messages' => 
      array (
        'declaringClassName' => 'Illuminate\\Validation\\Rules\\File',
        'implementingClassName' => 'Illuminate\\Validation\\Rules\\File',
        'name' => 'messages',
        'modifiers' => 2,
        'type' => NULL,
        'default' => 
        array (
          'code' => '[]',
          'attributes' => 
          array (
            'startLine' => 66,
            'endLine' => 66,
            'startTokenPos' => 153,
            'startFilePos' => 1501,
            'endTokenPos' => 154,
            'endFilePos' => 1502,
          ),
        ),
        'docComment' => '/**
 * The error message after validation, if any.
 *
 * @var array
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 66,
        'endLine' => 66,
        'startColumn' => 5,
        'endColumn' => 29,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'data' => 
      array (
        'declaringClassName' => 'Illuminate\\Validation\\Rules\\File',
        'implementingClassName' => 'Illuminate\\Validation\\Rules\\File',
        'name' => 'data',
        'modifiers' => 2,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The data under validation.
 *
 * @var array
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 73,
        'endLine' => 73,
        'startColumn' => 5,
        'endColumn' => 20,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'validator' => 
      array (
        'declaringClassName' => 'Illuminate\\Validation\\Rules\\File',
        'implementingClassName' => 'Illuminate\\Validation\\Rules\\File',
        'name' => 'validator',
        'modifiers' => 2,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The validator performing the validation.
 *
 * @var \\Illuminate\\Validation\\Validator
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 80,
        'endLine' => 80,
        'startColumn' => 5,
        'endColumn' => 25,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'defaultCallback' => 
      array (
        'declaringClassName' => 'Illuminate\\Validation\\Rules\\File',
        'implementingClassName' => 'Illuminate\\Validation\\Rules\\File',
        'name' => 'defaultCallback',
        'modifiers' => 17,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The callback that will generate the "default" version of the file rule.
 *
 * @var string|array|callable|null
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 87,
        'endLine' => 87,
        'startColumn' => 5,
        'endColumn' => 35,
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
      'defaults' => 
      array (
        'name' => 'defaults',
        'parameters' => 
        array (
          'callback' => 
          array (
            'name' => 'callback',
            'default' => 
            array (
              'code' => 'null',
              'attributes' => 
              array (
                'startLine' => 99,
                'endLine' => 99,
                'startTokenPos' => 194,
                'startFilePos' => 2320,
                'endTokenPos' => 194,
                'endFilePos' => 2323,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 99,
            'endLine' => 99,
            'startColumn' => 37,
            'endColumn' => 52,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Set the default callback to be used for determining the file default rules.
 *
 * If no arguments are passed, the default file rule configuration will be returned.
 *
 * @param  static|callable|null  $callback
 * @return ($callback is null ? static : void)
 *
 * @throws \\InvalidArgumentException
 */',
        'startLine' => 99,
        'endLine' => 110,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Illuminate\\Validation\\Rules',
        'declaringClassName' => 'Illuminate\\Validation\\Rules\\File',
        'implementingClassName' => 'Illuminate\\Validation\\Rules\\File',
        'currentClassName' => 'Illuminate\\Validation\\Rules\\File',
        'aliasName' => NULL,
      ),
      'default' => 
      array (
        'name' => 'default',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the default configuration of the file rule.
 *
 * @return static
 */',
        'startLine' => 117,
        'endLine' => 124,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Illuminate\\Validation\\Rules',
        'declaringClassName' => 'Illuminate\\Validation\\Rules\\File',
        'implementingClassName' => 'Illuminate\\Validation\\Rules\\File',
        'currentClassName' => 'Illuminate\\Validation\\Rules\\File',
        'aliasName' => NULL,
      ),
      'image' => 
      array (
        'name' => 'image',
        'parameters' => 
        array (
          'allowSvg' => 
          array (
            'name' => 'allowSvg',
            'default' => 
            array (
              'code' => 'false',
              'attributes' => 
              array (
                'startLine' => 132,
                'endLine' => 132,
                'startTokenPos' => 349,
                'startFilePos' => 3209,
                'endTokenPos' => 349,
                'endFilePos' => 3213,
              ),
            ),
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 132,
            'endLine' => 132,
            'startColumn' => 34,
            'endColumn' => 50,
            'parameterIndex' => 0,
            'isOptional' => true,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Limit the uploaded file to only image types.
 *
 * @param  bool  $allowSvg
 * @return ImageFile
 */',
        'startLine' => 132,
        'endLine' => 135,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Illuminate\\Validation\\Rules',
        'declaringClassName' => 'Illuminate\\Validation\\Rules\\File',
        'implementingClassName' => 'Illuminate\\Validation\\Rules\\File',
        'currentClassName' => 'Illuminate\\Validation\\Rules\\File',
        'aliasName' => NULL,
      ),
      'types' => 
      array (
        'name' => 'types',
        'parameters' => 
        array (
          'mimetypes' => 
          array (
            'name' => 'mimetypes',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 143,
            'endLine' => 143,
            'startColumn' => 34,
            'endColumn' => 43,
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
 * Limit the uploaded file to the given MIME types or file extensions.
 *
 * @param  string|array<int, string>  $mimetypes
 * @return static
 */',
        'startLine' => 143,
        'endLine' => 146,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 17,
        'namespace' => 'Illuminate\\Validation\\Rules',
        'declaringClassName' => 'Illuminate\\Validation\\Rules\\File',
        'implementingClassName' => 'Illuminate\\Validation\\Rules\\File',
        'currentClassName' => 'Illuminate\\Validation\\Rules\\File',
        'aliasName' => NULL,
      ),
      'extensions' => 
      array (
        'name' => 'extensions',
        'parameters' => 
        array (
          'extensions' => 
          array (
            'name' => 'extensions',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 154,
            'endLine' => 154,
            'startColumn' => 32,
            'endColumn' => 42,
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
 * Limit the uploaded file to the given file extensions.
 *
 * @param  string|array<int, string>  $extensions
 * @return $this
 */',
        'startLine' => 154,
        'endLine' => 159,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Validation\\Rules',
        'declaringClassName' => 'Illuminate\\Validation\\Rules\\File',
        'implementingClassName' => 'Illuminate\\Validation\\Rules\\File',
        'currentClassName' => 'Illuminate\\Validation\\Rules\\File',
        'aliasName' => NULL,
      ),
      'size' => 
      array (
        'name' => 'size',
        'parameters' => 
        array (
          'size' => 
          array (
            'name' => 'size',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 167,
            'endLine' => 167,
            'startColumn' => 26,
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
 * Indicate that the uploaded file should be exactly a certain size in kilobytes.
 *
 * @param  string|int  $size
 * @return $this
 */',
        'startLine' => 167,
        'endLine' => 173,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Validation\\Rules',
        'declaringClassName' => 'Illuminate\\Validation\\Rules\\File',
        'implementingClassName' => 'Illuminate\\Validation\\Rules\\File',
        'currentClassName' => 'Illuminate\\Validation\\Rules\\File',
        'aliasName' => NULL,
      ),
      'between' => 
      array (
        'name' => 'between',
        'parameters' => 
        array (
          'minSize' => 
          array (
            'name' => 'minSize',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 182,
            'endLine' => 182,
            'startColumn' => 29,
            'endColumn' => 36,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'maxSize' => 
          array (
            'name' => 'maxSize',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 182,
            'endLine' => 182,
            'startColumn' => 39,
            'endColumn' => 46,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Indicate that the uploaded file should be between a minimum and maximum size in kilobytes.
 *
 * @param  string|int  $minSize
 * @param  string|int  $maxSize
 * @return $this
 */',
        'startLine' => 182,
        'endLine' => 188,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Validation\\Rules',
        'declaringClassName' => 'Illuminate\\Validation\\Rules\\File',
        'implementingClassName' => 'Illuminate\\Validation\\Rules\\File',
        'currentClassName' => 'Illuminate\\Validation\\Rules\\File',
        'aliasName' => NULL,
      ),
      'min' => 
      array (
        'name' => 'min',
        'parameters' => 
        array (
          'size' => 
          array (
            'name' => 'size',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 196,
            'endLine' => 196,
            'startColumn' => 25,
            'endColumn' => 29,
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
 * Indicate that the uploaded file should be no less than the given number of kilobytes.
 *
 * @param  string|int  $size
 * @return $this
 */',
        'startLine' => 196,
        'endLine' => 201,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Validation\\Rules',
        'declaringClassName' => 'Illuminate\\Validation\\Rules\\File',
        'implementingClassName' => 'Illuminate\\Validation\\Rules\\File',
        'currentClassName' => 'Illuminate\\Validation\\Rules\\File',
        'aliasName' => NULL,
      ),
      'max' => 
      array (
        'name' => 'max',
        'parameters' => 
        array (
          'size' => 
          array (
            'name' => 'size',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 209,
            'endLine' => 209,
            'startColumn' => 25,
            'endColumn' => 29,
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
 * Indicate that the uploaded file should be no more than the given number of kilobytes.
 *
 * @param  string|int  $size
 * @return $this
 */',
        'startLine' => 209,
        'endLine' => 214,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Validation\\Rules',
        'declaringClassName' => 'Illuminate\\Validation\\Rules\\File',
        'implementingClassName' => 'Illuminate\\Validation\\Rules\\File',
        'currentClassName' => 'Illuminate\\Validation\\Rules\\File',
        'aliasName' => NULL,
      ),
      'encoding' => 
      array (
        'name' => 'encoding',
        'parameters' => 
        array (
          'encoding' => 
          array (
            'name' => 'encoding',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 222,
            'endLine' => 222,
            'startColumn' => 30,
            'endColumn' => 38,
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
 * Indicate that the uploaded file should be in the given encoding.
 *
 * @param  string  $encoding
 * @return $this
 */',
        'startLine' => 222,
        'endLine' => 227,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Validation\\Rules',
        'declaringClassName' => 'Illuminate\\Validation\\Rules\\File',
        'implementingClassName' => 'Illuminate\\Validation\\Rules\\File',
        'currentClassName' => 'Illuminate\\Validation\\Rules\\File',
        'aliasName' => NULL,
      ),
      'toKilobytes' => 
      array (
        'name' => 'toKilobytes',
        'parameters' => 
        array (
          'size' => 
          array (
            'name' => 'size',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 237,
            'endLine' => 237,
            'startColumn' => 36,
            'endColumn' => 40,
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
 * Convert a potentially human-friendly file size to kilobytes.
 *
 * @param  string|int  $size
 * @return ($size is int ? int : int|float)
 *
 * @throws \\InvalidArgumentException
 */',
        'startLine' => 237,
        'endLine' => 254,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Validation\\Rules',
        'declaringClassName' => 'Illuminate\\Validation\\Rules\\File',
        'implementingClassName' => 'Illuminate\\Validation\\Rules\\File',
        'currentClassName' => 'Illuminate\\Validation\\Rules\\File',
        'aliasName' => NULL,
      ),
      'rules' => 
      array (
        'name' => 'rules',
        'parameters' => 
        array (
          'rules' => 
          array (
            'name' => 'rules',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 262,
            'endLine' => 262,
            'startColumn' => 27,
            'endColumn' => 32,
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
 * Specify additional validation rules that should be merged with the default rules during validation.
 *
 * @param  string|array  $rules
 * @return $this
 */',
        'startLine' => 262,
        'endLine' => 267,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Validation\\Rules',
        'declaringClassName' => 'Illuminate\\Validation\\Rules\\File',
        'implementingClassName' => 'Illuminate\\Validation\\Rules\\File',
        'currentClassName' => 'Illuminate\\Validation\\Rules\\File',
        'aliasName' => NULL,
      ),
      'passes' => 
      array (
        'name' => 'passes',
        'parameters' => 
        array (
          'attribute' => 
          array (
            'name' => 'attribute',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 276,
            'endLine' => 276,
            'startColumn' => 28,
            'endColumn' => 37,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'value' => 
          array (
            'name' => 'value',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 276,
            'endLine' => 276,
            'startColumn' => 40,
            'endColumn' => 45,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Determine if the validation rule passes.
 *
 * @param  string  $attribute
 * @param  mixed  $value
 * @return bool
 */',
        'startLine' => 276,
        'endLine' => 292,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Validation\\Rules',
        'declaringClassName' => 'Illuminate\\Validation\\Rules\\File',
        'implementingClassName' => 'Illuminate\\Validation\\Rules\\File',
        'currentClassName' => 'Illuminate\\Validation\\Rules\\File',
        'aliasName' => NULL,
      ),
      'buildValidationRules' => 
      array (
        'name' => 'buildValidationRules',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Build the array of underlying validation rules based on the current state.
 *
 * @return array
 */',
        'startLine' => 299,
        'endLine' => 322,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Validation\\Rules',
        'declaringClassName' => 'Illuminate\\Validation\\Rules\\File',
        'implementingClassName' => 'Illuminate\\Validation\\Rules\\File',
        'currentClassName' => 'Illuminate\\Validation\\Rules\\File',
        'aliasName' => NULL,
      ),
      'buildMimetypes' => 
      array (
        'name' => 'buildMimetypes',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Separate the given MIME types from extensions and return an array of correct rules to validate against.
 *
 * @return array
 */',
        'startLine' => 329,
        'endLine' => 353,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Validation\\Rules',
        'declaringClassName' => 'Illuminate\\Validation\\Rules\\File',
        'implementingClassName' => 'Illuminate\\Validation\\Rules\\File',
        'currentClassName' => 'Illuminate\\Validation\\Rules\\File',
        'aliasName' => NULL,
      ),
      'fail' => 
      array (
        'name' => 'fail',
        'parameters' => 
        array (
          'messages' => 
          array (
            'name' => 'messages',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 361,
            'endLine' => 361,
            'startColumn' => 29,
            'endColumn' => 37,
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
 * Adds the given failures, and return false.
 *
 * @param  array|string  $messages
 * @return bool
 */',
        'startLine' => 361,
        'endLine' => 366,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 2,
        'namespace' => 'Illuminate\\Validation\\Rules',
        'declaringClassName' => 'Illuminate\\Validation\\Rules\\File',
        'implementingClassName' => 'Illuminate\\Validation\\Rules\\File',
        'currentClassName' => 'Illuminate\\Validation\\Rules\\File',
        'aliasName' => NULL,
      ),
      'message' => 
      array (
        'name' => 'message',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the validation error message.
 *
 * @return array
 */',
        'startLine' => 373,
        'endLine' => 376,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Validation\\Rules',
        'declaringClassName' => 'Illuminate\\Validation\\Rules\\File',
        'implementingClassName' => 'Illuminate\\Validation\\Rules\\File',
        'currentClassName' => 'Illuminate\\Validation\\Rules\\File',
        'aliasName' => NULL,
      ),
      'setValidator' => 
      array (
        'name' => 'setValidator',
        'parameters' => 
        array (
          'validator' => 
          array (
            'name' => 'validator',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 384,
            'endLine' => 384,
            'startColumn' => 34,
            'endColumn' => 43,
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
 * Set the current validator.
 *
 * @param  \\Illuminate\\Contracts\\Validation\\Validator  $validator
 * @return $this
 */',
        'startLine' => 384,
        'endLine' => 389,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Validation\\Rules',
        'declaringClassName' => 'Illuminate\\Validation\\Rules\\File',
        'implementingClassName' => 'Illuminate\\Validation\\Rules\\File',
        'currentClassName' => 'Illuminate\\Validation\\Rules\\File',
        'aliasName' => NULL,
      ),
      'setData' => 
      array (
        'name' => 'setData',
        'parameters' => 
        array (
          'data' => 
          array (
            'name' => 'data',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 397,
            'endLine' => 397,
            'startColumn' => 29,
            'endColumn' => 33,
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
 * Set the current data under validation.
 *
 * @param  array  $data
 * @return $this
 */',
        'startLine' => 397,
        'endLine' => 402,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'Illuminate\\Validation\\Rules',
        'declaringClassName' => 'Illuminate\\Validation\\Rules\\File',
        'implementingClassName' => 'Illuminate\\Validation\\Rules\\File',
        'currentClassName' => 'Illuminate\\Validation\\Rules\\File',
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