<?php

namespace sndsgd\cli;

use \Exception;
use \InvalidArgumentException;
use \sndsgd\util\Dir;
use \sndsgd\util\File;
use \sndsgd\util\Path;


class Command extends \sndsgd\util\Singleton
{
   /**
    * Set the path to a binary
    *
    * @param string $name The name of the binary
    * @param string $path The absolute path to the binary
    * @return void
    * @throws InvalidArgumentException If the path is not an executable file
    */
   public static function setPath($name, $path)
   {
      $instance = self::getInstance();
      $instance->addPath($name, $path);
   }

   /**
    * Get the path to a binary given its name
    *
    * @param string $name The name of the binary to retrieve the path to
    * @return string The absolute path to the binary
    * @throws Exception If the binary cannot be found
    */
   public static function getPath($name)
   {
      $instance = self::getInstance();
      if (array_key_exists($name, $instance->paths)) {
         return $instance->paths[$name];
      }
      else {
         foreach ($instance->dirs as $dir) {
            $path = $dir.DIRECTORY_SEPARATOR.$name;
            if (file_exists($path) && is_executable($path)) {
               $instance->addPath($name, $path);
               return $path;
            }
         }
         throw new Exception("failed to locate binary '$name'");
      }
   }

   /**
    * Add a directory to the list of directories to search for binaries in
    *
    * @param string $dir
    * @param boolean $prepend Whether or not to prepend to the list
    * @return void
    */
   public static function addSearchDir($dir, $prepend = false)
   {
      if (($test = Dir::isReadable($dir)) !== true) {
         throw new InvalidArgumentException(
            "invalid value provided for 'dir'; $test"
         );
      }

      $instance = self::getInstance();
      $dirs = array_flip($instance->dirs);
      if (!array_key_exists($dir, $dirs)) {
         if ($prepend === true) {
            array_unshift($instance->dirs, $dir);
         }
         else {
            $instance->dirs[] = $dir;
         }
      }
      $dirs[$dir] = true;
      $instance->dirs = array_keys($dirs);
   }

   /**
    * Verified binary paths
    *
    * @var array.<string,string>
    */
   protected $paths = [];

   /**
    * Directories to search for binaries in
    *
    * @var array.<string>
    */
   protected $dirs = [];

   /**
    * Initialize the singleton instance
    *
    * Adds PATH environment directories to array of search directories
    */
   public function __construct()
   {
      if ($dirs = getenv('PATH')) {
         $dirs = explode(':', $dirs);
         $dirs = array_flip($dirs);
         $this->dirs = array_keys($dirs);
      }
   }

   /**
    * Add a binary for later retrieval
    *
    * @param string $name The name of the binary
    * @param string $path The absolute path to the binary
    * @return void
    */
   protected function addPath($name, $path)
   {
      if (($test = Path::test($path, File::EXECUTABLE)) !== true) {
         throw new InvalidArgumentException(
            "invalid value provided for 'path'; $test"
         );
      }
      $this->paths[$name] = $path;
   }
}
