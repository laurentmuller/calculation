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
use App\Pdf\PdfColumn;
use App\Pdf\PdfDrawColor;
use App\Pdf\PdfStyle;
use App\Pdf\PdfTableBuilder;
use App\Service\HelpService;
use App\Util\FileUtils;

/**
 * Report for the help documentation.
 *
 * @author Laurent Muller
 */
class HelpReport extends AbstractReport
{
    /**
     * The absolute path to the images.
     *
     * @var string
     */
    private $imagePath;

    /**
     * The help service.
     *
     * @var HelpService
     */
    private $service;

    /**
     * Constructor.
     *
     * @param AbstractController $controller the parent controller
     * @param HelpService        $service    the help service
     */
    public function __construct(AbstractController $controller, HelpService $service)
    {
        parent::__construct($controller);

        $this->service = $service;
        $this->imagePath = $service->getImagePath();
    }

    /**
     * {@inheritdoc}
     */
    public function render(): bool
    {
        // get values
        $menus = $this->service->getMainMenus();
        $dialogs = $this->service->getDialogs();
        $entities = $this->service->getEntities();
        if (empty($menus) && empty($dialogs) && empty($entities)) {
            return false;
        }

        // title
        $this->setTitleTrans('help.title');

        // content
        $newPage = true;
        $newPage = $this->outputMainMenus($menus, $newPage);
        $newPage = $this->outputDialogs($dialogs, $newPage);
        $newPage = $this->outputEntities($entities, $newPage);

        return true;
    }

    private function findEntity(array $dialog): ?array
    {
        if ($id = $dialog['entity'] ?? false) {
            return $this->service->findEntity($id);
        }

        return null;
    }

    private function findFields(?array $entity): ?array
    {
        if ($entity) {
            return $entity['fields'] ?? null;
        }

        return null;
    }

    private function formatFieldName(array $item, array $field): string
    {
        $id = $item['id'];
        $name = $field['name'];

        return $this->trans("$id.fields.$name");
    }

    private function formatFieldType(array $field): string
    {
        $type = $this->trans('help.types.' . $field['type']);
        if ($length = $field['length'] ?? null) {
            return "$type ($length)";
        }

        return $type;
    }

    private function formatRequired(bool $required): string
    {
        return $this->trans($required ? 'common.value_true' : 'common.value_false');
    }

    private function outputActions(array $actions): void
    {
        $table = new PdfTableBuilder($this);
        $table->addColumn(PdfColumn::left($this->trans('help.fields.action'), 70, true))
            ->addColumn(PdfColumn::left($this->trans('help.fields.description'), 50))
            ->outputHeaders();

        foreach ($actions as $action) {
            $table->startRow()
                ->add($this->trans($action['id']))
                ->add($action['description'])
                ->endRow();
        }
    }

    private function outputColumns(array $item, array $fields): void
    {
        $table = new PdfTableBuilder($this);
        $table->addColumn(PdfColumn::left($this->trans('help.fields.column'), 30, true))
            ->addColumn(PdfColumn::left($this->trans('help.fields.description'), 50))
            ->outputHeaders();
        foreach ($fields as $field) {
            $table->startRow()
                ->add($this->formatFieldName($item, $field))
                ->add($field['description'])
                ->endRow();
        }
    }

    private function outputConstraints(array $constraints): void
    {
        $margin = $this->getLeftMargin();
        $this->SetLeftMargin($margin + 4);
        foreach ($constraints as $constraint) {
            $this->MultiCell(0, self::LINE_HEIGHT, \strip_tags("- $constraint"), self::BORDER_NONE, self::ALIGN_LEFT);
        }
        $this->SetLeftMargin($margin);
    }

    private function outputDetails(array $details): void
    {
        $text = \strip_tags(\implode(' ', $details));
        $this->MultiCell(0, self::LINE_HEIGHT, $text, self::BORDER_NONE, self::ALIGN_LEFT);
    }

    private function outputDialog(array $item): void
    {
        // title
        $this->outputTitle($item['id']);

        // description
        if ($description = $item['description'] ?? false) {
            $this->MultiCell(0, self::LINE_HEIGHT, $description);
        }

        // image
        if ($image = $item['image'] ?? false) {
            $this->Ln(3);
            $this->outputText('help.labels.screenshot');
            $this->outputImage($image);
        }

        // details
        if ($details = $item['details'] ?? null) {
            $this->Ln(3);
            $this->outputText('help.labels.description');
            $this->outputDetails($details);
        }

        // entity and fields
        $entity = $this->findEntity($item);
        $fields = $this->findFields($entity);
        if ($entity && $fields) {
            // columns
            if (isset($item['displayEntityColumns']) && $item['displayEntityColumns']) {
                $this->Ln(3);
                $this->outputText('help.labels.edit_columns');
                $this->outputColumns($entity, $fields);
            }

            // fields
            if (isset($item['displayEntityFields']) && $item['displayEntityFields']) {
                $this->Ln(3);
                $this->outputText('help.labels.edit_fields');
                $this->outputFields($entity, $fields);
            }

            // actions
            if (isset($item['displayEntityActions']) && $item['displayEntityActions']) {
                if ($actions = $entity['actions'] ?? false) {
                    $this->Ln(3);
                    $this->outputText('help.labels.entity_actions');
                    $this->outputActions($actions);
                }
            }
        }

        // edit actions
        if ($actions = $item['editActions'] ?? false) {
            $this->Ln(3);
            $this->outputText('help.labels.edit_actions');
            $this->outputActions($actions);
        }

        // global actions
        if ($actions = $item['globalActions'] ?? false) {
            $this->Ln(3);
            $this->outputText('help.labels.global_actions');
            $this->outputActions($actions);
        }

        // forbidden
        if ($forbidden = $item['forbidden'] ?? false) {
            $this->Ln(3);
            $text = $forbidden['text'] ?? $this->trans('help.labels.forbidden_text');
            $this->outputText($text, false);
            if ($image = $forbidden['image'] ?? false) {
                $this->outputImage($image);
            }
            if ($action = $forbidden['action'] ?? false) {
                $this->Ln(3);
                $this->outputText('help.labels.edit_actions');
                $this->outputActions([$action]);
            }
        }
    }

