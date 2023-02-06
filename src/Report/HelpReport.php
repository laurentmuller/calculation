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
use App\Pdf\Enums\PdfTextAlignment;
use App\Pdf\PdfBorder;
use App\Pdf\PdfColumn;
use App\Pdf\PdfDrawColor;
use App\Pdf\PdfStyle;
use App\Pdf\PdfTableBuilder;
use App\Service\HelpService;
use App\Util\FileUtils;

/**
 * Report for the help documentation.
 *
 * @psalm-import-type HelpFieldType from HelpService
 * @psalm-import-type HelpDialogType from HelpService
 * @psalm-import-type HelpEntityType from HelpService
 * @psalm-import-type HelpMainMenuType from HelpService
 * @psalm-import-type HelpMenuType from HelpService
 */
class HelpReport extends AbstractReport
{
    /**
     * The absolute path to the images.
     */
    private readonly string $imagePath;

    /**
     * Constructor.
     *
     * @param AbstractController $controller the parent controller
     * @param HelpService        $service    the help service
     *
     * @throws \Psr\Container\ContainerExceptionInterface
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function __construct(AbstractController $controller, private readonly HelpService $service)
    {
        parent::__construct($controller);
        $this->imagePath = $service->getImagePath();
        $this->setTitleTrans('help.title');
    }

    /**
     * {@inheritdoc}
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    public function render(): bool
    {
        $service = $this->service;
        $newPage = $this->outputMainMenus($service->getMainMenus());
        $newPage = $this->outputDialogs($service->getDialogs(), $newPage);

        return $this->outputEntities($service->getEntities(), $newPage);
    }

    /**
     * Convert the BR tags to the end of line symbol of this platform.
     */
    private function br2nl(string $str): string
    {
        return \preg_replace('#<br\s*/?>#i', \PHP_EOL, $str);
    }

