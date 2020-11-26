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

namespace App\DataTable;

use App\DataTable\Model\AbstractEntityDataTable;
use App\DataTable\Model\DataColumn;
use App\DataTable\Model\DataColumnFactory;
use App\Entity\User;
use App\Interfaces\RoleInterface;
use App\Repository\AbstractRepository;
use App\Repository\UserRepository;
use App\Util\FormatUtils;
use App\Util\Utils;
use DataTables\DataTablesInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

/**
 * User data table handler.
 *
 * @author Laurent Muller
 */
class UserDataTable extends AbstractEntityDataTable
{
    /**
     * The datatable identifier.
     */
    public const ID = User::class;

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
     * @param SessionInterface    $session     the session to save/retrieve user parameters
     * @param DataTablesInterface $datatables  the datatables to handle request
     * @param UserRepository      $repository  the repository to get entities
     * @param Environment         $environment the Twig environment to render cells
     * @param TranslatorInterface $translator  the service to translate messages
     * @param Security            $security    the service to get current user role
     */
    public function __construct(SessionInterface $session, DataTablesInterface $datatables, UserRepository $repository, Environment $environment, TranslatorInterface $translator, Security $security)
    {
        parent::__construct($session, $datatables, $repository, $environment);
        $this->translator = $translator;

        // check if current user has the super admin role
        if ($user = $security->getUser()) {
            $this->superAdmin = $user instanceof User && $user->isSuperAdmin();
        }
    }

    /**
     * Translate the user's enabled state.
     *
     * @param bool $enabled the user enablement state
     *
     * @return string the translated enabled state
     */
    public function enabledFormatter(bool $enabled): string
    {
        $key = $enabled ? 'common.value_enabled' : 'common.value_disabled';

        return $this->translator->trans($key);
    }

    /**
     * Render the image cell content with the user's image.
     *
     * @param string $image the image name
     * @param User   $item  the user
     *
     * @return string the image cell content
     */
    public function imageFormatter(?string $image, User $item): string
    {
        $context = [
            'image' > $image,
            'item' => $item,
        ];

        return $this->renderTemplate('user/user_image_cell.html.twig', $context);
    }

    /**
     * Format the last login date.
     *
     * @param \DateTimeInterface $date the last login date
     *
     * @return string the formatted date
     */
    public function lastLoginFormatter(?\DateTimeInterface $date): string
    {
        if (null === $date) {
            return $this->translator->trans('common.value_none');
        }

        return FormatUtils::formatDateTime($date);
    }

    /**
     * Translate the user's role.
     *
     * @param string $role the user's role
     *
     * @return string the translated role
     */
    public function roleFormatter(string $role): string
    {
        return Utils::translateRole($this->translator, $role);
    }

    /**
     * {@inheritdoc}
     */
    protected function createColumns(): array
    {
        $path = __DIR__ . '/Definition/user.json';

        return DataColumnFactory::fromJson($this, $path);
    }

    /**
     * {@inheritdoc}
     */
    protected function createQueryBuilder($alias = AbstractRepository::DEFAULT_ALIAS): QueryBuilder
    {
        // default
        $builder = parent::createQueryBuilder($alias);

        // filter
        if (!$this->superAdmin) {
            $field = 'role';
            $value = RoleInterface::ROLE_SUPER_ADMIN;
            $builder->where("{$alias}.{$field} IS NULL");
            $builder->orWhere("{$alias}.{$field} != :{$field}")
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
