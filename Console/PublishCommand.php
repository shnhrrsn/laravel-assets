<?php namespace Assets\Console;

use Assets\Asset;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;
use Symfony\Component\Finder\SplFileInfo;

class PublishCommand extends \Illuminate\Console\Command {
	public $name = 'assets:publish';
	public $description = 'Compies and publishes all assets';

	private $resourcesPath;
	private $assetsPath;
	private $publishPath;
	private $assets = [ ];
	private $oldAssets = [ ];

	private $rawDirs = [ 'img', 'fonts', 'font', 'css' ];

	public function fire() {
		$app = $this->laravel;

		Asset::$autoMinifyDefault = $app->environment('production');

		$this->resourcesPath = base_path('resources');
		$this->assetsPath = base_path('resources/assets');
		$this->publishPath = public_path();
		$this->oldAssets = config('published_assets');

		if(empty($this->oldAssets)) {
			$this->oldAssets = [ ];
		}

		$this->compile();

		foreach($this->rawDirs as $dir) {
			$this->rawFiles($dir);
		}

		$this->writeConfig($this->assets);

		foreach($this->oldAssets as $asset) {
			@unlink($asset);
		}
	}

	private function compile() {
		$rawDirsPattern = '/^(' . implode('|', $this->rawDirs) . ')(\/|$)/i';

		foreach(Finder::create()->files()->in($this->assetsPath) as $file) {
			$relativePath = $file->getRelativePath();
			if(empty($relativePath) || preg_match($rawDirsPattern, $relativePath)) continue;
			if(substr($file->getBasename(), 0, 1) === '_') continue;

			$asset = Asset::make($file->getRealPath());

			$process = $asset->getCompileProcess();

			$this->info($process->getCommandline());
			$process->setEnv([
				'PATH' => trim(`echo \$PATH`) . ':/usr/local/bin/'
			]);

			$out = '';
			$err = '';
			$status = $process->run(function($type, $line) use(&$out, &$err) {
				if($type === 'out') {
					$out .= $line . PHP_EOL;
					$err .= $line . PHP_EOL;
				} else if($type === 'err') {
					$err .= $line . PHP_EOL;
				}
			});

			$name = $file->getRelativePathname();
			$name = $asset->getType() . substr($name, strpos($name, '/'));
			$name = substr($name, 0, strrpos($name, '.')) . '-' . md5($out) . '.' . $asset->getType();

			if($status === 0) {
				$this->storeAsset($file->getRealPath(), $this->publishPath . '/' . $name, $out, $asset->getLastModified());
			} else {
				$this->error($err);
				exit(1);
			}

			unset($out, $err, $process);
		}
	}

	private function rawFiles($dir) {
		$src = $this->assetsPath . '/' . $dir;
		$dest = $this->publishPath . '/' . $dir . '/';

		if(!file_exists($src)) return;

		$src = realpath($src);

		foreach(Finder::create()->files()->in($src) as $file) {
			$path = $file->getRealPath();
			$base = substr($path, strlen($src) + 1);
			$this->info('Copying ' . $base);

			$this->storeAsset($path, $dest . $base, $file->getContents(), filemtime($path));
		}
	}

	private function storeAsset($asset, $file, $contents, $modifiedTime = null) {
		if(!file_exists(dirname($file))) {
			mkdir(dirname($file), 0777, true);
		}

		file_put_contents($file, $contents);

		if($modifiedTime !== null) {
			touch($file, $modifiedTime);
		}

		$src = substr($asset, strlen($this->resourcesPath) + 1);
		$dest = substr($file, strlen($this->publishPath) + 1);
		$this->assets[$src] = $dest;

		if(isset($this->oldAssets[$src])) {
			if($this->oldAssets[$src] === $dest) {
				unset($this->oldAssets[$src]);
			}
		}
	}

	private function writeConfig($config) {
		$file = "<?php\n\n";
		$file .= 'return ' . var_export($config, true) . ';';
		file_put_contents(base_path() . "/config/published_assets.php", $file);
	}

}
