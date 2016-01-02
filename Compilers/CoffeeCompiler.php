<?php namespace Assets\Compilers;

use Symfony\Component\Process\Process;

class CoffeeCompiler extends ProcessCompiler {

	protected function getCompileProcess($path, $context = null) {
		$out = escapeshellarg(tempnam(sys_get_temp_dir(), sha1($path)));
		$uglify = $this->autoMinify ? ' | uglifyjs --compress drop_console=true' : '';
		return new Process(
			'bash -c "importer ' . escapeshellarg($path) . ' ' . $out .
				' && cat ' . $out . $uglify .
				' && rm ' . $out . '"'
		);
	}

	public function getLastModified($file, $newest = 0) {
		if(!file_exists($file)) {
			return $newest;
		}

		$newest = max(@filemtime($file), $newest);
		$contents = file_get_contents($file);

		if(preg_match_all('/#import\s?([^\s;]+)/is', $contents, $m)) {
			$dirname = dirname($file);

			foreach($m[1] as $import) {
				$import = trim(preg_replace('/("|\')/', '', $import));
				$partial = null;
				$import = $dirname . '/' . $import;

				$ext = substr($import, -7);

				if($ext !== '.coffee') {
					if(file_exists($import . '.coffee')) {
						$newest = $this->getLastModified($import . '.coffee', $newest);
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
		return 'application/javascript';
	}

	public function getType() {
		return 'js';
	}

	public function getExtension() {
		return 'js';
	}

}