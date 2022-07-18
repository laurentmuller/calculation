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
use App\Pdf\PdfCell;
use App\Pdf\PdfColumn;
use App\Pdf\PdfImageCell;
use App\Pdf\PdfStyle;
use App\Pdf\PdfTableBuilder;
use App\Pdf\PdfTextColor;
use App\Traits\RoleTranslatorTrait;
use App\Util\FormatUtils;
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
     * Constructor.
     *
     * @param User[] $entities
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function __construct(AbstractController $controller, array $entities, private readonly StorageInterface $storage)
    {
        parent::__construct($controller, $entities);
    }

    /**
     * {@inheritdoc}
     *
     * @param User[] $entities
     */
    protected function doRender(array $entities): bool
    {
        // title
        $this->setTitleTrans('user.list.title');

        // styles
        $disabledStyle = PdfStyle::getCellStyle()->setTextColor(PdfTextColor::red());
        $enabledStyle = PdfStyle::getCellStyle()->setTextColor(PdfTextColor::darkGreen());

        // new page
        $this->AddPage();

        // table
        $table = new PdfTableBuilder($this);
        $table->addColumns(
            PdfColumn::center($this->trans('user.fields.imageFile'), 18, true),
            PdfColumn::left($this->trans('user.fields.username'), 25),
            PdfColumn::left($this->trans('user.fields.email'), 30),
            PdfColumn::left($this->trans('user.fields.role'), 35, true),
            PdfColumn::left($this->trans('user.fields.enabled'), 18, true),
            PdfColumn::left($this->trans('user.fields.lastLogin'), 30, true)
        )->outputHeaders();

        foreach ($entities as $entity) {
            $enabled = $entity->isEnabled();
            $style = $enabled ? $enabledStyle : $disabledStyle;
            $text = $this->booleanFilter($enabled, 'common.value_enabled', 'common.value_disabled', true);
            $role = $this->translateRole($entity->getRole());
            $cell = $this->getImageCell($entity);

            $table->startRow()
                ->addCell($cell)
                ->add($entity->getUserIdentifier())
                ->add($entity->getEmail())
                ->add($role)
                ->add(text: $text, style: $style)
                ->add($this->formatLastLogin($entity->getLastLogin()))
                ->endRow();
        }

        // count
        return $this->renderCount($entities, 'counters.users');
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
            $cell->resize(0, $size);
        } elseif ($width < $height) {
            $cell->resize($size);
        } elseif ($width !== $size) {
            $cell->resize($size);
        }

        return $cell;
    }
}
