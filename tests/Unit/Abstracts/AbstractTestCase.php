<?php
namespace CarloNicora\Minimalism\Services\MySQL\Tests\Unit\Abstracts;

use CarloNicora\Minimalism\Core\Services\Factories\ServicesFactory;
use CarloNicora\Minimalism\Services\MySQL\Interfaces\TableInterface;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionException;

class AbstractTestCase extends TestCase
{
    /** @var ServicesFactory|null  */
    protected ?ServicesFactory $services=null;

    /** @var string  */
    protected string $tableName = 'tablename';

    /** @var array  */
    protected array $primaryKey = [
        'id' => TableInterface::INTEGER
            + TableInterface::PRIMARY_KEY
            + TableInterface::AUTO_INCREMENT,
    ];

    /** @var array  */
    protected array $fields = [
        'id' => TableInterface::INTEGER
            + TableInterface::PRIMARY_KEY
            + TableInterface::AUTO_INCREMENT,
        'name' => TableInterface::STRING,
        'double' => TableInterface::DOUBLE,
        'blob' => TableInterface::BLOB,
        'bool' => TableInterface::INTEGER
    ];

    /** @var array  */
    protected array $record = [
        'id' => 1,
        'name' => 'Carlo',
        'double' => 1.2,
        'blob' => 'phlow',
        'bool' => true
    ];

    /**
     *
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->services = new ServicesFactory();
    }

    /**
     * @param $object
     * @param $parameterName
     * @return mixed|null
     */
    protected function getProperty($object, $parameterName)
    {
        try {
            $reflection = new ReflectionClass(get_class($object));
            $property = $reflection->getProperty($parameterName);
            $property->setAccessible(true);
            return $property->getValue($object);
        } catch (ReflectionException $e) {
            return null;
        }
    }

    /**
     * @param $object
     * @param $parameterName
     * @param $parameterValue
     */
    protected function setProperty($object, $parameterName, $parameterValue): void
    {
        try {
            $reflection = new ReflectionClass(get_class($object));
            $property = $reflection->getProperty($parameterName);
            $property->setAccessible(true);
            $property->setValue($object, $parameterValue);
        } catch (ReflectionException $e) {
        }
    }
}