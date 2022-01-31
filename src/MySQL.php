<?php
namespace CarloNicora\Minimalism\Services\MySQL;

use CarloNicora\Minimalism\Abstracts\AbstractService;
use CarloNicora\Minimalism\Exceptions\MinimalismException;
use CarloNicora\Minimalism\Interfaces\Cache\Enums\CacheType;
use CarloNicora\Minimalism\Interfaces\Cache\Interfaces\CacheBuilderInterface;
use CarloNicora\Minimalism\Interfaces\Cache\Interfaces\CacheInterface;
use CarloNicora\Minimalism\Interfaces\Data\Interfaces\DataObjectInterface;
use CarloNicora\Minimalism\Interfaces\Sql\Interfaces\SqlExecutionInterface;
use CarloNicora\Minimalism\Interfaces\Sql\Interfaces\SqlFactoryInterface;
use CarloNicora\Minimalism\Interfaces\Sql\Interfaces\SqlInterface;
use CarloNicora\Minimalism\Services\MySQL\Commands\SqlCommand;
use CarloNicora\Minimalism\Services\MySQL\Enums\DatabaseOperationType;
use CarloNicora\Minimalism\Services\MySQL\Factories\ExceptionFactory;
use Exception;
use CarloNicora\Minimalism\Services\MySQL\Factories\ConnectionFactory;

class MySQL extends AbstractService implements SqlExecutionInterface
{
    /** @var ConnectionFactory  */
    private ConnectionFactory $connectionFactory;

    /**
     * @param string $MINIMALISM_SERVICE_MYSQL
     * @param CacheInterface|null $cache
     */
    public function __construct(
        string $MINIMALISM_SERVICE_MYSQL,
        private ?CacheInterface $cache=null,
    )
    {
        $this->connectionFactory = new ConnectionFactory(
            databaseConfigurations: $MINIMALISM_SERVICE_MYSQL,
        );

        if (!$this->cache->useCaching()){
            $this->cache = null;
        }
    }

    /**
     * @return void
     */
    public function destroy(
    ): void
    {
        $this->connectionFactory->resetDatabases();
    }

    /**
     * @return string|null
     */
    public static function getBaseInterface(
    ): ?string
    {
        return SqlInterface::class;
    }

    /**
     * @param DatabaseOperationType $databaseOperationType
     * @param SqlFactoryInterface|DataObjectInterface|DataObjectInterface[] $factory
     * @param CacheBuilderInterface|null $cacheBuilder
     * @return array|null
     * @throws MinimalismException|Exception
     */
    private function execute(
        DatabaseOperationType                         $databaseOperationType,
        SqlFactoryInterface|DataObjectInterface|array $factory,
        ?CacheBuilderInterface                        $cacheBuilder,
    ): ?array
    {
        $response = null;
        $sqlCommand = null;

        try {
            if (is_array($factory)) {
                $response = [];
                $isFirstDataObjectInterface=true;
                foreach ($factory as $dataObjectInterface) {
                    if ($isFirstDataObjectInterface) {
                        $sqlCommand = new SqlCommand(
                            $this->connectionFactory,
                            $dataObjectInterface,
                        );
                    }
                    $isFirstDataObjectInterface=false;

                    $singleResponse = $sqlCommand->execute($databaseOperationType, $dataObjectInterface);
                    if ($singleResponse !== null){
                        $response[] = $singleResponse;
                    }
                }
            } else {
                $sqlCommand = new SqlCommand(
                    $this->connectionFactory,
                    $factory,
                );

                $singleResponse = $sqlCommand->execute($databaseOperationType, $factory);
                if ($singleResponse !== null){
                    $response[] = $singleResponse;
                }
            }

            $sqlCommand?->commit();
        } catch (Exception $e) {
            $sqlCommand?->rollback();
            throw $e;
        } finally {
            $sqlCommand = null;
        }

        if ($this->cache !== null && $cacheBuilder !== null) {
            $this->cache->invalidate($cacheBuilder);
        }

        return ($response);
    }

    /**
     * @param DataObjectInterface|DataObjectInterface[] $factory
     * @param CacheBuilderInterface|null $cacheBuilder
     * @return array
     * @throws MinimalismException
     */
    public function create(
        DataObjectInterface|array $factory,
        ?CacheBuilderInterface $cacheBuilder,
    ): array
    {
        return $this->execute(
            databaseOperationType: DatabaseOperationType::Create,
            factory: $factory,
            cacheBuilder: $cacheBuilder,
        );
    }

    /**
     * @param SqlFactoryInterface $factory
     * @param CacheBuilderInterface|null $cacheBuilder
     * @return array
     * @throws MinimalismException
     */
    public function read(
        SqlFactoryInterface $factory,
        ?CacheBuilderInterface $cacheBuilder,
    ): array
    {
        $response = null;
        if ($this->cache !== null && $cacheBuilder !== null) {
            $response = $this->cache->readArray($cacheBuilder, CacheType::Data);
        }

        if ($response === null){
            $sqlCommand = new SqlCommand($this->connectionFactory, $factory);
            try {
                $response = $sqlCommand->execute(databaseOperationType: DatabaseOperationType::Read, factory: $factory);
            } finally {
                $sqlCommand = null;
            }

            if ($this->cache !== null && $cacheBuilder !== null) {
                $this->cache->saveArray($cacheBuilder, $response, CacheType::Data);
            }
        } elseif ($response !== [] && !array_key_exists(0, $response)){
            $response = [$response];
        }

        return $response;
    }

    /**
     * @param DataObjectInterface|DataObjectInterface[] $factory
     * @param CacheBuilderInterface|null $cacheBuilder
     * @return void
     * @throws MinimalismException
     */
    public function update(
        DataObjectInterface|array $factory,
        ?CacheBuilderInterface $cacheBuilder,
    ): void
    {
        if (is_array($factory)) {
            foreach ($factory as $dataObjectInterface) {
                if ($dataObjectInterface->isNewObject()) {
                    throw ExceptionFactory::TryingToUpdateNewObject->create($factory->getTableInterfaceClass());
                }
            }
        }

        /** @noinspection UnusedFunctionResultInspection */
        $this->execute(
            databaseOperationType: DatabaseOperationType::Update,
            factory: $factory,
            cacheBuilder: $cacheBuilder,
        );
    }

    /**
     * @param SqlFactoryInterface|DataObjectInterface $factory
     * @param CacheBuilderInterface|null $cacheBuilder
     * @return void
     * @throws MinimalismException
     */
    public function delete(
        SqlFactoryInterface|DataObjectInterface $factory,
        ?CacheBuilderInterface $cacheBuilder,
    ): void
    {
        /** @noinspection UnusedFunctionResultInspection */
        $this->execute(
            databaseOperationType: DatabaseOperationType::Delete,
            factory: $factory,
            cacheBuilder: $cacheBuilder,
        );
    }
}