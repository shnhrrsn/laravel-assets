<?php namespace Assets\Console;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Process\Process;
use Symfony\Component\Finder\SplFileInfo;

use Assets\Asset;
use Assets\Exceptions\CompilationException;

class PublishCommand extends BaseCommand {
	public $name = 'assets:publish';
	public $description = 'Compies and publishes all assets';

	protected $resourcesPath;
	protected $assetsPath;
	protected $publishPath;
	protected $assets = [ ];
	protected $oldAssets = [ ];

	protected $rawDirs = [ 'img', 'fonts', 'font', 'css' ];

	public function fire() {
		$app = $this->laravel;

		Asset::$autoMinifyDefault = $app->environment('production');

		$this->resourcesPath = base_path('resources');
		$this->assetsPath = base_path('resources/assets');
		$this->publishPath = public_path();
		$this->oldAssets = $this->loadOldAssets();

		if(empty($this->oldAssets)) {
			$this->oldAssets = [ ];
		}

		$this->compile();

		foreach($this->rawDirs as $dir) {
			$this->rawFiles($dir);
		}

		$this->writeConfig($this->assets);
		$this->removeAssets($this->oldAssets);
	}

	private function compile() {
		$rawDirsPattern = '/^(' . implode('|', $this->rawDirs) . ')(\/|$)/i';

		foreach(Finder::create()->files()->in($this->assetsPath) as $file) {
			$relativePath = $file->getRelativePath();
			if(empty($relativePath) || preg_match($rawDirsPattern, $relativePath)) continue;
			if(substr($file->getBasename(), 0, 1) === '_') continue;

			$asset = Asset::make($file->getRealPath());
			$out = null;

			try {
				$out = $asset->compile();
			} catch(CompilationException $e) {
				$this->error($e->getMessage());

				if(is_array($e->context)) {
					foreach($e->context as $key => $value) {
						$this->error(' --> ' . strtoupper($key) . ': ' . $value);
					}
				} else if(is_string($e->context)) {
					$this->error(' --> ' . $e->context);
				}

				if(!empty($e->log)) {
					$this->error(' --> ' . $e->log);
				}

				exit(1);
			}

			$name = $file->getRelativePathname();
			$name = $asset->getType() . substr($name, strpos($name, '/'));
			$name = substr($name, 0, strrpos($name, '.')) . '-' . md5($out) . '.' . $asset->getType();

			$this->storeAsset($file->getRealPath(), $this->publishPath . '/' . $name, $out, $asset->getLastModified());

			unset($out, $err, $asset);
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

	protected function loadOldAssets() {
		return config('published_assets');
	}

	protected function storeAsset($asset, $file, $contents, $modifiedTime = null) {
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

	protected function removeAssets($assets) {
		foreach($assets as $asset) {
			$this->removeAsset($asset);
		}
	}

	protected function writeConfig($config) {
		$file = "<?php\n\n";
		$file .= 'return ' . var_export($config, true) . ';';
		file_put_contents(base_path() . "/config/published_assets.php", $file);
	}

}
