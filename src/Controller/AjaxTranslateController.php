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

namespace App\Controller;

use App\Attribute\Get;
use App\Attribute\Post;
use App\Interfaces\RoleInterface;
use App\Model\HttpClientError;
use App\Model\TranslateQuery;
use App\Translator\TranslatorFactory;
use App\Translator\TranslatorServiceInterface;
use App\Utils\StringUtils;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Controller for translation XMLHttpRequest (Ajax) calls.
 */
#[AsController]
#[Route(path: '/ajax')]
class AjaxTranslateController extends AbstractController
{
    public function __construct(private readonly TranslatorFactory $factory)
    {
    }

    /**
     * Identifies the language of a piece of text.
     *
     * @throws ServiceNotFoundException if the service is not found
     */
    #[IsGranted(RoleInterface::ROLE_USER)]
    #[Get(path: '/detect', name: 'ajax_detect')]
    public function detect(
        #[MapQueryParameter]
        ?string $text = null,
        #[MapQueryParameter(name: 'service')]
        ?string $class = null
    ): JsonResponse {
        if (!StringUtils::isString($text)) {
            return $this->jsonFalse([
                'message' => $this->trans('translator.text_error'),
            ]);
        }

        try {
            $service = $this->getService($class);
            $result = $service->detect($text);
            if (\is_array($result)) {
                return $this->jsonTrue([
                    'service' => $service::getName(),
                    'data' => $result,
                ]);
            }

            return $this->handleError($service, 'translator.detect_error');
        } catch (\Exception $e) {
            return $this->jsonException($e, $this->trans('translator.detect_error'));
        }
    }

    /**
     * Gets the list of translate languages.
     *
     * @throws ServiceNotFoundException if the service is not found
     */
    #[IsGranted(RoleInterface::ROLE_USER)]
    #[Get(path: '/languages', name: 'ajax_languages')]
    public function languages(#[MapQueryParameter(name: 'service')] ?string $class = null): JsonResponse
    {
        try {
            $service = $this->getService($class);
            $languages = $service->getLanguages();
            if (\is_array($languages)) {
                return $this->jsonTrue([
                    'languages' => $languages,
                ]);
            }

            return $this->handleError($service, 'translator.languages_error');
        } catch (\Exception $e) {
            return $this->jsonException($e, $this->trans('translator.languages_error'));
        }
    }

    /**
     * Translate a text.
     *
     * @throws ServiceNotFoundException if the service is not found
     */
    #[IsGranted(RoleInterface::ROLE_USER)]
    #[Post(path: '/translate', name: 'ajax_translate')]
    public function translate(#[MapRequestPayload] TranslateQuery $query): JsonResponse
    {
        if (empty($query->text)) {
            return $this->jsonFalse([
                'message' => $this->trans('translator.text_error'),
            ]);
        }
        if (empty($query->to)) {
            return $this->jsonFalse([
                'message' => $this->trans('translator.to_error'),
            ]);
        }

        try {
            $service = $this->getService($query->service);
            $result = $service->translate($query);
            if (\is_array($result)) {
                return $this->jsonTrue([
                    'service' => $service::getName(),
                    'data' => $result,
                ]);
            }

            return $this->handleError($service, 'translator.translate_error');
        } catch (\Exception $e) {
            return $this->jsonException($e, $this->trans('translator.translate_error'));
        }
    }

    /**
     * @throws ServiceNotFoundException if the service is not found
     */
    private function getService(?string $class = null): TranslatorServiceInterface
    {
        return $this->factory->getService($class ?? TranslatorFactory::DEFAULT_SERVICE);
    }

    private function handleError(TranslatorServiceInterface $service, string $message): JsonResponse
    {
        $error = $service->getLastError();
        if ($error instanceof HttpClientError) {
            $id = \sprintf('%s.%s', $service->getName(), $error->getCode());
            if ($this->isTransDefined($id, 'translator')) {
                $error->setMessage($this->trans($id, [], 'translator'));
            }

            return $this->jsonFalse([
                'message' => $this->trans($message),
                'exception' => $error,
            ]);
        }

        return $this->jsonFalse([
            'message' => $this->trans($message),
        ]);
    }
}
