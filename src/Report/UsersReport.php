<?php
/*
 * This file is part of the Calculation package.
 *
 * Copyright (c) 2019 bibi.nu. All rights reserved.
 *
 * This computer code is protected by copyright law and international
 * treaties. Unauthorised reproduction or distribution of this code, or
 * any portion of it, may result in severe civil and criminal penalties,
 * and will be prosecuted to the maximum extent possible under the law.
 */

declare(strict_types=1);

namespace App\Report;

use App\Controller\BaseController;
use App\Entity\User;
use App\Pdf\PdfCell;
use App\Pdf\PdfColumn;
use App\Pdf\PdfImageCell;
use App\Pdf\PdfStyle;
use App\Pdf\PdfTableBuilder;
use App\Pdf\PdfTextColor;
use App\Utils\Utils;
use Symfony\Component\HttpKernel\KernelInterface;
use Vich\UploaderBundle\Mapping\PropertyMappingFactory;
use Vich\UploaderBundle\Storage\StorageInterface;

/**
 * Report for the list of users.
 *
 * @author Laurent Muller
 */
class UsersReport extends BaseReport
{
    /**
     * The default image path.
     *
     * @var string
     */
    private $defaultImagePath;
    /**
     * The mapping factory.
     *
     * @var PropertyMappingFactory
     */
    private $factory;

    /**
     * The configured file property name.
     *
     * @var string
     */
    private $fieldName;

    /**
     * The image storage.
     *
     * @var StorageInterface
     */
    private $storage;

    /**
     * The users to render.
     *
     * @var \App\Entity\User[]
     */
    private $users;

    /**
     * Constructor.
     *
     * @param BaseController         $controller the parent controller
     * @param PropertyMappingFactory $factory    the factory to get mapping informations
     * @param StorageInterface       $storage    the storage to get images path
     * @param KernelInterface        $kernel     the kernel to get the default image path
     */
    public function __construct(BaseController $controller, PropertyMappingFactory $factory, StorageInterface $storage, KernelInterface $kernel)
    {
        parent::__construct($controller);

        $this->SetTitleTrans('user.list.title');
        $this->defaultImagePath = $kernel->getProjectDir() . '/public/images/avatar.png';
        $this->factory = $factory;
        $this->storage = $storage;
    }

    /**
     * {@inheritdoc}
     */
    public function render(): bool
    {
        // users?
        $users = $this->users;
        $count = \count($users);
        if (0 === $count) {
            return false;
        }

        // sort
        Utils::sortField($users, 'username');

        // styles
        $disabledStyle = PdfStyle::getCellStyle()->setTextColor(PdfTextColor::red());
        $enabledStyle = PdfStyle::getCellStyle()->setTextColor(PdfTextColor::darkGreen());

        // new page
        $this->AddPage();

        // table
        $table = new PdfTableBuilder($this);
        $table->addColumn(PdfColumn::center($this->trans('user.fields.image'), 18, true))
            ->addColumn(PdfColumn::left($this->trans('user.fields.username'), 25))
            ->addColumn(PdfColumn::left($this->trans('user.fields.email'), 30))
            ->addColumn(PdfColumn::left($this->trans('user.fields.role'), 35, true))
            ->addColumn(PdfColumn::left($this->trans('user.fields.enabled'), 18, true))
            ->addColumn(PdfColumn::left($this->trans('user.fields.lastLogin'), 30, true))
            ->outputHeaders();

        // users
        foreach ($users as $user) {
            $enabled = $user->isEnabled();
            $style = $enabled ? $enabledStyle : $disabledStyle;
            $text = $this->booleanFilter($enabled, 'common.value_enabled', 'common.value_disabled', true);
            $role = Utils::translateRole($this->translator, $user->getRole());
            $cell = $this->getImageCell($user);

            $table->startRow()
                ->addCell($cell)
                ->add($user->getUsername())
                ->add($user->getEmail())
                ->add($role)
                ->add($text, 1, $style)
                ->add($this->localeDateTime($user->getLastLogin()))
                ->endRow();
        }

        // count
        return $this->resetStyle()->renderCount($count);
    }

    /**
     * Sets the users to render.
     *
     * @param \App\Entity\User[] $users
     */
    public function setUsers(array $users): self
    {
        $this->users = $users;

        return $this;
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
        list($width, $height) = $cell->getOriginalSize();
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
        // get user image path
        if ($fieldName = $this->getFieldName($user)) {
            $path = $this->storage->resolvePath($user, $fieldName);
            if ($path && \file_exists($path)) {
                return $path;
            }
        }

        // get default image path
        if (\file_exists($this->defaultImagePath)) {
            return $this->defaultImagePath;
        }

        return null;
    }
}
