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

use App\Enums\EntityPermission;
use App\Enums\FlashType;
use App\Form\FormHelper;
use App\Model\CustomerInformation;
use App\Report\AbstractReport;
use App\Response\PdfResponse;
use App\Response\SpreadsheetResponse;
use App\Response\WordResponse;
use App\Service\ApplicationService;
use App\Service\UrlGeneratorService;
use App\Service\UserService;
use App\Spreadsheet\AbstractDocument;
use App\Spreadsheet\SpreadsheetDocument;
use App\Traits\ExceptionContextTrait;
use App\Traits\RequestTrait;
use App\Traits\TranslatorFlashMessageAwareTrait;
use App\Utils\FileUtils;
use App\Utils\StringUtils;
use App\Word\AbstractWordDocument;
use App\Word\WordDocument;
use fpdf\PdfDocument;
use Psr\Container\ContainerExceptionInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as BaseController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Contracts\Translation\TranslatableInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Provides common features needed in controllers.
 */
abstract class AbstractController extends BaseController
{
    use ExceptionContextTrait;
    use RequestTrait;
    use TranslatorFlashMessageAwareTrait;

    /**
     * The home page route name.
     */
    final public const HOME_PAGE = 'homepage';

    /**
     * The route requirement for an identifier.
     */
    final public const ID_REQUIREMENT = ['id' => Requirement::DIGITS];

    // services
    private ?UrlGeneratorService $generatorService = null;
    private ?UserService $userService = null;

    /**
     * Gets the address from (email and name) used to send email.
     */
    public function getAddressFrom(): Address
    {
        /** @phpstan-var string $email */
        $email = $this->getParameter('mailer_user_email');
        /** @phpstan-var string $name */
        $name = $this->getParameter('mailer_user_name');

        return new Address($email, $name);
    }

    /**
     * Gets the application name (without the version).
     */
    public function getApplication(): string
    {
        /** @phpstan-var string */
        return $this->getParameter('app_name');
    }

    /**
     * Gets the application name and the version.
     */
    public function getApplicationName(): string
    {
        /** @phpstan-var string */
        return $this->getParameter('app_name_version');
    }

    /**
     * Gets the application owner URL.
     */
    public function getApplicationOwnerUrl(): string
    {
        /** @phpstan-var string */
        return $this->getParameter('app_owner_url');
    }

    /**
     * Gets the application service.
     *
     * @throws \LogicException if the service cannot be found
     */
    public function getApplicationService(): ApplicationService
    {
        return $this->getUserService()->getApplication();
    }

    /**
     * Gets the customer information.
     *
     * This is a shortcut to get customer information from the user service.
     */
    public function getCustomer(): CustomerInformation
    {
        return $this->getUserService()->getCustomer();
    }

    /**
     * Gets the minimum margin, in percent, for a calculation.
     */
    public function getMinMargin(): float
    {
        return $this->getApplicationService()->getMinMargin();
    }

    /**
     * Gets the project (root) directory.
     */
    public function getProjectDir(): string
    {
        /** @phpstan-var string */
        return $this->getParameter('kernel.project_dir');
    }

    /**
     * Convert the given file to a path relative to the project directory.
     */
    public function getRelativePath(string $file): string
    {
        return FileUtils::makePathRelative($file, $this->getProjectDir());
    }

    /**
     * Gets the request stack.
     *
     * @throws \LogicException if the service cannot be found
     *
     * @psalm-suppress MixedAssignment
     */
    public function getRequestStack(): RequestStack
    {
        try {
            /** @phpstan-var RequestStack */
            return $this->requestStack ??= $this->container->get('request_stack');
        } catch (ContainerExceptionInterface $e) {
            throw new \LogicException($e->getMessage(), $e->getCode(), $e);
        }
    }

    #[\Override]
    public static function getSubscribedServices(): array
    {
        return [
            ...parent::getSubscribedServices(),
            UserService::class,
            TranslatorInterface::class,
            UrlGeneratorService::class,
        ];
    }

