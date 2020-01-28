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

namespace App\Controller;

use App\Pdf\PdfDocument;
use App\Pdf\PdfResponse;
use App\Report\BaseReport;
use App\Service\ApplicationService;
use App\Service\UrlGeneratorService;
use App\Traits\FormatterTrait;
use App\Traits\TranslatorFlashMessageTrait;
use Doctrine\ORM\EntityManagerInterface;
use FOS\UserBundle\Model\UserInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Base controller.
 */
abstract class BaseController extends AbstractController
{
    use FormatterTrait;
    use TranslatorFlashMessageTrait;

    /**
     * The URL generator service.
     *
     * @var UrlGeneratorService
     */
    protected $generatorService;

    /**
     * Gets the application service.
     */
    public function getApplication(): ApplicationService
    {
        if (isset($this->application)) {
            return $this->application;
        } else {
            return $this->application = $this->get(ApplicationService::class);
        }
    }

    /**
     * Gets the application name and version.
     */
    public function getApplicationName(): string
    {
        $name = $this->getParameter('app_name');
        $version = $this->getParameter('app_version');

        return \sprintf('%s v%s', $name, $version);
    }

    /**
     * Gets the application owner.
     */
    public function getApplicationOwner(): string
    {
        return $this->getParameter('app_owner');
    }

    /**
     * Gets the application owner URL.
     */
    public function getApplicationOwnerUrl(): string
    {
        return $this->getParameter('app_owner_url');
    }

    /**
     * Gets the session.
     */
    public function getSession(): SessionInterface
    {
        if (isset($this->session)) {
            return $this->session;
        } else {
            return $this->session = $this->get('session');
        }
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices()
    {
        return \array_merge([
            ApplicationService::class,
            TranslatorInterface::class,
            UrlGeneratorService::class,
        ], parent::getSubscribedServices());
    }

    /**
     * Gets the translator service.
     */
    public function getTranslator(): TranslatorInterface
    {
        if (isset($this->translator)) {
            return $this->translator;
        } else {
            return $this->translator = $this->get(TranslatorInterface::class);
        }
    }

    /**
     * Gets the URL generator service.
     */
    public function getUrlGenerator(): UrlGeneratorService
    {
        if (isset($this->generatorService)) {
            return $this->generatorService;
        } else {
            return $this->generatorService = $this->get(UrlGeneratorService::class);
        }
    }

    /**
     * Gets the connected user e-mail.
     *
     * @return string|null The user e-mail or NULL if not connected
     */
    public function getUserEmail(): ?string
    {
        $user = $this->getUser();
        if ($user instanceof UserInterface) {
            return $user->getEmail();
        }

        return null;
    }

    /**
     * Gets the connected user name.
     *
     * @return string|null The user name or NULL if not connected
     */
    public function getUserName(): ?string
    {
        $user = $this->getUser();
        if ($user instanceof UserInterface) {
            return $user->getUsername();
        }

        return null;
    }

    /**
     * Returns if the debug mode is enabled.
     *
     * @return bool true if enabled
     */
    public function isDebug(): bool
    {
        return (bool) $this->getParameter('kernel.debug');
    }

    /**
     * Redirect to the home page.
     */
    public function redirectToHomePage(): Response
    {
        return  $this->redirectToRoute(IndexController::HOME_PAGE);
    }

    /**
     * Gets the named object manager.
     *
     * This is a shortcut to: <code>$this->getDoctrine()->getManager();</code>.
     */
    protected function getManager(): EntityManagerInterface
    {
        return $this->getDoctrine()->getManager();
    }

    /**
     * Handle the given request within the form and test if the form is submitted and valid.
     *
     * @param FormInterface $form    the form to check
     * @param Request       $request the optional request to handle
     *
     * @return bool true if the form is submitted and valid
     *
     * @see FormInterface::handleRequest()
     * @see FormInterface::isSubmitted()
     * @see FormInterface::isValid()
     */
    protected function handleFormRequest(FormInterface $form, ?Request $request): bool
    {
        if ($request) {
            $form->handleRequest($request);
        }

        return $form->isSubmitted() && $form->isValid();
    }

    /**
     * Returns the given exception as JsonResponse.
     *
     * @param \Exception  $e       the exception to serialize
     * @param string|null $message an additionnal error message
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse The response
     */
    protected function jsonException(\Exception $e, ?string $message = null): JsonResponse
    {
        $result = [
            'result' => false,
            'message' => $message ?? $e->getMessage(),
            'exception' => [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ],
        ];

        return $this->json($result);
    }

    /**
     * Render the given PDF document and ouput the response.
     *
     * @param PdfDocument $doc    the document to render
     * @param bool        $inline <code>true</code> to send the file inline to the browser. The PDF viewer is used if available.
     *                            <code>false</code> to send to the browser and force a file download with the name given.
     * @param string      $name   the name of the PDF file
     * @param bool        $isUTF8 indicates if name is encoded in ISO-8859-1 (false) or UTF-8 (true)
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException if the report can not be rendered
     * @throws \Symfony\Component\Finder\Exception\AccessDeniedException     if the access is denied
     */
    protected function renderDocument(PdfDocument $doc, bool $inline = true, string $name = '', bool $isUTF8 = false): PdfResponse
    {
        // render
        if ($doc instanceof BaseReport && !$doc->render()) {
            throw $this->createNotFoundException($this->trans('errors.render_document'));
        }

        // title
        if (empty($name) && !empty($doc->getTitle())) {
            $name = $doc->getTitle() . '.pdf';
        }

        // create response
        return new PdfResponse($doc, $inline, $name, $isUTF8);
    }
}
