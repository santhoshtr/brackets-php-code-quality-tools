<?php
/**
 * Drupal_Sniffs_WhiteSpace_OperatorSpacingSniff.
 *
 * PHP version 5
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */

/**
 * Overrides Squiz_Sniffs_WhiteSpace_OperatorSpacingSniff to not check inline if/then
 * statements because those are handled by
 * Drupal_Sniffs_Formatting_SpaceInlineIfSniff. Also makes sure that newlines are
 * allowed after operators.
 *
 * @category PHP
 * @package  PHP_CodeSniffer
 * @link     http://pear.php.net/package/PHP_CodeSniffer
 */
class Drupal_Sniffs_WhiteSpace_OperatorSpacingSniff implements PHP_CodeSniffer_Sniff
{

    /**
     * A list of tokenizers this sniff supports.
     *
     * @var array
     */
    public $supportedTokenizers = array(
                                   'PHP',
                                   'JS',
                                  );


    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array
     */
    public function register()
    {
        $comparison = PHP_CodeSniffer_Tokens::$comparisonTokens;
        $operators  = PHP_CodeSniffer_Tokens::$operators;
        $assignment = PHP_CodeSniffer_Tokens::$assignmentTokens;

        return array_unique(
            array_merge($comparison, $operators, $assignment)
        );

    }//end register()


