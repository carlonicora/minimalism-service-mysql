<?php
namespace CarloNicora\Minimalism\Services\MySQL;

use CarloNicora\Minimalism\Interfaces\CacheBuilderInterface;
use CarloNicora\Minimalism\Interfaces\CacheInterface;
use CarloNicora\Minimalism\Interfaces\DataInterface;
use CarloNicora\Minimalism\Interfaces\LoggerInterface;
use CarloNicora\Minimalism\Interfaces\ServiceInterface;
use CarloNicora\Minimalism\Services\MySQL\Factories\ConnectionFactory;
use CarloNicora\Minimalism\Services\MySQL\Interfaces\MySqlTableInterface;
use Exception;
use RuntimeException;

class MySQL implements ServiceInterface, DataInterface
{
    /** @var array */
    private array $tableManagers = [];

    /**
     * @var ConnectionFactory
     */
    private ConnectionFactory $connectionFactory;

    /**
     * MySQL constructor.
     * @param LoggerInterface $logger
     * @param CacheInterface|null $cache
     * @param string $MINIMALISM_SERVICE_MYSQL
     */
    public function __construct(
        private LoggerInterface $logger,
        private ?CacheInterface $cache,
        string $MINIMALISM_SERVICE_MYSQL
    )
    {
        $this->connectionFactory = new ConnectionFactory(
            $this->logger,
            $MINIMALISM_SERVICE_MYSQL
        );
    }


    /**
     * @param CacheInterface $cache
     */
    public function setCacheInterface(CacheInterface $cache): void
    {
        $this->cache = $cache;
    }

    /**
     * @param string $dbReader
     * @return MySqlTableInterface
     * @throws Exception
     */
    public function create(string $dbReader): MySqlTableInterface
    {
        if (array_key_exists($dbReader, $this->tableManagers)) {
            return $this->tableManagers[$dbReader];
        }

        if (!class_exists($dbReader)) {
            $this->logger->error(
                message: 'Database reader class missing: ' . $dbReader,
                domain: 'mysql'
            );
            throw new RuntimeException('Database reader class missing', 500);
        }

        /** @var MySqlTableInterface $response */
        $response = new $dbReader($this->logger, $this->connectionFactory);
        $response->initialiseAttributes();

        $databaseName = $response->getDbToUse();

        $connection = $this->connectionFactory->getDatabase($databaseName);

        $response->setConnection($connection);

        $this->tableManagers[$dbReader] = $response;

        return $response;
    }

    /**
     *
     */
    public function initialise(): void {}

    /**
     *
     */
    public function destroy(): void
    {
        $this->connectionFactory->resetDatabases();
        $this->tableManagers = [];
    }

    /**
     * @param array $parameters
     * @return array
     */
    private function flattenArray(array $parameters): array
    {
        $response = [];

        foreach ($parameters ?? [] as $parameter) {
            $response[] = $parameter;
        }

        return $response;
    }

    /**
     * @param string $tableInterfaceClassName
     * @param string $functionName
     * @param array $parameters
     * @param CacheBuilderInterface|null $cacheBuilder
     * @return array
     * @throws Exception
     * @noinspection PhpDocRedundantThrowsInspection
     */
    public function read(
        string $tableInterfaceClassName,
        string $functionName,
        array $parameters,
        ?CacheBuilderInterface $cacheBuilder = null
    ): array
    {
        $response = null;
        if ($this->cache !== null
            &&
            $cacheBuilder !== null
            &&
            $this->cache->useCaching()
        ) {
            $response = $this->cache->readArray($cacheBuilder, CacheBuilderInterface::DATA);
        }

        if ($response === null){
            $tableInterface = $this->create($tableInterfaceClassName);
            $parameters = $this->flattenArray($parameters);
            $response = $tableInterface->{$functionName}(...$parameters);

            if ($this->cache !== null && $cacheBuilder !== null && $this->cache->useCaching()) {
                $this->cache->saveArray($cacheBuilder, $response, CacheBuilderInterface::DATA);
            }
        }

        return $response;
    }

    /**
     * @param string $tableInterfaceClassName
     * @param string $functionName
     * @param array $parameters
     * @return int
     * @throws Exception
     */
    public function count(
        string $tableInterfaceClassName,
        string $functionName,
        array $parameters
    ): int
    {
        $tableInterface = $this->create($tableInterfaceClassName);
        return $tableInterface->{$functionName}($parameters);
    }

    /**
     * @param string $tableInterfaceClassName
     * @param array $records
     * @param CacheBuilderInterface|null $cacheBuilder
     * @param bool $avoidSingleInsert
     * @throws Exception
     */
    public function update(
        string $tableInterfaceClassName,
        array $records,
        ?CacheBuilderInterface $cacheBuilder = null,
        bool $avoidSingleInsert=false
    ): void
    {
        $tableInterface = $this->create($tableInterfaceClassName);
        $tableInterface->update(
            records: $records,
            avoidSingleInsert: $avoidSingleInsert,
        );

        if ($this->cache !== null && $cacheBuilder !== null && $this->cache->useCaching()) {
            $this->cache->invalidate($cacheBuilder);

            $this->cache->saveArray($cacheBuilder, $records, CacheBuilderInterface::DATA);
        }
    }

    /**
     * @param string $tableInterfaceClassName
     * @param array $records
     * @param CacheBuilderInterface|null $cacheBuilder
     * @throws Exception
     */
    public function delete(
        string $tableInterfaceClassName,
        array $records,
        ?CacheBuilderInterface $cacheBuilder = null
    ): void
    {
        $tableInterface = $this->create($tableInterfaceClassName);
        $tableInterface->update($records, true);

        if ($this->cache !== null && $cacheBuilder !== null && $this->cache->useCaching()) {
            $this->cache->invalidate($cacheBuilder);
        }
    }

    /**
     * @param string $tableInterfaceClassName
     * @param array $records
     * @param CacheBuilderInterface|null $cacheBuilder
     * @param bool $avoidSingleInsert
     * @return array
     * @throws Exception
     */
    public function insert(
        string $tableInterfaceClassName,
        array $records,
        ?CacheBuilderInterface $cacheBuilder = null,
        bool $avoidSingleInsert=false
    ): array
    {
        $tableInterface = $this->create($tableInterfaceClassName);
        $tableInterface->update(
            records: $records,
            avoidSingleInsert: $avoidSingleInsert
        );

        if ($this->cache !== null && $cacheBuilder !== null && $this->cache->useCaching()) {
            $this->cache->invalidate($cacheBuilder);

            $this->cache->saveArray($cacheBuilder, $records, CacheBuilderInterface::DATA);
        }

        return ($records);
    }

    /**
     * @param string $tableInterfaceClassName
     * @param string $functionName
     * @param array $parameters
     * @return array|null
     * @throws Exception
     * @noinspection PhpDocRedundantThrowsInspection
     */
    public function run(
        string $tableInterfaceClassName,
        string $functionName,
        array $parameters,
    ): ?array
    {
        $tableInterface = $this->create($tableInterfaceClassName);
        $parameters = $this->flattenArray($parameters);
        return $tableInterface->{$functionName}(...$parameters);
    }
}