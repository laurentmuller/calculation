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
use App\Traits\RoleTranslatorTrait;
use App\Util\FileUtils;
use App\Util\FormatUtils;
use App\Util\Utils;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\SwitchUserToken;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

/**
 * The users table.
 *
 * @template-extends AbstractEntityTable<User>
 */
class UserTable extends AbstractEntityTable
{
    use RoleTranslatorTrait;

    /**
     * Constructor.
     */
    public function __construct(UserRepository $repository, private readonly TranslatorInterface $translator, private readonly Environment $twig, private readonly Security $security)
    {
        parent::__construct($repository);
    }

    /**
     * Translate the user's enabled state.
     */
    public function formatEnabled(bool $enabled): string
    {
        $key = $enabled ? 'common.value_enabled' : 'common.value_disabled';

        return $this->trans($key);
    }

    /**
     * Format the user's image.
     *
     * @throws \Twig\Error\Error
     */
    public function formatImage(?string $image, User $user): string
    {
        if (Utils::isString($image)) {
            return $this->twig->render('macros/_cell_user_image.html.twig', ['user' => $user]);
        }

        return '';
    }

    /**
     * Format the last login date.
     */
    public function formatLastLogin(?\DateTimeInterface $date): string
    {
        if ($date instanceof \DateTimeInterface) {
            return (string) FormatUtils::formatDateTime($date);
        }

        return $this->trans('common.value_none');
    }

    /**
     * Translate the user's role.
     */
    public function formatRole(string $role): string
    {
        return $this->translateRole($role);
    }

    /**
     * {@inheritDoc}
     */
    public function getTranslator(): TranslatorInterface
    {
        return $this->translator;
    }

    /**
     * {@inheritDoc}
     */
    protected function createDefaultQueryBuilder(string $alias = AbstractRepository::DEFAULT_ALIAS): QueryBuilder
    {
        $query = parent::createDefaultQueryBuilder($alias);

        $user = $this->security->getUser();
        if ($user instanceof User && !$user->isSuperAdmin()) {
            $role = RoleInterface::ROLE_SUPER_ADMIN;
            $criteria = "$alias.role <> '$role' or $alias.role IS NULL";
            $query->andWhere($criteria);
        }

        return $query;
    }

    /**
     * {@inheritdoc}
     */
    protected function getColumnDefinitions(): string
    {
        return FileUtils::buildPath(__DIR__, 'Definition', 'user.json');
    }

    /**
     * {@inheritdoc}
     */
    protected function getDefaultOrder(): array
    {
        return ['username' => self::SORT_ASC];
    }

    /**
     * {@inheritDoc}
     */
    protected function updateResults(DataQuery $query, DataResults &$results): void
    {
        parent::updateResults($query, $results);
        if (!$query->callback) {
            $results->addAttribute('row-style', 'styleTextMuted');
            $results->addAttribute('original-user-id', $this->getOriginalUserId());
        }
    }

    private function getOriginalUserId(): int
    {
        $token = $this->security->getToken();
        if ($token instanceof SwitchUserToken) {
            $user = $token->getOriginalToken()->getUser();
            if ($user instanceof User) {
                return (int) $user->getId();
            }
        }

        return 0;
    }
}
