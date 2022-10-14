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

namespace App\Spreadsheet;

use App\Controller\AbstractController;
use App\Entity\User;
use App\Traits\RoleTranslatorTrait;
use App\Util\FileUtils;
use App\Util\FormatUtils;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Conditional;
use Vich\UploaderBundle\Storage\StorageInterface;

/**
 * Spreadsheet document for the list of users.
 *
 * @extends AbstractArrayDocument<User>
 */
class UsersDocument extends AbstractArrayDocument
{
    use RoleTranslatorTrait;

    /**
     * Constructor.
     *
     * @param User[] $entities
     */
    public function __construct(AbstractController $controller, array $entities, private readonly StorageInterface $storage)
    {
        parent::__construct($controller, $entities);
    }

    /**
     * {@inheritdoc}
     *
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Cache\InvalidArgumentException
     */
    protected function doRender(array $entities): bool
    {
        // initialize
        $this->start('user.list.title');

        // conditionals
        $this->createEnabledConditionals();

        // headers
        $row = $this->setHeaderValues([
            'user.fields.imageFile' => [Alignment::HORIZONTAL_LEFT, Alignment::VERTICAL_TOP],
            'user.fields.username' => [Alignment::HORIZONTAL_GENERAL, Alignment::VERTICAL_TOP],
            'user.fields.email' => [Alignment::HORIZONTAL_GENERAL, Alignment::VERTICAL_TOP],
            'user.fields.role' => [Alignment::HORIZONTAL_GENERAL, Alignment::VERTICAL_TOP],
            'user.fields.enabled' => [Alignment::HORIZONTAL_LEFT, Alignment::VERTICAL_TOP],
            'user.fields.lastLogin' => [Alignment::HORIZONTAL_LEFT, Alignment::VERTICAL_TOP],
        ]);

        // format
        $this->setFormatBoolean(5, 'common.value_enabled', 'common.value_disabled', true);

        // rows
        foreach ($entities as $entity) {
            $this->setRowValues($row, [
                null,
                $entity->getUserIdentifier(),
                $entity->getEmail(),
                $this->translateRole($entity),
                $entity->isEnabled(),
                $this->formatLastLogin($entity->getLastLogin()),
            ]);

            // image
            $path = $this->getImagePath($entity);
            if (!empty($path) && FileUtils::isFile($path)) {
                [$width, $height] = (array) \getimagesize($path);
                $this->setCellImage($path, "A$row", (int) $width, (int) $height);
            }

            ++$row;
        }

        $this->finish();

        return true;
    }

    /**
     * Creates a conditional.
     *
     * @param string $value the conditional value
     * @param string $color the conditional color
     */
    private function createConditional(string $value, string $color): Conditional
    {
        $conditional = new Conditional();
        $style = $conditional->setConditionType(Conditional::CONDITION_CELLIS)
            ->setOperatorType(Conditional::OPERATOR_EQUAL)
            ->addCondition($value)
            ->getStyle();
        $style->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_LEFT)
            ->setVertical(Alignment::VERTICAL_TOP);
        $style->getFont()
            ->getColor()
            ->setARGB($color);

        return $conditional;
    }

    /**
     * Sets the enabled/disable conditionals.
     */
    private function createEnabledConditionals(): void
    {
        $disabled = $this->createConditional('0', Color::COLOR_RED);
        $enabled = $this->createConditional('1', Color::COLOR_DARKGREEN);
        $this->setColumnConditional(5, $disabled, $enabled);
    }

    /**
     * Format the last login date.
     */
    private function formatLastLogin(?\DateTimeInterface $date): string
    {
        if ($date instanceof \DateTimeInterface) {
            return (string) FormatUtils::formatDateTime($date);
        }

        return $this->trans('common.value_none');
    }

    /**
     * Gets the user's image full path.
     *
     * @param User $user the user to get image path for
     *
     * @return string|null the image path, if exists; null otherwise
     */
    private function getImagePath(User $user): ?string
    {
        $path = $user->getImagePath($this->storage);
        if (null !== $path) {
            $path = \str_replace('192', '032', $path);
            if (FileUtils::isFile($path)) {
                return $path;
            }
        }

        return null;
    }
}
