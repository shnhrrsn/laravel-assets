<?php namespace Assets\Compilers;

use Symfony\Component\Process\Process;

abstract class Compiler {
	protected $autoMinify;

	public function __construct($autoMinify) {
		$this->autoMinify = $autoMinify;
	}

	public abstract function compile($path, $context = null);

	public abstract function getLastModified($file, $newest = 0);

	public abstract function getMime();
	public abstract function getType();
	public abstract function getExtension();

}
