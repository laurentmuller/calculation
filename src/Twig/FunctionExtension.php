<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\Twig;

use App\Controller\AbstractController;
use App\Interfaces\RoleInterface;
use App\Service\UrlGeneratorService;
use App\Traits\RoleTranslatorTrait;
use App\Util\FileUtils;
use App\Util\Utils;
use Symfony\Bridge\Twig\Extension\AssetExtension;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

/**
 * Twig extension for the application.
 */
final class FunctionExtension extends AbstractExtension
{
    use RoleTranslatorTrait;
    /**
     * The asset extension.
     */
    private ?AssetExtension $asset = null;

    /**
     * The nonce value.
     */
    private ?string $nonce = null;

    /**
     * The file version.
     */
    private readonly int $version;

    /**
     * The real path of the public directory.
     */
    private readonly string $webDir;

    /**
     * Constructor.
     */
    public function __construct(KernelInterface $kernel, private readonly TranslatorInterface $translator, private readonly UrlGeneratorService $generator)
    {
        $projectDir = $kernel->getProjectDir();
        $filename = FileUtils::buildPath($projectDir, 'composer.lock');
        $this->webDir = (string) \realpath(FileUtils::buildPath($projectDir, 'public'));
        $this->version = $this->fileExists($filename) ? (int) \filemtime($filename) : Kernel::VERSION_ID;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('trans_role', fn (RoleInterface|string $role): string => $this->translateRole($role)),
            new TwigFilter('var_export', fn (mixed $expression): ?string => Utils::exportVar($expression)),
            new TwigFilter('normalize_whitespace', fn (string $value): string => $this->normalizeWhitespace($value), ['preserves_safety' => ['html']]),
        ];
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
            // assets
            new TwigFunction('asset_exists', fn (?string $path): bool => $this->assetExists($path)),
            new TwigFunction('asset_if', fn (?string $path = null, ?string $default = null): ?string => $this->assetIf($path, $default)),
            new TwigFunction('asset_js', fn (Environment $env, string $path, array $parameters = [], ?string $packageName = null): string => $this->getAssetJs($env, $path, $parameters, $packageName), $assetOptions),
            new TwigFunction('asset_css', fn (Environment $env, string $path, array $parameters = [], ?string $packageName = null): string => $this->getAssetCss($env, $path, $parameters, $packageName), $assetOptions),
            new TwigFunction('asset_versioned', fn (Environment $env, string $path, ?string $packageName = null): string => $this->getVersionedAsset($env, $path, $packageName), $assetOptions),
            new TwigFunction('asset_time', fn (?string $path): int => $this->getAssetVersion($path)),

            // images
            new TwigFunction('image_height', fn (string $path): int => $this->getImageHeight($path)),
            new TwigFunction('image_width', fn (string $path): int => $this->getImageWidth($path)),

            // routes
            new TwigFunction('cancel_url', fn (Request $request, int $id = 0, string $defaultRoute = AbstractController::HOME_PAGE): string => $this->cancelUrl($request, $id, $defaultRoute)),
            new TwigFunction('route_params', fn (Request $request, int $id = 0): array => $this->routeParams($request, $id)),

