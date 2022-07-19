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
     * Output a link style sheet tag with a version and nonce.
     *
     * @throws \Exception
     */
    public function assetCss(Environment $env, string $path, array $parameters = [], ?string $packageName = null): string
    {
        $href = $this->versionedAsset($env, $path, $packageName);
        $parameters = \array_merge([
            'href' => $href,
            'rel' => 'stylesheet',
            'nonce' => $this->getNonce($env),
        ], $parameters);
        $attributes = $this->reduceParameters($parameters);

        return "<link$attributes>";
    }

    /**
     * Checks if the given asset path exists.
     */
    public function assetExists(?string $path): bool
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
     * Output an image tag with a version.
     *
     * @throws \Exception
     */
    public function assetImage(Environment $env, string $path, array $parameters = [], ?string $packageName = null): string
    {
        $size = $this->imageSize($path);
        $src = $this->versionedAsset($env, $path, $packageName);
        $parameters = \array_merge([
            'src' => $src,
            'width' => $size[0],
            'height' => $size[1],
        ], $parameters);
        $attributes = $this->reduceParameters($parameters);

        return "<image$attributes>";
    }

    /**
     * Output a javascript source tag with a version and nonce.
     *
     * @throws \Exception
     */
    public function assetJs(Environment $env, string $path, array $parameters = [], ?string $packageName = null): string
    {
        $src = $this->versionedAsset($env, $path, $packageName);
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
    public function assetUrl(Environment $env, string $path, ?string $packageName = null): string
    {
        if (null === $this->asset) {
            $this->asset = $this->getExtension($env, AssetExtension::class);
        }

        return $this->asset->getAssetUrl($path, $packageName);
    }

    /**
     * Gets the version for the given path.
     */
    public function assetVersion(?string $path): int
    {
        if (null !== $file = $this->getRealPath($path)) {
            return (int) \filemtime($file);
        }

        return $this->version;
    }

    /**
     * Gets the cancel URL.
     */
    public function cancelUrl(Request $request, int $id = 0, string $defaultRoute = AbstractController::HOME_PAGE): string
    {
        return $this->generator->cancelUrl($request, $id, $defaultRoute);
    }

    /**
     * Checks the existence of file or directory.
     */
    public function fileExists(?string $filename): bool
    {
        return null !== $filename && FileUtils::exists($filename);
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
     * {@inheritdoc}
     */
    public function getFunctions(): array
    {
        $options = [
            'is_safe' => ['html'],
            'needs_environment' => true,
        ];

        return [
            // assets
            new TwigFunction('asset_if', [$this, 'assetIf']),
            new TwigFunction('asset_exists', [$this, 'assetExists']),
            new TwigFunction('asset_js', [$this, 'assetJs'], $options),
            new TwigFunction('asset_css', [$this, 'assetCss'], $options),
            new TwigFunction('asset_image', [$this, 'assetImage'], $options),
            new TwigFunction('asset_versioned', [$this, 'versionedAsset'], $options),
            // routes
            new TwigFunction('cancel_url', [$this, 'cancelUrl']),
            new TwigFunction('route_params', [$this, 'routeParams']),
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
     * This filter replaces duplicated spaces and/or linebreaks with single space.
     */
    public function normalizeWhitespace(string $value): string
    {
        // attributes
        $value = (string) \preg_replace('/\s+=\s+/u', '=', $value);

        // space and new lines
        $value = (string) \preg_replace('/\s+/u', ' ', $value);

        return \trim($value);
    }

    /**
     * Gets the route parameters.
     */
    public function routeParams(Request $request, int $id = 0): array
    {
        return $this->generator->routeParams($request, $id);
    }

    /**
     * Gets an asset with version.
     */
    public function versionedAsset(Environment $env, string $path, ?string $packageName = null): string
    {
        $url = $this->assetUrl($env, $path, $packageName);
        $version = $this->assetVersion($path);

        return \sprintf('%s?version=%d', $url, $version);
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
        $full_path = \implode('/', [$this->webDir, $path]);
        if (false === $file = \realpath($full_path)) {
            return null;
        }

        // file exist?
        if (!FileUtils::isFile($file)) {
            return null;
        }

        return $file;
    }

    /**
     * Gets the image size.
     *
     * @return array{0: int, 1: int}
     */
    private function imageSize(string $path): array
    {
        $full_path = (string) $this->getRealPath($path);
        /** @psalm-var array{0: int, 1: int} $size */
        $size = (array) \getimagesize($full_path);

        return $size;
    }

    /**
     * Reduce parameters with a key/value tags.
     */
    private function reduceParameters(array $parameters): string
    {
        $callback = static fn (string $carry, string $key, mixed $value): string => \sprintf('%s %s="%s"', $carry, $key, \htmlspecialchars((string) $value));

        return (string) Utils::arrayReduceKey($parameters, $callback, '');
    }
}
