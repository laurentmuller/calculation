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

use App\Controller\AbstractController;
use App\Entity\Calculation;
use App\Service\ApplicationService;
use App\Service\UrlGeneratorService;
use App\Util\Utils;
use Symfony\Bridge\Twig\Extension\AssetExtension;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Twig extension for the application service.
 *
 * @author Laurent Muller
 */
final class FunctionExtension extends AbstractExtension
{
    /**
     * The asset extension.
     *
     * @var AssetExtension
     */
    private $asset;

    /**
     * The URL generator service.
     *
     * @var UrlGeneratorService
     */
    private $generator;

    /**
     * The nonce value.
     *
     * @var string
     */
    private $nonce;

    /**
     * The application service.
     *
     * @var ApplicationService
     */
    private $service;

    /**
     * The translator service.
     *
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * The real path of the public directory.
     *
     * @var string
     */
    private $webDir;

    /**
     * Constructor.
     *
     * @param KernelInterface     $kernel     the kernel to get the public directory
     * @param TranslatorInterface $translator the translator service
     * @param ApplicationService  $service    the application service
     * @param UrlGeneratorService $generator  the URL generator service
     */
    public function __construct(KernelInterface $kernel, TranslatorInterface $translator, ApplicationService $service, UrlGeneratorService $generator)
    {
        $this->webDir = \realpath($kernel->getProjectDir() . '/public');

        $this->service = $service;
        $this->translator = $translator;
        $this->generator = $generator;
    }

    /**
     * Checks if the given asset path exists.
     *
     * @param string $path the path to be verified
     *
     * @return bool true if exists
     */
    public function assetExists(?string $path): bool
    {
        // path?
        if (empty($path)) {
            return false;
        }

        // web directory?
        if (empty($this->webDir)) {
            return false;
        }

        // real path?
        if (!$file = \realpath($this->webDir . $path)) {
            return false;
        }

        // file exists?
        if (!\is_file($file)) {
            return false;
        }

        // check if file is well contained in public/ directory (prevents ../ in paths)
        if (0 !== \strncmp($this->webDir, $file, \strlen($this->webDir))) {
            return false;
        }

        // ok
        return true;
    }

    /**
     * Returns the given asset path, if exist; the default path otherwise.
     *
     * @param string $path    the path to be verified
     * @param string $default the default path
     *
     * @return string the path, if exist, the default path otherwise
     */
    public function assetIf(?string $path = null, ?string $default = null): ?string
    {
        if ($this->assetExists($path)) {
            return $path;
        }
        if ($this->assetExists($default)) {
            return $default;
        }

        return null;
    }

    /**
     * Gets the cancel URL.
     *
     * @param Request $request      the request
     * @param int     $id           the entity identifier
     * @param string  $defaultRoute the default route to use
     *
     * @return string the cancel URL
     */
    public function cancelUrl(Request $request, int $id = 0, string $defaultRoute = AbstractController::HOME_PAGE): string
    {
        return $this->generator->cancelUrl($request, $id, $defaultRoute);
    }

    /**
     * Checks the existence of file or directory.
     *
     * @param string $filename the path to the file or directory
     *
     * @return bool true if the file or directory exists, false otherwise
     */
    public function fileExists(?string $filename): bool
    {
        if ($filename) {
            return (new Filesystem())->exists($filename);
        }

        return false;
    }

    /**
     * Output a style sheet tag with a nonce value.
     *
     * @param Environment $env         the Twig environnement
     * @param string      $path        a public path
     * @param array       $parameters  additional parameters
     * @param string      $packageName the name of the asset package to use
     *
     * @return string the style sheet tag
     */
    public function getAssetCss(Environment $env, string $path, array $parameters = [], ?string $packageName = null): string
    {
        $nonce = $this->getNonce($env);
        $url = $this->getAssetUrl($env, $path, $packageName);
        $alternate = $this->getAlternateParameter($parameters);
        $params = $this->reduceParameters($parameters);

        return \sprintf('<link rel="stylesheet%s" href="%s" nonce="%s"%s>', $alternate, $url, $nonce, $params);
    }

