<?php

/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Tests\Twig;

use PHPUnit\Framework\Constraint\Exception;
use PHPUnit\Framework\TestCase;
use Twig\Environment;
use Twig\Error\Error;
use Twig\Extension\ExtensionInterface;
use Twig\Loader\ArrayLoader;
use Twig\RuntimeLoader\RuntimeLoaderInterface;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Twig\TwigTest;

/**
 * Integration test helper.
 *
 * @phpstan-type TestTemplateType=array{
 *      file: string,
 *      message: string,
 *      condition: string,
 *      templates: array<string, string>,
 *      exception: false|string,
 *      outputs: array<int, string[]>,
 *      deprecation: string}
 */
abstract class IntegrationTestCase extends TestCase
{
    private const FILE_EXTENSION = 'test';
    private const PATTERN_OUTPUTS = '/--DATA--(.*?)(?:--CONFIG--(.*?))?--EXPECT--(.*?)(?=\-\-DATA\-\-|$)/s';
    private const PATTERN_TEMPLATES = '/--TEMPLATE(?:\((.*?)\))?--(.*?)(?=\-\-TEMPLATE|$)/s';
    private const PATTERN_WITH_EXCEPTION = '/--TEST--\s*(.*?)\s*(?:--CONDITION--\s*(.*))?\s*(?:--DEPRECATION--\s*(.*?))?\s*((?:--TEMPLATE(?:\(.*?\))?--(?:.*?))+)\s*(?:--DATA--\s*(.*))?\s*--EXCEPTION--\s*(.*)/sx';
    private const PATTERN_WITH_EXPECT = '/--TEST--\s*(.*?)\s*(?:--CONDITION--\s*(.*))?\s*(?:--DEPRECATION--\s*(.*?))?\s*((?:--TEMPLATE(?:\(.*?\))?--(?:.*?))+)--DATA--.*?--EXPECT--.*/s';

    /**
     * @throws Error
     */
    public function testIntegration(): void
    {
        $tests = $this->getTests();
        foreach ($tests as $test) {
            $this->doIntegrationTest(
                $test['file'],
                $test['message'],
                $test['condition'],
                $test['templates'],
                $test['exception'],
                $test['outputs'],
                $test['deprecation']
            );
        }
    }

    /**
     * @param array<string, string> $templates
     * @param array<int, string[]>  $outputs
     *
     * @throws Error
     */
    protected function doIntegrationTest(
        string $file,
        string $message,
        string $condition,
        array $templates,
        false|string $exception,
        array $outputs,
        string $deprecation
    ): void {
        if ([] === $outputs) {
            self::markTestSkipped('No test to run');
        }

        if ('' !== $condition) {
            $ret = true;
            $this->eval('$ret = ' . $condition);
            /** @phpstan-var bool $ret */
            if (!$ret) {
                self::markTestSkipped($condition);
            }
        }

        $loader = new ArrayLoader($templates);

        foreach ($outputs as $index => $match) {
            $twig = $this->createEnvironment($loader, $match);
            $deprecations = [];

            try {
                \set_error_handler(
                    static function (int $type, string $message) use (&$deprecations): bool {
                        if (\E_USER_DEPRECATED === $type) {
                            $deprecations[] = $message;

                            return true;
                        }

                        return false;
                    }
                );

                $template = $twig->load('index.twig');
            } catch (\Exception $e) {
                if (false !== $exception) {
                    $message = $e->getMessage();
                    self::assertSame(\trim($exception), \trim(\sprintf('%s: %s', $e::class, $message)));
                    $last = \substr($message, \strlen($message) - 1);
                    self::assertTrue('.' === $last || '?' === $last, 'Exception message must end with a dot or a question mark.');

                    return;
                }

                throw new Error(message: \sprintf('%s: %s', $e::class, $e->getMessage()), previous: $e);
            } finally {
                \restore_error_handler();
            }

            self::assertSame($deprecation, \implode("\n", $deprecations));

            try {
                $output = \trim($template->render($this->eval($match[1])), "\n ");
            } catch (\Exception $e) {
                if (false !== $exception) {
                    self::assertSame(\trim($exception), \trim(\sprintf('%s: %s', $e::class, $e->getMessage())));

                    return;
                }
                $e = new Error(message: \sprintf('%s: %s', $e::class, $e->getMessage()), previous: $e);
                $output = \trim(\sprintf('%s: %s', $e::class, $e->getMessage()));
            }

            if (false !== $exception) {
                [$className] = \explode(':', $exception);
                self::assertThat(null, new Exception($className));
            }

            $expected = \trim($match[3], "\n ");
            if ($expected !== $output) {
                \printf("Compiled templates that failed on case %d:\n", $index + 1);
                foreach (\array_keys($templates) as $name) {
                    echo \sprintf('Template: %s%s', $name, \PHP_EOL);
                    echo $this->compile($twig, $name);
                }
            }
            self::assertSame($expected, $output, $message . ' (in ' . $file . ')');
        }
    }

    /**
     * @return ExtensionInterface[]
     */
    protected function getExtensions(): array
    {
        return [];
    }

    abstract protected function getFixturesDir(): string;

