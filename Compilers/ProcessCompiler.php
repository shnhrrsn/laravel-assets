<?php namespace Assets\Compilers;

use Symfony\Component\Process\Process;
use Assets\Exceptions\CompilationException;

abstract class ProcessCompiler extends Compiler {

	protected $paths = [
		'/bin/',
		'/usr/bin/',
		'/usr/local/bin/'
	];

	public function __construct($autoMinify) {
		parent::__construct($autoMinify);

		$this->paths = array_filter($this->paths, function($path) {
			return file_exists($path);
		});
	}

	protected abstract function getCompileProcess($path, $context = null);

	public function compile($path, $context = null) {
		return $this->compileProcess($this->getCompileProcess($path, $context), $path);
	}

	protected function makeProcess($command, array $arguments = null) {
		$command = escapeshellcmd($command);

		if($arguments !== null && !empty($arguments)) {
			foreach($arguments as $argument) {
				$command .= ' ' . escapeshellarg($argument);
			}
		}

		return new Process($command);
	}

	protected function compileProcess(Process $process, $path) {
		$process->setEnv([
			'PATH' => trim(`echo \$PATH`) . ':' . implode(':', $this->paths)
		]);

		$out = '';
		$err = '';
		$status = $process->run(function($type, $line) use(&$out, &$err) {
			if($type === 'out') {
				$out .= $line . PHP_EOL;
			} else if($type === 'err') {
				$err .= $line . PHP_EOL;
			}
		});

		if($status === 0) {
			return $out;
		} else {
			throw new CompilationException($path, $err, [
				'path' => $process->getEnv()['PATH'],
				'time' => date('Y-m-d H:i T'),
				'command' => $process->getCommandLine()
			]);
		}
	}

}
