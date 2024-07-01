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
use App\Attribute\GetDelete;
use App\Attribute\GetPost;
use App\Entity\User;
use App\Enums\EntityPermission;
use App\Form\User\ResetAllPasswordType;
use App\Form\User\UserChangePasswordType;
use App\Form\User\UserCommentType;
use App\Form\User\UserRightsType;
use App\Interfaces\RoleInterface;
use App\Model\Comment;
use App\Report\UsersReport;
use App\Report\UsersRightsReport;
use App\Repository\AbstractRepository;
use App\Repository\UserRepository;
use App\Resolver\DataQueryValueResolver;
use App\Response\PdfResponse;
use App\Response\SpreadsheetResponse;
use App\Service\MailerService;
use App\Service\ResetPasswordService;
use App\Service\RoleBuilderService;
use App\Service\RoleHierarchyService;
use App\Spreadsheet\UserRightsDocument;
use App\Spreadsheet\UsersDocument;
use App\Table\DataQuery;
use App\Table\UserTable;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordToken;
use Vich\UploaderBundle\Storage\StorageInterface;

/**
 * The controller for user entities.
 *
 * @template-extends AbstractEntityController<User, UserRepository>
 */
#[AsController]
#[Route(path: '/user', name: 'user_')]
#[IsGranted(RoleInterface::ROLE_ADMIN)]
class UserController extends AbstractEntityController
{
    public function __construct(UserRepository $repository)
    {
        parent::__construct($repository);
    }

    /**
     * Add a user.
     */
    #[GetPost(path: '/add', name: 'add')]
    public function add(Request $request): Response
    {
        return $this->editEntity($request, new User());
    }

    /**
     * Delete an user.
     */
    #[GetDelete(path: '/delete/{id}', name: 'delete', requirements: self::ID_REQUIREMENT)]
    public function delete(Request $request, User $item, Security $security, LoggerInterface $logger): Response
    {
        if ($this->isConnectedUser($item) || $this->isOriginalUser($item, $security)) {
            $this->warningTrans('user.delete.connected', ['%name%' => $item]);

            return $this->redirectToDefaultRoute($request, $item);
        }

        return $this->deleteEntity($request, $item, $logger);
    }

    /**
     * Edit a user.
     */
    #[GetPost(path: '/edit/{id}', name: 'edit', requirements: self::ID_REQUIREMENT)]
    public function edit(Request $request, User $item): Response
    {
        return $this->editEntity($request, $item);
    }

    /**
     * Export the customers to a Spreadsheet document.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    #[Get(path: '/excel', name: 'excel')]
    public function excel(StorageInterface $storage): SpreadsheetResponse
    {
        $entities = $this->getEntities('username');
        if ([] === $entities) {
            $message = $this->trans('user.list.empty');
            throw $this->createNotFoundException($message);
        }
        $doc = new UsersDocument($this, $entities, $storage);

        return $this->renderSpreadsheetDocument($doc);
    }

    /**
     * Render the table view.
     */
    #[Get(path: '', name: 'index')]
    public function index(
        UserTable $table,
        LoggerInterface $logger,
        #[ValueResolver(DataQueryValueResolver::class)]
        DataQuery $query = new DataQuery()
    ): Response {
        return $this->handleTableRequest($table, $logger, $query, 'user/user_table.html.twig');
    }

    /**
     * Send an email from the current user to the selected user.
     */
    #[GetPost(path: '/message/{id}', name: 'message', requirements: self::ID_REQUIREMENT)]
    public function message(Request $request, User $user, MailerService $service, LoggerInterface $logger): Response
    {
        if ($this->isConnectedUser($user)) {
            $this->warningTrans('user.message.connected');

            return $this->redirectToDefaultRoute($request, $user);
        }

        /** @psalm-var User|Address $from */
        $from = $this->getUser() ?? $this->getAddressFrom();
        $comment = new Comment();
        $comment->setSubject($this->getApplicationName())
            ->setFromAddress($from)
            ->setToAddress($user);
        $form = $this->createForm(UserCommentType::class, $comment);
        if ($this->handleRequestForm($request, $form)) {
            try {
                $service->sendComment($comment);
                $this->successTrans('user.message.success', ['%name%' => $user->getDisplay()]);

                return $this->redirectToDefaultRoute($request, $user);
            } catch (TransportExceptionInterface $e) {
                return $this->renderFormException('user.message.error', $e, $logger);
            }
        }
        $parameters = [
            'form' => $form,
            'isMail' => $comment->isMail(),
        ];

        return $this->render('user/user_comment.html.twig', $parameters);
    }