            // php
            new TwigFunction('is_int', 'is_int'),
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }

    /**
     * Checks if the given asset path exists.
     */
    private function assetExists(?string $path): bool
    {
        if (null === $file = $this->getRealPath($path)) {
            return false;
        }

        // check if file is well contained in public/ directory (prevents ../ in paths)
        return 0 === \strncmp($this->webDir, $file, \strlen($this->webDir));
    }

    /**
     * Returns the given asset path, if valid; the default path otherwise.
     */
    private function assetIf(?string $path = null, ?string $default = null): ?string
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
     */
    private function cancelUrl(Request $request, int $id = 0, string $defaultRoute = AbstractController::HOME_PAGE): string
    {
        return $this->generator->cancelUrl($request, $id, $defaultRoute);
    }

    /**
     * Checks the existence of file or directory.
     */
    private function fileExists(?string $filename): bool
    {
        return null !== $filename && FileUtils::exists($filename);
    }

    /**
     * Output a link style sheet tag with a version and nonce.
     *
     * @throws \Exception
     */
    private function getAssetCss(Environment $env, string $path, array $parameters = [], ?string $packageName = null): string
    {
        $href = $this->getVersionedAsset($env, $path, $packageName);
        $parameters = \array_merge([
            'href' => $href,
            'rel' => 'stylesheet',
            'nonce' => $this->getNonce($env),
        ], $parameters);
        $attributes = $this->reduceParameters($parameters);

        return "<link$attributes>";
    }

    /**
     * Output a javascript source tag with a version and nonce.
     *
     * @throws \Exception
     */
    private function getAssetJs(Environment $env, string $path, array $parameters = [], ?string $packageName = null): string
    {
        $src = $this->getVersionedAsset($env, $path, $packageName);
        $parameters = \array_merge([
            'src' => $src,
            'nonce' => $this->getNonce($env),
        ], $parameters);
        $attributes = $this->reduceParameters($parameters);

        return "<script$attributes></script>";
    }

    /**
     * Returns the public url/path of an asset.
     *
     * If the package used to generate the path is an instance of
     * UrlPackage, you will always get a URL and not a path.
     */
    private function getAssetUrl(Environment $env, string $path, ?string $packageName = null): string
    {
        if (null === $this->asset) {
            $this->asset = $this->getExtension($env, AssetExtension::class);
        }

        return $this->asset->getAssetUrl($path, $packageName);
    }

    /**
     * Gets the version for the given path.
     */
    private function getAssetVersion(?string $path): int
    {
        if (null !== $file = $this->getRealPath($path)) {
            return (int) \filemtime($file);
        }

        return $this->version;
    }

    /**
     * Gets a Twig extension.
     *
     * @template T of \Twig\Extension\ExtensionInterface
     * @psalm-param class-string<T> $className
     * @psalm-return T
     */
    private function getExtension(Environment $env, string $className)
    {
        return $env->getExtension($className);
    }

    /**
     * Gets the image height.
     */
    private function getImageHeight(string $path): int
    {
        return $this->getImageSize($path)[1];
    }

    /**
     * Gets the image size.
     *
     * @return array{0: int, 1: int}
     */
    private function getImageSize(string $path): array
    {
        $fullPath = (string) \realpath($this->webDir . $path);
        /** @psalm-var array{0: int, 1: int} $size */
        $size = (array) \getimagesize($fullPath);

        return $size;
    }

    /**
     * Gets the image width.
     */
    private function getImageWidth(string $path): int
    {
        return $this->getImageSize($path)[0];
    }

    /**
     * Generates a random nonce parameter.
     *
     * @throws \Exception
     */
    private function getNonce(Environment $env): string
    {
        if (null === $this->nonce) {
            $this->nonce = $this->getExtension($env, NonceExtension::class)->getNonce();
        }

        return $this->nonce;
    }

    /**
     * Gets the real (absolute) path or null if not exist.
     */
    private function getRealPath(?string $path): ?string
    {
        // empty?
        if (empty($path) || empty($this->webDir)) {
            return null;
        }

        // real path?
        $join_path = \implode('/', [$this->webDir, $path]);
        if (false === $file = \realpath($join_path)) {
            return null;
        }

        // file exist?
        if (!FileUtils::isFile($file)) {
            return null;
        }

        return $file;
    }

    /**
     * Gets an asset with version.
     */
    private function getVersionedAsset(Environment $env, string $path, ?string $packageName = null): string
    {
        $url = $this->getAssetUrl($env, $path, $packageName);
        $version = $this->getAssetVersion($path);

        return \sprintf('%s?version=%d', $url, $version);
    }

    /**
     * This filter replaces duplicated spaces and/or linebreaks with single space.
     *
     * It also removes whitespace from the beginning and at the end of the string.
     */
    private function normalizeWhitespace(string $value): string
    {
        // attributes
        $value = (string) \preg_replace('/\s+=\s+/u', '=', $value);

        // space and new lines
        $value = (string) \preg_replace('/\s+/u', ' ', $value);

        return \trim($value);
    }

    /**
     * Reduce parameters with a key/value tags.
     */
    private function reduceParameters(array $parameters): string
    {
        if (!empty($parameters)) {
            $callback = static fn (string $carry, string $key): string => $carry . ' ' . $key . '="' . \htmlspecialchars((string) $parameters[$key]) . '"';

            return (string) Utils::arrayReduceKey($parameters, $callback, '');
        }

        return '';
    }

    /**
     * Gets the route parameters.
     */
    private function routeParams(Request $request, int $id = 0): array
    {
        return $this->generator->routeParams($request, $id);
    }
}
