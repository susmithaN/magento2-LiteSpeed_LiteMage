<?php
/**
 * LiteMage
 *
 * NOTICE OF LICENSE
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see https://opensource.org/licenses/GPL-3.0 .
 *
 * @package   LiteSpeed_LiteMage
 * @copyright  Copyright (c) 2016 LiteSpeed Technologies, Inc. (https://www.litespeedtech.com)
 * @license     https://opensource.org/licenses/GPL-3.0
 */


namespace Litespeed\Litemage\Observer;

use Magento\Framework\Event\ObserverInterface;

class FlushCacheByCli implements ObserverInterface
{
	protected $config;
	protected $logger;
	protected $url;
	
	
    /**
     * @var \Magento\Framework\HTTP\ZendClientFactory
     */
    protected $httpClientFactory;

    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry;
	
    /**
     * @param \Litespeed\Litemage\Model\Config $config,
	 * @param \Magento\Framework\HTTP\ZendClientFactory $httpClientFactory,
	 * @param \Magento\Framework\Registry $coreRegistry,
	 * @param \Magento\Framework\Url $url,
     * @param \Psr\Log\LoggerInterface $logger,
	 * @throws \Magento\Framework\Exception\IntegrationException
     */
    public function __construct(\Litespeed\Litemage\Model\Config $config,
			\Magento\Framework\HTTP\ZendClientFactory $httpClientFactory,
			\Magento\Framework\Registry $coreRegistry,
			\Magento\Framework\Url $url,
			\Psr\Log\LoggerInterface $logger)
    {
		if (PHP_SAPI !== 'cli')	{
			throw new \Magento\Framework\Exception\IntegrationException('Should only invoke from command line');
		}
        $this->config = $config;
		$this->httpClientFactory = $httpClientFactory;
		$this->coreRegistry = $coreRegistry;
		$this->url = $url;
		$this->logger = $logger;
    }	
	
    /**
     * Flush All Litemage cache
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->config->cliModuleEnabled() 
				&& ($this->coreRegistry->registry('shellPurgeAll') === null)) {
			$this->coreRegistry->register('shellPurgeAll', 1);
			$this->_shellPurgeAll();
		}
    }
	
	protected function _shellPurgeAll()
	{
		$client = $this->httpClientFactory->create();
		
		$clientConfig = ['verifypeer' => 0,
			'timeout' => 180,
			'useragent' => 'litemage_walker'];
		
		$client->setConfig($clientConfig);
		$client->setMethod(\Zend_Http_Client::POST);
		$client->setParameterPost('all', 1);
		
		$server_ip = '127.0.0.1' ; //$helper->getConf($helper::CFG_WARMUP_SERVER_IP, $helper::CFG_WARMUP);
		$base = $this->url->getBaseUrl();
		if ($server_ip) {
			$pattern = "/:\/\/([^\/^:]+)(\/|:)?/";
			if (preg_match($pattern, $base, $m)) {
				$domain = $m[1];
				$pos = strpos($base, $domain);
				$base = substr($base, 0, $pos) . $server_ip . substr($base, $pos + strlen($domain));
				$client->setHeaders(['Host' => $domain]);
			}
		}
		
		$uri = $base . 'litemage/shell/purge';
		$client->setUri($uri);
        $client->setUrlEncodeBody(false);
		try {
			$response = $client->request();
			$this->logger->debug($uri . ' ' . $response->getBody());
		} catch (\Zend_Http_Client_Exception $e) {
			$this->logger->critical($uri . ' ' . $e->getMessage());
			return false;
		}

        return true;
		
	}
	
}