    /**
     * Change password for an existing user.
     */
    #[GetPost(path: '/password/{id}', name: 'password', requirements: self::ID_REQUIREMENT)]
    public function password(Request $request, User $item): Response
    {
        $form = $this->createForm(UserChangePasswordType::class, $item);
        if ($this->handleRequestForm($request, $form)) {
            $this->saveToDatabase($item);

            return $this->redirectToDefaultRoute($request, $item);
        }
        $parameters = [
            'item' => $item,
            'form' => $form,
            'params' => ['id' => $item->getId()],
        ];

        return $this->render('user/user_password.html.twig', $parameters);
    }

    /**
     * Export the users to a PDF document.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException if no user is found
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    #[Get(path: '/pdf', name: 'pdf')]
    public function pdf(StorageInterface $storage): PdfResponse
    {
        $entities = $this->getEntities('username');
        if ([] === $entities) {
            $message = $this->trans('user.list.empty');
            throw $this->createNotFoundException($message);
        }
        $doc = new UsersReport($this, $entities, $storage);

        return $this->renderPdfDocument($doc);
    }

    /**
     * Clear all requested reset passwords.
     */
    #[GetPost(path: '/reset', name: 'reset_all')]
    public function resetAllPasswordRequest(Request $request): Response
    {
        $repository = $this->getRepository();
        $users = $repository->getResettableUsers();
        $generator = $this->getUrlGenerator();
        if ([] === $users) {
            $this->warningTrans('user.reset_all.empty');

            return $this->redirectToDefaultRoute($request);
        }
        if (1 === \count($users)) {
            $params = $generator->routeParams($request, \reset($users));

            return $this->redirectToRoute('user_reset', $params);
        }

        $name = 'users';
        $data = [$name => $users];
        $form = $this->createFormBuilder($data)
            ->add($name, ResetAllPasswordType::class)
            ->getForm();
        if ($this->handleRequestForm($request, $form)) {
            /** @var User[] $users */
            $users = $form->get($name)->getData();
            $repository->resetPasswordRequest($users);
            $this->successResetPassword($users);

            return $this->redirectToDefaultRoute($request);
        }

        return $this->render('user/user_reset_all_passwords.html.twig', ['form' => $form]);
    }

    /**
     * Clear the request reset password.
     */
    #[GetPost(path: '/reset/{id}', name: 'reset', requirements: self::ID_REQUIREMENT)]
    public function resetPasswordRequest(Request $request, User $item): Response
    {
        $form = $this->createForm(FormType::class);
        if ($this->handleRequestForm($request, $form)) {
            if ($item->isResetPassword()) {
                $this->getRepository()->removeResetPasswordRequest($item);
                $this->successResetPassword([$item]);
            } else {
                $this->warningTrans('user.reset.error', ['%name%' => $item]);
            }

            return $this->redirectToDefaultRoute($request, $item);
        }

        return $this->render('cards/card_delete.html.twig', [
            'form' => $form,
            'title' => 'user.reset.title',
            'title_icon' => 'eraser',
            'message' => 'user.reset.confirmation',
            'message_parameters' => ['%name%' => $item],
        ]);
    }

    /**
     * Edit user access rights.
     */
    #[GetPost(path: '/rights/{id}', name: 'rights', requirements: self::ID_REQUIREMENT)]
    public function rights(
        Request $request,
        User $item,
        RoleBuilderService $builder,
        RoleHierarchyService $service,
        EntityManagerInterface $manager
    ): Response {
        if ($this->isConnectedUser($item) && !$service->hasRole($item, RoleInterface::ROLE_SUPER_ADMIN)) {
            $this->warningTrans('user.rights.connected');

            return $this->redirectToDefaultRoute($request, $item);
        }

        $default = $builder->getRole($item);
        $form = $this->createForm(UserRightsType::class, $item);
        if ($this->handleRequestForm($request, $form)) {
            // same as default?
            if ($item->getRights() === $default->getRights()) {
                $item->setRights(null);
                if ($item->isEnabled()) {
                    $item->setOverwrite(false);
                }
            }

            $manager->flush();

            return $this->redirectToDefaultRoute($request, $item);
        }

        return $this->render('user/user_rights.html.twig', [
            'item' => $item,
            'form' => $form,
            'default' => $default,
            'params' => ['id' => $item->getId()],
            'permissions' => EntityPermission::sorted(),
        ]);
    }

