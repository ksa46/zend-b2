<?php
/*
 * This file is part of PHPLOC.
 *
 * (c) Sebastian Bergmann <sebastian@phpunit.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SebastianBergmann\PHPLOC
{
    // @codeCoverageIgnoreStart
    if (!defined('T_TRAIT')) {
        define('T_TRAIT', 1000);
    }
    // @codeCoverageIgnoreEnd

    /**
     * PHPLOC code analyser.
     *
     * @author    Sebastian Bergmann <sebastian@phpunit.de>
     * @copyright Sebastian Bergmann <sebastian@phpunit.de>
     * @license   http://www.opensource.org/licenses/BSD-3-Clause  The BSD 3-Clause License
     * @link      http://github.com/sebastianbergmann/phploc/tree
     * @since     Class available since Release 1.0.0
     */
    class Analyser
    {
        /**
         * @var array
         */
        private $namespaces = array();

        /**
         * @var array
         */
        private $classes = array();

        /**
         * @var array
         */
        private $constants = array();

        /**
         * @var array
         */
        private $possibleConstantAccesses = array();

        /**
         * @var array
         */
        private $count = array(
          'files'                       => 0,
          'loc'                         => 0,
          'lloc'                        => 0,
          'llocClasses'                 => 0,
          'llocFunctions'               => 0,
          'llocGlobal'                  => 0,
          'cloc'                        => 0,
          'ccn'                         => 0,
          'ccnMethods'                  => 0,
          'interfaces'                  => 0,
          'traits'                      => 0,
          'classes'                     => 0,
          'abstractClasses'             => 0,
          'concreteClasses'             => 0,
          'functions'                   => 0,
          'namedFunctions'              => 0,
          'anonymousFunctions'          => 0,
          'methods'                     => 0,
          'publicMethods'               => 0,
          'nonPublicMethods'            => 0,
          'nonStaticMethods'            => 0,
          'staticMethods'               => 0,
          'constants'                   => 0,
          'classConstants'              => 0,
          'globalConstants'             => 0,
          'testClasses'                 => 0,
          'testMethods'                 => 0,
          'ccnByLloc'                   => 0,
          'llocByNof'                   => 0,
          'methodCalls'                 => 0,
          'staticMethodCalls'           => 0,
          'instanceMethodCalls'         => 0,
          'attributeAccesses'           => 0,
          'staticAttributeAccesses'     => 0,
          'instanceAttributeAccesses'   => 0,
          'globalAccesses'              => 0,
          'globalVariableAccesses'      => 0,
          'superGlobalVariableAccesses' => 0,
          'globalConstantAccesses'      => 0,
        );

        /**
         * @var array
         */
        private $superGlobals = array(
          '$_ENV' => TRUE,
          '$_POST' => TRUE,
          '$_GET' => TRUE,
          '$_COOKIE' => TRUE,
          '$_SERVER' => TRUE,
          '$_FILES' => TRUE,
          '$_REQUEST' => TRUE,
          '$HTTP_ENV_VARS' => TRUE,
          '$HTTP_POST_VARS' => TRUE,
          '$HTTP_GET_VARS' => TRUE,
          '$HTTP_COOKIE_VARS' => TRUE,
          '$HTTP_SERVER_VARS' => TRUE,
          '$HTTP_POST_FILES' => TRUE
        );

        /**
         * @var array
         */
        private $classCcn = array();

        /**
         * @var array
         */
        private $classLloc = array();

        /**
         * @var array
         */
        private $methodCcn = array();

        /**
         * @var array
         */
        private $methodLloc = array();

        /**
         * Processes a set of files.
         *
         * @param  array   $files
         * @param  boolean $countTests
         * @return array
         * @since  Method available since Release 1.2.0
         */
        public function countFiles(array $files, $countTests)
        {
            if ($countTests) {
                foreach ($files as $file) {
                    $this->preProcessFile($file);
                }
            }

            $directories = array();

            foreach ($files as $file) {
                $directory = dirname($file);

                if (!isset($directories[$directory])) {
                    $directories[$directory] = TRUE;
                }

                $this->countFile($file, $countTests);
            }

            $count = $this->count;

            $count['directories']       = count($directories) - 1;
            $count['namespaces']        = count($this->namespaces);
            $count['classes']           = $count['abstractClasses'] +
                                          $count['concreteClasses'];
            $count['functions']         = $count['namedFunctions'] +
                                          $count['anonymousFunctions'];
            $count['constants']         = $count['classConstants'] +
                                          $count['globalConstants'];
            $count['attributeAccesses'] = $count['staticAttributeAccesses'] +
                                          $count['instanceAttributeAccesses'];
            $count['methodCalls']       = $count['staticMethodCalls'] +
                                          $count['instanceMethodCalls'];
            $count['llocGlobal']        = $count['lloc'] -
                                          $count['llocClasses'] -
                                          $count['llocFunctions'];
            $count['ncloc']             = $count['loc'] - $count['cloc'];

            foreach ($this->possibleConstantAccesses as $possibleConstantAccess) {
                if (in_array($possibleConstantAccess, $this->constants)) {
                    $count['globalConstantAccesses']++;
                }
            }

            $count['globalAccesses'] = $count['globalConstantAccesses'] +
                                       $count['globalVariableAccesses'] +
                                       $count['superGlobalVariableAccesses'];

            if ($count['lloc'] > 0) {
                $count['ccnByLloc'] = $count['ccn'] / $count['lloc'];
            }

            if (count($this->classCcn) > 0) {
                $count['classCcnMin'] = min($this->classCcn);
                $count['classCcnAvg'] = array_sum($this->classCcn) / count($this->classCcn);
                $count['classCcnMax'] = max($this->classCcn);
            }

            if (count($this->methodCcn) > 0) {
                $count['methodCcnMin'] = min($this->methodCcn);
                $count['methodCcnAvg'] = array_sum($this->methodCcn) / count($this->methodCcn);
                $count['methodCcnMax'] = max($this->methodCcn);
            }

            if (count($this->classLloc) > 0) {
                $count['classLlocMin'] = min($this->classLloc);
                $count['classLlocAvg'] = array_sum($this->classLloc) / count($this->classLloc);
                $count['classLlocMax'] = max($this->classLloc);
            }

            if (count($this->methodLloc) > 0) {
                $count['methodLlocMin'] = min($this->methodLloc);
                $count['methodLlocAvg'] = array_sum($this->methodLloc) / count($this->methodLloc);
                $count['methodLlocMax'] = max($this->methodLloc);
            }

            if ($count['functions'] > 0) {
                $count['llocByNof'] = $count['llocFunctions'] / $count['functions'];
            }

            return $count;
        }

        /**
         * Pre-processes a single file.
         *
         * @param string $filename
         * @since Method available since Release 1.2.0
         */
        public function preProcessFile($filename)
        {
            $tokens    = token_get_all(file_get_contents($filename));
            $numTokens = count($tokens);
            $namespace = FALSE;

            for ($i = 0; $i < $numTokens; $i++) {
                if (is_string($tokens[$i])) {
                    continue;
                }

                switch ($tokens[$i][0]) {
                    case T_NAMESPACE: {
                        $namespace = $this->getNamespaceName($tokens, $i);
                    }
                    break;

                    case T_CLASS: {
                        if (!$this->isClassDeclaration($tokens, $i)) {
                            continue;
                        }

                        $className = $this->getClassName($namespace, $tokens, $i);

                        if (isset($tokens[$i+4]) && is_array($tokens[$i+4]) &&
                            $tokens[$i+4][0] == T_EXTENDS) {
                            $parent = $this->getClassName(
                              $namespace, $tokens, $i + 4
                            );
                        } else {
                            $parent = NULL;
                        }

                        $this->classes[$className] = $parent;
                    }
                    break;
                }
            }
        }

        /**
         * Processes a single file.
         *
         * @param string  $filename
         * @param boolean $countTests
         */
        public function countFile($filename, $countTests)
        {
            $buffer              = file_get_contents($filename);
            $this->count['loc'] += substr_count($buffer, "\n");
            $tokens              = token_get_all($buffer);
            $numTokens           = count($tokens);

            unset($buffer);

            $this->count['files']++;

            $blocks            = array();
            $currentBlock      = FALSE;
            $namespace         = FALSE;
            $className         = NULL;
            $functionName      = NULL;
            $testClass         = FALSE;
            $currentClassData  = NULL;
            $currentMethodData = NULL;

            for ($i = 0; $i < $numTokens; $i++) {
                if (is_string($tokens[$i])) {
                    $token = trim($tokens[$i]);

                    if ($token == ';') {
                        if ($className !== NULL && !$testClass) {
                            $this->count['llocClasses']++;
                            $currentClassData['lloc']++;

                            if ($functionName !== NULL) {
                                $currentMethodData['lloc']++;
                            }
                        }

                        else if ($functionName !== NULL) {
                            $this->count['llocFunctions']++;
                        }

                        $this->count['lloc']++;
                    }

                    else if ($token == '?' && !$testClass) {
                        if ($className !== NULL) {
                            $this->count['ccnMethods']++;
                            $currentClassData['ccn']++;
                            $currentMethodData['ccn']++;
                        }

                        $this->count['ccn']++;
                    }

                    else if ($token == '{') {
                        if ($currentBlock == T_CLASS) {
                            $block = $className;
                        }

                        else if ($currentBlock == T_FUNCTION) {
                            $block = $functionName;
                        }

                        else {
                            $block = FALSE;
                        }

                        array_push($blocks, $block);

                        $currentBlock = FALSE;
                    }

                    else if ($token == '}') {
                        $block = array_pop($blocks);

                        if ($block !== FALSE && $block !== NULL) {
                            if ($block == $functionName) {
                                $functionName = NULL;

                                if ($currentMethodData !== NULL) {
                                    $this->methodCcn[] = $currentMethodData['ccn'];
                                    $this->methodLloc[] = $currentMethodData['lloc'];
                                    $currentMethodData = NULL;
                                }
                            }

                            else if ($block == $className) {
                                $className         = NULL;
                                $testClass         = FALSE;
                                $this->classCcn[]  = $currentClassData['ccn'];
                                $this->classLloc[] = $currentClassData['lloc'];
                                $currentClassData  = NULL;
                            }
                        }
                    }

                    continue;
                }

                list ($token, $value) = $tokens[$i];

                switch ($token) {
                    case T_NAMESPACE: {
                        $namespace = $this->getNamespaceName($tokens, $i);

                        if (!isset($this->namespaces[$namespace])) {
                            $this->namespaces[$namespace] = TRUE;
                        }
                    }
                    break;

                    case T_CLASS:
                    case T_INTERFACE:
                    case T_TRAIT: {
                        if (!$this->isClassDeclaration($tokens, $i)) {
                            continue;
                        }

                        $currentClassData = array('ccn' => 1, 'lloc' => 0);
                        $className        = $this->getClassName(
                                                $namespace, $tokens, $i
                                            );
                        $currentBlock     = T_CLASS;

                        if ($token == T_TRAIT) {
                            $this->count['traits']++;
                        }

                        else if ($token == T_INTERFACE) {
                            $this->count['interfaces']++;
                        }

                        else {
                            if ($countTests && $this->isTestClass($className)) {
                                $testClass = TRUE;
                                $this->count['testClasses']++;
                            } else {
                                if (isset($tokens[$i-2]) &&
                                    is_array($tokens[$i-2]) &&
                                    $tokens[$i-2][0] == T_ABSTRACT) {
                                    $this->count['abstractClasses']++;
                                } else {
                                    $this->count['concreteClasses']++;
                                }
                            }
                        }
                    }
                    break;

                    case T_FUNCTION: {
                        $currentBlock = T_FUNCTION;

                        if (is_array($tokens[$i+2]) &&
                            $tokens[$i+2][0] == T_STRING) {
                            $functionName = $tokens[$i+2][1];
                        }

                        else if ($tokens[$i+2] == '&' &&
                                 is_array($tokens[$i+3]) &&
                                 $tokens[$i+3][0] == T_STRING) {
                            $functionName = $tokens[$i+3][1];
                        }

                        else {
                            $currentBlock = 'anonymous function';
                            $functionName = 'anonymous function';
                            $this->count['anonymousFunctions']++;
                        }

                        if ($currentBlock == T_FUNCTION) {
                            if ($className === NULL &&
                                $functionName != 'anonymous function') {
                                $this->count['namedFunctions']++;
                            } else {
                                $static     = FALSE;
                                $visibility = T_PUBLIC;

                                for ($j = $i; $j > 0; $j--) {
                                    if (is_string($tokens[$j])) {
                                        if ($tokens[$j] == '{' ||
                                            $tokens[$j] == '}' ||
                                            $tokens[$j] == ';') {
                                            break;
                                        }

                                        continue;
                                    }

                                    if (isset($tokens[$j][0])) {
                                        switch ($tokens[$j][0]) {
                                            case T_PRIVATE: {
                                                $visibility = T_PRIVATE;
                                            }
                                            break;

                                            case T_PROTECTED: {
                                                $visibility = T_PROTECTED;
                                            }
                                            break;

                                            case T_STATIC: {
                                                $static = TRUE;
                                            }
                                            break;
                                        }
                                    }
                                }

                                if ($testClass &&
                                    $this->isTestMethod($functionName, $visibility, $static, $tokens, $i)) {
                                    $this->count['testMethods']++;
                                }

                                else if (!$testClass) {
                                    $currentMethodData = array('ccn' => 1, 'lloc' => 0);

                                    if (!$static) {
                                        $this->count['nonStaticMethods']++;
                                    } else {
                                        $this->count['staticMethods']++;
                                    }

                                    if ($visibility == T_PUBLIC) {
                                        $this->count['publicMethods']++;
                                    } else {
                                        $this->count['nonPublicMethods']++;
                                    }

                                    $this->count['methods']++;
                                }
                            }
                        }
                    }
                    break;

                    case T_CURLY_OPEN: {
                        $currentBlock = T_CURLY_OPEN;
                        array_push($blocks, $currentBlock);
                    }
                    break;

                    case T_DOLLAR_OPEN_CURLY_BRACES: {
                        $currentBlock = T_DOLLAR_OPEN_CURLY_BRACES;
                        array_push($blocks, $currentBlock);
                    }
                    break;

                    case T_IF:
                    case T_ELSEIF:
                    case T_FOR:
                    case T_FOREACH:
                    case T_WHILE:
                    case T_CASE:
                    case T_CATCH:
                    case T_BOOLEAN_AND:
                    case T_LOGICAL_AND:
                    case T_BOOLEAN_OR:
                    case T_LOGICAL_OR: {
                        if (!$testClass) {
                            if ($currentMethodData !== NULL) {
                                $this->count['ccnMethods']++;
                                $currentClassData['ccn']++;
                                $currentMethodData['ccn']++;
                            }

                            $this->count['ccn']++;
                        }
                    }
                    break;

                    case T_COMMENT: {
                        $this->count['cloc']++;
                    }
                    break;

                    case T_DOC_COMMENT: {
                        $this->count['cloc'] += substr_count($value, "\n") + 1;
                    }
                    break;

                    case T_CONST: {
                        $this->count['classConstants']++;
                    }
                    break;

                    case T_STRING: {
                        if ($value == 'define') {
                            $this->count['globalConstants']++;

                            $j = $i + 1;

                            while (isset($tokens[$j]) && $tokens[$j] != ';') {
                                if (is_array($tokens[$j]) &&
                                    $tokens[$j][0] == T_CONSTANT_ENCAPSED_STRING) {
                                    $this->constants[] = str_replace(
                                      '\'', '', $tokens[$j][1]
                                    );

                                    break;
                                }

                                $j++;
                            }
                        }

                        else {
                            $this->possibleConstantAccesses[] = $value;
                        }
                    }
                    break;

                    case T_DOUBLE_COLON:
                    case T_OBJECT_OPERATOR: {
                        $n = $this->getNextNonWhitespaceTokenPos($tokens, $i);
                        $nn = $this->getNextNonWhitespaceTokenPos($tokens, $n);

                        if ($n && $nn &&
                            isset($tokens[$n][0]) &&
                            ($tokens[$n][0] == T_STRING ||
                             $tokens[$n][0] == T_VARIABLE) &&
                            $tokens[$nn] == '(') {
                            if ($token == T_DOUBLE_COLON) {
                                $this->count['staticMethodCalls']++;
                            } else {
                                $this->count['instanceMethodCalls']++;
                            }
                        } else {
                            if ($token == T_DOUBLE_COLON &&
                                $tokens[$n][0] == T_VARIABLE) {
                                $this->count['staticAttributeAccesses']++;
                            }

                            else if ($token == T_OBJECT_OPERATOR) {
                                $this->count['instanceAttributeAccesses']++;
                            }
                        }
                    }
                    break;

                    case T_GLOBAL: {
                        $this->count['globalVariableAccesses']++;
                    }
                    break;

                    case T_VARIABLE: {
                        if ($value == '$GLOBALS') {
                            $this->count['globalVariableAccesses']++;
                        }

                        else if (isset($this->superGlobals[$value])) {
                            $this->count['superGlobalVariableAccesses']++;
                        }
                    }
                    break;
                }
            }
        }

        /**
         * @param  array   $tokens
         * @param  integer $i
         * @return string
         * @since  Method available since Release 1.3.0
         */
        private function getNamespaceName(array $tokens, $i)
        {
            if (isset($tokens[$i+2][1])) {
                $namespace = $tokens[$i+2][1];

                for ($j = $i+3; ; $j += 2) {
                    if (isset($tokens[$j]) && $tokens[$j][0] == T_NS_SEPARATOR) {
                        $namespace .= '\\' . $tokens[$j+1][1];
                    } else {
                        break;
                    }
                }

                return $namespace;
            }

            return FALSE;
        }

        /**
         * @param  string  $namespace
         * @param  array   $tokens
         * @param  integer $i
         * @return string
         * @since  Method available since Release 1.3.0
         */
        private function getClassName($namespace, array $tokens, $i)
        {
            $i         += 2;
            $namespaced = FALSE;

            if (!isset($tokens[$i][1])) {
                return 'invalid class name';
            }

            $className  = $tokens[$i][1];

            if ($className === '\\') {
                $namespaced = TRUE;
            }

            while (is_array($tokens[$i+1]) && $tokens[$i+1][0] !== T_WHITESPACE) {
                $className .= $tokens[++$i][1];
            }

            if (!$namespaced && $namespace !== FALSE) {
                $className = $namespace . '\\' . $className;
            }

            return $className;
        }

        /**
         * @param  string $className
         * @return boolean
         * @since  Method available since Release 1.2.0
         */
        private function isTestClass($className)
        {
            $parent = $this->classes[$className];
            $result = FALSE;
            $count = 0;

            // Check ancestry for PHPUnit_Framework_TestCase.
            while ($parent !== NULL) {
                $count++;
                if ($count > 100) {
                    // Prevent infinite loops and just bail
                    break;
                }
                if ($parent == 'PHPUnit_Framework_TestCase' ||
                    $parent == '\\PHPUnit_Framework_TestCase') {
                    $result = TRUE;
                    break;
                }

                if (isset($this->classes[$parent]) && $parent !== $this->classes[$parent]) {
                    $parent = $this->classes[$parent];
                }

                // Class has a parent that is declared in a file
                // that was not pre-processed.
                else {
                    break;
                }
            }

            // Fallback: Treat the class as a test case class if the name
            // of the parent class ends with "TestCase".
            if (!$result) {
                if (substr($this->classes[$className], -8) == 'TestCase') {
                    $result = TRUE;
                }
            }

            return $result;
        }

        /**
         * @param  string  $functionName
         * @param  integer $visibility
         * @param  boolean $static
         * @param  array   $tokens
         * @param  integer $currentToken
         * @return boolean
         * @since  Method available since Release 2.0.0
         */
        private function isTestMethod($functionName, $visibility, $static, array $tokens, $currentToken) {
            if ($static || $visibility != T_PUBLIC) {
                return FALSE;
            }

            if (strpos($functionName, 'test') === 0) {
                return TRUE;
            }

            while ($tokens[$currentToken][0] != T_DOC_COMMENT) {
                if ($tokens[$currentToken] == '{' || $tokens[$currentToken] == '}') {
                    return FALSE;
                }

                --$currentToken;
            }

            return strpos($tokens[$currentToken][1], '@test') !== FALSE ||
                   strpos($tokens[$currentToken][1], '@scenario') !== FALSE;
        }

        /**
         * @param  array   $tokens
         * @param  integer $start
         * @return boolean
         */
        private function getNextNonWhitespaceTokenPos(array $tokens, $start)
        {
            if (isset($tokens[$start+1])) {
                if (isset($tokens[$start+1][0]) &&
                    $tokens[$start+1][0] == T_WHITESPACE &&
                    isset($tokens[$start+2])) {
                    return $start + 2;
                } else {
                    return $start + 1;
                }
            }

            return FALSE;
        }

        /**
         * @param  array   $tokens
         * @param  integer $start
         * @return boolean
         */
        private function getPreviousNonWhitespaceTokenPos(array $tokens, $start)
        {
            if (isset($tokens[$start-1])) {
                if (isset($tokens[$start-1][0]) &&
                    $tokens[$start-1][0] == T_WHITESPACE &&
                    isset($tokens[$start-2])) {
                    return $start - 2;
                } else {
                    return $start - 1;
                }
            }

            return FALSE;
        }

        /**
         * @param  array   $tokens
         * @param  integer $i
         * @return boolean
         */
        private function isClassDeclaration(array $tokens, $i)
        {
            $n = $this->getPreviousNonWhitespaceTokenPos($tokens, $i);

            if (isset($tokens[$n]) && is_array($tokens[$n]) &&
                $tokens[$n][0] == T_DOUBLE_COLON) {
                return FALSE;
            }

            return TRUE;
        }
    }
}
