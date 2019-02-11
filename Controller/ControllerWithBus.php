<?php

/*
 * This file is part of the Apisearch Server
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * Feel free to edit as you please, and have fun.
 *
 * @author Marc Morera <yuhu@mmoreram.com>
 */

declare(strict_types=1);

namespace Apisearch\Server\Controller;

use Apisearch\Exception\TransportableException;
use League\Tactician\CommandBus;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ControllerWithBus.
 */
abstract class ControllerWithBus extends BaseController
{
    /**
     * @var CommandBus
     *
     * Message bus
     */
    protected $commandBus;

    /**
     * Controller constructor.
     *
     * @param CommandBus $commandBus
     */
    public function __construct(CommandBus $commandBus)
    {
        $this->commandBus = $commandBus;
    }

    /**
     * Get body element.
     *
     * @param Request                $request
     * @param string|null                 $field
     * @param TransportableException $exception
     * @param array                  $default
     *
     * @return array
     */
    protected function getRequestContentObject(
        Request $request,
        ?string $field,
        TransportableException $exception,
        array $default = null
    ): array {
        $requestContent = $request->getContent();
        $requestBody = json_decode($requestContent, true);

        if (
            (
                !empty($requestContent) &&
                is_null($requestBody)
            ) ||
            (
                is_null($field) &&
                !is_array($requestBody)
            )
        ) {
            throw $exception;
        }

        if (is_null($field)) {
            return $requestBody;
        }

        if (
            !is_array($requestBody) ||
            !isset($requestBody[$field]) ||
            !is_array($requestBody[$field])
        ) {
            if (is_null($default)) {
                throw $exception;
            }

            return $default;
        }

        return $requestBody[$field];
    }
}
