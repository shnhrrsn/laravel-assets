<?php namespace Assets\Compilers;

use Closure;
use Symfony\Component\Process\Process;

abstract class Compiler {
	protected $autoMinify;

	public function __construct($autoMinify, array $options = [ ]) {
		$this->autoMinify = $autoMinify;
	}

	public abstract function compile($path, $context = null);

	public function getLastModified($file, $newest = 0) {
		if(!file_exists($file)) {
			return $newest;
		}

		$newest = max(@filemtime($file), $newest);

		$this->enumerateImports($file, function($file) use (&$newest) {
			$newest = $this->getLastModified($file, $newest);
		});

		return $newest;
	}

	public abstract function getMime();
	public abstract function getType();
	public abstract function getExtension();

	protected function enumerateImports($file, Closure $callback) {
		// Does nothing by default, subclasses that support imports
		// should override and provider their own implementation.
	}

}
