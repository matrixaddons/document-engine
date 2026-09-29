<?php

namespace MatrixAddons\DocumentEngine\Vendor\Mpdf\Language;

interface ScriptToLanguageInterface
{

	public function getLanguageByScript($script);

	public function getLanguageDelimiters($language);

}
