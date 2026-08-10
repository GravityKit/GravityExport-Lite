<?php

namespace GFExcel\Tests;

use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Guards the plugin source against PHP 8.4's implicitly-nullable-parameter deprecation.
 *
 * A parameter written as `Type $param = null` is implicitly nullable, which PHP 8.4 deprecates at compile
 * time — so the notice fires on every request that loads the class, not only when the method is called.
 * Tokenizing rather than reflecting keeps this runnable on every supported PHP version.
 *
 * @since TBD
 *
 * @see https://linear.app/gravitykit/issue/GEXPLIT-20
 */
class ImplicitNullableTest extends TestCase
{
    /**
     * Asserts no source file declares an implicitly nullable parameter.
     * @since TBD
     */
    public function testSourceHasNoImplicitlyNullableParameters(): void
    {
        $offenders = [];

        foreach ($this->shippedSourceFiles() as $file) {
            $offenders = array_merge($offenders, $this->findImplicitlyNullableParameters($file));
        }

        sort($offenders);

        $this->assertSame([], $offenders, sprintf(
            "Implicitly nullable parameters are deprecated as of PHP 8.4; declare them as `?Type \$param = null`:\n- %s",
            implode("\n- ", $offenders)
        ));
    }

    /**
     * Returns every PHP file the plugin ships from its own codebase.
     *
     * `gfexcel.php` and `uninstall.php` sit in the plugin root and ship too, so scanning `src/` alone would
     * leave them unguarded.
     *
     * @since TBD
     * @return string[] Absolute paths.
     */
    private function shippedSourceFiles(): array
    {
        $root = dirname(__DIR__);
        $files = glob($root . '/*.php') ?: [];

        $source = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/src'));

        foreach ($source as $file) {
            if ($file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    /**
     * Asserts the build still patches the vendored dependencies.
     *
     * The patcher only runs during `composer build`, so without this the step could be dropped and every
     * test would stay green while the shipped plugin went back to emitting the notices.
     *
     * @since TBD
     */
    public function testBuildPatchesVendoredDeprecations(): void
    {
        $root = dirname(__DIR__);
        $composer = json_decode((string) file_get_contents($root . '/composer.json'), true);
        $build = $composer['scripts']['build'] ?? [];

        $this->assertContains(
            'php bin/fix-vendored-php-deprecations.php',
            $build,
            'The `build` script must run the vendored-deprecation patcher, or the shipped plugin will emit '
            . 'PHP deprecation notices under our own namespace prefix.'
        );

        $this->assertFileExists($root . '/bin/fix-vendored-php-deprecations.php');

        // The patcher must run after Strauss writes vendor_prefixed/, otherwise it has nothing to patch.
        $strauss = array_search('cd build && ./strauss.phar', $build, true);
        $patcher = array_search('php bin/fix-vendored-php-deprecations.php', $build, true);

        $this->assertIsInt($strauss, 'The `build` script must still run Strauss.');
        $this->assertGreaterThan($strauss, $patcher, 'The patcher must run after Strauss.');
    }

    /**
     * Returns `file:line $param` for every implicitly nullable parameter in a file.
     * @since TBD
     * @param string $path Absolute path to the PHP file.
     * @return string[] The offending parameters.
     */
    private function findImplicitlyNullableParameters(string $path): array
    {
        $offenders = [];
        $tokens = array_values(array_filter(
            token_get_all((string) file_get_contents($path)),
            static function ($token): bool {
                return !is_array($token) || !in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true);
            }
        ));
        $count = count($tokens);

        // T_FN (arrow functions) only exists on PHP 7.4+.
        $function_tokens = defined('T_FN') ? [T_FUNCTION, constant('T_FN')] : [T_FUNCTION];

        for ($i = 0; $i < $count; $i++) {
            if (!is_array($tokens[$i]) || !in_array($tokens[$i][0], $function_tokens, true)) {
                continue;
            }

            // `fn` is a valid method name, and PHP still tokenizes it as T_FN there — skip the name, not
            // the declaration, or the parameter list gets scanned twice.
            $previous = $tokens[$i - 1] ?? null;

            if (is_array($previous) && $previous[0] === T_FUNCTION) {
                continue;
            }

            $open = $this->findNext($tokens, $i + 1, '(');

            if ($open === null) {
                continue;
            }

            foreach ($this->splitParameters($tokens, $open) as $parameter) {
                $offender = $this->describeIfImplicitlyNullable($parameter, basename($path));

                if ($offender !== null) {
                    $offenders[] = $offender;
                }
            }
        }

        return $offenders;
    }

    /**
     * Returns the index of the next occurrence of a literal token, or null.
     * @since TBD
     * @param array $tokens The token list.
     * @param int $from The index to start at.
     * @param string $needle The literal token to find.
     * @return int|null The index, or null when not found.
     */
    private function findNext(array $tokens, int $from, string $needle): ?int
    {
        for ($i = $from; $i < count($tokens); $i++) {
            if ($tokens[$i] === $needle) {
                return $i;
            }
        }

        return null;
    }

    /**
     * Splits a parameter list into one token list per parameter.
     * @since TBD
     * @param array $tokens The token list.
     * @param int $open The index of the parameter list's opening parenthesis.
     * @return array[] One token list per declared parameter.
     */
    private function splitParameters(array $tokens, int $open): array
    {
        $parameters = [];
        $current = [];
        $depth = 0;

        for ($i = $open; $i < count($tokens); $i++) {
            $token = $tokens[$i];

            // An attribute opens with `#[` and closes with `]`. Skip it whole: its arguments are not part of
            // the parameter, and its brackets would otherwise unbalance the depth count.
            if (is_array($token) && defined('T_ATTRIBUTE') && $token[0] === constant('T_ATTRIBUTE')) {
                $i = $this->skipAttribute($tokens, $i);

                continue;
            }

            if (in_array($token, ['(', '[', '{'], true)) {
                $depth++;

                if ($depth === 1) {
                    continue;
                }
            }

            if (in_array($token, [')', ']', '}'], true)) {
                $depth--;

                if ($depth === 0) {
                    if ($current) {
                        $parameters[] = $current;
                    }

                    break;
                }
            }

            if ($depth === 1 && $token === ',') {
                $parameters[] = $current;
                $current = [];

                continue;
            }

            $current[] = $token;
        }

        return $parameters;
    }

    /**
     * Returns the index of the `]` closing an attribute that opens at `$start`.
     * @since TBD
     * @param array $tokens The token list.
     * @param int $start The index of the T_ATTRIBUTE token.
     * @return int The index of the closing bracket.
     */
    private function skipAttribute(array $tokens, int $start): int
    {
        $depth = 1;

        for ($i = $start + 1; $i < count($tokens); $i++) {
            if (in_array($tokens[$i], ['(', '[', '{'], true)) {
                $depth++;

                continue;
            }

            if (in_array($tokens[$i], [')', ']', '}'], true)) {
                $depth--;

                if ($depth === 0) {
                    return $i;
                }
            }
        }

        return $start;
    }

    /**
     * Returns `file:line $param` when the parameter is implicitly nullable, otherwise null.
     * @since TBD
     * @param array $parameter The parameter's tokens.
     * @param string $file The file's basename, for the message.
     * @return string|null The description, or null when the parameter is fine.
     */
    private function describeIfImplicitlyNullable(array $parameter, string $file): ?string
    {
        // Promoted properties carry visibility/readonly keywords before the type; they are not part of it.
        $modifiers = ['public', 'protected', 'private', 'readonly'];

        $type_tokens = [];
        $variable = null;
        $line = 0;
        $default = [];
        $seen_equals = false;

        foreach ($parameter as $token) {
            if ($token === '=') {
                $seen_equals = true;

                continue;
            }

            if ($seen_equals) {
                $default[] = $token;

                continue;
            }

            if (is_array($token) && $token[0] === T_VARIABLE) {
                $variable = $token[1];
                $line = $token[2];

                continue;
            }

            if ($variable !== null) {
                continue;
            }

            if (is_array($token) && in_array(strtolower($token[1]), $modifiers, true)) {
                continue;
            }

            $type_tokens[] = $token;
        }

        if ($variable === null || !$default) {
            return null;
        }

        $type = strtolower($this->stringify($type_tokens));

        // `&` and `...` sit between the type and the variable; only a trailing one is a marker, not a type.
        $type = rtrim($type, '&.');

        if ($type === '' || strpos($type, '?') === 0) {
            return null;
        }

        // A `null` or `mixed` arm already makes the type nullable.
        $arms = explode('|', $type);

        if (in_array('null', $arms, true) || in_array('mixed', $arms, true)) {
            return null;
        }

        // `= (null)` is still an implicitly nullable default.
        if (trim($this->stringify($default), '() ') !== 'null') {
            return null;
        }

        return sprintf('%s:%d %s', $file, $line, $variable);
    }

    /**
     * Flattens a token list back into its source text.
     * @since TBD
     * @param array $tokens The token list.
     * @return string The source text.
     */
    private function stringify(array $tokens): string
    {
        return strtolower(implode('', array_map(static function ($token) {
            return is_array($token) ? $token[1] : $token;
        }, $tokens)));
    }
}
