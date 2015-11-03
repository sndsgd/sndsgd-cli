<?php

namespace sndsgd;

use \sndsgd\cli\Command;
use \sndsgd\shell\Process;
use \sndsgd\Path;


class Cli
{
   /**
    * Get the name of the command that was executed
    *
    * @param boolean $asCalled Set to true to return the name as it was called
    * @return string
    */
   public static function getScriptName($asCalled = false)
   {
      $name = $_SERVER['argv'][0];
      return ($asCalled === false) ? basename($name) : $name;
   }

   /**
    * Attempt to determine the path to the currently executing script
    *
    * @return string The absolute path the script that was executed
    */
   public static function getScriptPath()
   {
      return realpath($_SERVER['argv'][0]);
   }

   /**
    * Fork a system call
    *
    * @param string $command The command to fork
    * @param string $output A file path to redirect output to
    * @return void
    */
   public static function fork($command, $output = '/dev/null')
   {
      pclose(popen($command.' > '.escapeshellarg($output).' &', 'r'));
   }

   /**
    * Get input from the user
    * 
    * @param string $message The message to display
    * @param boolean $silent Hide characters as the user types
    * @return string
    */
   public static function prompt($message, $silent = false)
   {
      if ($silent) {
         $message = addslashes($message);
         $cmd = "bash -c 'read -s -p \"".$message."\" val && echo \$val'";
         $value = rtrim(shell_exec($cmd));
         echo PHP_EOL;
      }
      else {
         $value = readline($message);
      }
      return $value;
   }

   /**
    * Determine the user that owns the current process
    *
    * @return string the name of the current user
    */
   public static function getUser()
   {
      return trim(shell_exec('whoami'));
   }

   /**
    * Get the number of columns in the terminal
    *
    * @return integer
    */
   public static function getWidth()
   {
      return intval(shell_exec('tput cols'));
   }

   /**
    * Get the number of lines in the terminal
    *
    * @return integer
    */
   public static function getHeight()
   {
      return intval(shell_exec('tput lines'));
   }
}
