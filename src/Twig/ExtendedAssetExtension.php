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
use Twig\Environment;
use Twig\Extra\Html\HtmlExtension;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

/**
 * Twig extension for assets.
 */
final readonly class ExtendedAssetExtension
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
    #[AsTwigFunction(name: 'asset_css', needsEnvironment: true, isSafe: ['html'])]
    public function assetCss(Environment $env, string $path, array $parameters = []): string
    {
        $parameters = \array_merge([
            'rel' => 'stylesheet',
            'href' => $this->getAssetUrl($path),
            'nonce' => $this->getNonce(),
        ], $parameters);

        return $this->reduceParams($env, $parameters, 'link');
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
    #[AsTwigFunction(name: 'asset_image', needsEnvironment: true, isSafe: ['html'])]
    public function assetImage(Environment $env, string $path, array $parameters = []): string
    {
        $size = $this->imageSize($path);
        $parameters = \array_merge([
            'src' => $this->getAssetUrl($path),
            'height' => $size->height,
            'width' => $size->width,
            'loading' => 'lazy',
            'decoding' => 'async',
        ], $parameters);

        return $this->reduceParams($env, $parameters, 'image');
    }

    /**
     * Output the user image profile.
     *
     * @param array<string, string|int> $parameters additional parameters
     */
    #[AsTwigFunction(name: 'asset_image_user', needsEnvironment: true, isSafe: ['html'])]
    public function assetImageUser(
        Environment $env,
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

        return $this->assetImage($env, \ltrim($asset, '/'), $parameters);
    }

    /**
     * Output a JavaScript source tag within a nonce token.
     *
     * @param array<string, string|int> $parameters
     */
    #[AsTwigFunction(name: 'asset_js', needsEnvironment: true, isSafe: ['html'])]
    public function assetJs(Environment $env, string $path, array $parameters = []): string
    {
        $parameters = \array_merge([
            'src' => $this->getAssetUrl($path),
            'nonce' => $this->getNonce(),
        ], $parameters);

        return $this->reduceParams($env, $parameters, 'script', '</script>');
    }

    /**
     * Output a preload style sheet tag within a nonce token.
     *
     * @param array<string, string|int> $parameters
     */
    #[AsTwigFunction(name: 'preload_css', needsEnvironment: true, isSafe: ['html'])]
    public function preloadCss(Environment $env, string $path, array $parameters = []): string
    {
        $url = $this->getAssetUrl($path);
        $href = $this->webLinkExtension->preload($url, ['as' => 'style']);
        $parameters = \array_merge([
            'rel' => 'preload',
            'href' => $href,
            'as' => 'style',
            'nonce' => $this->getNonce(),
        ], $parameters);

        return $this->reduceParams($env, $parameters, 'link');
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
     * @param array<string, scalar|\BackedEnum> $parameters
     */
    private function reduceParams(Environment $env, array $parameters, string $prefix, string $suffix = ''): string
    {
        foreach ($parameters as &$value) {
            if (\is_bool($value)) {
                $value = \json_encode($value);
            }
        }
        $attributes = HtmlExtension::htmlAttr($env, $parameters);

        return \sprintf('<%s %s>%s', $prefix, $attributes, $suffix);
    }
}
