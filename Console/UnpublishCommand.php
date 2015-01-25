<?php namespace Assets\Console;

use Symfony\Component\Finder\Finder;

class UnpublishCommand extends \Illuminate\Console\Command {
	public $name = 'assets:unpublish';
	public $description = 'Removes published assets';

	public function fire() {
		chdir(public_path());

		$dirs = [ ];
		foreach(config('published_assets') as $asset) {
			$this->info('Removing asset: ' . $asset);
			$dirs[] = realpath(dirname($asset));
			@unlink($asset);
		}

		foreach($dirs as $dir) {
			if(empty($dir) || !file_exists($dir)) continue;

			if(Finder::create()->in($dir)->files()->count() === 0) {
				$this->info('Removing empty asset directory: ' . $dir);
				@rmdir($dir);
			}
		}

		@unlink(base_path() . "/config/published_assets.php");
	}

}