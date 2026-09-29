<?php

namespace MatrixAddons\DocumentEngine\Vendor\Mpdf\PsrLogAwareTrait;

use MatrixAddons\DocumentEngine\Vendor\Psr\Log\LoggerInterface;

trait PsrLogAwareTrait 
{

	/**
	 * @var \MatrixAddons\DocumentEngine\Vendor\Psr\Log\LoggerInterface
	 */
	protected $logger;

	public function setLogger(LoggerInterface $logger)
	{
		$this->logger = $logger;
	}
	
}
