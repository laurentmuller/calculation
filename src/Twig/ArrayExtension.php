<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
 */

declare(strict_types=1);

namespace App\Twig;

use Symfony\Bridge\Twig\Extension\TranslationExtension;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Twig extension for array.
 *
 * @author Laurent Muller
 */
final class ArrayExtension extends AbstractExtension
{
    /**
     * The translation extension.
     *
     * @var TranslationExtension
     */
    private $extension;

    /**
     * Constructs the service.
     *
     * @param translatorInterface $translator The Translation service implementing the translation interface
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->extension = new TranslationExtension($translator);
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new TwigFilter('transArray', [$this, 'transArrayFilter']),
            new TwigFilter('replaceArgs', [$this, 'replaceArgsFilter']),
            new TwigFilter('replaceArray', [$this, 'replaceArrayFilter']),
            new TwigFilter('lowerArray', [$this, 'lowerArrayFilter'], ['needs_environment' => true]),
        ];
    }

    /**
     * Converts an array of string to lowercase.
     *
     * @param Environment $env    the Twig environment
     * @param string[]    $values the array of strings to convert
     *
     * @return string[] the lowercased strings,
     */
    public function lowerArrayFilter(Environment $env, array $values): array
    {
        $callback = function ($value) use ($env) {
            return twig_lower_filter($env, $value);
        };

        return \array_map($callback, $values);
    }

    /**
     * Converts arguments with the correct format to display in error templates.
     *
     * @param array $args the arguments to convert
     *
     * @return array the converted arguments
     */
    public function replaceArgsFilter(array $args): array
    {
        $result = [];
        foreach ($args as  $arg) {
            //already converted?
            if ($this->isArgument($arg)) {
                $result[] = $arg;
                continue;
            }

            // type
            $type = \strtolower(\gettype($arg));

            if (\is_object($arg)) {
                // object is converted to it's string class
                $arg = \get_class($arg);
            } elseif (\is_array($arg)) {
                // array is converted recursively
                $arg = $this->replaceArgsFilter($arg);
            }

            $result[] = [$type, $arg];
        }

        return $result;
    }

    /**
     * Replaces strings within an array of strings.
     *
     * @param string[]           $values the array of strings to replace in
     * @param array|\Traversable $from   the replace values
     * @param string|null        $to     the replace to, deprecated (see <a href='http://php.net/manual/en/function.strtr.php'>strtr</a>)
     *
     * @return string[]
     */
    public function replaceArrayFilter(array $values, $from, $to = null): array
    {
        $callback = function ($value) use ($from, $to) {
            return twig_replace_filter($value, $from, $to);
        };

        return \array_map($callback, $values);
    }

    /**
     * Translates each element of an array using the provided translator.
     *
     * @param array  $ids        an array of message ids (may also be an array of objects that can be cast to string)
     * @param array  $parameters an array of parameters for the messages
     * @param string $domain     the domain for the messages or null to use the default
     * @param string $locale     the locale or null to use the default
     *
     * @return string[] an array of translated messages
     */
    public function transArrayFilter(array $ids, array $parameters = [], $domain = null, $locale = null): array
    {
        $callback = function ($message) use ($parameters, $domain, $locale) {
            return $this->extension->trans($message, $parameters, $domain, $locale);
        };

        return \array_map($callback, $ids);
    }

    /**
     * Checks if the given argument is already in the correct format.
     *
     * @param mixed $arg the argument to verify
     *
     * @return bool true if valid
     */
    private function isArgument($arg): bool
    {
        return \is_array($arg) && 2 === \count($arg) && \is_string($arg[0]);
    }
}
