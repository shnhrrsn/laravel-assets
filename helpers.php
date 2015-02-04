<?php

if (!function_exists('asset_path')) {
	/**
	 * Get a path to your asset
	 *
	 * @param  mixed $path Asset path relative to resources/assets
	 * @return string
	 */
	function asset_path($path) {
		return \Assets\Asset::publishedPath($path);
	}
}
