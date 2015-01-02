<?php

namespace sndsgd\cli\task\phar;

use \Phar;
use \sndsgd\cli\Process;
use \sndsgd\Debug;
use \sndsgd\event\Event;
use \sndsgd\Field;
use \sndsgd\field\rule\Closure;
use \sndsgd\field\rule\Required;
use \sndsgd\field\rule\MaxValueCount;
use \sndsgd\field\rule\PathTest;
use \sndsgd\field\rule\Regex;
use \sndsgd\field\ValidationError;
use \sndsgd\Task;
use \sndsgd\util\Classname;
use \sndsgd\util\Dir;
use \sndsgd\util\File;
use \sndsgd\util\Json;
use \sndsgd\util\Path;
use \sndsgd\util\Str;
use \sndsgd\util\Temp;


class Generate extends Task
{
   public static function validateComposer($v, $d, $n, $i, $c)
   {
      $err = null;
      $path = Path::normalize("$v/composer.json");
      if (($test = File::isReadable($path)) !== true) {
	 $err = "failed to read '$path'; $test";
      }
      else if (($json = file_get_contents($path)) === null) {
	 $err = "failed to read '$path'";
      }
      else if (($data = json_decode($json, true)) === null) {
	 $err = "failed to decode '$path'; ".Json::getError();
      }
      else {
	 $c->addData('composer', $data);
      }

      return ($err === null)
	 ? $v
	 : new ValidationError($err, $v, $n, $i);
   }

   /**
    * The app source directories and dependencies are copied here, then the
    * phar is created from this directory.
    *
    * @var string
    */
   protected $tempdir;

   /**
    * The absolute path to the phar file
    *
    * @var string
    */
   protected $path;

   /**
    * Create the app
    */
   public function __construct()
   {
      $this->validatePharWrite();

      parent::__construct();

      $task = $this;
      $fc = $this->getFieldCollection();
      $fc->addFields(
	 Field::boolean('executable')
	    ->addAliases('x')
	    ->setDescription('Make the resulting file executable'),
	 Field::string('working-dir')
	    ->setExportName('dir')
	    ->addAliases('d')
	    ->setDefault('.')
	    ->setDescription('The absolute directory of the project')
	    ->addRules(
	       new Required(),
	       new MaxValueCount(1),
	       new PathTest(Dir::READABLE),
	       new Closure(__CLASS__.'::validateComposer')
	    ),
	 Field::string('class')
	    ->addAliases('c')
	    ->setDescription('The namespaced name of the task class to pharify')
	    ->addRules(
	       new Required(),
	       new MaxValueCount(1),
	       new Closure(function($v, $d, $n, $i, $c) {
		  $class = Classname::toString($v);
		  return (Task::validateClassname($class))
		     ? $class
		     : new ValidationError("must be a subclass of sndsgd\\Task");
	       })
	    ),
	 Field::string('output-directory')
	    ->addAliases('o')
	    ->setExportName('outdir')
	    ->setDescription('The directory to create the phar in')
	    ->setDefault('.')
	    ->addRules(
	       new MaxValueCount(1),
	       new PathTest(Dir::WRITABLE)
	    ),
	 Field::string('name')
	    ->addAliases('n')
	    ->setExportName('name')
	    ->setDescription('The filename of the phar')
	    ->addRules(
	       new MaxValueCount(1),
	       (new Regex('/^[a-z0-9-_]+$/i'))
		  ->setMessage('contains wonky characters')
	    )
      );


      $fc->on('afterValidate', function(Event $ev) use ($fc) {
	 if (($name = $fc->exportFieldValue('name')) === null) {
	    $classname = $fc->exportFieldValue('class');
	    $class = Classname::split($classname);
	    $class = array_pop($class);
	    $fc->getField('name')->addValue(strtolower($class));
	 }
      });
   }

   /**
    * Verify that *phar.readonly* is *Off* in php.ini
    *
    * @todo update the php.ini script for the user
    */
   protected function validatePharWrite()
   {
      if (Str::toBoolean(ini_get('phar.readonly')) === true) {
	 $iniPath = php_ini_loaded_file();
	 Debug::error(
	    "phar write support is disabled\n".
	    "writing phar archives is currently disabled in php.ini\n".
	    "to continue, you must update the value for 'phar.readonly' ".
	    "in {$iniPath} to 'Off'\n"
	 );
      }
   }

   /**
    * {@inheritdoc}
    */
   public function getDescription()
   {
      return "Create a phar from an instance of sndsgd\Task";
   }

