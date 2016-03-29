<?php namespace Assets\Compilers;

use Closure;

class LessCompiler extends ProcessCompiler {

	protected function getCompileProcess($path, $context = null) {
		return $this->makeProcess('lessc', [ '--yui-compress', $path ]);
	}

	protected function enumerateImports($file, Closure $callback) {
		$contents = file_get_contents($file);

		if(preg_match_all('/@import\s?([^\s;]+)/is', $contents, $m)) {
			$dirname = dirname($file);

			foreach($m[1] as $import) {
				$import = trim(preg_replace('/("|\'|url|\(|\))/', '', $import));
				$import = $dirname . '/' . $import;

				if(substr($import, -5) !== '.less') {
					if(file_exists($import . '.less')) {
						$callback($import . '.less');
					}
				} else if(file_exists($file)) {
					$callback($file);
				}
			}
		}

		unset($contents);
	}

	public function getMime() {
		return 'text/css';
	}

	public function getType() {
		return 'css';
	}

	public function getExtension() {
		return 'css';
	}

}
