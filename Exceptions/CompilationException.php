<?php namespace Assets\Exceptions;

class CompilationException extends \Exception {
	public $log;
	public $context;

	public function __construct($path, $log, $context = null) {
		parent::__construct('Error compiling ' . $path);

		$this->log = $log;
		$this->context = $context;
	}

}