    /**
     * Output a java script tag with a nonce value.
     *
     * @param Environment $env         the Twig environnement
     * @param string      $path        a public path
     * @param array       $parameters  additional parameters
     * @param string      $packageName the name of the asset package to use
     *
     * @return string the java script tag
     */
    public function getAssetJs(Environment $env, string $path, array $parameters = [], ?string $packageName = null): string
    {
        $nonce = $this->getNonce($env);
        $url = $this->getAssetUrl($env, $path, $packageName);
        $params = $this->reduceParameters($parameters);

        return \sprintf('<script src="%s" nonce="%s"%s></script>', $url, $nonce, $params);
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('trans_role', [$this, 'translateRole']),
            new TwigFilter('var_export', [Utils::class, 'exportVar']),
            new TwigFilter('normalize_whitespace', [$this, 'normalizeWhitespace'], ['preserves_safety' => ['html']]),
        ];
    }

    /**
     * Gets the position of the flashbag messages.
     *
     * @return string the position
     */
    public function getFlashbagPosition(): string
    {
        return $this->service->getMessagePosition();
    }

    /**
     * Gets the timeout of the flashbag messages.
     *
     * @return int the timeout
     */
    public function getFlashbagTimeout(): int
    {
        return $this->service->getMessageTimeout();
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions(): array
    {
        $assetOptions = [
            'needs_environment' => true,
            'is_safe' => ['html'],
        ];

        return [
            // flash bag messages
            new TwigFunction('flashbag_position', [$this, 'getFlashbagPosition'], ['deprecated' => true, 'alternative' => 'app.messagePosition']),
            new TwigFunction('flashbag_timeout', [$this, 'getFlashbagTimeout'], ['deprecated' => true, 'alternative' => 'app.messageTimeout']),
            new TwigFunction('flashbag_subtitle', [$this, 'isFlashbagSubTitle'], ['deprecated' => true, 'alternative' => 'app.messageSubTitle']),

            // asssets
            new TwigFunction('asset_exists', [$this, 'assetExists']),
            new TwigFunction('file_exists', [$this, 'fileExists']),

            new TwigFunction('asset_if', [$this, 'assetIf']),
            new TwigFunction('asset_js', [$this, 'getAssetJs'], $assetOptions),
            new TwigFunction('asset_css', [$this, 'getAssetCss'], $assetOptions),

            // images
            new TwigFunction('image_height', [$this, 'getImageHeight']),
            new TwigFunction('image_width', [$this, 'getImageWidth']),

            // routes
            new TwigFunction('cancel_url', [$this, 'cancelUrl']),
            new TwigFunction('route_params', [$this, 'routeParams']),

            // php
            new TwigFunction('is_int', 'is_int'),

            // application
            new TwigFunction('margin_below', [$this, 'isMarginBelow'], ['deprecated' => true, 'alternative' => 'app.marginBelow']),
            new TwigFunction('display_tabular', [$this, 'isDisplayTabular'], ['deprecated' => true, 'alternative' => 'app.displayTabular']),
        ];
    }

    /**
     * Gets the image height.
     *
     * @param string $path an existing image path relative to the public directory
     *
     * @return int the image height
     */
    public function getImageHeight(string $path): int
    {
        $fullPath = \realpath($this->webDir . $path);

        return \getimagesize($fullPath)[1];
    }

    /**
     * Gets the image width.
     *
     * @param string $path an existing image path relative to the public directory
     *
     * @return int the image width
     */
    public function getImageWidth(string $path): int
    {
        $fullPath = \realpath($this->webDir . $path);

        return \getimagesize($fullPath)[0];
    }

    /**
     * Gets a value indicating how entities are displayed.
     *
     * @return bool true, displays the entities in tabular mode; false, displays entities as cards
     */
    public function isDisplayTabular(): bool
    {
        return $this->service->isDisplayTabular();
    }

    /**
     * Gets a value indicating if the sub-title of the flashbag messages is displayed.
     *
     * @return bool true if displayed
     */
    public function isFlashbagSubTitle(): bool
    {
        return $this->service->isMessageSubTitle();
    }

    /**
     * Returns if the given calculation or margin is below the minimum margin allowed.
     *
     * @param Calculation|float $value the calculation or the margin to be tested
     *
     * @return bool true if below the minimum margin allowed
     */
    public function isMarginBelow($value): bool
    {
        return $this->service->isMarginBelow($value);
    }

    /**
     * This filter replaces duplicated spaces and/or linebreaks with single space.
     *
     * It also remove whitespace from the beginning and at the end of the string.
     *
     * @param string $value the value to clean
     *
     * @return string the cleaned value
     */
    public function normalizeWhitespace(string $value): string
    {
        // attributes
        $value = \preg_replace('/\s+=\s+/u', '=', $value);

        // space and new lines
        $value = \preg_replace('/\s+/u', ' ', $value);

        return \trim($value);
    }

    /**
     * Gets the route parameters.
     *
     * @param Request $request the request
     * @param int     $id      the entity identifier
     *
     * @return array the parameters
     */
    public function routeParams(Request $request, int $id = 0): array
    {
        return $this->generator->routeParams($request, $id);
    }

    /**
     * Translate the given role.
     *
     * @param string $role the role name
     *
     * @return string the translated role
     */
    public function translateRole(string $role): string
    {
        return Utils::translateRole($this->translator, $role);
    }

    /**
     * Gets the CSS alternate parameter value.
     *
     * @param array $parameters the parameters to search in and to update
     *
     * @return string the alternate parameter value
     */
    private function getAlternateParameter(array &$parameters): string
    {
        $alternate = isset($parameters['alternate']) && (bool) ($parameters['alternate']) ? ' alternate' : '';
        unset($parameters['alternate']);

        return $alternate;
    }

    /**
     * Returns the public url/path of an asset.
     *
     * If the package used to generate the path is an instance of
     * UrlPackage, you will always get a URL and not a path.
     *
     * @param Environment $env         the Twig environnement
     * @param string      $path        a public path
     * @param string      $packageName the optional name of the asset package to use
     *
     * @return string the public path of the asset
     */
    private function getAssetUrl(Environment $env, string $path, ?string $packageName = null): string
    {
        if (null === $this->asset) {
            /** @phpstan-ignore-next-line */
            $this->asset = $env->getExtension(AssetExtension::class);
        }

        return $this->asset->getAssetUrl($path, $packageName);
    }

    /**
     * Generates a random nonce parameter.
     *
     * @param Environment $env the Twig environnement
     *
     * @return string the random nonce parameter
     */
    private function getNonce(Environment $env): string
    {
        if (null === $this->nonce) {
            /** @var NonceExtension $extension */
            $extension = $env->getExtension(NonceExtension::class);
            $this->nonce = $extension->getNonce();
        }

        return $this->nonce;
    }

    /**
     * Reduce parameters with a key/value tags.
     *
     * @param array $parameters the parameters to process
     *
     * @return string the reduced parameters
     */
    private function reduceParameters(array $parameters): string
    {
        if (!empty($parameters)) {
            $callback = function (string $carry, $key, $value) {
                return $carry . ' ' . $key . '="' . \htmlspecialchars((string) $value) . '"';
            };

            return Utils::arrayReduceKey($parameters, $callback, '');
        }

        return '';
    }
}
