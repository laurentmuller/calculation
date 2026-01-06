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

use App\Attribute\AddEntityRoute;
use App\Attribute\DeleteEntityRoute;
use App\Attribute\EditEntityRoute;
use App\Attribute\ExcelRoute;
use App\Attribute\ForAdmin;
use App\Attribute\GetPostRoute;
use App\Attribute\GetRoute;
use App\Attribute\IndexRoute;
use App\Attribute\PdfRoute;
use App\Attribute\ShowEntityRoute;
use App\Entity\User;
use App\Enums\EntityPermission;
use App\Form\User\ResetAllPasswordType;
use App\Form\User\UserChangePasswordType;
use App\Form\User\UserCommentType;
use App\Form\User\UserRightsType;
use App\Interfaces\EntityInterface;
use App\Interfaces\RoleInterface;
use App\Model\Role;
use App\Model\UserComment;
use App\Report\UsersReport;
use App\Report\UsersRightsReport;
use App\Repository\AbstractRepository;
use App\Repository\UserRepository;
use App\Resolver\DataQueryValueResolver;
use App\Response\PdfResponse;
use App\Response\SpreadsheetResponse;
use App\Service\FontAwesomeService;
use App\Service\MailerService;
use App\Service\PasswordTooltipService;
use App\Service\ResetPasswordService;
use App\Service\RoleBuilderService;
use App\Service\RoleService;
use App\Spreadsheet\UserRightsDocument;
use App\Spreadsheet\UsersDocument;
use App\Table\DataQuery;
use App\Table\UserTable;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\ValueResolver;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordToken;
use Vich\UploaderBundle\Storage\StorageInterface;

/**
 * The controller for user entities.
 *
 * @extends AbstractEntityController<User, UserRepository>
 */
#[ForAdmin]
#[Route(path: '/user', name: 'user_')]
class UserController extends AbstractEntityController
{
    public function __construct(UserRepository $repository, private readonly PasswordTooltipService $service)
    {
        parent::__construct($repository);
    }

    /**
     * Delete an user.
     */
    #[DeleteEntityRoute]
    public function delete(Request $request, User $item, Security $security, LoggerInterface $logger): Response
    {
        if ($this->isConnectedUser($item) || $this->isOriginalUser($item, $security)) {
            $this->warningTrans('user.delete.connected', ['%name%' => $item]);

            return $this->redirectToDefaultRoute($request, $item);
        }

        return $this->deleteEntity($request, $item, $logger);
    }

    /**
     * Add or edit a user.
     */
    #[AddEntityRoute]
    #[EditEntityRoute]
    public function edit(Request $request, ?User $item): Response
    {
        return $this->editEntity($request, $item ?? new User());
    }

    /**
     * Export the customers to a Spreadsheet document.
     */
    #[ExcelRoute]
    public function excel(RoleService $roleService, StorageInterface $storage): SpreadsheetResponse
    {
        $entities = $this->getEntitiesByUserName();

        return $this->renderSpreadsheetDocument(new UsersDocument($this, $entities, $roleService, $storage));
    }

    /**
     * Render the table view.
     */
    #[IndexRoute]
    public function index(
        UserTable $table,
        LoggerInterface $logger,
        #[ValueResolver(DataQueryValueResolver::class)]
        DataQuery $query = new DataQuery()
    ): Response {
        return $this->handleTableRequest($table, $logger, $query, 'user/user_table.html.twig');
    }

