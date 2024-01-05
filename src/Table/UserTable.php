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
use App\Interfaces\SortModeInterface;
use App\Repository\AbstractRepository;
use App\Repository\UserRepository;
use App\Traits\RoleTranslatorTrait;
use App\Utils\FileUtils;
use App\Utils\FormatUtils;
use App\Utils\StringUtils;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

/**
 * The users table.
 *
 * @template-extends AbstractEntityTable<User, UserRepository>
 */
class UserTable extends AbstractEntityTable
{
    use RoleTranslatorTrait;

    public function __construct(
        UserRepository $repository,
        private readonly TranslatorInterface $translator,
        private readonly Environment $twig,
        private readonly Security $security
    ) {
        parent::__construct($repository);
    }

    /**
     * Translate the user's enabled state.
     *
     * @psalm-api
     */
    public function formatEnabled(bool $enabled): string
    {
        return $this->trans($enabled ? 'common.value_enabled' : 'common.value_disabled');
    }

    /**
     * Format the user's image.
     *
     * @throws \Twig\Error\Error
     *
     * @psalm-api
     */
    public function formatImage(?string $image, array $user): string
    {
        if (StringUtils::isString($image)) {
            return $this->twig->render('macros/_cell_user_image.html.twig', ['user' => $user]);
        }

        return '';
    }

    /**
     * Format the last login date.
     *
     * @psalm-api
     */
    public function formatLastLogin(?\DateTimeInterface $date): string
    {
        if ($date instanceof \DateTimeInterface) {
            return FormatUtils::formatDateTime($date);
        }

        return $this->trans('common.value_none');
    }

    /**
     * @throws \Twig\Error\Error
     *
     * @psalm-api
     */
    public function formatRole(?string $role): string
    {
        $role ??= RoleInterface::ROLE_USER;

        return $this->twig->render('macros/_cell_user_role.html.twig', [
            'role' => $this->translateRole($role),
            'icon' => $this->getRoleIcon($role)]);
    }

    public function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }

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

    protected function getColumnDefinitions(): string
    {
        return FileUtils::buildPath(__DIR__, 'Definition', 'user.json');
    }

    protected function getDefaultOrder(): array
    {
        return ['username' => SortModeInterface::SORT_ASC];
    }

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
