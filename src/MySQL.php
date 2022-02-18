<?php
namespace CarloNicora\Minimalism\Services\MySQL;

use CarloNicora\Minimalism\Abstracts\AbstractService;
use CarloNicora\Minimalism\Enums\HttpCode;
use CarloNicora\Minimalism\Exceptions\MinimalismException;
use CarloNicora\Minimalism\Interfaces\Cache\Enums\CacheType;
use CarloNicora\Minimalism\Interfaces\Cache\Interfaces\CacheBuilderInterface;
use CarloNicora\Minimalism\Interfaces\Cache\Interfaces\CacheInterface;
use CarloNicora\Minimalism\Interfaces\Sql\Factories\SqlDataObjectFactory;
use CarloNicora\Minimalism\Interfaces\Sql\Interfaces\SqlDataObjectInterface;
use CarloNicora\Minimalism\Interfaces\Sql\Interfaces\SqlQueryFactoryInterface;
use CarloNicora\Minimalism\Interfaces\Sql\Interfaces\SqlInterface;
use CarloNicora\Minimalism\Services\MySQL\Commands\SqlCommand;
use CarloNicora\Minimalism\Services\MySQL\Enums\DatabaseOperationType;
use CarloNicora\Minimalism\Services\MySQL\Factories\SqlTableFactory;
use Exception;
use CarloNicora\Minimalism\Services\MySQL\Factories\ConnectionFactory;
use Throwable;