    /**
     * Gets the translator.
     *
     * @throws \LogicException if the service cannot be found
     */
    public function getTranslator(): TranslatorInterface
    {
        try {
            return $this->translator ??= $this->container->get(TranslatorInterface::class);
        } catch (ContainerExceptionInterface $e) {
            throw new \LogicException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * Gets the URL generator service.
     *
     * @throws \LogicException if the service cannot be found
     */
    public function getUrlGenerator(): UrlGeneratorService
    {
        try {
            return $this->generatorService ??= $this->container->get(UrlGeneratorService::class);
        } catch (ContainerExceptionInterface $e) {
            throw new \LogicException(\sprintf('Unable to get the "%s" service,', UserService::class), $e->getCode(), $e);
        }
    }

    /**
     * Gets the connected user identifier.
     *
     * @return string|null the user identifier or null if not connected
     */
    public function getUserIdentifier(): ?string
    {
        return $this->getUser()?->getUserIdentifier();
    }

    /**
     * Gets the user service.
     *
     * @throws \LogicException if the service cannot be found
     */
    public function getUserService(): UserService
    {
        try {
            return $this->userService ??= $this->container->get(UserService::class);
        } catch (ContainerExceptionInterface $e) {
            throw new \LogicException(\sprintf('Unable to get the "%s" service,', UserService::class), $e->getCode(), $e);
        }
    }

    /**
     * Display a flash message, if not empty, and redirect to the home page.
     *
     * @param ?string   $id         the optional flash message identifier to translate
     * @param array     $parameters an array of parameters for the flash message
     * @param FlashType $type       the flash message type
     * @param ?Request  $request    the optional request, if not null, and the caller parameter is set, redirect to it
     */
    public function redirectToHomePage(
        ?string $id = null,
        array $parameters = [],
        FlashType $type = FlashType::SUCCESS,
        ?Request $request = null
    ): RedirectResponse {
        if (StringUtils::isString($id)) {
            $id = $this->trans($id, $parameters);
            $this->addFlashMessage($type, $id);
        }
        if ($request instanceof Request) {
            return $this->getUrlGenerator()->redirect($request);
        }

        return $this->redirectToRoute(self::HOME_PAGE);
    }

    /**
     * Creates and returns a form helper instance.
     *
     * @param ?string    $labelPrefix the label prefix. If the prefix is not null, the label is automatically added
     *                                when the field property is set.
     * @param mixed|null $data        the initial data
     * @param array      $options     the initial options
     */
    protected function createFormHelper(?string $labelPrefix = null, mixed $data = null, array $options = []): FormHelper
    {
        $builder = $this->createFormBuilder($data, $options);

        return new FormHelper($builder, $labelPrefix);
    }

    /**
     * Returns a translated NotFoundHttpException.
     *
     * This will result in a 404 response code.
     *
     * @param string|\Stringable|TranslatableInterface $id         the message identifier
     *                                                             (may also be an object that can be cast to string)
     * @param array                                    $parameters an array of parameters for the message
     * @param ?string                                  $domain     the domain or null to use the default
     * @param ?\Throwable                              $previous   the previous throwable used for
     *                                                             the exception chaining
     */
    protected function createTranslatedNotFoundException(
        string|\Stringable|TranslatableInterface $id,
        array $parameters = [],
        ?\Throwable $previous = null,
        ?string $domain = null,
    ): NotFoundHttpException {
        return $this->createNotFoundException($this->trans($id, $parameters, $domain), $previous);
    }

    /**
     * {inheritDoc}.
     */
    #[\Override]
    protected function denyAccessUnlessGranted(mixed $attribute, mixed $subject = null, ?string $message = null): void
    {
        if ($attribute instanceof EntityPermission) {
            $attribute = $attribute->name;
        }
        $message ??= $this->trans('http_error_403.description');
        parent::denyAccessUnlessGranted($attribute, $subject, $message);
    }

    /**
     * Gets the cookie path.
     */
    protected function getCookiePath(): string
    {
        /** @phpstan-var string */
        return $this->getParameter('cookie_path');
    }

    /**
     * Inspects the given request and calls submit() if the form was submitted, checks whether the given
     * form is submitted and if the form and all children are valid.
     *
     * @template T
     *
     * @param FormInterface<T> $form the form to validate
     *
     * @see FormInterface::handleRequest()
     * @see FormInterface::isSubmitted()
     * @see FormInterface::isValid()
     */
    protected function handleRequestForm(Request $request, FormInterface $form): bool
    {
        $form->handleRequest($request);

        return $form->isSubmitted() && $form->isValid();
    }

    /**
     * Returns the given exception as a JsonResponse.
     *
     * @param \Exception $e       the exception to serialize
     * @param ?string    $message the optional error message
     */
    protected function jsonException(\Exception $e, ?string $message = null): JsonResponse
    {
        return $this->jsonFalse([
            'message' => $message ?? $e->getMessage(),
            'exception' => $this->getExceptionContext($e),
        ]);
    }

    /**
     * Returns a JSON response with false as the result.
     *
     * @param array $data the data to merge within the response
     */
    protected function jsonFalse(array $data = []): JsonResponse
    {
        return $this->json(\array_merge_recursive(['result' => false], $data));
    }

    /**
     * Returns a JSON response with true as the result.
     *
     * @param array $data the data to merge within the response
     */
    protected function jsonTrue(array $data = []): JsonResponse
    {
        return $this->json(\array_merge_recursive(['result' => true], $data));
    }

    /**
     * Render the template exception.
     */
    protected function renderFormException(string $id, \Throwable $e, ?LoggerInterface $logger = null): Response
    {
        $message = $this->trans($id);
        $context = $this->getExceptionContext($e);
        $logger?->error($message, $context);

        return $this->render('bundles/TwigBundle/Exception/exception.html.twig', [
            'message' => $message,
            'exception' => $e,
        ]);
    }

    /**
     * Render the given PDF document and output the response.
     *
     * @param PdfDocument $doc    the document to render
     * @param bool        $inline <code>true</code> to send the file inline to the browser. The PDF viewer is used if
     *                            available. <code>false</code> to send to the browser and force a file download with
     *                            the name given.
     * @param string      $name   the name of the file (without an extension) or '' to use default ('document')
     */
    protected function renderPdfDocument(PdfDocument $doc, bool $inline = true, string $name = ''): PdfResponse
    {
        if ($doc instanceof AbstractReport && !$doc->render()) {
            throw $this->createTranslatedNotFoundException('errors.render_document');
        }
        if (!StringUtils::isString($name) && StringUtils::isString($doc->getTitle())) {
            $name = $doc->getTitle();
        }

        return new PdfResponse($doc, $inline, $name);
    }

    /**
     * Render the given Spreadsheet document and output the response.
     *
     * @param SpreadsheetDocument $doc    the document to render
     * @param bool                $inline <code>true</code> to send the file inline to the browser. The Spreadsheet
     *                                    viewer is used if available.
     *                                    <code>false</code> to send to the browser and force a file download.
     * @param string              $name   the name of the file (without an extension) or '' to use default ('document')
     */
    protected function renderSpreadsheetDocument(
        SpreadsheetDocument $doc,
        bool $inline = true,
        string $name = ''
    ): SpreadsheetResponse {
        if ($doc instanceof AbstractDocument && !$doc->render()) {
            throw $this->createTranslatedNotFoundException('errors.render_document');
        }
        if (!StringUtils::isString($name) && StringUtils::isString($doc->getTitle())) {
            /** @phpstan-var non-empty-string $name */
            $name = $doc->getTitle();
        }

        return new SpreadsheetResponse($doc, $inline, $name);
    }

    /**
     * Render the given Word document and output the response.
     *
     * @param WordDocument $doc    the document to render
     * @param bool         $inline <code>true</code> to send the file inline to the browser. The PDF viewer is used
     *                             if available. <code>false</code> to send to the browser and force a file download
     *                             with the name given.
     * @param string       $name   the name of the file (without an extension) or '' to use default ('document')
     */
    protected function renderWordDocument(WordDocument $doc, bool $inline = true, string $name = ''): WordResponse
    {
        if ($doc instanceof AbstractWordDocument && !$doc->render()) {
            throw $this->createTranslatedNotFoundException('errors.render_document');
        }
        if (!StringUtils::isString($name) && StringUtils::isString($doc->getTitle())) {
            /** @phpstan-var non-empty-string $name */
            $name = $doc->getTitle();
        }

        return new WordResponse($doc, $inline, $name);
    }
}
