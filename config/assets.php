<?php

return [

	/**
		Minification is turned on by default when app.debug is false
		and turned off by default when it’s set to true.  To override
		this, explicity set the value below to true or false
	 */
	'auto_minify' => null,

	/**
		* To register a compiler, set the key to a comma deliminated value
		  of it’s file extension.
		* All compiler classes must extend `Assets\Compilers\Compiler`.
		* If an array value is provided, the options value of the array
		  will be passed to the compiler when it’s created.
	*/

	'compilers' => [

		'scss,sass' => Assets\Compilers\ScssCompiler::class,
		'less' => Assets\Compilers\LessCompiler::class,
		'coffee' => Assets\Compilers\CoffeeCompiler::class,
		'js' => Assets\Compilers\JavascriptCompiler::class,

	]

];
