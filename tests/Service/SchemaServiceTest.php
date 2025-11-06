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

namespace App\Tests\Service;

use App\Entity\User;
use App\Entity\UserProperty;
use App\Service\SchemaService;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ConnectionException;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Schema\AbstractSchemaManager;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\ForeignKeyConstraint;
use Doctrine\DBAL\Schema\Index;
use Doctrine\DBAL\Schema\MySQLSchemaManager;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\BooleanType;
use Doctrine\DBAL\Types\FloatType;
use Doctrine\DBAL\Types\IntegerType;
use Doctrine\DBAL\Types\StringType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\AssociationMapping;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Mapping\ClassMetadataFactory;
use Doctrine\ORM\Mapping\ManyToOneAssociationMapping;
use Doctrine\ORM\Mapping\OneToManyAssociationMapping;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

final class SchemaServiceTest extends TestCase
{
    private const TABLE_PROPERTY = 'property';
    private const TABLE_USER = 'user';

    public function testCountAllWithoutDatabase(): void
    {
        $connection = $this->createConnection(platform: new MySQLPlatform());
        $connection->method('getDatabase')
            ->willReturn(null);
        $manager = $this->createEntityManager($connection);
        $service = new SchemaService($manager, new ArrayAdapter());
        $actual = $service->getTables();
        self::assertCount(0, $actual);
    }

    public function testCountAllWithRows(): void
    {
        $row = [
            'name' => 'group',
            'records' => 1,
            'size' => 1,
        ];
        $table = $this->createUserTable();
        $schemaManager = $this->createSchemaManager($table);
        $result = $this->createMock(Result::class);
        $result->method('fetchAllAssociative')
            ->willReturn([$row]);
        $connection = $this->createConnection($schemaManager, new MySQLPlatform());
        $connection->method('getDatabase')
            ->willReturn('fake');
        $connection->method('executeQuery')
            ->willReturn($result);
        $manager = $this->createEntityManager($connection);
        $service = new SchemaService($manager, new ArrayAdapter());
        $tables = $service->getTables();
        self::assertCount(1, $tables);
    }

    public function testExecuteMySQLPlatformThrowException(): void
    {
        $table = $this->createUserTable();
        $schemaManager = $this->createSchemaManager($table);
        $connection = $this->createConnection($schemaManager, new MySQLPlatform());
        $connection->method('getDatabase')
            ->willReturn('fake');
        $connection->method('executeQuery')
            ->willThrowException(new ConnectionException());
        $manager = $this->createEntityManager($connection);
        $service = new SchemaService($manager, new ArrayAdapter());
        $tables = $service->getTables();
        self::assertCount(1, $tables);
    }

    public function testExecuteSQLitePlatformThrowException(): void
    {
        $table = $this->createUserTable();
        $schemaManager = $this->createSchemaManager($table);
        $connection = $this->createConnection($schemaManager, new SQLitePlatform());
        $connection->method('getDatabase')
            ->willReturn('fake');
        $connection->method('executeQuery')
            ->willThrowException(new ConnectionException());
        $manager = $this->createEntityManager($connection);
        $service = new SchemaService($manager, new ArrayAdapter());
        $tables = $service->getTables();
        self::assertCount(1, $tables);
    }

    public function testGetDatabasePlatformThrowException(): void
    {
        $connection = $this->createConnection();
        $connection->method('getDatabasePlatform')
            ->willThrowException(new ConnectionException());
        $manager = $this->createEntityManager($connection);
        $service = new SchemaService($manager, new ArrayAdapter());
        $tables = $service->getTables();
        self::assertCount(0, $tables);
    }

    public function testGetTables(): void
    {
        $service = $this->createService();
        $actual = $service->getTables();
        self::assertCount(2, $actual);
    }

    public function testTableExists(): void
    {
        $service = $this->createService();
        self::assertTrue($service->tableExists(self::TABLE_USER));
        self::assertTrue($service->tableExists(self::TABLE_PROPERTY));
        self::assertFalse($service->tableExists('fake'));
    }

    public function testWithoutAssociation(): void
    {
        $table = $this->createUserTable();
        $schemaManager = $this->createSchemaManager($table);
        $connection = $this->createConnection($schemaManager);
        $data = $this->createMetadata(User::class, self::TABLE_USER, null);
        $metadataFactory = $this->createMetaDatFactory($data);
        $manager = $this->createEntityManager($connection);
        $manager->method('getMetadataFactory')
            ->willReturn($metadataFactory);
        $service = new SchemaService($manager, new ArrayAdapter());
        $tables = $service->getTables();
        self::assertCount(1, $tables);
    }

