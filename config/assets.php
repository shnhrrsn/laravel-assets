<?php

return [

	/**
		Minification is turned on by default when app.debug is false
		and turned off by default when it’s set to true.  To override
		this, explicity set the value below to true or false
	 */
	'auto_minify' => null,

	/**
		Extra paths that may not exist in the systems $PATH value
	*/
	'paths' => [
		'/bin/',
		'/usr/bin/',
		'/usr/local/bin/'
	],

	/**
		* To register a compiler, set the key to a comma deliminated value
		  of it’s file extension.
		* All compiler classes must extend `Assets\Compilers\Compiler`.
		* If an array value is provided, the options value of the array
		  will be passed to the compiler when it’s created.
	*/

	'compilers' => [

		'scss,sass' => [
			'class' => Assets\Compilers\ScssCompiler::class,
			'options' => [
				'bin' => 'scss',
				'include_paths' => [ ],
				'arguments' => [
					'--precision=14',
					'--compass'
				]
			]
		],

		'less' => Assets\Compilers\LessCompiler::class,
		'coffee' => Assets\Compilers\CoffeeCompiler::class,
		'js' => Assets\Compilers\JavascriptCompiler::class,

	]

];
