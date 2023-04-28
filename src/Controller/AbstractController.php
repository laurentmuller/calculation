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

use App\Entity\User;
use App\Enums\EntityPermission;
use App\Enums\FlashType;
use App\Form\FormHelper;
use App\Pdf\PdfDocument;
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
use App\Word\AbstractWordDocument;
use App\Word\WordDocument;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as BaseController;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mime\Address;
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
     * The home route name.
     */
    final public const HOME_PAGE = 'homepage';

    // services
    private ?UrlGeneratorService $generatorService = null;
    private ?TranslatorInterface $translator = null;
    private ?UserService $userService = null;

    /**
     * Gets the address from (email and name) used to send email.
     */
    public function getAddressFrom(): Address
    {
        $email = $this->getParameterString('mailer_user_email');
        $name = $this->getParameterString('mailer_user_name');

        return new Address($email, $name);
    }

    /**
     * Gets the application service.
     */
    public function getApplication(): ApplicationService
    {
        return $this->getUserService()->getApplication();
    }

    /**
     * Gets the application name and version.
     */
    public function getApplicationName(): string
    {
        $name = $this->getParameterString('app_name');
        $version = $this->getParameterString('app_version');

        return \sprintf('%s v%s', $name, $version);
    }

    /**
     * Gets the application owner.
     */
    public function getApplicationOwner(): string
    {
        return $this->getParameterString('app_owner');
    }

    /**
     * Gets the application owner URL.
     */
    public function getApplicationOwnerUrl(): string
    {
        return $this->getParameterString('app_owner_url');
    }

    /**
     * Gets the minimum margin, in percent, for a calculation.
     */
    public function getMinMargin(): float
    {
        return $this->getApplication()->getMinMargin();
    }

    /**
     * @psalm-suppress all
     */
    public function getRequestStack(): RequestStack
    {
        if (!$this->requestStack instanceof RequestStack) {
            /* @noinspection PhpUnhandledExceptionInspection */
            $this->requestStack = $this->container->get('request_stack');
        }

        return $this->requestStack;
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedServices(): array
    {
        return \array_merge(parent::getSubscribedServices(), [
            UserService::class,
            TranslatorInterface::class,
            UrlGeneratorService::class,
        ]);
    }

    /**
     * Gets the translator.
     */
    public function getTranslator(): TranslatorInterface
    {
        if (!$this->translator instanceof TranslatorInterface) {
            /* @noinspection PhpUnhandledExceptionInspection */
            $this->translator = $this->container->get(TranslatorInterface::class);
        }

        return $this->translator;
    }

    /**
     * Gets the URL generator service.
     */
    public function getUrlGenerator(): UrlGeneratorService
    {
        if (!$this->generatorService instanceof UrlGeneratorService) {
            /* @noinspection PhpUnhandledExceptionInspection */
            $this->generatorService = $this->container->get(UrlGeneratorService::class);
        }

        return $this->generatorService;
    }

    /**
     * Gets the connected user e-mail.
     *
     * @return string|null the user e-mail or null if not connected
     */
    public function getUserEmail(): ?string
    {
        /** @psalm-var User|null $user */
        $user = $this->getUser();

        return $user?->getEmail();
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
     */
    public function getUserService(): UserService
    {
        if (!$this->userService instanceof UserService) {
            /* @noinspection PhpUnhandledExceptionInspection */
            $this->userService = $this->container->get(UserService::class);
        }

        return $this->userService;
    }

    /**
     * Display a message, if not empty; and redirect to the home page.
     *
     * @param string    $message    the translatable message
     * @param array     $parameters the message parameters
     * @param FlashType $type       the message type
     * @param ?string   $domain     the translation domain
     * @param ?string   $locale     the translation locale
     *
     * @return RedirectResponse the response
     */
    public function redirectToHomePage(string $message = '', array $parameters = [], FlashType $type = FlashType::SUCCESS, ?string $domain = null, ?string $locale = null): RedirectResponse
    {
        if ('' !== $message) {
            $message = $this->trans($message, $parameters, $domain, $locale);
            $this->addFlashMessage($type, $message);
        }

        return $this->redirectToRoute(self::HOME_PAGE);
    }

    /**
     * {@inheritDoc}
     *
     * Override the parent function to allow to use the default type like defined in the <code>FormFactoryInterface</code>.
     *
     * @template TData
     *
     * @return FormInterface<TData>
     *
     * @phpstan-param class-string<\Symfony\Component\Form\FormTypeInterface<TData>> $type
     *
     * @psalm-param class-string<\Symfony\Component\Form\FormTypeInterface> $type
     *
     * @psalm-suppress InvalidCast
     */
    protected function createForm(string $type = FormType::class, mixed $data = null, array $options = []): FormInterface
    {
        return parent::createForm($type, $data, $options);
    }

    /**
     * Creates and returns a form helper instance.
     *
     * @param ?string    $labelPrefix the label prefix. If the prefix is not null, the label is automatically added when the field property is set.
     * @param mixed|null $data        the initial data
     * @param array      $options     the initial options
     */
    protected function createFormHelper(string $labelPrefix = null, mixed $data = null, array $options = []): FormHelper
    {
        $builder = $this->createFormBuilder($data, $options);

        return new FormHelper($builder, $labelPrefix);
    }

    /**
     * {inheritDoc}.
     */
    protected function denyAccessUnlessGranted(mixed $attribute, mixed $subject = null, string $message = 'Access Denied.'): void
    {
        if ($attribute instanceof EntityPermission) {
            $attribute = $attribute->name;
        }
        parent::denyAccessUnlessGranted($attribute, $subject, $message);
    }

    /**
     * Gets the cookie path.
     */
    protected function getCookiePath(): string
    {
        return $this->getParameterString('cookie_path');
    }

    /**
     * Gets a string container parameter by its name.
     */
    protected function getParameterString(string $name): string
    {
        /** @psalm-var string $value */
        $value = $this->getParameter($name);

        return $value;
    }

    /**
     * Inspects the given request and calls submit() if the form was submitted, checks whether the given
     * form is submitted and if the form and all children are valid.
     *
     * @template T
     *
     * @psalm-param FormInterface<T> $form    the form to validate
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
     * Returns a Json response with false as result.
     *
     * @param array $data the data to merge within the response
     */
    protected function jsonFalse(array $data = []): JsonResponse
    {
        return $this->json(\array_merge_recursive(['result' => false], $data));
    }

    /**
     * Returns a Json response with true as result.
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
    protected function renderFormException(string $id, \Throwable $e, LoggerInterface $logger = null): Response
    {
        $message = $this->trans($id);
        $context = $this->getExceptionContext($e);
        $logger?->error($message, $context);

        return $this->render('@Twig/Exception/exception.html.twig', [
            'message' => $message,
            'exception' => $e,
        ]);
    }

    /**
     * Render the given PDF document and output the response.
     *
     * @param PdfDocument $doc    the document to render
     * @param bool        $inline <code>true</code> to send the file inline to the browser. The PDF viewer is used if available.
     *                            <code>false</code> to send to the browser and force a file download with the name given.
     * @param string      $name   the name of the PDF file or null to use default ('document.pdf')
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException if the report can not be rendered
     */
    protected function renderPdfDocument(PdfDocument $doc, bool $inline = true, string $name = ''): PdfResponse
    {
        if ($doc instanceof AbstractReport && !$doc->render()) {
            throw $this->createNotFoundException($this->trans('errors.render_document'));
        }
        if ('' === $name && \is_string($title = $doc->getTitle())) {
            $name = $title;
        }

        return new PdfResponse($doc, $inline, $name);
    }

    /**
     * Render the given Spreadsheet document and output the response.
     *
     * @param SpreadsheetDocument $doc    the document to render
     * @param bool                $inline <code>true</code> to send the file inline to the browser. The Spreadsheet viewer is used if available.
     *                                    <code>false</code> to send to the browser and force a file download.
     * @param string              $name   the name of the Spreadsheet file or null to use default ('document.xlsx')
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    protected function renderSpreadsheetDocument(SpreadsheetDocument $doc, bool $inline = true, string $name = ''): SpreadsheetResponse
    {
        if ($doc instanceof AbstractDocument && !$doc->render()) {
            throw $this->createNotFoundException($this->trans('errors.render_document'));
        }
        if ('' === $name && \is_string($title = $doc->getTitle())) {
            $name = $title;
        }

        return new SpreadsheetResponse($doc, $inline, $name);
    }

    /**
     * Render the given Word document and output the response.
     *
     * @param WordDocument $doc    the document to render
     * @param bool         $inline <code>true</code> to send the file inline to the browser. The PDF viewer is used if available.
     *                             <code>false</code> to send to the browser and force a file download with the name given.
     * @param string       $name   the name of the PDF file or null to use default ('document.pdf')
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @throws \PhpOffice\PhpWord\Exception\Exception
     */
    protected function renderWordDocument(WordDocument $doc, bool $inline = true, string $name = ''): WordResponse
    {
        if ($doc instanceof AbstractWordDocument && !$doc->render()) {
            throw $this->createNotFoundException($this->trans('errors.render_document'));
        }
        if ('' === $name && \is_string($title = $doc->getTitle())) {
            $name = $title;
        }

        return new WordResponse($doc, $inline, $name);
    }
}