    /**
     * @template T of AbstractPlatform
     *
     * @phpstan-param AbstractSchemaManager<T>|null $schemaManager
     */
    private function createConnection(
        ?AbstractSchemaManager $schemaManager = null,
        ?AbstractPlatform $platform = null
    ): MockObject&Connection {
        $connection = $this->createMock(Connection::class);
        if ($schemaManager instanceof AbstractSchemaManager) {
            $connection->method('createSchemaManager')
                ->willReturn($schemaManager);
        }
        if ($platform instanceof AbstractPlatform) {
            $connection->method('getDatabasePlatform')
                ->willReturn($platform);
        }

        return $connection;
    }

    private function createEntityManager(Connection $connection): MockObject&EntityManagerInterface
    {
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager->method('getConnection')
            ->willReturn($connection);

        return $manager;
    }

    /**
     * @template T of object
     *
     * @phpstan-param class-string<T> $class
     *
     * @phpstan-return ClassMetadata<T>
     */
    private function createMetadata(
        string $class,
        string $name,
        OneToManyAssociationMapping|ManyToOneAssociationMapping|null $association
    ): ClassMetadata {
        $classMetadata = new ClassMetadata($class);
        $classMetadata->table['name'] = $name;
        if ($association instanceof AssociationMapping) {
            $classMetadata->associationMappings = [$association->targetEntity => $association];
        }

        return $classMetadata;
    }

    /**
     * @return ClassMetadata<object>[]
     */
    private function createMetaDatas(): array
    {
        $userClass = User::class;
        $propertyClass = UserProperty::class;

        return [
            $this->createMetadata(
                $userClass,
                self::TABLE_USER,
                new OneToManyAssociationMapping('id', $userClass, $propertyClass)
            ),
            $this->createMetadata(
                $propertyClass,
                self::TABLE_PROPERTY,
                new ManyToOneAssociationMapping('user_id', $userClass, $propertyClass)
            ),
        ];
    }

    /**
     * @phpstan-param ClassMetadata<object>[]|ClassMetadata<object> $metaDatas
     */
    private function createMetaDatFactory(array|ClassMetadata $metaDatas): MockObject&ClassMetadataFactory
    {
        $metaDatas = \is_array($metaDatas) ? $metaDatas : [$metaDatas];
        $metadataFactory = $this->createMock(ClassMetadataFactory::class);
        $metadataFactory->method('getAllMetadata')
            ->willReturn($metaDatas);

        return $metadataFactory;
    }

    private function createPropertyTable(): Table
    {
        $columns = [
            new Column('id', new IntegerType()),
            new Column('name', new StringType()),
            new Column('user_id', new IntegerType()),
        ];
        $indexes = [
            new Index('PRIMARY', ['id'], true, true),
        ];
        $foreignKeyConstraint = new ForeignKeyConstraint(
            ['user_id'],
            self::TABLE_USER,
            ['id']
        );

        return new Table(
            name: self::TABLE_PROPERTY,
            columns: $columns,
            indexes: $indexes,
            fkConstraints: [$foreignKeyConstraint]
        );
    }

    /**
     * @phpstan-param Table[]|Table $tables
     */
    private function createSchemaManager(array|Table $tables): MockObject&MySQLSchemaManager
    {
        $tables = \is_array($tables) ? $tables : [$tables];
        $schemaManager = $this->createMock(MySQLSchemaManager::class);
        $schemaManager->method('listTables')
            ->willReturn($tables);

        return $schemaManager;
    }

    private function createService(): SchemaService
    {
        $tables = $this->createTables();
        $metaDatas = $this->createMetaDatas();
        $schemaManager = $this->createSchemaManager($tables);
        $connection = $this->createConnection($schemaManager);
        $metadataFactory = $this->createMetaDatFactory($metaDatas);
        $manager = $this->createEntityManager($connection);
        $manager->method('getMetadataFactory')
            ->willReturn($metadataFactory);

        return new SchemaService($manager, new ArrayAdapter());
    }

    /**
     * @return Table[]
     */
    private function createTables(): array
    {
        return [
            $this->createUserTable(),
            $this->createPropertyTable(),
        ];
    }

    private function createUserTable(): Table
    {
        $columns = [
            new Column('id', new IntegerType()),
            new Column('name', new StringType(), ['default' => 'fake']),
            new Column('amount', new FloatType(), ['default' => '0']),
            new Column('active', new BooleanType(), ['default' => 'false']),
        ];
        $indexes = [
            new Index('PRIMARY', ['id'], true, true),
            new Index('name', ['name'], false, false),
            new Index('amount', ['id'], false, false),
        ];

        return new Table(
            name: self::TABLE_USER,
            columns: $columns,
            indexes: $indexes,
        );
    }
}
