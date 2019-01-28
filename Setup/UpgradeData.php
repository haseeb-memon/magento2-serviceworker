<?php

namespace Challenge\ServiceWorker\Setup;

use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Magento\Framework\Setup\UpgradeDataInterface;
use Challenge\ServiceWorker\Model\Config\Source\CachingStrategy;

class UpgradeData implements UpgradeDataInterface
{
    /** @var \Magento\Framework\App\Config\ScopeConfigInterface $configReader */
    protected $configReader;

    /** @var \Magento\Framework\App\Config\Storage\WriterInterface $configWriter */
    protected $configWriter;

    /** @var \Magento\Framework\Serialize\Serializer\Json $serializer */
    protected $serializer;

    public function __construct(
        \Magento\Framework\App\Config\ValueInterface $configReader,
        \Magento\Framework\App\Config\Storage\WriterInterface $configWriter,
        \Magento\Framework\Serialize\Serializer\Json $serializer
    ) {
        $this->configReader = $configReader;
        $this->configWriter = $configWriter;
        $this->serializer   = $serializer;
    }

    /**
     * Upgrades data for a module
     *
     * @param ModuleDataSetupInterface $setup
     * @param ModuleContextInterface   $context
     *
     * @return void
     */
    public function upgrade(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if ($version = $context->getVersion()) {

            /**
             * Migrate the url_blacklist configuration to custom_strategies
             */
            if (version_compare($version, "2.0.0", "<")) {
                /** @var \Magento\Config\Model\ResourceModel\Config\Data\Collection $collection */
                $collection = $this->configReader->getCollection()
                    ->addFieldToFilter("path", "web/serviceworker/url_blacklist");

                $values_migrated = false;

                foreach ($collection as $config) {
                    /** @var \Magento\Framework\App\Config\Value $config */
                    $value = array_filter(array_map(
                        "trim",
                        explode("\n", $config->getValue())
                    ));

                    array_walk($value, function (&$item) {
                        $item = [
                            "path"     => $item,
                            "strategy" => CachingStrategy::NETWORK_ONLY,
                        ];
                    });

                    $this->configWriter->save(
                        "web/serviceworker/custom_strategies",
                        $this->serializer->serialize($value),
                        $config->getScope(),
                        $config->getScopeId()
                    );

                    $this->configWriter->delete(
                        "web/serviceworker/url_blacklist",
                        $config->getScope(),
                        $config->getScopeId()
                    );

                    $values_migrated = true;
                }

                // Insert default values if there are no values to migrate
                if (!$values_migrated) {
                    $strategies = [
                        ["path" => "checkout/", "strategy" => CachingStrategy::NETWORK_ONLY],
                        ["path" => "customer/account/create*", "strategy" => CachingStrategy::NETWORK_ONLY],
                        ["path" => "checkout/account/login*", "strategy" => CachingStrategy::NETWORK_ONLY],
                    ];

                    $this->configWriter->save(
                        "web/serviceworker/custom_strategies",
                        $this->serializer->serialize($strategies)
                    );
                }
            }

        }

        $setup->endSetup();
    }
}
