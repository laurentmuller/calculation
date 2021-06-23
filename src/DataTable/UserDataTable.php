<?php
/*
 * This file is part of the Calculation package.
 *
 * (c) bibi.nu. <bibi@bibi.nu>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace App\DataTable;

use App\DataTable\Model\AbstractEntityDataTable;
use App\DataTable\Model\DataColumnFactory;
use App\Entity\User;
use App\Interfaces\RoleInterface;
use App\Repository\AbstractRepository;
use App\Repository\UserRepository;
use App\Util\Utils;
use DataTables\DataTablesInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

/**
 * User data table handler.
 *
 * @author Laurent Muller
 *
 * @template-extends AbstractEntityDataTable<User>
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
     */
    public function __construct(RequestStack $requestStack, DataTablesInterface $datatables, UserRepository $repository, Environment $environment, TranslatorInterface $translator, Security $security)
    {
        parent::__construct($requestStack, $datatables, $repository, $environment);
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
    public function formatEnabled(bool $enabled): string
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
    public function formatImage(?string $image, User $item): string
    {
        return $this->renderTemplate('user/user_cell_image.html.twig', ['item' => $item]);
    }

    /**
     * Format the last login date.
     *
     * @param \DateTimeInterface $date the last login date
     *
     * @return string the formatted date
     */
    public function formatLastLogin(?\DateTimeInterface $date): string
    {
        if (null === $date) {
            return $this->translator->trans('common.value_none');
        }

        return $this->formatDateTime($date);
    }

    /**
     * Translate the user's role.
     *
     * @param string $role the user's role
     *
     * @return string the translated role
     */
    public function formatRole(string $role): string
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
        return ['username' => self::SORT_ASC];
    }
}
