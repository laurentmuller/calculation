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

use App\Entity\User;
use App\Model\ImageSize;
use App\Service\ImageResizer;
use App\Service\NonceService;
use App\Traits\ImageSizeTrait;
use App\Utils\FileUtils;
use App\Utils\StringUtils;
use Symfony\Bridge\Twig\Extension\AssetExtension;
use Symfony\Bridge\Twig\Extension\WebLinkExtension;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Filesystem\Path;
use Twig\Attribute\AsTwigFunction;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

/**
 * Twig extension for assets.
 */
final readonly class FunctionExtension
{
    use ImageSizeTrait;

    private string $publicDir;

    public function __construct(
        #[Autowire('%kernel.project_dir%/public')]
        string $publicDir,
        #[Autowire(service: 'twig.extension.assets')]
        private AssetExtension $assetExtension,
        #[Autowire(service: 'twig.extension.weblink')]
        private WebLinkExtension $webLinkExtension,
        private NonceService $nonceService,
        private UploaderHelper $uploaderHelper,
    ) {
        $this->publicDir = FileUtils::normalize($publicDir);
    }

    /**
     * Output a link style sheet tag within a nonce token.
     *
     * @param array<string, string|int> $parameters
     */
    #[AsTwigFunction(name: 'asset_css', isSafe: ['html'])]
    public function assetCss(string $path, array $parameters = []): string
    {
        $parameters = \array_merge([
            'href' => $this->getAssetUrl($path),
            'nonce' => $this->getNonce(),
            'rel' => 'stylesheet',
        ], $parameters);

        return $this->reduceParams($parameters, '<link ');
    }

    /**
     * Checks if the given asset path exists.
     *
     * @phpstan-assert-if-true non-empty-string $path
     */
    #[AsTwigFunction(name: 'asset_exists')]
    public function assetExists(?string $path = null): bool
    {
        $file = $this->getRealPath($path);

        return StringUtils::isString($file) && StringUtils::startWith($file, $this->publicDir);
    }

    /**
     * Gets an application icon.
     */
    #[AsTwigFunction(name: 'asset_icon', isSafe: ['html'])]
    public function assetIcon(int $size): string
    {
        $path = \sprintf('images/icons/favicon-%1$dx%1$d.png', $size);

        return $this->getAssetUrl($path);
    }

    /**
     * Output an image tag with a version.
     *
     * @param array<string, string|int> $parameters
     */
    #[AsTwigFunction(name: 'asset_image', isSafe: ['html'])]
    public function assetImage(string $path, array $parameters = []): string
    {
        $size = $this->imageSize($path);
        $parameters = \array_merge([
            'src' => $this->getAssetUrl($path),
            'height' => $size->height,
            'width' => $size->width,
        ], $parameters);

        return $this->reduceParams($parameters, '<image ');
    }

    /**
     * Output the user image profile.
     *
     * @param array<string, string|int> $parameters additional parameters
     */
    #[AsTwigFunction(name: 'asset_image_user', isSafe: ['html'])]
    public function assetImageUser(
        User|array|null $user,
        int $size = ImageResizer::IMAGE_SIZE,
        array $parameters = []
    ): string|false {
        if (null === $user || [] === $user) {
            return false;
        }

        $asset = $this->uploaderHelper->asset(obj: $user, className: User::class);
        if (!$this->assetExists($asset)) {
            return false;
        }

        if (ImageResizer::IMAGE_SIZE !== $size) {
            $imageSize = $this->imageSize($asset)
                ->resize($size);
            $parameters = \array_merge([
                'height' => $imageSize->height,
                'width' => $imageSize->width,
            ], $parameters);
        }

        return $this->assetImage(\ltrim($asset, '/'), $parameters);
    }

    /**
     * Output a JavaScript source tag within a nonce token.
     *
     * @param array<string, string|int> $parameters
     */
    #[AsTwigFunction(name: 'asset_js', isSafe: ['html'])]
    public function assetJs(string $path, array $parameters = []): string
    {
        $parameters = \array_merge([
            'src' => $this->getAssetUrl($path),
            'nonce' => $this->getNonce(),
        ], $parameters);

        return $this->reduceParams($parameters, '<script ', '></script>');
    }

    /**
     * Output a preload style sheet tag within a nonce token.
     *
     * @param array<string, string|int> $parameters
     */
    #[AsTwigFunction(name: 'preload_css', isSafe: ['html'])]
    public function preloadCss(string $path, array $parameters = []): string
    {
        $url = $this->getAssetUrl($path);
        $href = $this->webLinkExtension->preload($url, ['as' => 'style']);
        $parameters = \array_merge([
            'rel' => 'preload',
            'href' => $href,
            'as' => 'style',
        ], $parameters);

        return $this->assetCss($path, $parameters);
    }

    /**
     * Returns the public url/path of an asset.
     */
    private function getAssetUrl(string $path): string
    {
        return $this->assetExtension->getAssetUrl($path);
    }

    private function getNonce(): string
    {
        return $this->nonceService->getNonce();
    }

    /**
     * Gets the real (absolute) file path or null if not exist.
     */
    private function getRealPath(?string $path): ?string
    {
        if (!StringUtils::isString($path)) {
            return null;
        }
        $file = Path::join($this->publicDir, $path);

        return \file_exists($file) ? $file : null;
    }

    /**
     * Gets the image size.
     */
    private function imageSize(string $path): ImageSize
    {
        $realPath = (string) $this->getRealPath($path);

        return $this->getImageSize($realPath);
    }

    /**
     * Reduce parameters.
     *
     * @param array<string, string|int> $parameters
     */
    private function reduceParams(array $parameters, string $prefix, string $suffix = '>'): string
    {
        $parameters = \array_map(
            static fn (string $key, string|int $value): string => \sprintf('%s="%s"', $key, $value),
            \array_keys($parameters),
            \array_values($parameters)
        );

        return $prefix . \implode(' ', $parameters) . $suffix;
    }
}
