<?php
namespace CarloNicora\Minimalism\Services\MySQL\Factories;

use CarloNicora\Minimalism\core\Services\Exceptions\configurationException;
use CarloNicora\Minimalism\core\Services\Abstracts\AbstractServiceFactory;
use CarloNicora\Minimalism\core\Services\Exceptions\serviceNotFoundException;
use CarloNicora\Minimalism\Services\MySQL\Configurations\DDatabaseConfigurations;
use CarloNicora\Minimalism\Services\MySQL\MySQL;
use CarloNicora\Minimalism\core\Services\Factories\ServicesFactory;

class SServiceFactory  extends abstractServiceFactory {
    /**
     * serviceFactory constructor.
     * @param servicesFactory $services
     * @throws configurationException
     */
    public function __construct(servicesFactory $services) {
        $this->configData = new DDatabaseConfigurations();

        parent::__construct($services);
    }

    /**
     * @param servicesFactory $services
     * @return MySQL
     * @throws serviceNotFoundException
     */
    public function create(servicesFactory $services): MySQL {
        return new MySQL($this->configData, $services);
    }
}