    /**
     * @psalm-param HelpDialogType $dialog
     *
     * @psalm-return HelpEntityType|null
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    private function findEntity(array $dialog): ?array
    {
        $id = $dialog['entity'] ?? null;
        if (null !== $id) {
            /** @psalm-var HelpEntityType|null $entity */
            $entity = $this->service->findEntity($id);
            if (null !== $entity) {
                return $entity;
            }
        }

        return null;
    }

    /**
     * @psalm-param HelpEntityType|null $entity
     *
     * @psalm-return HelpFieldType[]|null
     */
    private function findFields(?array $entity): ?array
    {
        return null === $entity ? null : $entity['fields'] ?? null;
    }

    /**
     * @psalm-param HelpEntityType $item
     * @psalm-param HelpFieldType $field
     */
    private function formatFieldName(array $item, array $field): string
    {
        $id = $item['id'];
        $name = $field['name'];

        return $this->trans("$id.fields.$name");
    }

    /**
     * @psalm-param HelpFieldType $field
     */
    private function formatFieldType(array $field): string
    {
        $default = $field['type'] ?? 'text';
        $type = $this->trans("help.types.$default");
        if ($length = $field['length'] ?? null) {
            return "$type ($length)";
        }

        return $type;
    }

    private function formatRequired(bool $required): string
    {
        return $this->trans($required ? 'common.value_true' : 'common.value_false');
    }

    /**
     * @psalm-param array<array{id: string, description: string}> $actions
     */
    private function outputActions(array $actions, string $description): void
    {
        // check height
        $height = self::LINE_HEIGHT * (empty($description) ? 0 : $this->getLinesCount($description, 0))
            + 3 + self::LINE_HEIGHT;
        if (!$this->isPrintable($height)) {
            $this->AddPage();
        } else {
            $this->Ln(3);
        }
        $this->outputText($description);
        $table = new PdfTableBuilder($this);
        $table->addColumns(
            PdfColumn::left($this->trans('help.fields.action'), 70, true),
            PdfColumn::left($this->trans('help.fields.description'), 50)
        )->outputHeaders();

        foreach ($actions as $action) {
            $table->addRow(
                $this->trans($action['id']),
                $action['description']
            );
        }
    }

    /**
     * @psalm-param HelpEntityType $item
     * @psalm-param HelpFieldType[] $fields
     */
    private function outputColumns(array $item, array $fields): void
    {
        $table = new PdfTableBuilder($this);
        $table->addColumns(
            PdfColumn::left($this->trans('help.fields.column'), 30, true),
            PdfColumn::left($this->trans('help.fields.description'), 50)
        )->outputHeaders();

        foreach ($fields as $field) {
            $table->addRow(
                $this->formatFieldName($item, $field),
                $field['description']
            );
        }
    }

    /**
     * @psalm-param string[] $constraints
     */
    private function outputConstraints(array $constraints): void
    {
        $margin = $this->getLeftMargin();
        $this->SetLeftMargin($margin + 4);
        foreach ($constraints as $constraint) {
            $this->MultiCell(0, self::LINE_HEIGHT, \strip_tags("- $constraint"), PdfBorder::none(), PdfTextAlignment::LEFT);
        }
        $this->SetLeftMargin($margin);
    }

    /**
     * @psalm-param string[] $details
     */
    private function outputDetails(array $details): void
    {
        $text = \array_reduce($details, function (string $carry, string $str): string {
            $str = \strip_tags($this->br2nl($str));

            return empty($carry) ? $str : $carry . ' ' . $str;
        }, '');
        $this->MultiCell(0, self::LINE_HEIGHT, $text, PdfBorder::none(), PdfTextAlignment::LEFT);
    }

    /**
     * @psalm-param HelpDialogType $item
     *
     * @throws \Psr\Cache\InvalidArgumentException
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
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
        if (null !== $entity && null !== $fields) {
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
            $displayEntityActions = $item['displayEntityActions'] ?? false;
            if ($displayEntityActions) {
                /** @psalm-var array<array{id: string, description: string}>|null $actions */
                $actions = $entity['actions'] ?? null;
                if (null !== $actions) {
                    $this->outputActions($actions, 'help.labels.entity_actions');
                }
            }
        }

        // edit actions
        /** @psalm-var array<array{id: string, description: string}>|null $actions */
        $actions = $item['editActions'] ?? null;
        if (null !== $actions) {
            $this->outputActions($actions, 'help.labels.edit_actions');
        }

        // global actions
        /** @psalm-var array<array{id: string, description: string}>|null $actions */
        $actions = $item['globalActions'] ?? null;
        if (null !== $actions) {
            $this->outputActions($actions, 'help.labels.global_actions');
        }

        /** @psalm-var array{image: string|null, text:string|null, action: array|null}|null $forbidden */
        $forbidden = $item['forbidden'] ?? null;
        if (null !== $forbidden) {
            $this->Ln(3);
            $text = $forbidden['text'] ?? $this->trans('help.labels.forbidden_text');
            $this->outputText($text, false);
            $image = $forbidden['image'] ?? null;
            if (null !== $image) {
                $this->outputImage($image);
            }
            /** @psalm-var array{id: string, description: string}|null $action */
            $action = $forbidden['action'] ?? null;
            if (null !== $action) {
                $this->outputActions([$action], 'help.labels.edit_actions');
            }
        }
    }

    /**
     * @psalm-param HelpDialogType[]|null $dialogs
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    private function outputDialogs(?array $dialogs, bool $newPage): bool
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

    /**
     * @psalm-param HelpEntityType[]|null $entities
     */
    private function outputEntities(?array $entities, bool $newPage): bool
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

    /**
     * @psalm-param HelpEntityType $item
     */
    private function outputEntity(array $item): void
    {
        $this->outputTitle($item['id'] . '.name');

        if ($description = $item['description'] ?? false) {
            $this->MultiCell(0, self::LINE_HEIGHT, $description);
        }

        $fields = $this->findFields($item);
        if (null !== $fields) {
            $this->Ln(3);
            $this->outputText('help.labels.edit_fields');
            $this->outputFields($item, $fields);
        } else {
            $this->outputText('help.labels.entity_empty');
        }

        $constraints = $item['constraints'] ?? null;
        if (null !== $constraints) {
            $this->Ln(3);
            $this->outputText('help.labels.constraints');
            $this->outputConstraints($constraints);
        }

        /** @psalm-var array<array{id: string, description: string}>|null $actions */
        $actions = $item['actions'] ?? null;
        if (null !== $actions) {
            $this->outputActions($actions, 'help.labels.entity_actions');
        }
    }

    /**
     * @psalm-param HelpEntityType $item
     * @psalm-param HelpFieldType[] $fields
     */
    private function outputFields(array $item, array $fields): void
    {
        $table = new PdfTableBuilder($this);
        $table->addColumns(
            PdfColumn::left($this->trans('help.fields.field'), 30, true),
            PdfColumn::left($this->trans('help.fields.description'), 50),
            PdfColumn::left($this->trans('help.fields.type'), 30, true),
            PdfColumn::center($this->trans('help.fields.required'), 18, true)
        )->outputHeaders();

        foreach ($fields as $field) {
            $table->addRow(
                $this->formatFieldName($item, $field),
                $field['description'],
                $this->formatFieldType($field),
                $this->formatRequired($field['required'] ?? true)
            );
        }
    }

    private function outputImage(string $image): void
    {
        $file = $this->imagePath . $image;
        if (!FileUtils::exists($file)) {
            return;
        }

        /** @var float[] $size */
        $size = \getimagesize($file);
        $width = $this->pixels2UserUnit($size[0]);
        $width = \min($width, $this->getPrintableWidth());
        $this->Image(file: $file, w: $width);
    }

    private function outputLine(): void
    {
        PdfDrawColor::cellBorder()->apply($this);
        $this->horizontalLine();
        PdfStyle::getDefaultStyle()->apply($this);
    }

    /**
     * @psalm-param HelpMenuType[]|null $menus
     *
     * @throws \Psr\Cache\InvalidArgumentException
     */
    private function outputMainMenus(?array $menus): bool
    {
        if (empty($menus)) {
            return false;
        }

        $this->AddPage();
        $this->outputTitle('help.main_menu', 12);
        $this->outputLine();

        if (null !== $rootMenu = $this->service->getMainMenu()) {
            $description = $rootMenu['description'] ?? null;
            if (null !== $description) {
                $this->outputText($description, false);
            }

            $image = $rootMenu['image'] ?? null;
            if (null !== $image) {
                $this->Ln(3);
                $this->outputText('help.labels.screenshot');
                $this->outputImage($image);
            }
        }

        // menus
        $this->Ln(3);
        $this->outputText('help.labels.edit_actions');
        $table = new PdfTableBuilder($this);
        $table->addColumns(
            PdfColumn::left($this->trans('help.fields.action'), 60, true),
            PdfColumn::left($this->trans('help.fields.description'), 50)
        )->outputHeaders();
        $this->outputMenus($table, $menus);

        return true;
    }

    /**
     * @psalm-param array<array{id: string, description: string, menus: array|null}> $menus
     *
     * @psalm-suppress MixedArgumentTypeCoercion
     */
    private function outputMenus(PdfTableBuilder $table, array $menus, int $indent = 0): void
    {
        $style = PdfStyle::getCellStyle()->setIndent($indent);
        foreach ($menus as $menu) {
            $table->startRow()
                ->add(text: $this->splitTrans($menu['id']), style: $style)
                ->add($menu['description'])
                ->endRow();

            if (isset($menu['menus'])) {
                $this->outputMenus($table, $menu['menus'], $indent + 4);
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
