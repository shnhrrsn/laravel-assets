<?php namespace Assets\Compilers;

use Closure;

class ScssCompiler extends ProcessCompiler {

	protected function getCompileProcess($path, $context = null) {
		if(empty($context)) {
			if($this->autoMinify) {
				$context = 'compressed';
			} else {
				$context = 'nested';
			}
		}

		return $this->makeProcess('scss', [
			'-t', $context,
			'--compass',
			'--precision=14',
			$path
		]);
	}

	protected function enumerateImports($file, Closure $callback) {
		$contents = file_get_contents($file);

		if(preg_match_all('/@import\s?([^\s;]+)/is', $contents, $m)) {
			$dirname = dirname($file);

			foreach($m[1] as $import) {
				$import = trim(preg_replace('/("|\'|url|\(|\))/', '', $import));
				$partial = null;

				if(strpos($import, '/') !== false) {
					$partial = $dirname . '/' . dirname($import) . '/_' . basename($import);
				} else {
					$partial = $dirname . '/_' . $import;
				}

				$import = $dirname . '/' . $import;

				$ext = substr($import, -5);

				if($ext !== '.scss' && $ext !== '.sass') {
					if(file_exists($import . '.scss')) {
						$callback($import . '.scss');
					} else if(file_exists($partial . '.scss')) {
						$callback($partial . '.scss');
					} else if(file_exists($import . '.sass')) {
						$callback($import . '.sass');
					}
				} else if(file_exists($import)) {
					$callback($import);
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
