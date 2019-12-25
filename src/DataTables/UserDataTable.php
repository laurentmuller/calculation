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

namespace App\DataTables;

use App\DataTables\Columns\DataColumn;
use App\DataTables\Tables\EntityDataTable;
use App\Entity\User;
use App\Repository\BaseRepository;
use App\Repository\UserRepository;
use App\Service\ApplicationService;
use App\Utils\Utils;
use DataTables\DataTablesInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

/**
 * User data table handler.
 *
 * @author Laurent Muller
 */
class UserDataTable extends EntityDataTable
{
    /**
     * The datatable identifier.
     */
    public const ID = User::class;

    /**
     * @var Environment
     */
    private $environment;

    /**
     * @var bool
     */
    private $superAdmin = false;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * Constructor.
     *
     * @param ApplicationService    $application  the application to get parameters
     * @param SessionInterface      $session      the session to save/retrieve user parameters
     * @param DataTablesInterface   $datatables   the datatables to handle request
     * @param UserRepository        $repository   the repository to get entities
     * @param Environment           $environment  the Twig environment to render cells
     * @param TranslatorInterface   $translator   the service to translate messages
     * @param TokenStorageInterface $tokenStorage the token service to get current user role
     */
    public function __construct(ApplicationService $application, SessionInterface $session, DataTablesInterface $datatables, UserRepository $repository, Environment $environment, TranslatorInterface $translator, TokenStorageInterface $tokenStorage)
    {
        parent::__construct($application, $session, $datatables, $repository);

        $this->environment = $environment;
        $this->translator = $translator;

        // check if current user has the super admin role
        if ($token = $tokenStorage->getToken()) {
            if ($user = $token->getUser()) {
                if ($user instanceof User && $user->isSuperAdmin()) {
                    $this->superAdmin = true;
                }
            }
        }
    }

    /**
     * Render the image cell content with the user's image.
     *
     * @param string $image the image name
     * @param User   $item  the user
     *
     * @return string the image cell content
     */
    public function renderImage(?string $image, User $item): string
    {
        return $this->environment->render('user/user_image_cell.html.twig', ['item' => $item]);
    }

    /**
     * Translate the user's enabled state.
     *
     * @param bool $enabled the user enablement state
     *
     * @return string the translated enabled state
     */
    public function translateEnabled(bool $enabled): string
    {
        $key = $enabled ? 'common.value_enabled' : 'common.value_disabled';

        return $this->translator->trans($key);
    }

    /**
     * Translate the user's role.
     *
     * @param string[] $roles the user's roles
     * @param User     $user  the user to get enabled state for
     *
     * @return string the translated role
     */
    public function translateRole(array $roles, User $user): string
    {
        return Utils::translateRole($this->translator, $user->getRole());
    }

    /**
     * {@inheritdoc}
     */
    protected function createColumns(): array
    {
        // callback
        $localeDateTime = function (\DateTimeInterface $date) {
            return $this->localeDateTime($date);
        };

        return [
            DataColumn::hidden('id'),
            DataColumn::instance('imageName')
                ->setTitle('user.fields.image')
                ->setClassName('text-image')
                ->setSearchable(false)
                ->setOrderable(false)
                ->setRawData(true)
                ->setFormatter([$this, 'renderImage']),
            DataColumn::instance('username')
                ->setTitle('user.fields.username_short')
                ->setClassName('w-15')
                ->setDefault(true),
            DataColumn::instance('roles')
                ->setTitle('user.fields.role')
                ->setClassName('w-25 cell')
                ->setFormatter([$this, 'translateRole']),
            DataColumn::instance('email')
                ->setTitle('user.fields.email')
                ->setClassName('w-25 cell'),
            DataColumn::instance('enabled')
                ->setTitle('user.fields.enabled')
                ->setClassName('w-15 cell')
                ->setFormatter([$this, 'translateEnabled']),
            DataColumn::instance('lastLogin')
                ->setTitle('user.fields.lastLogin')
                ->setClassName('text-date-time')
                ->setFormatter($localeDateTime),
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function createQueryBuilder($alias = BaseRepository::DEFAULT_ALIAS): QueryBuilder
    {
        // default
        $builder = parent::createQueryBuilder($alias);

        // filter
        if (!$this->superAdmin) {
            $field = 'roles';
            $value = '%' . User::ROLE_SUPER_ADMIN . '%';
            $builder->where("{$alias}.{$field} NOT LIKE :{$field}")
                ->setParameter($field, $value);
        }

        return $builder;
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultOrder(): array
    {
        return ['username' => DataColumn::SORT_ASC];
    }
}
