<?php namespace Assets\Http;

use Closure;

use Symfony\Component\Process\Process;

use Assets\Asset;
use Assets\Exceptions\CompilationException;

class Controller extends \Illuminate\Routing\Controller {
	private $local;

	private $path;
	private $extension;

	public function __construct() {
		list($uri) = explode('?', array_get($_SERVER, 'REQUEST_URI', ''));

		$this->local = app()->environment('local');
		$this->path = base_path() . '/resources/' . trim($uri, '/');
		$this->extension = substr($this->path, strrpos($this->path, '.') + 1);

		if(!file_exists($this->path)) {
			app()->abort(404);
		}
	}

	public function font() {
		$contentType = null;

		switch(strtolower($this->extension)) {
			case 'svg':
				$contentType = 'image/svg+xml';
				break;
			case 'eot':
				$contentType = 'application/vnd.ms-fontobject';
				break;
			case 'woff':
				$contentType = 'application/x-font-woff';
				break;
			case 'otf':
				$contentType = 'font/opentype';
				break;
			case 'ttf':
			default:
				$contentType = 'application/x-font-ttf';
				break;
		}

		return $this->process($contentType, null);
	}

	public function img() {
		$contentType = null;

		switch(strtolower($this->extension)) {
			case 'svg':
				$contentType = 'image/svg+xml';
				break;
			case 'png':
				$contentType = 'image/png';
				break;
			case 'gif':
				$contentType = 'image/gif';
				break;
			case 'ico':
				$contentType = 'image/x-icon';
				break;
			case 'jpeg':
			case 'jpg':
			default:
				$contentType = 'image/jpeg';
				break;
		}

		return $this->process($contentType, null);
	}

	public function css() {
		return $this->process('text/css', null);
	}

	public function compile($type) {
		$asset = Asset::make($this->path);
		return $this->process($asset->getMime(), $asset, $asset->getLastModified());
	}

	/**
	 * Gets the path for the request assets and handles caching/etag responses
	 * Automatically sends a 404 and exits if path doesn't exist or fails a security check
	 *
	 * @param string $contentType
	 * @param Asset $asset Asset to compile.  If null, path contents will be ouputed
	 * @param int $lastModified If null, filemtime will be used, should return a unix timestamp
	 */
	private function process($contentType, Asset $asset = null, $lastModified = null) {
		if($lastModified === null) {
			$lastModified = filemtime($this->path);
		}

		$etag = '"' . sha1($this->path . $lastModified) . '"';

		if(isset($_SERVER['HTTP_IF_NONE_MATCH'])) {
			if($_SERVER['HTTP_IF_NONE_MATCH'] === $etag) {
				header('HTTP/1.1 304 Not Modified');
				exit;
			}
		}

		$dateFormat = 'D, d M Y H:i:s T';
		header('Cache-Control: public, max-age=31536000');
		header('Expires: ' . gmdate($dateFormat, $lastModified + 31536000));
		header('Last-Modified: '.gmdate($dateFormat, $lastModified));
		header('ETag: ' . $etag);
		header('Content-Type: ' . $contentType . '; charset=utf-8');

		$compile = function() use ($asset) {
			if($asset === null) {
				return file_get_contents($this->path);
			} else {
				header('X-Cached: false');

				try {
					return $asset->compile();
				} catch(CompilationException $e) {
					if(is_array($e->context)) {
						foreach($e->context as $key => $value) {
							printf('/* %s: %s */%s', strtoupper($key), $value, PHP_EOL);
						}
					} else if(is_string($e->context)) {
						printf('/* %s */%s', $e->context, PHP_EOL);
					}

					echo $e->log;
					exit(1);
				}
			}
		};

		if(isset($_GET['ignore-cache'])) {
			echo $compile();
		} else {
			echo app('cache')->remember(str_slug($this->path) . '-' . md5($lastModified), 1440, $compile);
		}

		exit;
	}
}
