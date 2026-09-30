<?php

namespace MatrixAddons\DocumentEngine\Vendor\Mpdf\File;

interface LocalContentLoaderInterface
{

	/**
	 * @return string|null
	 */
	public function load($path);

}
