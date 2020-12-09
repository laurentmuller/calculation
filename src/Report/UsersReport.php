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

namespace App\Report;

use App\Controller\AbstractController;
use App\Entity\User;
use App\Pdf\PdfCell;
use App\Pdf\PdfColumn;
use App\Pdf\PdfImageCell;
use App\Pdf\PdfStyle;
use App\Pdf\PdfTableBuilder;
use App\Pdf\PdfTextColor;
use App\Util\FormatUtils;
use App\Util\Utils;
use Vich\UploaderBundle\Mapping\PropertyMappingFactory;
use Vich\UploaderBundle\Storage\StorageInterface;

/**
 * Report for the list of users.
 *
 * @author Laurent Muller
 */
class UsersReport extends AbstractArrayReport
{
    /**
     * The mapping factory.
     *
     * @var PropertyMappingFactory
     */
    private $factory;

    /**
     * The configured file property name.
     *
     * @var string|null
     */
    private $fieldName;

    /**
     * The image storage.
     *
     * @var StorageInterface
     */
    private $storage;

    /**
     * Constructor.
     *
     * @param AbstractController     $controller the parent controller
     * @param User[]                 $entities   the users to export
     * @param PropertyMappingFactory $factory    the factory to get mapping informations
     * @param StorageInterface       $storage    the storage to get images path
     */
    public function __construct(AbstractController $controller, array $entities, PropertyMappingFactory $factory, StorageInterface $storage)
    {
        parent::__construct($controller, $entities);
        $this->factory = $factory;
        $this->storage = $storage;
    }

    /**
     * {@inheritdoc}
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
        $table->addColumn(PdfColumn::center($this->trans('user.fields.imageFile'), 18, true))
            ->addColumn(PdfColumn::left($this->trans('user.fields.username'), 25))
            ->addColumn(PdfColumn::left($this->trans('user.fields.email'), 30))
            ->addColumn(PdfColumn::left($this->trans('user.fields.role'), 35, true))
            ->addColumn(PdfColumn::left($this->trans('user.fields.enabled'), 18, true))
            ->addColumn(PdfColumn::left($this->trans('user.fields.lastLogin'), 30, true))
            ->outputHeaders();

        /** @var User $entity */
        foreach ($entities as $entity) {
            $enabled = $entity->isEnabled();
            $style = $enabled ? $enabledStyle : $disabledStyle;
            $text = $this->booleanFilter($enabled, 'common.value_enabled', 'common.value_disabled', true);
            $role = Utils::translateRole($this->translator, $entity->getRole());
            $cell = $this->getImageCell($entity);

            $table->startRow()
                ->addCell($cell)
                ->add($entity->getUsername())
                ->add($entity->getEmail())
                ->add($role)
                ->add($text, 1, $style)
                ->add($this->formatLastLogin($entity->getLastLogin()))
                ->endRow();
        }

        // count
        return $this->renderCount(\count($entities));
    }

    /**
     * Format the last login date.
     *
     * @param \DateTimeInterface $date the date to format
     *
     * @return string the formatted date
     */
    private function formatLastLogin(?\DateTimeInterface $date): string
    {
        if (null === $date) {
            return $this->trans('common.value_none');
        }

        return FormatUtils::formatDateTime($date);
    }

    /**
     * Gets the configured file property name used to resolve path.
     *
     * @param User $user the user to get field name
     *
     * @return string|null the configured file property name or null if none
     */
    private function getFieldName(User $user): ?string
    {
        if (!$this->fieldName) {
            $mappings = $this->factory->fromObject($user);
            if (!empty($mappings)) {
                $this->fieldName = $mappings[0]->getFilePropertyName();
            }
        }

        return $this->fieldName;
    }

    /**
     * Gets the image cell for the given user.
     *
     * @param User $user the user
     *
     * @return PdfCell the image cell, if applicable, an empty cell otherwise
     */
    private function getImageCell(User $user): PdfCell
    {
        $path = $this->getImagePath($user);
        if (empty($path)) {
            return new PdfCell();
        }

        $size = 64;
        $cell = new PdfImageCell($path);
        [$width, $height] = $cell->getOriginalSize();
        if ($width > $height) {
            $cell->resize(0, $size);
        } elseif ($width < $height) {
            $cell->resize($size, 0);
        } elseif ($width !== $size) {
            $cell->resize($size, 0);
        }

        return $cell;
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
        if ($fieldName = $this->getFieldName($user)) {
            $path = $this->storage->resolvePath($user, $fieldName);
            if ($path && \is_file($path)) {
                return $path;
            }
        }

        return null;
    }
}
