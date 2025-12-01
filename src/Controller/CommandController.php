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

use App\Attribute\ForSuperAdmin;
use App\Attribute\GetPostRoute;
use App\Attribute\GetRoute;
use App\Attribute\IndexRoute;
use App\Attribute\PdfRoute;
use App\Report\CommandsReport;
use App\Service\CommandDataService;
use App\Service\CommandFormService;
use App\Service\CommandService;
use App\Traits\CacheKeyTrait;
use App\Utils\StringUtils;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controller for the console commands.
 *
 * @phpstan-import-type CommandType from CommandService
 */
#[ForSuperAdmin]
#[Route(path: '/command', name: 'command_')]
class CommandController extends AbstractController
{
    use CacheKeyTrait;

    private const LAST_COMMAND = 'last_command';

    /**
     * Render a single command.
     *
     * MapQueryString
     */
    #[GetRoute(path: '/content', name: 'content')]
    public function command(
        #[MapQueryParameter]
        string $name,
        CommandService $service,
        SessionInterface $session,
    ): JsonResponse {
        if (!$service->hasCommand($name)) {
            return $this->jsonFalse(['message' => $this->trans('command.list.error', ['%name%' => $name])]);
        }

        $command = $service->getCommand($name);
        $session->set(self::LAST_COMMAND, $name);
        $view = $this->renderView('command/_command.htm.twig', ['command' => $command]);
        $lines = \array_map(\trim(...), \explode(StringUtils::NEW_LINE, \trim($view)));
        $view = \implode('', \array_filter($lines));

        return $this->jsonTrue(['content' => $view]);
    }

    /**
     * Show all commands.
     */
    #[GetRoute(path: IndexRoute::PATH, name: 'all')]
    public function commands(
        CommandService $service,
        SessionInterface $session,
        #[MapQueryParameter]
        ?string $name = null,
    ): Response {
        $count = $service->count();
        if (0 === $count) {
            return $this->redirectToHomePage('command.list.empty');
        }

        $name ??= (string) $session->get(self::LAST_COMMAND);
        if (StringUtils::isString($name) && $service->hasCommand($name)) {
            $command = $service->getCommand($name);
        } else {
            $command = $service->first();
        }
        $session->set(self::LAST_COMMAND, $name);
        $root = $this->trans('command.list.available');
        $commands = $service->getGroupedNames($root);
        $parameters = [
            'commands' => $commands,
            'command' => $command,
            'count' => $count,
        ];

        return $this->render('command/commands.html.twig', $parameters);
    }

    /**
     * Execute a command.
     */
    #[GetPostRoute(path: '/execute', name: 'execute')]
    public function execute(
        #[MapQueryParameter]
        string $name,
        Request $request,
        CommandService $service,
        CommandFormService $formService,
        CommandDataService $dataService,
    ): Response {
        if (!$service->hasCommand($name)) {
            throw $this->createTranslatedNotFoundException('command.list.error', ['%name%' => $name]);
        }

        /** @phpstan-var CommandType $command */
        $command = $service->getCommand($name);
        $session = $request->getSession();
        $key = $this->cleanKey('command.execute.' . $name);
        $data = $this->getCommandData($session, $dataService, $key, $command);

        // form
        $form = $formService->createForm($command, $data);
        if ($this->handleRequestForm($request, $form)) {
            try {
                /** @phpstan-var array<string, array|scalar|null> $data */
                $data = $form->getData();
                $session->set($key, $data);
                $parameters = $dataService->createParameters($command, $data);
                $result = $service->execute($name, $parameters);

                return $this->render('command/command_result.html.twig', [
                    'parameters' => $parameters,
                    'command' => $command,
                    'result' => $result,
                ]);
            } catch (\Exception $e) {
                return $this->renderFormException('command.result.error', $e);
            }
        }

        $view = $form->createView();
        $parameters = [
            'arguments' => [
                'texts' => $formService->filter($view, CommandFormService::ARGUMENT_TEXT),
                'checkboxes' => $formService->filter($view, CommandFormService::ARGUMENT_BOOL),
            ],
            'options' => [
                'texts' => $formService->filter($view, CommandFormService::OPTION_TEXT),
                'checkboxes' => $formService->filter($view, CommandFormService::OPTION_BOOL),
            ],
            'command' => $command,
            'form' => $view,
        ];

        return $this->render('command/command_query.html.twig', $parameters);
    }

    /**
     * Export commands to a PDF document.
     */
    #[PdfRoute]
    public function pdf(CommandService $service): Response
    {
        if (0 === $service->count()) {
            return $this->redirectToHomePage('command.list.empty');
        }

        $root = $this->trans('command.list.available');
        $groups = $service->getGroupedCommands($root);

        return $this->renderPdfDocument(new CommandsReport($this, $groups));
    }

    /**
     * @phpstan-param CommandType $command
     */
    private function getCommandData(
        SessionInterface $session,
        CommandDataService $dataService,
        string $key,
        array $command
    ): array {
        $data = $dataService->createData($command);
        /** @phpstan-var array<string, array|scalar|null> $existing */
        $existing = (array) $session->get($key, []);
        if ([] === $existing) {
            return $data;
        }

        return $dataService->validateData($command, \array_merge($data, $existing));
    }
}
