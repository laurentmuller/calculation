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

namespace App\Report;

use App\Controller\AbstractController;
use App\Entity\User;
use App\Pdf\Colors\PdfTextColor;
use App\Pdf\PdfCell;
use App\Pdf\PdfImageCell;
use App\Pdf\PdfStyle;
use App\Pdf\PdfTable;
use App\Service\FontAwesomeService;
use App\Traits\RoleTranslatorTrait;
use App\Utils\FormatUtils;
use Vich\UploaderBundle\Storage\StorageInterface;

/**
 * Report for the list of users.
 *
 * @extends AbstractArrayReport<User>
 */
class UsersReport extends AbstractArrayReport
{
    use RoleTranslatorTrait;

    /**
     * @var array<string, PdfCell>
     */
    private array $cells = [];

    /**
     * @param User[] $entities
     */
    public function __construct(
        AbstractController $controller,
        array $entities,
        private readonly StorageInterface $storage,
        private readonly FontAwesomeService $service
    ) {
        parent::__construct($controller, $entities);
    }

    #[\Override]
    protected function doRender(array $entities): bool
    {
        $this->setTitleTrans('user.list.title');
        $disabledStyle = PdfStyle::getCellStyle()->setTextColor(PdfTextColor::red());
        $enabledStyle = PdfStyle::getCellStyle()->setTextColor(PdfTextColor::darkGreen());

        $this->addPage();
        $table = $this->createTable();
        foreach ($entities as $entity) {
            $this->outputEntity($table, $entity, $enabledStyle, $disabledStyle);
        }

        return $this->renderCount($table, $entities, 'counters.users');
    }

    private function createTable(): PdfTable
    {
        return PdfTable::instance($this)
            ->addColumns(
                $this->centerColumn('user.fields.imageFile', 18, true),
                $this->leftColumn('user.fields.username', 25),
                $this->leftColumn('user.fields.email', 35),
                $this->leftColumn('user.fields.role', 40, true),
                $this->leftColumn('user.fields.enabled', 18, true),
                $this->leftColumn('user.fields.lastLogin', 30, true)
            )->outputHeaders();
    }

    private function formatEditable(bool $editable): string
    {
        return $this->trans($editable ? 'common.value_enabled' : 'common.value_disabled');
    }

    /**
     * Format the last login date.
     */
    private function formatLastLogin(?\DateTimeInterface $date): string
    {
        if ($date instanceof \DateTimeInterface) {
            return FormatUtils::formatDateTime($date);
        }

        return $this->trans('common.value_none');
    }

    /**
     * Gets the image cell for the given user.
     */
    private function getImageCell(User $user): PdfCell
    {
        $path = $user->getImagePath($this->storage);
        if (null === $path) {
            return new PdfCell();
        }
        $size = 64;
        $cell = new PdfImageCell($path);
        [$width, $height] = $cell->getOriginalSize();
        if ($width > $height) {
            return $cell->resize(width: $size);
        }
        if ($width < $height || $width !== $size) {
            return $cell->resize(height: $size);
        }

        return $cell;
    }

    private function getRoleCell(User $user): PdfCell
    {
        $role = $user->getRole();
        if (isset($this->cells[$role])) {
            return $this->cells[$role];
        }

        $icon = $this->getRoleIcon($role);
        $text = $this->translateRole($role);
        $cell = $this->service->getFontAwesomeCell(icon: $icon, text: $text) ?? new PdfCell($text);

        return $this->cells[$role] = $cell;
    }

    private function outputEntity(PdfTable $table, User $entity, PdfStyle $enabledStyle, PdfStyle $disabledStyle): void
    {
        $enabled = $entity->isEnabled();
        $editableText = $this->formatEditable($enabled);
        $editableStyle = $enabled ? $enabledStyle : $disabledStyle;

        $table->startRow()
            ->addCell($this->getImageCell($entity))
            ->add($entity->getUserIdentifier())
            ->add($entity->getEmail())
            ->addCell($this->getRoleCell($entity))
            ->add(text: $editableText, style: $editableStyle)
            ->add($this->formatLastLogin($entity->getLastLogin()))
            ->endRow();
    }
}
