<?php
namespace CarloNicora\Minimalism\Services\MySQL\Tests\Unit;

use CarloNicora\Minimalism\Core\Services\Interfaces\ServiceInterface;
use CarloNicora\Minimalism\Services\MySQL\Factories\ServiceFactory;
use CarloNicora\Minimalism\Services\MySQL\MySQL;
use CarloNicora\Minimalism\Services\MySQL\Tests\Unit\Abstracts\AbstractTestCase;

class MySQLTest extends AbstractTestCase
{
    /** @var MySQL|ServiceInterface|null  */
    private ?ServiceInterface $MySQL=null;

    public function setUp(): void
    {
        parent::setUp();

        if (false === getenv('MINIMALISM_SERVICE_MYSQL')) {
            putenv('MINIMALISM_SERVICE_MYSQL=mydb');
        }
        if (!isset($_ENV['MINIMALISM_SERVICE_MYSQL'])) {
            $_ENV['MINIMALISM_SERVICE_MYSQL'] = 'mydb';
        }

        if (false === getenv('mydb')) {
            putenv('mydb=host,username,password,dbName,port');
        }
        if (!isset($_ENV['mydb'])) {
            $_ENV['mydb'] = 'mydb=host,username,password,dbName,port';
        }

        $this->MySQL = $this->services->loadService(ServiceFactory::class);
    }

    /**
     *
     */
    public function testCreation(): void
    {
        $MySQL = $this->services->service(MySQL::class);
        $this->assertNotNull($MySQL);
    }
}