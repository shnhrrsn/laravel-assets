<?php namespace Assets\Console;

use Symfony\Component\Finder\Finder;

class InstallToolchainCommand extends BaseCommand {
	public $name = 'assets:install-toolchain';
	public $description = 'Installs necessary tools for compiling assets.';

	public function fire() {
		$npms = [ ];

		foreach(config('assets.compilers') as $compiler) {
			if(!is_array($compiler)) {
				continue;
			}

			$toolchain = array_get($compiler, 'toolchain');
			if(!is_array($toolchain)) {
				continue;
			}

			$npms = array_merge($npms, array_get($toolchain, 'npm', [ ]));
		}

		if(!empty($npms) && !$this->hasBin('npm')) {
			$this->error('ERROR: NPM not installed');
			$this->info(' --> NPM needs to be installed before running ' . $this->name);
			$this->info(' --> Install NPM by running the following:');

			if(stripos(PHP_OS, 'darwin') !== false) {
				$this->info('        `sudo curl -L https://npmjs.org/install.sh | sh`');
			} else {
				$this->info('        `sudo apt-get install npm -y`');
			}

			return;
		}

		$packagePath = base_path('package.json');

		if(!file_exists($packagePath)) {
			$package = [ ];

			if(file_exists(base_path('composer.json'))) {
				$composer = json_decode(file_get_contents(base_path('composer.json')), true);

				foreach([ 'name', 'version', 'description' ] as $key) {
					if(isset($composer[$key])) {
						$package[$key] = $composer[$key];
					}
				}
			}

			$package['private'] = true;
			file_put_contents($packagePath, json_encode($package, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
		}

		$package = json_decode(file_get_contents($packagePath), true);
		$installed = array_keys(array_get($package, 'dependencies', [ ]));
		$installed = array_merge($installed, array_keys(array_get($package, 'devDependencies', [ ])));

		foreach($npms as $npm) {
			if(in_array($npm, $installed)) {
				continue;
			}

			if(!$this->confirm('Do you want to install: ' . $npm . '?', true)) {
				continue;
			}

			if(!$this->installNpmPackage($npm)) {
				$this->error(' --> Unable to install “' . $npm .'”, aborting.');
				return;
			}
		}

		$this->comment('Toolchain is installed!');
	}

	private function hasBin($bin) {
		return $this->system('/usr/bin/env which ' . escapeshellarg($bin) .' > /dev/null') == 0;
	}

	private function installNpmPackage($package) {
		$this->info('Installing ' . $package);
		return $this->system('/usr/bin/env npm install --save ' . $package) == 0;
	}

	private function system($command) {
		$exitCode = 0;
		system($command, $exitCode);
		return $exitCode;
	}

}