    /**
     * Send a comment from the current user to the selected user.
     */
    #[GetPostRoute(path: '/message/{id}', name: 'message', requirements: self::ID_REQUIREMENT)]
    public function message(
        Request $request,
        User $user,
        #[CurrentUser]
        User $from,
        MailerService $service,
        LoggerInterface $logger
    ): Response {
        if ($this->isConnectedUser($user)) {
            $this->warningTrans('user.message.connected');

            return $this->redirectToDefaultRoute($request, $user);
        }

        $comment = UserComment::instance($this->getApplicationName())
            ->setFrom($from)
            ->setTo($user);
        $form = $this->createForm(UserCommentType::class, $comment)
            ->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $service->sendComment($comment);
                $this->successTrans('user.message.success', ['%name%' => $user->getDisplay()]);

                return $this->redirectToDefaultRoute($request, $user);
            } catch (TransportExceptionInterface $e) {
                return $this->renderFormException('user.message.error', $e, $logger);
            }
        }

        return $this->render('user/user_comment.html.twig', [
            'form' => $form,
            'message' => true,
        ]);
    }

    /**
     * Change password for an existing user.
     */
    #[GetPostRoute(path: '/password/{id}', name: 'password', requirements: self::ID_REQUIREMENT)]
    public function password(Request $request, User $item, PasswordTooltipService $service): Response
    {
        $form = $this->createForm(UserChangePasswordType::class, $item)
            ->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->saveToDatabase($item);

            return $this->redirectToDefaultRoute($request, $item);
        }

        return $this->render('user/user_password.html.twig', [
            'item' => $item,
            'form' => $form,
            'tooltips' => $service->getTooltips(),
        ]);
    }

    /**
     * Export the users to a PDF document.
     */
    #[PdfRoute]
    public function pdf(
        StorageInterface $storage,
        RoleService $roleService,
        FontAwesomeService $fontService,
    ): PdfResponse {
        $entities = $this->getEntitiesByUserName();

        return $this->renderPdfDocument(new UsersReport($this, $entities, $storage, $roleService, $fontService));
    }

    /**
     * Clear all requested reset passwords.
     */
    #[GetPostRoute(path: '/reset', name: 'reset_all')]
    public function resetAllPasswordRequest(Request $request): Response
    {
        $repository = $this->getRepository();
        $users = $repository->getResettableUsers();
        $count = \count($users);
        if (0 === $count) {
            $this->warningTrans('user.reset_all.empty');

            return $this->redirectToDefaultRoute($request);
        }

        if (1 === $count) {
            $params = $this->getUrlGenerator()
                ->routeParams($request, \reset($users));

            return $this->redirectToRoute('user_reset', $params);
        }

        $name = 'users';
        $data = [$name => $users];
        $form = $this->createFormBuilder($data)
            ->add($name, ResetAllPasswordType::class)
            ->getForm()
            ->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var User[] $users */
            $users = $form->get($name)->getData();
            $repository->resetPasswordRequest($users);
            $this->successTrans('user.reset_all.success', ['%count%' => \count($users)]);

            return $this->redirectToDefaultRoute($request);
        }

        return $this->render('user/user_reset_all_passwords.html.twig', ['form' => $form]);
    }

    /**
     * Clear the request reset password.
     */
    #[GetPostRoute(path: '/reset/{id}', name: 'reset', requirements: self::ID_REQUIREMENT)]
    public function resetPasswordRequest(Request $request, User $item): Response
    {
        $parameters = ['%name%' => $item];
        $form = $this->createForm(FormType::class)
            ->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            if ($this->removeResetPasswordRequest($item)) {
                $this->successTrans('user.reset.success', $parameters);
            } else {
                $this->warningTrans('user.reset.error', $parameters);
            }

            return $this->redirectToDefaultRoute($request, $item);
        }

        return $this->render('cards/card_delete.html.twig', [
            'form' => $form,
            'title' => 'user.reset.title',
            'title_icon' => 'eraser',
            'message' => 'user.reset.confirmation',
            'message_parameters' => $parameters,
        ]);
    }

    /**
     * Edit user access rights.
     */
    #[GetPostRoute(path: '/rights/{id}', name: 'rights', requirements: self::ID_REQUIREMENT)]
    public function rights(
        Request $request,
        User $item,
        RoleService $service,
        RoleBuilderService $builder
    ): Response {
        if ($this->isConnectedUser($item) && !$service->hasRole($item, RoleInterface::ROLE_SUPER_ADMIN)) {
            $this->warningTrans('user.rights.connected');

            return $this->redirectToDefaultRoute($request, $item);
        }

        $default = $this->getDefaultRole($builder, $item);
        $form = $this->createForm(UserRightsType::class, $item)
            ->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // same as default?
            if ($item->getRights() === $default->getRights()) {
                $item->setRights(null);
                if ($item->isEnabled()) {
                    $item->setOverwrite(false);
                }
            }
            $this->getRepository()->flush();

            return $this->redirectToDefaultRoute($request, $item);
        }

        return $this->render('user/user_rights.html.twig', [
            'form' => $form,
            'item' => $item,
            'default' => $default,
            'entities' => EntityPermission::sorted(),
        ]);
    }

    /**
     * Export the user access rights to a Spreadsheet document.
     */
    #[GetRoute(path: '/rights/excel', name: 'rights_excel')]
    public function rightsExcel(RoleService $roleService, RoleBuilderService $roleBuilderService): SpreadsheetResponse
    {
        $entities = $this->getEntitiesByUserName();

        return $this->renderSpreadsheetDocument(
            new UserRightsDocument($this, $entities, $roleService, $roleBuilderService)
        );
    }

    /**
     * Export user access rights to a PDF document.
     */
    #[GetRoute(path: '/rights/pdf', name: 'rights_pdf')]
    public function rightsPdf(
        RoleService $roleService,
        RoleBuilderService $roleBuilderService,
        FontAwesomeService $fontAwesomeService
    ): PdfResponse {
        $entities = $this->getEntitiesByUserName();

        return $this->renderPdfDocument(
            new UsersRightsReport($this, $entities, $roleService, $roleBuilderService, $fontAwesomeService)
        );
    }

    /**
     * Sends an email to the user to reset its password.
     */
    #[GetPostRoute(path: '/reset/send/{id}', name: 'reset_send', requirements: self::ID_REQUIREMENT)]
    public function sendPasswordRequest(Request $request, User $item, ResetPasswordService $service): Response
    {
        $parameters = ['%name%' => $item];
        $form = $this->createForm(FormType::class)
            ->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $this->removeResetPasswordRequest($item);
            $result = $service->sendEmail($request, $item);
            if (false === $result) {
                $this->warningTrans('reset.user_not_found', $parameters, 'security');
            } elseif (!$result instanceof ResetPasswordToken) {
                $this->warningTrans('reset.token_not_found', $parameters, 'security');
            } else {
                $this->successTrans('reset.token_send', $parameters, 'security');
            }

            return $this->redirectToDefaultRoute($request, $item);
        }

        return $this->render('cards/card_confirm.html.twig', [
            'form' => $form,
            'title' => 'user.send.title',
            'title_icon' => 'envelope-circle-check fas',
            'message' => 'user.send.message',
            'message_parameters' => $parameters,
            'submit_text' => 'user.send.submit',
        ]);
    }

    /**
     * Show the properties of a user.
     */
    #[ShowEntityRoute]
    public function show(User $item): Response
    {
        return $this->showEntity($item);
    }

    /**
     * @param User $item
     */
    #[\Override]
    protected function editEntity(Request $request, EntityInterface $item, array $parameters = []): Response
    {
        $parameters['tooltips'] = $item->isNew() ? $this->service->getTooltips() : null;

        return parent::editEntity($request, $item, $parameters);
    }

    #[\Override]
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

    private function getDefaultRole(RoleBuilderService $builder, User $user): Role
    {
        if ($user->isSuperAdmin()) {
            return $builder->getRole($user);
        }
        if ($user->isAdmin()) {
            return $this->getApplicationParameters()
                ->getRights()
                ->getAdminRole();
        }
        if ($user->isEnabled()) {
            return $this->getApplicationParameters()
                ->getRights()
                ->getUserRole();
        }

        return $builder->getRole($user);
    }

    /**
     * @return User[]
     */
    private function getEntitiesByUserName(): array
    {
        return $this->getEntities('username');
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
     * Remove the reset password request for the given user.
     */
    private function removeResetPasswordRequest(User $user): bool
    {
        if (!$user->isResetPassword()) {
            return false;
        }
        $this->getRepository()->removeResetPasswordRequest($user);

        return true;
    }
}
