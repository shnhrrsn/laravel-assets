<?php namespace Assets\Compilers;

use BadMethodCallException;

use Symfony\Component\Process\Process;
use Assets\Exceptions\CompilationException;

class CoffeeCompiler extends ProcessCompiler {

	public function compile($path, $context = null) {
		$tmp = tempnam(sys_get_temp_dir(), sha1($path)) . '.js';
		touch($tmp); // Ensures file always exists for unlinking

		try {
			$this->compileProcess(new Process('importer ' . escapeshellarg($path) . ' ' . escapeshellarg($tmp)));
		} catch(CompilationException $e) {
			unlink($tmp);
			throw $e;
		}

		if(!$this->autoMinify) {
			$output = file_get_contents($tmp);
			unlink($tmp);
			return $output;
		} else {
			try {
				return $this->compileProcess(new Process('uglifyjs --compress drop_console=true ' . escapeshellarg($tmp)));
			} catch(CompilationException $e) {
				throw $e;
			} finally {
				unlink($tmp);
			}
		}
	}

	protected function getCompileProcess($path, $context = null) {
		throw new BadMethodCallException();
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