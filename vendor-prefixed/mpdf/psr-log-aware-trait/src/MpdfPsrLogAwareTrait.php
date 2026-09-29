<?php

namespace MatrixAddons\DocumentEngine\Vendor\Mpdf\PsrLogAwareTrait;

use MatrixAddons\DocumentEngine\Vendor\Psr\Log\LoggerInterface;

trait MpdfPsrLogAwareTrait
{

	/**
	 * @var \MatrixAddons\DocumentEngine\Vendor\Psr\Log\LoggerInterface
	 */
	protected $logger;

	public function setLogger(LoggerInterface $logger)
	{
		$this->logger = $logger;
		if (property_exists($this, 'services') && is_array($this->services)) {
			foreach ($this->services as $name) {
				if ($this->$name && $this->$name instanceof \MatrixAddons\DocumentEngine\Vendor\Psr\Log\LoggerAwareInterface) {
					$this->$name->setLogger($logger);
				}
			}
		}
	}

}
