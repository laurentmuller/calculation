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

namespace App\BootstrapTable;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Traits\RoleTranslatorTrait;
use App\Util\Utils;
use Knp\Bundle\TimeBundle\DateTimeFormatter;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

/**
 * The users table.
 *
 * @author Laurent Muller
 * @template-extends AbstractEntityTable<\App\Entity\User>
 */
class UserTable extends AbstractEntityTable
{
    use RoleTranslatorTrait;

    private DateTimeFormatter $formatter;
    private Environment $twig;

    /**
     * Contructor.
     */
    public function __construct(UserRepository $repository, TranslatorInterface $translator, Environment $twig, DateTimeFormatter $formatter)
    {
        parent::__construct($repository);
        $this->translator = $translator;
        $this->twig = $twig;
        $this->formatter = $formatter;
    }

    /**
     * Translate the user's enabled state.
     */
    public function formatEnabled(bool $enabled): string
    {
        $key = $enabled ? 'common.value_enabled' : 'common.value_disabled';

        return $this->translator->trans($key);
    }

    /**
     * Format the image user URL.
     */
    public function formatImage(?string $image, User $user): string
    {
        if (Utils::isString($image)) {
            return $this->twig->render('table/_cell_user_image.html.twig', ['user' => $user]);
        }

        return '';
    }

    /**
     * Format the last login date.
     */
    public function formatLastLogin(?\DateTimeInterface $date): string
    {
        if ($date instanceof \DateTimeInterface) {
            return $this->formatter->formatDiff($date, new \DateTime());
        }

        return $this->translator->trans('common.value_none');
    }

    /**
     * Translate the user's role.
     */
    public function formatRole(string $role): string
    {
        return $this->translateRole($role);
    }

    /**
     * {@inheritdoc}
     */
    protected function getColumnDefinitions(): string
    {
        return __DIR__ . '/Definition/user.json';
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
        }
    }
}
