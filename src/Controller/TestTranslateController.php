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

use App\Attribute\GetRoute;
use App\Interfaces\RoleInterface;
use App\Model\HttpClientError;
use App\Service\AbstractHttpClientService;
use App\Translator\TranslatorFactory;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route(path: '/test', name: 'test_')]
#[IsGranted(RoleInterface::ROLE_SUPER_ADMIN)]
class TestTranslateController extends AbstractController
{
    /**
     * Show the translation page.
     *
     * @throws ServiceNotFoundException if the service is not found
     */
    #[GetRoute(path: '/translate', name: 'translate')]
    public function translate(TranslatorFactory $factory): Response
    {
        $service = $factory->getSessionService();
        $languages = $service->getLanguages();
        $error = $service->getLastError();
        if ($error instanceof HttpClientError) {
            $id = \sprintf('%s.%s', $service->getName(), $error->getCode());
            if ($this->isTransDefined($id, 'translator')) {
                $error->setMessage($this->trans($id, [], 'translator'));
            }
            $message = $this->trans('translator.title') . '|';
            $message .= $this->trans('translator.languages_error');
            $message .= $this->trans('translator.last_error', [
                '%code%' => $error->getCode(),
                '%message%' => $error->getMessage(),
            ]);
            $this->error($message);
            $error = true;
        }
        $parameters = [
            'service' => $service,
            'form' => $this->createForm(FormType::class),
            'translators' => $factory->getTranslators(),
            'language' => AbstractHttpClientService::getAcceptLanguage(),
            'languages' => $languages,
            'error' => $error,
        ];

        return $this->render('test/translate.html.twig', $parameters);
    }
}
