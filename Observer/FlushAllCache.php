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

class FlushAllCache implements ObserverInterface
{
	protected $config;
	
    /** @var \Magento\Framework\Event\ManagerInterface */
    protected $eventManager;
	

    /**
     * @param \Litespeed\Litemage\Model\Config $config,
	 * @param \Magento\Framework\Event\ManagerInterface $eventManager,
     */
    public function __construct(\Litespeed\Litemage\Model\Config $config,
			\Magento\Framework\Event\ManagerInterface $eventManager)
    {
        $this->config = $config;
		$this->eventManager = $eventManager;
    }

    /**
     * Flush All Litemage cache
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
		if (PHP_SAPI == 'cli') {
			// from command line
			if ($this->config->cliModuleEnabled())
				$this->eventManager->dispatch('litemage_cli_purge_all');
		}
		else if ($this->config->moduleEnabled()) {
			$this->eventManager->dispatch('litemage_purge_all');
        }
    }
	

}