    /**
     * Export the user access rights to a Spreadsheet document.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @throws \Doctrine\ORM\Exception\ORMException
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    #[Get(path: '/rights/excel', name: 'rights_excel')]
    public function rightsExcel(RoleBuilderService $builder): SpreadsheetResponse
    {
        $entities = $this->getEntities('username');
        if ([] === $entities) {
            $message = $this->trans('user.list.empty');
            throw $this->createNotFoundException($message);
        }
        $doc = new UserRightsDocument($this, $entities, $builder);

        return $this->renderSpreadsheetDocument($doc);
    }

    /**
     * Export user access rights to a PDF document.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * @throws \Doctrine\ORM\Exception\ORMException
     */
    #[Get(path: '/rights/pdf', name: 'rights_pdf')]
    public function rightsPdf(RoleBuilderService $builder): PdfResponse
    {
        $entities = $this->getEntities('username');
        if ([] === $entities) {
            $message = $this->trans('user.list.empty');
            throw $this->createNotFoundException($message);
        }
        $doc = new UsersRightsReport($this, $entities, $builder);

        return $this->renderPdfDocument($doc);
    }

    /**
     * Sends an email to the user for reset its password.
     */
    #[GetPost(path: '/reset/send/{id}', name: 'reset_send', requirements: self::ID_REQUIREMENT)]
    public function sendPasswordRequest(Request $request, User $item, ResetPasswordService $service): Response
    {
        $form = $this->createForm(FormType::class);
        if ($this->handleRequestForm($request, $form)) {
            $result = $service->sendEmail($request, $item);
            $parameters = ['%name%' => $item];
            if (false === $result) {
                $this->warningTrans('reset_user_not_found', $parameters, 'security');
            } elseif (!$result instanceof ResetPasswordToken) {
                $this->warningTrans('reset_token_not_found', $parameters, 'security');
            } else {
                $this->successTrans('reset_token_send', $parameters, 'security');
            }

            return $this->redirectToDefaultRoute($request, $item);
        }

        return $this->render('cards/card_confirm.html.twig', [
            'form' => $form,
            'title' => 'user.send.title',
            'title_icon' => 'envelope-circle-check fas',
            'message' => 'user.send.message',
            'message_parameters' => ['%name%' => $item],
            'submit_text' => 'user.send.submit',
        ]);
    }

    /**
     * Show the properties of a user.
     */
    #[Get(path: '/show/{id}', name: 'show', requirements: self::ID_REQUIREMENT)]
    public function show(User $item): Response
    {
        return $this->showEntity($item);
    }

    protected function getEntities(
        array|string $sortedFields = [],
        array $criteria = [],
        string $alias = AbstractRepository::DEFAULT_ALIAS
    ): array {
        if (!$this->isGranted(RoleInterface::ROLE_SUPER_ADMIN)) {
            $criteria[] = $this->getRepository()->getSuperAdminFilter($alias);
        }

        return parent::getEntities($sortedFields, $criteria, $alias);
    }

    /**
     * Returns if the given user is the same as the logged-in user.
     */
    private function isConnectedUser(User $user): bool
    {
        return $this->isSameUser($user, $this->getUser());
    }

    /**
     * Returns if the given user is the same as the original user.
     */
    private function isOriginalUser(User $user, Security $security): bool
    {
        $token = $security->getToken();
        if ($token instanceof SwitchUserToken) {
            return $this->isSameUser($user, $token->getOriginalToken()->getUser());
        }

        return false;
    }

    /**
     * Returns if the given users are equal.
     */
    private function isSameUser(User $user, ?UserInterface $value): bool
    {
        return $value instanceof User && $value->getId() === $user->getId();
    }

    /**
     * @param User[] $users
     */
    private function successResetPassword(array $users): void
    {
        $count = \count($users);
        if (1 === $count) {
            $user = \reset($users);
            if (false !== $user) {
                $this->successTrans('user.reset.success', ['%name%' => $user->getUserIdentifier()]);

                return;
            }
        }

        $this->successTrans('user.reset_all.success', ['%count%' => $count]);
    }
}
