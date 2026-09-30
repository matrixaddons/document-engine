<?php

if (!function_exists('matrixaddons_documentengine_dd')) {
	function matrixaddons_documentengine_dd(...$args)
	{
		if (function_exists('dump')) {
			dump(...$args);
		} else {
			var_dump(...$args);
		}
		die;
	}
}
