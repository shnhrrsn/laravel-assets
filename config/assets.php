<?php

$nodeModules = base_path('node_modules');

if(!file_exists($nodeModules)) {
	$nodeModules = null;
}

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
		'{{ node_modules }}/.bin/',
		'/bin/',
		'/usr/bin/',
		'/usr/local/bin/'
	],

	/**
		When compiling, these dirs will be copied over as is
		without any processing
	*/
	'raw_dirs' => [
		'img', 'fonts', 'font', 'css'
	],

	/**
		Used for node based compilers
		Config can self-reference via {{ node_modules }}
	*/
	'node_modules' => $nodeModules,

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
				'bin' => 'node-sass',
				'include_paths' => [ ],
				'arguments' => [
					'--precision=14'
				]
			],
			'toolchain' => [
				'npm' => [ 'node-sass' ]
			]
		],

		'less' => [
			'class' => Assets\Compilers\LessCompiler::class,
			'toolchain' => [
				'npm' => [ 'less' ]
			]
		],

		'coffee' => [
			'class' => Assets\Compilers\CoffeeCompiler::class,
			'toolchain' => [
				'npm' => [ 'coffee-script' ]
			]
		],

		'js' => [
			'class' => Assets\Compilers\JavascriptCompiler::class,
			'toolchain' => [
				'npm' => [ 'uglify-js' ]
			]
		],

	]

];