    /**
     * @return RuntimeLoaderInterface[]
     */
    protected function getRuntimeLoaders(): array
    {
        return [];
    }

    /**
     * @return TwigFilter[]
     */
    protected function getTwigFilters(): array
    {
        return [];
    }

    /**
     * @return TwigFunction[]
     */
    protected function getTwigFunctions(): array
    {
        return [];
    }

    /**
     * @return TwigTest[]
     */
    protected function getTwigTests(): array
    {
        return [];
    }

    /**
     * @throws Error
     */
    private function compile(Environment $twig, string $name): string
    {
        $loader = $twig->getLoader();
        $context = $loader->getSourceContext($name);
        $stream = $twig->tokenize($context);
        $node = $twig->parse($stream);

        return $twig->compile($node);
    }

    /**
     * @param string[] $match
     */
    private function createEnvironment(ArrayLoader $loader, array $match): Environment
    {
        $config = [
            'cache' => false,
            'strict_variables' => true,
        ];
        if (isset($match[2]) && '' !== $match[2]) {
            $config = \array_merge($config, $this->eval($match[2]));
        }

        $twig = new Environment($loader, $config);
        $twig->addGlobal('global', 'global');

        foreach ($this->getRuntimeLoaders() as $runtimeLoader) {
            $twig->addRuntimeLoader($runtimeLoader);
        }
        foreach ($this->getExtensions() as $extension) {
            $twig->addExtension($extension);
        }
        foreach ($this->getTwigFilters() as $filter) {
            $twig->addFilter($filter);
        }
        foreach ($this->getTwigTests() as $test) {
            $twig->addTest($test);
        }
        foreach ($this->getTwigFunctions() as $function) {
            $twig->addFunction($function);
        }

        return $twig;
    }

    private function eval(string $code): array
    {
        $stream = \tmpfile();
        if (!\is_resource($stream)) {
            throw new \InvalidArgumentException('Unable to evaluate the code.');
        }

        $uri = \stream_get_meta_data($stream)['uri'] ?? '';
        if (!\file_exists($uri) || '' === $uri) {
            throw new \InvalidArgumentException('Unable to open the file.');
        }
        \register_shutdown_function(fn () => $this->unlink($uri));
        \fwrite($stream, \sprintf('<?php %s;', $code));
        $vars = (array) include $uri;
        \fclose($stream);

        return $vars;
    }

    /**
     * @phpstan-return \Iterator<string>
     */
    private function getIterator(string $fixturesDir): \Iterator
    {
        $flags = \FilesystemIterator::CURRENT_AS_PATHNAME | \FilesystemIterator::SKIP_DOTS;

        return new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($fixturesDir, $flags),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );
    }

    /**
     * @phpstan-return TestTemplateType[]
     */
    private function getTests(): array
    {
        $tests = [];
        $dir = $this->getFixturesDir();
        $fixturesDir = \realpath($dir);
        if (!\is_string($fixturesDir)) {
            throw new \InvalidArgumentException(\sprintf('The fixture directory "%s" is not valid.', $dir));
        }

        foreach ($this->getIterator($fixturesDir) as $path) {
            if (!\str_ends_with($path, self::FILE_EXTENSION)) {
                continue;
            }

            $test = \file_get_contents($path);
            if (!\is_string($test)) {
                throw new \InvalidArgumentException(\sprintf('Unable to get content of the file "%s".', $path));
            }
            if (1 === \preg_match(self::PATTERN_WITH_EXCEPTION, $test, $match)) {
                $message = $match[1];
                $condition = $match[2];
                $deprecation = $match[3];
                $templates = $this->parseTemplates($match[4]);
                $exception = $match[6];
                $outputs = [[null, $match[5], null, '']];
            } elseif (1 === \preg_match(self::PATTERN_WITH_EXPECT, $test, $match)) {
                $message = $match[1];
                $condition = $match[2];
                $deprecation = $match[3];
                $templates = $this->parseTemplates($match[4]);
                $exception = false;
                \preg_match_all(self::PATTERN_OUTPUTS, $test, $outputs, \PREG_SET_ORDER);
            } else {
                throw new \InvalidArgumentException(\sprintf('Test "%s" is not valid.', $path));
            }

            $tests[] = [
                'file' => $path,
                'message' => $message,
                'condition' => $condition,
                'templates' => $templates,
                'exception' => $exception,
                'outputs' => $outputs,
                'deprecation' => $deprecation,
            ];
        }

        /** @phpstan-var TestTemplateType[] */
        return $tests;  // @phpstan-ignore varTag.type
    }

    /**
     * @return array<string, string>
     */
    private function parseTemplates(string $test): array
    {
        $result = \preg_match_all(self::PATTERN_TEMPLATES, $test, $matches, \PREG_SET_ORDER);
        if (false === $result || 0 === $result) {
            return [];
        }

        $templates = [];
        foreach ($matches as $match) {
            $name = '' === $match[1] ? 'index.twig' : $match[1];
            $templates[$name] = $match[2];
        }

        return $templates;
    }

    private function unlink(string $path): void
    {
        if (\file_exists($path)) {
            \unlink($path);
        }
    }
}
