<?php namespace Assets\Compilers;

class JavascriptCompiler extends ProcessCompiler {

	public function compile($path, $context = null) {
		if($this->autoMinify) {
			return parent::compile($path, $context);
		} else {
			return file_get_contents($path);
		}
	}

	protected function getCompileProcess($path, $context = null) {
		return $this->makeProcess('uglifyjs', [
			'--compress',
			' drop_console=true',
			$path
		]);
	}

	public function getMime() {
		return 'application/javascript';
	}

	public function getType() {
		return 'js';
	}

	public function getExtension() {
		return 'js';
	}

}
