<?php

namespace sndsgd\cli\task\phar;

use \Exception;
use \Phar;
use \sndsgd\Debug;
use \sndsgd\Field;
use \sndsgd\field\rule\Closure;
use \sndsgd\field\rule\Required;
use \sndsgd\field\rule\MaxValueCount;
use \sndsgd\field\rule\PathTest;
use \sndsgd\field\rule\WritablePath;
use \sndsgd\field\ValidationError;
use \sndsgd\Task;
use \sndsgd\util\Dir;
use \sndsgd\util\File;
use \sndsgd\util\Path;
use \sndsgd\util\Temp;


/**
 * A task that extracts files from a phar into a directory
 */
class Extract extends Task
{
   /**
    * Create the app
    */
   public function __construct()
   {
      parent::__construct();
      $fc = $this->getFieldCollection();
      $fc->addFields(
         Field::string('phar')
            ->addAliases('p')
            ->setDescription('The the phar to extract')
            ->addRules(
               new Required(),
               new MaxValueCount(1),
               new ValidatePhar()
            ),
         Field::string('dir')
            ->addAliases('d')
            ->setDescription('The directory to extract the phar contents into')
            ->addRules(
               new Required(),
               new MaxValueCount(1),
               new WritablePath(true),
               new Closure(function($dir, $d, $n, $i, $c) {
                  if (
                     file_exists($dir) && 
                     is_dir($dir) &&
                     (count(scandir($dir)) !== 2)
                  ) {
                     $err = "must be an empty directory";
                     return new ValidationError($err, $dir, $n, $i);
                  }
                  return $dir;
               })
            )
      );
   }

   /**
    * {@inheritdoc}
    */
   public function getDescription()
   {
      return "Extract the contents of a phar into a directory";
   }

   /**
    * {@inheritdoc}
    */
   public function run(array $opts = null)
   {
      if (($test = Dir::prepare($opts['dir'])) !== true) {
         Debug::error("failed to create directory; $test\n");
      }

      $path = $this->getValidPharPath($opts['phar']);

      try {
         Debug::info("loading phar...", Debug::VERBOSE_1);
         $phar = new Phar($path);
         Debug::info(" @[green]done@[reset]\n", Debug::VERBOSE_1);

         Debug::info("extracting contents...", Debug::VERBOSE_1);
         $phar->extractTo($opts['dir']);
         Debug::info(" @[green]done@[reset]\n", Debug::VERBOSE_1);

         return $opts['dir'].PHP_EOL;
      }
      catch (Exception $ex) {
         Debug::info("\n", Debug::VERBOSE_1);
         $message = $ex->getMessage();
         Debug::error("failed to extract phar\n$message\n");
      }
   }

   /**
    * PHP will fail to open a phar file that doesn't have the phar extension
    *
    * @param string $phar The absolute path to a phar
    * @return string
    */
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
