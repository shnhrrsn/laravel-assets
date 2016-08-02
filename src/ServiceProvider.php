<?php namespace Assets;

class ServiceProvider extends \Illuminate\Support\ServiceProvider {
	private $configPath;

	public function __construct($app) {
		parent::__construct($app);

		$this->configPath = __DIR__ . '/../config/assets.php';
	}

	public function register() {
		if($this->app->configurationIsCached()) {
			return;
		}

		// NOTE: mergeConfigFrom isn’t used because it does array_merge, not array_replace_recursive

		$key = 'assets';
		$repository = $this->app['config'];
		$config = array_replace_recursive(require $this->configPath, $repository->get($key, [ ]));

		$macros = [
			'node_modules' => array_get($config, 'node_modules', null)
		];

		$macros = array_combine(array_map(function($key) {
			return '/\{\{\s*' . preg_quote($key) . '\s*\}\}/';
		}, array_keys($macros)), array_values($macros));

		$this->expandConfigMacros($config, $macros);
		$repository->set($key, $config);
	}

	public function boot() {
		$config = $this->app['config'];
		$autoMinify = $config->get('assets.auto_minify');

		if($autoMinify !== true && $autoMinify !== false) {
			$autoMinify = $config->get('app.debug', false);
		}

		Asset::$autoMinifyDefault = $autoMinify;

		foreach($config->get('assets.compilers', [ ]) as $extensions => $class) {
			$options = [ ];

			if(is_array($class)) {
				$options = array_get($class, 'options', [ ]);
				$class = $class['class'];
			}

			$compiler = new $class($autoMinify, $options);

			foreach(explode(',', $extensions) as $extension) {
				Asset::registerCompiler($extension, $compiler);
			}
		}

		if($this->app->resolved('router') || $this->app->bound('router')) {
			$router = $this->app['router'];

			$router->get('assets/img/{a?}/{b?}/{c?}/{d?}/{e?}', '\Assets\Http\Controller@img');
			$router->get('assets/font/{a?}/{b?}/{c?}/{d?}/{e?}', '\Assets\Http\Controller@font');
			$router->get('assets/fonts/{a?}/{b?}/{c?}/{d?}/{e?}', '\Assets\Http\Controller@font');
			$router->get('assets/css/{a?}/{b?}/{c?}/{d?}/{e?}', '\Assets\Http\Controller@css');
			$router->get('assets/{type}/{a?}/{b?}/{c?}/{d?}/{e?}', '\Assets\Http\Controller@compile');
		}

		$this->commands(
			Console\PublishCommand::class,
			Console\UnpublishCommand::class,
			Console\InstallToolchainCommand::class
		);

		if(class_exists('\Collective\Html\HtmlBuilder')) {
			\Collective\Html\HtmlBuilder::macro('assetPath', function($path) {
				return Asset::publishedPath($path);
			});
		}

	    $this->publishes([
	        $this->configPath => config_path('assets.php'),
	    ]);
	}

	private function expandConfigMacros(&$config, $macros) {
		$sequential = array_values($config) === $config;
		$reindex = false;

		foreach($config as $key => &$value) {
			if(is_array($value)) {
				$this->expandConfigMacros($value, $macros);
				continue;
			} else if(!is_string($value)) {
				continue;
			}

			foreach($macros as $pattern => $replacement) {
				if($replacement !== null) {
					$value = preg_replace($pattern, $replacement, $value);
					continue;
				}

				if(preg_match($pattern, $value)) {
					$reindex = $sequential;
					unset($config[$key]);
				}
			}
		}

		if($reindex) {
			$config = array_values($config);
		}
	}

}
