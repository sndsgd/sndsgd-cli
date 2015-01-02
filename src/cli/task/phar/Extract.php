<?php

namespace sndsgd\cli\task\phar;

use \Exception;
use \Phar;
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


class Extract extends Task
{
   /**
    * Create the app
    */
   public function __construct()
   {
      parent::__construct();
      $this->fieldCollection->addFields(
	 Field::string('phar')
	    ->addAliases('p')
	    ->setDescription('The the phar to extract')
	    ->addRules(
	       new Required(),
	       new MaxValueCount(1),
	       new PathTest(File::READABLE)
	    ),
	 Field::string('dir')
	    ->addAliases('d')
	    ->setDescription('The directory to extract the phar contents into')
	    ->addRules(
	       new Required(),
	       new MaxValueCount(1)
	    )
      );
   }

   /**
    * {@inheritdoc}
    */
   public function getDescription()
   {
      return "Extract the contents of a PHP Archive (PHAR)";
   }

   /**
    * {@inheritdoc}
    */
   public function run(array $opts = null)
   {
      try {
	 $path = $this->getValidPharPath($opts['phar']);
	 $phar = new Phar($path);
	 $phar->extractTo($opts['dir']);
      }
      catch (Exception $ex) {
	 $message = $ex->getMessage();
	 Debug::error("failed to extract phar;\n$message\n");
      }
   }

   private function getValidPharPath($phar)
   {
      list($name, $ext) = File::splitName($phar);
      if (strtolower($ext) === 'phar') {
	 return $phar;
      }
      else if ($ext === null) {
	 $tmp = Temp::file(__METHOD__.'.phar');
	 if (!@copy($phar, $tmp)) {
	    Debug::error("failed to copy phar to path with extension\n");
	 }
	 return $tmp;
      }
      else {
	 Debug::error("Invalid extension; expecting 'phar', or no extension\n");
      }
   }
}
