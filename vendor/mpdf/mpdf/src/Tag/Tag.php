<?php

namespace MatrixAddons\DocumentEngine\Vendor\Mpdf\Tag;

use MatrixAddons\DocumentEngine\Vendor\Mpdf\Strict;

use MatrixAddons\DocumentEngine\Vendor\Mpdf\Cache;
use MatrixAddons\DocumentEngine\Vendor\Mpdf\Color\ColorConverter;
use MatrixAddons\DocumentEngine\Vendor\Mpdf\CssManager;
use MatrixAddons\DocumentEngine\Vendor\Mpdf\Form;
use MatrixAddons\DocumentEngine\Vendor\Mpdf\Image\ImageProcessor;
use MatrixAddons\DocumentEngine\Vendor\Mpdf\Language\LanguageToFontInterface;
use MatrixAddons\DocumentEngine\Vendor\Mpdf\Mpdf;
use MatrixAddons\DocumentEngine\Vendor\Mpdf\Otl;
use MatrixAddons\DocumentEngine\Vendor\Mpdf\SizeConverter;
use MatrixAddons\DocumentEngine\Vendor\Mpdf\TableOfContents;

abstract class Tag
{

	use Strict;

	/**
	 * @var \MatrixAddons\DocumentEngine\Vendor\Mpdf\Mpdf
	 */
	protected $mpdf;

	/**
	 * @var \MatrixAddons\DocumentEngine\Vendor\Mpdf\Cache
	 */
	protected $cache;

	/**
	 * @var \MatrixAddons\DocumentEngine\Vendor\Mpdf\CssManager
	 */
	protected $cssManager;

	/**
	 * @var \MatrixAddons\DocumentEngine\Vendor\Mpdf\Form
	 */
	protected $form;

	/**
	 * @var \MatrixAddons\DocumentEngine\Vendor\Mpdf\Otl
	 */
	protected $otl;

	/**
	 * @var \MatrixAddons\DocumentEngine\Vendor\Mpdf\TableOfContents
	 */
	protected $tableOfContents;

	/**
	 * @var \MatrixAddons\DocumentEngine\Vendor\Mpdf\SizeConverter
	 */
	protected $sizeConverter;

	/**
	 * @var \MatrixAddons\DocumentEngine\Vendor\Mpdf\Color\ColorConverter
	 */
	protected $colorConverter;

	/**
	 * @var \MatrixAddons\DocumentEngine\Vendor\Mpdf\Image\ImageProcessor
	 */
	protected $imageProcessor;

	/**
	 * @var \MatrixAddons\DocumentEngine\Vendor\Mpdf\Language\LanguageToFontInterface
	 */
	protected $languageToFont;

	const ALIGN = [
		'left' => 'L',
		'center' => 'C',
		'right' => 'R',
		'top' => 'T',
		'text-top' => 'TT',
		'middle' => 'M',
		'baseline' => 'BS',
		'bottom' => 'B',
		'text-bottom' => 'TB',
		'justify' => 'J'
	];

	public function __construct(
		Mpdf $mpdf,
		Cache $cache,
		CssManager $cssManager,
		Form $form,
		Otl $otl,
		TableOfContents $tableOfContents,
		SizeConverter $sizeConverter,
		ColorConverter $colorConverter,
		ImageProcessor $imageProcessor,
		LanguageToFontInterface $languageToFont
	) {

		$this->mpdf = $mpdf;
		$this->cache = $cache;
		$this->cssManager = $cssManager;
		$this->form = $form;
		$this->otl = $otl;
		$this->tableOfContents = $tableOfContents;
		$this->sizeConverter = $sizeConverter;
		$this->colorConverter = $colorConverter;
		$this->imageProcessor = $imageProcessor;
		$this->languageToFont = $languageToFont;
	}

	public function getTagName()
	{
		$tag = get_class($this);
		return strtoupper(str_replace('MatrixAddons\DocumentEngine\Vendor\Mpdf\Tag\\', '', $tag));
	}

	protected function getAlign($property)
	{
		$property = strtolower($property);
		return array_key_exists($property, self::ALIGN) ? self::ALIGN[$property] : '';
	}

	abstract public function open($attr, &$ahtml, &$ihtml);

	abstract public function close(&$ahtml, &$ihtml);

}
