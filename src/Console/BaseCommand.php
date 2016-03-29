<?php namespace Assets\Console;

abstract class BaseCommand extends \Illuminate\Console\Command {

	protected function removeAsset($asset) {
		@unlink($asset);
	}

}
