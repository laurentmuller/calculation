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

namespace App\Table;

use App\Entity\User;
use App\Interfaces\RoleInterface;
use App\Repository\AbstractRepository;
use App\Repository\UserRepository;
use App\Service\RoleService;
use App\Utils\FileUtils;
use App\Utils\FormatUtils;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Clock\DatePoint;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

/**
 * The user table.
 *
 * @extends AbstractEntityTable<User, UserRepository>
 */
class UserTable extends AbstractEntityTable
{
    public function __construct(
        UserRepository $repository,
        private readonly RoleService $roleService,
        private readonly TranslatorInterface $translator,
        private readonly Environment $twig,
        private readonly Security $security
    ) {
        parent::__construct($repository);
    }

    /**
     * Translate the user's enabled state.
     */
    public function formatEnabled(bool $enabled): string
    {
        return $this->translator->trans($enabled ? 'common.value_enabled' : 'common.value_disabled');
    }

    /**
     * Format the user's image.
     *
     * @throws \Twig\Error\Error
     */
    public function formatImage(?string $image, array $user): string
    {
        return $this->twig->render('macros/_cell_user_image.html.twig', ['user' => $user]);
    }

    /**
     * Format the last login date.
     */
    public function formatLastLogin(?DatePoint $date): string
    {
        if ($date instanceof DatePoint) {
            return FormatUtils::formatDateTime($date);
        }

        return $this->translator->trans('common.value_none');
    }

    /**
     * Format the user's role.
     *
     * @phpstan-param RoleInterface::ROLE_*|null $role
     */
    public function formatRole(?string $role): string
    {
        return $this->roleService->getRoleIconAndName($role ?? RoleInterface::ROLE_USER);
    }

    #[\Override]
    protected function createQueryBuilder(string $alias = AbstractRepository::DEFAULT_ALIAS): QueryBuilder
    {
        $user = $this->security->getUser();
        $repository = $this->getRepository();
        $query = $repository->getTableQueryBuilder($alias);
        if (!$user instanceof User || !$user->isSuperAdmin()) {
            $criteria = $repository->getSuperAdminFilter($alias);
            $query->andWhere($criteria);
        }

        return $query;
    }

    #[\Override]
    protected function getColumnDefinitions(): string
    {
        return FileUtils::buildPath(__DIR__, 'Definition', 'user.json');
    }

    #[\Override]
    protected function updateResults(DataQuery $query, DataResults &$results): void
    {
        parent::updateResults($query, $results);
        if (!$query->callback) {
            $results->addAttribute('row-style', 'styleTextMuted');
            $results->addAttribute('original-user-id', $this->getOriginalUserId());
            $results->addCustomData('resetPasswords', $this->isResettableUsers());
        }
    }

    private function getOriginalUserId(): int
    {
        $token = $this->security->getToken();
        if (!$token instanceof SwitchUserToken) {
            return 0;
        }
        $user = $token->getOriginalToken()->getUser();
        if (!$user instanceof User) {
            return 0;
        }

        return (int) $user->getId();
    }

    private function isResettableUsers(): bool
    {
        return $this->getRepository()->isResettableUsers();
    }
}
