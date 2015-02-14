<?php namespace Assets;

class ServiceProvider extends \Illuminate\Support\ServiceProvider {

	public function register() {

	}

	public function boot() {
		if($this->app->resolved('router') || $this->app->bound('router')) {
			$router = $this->app['router'];

			$router->get('assets/img/{a?}/{b?}/{c?}/{d?}/{e?}', '\Assets\Http\Controller@img');
			$router->get('assets/font/{a?}/{b?}/{c?}/{d?}/{e?}', '\Assets\Http\Controller@font');
			$router->get('assets/fonts/{a?}/{b?}/{c?}/{d?}/{e?}', '\Assets\Http\Controller@font');
			$router->get('assets/css/{a?}/{b?}/{c?}/{d?}/{e?}', '\Assets\Http\Controller@css');
			$router->get('assets/{type}/{a?}/{b?}/{c?}/{d?}/{e?}', '\Assets\Http\Controller@compile');
		}

		$this->commands('\Assets\Console\PublishCommand', '\Assets\Console\UnpublishCommand');

		if(class_exists('\Illuminate\Html\HtmlBuilder')) {
			\Illuminate\Html\HtmlBuilder::macro('assetPath', function($path) {
				return Asset::publishedPath($path);
			});
		}
	}

}
