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

use App\Interfaces\RoleInterface;
use App\Model\HttpClientError;
use App\Translator\TranslatorFactory;
use App\Translator\TranslatorServiceInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Annotation\Route;
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
     * @throws \Psr\Container\ContainerExceptionInterface if the service is not found
     */
    #[IsGranted(RoleInterface::ROLE_USER)]
    #[Route(path: '/detect', name: 'ajax_detect', methods: Request::METHOD_GET)]
    public function detect(
        #[MapQueryParameter] string $text = null,
        #[MapQueryParameter(name: 'service')] string $class = null
    ): JsonResponse {
        if (empty($text)) {
            return $this->jsonFalse([
                'message' => $this->trans('translator.text_error'),
            ]);
        }

        try {
            $service = $this->getService($class);
            if ($result = $service->detect($text)) {
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
     * @throws \Psr\Container\ContainerExceptionInterface if the service is not found
     */
    #[IsGranted(RoleInterface::ROLE_USER)]
    #[Route(path: '/languages', name: 'ajax_languages', methods: Request::METHOD_GET)]
    public function languages(#[MapQueryParameter(name: 'service')] string $class = null): JsonResponse
    {
        try {
            $service = $this->getService($class);
            if ($languages = $service->getLanguages()) {
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
     * @throws \Psr\Container\ContainerExceptionInterface if the service is not found
     */
    #[IsGranted(RoleInterface::ROLE_USER)]
    #[Route(path: '/translate', name: 'ajax_translate', methods: Request::METHOD_GET)]
    public function translate(
        #[MapQueryParameter] string $text = null,
        #[MapQueryParameter] string $from = '',
        #[MapQueryParameter] string $to = '',
        #[MapQueryParameter(name: 'service')] string $class = null
    ): JsonResponse {
        if (empty($text)) {
            return $this->jsonFalse([
                'message' => $this->trans('translator.text_error'),
            ]);
        }
        if (empty($to)) {
            return $this->jsonFalse([
                'message' => $this->trans('translator.to_error'),
            ]);
        }

        try {
            $service = $this->getService($class);
            if ($result = $service->translate($text, $to, $from)) {
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
     * @throws \Psr\Container\ContainerExceptionInterface if the service is not found
     */
    private function getService(?string $class): TranslatorServiceInterface
    {
        return $this->factory->getService($class ?: TranslatorFactory::DEFAULT_SERVICE);
    }

    private function handleError(TranslatorServiceInterface $service, string $message): JsonResponse
    {
        if (($error = $service->getLastError()) instanceof HttpClientError) {
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
