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
use App\Entity\User;
use App\Interfaces\EntityInterface;
use App\Service\NonceService;
use App\Service\UrlGeneratorService;
use App\Traits\ImageSizeTrait;
use App\Utils\FileUtils;
use App\Utils\StringUtils;
use Symfony\Bridge\Twig\Extension\AssetExtension;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

/**
 * Twig extension for assets.
 */
final class FunctionExtension extends AbstractExtension
{
    use ImageSizeTrait;

    private readonly string $publicDir;

    public function __construct(
        #[Autowire('%kernel.project_dir%/public')]
        string $publicDir,
        #[Autowire(service: 'twig.extension.assets')]
        private readonly AssetExtension $extension,
        private readonly NonceService $service,
        private readonly UploaderHelper $helper,
        private readonly UrlGeneratorService $generator,
    ) {
        $this->publicDir = FileUtils::normalize($publicDir);
    }

    #[\Override]
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
            new TwigFunction('asset_image_user', $this->assetImageUser(...), $options),

            // routes
            new TwigFunction('cancel_url', $this->cancelUrl(...)),
            new TwigFunction('route_params', $this->routeParams(...)),
        ];
    }

    /**
     * Output a link style sheet tag within a nonce token.
     *
     * @param array<string, string|int> $parameters
     */
    private function assetCss(string $path, array $parameters = []): string
    {
        $parameters = \array_merge([
            'href' => $this->getAssetUrl($path),
            'nonce' => $this->getNonce(),
            'rel' => 'stylesheet',
        ], $parameters);
        $attributes = $this->reduceParams($parameters);

        return "<link $attributes>";
    }

    /**
     * Checks if the given asset path exists.
     */
    private function assetExists(?string $path = null): bool
    {
        $file = $this->getRealPath($path);
        if (!StringUtils::isString($file)) {
            return false;
        }

        return StringUtils::startWith($file, $this->publicDir);
    }

    /**
     * Gets an application icon.
     */
    private function assetIcon(int $size): string
    {
        $path = \sprintf('images/icons/favicon-%1$dx%1$d.png', $size);

        return $this->getAssetUrl($path);
    }

    /**
     * Output an image tag with a version.
     *
     * @param array<string, string|int> $parameters
     */
    private function assetImage(string $path, array $parameters = []): string
    {
        [$width, $height] = $this->imageSize($path);
        $parameters = \array_merge([
            'src' => $this->getAssetUrl($path),
            'height' => $height,
            'width' => $width,
        ], $parameters);
        $attributes = $this->reduceParams($parameters);

        return "<image $attributes>";
    }

    /**
     * Output the user image profile.
     *
     * @param User|array|null $user the user
     *
     * @psalm-param array<string, string|int> $parameters
     */
    private function assetImageUser(User|array|null $user, ?string $size = null, array $parameters = []): string|false
    {
        if (null === $user || [] === $user) {
            return false;
        }
        $asset = $this->helper->asset($user, className: User::class);
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
     * Output a JavaScript source tag within a nonce token.
     *
     * @param array<string, string|int> $parameters
     */
    private function assetJs(string $path, array $parameters = []): string
    {
        $parameters = \array_merge([
            'src' => $this->getAssetUrl($path),
            'nonce' => $this->getNonce(),
        ], $parameters);
        $attributes = $this->reduceParams($parameters);

        return "<script $attributes></script>";
    }

    /**
     * Gets the cancel URL.
     */
    private function cancelUrl(
        Request $request,
        EntityInterface|int|null $id = 0,
        string $defaultRoute = AbstractController::HOME_PAGE
    ): string {
        return $this->generator->cancelUrl($request, $id, $defaultRoute);
    }

    /**
     * Returns the public url/path of an asset.
     */
    private function getAssetUrl(string $path): string
    {
        return $this->extension->getAssetUrl($path);
    }

    private function getNonce(): string
    {
        return $this->service->getNonce();
    }

    /**
     * Gets the real (absolute) file path or null if not exist.
     */
    private function getRealPath(?string $path): ?string
    {
        if (!StringUtils::isString($path)) {
            return null;
        }
        $path = FileUtils::buildPath($this->publicDir, $path);
        $file = \realpath($path);
        if (false === $file) {
            return null;
        }

        return FileUtils::normalize($file);
    }

    /**
     * Gets the image size.
     *
     * @return array{0: int, 1: int}
     */
    private function imageSize(string $path): array
    {
        $full_path = (string) $this->getRealPath($path);

        return $this->getImageSize($full_path);
    }

    /**
     * Reduce parameters.
     *
     * @param array<string, string|int> $parameters
     */
    private function reduceParams(array $parameters): string
    {
        $callback = static fn (string $key, string|int $value): string => \sprintf('%s="%s"', $key, \htmlspecialchars((string) $value));

        return \implode(' ', \array_map($callback, \array_keys($parameters), \array_values($parameters)));
    }

    /**
     * Gets the route parameters.
     */
    private function routeParams(Request $request, EntityInterface|int|null $id = 0): array
    {
        return $this->generator->routeParams($request, $id);
    }
}
