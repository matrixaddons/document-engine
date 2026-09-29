<?php

namespace MatrixAddons\DocumentEngine\Vendor\Mpdf\Http;

use MatrixAddons\DocumentEngine\Vendor\Psr\Http\Message\RequestInterface;

interface ClientInterface
{

	public function sendRequest(RequestInterface $request);

}
