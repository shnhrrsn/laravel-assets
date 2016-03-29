<?php namespace Assets\Console;

use Symfony\Component\Finder\Finder;

class InstallToolchainCommand extends BaseCommand {
	public $name = 'assets:install-toolchain';
	public $description = 'Installs necessary tools for compiling assets.';

	public function fire() {
		$npms = [ ];
		$gems = [ ];

		foreach(config('assets.compilers') as $compiler) {
			if(!is_array($compiler)) {
				continue;
			}

			$toolchain = array_get($compiler, 'toolchain');
			if(!is_array($toolchain)) {
				continue;
			}

			$gem = array_get($toolchain, 'gem');
			if(is_array($gem)) {
				foreach($gem as $bin => $name) {
					if(!$this->hasBin($bin)) {
						$gems[] = $name;
					}
				}
			}

			$npm = array_get($toolchain, 'npm');
			if(is_array($npm)) {
				foreach($npm as $bin => $name) {
					if(!$this->hasBin($bin)) {
						$npms[] = $name;
					}
				}
			}
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

		if(!empty($gems) && !$this->hasBin('gem')) {
			$this->error('ERROR: RubyGems not installed');
			$this->info(' --> RubyGems needs to be installed before running ' . $this->name);
			$this->info(' --> Install RubyGems by running the following:');

			if(stripos(PHP_OS, 'darwin') !== false) {
				$this->error('        RubyGems should be preinstalled on Mac.  Please check your $PATH and try again.');
			} else {
				$this->info('        `sudo apt-get install rubygems -y`');
			}

			return;
		}

		foreach($npms as $npm) {
			if(!$this->installNpmPackage($npm)) {
				$this->error(' --> Unable to install “' . $npm .'”, aborting.');
				return;
			}
		}

		foreach($gems as $gem) {
			if(!$this->installGemPackage($gem)) {
				$this->error(' --> Unable to install “' . $gem .'”, aborting.');
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
		return $this->system('sudo /usr/bin/env npm install -g ' . $package) == 0;
	}

	private function installGemPackage($package) {
		$this->info('Installing ' . $package);
		return $this->system('sudo /usr/bin/env gem install ' . $package) == 0;
	}

	private function system($command) {
		$exitCode = 0;
		system($command, $exitCode);
		return $exitCode;
	}

}
