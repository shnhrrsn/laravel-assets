<?php namespace Assets;

class ServiceProvider extends \Illuminate\Support\ServiceProvider {
	private $configPath;

	public function __construct($app) {
		parent::__construct($app);

		$this->configPath = __DIR__ . '/../config/assets.php';
	}

	public function register() {
		$this->mergeConfigFrom($this->configPath, 'assets');
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

}
