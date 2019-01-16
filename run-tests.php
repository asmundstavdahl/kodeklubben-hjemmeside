<?php

require __DIR__."/src/autoload.php";

if(file_exists(__DIR__."/vendor/autoload.php")){
	require __DIR__."/vendor/autoload.php";
}

# Show all messages, including notices (for deprecations)
error_reporting(E_ALL);

# Make sure php.ini doesn't disable assert()
ini_set("zend.assertions", 1);
# 1 => throw exceptions and die, 0 => print warnings
ini_set("assert.exception", 1);
# Show errors (and warnings) in output
ini_set("display_errors", 1);

$testDir = __DIR__."/src/tests/";

# Remove [0]; that's this script
array_shift($argv);
$categories = $argv;

if(count($categories) == 0){
	echo "Running all tests...\n";
	echo "====================\n";
} else {
	echo "Running test beginning with...\n";
	echo array_reduce($categories, function($acc, $cur){
		return "{$acc}\t- {$cur}\n";
	});
	echo "====================\n";
}

$directoryIterator = new RecursiveDirectoryIterator($testDir);
$itIt = new RecursiveIteratorIterator($directoryIterator);
foreach($itIt as $node){
	# Test files must not start with an underscore;
	# use a prefixing underscore for supporting PHP files.
	if(is_file($node) && preg_match('/\/[^_][^\/]+\.php$/', $node)){
		$name = str_replace($testDir, "", $node);

		if(count($categories) > 0){
			$runFile = array_reduce(
				$categories,
				function($acc, $cur) use ($name) {
					return $acc || preg_match("/^{$cur}/", $name);
				}
			);
			if(!$runFile){
				continue;
			}
		}

		echo " · {$name}…\n";
		try {
			include $node;
			echo "\t→ OK?\n";
		} catch(Exception $e){
			echo "\t- {$e}\n";
		}
	}
}

echo "Finish!\n";
