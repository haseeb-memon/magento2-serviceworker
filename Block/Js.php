<?php

namespace Challenge\ServiceWorker\Block;

class Js extends \Magento\Framework\View\Element\Template
{
    const VERSION_PREFIX = "v1";

    /** @var \Challenge\ServiceWorker\Helper\Config $config */
    protected $config;

    /** @var  \Magento\Framework\Json\Helper\Data $jsonHelper */
    protected $jsonHelper;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Challenge\ServiceWorker\Helper\Config $config,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        array $data
    ) {
        $this->config = $config;

        $this->jsonHelper = $jsonHelper;

        $data["cache_lifetime"] = 60 * 60 * 24 * 365;

        parent::__construct($context, $data);
    }

    /**
     * Get the provided data encoded as a JSON object.
     *
     * @param mixed $data
     *
     * @return string
     */
    public function jsonEncode($data)
    {
        return $this->jsonHelper->jsonEncode($data);
    }

    /**
     * Get the service worker version string.
     *
     * @return string
     */
    public function getVersion()
    {
        return implode("-", [
            static::VERSION_PREFIX,
            time()
        ]);
    }

    /**
     * Get the offline notification page URL.
     *
     * @return string
     */
    public function getOfflinePageUrl()
    {
        return $this->config->getOfflinePageUrl();
    }

    /**
     * Get the list of URLs for external scripts to import into the service worker.
     *
     * @return string[]
     */
    public function getExternalScriptUrls()
    {
        $scripts = [
            $this->getViewFileUrl("Challenge_ServiceWorker::js/lib/workbox-sw.prod.v1.0.1.js"),
        ];

        if ($this->isGaOfflineEnabled()) {
            $scripts[] = $this->getViewFileUrl("Challenge_ServiceWorker::js/lib/workbox-google-analytics.prod.v1.0.0.js");
        }

        return array_filter($scripts);
    }

    /**
     * Get the path prefix for backend requests.
     *
     * @return string
     */
    public function getBackendPathPrefix()
    {
        return $this->config->getBackendPathPrefix();
    }

    /**
     * Get the configured paths with custom caching strategies.
     *
     * @return \array[]
     */
    public function getCustomStrategies()
    {
        return $this->config->getCustomStrategies();
    }

    /**
     * Check if Offline Google Analytics features are enabled.
     *
     * @return bool
     */
    public function isGaOfflineEnabled()
    {
        return $this->config->isGaOfflineEnabled();
    }
}
