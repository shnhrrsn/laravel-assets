<?php namespace Assets\Compilers;

use Symfony\Component\Process\Process;

class LessCompiler extends Compiler {

	public function getCompileProcess($path, $context = null) {
		return new Process('lessc --yui-compress ' . escapeshellarg($path));
	}

	public function getLastModified($file, $newest = 0) {
		if(!file_exists($file)) {
			return $newest;
		}

		$newest = max(@filemtime($file), $newest);

		$contents = file_get_contents($file);

		if(preg_match_all('/@import\s?([^\s;]+)/is', $contents, $m)) {
			$dirname = dirname($file);

			foreach($m[1] as $import) {
				$import = trim(preg_replace('/("|\'|url|\(|\))/', '', $import));
				$import = $dirname . '/' . $import;

				if(substr($import, -5) !== '.less') {
					if(file_exists($import . '.less')) {
						$newest = $this->getLastModified($import . '.less', $newest);
					}
				} else {
					$newest = $this->getLastModified($import, $newest);
				}
			}
		}

		unset($contents);

		return $newest;
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