    private function outputDialogs(array $dialogs, bool $newPage): bool
    {
        if (empty($dialogs)) {
            return false;
        }

        if ($newPage) {
            $this->AddPage();
            $newPage = false;
        }

        $this->outputTitle('help.dialog_menu', 12);
        $this->outputLine();

        foreach ($dialogs as $dialog) {
            if ($newPage) {
                $this->AddPage();
            }
            $newPage = true;
            $this->outputDialog($dialog);
        }

        return true;
    }

    private function outputEntities(array $entities, bool $newPage): bool
    {
        if (empty($entities)) {
            return false;
        }

        if ($newPage) {
            $this->AddPage();
            $newPage = false;
        }

        $this->outputTitle('help.entity_menu', 12);
        $this->outputLine();

        foreach ($entities as $entity) {
            if ($newPage) {
                $this->AddPage();
            }
            $newPage = true;
            $this->outputEntity($entity);
        }

        return true;
    }

    private function outputEntity(array $item): void
    {
        $this->outputTitle($item['id'] . '.name');

        if ($description = $item['description'] ?? false) {
            $this->MultiCell(0, self::LINE_HEIGHT, $description);
        }

        if ($fields = $this->findFields($item)) {
            $this->Ln(3);
            $this->outputText('help.labels.edit_fields');
            $this->outputFields($item, $fields);
        } else {
            $this->outputText('help.labels.entity_empty');
        }

        if ($constraints = $item['constraints'] ?? false) {
            $this->Ln(3);
            $this->outputText('help.labels.constraints');
            $this->outputConstraints($constraints);
        }

        if ($actions = $item['actions'] ?? false) {
            $this->Ln(3);
            $this->outputText('help.labels.entity_actions');
            $this->outputActions($actions);
        }
    }

    private function outputFields(array $item, array $fields): void
    {
        $table = new PdfTableBuilder($this);
        $table->addColumn(PdfColumn::left($this->trans('help.fields.field'), 30, true))
            ->addColumn(PdfColumn::left($this->trans('help.fields.description'), 50))
            ->addColumn(PdfColumn::left($this->trans('help.fields.type'), 30, true))
            ->addColumn(PdfColumn::center($this->trans('help.fields.required'), 18, true))
            ->outputHeaders();
        foreach ($fields as $field) {
            $table->startRow()
                ->add($this->formatFieldName($item, $field))
                ->add($field['description'])
                ->add($this->formatFieldType($field))
                ->add($this->formatRequired($field['required']))
                ->endRow();
        }
    }

    private function outputImage(string $image): void
    {
        $file = $this->imagePath . $image;
        if (!FileUtils::exists($file)) {
            return;
        }

        [$width, $height] = (array) \getimagesize($file);
        $width = $this->pixels2UserUnit($width);
        $height = $this->pixels2UserUnit($height);
        $width = \min($width, $this->getPrintableWidth());
        $this->Image($file, null, null, $width);
    }

    private function outputLine(): void
    {
        PdfDrawColor::cellBorder()->apply($this);
        $this->horizontalLine();
        PdfStyle::getDefaultStyle()->apply($this);
    }

    private function outputMainMenus(array $menus, bool $newPage): bool
    {
        if (!empty($menus)) {
            if ($newPage) {
                $this->AddPage();
                $newPage = false;
            }

            $this->outputTitle('help.main_menu', 12);
            $this->outputLine();

            // root
            if ($rootMenu = $this->service->getMainMenu()) {
                //description
                if ($description = $rootMenu['description'] ?? false) {
                    $this->outputText($description, false);
                }

                // image
                if ($image = $rootMenu['image'] ?? false) {
                    $this->Ln(3);
                    $this->outputText('help.labels.screenshot');
                    $this->outputImage($image);
                }
            }

            // menus
            $this->Ln(3);
            $this->outputText('help.labels.edit_actions');
            $table = new PdfTableBuilder($this);
            $table->addColumn(PdfColumn::left($this->trans('help.fields.action'), 60, true))
                ->addColumn(PdfColumn::left($this->trans('help.fields.description'), 50))
                ->outputHeaders();
            $this->outputMenus($table, $menus);

            return true;
        }

        return false;
    }

    private function outputMenus(PdfTableBuilder $table, array $menus, int $indent = 0): void
    {
        $style = PdfStyle::getCellStyle()->setIndent($indent);
        foreach ($menus as $menu) {
            $table->startRow()
                ->add($this->splitTrans($menu['id']), 1, $style)
                ->add($menu['description'])
                ->endRow();

            if ($children = $menu['menus'] ?? false) {
                $this->outputMenus($table, $children, $indent + 4);
            }
        }
    }

    private function outputText(string $id, bool $translate = true): void
    {
        if ($translate) {
            $id = $this->trans($id);
        }
        $this->MultiCell(0, self::LINE_HEIGHT, $id);
    }

    private function outputTitle(string $id, float $size = 10): void
    {
        PdfStyle::getHeaderStyle()->setFontSize($size)->apply($this);
        $this->outputText($id);
        PdfStyle::getDefaultStyle()->apply($this);
    }

    private function splitTrans(string $id): string
    {
        $values = \explode('|', $id);
        if (2 === \count($values)) {
            return $this->trans($values[0], [], $values[1]);
        }

        return $this->trans($id);
    }
}
