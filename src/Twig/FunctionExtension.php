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
use App\Controller\ThemeController;
use App\Entity\User;
use App\Service\NonceService;
use App\Service\UrlGeneratorService;
use App\Traits\RoleTranslatorTrait;
use App\Util\FileUtils;
use App\Util\Utils;
use Symfony\Bridge\Twig\Extension\AssetExtension;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Kernel;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

/**
 * Twig extension for the application.
 */
final class FunctionExtension extends AbstractExtension
{
    use RoleTranslatorTrait;

    /**
     * The file version.
     */
    private readonly int $version;

    /**
     * The asset versions.
     *
     * @var array<string, int>
     */
    private array $versions = [];

    /**
     * The real path of the public directory.
     */
    private readonly string $webDir;

    /**
     * Constructor.
     */
    public function __construct(
        #[Autowire('%kernel.project_dir%')]
        string $projectDir,
        #[Autowire('%kernel.debug%')]
        private readonly bool $debug,
        #[Autowire(service: 'twig.extension.assets')]
        private readonly AssetExtension $extension,
        private readonly NonceService $service,
        private readonly UploaderHelper $helper,
        private readonly UrlGeneratorService $generator,
        private readonly TranslatorInterface $translator
    ) {
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
            new TwigFilter('trans_role', $this->translateRole(...)),
            new TwigFilter('var_export', Utils::exportVar(...)),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions(): array
    {
        $options = ['is_safe' => ['html']];

        return [
            // assets
            new TwigFunction('asset_exists', $this->assetExists(...)),
            new TwigFunction('asset_js', $this->assetJs(...), $options),
            new TwigFunction('asset_css', $this->assetCss(...), $options),
            new TwigFunction('asset_icon', $this->assetIcon(...), $options),
            new TwigFunction('asset_image', $this->assetImage(...), $options),
            new TwigFunction('asset_versioned', $this->versionedAsset(...), $options),
            new TwigFunction('asset_image_user', $this->assetImageUser(...), $options),
            // routes
            new TwigFunction('cancel_url', $this->cancelUrl(...)),
            new TwigFunction('route_params', $this->routeParams(...)),
            // php
            new TwigFunction('is_int', 'is_int'),
            // theme
            new TwigFunction('theme_dark', $this->isDarkTheme(...)),
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
     * Output a link style sheet tag with a version and nonce.
     *
     * @throws \Exception
     */
    private function assetCss(string $path, array $parameters = [], ?string $packageName = null): string
    {
        $href = $this->versionedAsset($path, $packageName);
        $parameters = \array_merge([
            'href' => $href,
            'rel' => 'stylesheet',
            'nonce' => $this->service->getNonce(),
        ], $parameters);
        $attributes = $this->reduceParameters($parameters);

        return "<link$attributes>";
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
     * Gets an application icon.
     */
    private function assetIcon(int $size): string
    {
        $path = \sprintf('images/icons/favicon-%1$dx%1$d.png', $size);

        return $this->assetUrl($path);
    }

    /**
     * Output an image tag with a version.
     *
     * @throws \Exception
     */
    private function assetImage(string $path, array $parameters = [], ?string $packageName = null): string
    {
        [$width, $height] = $this->imageSize($path);
        $src = $this->versionedAsset($path, $packageName);
        $parameters = \array_merge([
            'src' => $src,
            'width' => $width,
            'height' => $height,
        ], $parameters);
        $attributes = $this->reduceParameters($parameters);

        return "<image$attributes>";
    }

    /**
     * Output the user image profile.
     *
     * @throws \Exception
     */
    private function assetImageUser(?User $user, ?string $size = null, array $parameters = []): string|false
    {
        if (null === $user) {
            return false;
        }
        $asset = $this->helper->asset($user);
        if (null === $asset) {
            return false;
        }
        if (null !== $size) {
            $asset = \str_replace('192', $size, $asset);
        }
        if (!$this->assetExists($asset)) {
            return false;
        }

        return $this->assetImage(\ltrim($asset, '/'), $parameters);
    }

    /**
     * Output a javascript source tag with a version and nonce.
     *
     * @throws \Exception
     */
    private function assetJs(string $path, array $parameters = [], ?string $packageName = null): string
    {
        $src = $this->versionedAsset($path, $packageName);
        $parameters = \array_merge([
            'src' => $src,
            'nonce' => $this->service->getNonce(),
        ], $parameters);
        $attributes = $this->reduceParameters($parameters);

        return "<script$attributes></script>";
    }

    /**
     * Returns the public url/path of an asset.
     */
    private function assetUrl(string $path, ?string $packageName = null): string
    {
        return $this->extension->getAssetUrl($path, $packageName);
    }

    /**
     * Gets the version for the given path.
     */
    private function assetVersion(?string $path): int
    {
        if ($this->debug || null === $realPath = $this->getRealPath($path)) {
            return $this->version;
        }
        if (!isset($this->versions[$realPath])) {
            $this->versions[$realPath] = (int) \filemtime($realPath);
        }

        return $this->versions[$realPath];
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

        return [$size[0], $size[1]];
    }

    /**
     * Returns if the selected theme is dark.
     */
    private function isDarkTheme(Request $request): bool
    {
        return $request->cookies->getBoolean(ThemeController::KEY_DARK);
    }

    /**
     * Reduce parameters with a key/value tags.
     */
    private function reduceParameters(array $parameters): string
    {
        $callback = static fn (string $carry, string $key, mixed $value): string => \sprintf('%s %s="%s"', $carry, $key, \htmlspecialchars((string) $value));

        return (string) Utils::arrayReduceKey($parameters, $callback, '');
    }

    /**
     * Gets the route parameters.
     */
    private function routeParams(Request $request, int $id = 0): array
    {
        return $this->generator->routeParams($request, $id);
    }

    /**
     * Gets an asset with version.
     */
    private function versionedAsset(string $path, ?string $packageName = null): string
    {
        $url = $this->assetUrl($path, $packageName);
        $version = $this->assetVersion($path);

        return \sprintf('%s?version=%d', $url, $version);
    }
}