class MySQL extends AbstractService implements SqlInterface
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

        if (!$this->cache?->useCaching()){
            $this->cache = null;
        }
    }

    /**
     * @return void
     */
    public function initialise(
    ): void
    {
        SqlTableFactory::initialise($this->connectionFactory->getConfigurations());
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
     * @template InstanceOfType
     * @param SqlQueryFactoryInterface|SqlDataObjectInterface|SqlDataObjectInterface[] $queryFactory
     * @param CacheBuilderInterface|null $cacheBuilder
     * @param class-string<InstanceOfType>|null $responseType
     * @param bool $requireObjectsList
     * @return InstanceOfType|array
     * @throws MinimalismException|Exception|Throwable
     */
    public function create(
        SqlQueryFactoryInterface|SqlDataObjectInterface|array $queryFactory,
        ?CacheBuilderInterface $cacheBuilder=null,
        ?string $responseType=null,
        bool $requireObjectsList=false,
    ): SqlDataObjectInterface|array
    {
        $response = $this->execute(
            databaseOperationType: DatabaseOperationType::Create,
            queryFactory: $queryFactory,
            cacheBuilder: $cacheBuilder,
        );

        if ($responseType !== null){
            if ($requireObjectsList){
                $response = $this->returnObjectArray(
                    recordset: $response,
                    objectType: $responseType,
                );
            } else {
                $response = $this->returnSingleObject(
                    recordset: $response,
                    objectType: $responseType,
                );
            }
        }

        return $response;
    }

    /**
     * @template InstanceOfType
     * @param SqlQueryFactoryInterface $queryFactory
     * @param CacheBuilderInterface|null $cacheBuilder
     * @param class-string<InstanceOfType>|null $responseType
     * @param bool $requireObjectsList
     * @return InstanceOfType|array
     * @throws MinimalismException|Exception
     */
    public function read(
        SqlQueryFactoryInterface $queryFactory,
        ?CacheBuilderInterface $cacheBuilder=null,
        ?string $responseType=null,
        bool $requireObjectsList=false,
    ): SqlDataObjectInterface|array
    {
        $response = null;
        if ($this->cache !== null && $cacheBuilder !== null) {
            $response = $this->cache->readArray($cacheBuilder, CacheType::Data);
        }

        if ($response === null){
            $sqlCommand = new SqlCommand($this->connectionFactory, $queryFactory);
            try {
                $response = $sqlCommand->execute(databaseOperationType: DatabaseOperationType::Read, queryFactory: $queryFactory);
            } finally {
                $sqlCommand = null;
            }

            if ($this->cache !== null && $cacheBuilder !== null) {
                $this->cache->saveArray($cacheBuilder, $response, CacheType::Data);
            }
        } elseif ($response !== [] && !array_key_exists(0, $response)){
            $response = [$response];
        }

        if ($responseType !== null){
            if ($requireObjectsList){
                $response = $this->returnObjectArray(
                    recordset: $response,
                    objectType: $responseType,
                );
            } else {
                $response = $this->returnSingleObject(
                    recordset: $response,
                    objectType: $responseType,
                );
            }
        }

        return $response;
    }

    /**
     * @param SqlDataObjectInterface|SqlQueryFactoryInterface|SqlDataObjectInterface[] $queryFactory
     * @param CacheBuilderInterface|null $cacheBuilder
     * @return void
     * @throws MinimalismException|Throwable
     */
    public function update(
        SqlDataObjectInterface|SqlQueryFactoryInterface|array $queryFactory,
        ?CacheBuilderInterface $cacheBuilder=null,
    ): void
    {
        /** @noinspection UnusedFunctionResultInspection */
        $this->execute(
            databaseOperationType: DatabaseOperationType::Update,
            queryFactory: $queryFactory,
            cacheBuilder: $cacheBuilder,
        );
    }

    /**
     * @param SqlDataObjectInterface|SqlQueryFactoryInterface|array $queryFactory
     * @param CacheBuilderInterface|null $cacheBuilder
     * @return void
     * @throws MinimalismException|Throwable
     */
    public function delete(
        SqlQueryFactoryInterface|SqlDataObjectInterface|array $queryFactory,
        ?CacheBuilderInterface $cacheBuilder=null,
    ): void
    {
        /** @noinspection UnusedFunctionResultInspection */
        $this->execute(
            databaseOperationType: DatabaseOperationType::Delete,
            queryFactory: $queryFactory,
            cacheBuilder: $cacheBuilder,
        );
    }

    /**
     * @param DatabaseOperationType $databaseOperationType
     * @param SqlQueryFactoryInterface|SqlDataObjectInterface|SqlDataObjectInterface[] $queryFactory
     * @param CacheBuilderInterface|null $cacheBuilder
     * @return array|null
     * @throws MinimalismException|Exception
     * @throws Throwable
     */
    private function execute(
        DatabaseOperationType $databaseOperationType,
        SqlQueryFactoryInterface|SqlDataObjectInterface|array $queryFactory,
        ?CacheBuilderInterface $cacheBuilder,
    ): ?array
    {
        $response = null;
        $sqlCommand = null;

        try {
            if (is_array($queryFactory)) {
                $response = [];
                $isFirstDataObjectInterface=true;
                foreach ($queryFactory as $dataObjectInterface) {
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
                    $queryFactory,
                );

                $singleResponse = $sqlCommand->execute($databaseOperationType, $queryFactory);
                if ($singleResponse !== null){
                    $response[] = $singleResponse;
                }
            }

            $sqlCommand?->commit();
        } catch (Exception|Throwable $e) {
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
     * @template InstanceOfType
     * @param array $recordset
     * @param class-string<InstanceOfType> $objectType
     * @return InstanceOfType
     * @throws Exception
     */
    private function returnSingleObject(
        array $recordset,
        string $objectType,
    ): SqlDataObjectInterface
    {
        if ($recordset === [] || $recordset === [[]]){
            throw new MinimalismException(
                status: HttpCode::NotFound,
                message: 'Record Not found',
            );
        }

        if (array_is_list($recordset)){
            $response = SqlDataObjectFactory::createObject(
                objectFactory: $this->objectFactory,
                objectClass: $objectType,
                data: $recordset[0],
            );
        } else {
            $response = SqlDataObjectFactory::createObject(
                objectFactory: $this->objectFactory,
                objectClass: $objectType,
                data: $recordset,
            );
        }

        return $response;
    }

    /**
     * @template InstanceOfType
     * @param array $recordset
     * @param class-string<InstanceOfType> $objectType
     * @return InstanceOfType[]
     * @throws Exception
     */
    private function returnObjectArray(
        array $recordset,
        string $objectType,
    ): array
    {
        $response = [];

        if (array_is_list($recordset)) {
            foreach ($recordset ?? [] as $record) {
                $response[] = SqlDataObjectFactory::createObject(
                    objectFactory: $this->objectFactory,
                    objectClass: $objectType,
                    data: $record,
                );
            }
        } else {
            $response[] = SqlDataObjectFactory::createObject(
                objectFactory: $this->objectFactory,
                objectClass: $objectType,
                data: $recordset[0],
            );
        }

        return $response;
    }
}