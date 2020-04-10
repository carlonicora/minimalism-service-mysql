<?php
namespace carlonicora\minimalism\services\MySQL\factories;

use carlonicora\minimalism\core\services\exceptions\configurationException;
use carlonicora\minimalism\core\services\abstracts\abstractServiceFactory;
use carlonicora\minimalism\services\MySQL\configurations\databaseConfigurations;
use carlonicora\minimalism\services\MySQL\MySQL;
use carlonicora\minimalism\core\services\factories\servicesFactory;

class serviceFactory  extends abstractServiceFactory {
    /**
     * serviceFactory constructor.
     * @param servicesFactory $services
     * @throws configurationException
     */
    public function __construct(servicesFactory $services) {
        $this->configData = new databaseConfigurations();

        parent::__construct($services);
    }

    /**
     * @param servicesFactory $services
     * @return MySQL
     */
    public function create(servicesFactory $services): MySQL {
        return new MySQL($this->configData, $services);
    }
}