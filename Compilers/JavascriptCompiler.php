<?php namespace Assets\Compilers;

use Symfony\Component\Process\Process;

class JavascriptCompiler extends Compiler {

	public function getCompileProcess($path, $context = null) {
		$uglify = $this->autoMinify ? ' | uglifyjs --compress drop_console=true' : '';
		return new Process('cat ' . escapeshellarg($path) . $uglify);
	}

	public function getLastModified($file, $newest = 0) {
		if(!file_exists($file)) {
			return $newest;
		} else {
			return max(filemtime($file), $newest);
		}
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
