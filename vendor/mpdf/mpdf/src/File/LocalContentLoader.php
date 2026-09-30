<?php

namespace MatrixAddons\DocumentEngine\Vendor\Mpdf\File;

class LocalContentLoader implements \MatrixAddons\DocumentEngine\Vendor\Mpdf\File\LocalContentLoaderInterface
{

	public function load($path)
	{
		return file_get_contents($path);
	}

}
