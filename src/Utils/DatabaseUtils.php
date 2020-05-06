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

namespace App\Utils;

use Doctrine\ORM\EntityManagerInterface;

/**
 * Service to get database informations.
 *
 * @author Laurent Muller
 */
class DatabaseUtils
{
    private EntityManagerInterface $manager;

    /**
     * Constructor.
     */
    public function __construct(EntityManagerInterface $manager)
    {
        $this->manager = $manager;
    }

    /**
     * Gets database variables.
     *
     * @return array an array with each row containing 2 columns ('Variable_name' and 'Value)
     */
    public function getConfiguration(): array
    {
        $result = [];

        try {
            $sql = 'SHOW VARIABLES';

            $connection = $this->getConnection();

            /** @var \Doctrine\DBAL\Driver\Statement $statement */
            $statement = $connection->prepare($sql);

            if ($statement->execute()) {
                $values = $statement->fetchAll();
                $statement->closeCursor();

                return \array_filter($values, function ($key) {
                    return 0 !== \strlen($key['Value']);
                });
            }
        } catch (\Exception $e) {
            // ignore
        }

        return $result;
    }

    /**
     * Gets the database configuration.
     *
     * @return array the database server informations
     */
    public function getDatabase(): array
    {
        $result = [];

        try {
            $params = $this->getConnection()->getParams();
            foreach (['dbname', 'host', 'port', 'driver'] as $key) {
                $result[$key] = $params[$key] ?? null;
            }

            return \array_filter($result, function ($value) {
                return Utils::isString($value);
            });
        } catch (\Exception $e) {
            // ignore
        }

        return $result;
    }

    /**
     * Gets database version.
     *
     * @return string the server version or "<code>Unknown</code>" if an error occurs
     */
    public function getVersion(): string
    {
        try {
            $sql = 'SHOW VARIABLES LIKE "version"';

            $connection = $this->getConnection();

            /** @var \Doctrine\DBAL\Driver\Statement $statement */
            $statement = $connection->prepare($sql);

            if ($statement->execute()) {
                $result = $statement->fetch();
                $statement->closeCursor();

                if (false !== $result) {
                    return $result['Value'];
                }
            }
        } catch (\Exception $e) {
            // ignore
        }

        return 'Unknown';
    }

    /**
     * Gets the connection.
     *
     * @return \Doctrine\DBAL\Connection
     */
    private function getConnection()
    {
        return $this->manager->getConnection();
    }
}
