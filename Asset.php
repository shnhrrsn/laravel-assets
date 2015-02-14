<?php namespace Assets;

use InvalidArgumentException;
use Symfony\Component\Process\Process;

class Asset {
	public static $autoMinifyDefault = false;

	protected static $compilers = [
		'scss' => 'ScssCompiler',	
		'less' => 'LessCompiler',
		'coffee' => 'CoffeeCompiler',
		'js' => 'JavascriptCompiler'
	];

	protected $autoMinify;
	protected $path;
	protected $compiler;

	public static function make($path, $autoMinify = null) {
		return new static($path, $autoMinify);
	}

	public function __construct($path, $autoMinify = null) {
		$this->path = $path;
		$this->autoMinify = $autoMinify === null ? static::$autoMinifyDefault : $autoMinify;
	}

	/**
	 * @return Symfony\Component\Process\Process
	 */
	public function getCompileProcess($context = null) {
		return $this->getCompiler()->getCompileProcess($this->path, $context);
	}

	public function getLastModified($newest = 0) {
		return $this->getCompiler()->getLastModified($this->path, $newest);
	}

	public function getMime() {
		return $this->getCompiler()->getMime();
	}

	public function getType() {
		return $this->getCompiler()->getType();
	}

	public function getExtension() {
		return $this->getCompiler()->getExtension();
	}

	public function getCompiler() {
		if($this->compiler === null) {
			$this->compiler = static::getCompilerFromPath($this->path);
		}

		return $this->compiler;
	}

	public static function registerCompiler($extension, \Assets\Compilers\Compiler $compiler) {
		static::$compilers[$extension] = $compiler;
	}

	public static function getCompilerFromPath($path) {
		$extension = array_first(array_keys(static::$compilers), function($key, $value) use ($path) {
			return ends_with($path, $value);
		});

		if($extension === null) {
			throw new InvalidArgumentException('Unrecognized extension in file: ' . $path);
		}

		$compiler = static::$compilers[$extension];

		if(is_string($compiler)) {
			$className = '\Assets\Compilers\\' . $compiler;
			$compiler = new $className(static::$autoMinifyDefault);
			static::$compilers[$extension] = $compiler;
		}

		return $compiler;
	}

	public static function isPathSupported($path) {
		$extension = array_first(array_keys(static::$compilers), function($key, $value) use ($path) {
			return ends_with($path, $value);
		});

		return $extension !== null;
	}

	public static function publishedPath($path) {
		static $published = null;

		if($published === null) {
			$published = config('published_assets');
		}

		if(strpos($path, 'assets/') !== 0) {
			$path = 'assets/' . $path;
		}

		if(isset($published[$path])) {
			$path = $published[$path];
		}

		if(strpos($path, '://') === false) {
			return app('url')->asset($path);
		} else {
			return $path;
		}
	}

}