   /**
    * {@inheritdoc}
    */
   public function run(array $opts = null)
   {
      $this->tmpdir = Temp::dir(__CLASS__);
      $this->path = $opts['outdir'].DIRECTORY_SEPARATOR.$opts['name'].'.phar';
      $this->createSlimClone($opts);
      $this->createPhar($opts);
      if ($opts['executable']) {
	 $this->makeExecutable($opts);
      }
      $size = File::formatSize(filesize($this->path));
      Debug::info("resulting filesize: $size\n", 1);
      return $this->path;
   }

   /**
    * Create a copy that only contains neccesary files to create the phar from
    *
    */
   protected function createSlimClone($opts)
   {
      $composer = $this->copyComposer($opts);
      $this->copyAutoloadDirs($opts, $composer['autoload']);
      $this->updateComposer($opts);
   }

   private function copyComposer($o)
   {
      Debug::info("copying composer.json... ");
      $data = $this->fieldCollection->getData('composer');
      if (array_key_exists('require-dev', $data)) {
	 unset($data['require-dev']);
      }

      $path = $this->tmpdir.'/composer.json';
      Json::encodeFile($path, $data, Json::HUMAN);
      Debug::info("done\n");
      return $data;
   }

   private function copyAutoloadDirs($opts, $autoload)
   {
      Debug::info("copying autoload source directories... ");
      foreach ($autoload as $method => $paths) {
	 foreach ($paths as $ns => $dir) {
	    $dir = rtrim($dir, '/');
	    $source = $opts['dir'].DIRECTORY_SEPARATOR.$dir;
	    $dest = $this->tmpdir.DIRECTORY_SEPARATOR.$dir;
	    exec('cp -r '.escapeshellarg($source).' '.escapeshellarg($dest));
	 }
      }
      Debug::info("done\n");
   }

   private function updateComposer($opts)
   {
      Debug::info("fetching dependencies (this may take a while)... ");
      $proc = new Process([
	 'composer',
	 '--working-dir='.escapeshellarg($this->tmpdir),
	 'update',
	 '--no-dev',
	 '--prefer-dist',
	 '--optimize-autoloader',
      ]);
      $exitcode = $proc->exec();
      if ($exitcode !== 0) {
	 Debug::info("\n");
	 Debug::error("failed to fetch dependencies\n");
      }

      Debug::info("done\n");
      $autoloader = "{$this->tmpdir}/vendor/composer/autoload_classmap.php";
      $classes = require $autoloader;
      if (!array_key_exists($opts['class'], $classes)) {
	 Debug::error("the specified class could not be found\n");
      }
   }

   protected function createPhar($opts)
   {
      Debug::info("creating phar... ");
      $test = File::prepare($this->path);
      if ($test !== true) {
	 Debug::error("failed to create PHAR; $test\n");
      }

      $pharName = basename($this->path);
      $phar = new Phar($this->path, 0, $pharName);
      $phar->buildFromDirectory($this->tmpdir);
      $phar->setStub($this->getStub($pharName, $opts['class']));
      $phar->compressFiles(Phar::GZ);
      Debug::info("done\n");
   }

   private function getStub($pharName, $class)
   {
      return implode(PHP_EOL, [
	 '#!/usr/bin/env php',
	 '<?php',
	 '',
	 "Phar::mapPhar('{$pharName}');",
	 '',
	 "require 'phar://{$pharName}/vendor/autoload.php';",
	 '',
	 '$task = new '.$class.';',
	 '$runner = new sndsgd\\cli\\task\\Runner;',
	 '$result = $runner->run($task, $argv);',
	 'if (is_string($result) || is_int($result) || is_float($result)) {',
	 '   echo $result;',
	 '}',
	 '',
	 '__HALT_COMPILER();',
	 ''
      ]);
   }

   protected function makeExecutable($opts)
   {
      Debug::info("updating permissions... ", 2);
      if (!@chmod($this->path, 0775)) {
	 Debug::info("\n", 2);
	 Debug::error("failed to change permissions for '{$opts['file']}'\n");
      }
      Debug::info("done\n", 2);

      $filename = basename($this->path);
      list($name, $ext) = File::splitName($filename);
      if ($ext !== null) {
	 Debug::info("renaming from {$filename} to {$name}... ");
	 $source = $this->path;
	 $dest = dirname($this->path).DIRECTORY_SEPARATOR.$name;
	 if (!@rename($source, $dest)) {
	    Debug::info("\n", 2);
	    Debug::error("failed to rename '{$this->path}'\n");
	 }
	 $this->path = $dest;
	 Debug::info("done\n", 2);
      }
   }
}
