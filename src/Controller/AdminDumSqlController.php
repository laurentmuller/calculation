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

namespace App\Controller;

use App\Attribute\ForAdmin;
use App\Attribute\ForSuperAdmin;
use App\Attribute\GetRoute;
use App\Enums\FlashType;
use App\Model\TranslatableFlashMessage;
use App\Service\CommandService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controller to dump SQL changes.
 */
#[ForAdmin]
#[Route(path: '/admin', name: 'admin_')]
class AdminDumSqlController extends AbstractController
{
    /**
     * @throws \Exception
     */
    #[ForSuperAdmin]
    #[GetRoute(path: '/dump-sql', name: 'dump_sql')]
    public function dumpSql(CommandService $service): Response
    {
        $result = $service->execute('doctrine:schema:update', ['--dump-sql' => true]);
        if (!$result->isSuccess()) {
            return $this->redirectToHomePage(
                message: TranslatableFlashMessage::instance(
                    message: 'admin.dump_sql.error',
                    type: FlashType::WARNING
                )
            );
        }

        if (\str_contains($result->content, '[OK]')) {
            return $this->redirectToHomePage(
                message: TranslatableFlashMessage::instance(
                    message: 'admin.dump_sql.no_change',
                    type: FlashType::INFO
                )
            );
        }

        return $this->render('admin/dump_sql.html.twig', [
            'count' => \substr_count($result->content, ';'),
            'content' => $result->content,
        ]);
    }
}
