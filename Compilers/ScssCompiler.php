<?php namespace Assets\Compilers;

use Symfony\Component\Process\Process;

class ScssCompiler extends ProcessCompiler {

	protected function getCompileProcess($path, $context = null) {
		if(empty($context)) {
			if($this->autoMinify) {
				$context = 'compressed';
			} else {
				$context = 'nested';
			}
		}

		return new Process('scss -t ' . $context . ' --compass --precision=14 ' . escapeshellarg($path));
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
						$newest = $this->getLastModified($import . '.scss', $newest);
					} else if(file_exists($partial . '.scss')) {
						$newest = $this->getLastModified($partial . '.scss', $newest);
					} else if(file_exists($import . '.sass')) {
						$newest = $this->getLastModified($import . '.sass', $newest);
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