    /**
     * Processes this sniff, when one of its tokens is encountered.
     *
     * @param PHP_CodeSniffer_File $phpcsFile The current file being checked.
     * @param int                  $stackPtr  The position of the current token in
     *                                        the stack passed in $tokens.
     *
     * @return void
     */
    public function process(PHP_CodeSniffer_File $phpcsFile, $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        // Skip default values in function declarations.
        if ($tokens[$stackPtr]['code'] === T_EQUAL
            || $tokens[$stackPtr]['code'] === T_MINUS
        ) {
            if (isset($tokens[$stackPtr]['nested_parenthesis']) === true) {
                $parenthesis = array_keys($tokens[$stackPtr]['nested_parenthesis']);
                $bracket     = array_pop($parenthesis);
                if (isset($tokens[$bracket]['parenthesis_owner']) === true) {
                    $function = $tokens[$bracket]['parenthesis_owner'];
                    if ($tokens[$function]['code'] === T_FUNCTION
                        || $tokens[$function]['code'] === T_CLOSURE
                    ) {
                        return;
                    }
                }
            }
        }

        if ($tokens[$stackPtr]['code'] === T_EQUAL) {
            // Skip for '=&' case.
            if (isset($tokens[($stackPtr + 1)]) === true
                && $tokens[($stackPtr + 1)]['code'] === T_BITWISE_AND
            ) {
                return;
            }
        }

        // Skip short ternary such as: $foo = $bar ?: true;
        if (($tokens[$stackPtr]['code'] == T_INLINE_THEN
            && $tokens[$stackPtr + 1]['code'] == T_INLINE_ELSE)
            || ($tokens[$stackPtr - 1]['code'] == T_INLINE_THEN
            && $tokens[$stackPtr]['code'] == T_INLINE_ELSE)
        ) {
                return;
        }

        if ($tokens[$stackPtr]['code'] === T_BITWISE_AND) {
            // If it's not a reference, then we expect one space either side of the
            // bitwise operator.
            if ($phpcsFile->isReference($stackPtr) === true) {
                return;
            }

            // Check there is one space before the & operator.
            if ($tokens[($stackPtr - 1)]['code'] !== T_WHITESPACE) {
                $error = 'Expected 1 space before "&" operator; 0 found';
                $fix   = $phpcsFile->addFixableError($error, $stackPtr, 'NoSpaceBeforeAmp');
                if ($fix === true && $phpcsFile->fixer->enabled === true) {
                    $phpcsFile->fixer->addContentBefore($stackPtr, ' ');
                }

                $phpcsFile->recordMetric($stackPtr, 'Space before operator', 0);
            } else {
                if ($tokens[($stackPtr - 2)]['line'] !== $tokens[$stackPtr]['line']) {
                    $found = 'newline';
                } else {
                    $found = strlen($tokens[($stackPtr - 1)]['content']);
                }

                $phpcsFile->recordMetric($stackPtr, 'Space before operator', $found);
                if ($found !== 1) {
                    $error = 'Expected 1 space before "&" operator; %s found';
                    $data  = array($found);
                    $fix   = $phpcsFile->addFixableError($error, $stackPtr, 'SpacingBeforeAmp', $data);
                    if ($fix === true && $phpcsFile->fixer->enabled === true) {
                        $phpcsFile->fixer->replaceToken(($stackPtr - 1), ' ');
                    }
                }
            }//end if

            // Check there is one space after the & operator.
            if ($tokens[($stackPtr + 1)]['code'] !== T_WHITESPACE) {
                $error = 'Expected 1 space after "&" operator; 0 found';
                $fix   = $phpcsFile->addFixableError($error, $stackPtr, 'NoSpaceAfterAmp');
                if ($fix === true && $phpcsFile->fixer->enabled === true) {
                    $phpcsFile->fixer->addContent($stackPtr, ' ');
                }

                $phpcsFile->recordMetric($stackPtr, 'Space after operator', 0);
            } else {
                if ($tokens[($stackPtr + 2)]['line'] !== $tokens[$stackPtr]['line']) {
                    $found = 'newline';
                } else {
                    $found = strlen($tokens[($stackPtr + 1)]['content']);
                }

                $phpcsFile->recordMetric($stackPtr, 'Space after operator', $found);
                if ($found !== 1) {
                    $error = 'Expected 1 space after "&" operator; %s found';
                    $data  = array($found);
                    $fix   = $phpcsFile->addFixableError($error, $stackPtr, 'SpacingAfterAmp', $data);
                    if ($fix === true && $phpcsFile->fixer->enabled === true) {
                        $phpcsFile->fixer->replaceToken(($stackPtr + 1), ' ');
                    }
                }
            }//end if

            return;
        }//end if

        if ($tokens[$stackPtr]['code'] === T_MINUS) {
            // Check minus spacing, but make sure we aren't just assigning
            // a minus value or returning one.
            $prev = $phpcsFile->findPrevious(T_WHITESPACE, ($stackPtr - 1), null, true);
            if ($tokens[$prev]['code'] === T_RETURN) {
                // Just returning a negative value; eg. (return -1).
                return;
            }

            if (in_array($tokens[$prev]['code'], PHP_CodeSniffer_Tokens::$operators) === true) {
                // Just trying to operate on a negative value; eg. ($var * -1).
                return;
            }

            if (in_array($tokens[$prev]['code'], PHP_CodeSniffer_Tokens::$comparisonTokens) === true) {
                // Just trying to compare a negative value; eg. ($var === -1).
                return;
            }

            if (in_array($tokens[$prev]['code'], PHP_CodeSniffer_Tokens::$assignmentTokens) === true) {
                // Just trying to assign a negative value; eg. ($var = -1).
                return;
            }

            // A list of tokens that indicate that the token is not
            // part of an arithmetic operation.
            $invalidTokens = array(
                              T_COMMA,
                              T_OPEN_PARENTHESIS,
                              T_OPEN_SQUARE_BRACKET,
                              T_DOUBLE_ARROW,
                              T_COLON,
                              T_INLINE_THEN,
                              T_INLINE_ELSE,
                              T_CASE,
                             );

            if (in_array($tokens[$prev]['code'], $invalidTokens) === true) {
                // Just trying to use a negative value; eg. myFunction($var, -2).
                return;
            }
        }//end if

        $operator = $tokens[$stackPtr]['content'];

        if ($tokens[($stackPtr - 1)]['code'] !== T_WHITESPACE) {
            $error = "Expected 1 space before \"$operator\"; 0 found";
            $fix   = $phpcsFile->addFixableError($error, $stackPtr, 'NoSpaceBefore');
            if ($fix === true && $phpcsFile->fixer->enabled === true) {
                $phpcsFile->fixer->addContentBefore($stackPtr, ' ');
            }

            $phpcsFile->recordMetric($stackPtr, 'Space before operator', 0);
        } else if (in_array($tokens[$stackPtr]['code'], PHP_CodeSniffer_Tokens::$assignmentTokens) === false) {
            // Don't throw an error for assignments, because other standards allow
            // multiple spaces there to align multiple assignments.
            if ($tokens[($stackPtr - 2)]['line'] !== $tokens[$stackPtr]['line']) {
                $found = 'newline';
            } else {
                $found = strlen($tokens[($stackPtr - 1)]['content']);
            }

            $phpcsFile->recordMetric($stackPtr, 'Space before operator', $found);
            if ($found !== 1) {
                $error = 'Expected 1 space before "%s"; %s found';
                $data  = array(
                          $operator,
                          $found,
                         );
                $fix   = $phpcsFile->addFixableError($error, $stackPtr, 'SpacingBefore', $data);
                if ($fix === true && $phpcsFile->fixer->enabled === true) {
                    $phpcsFile->fixer->replaceToken(($stackPtr - 1), ' ');
                }
            }
        }//end if

        if ($tokens[($stackPtr + 1)]['code'] !== T_WHITESPACE) {
            $error = "Expected 1 space after \"$operator\"; 0 found";
            $fix   = $phpcsFile->addFixableError($error, $stackPtr, 'NoSpaceAfter');
            if ($fix === true && $phpcsFile->fixer->enabled === true) {
                $phpcsFile->fixer->addContent($stackPtr, ' ');
            }

            $phpcsFile->recordMetric($stackPtr, 'Space after operator', 0);
        } else {
            if ($tokens[($stackPtr + 2)]['line'] !== $tokens[$stackPtr]['line']) {
                $found = 'newline';
            } else {
                $found = strlen($tokens[($stackPtr + 1)]['content']);
            }

            $phpcsFile->recordMetric($stackPtr, 'Space after operator', $found);
            if ($found !== 1 && $found !== 'newline') {
                $error = 'Expected 1 space after "%s"; %s found';
                $data  = array(
                          $operator,
                          $found,
                         );
                $fix   = $phpcsFile->addFixableError($error, $stackPtr, 'SpacingAfter', $data);
                if ($fix === true && $phpcsFile->fixer->enabled === true) {
                    $phpcsFile->fixer->replaceToken(($stackPtr + 1), ' ');
                }
            }
        }//end if

    }//end process()


}//end class
