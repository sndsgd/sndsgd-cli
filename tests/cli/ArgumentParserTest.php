<?php

namespace sndsgd\cli;

use \ReflectionClass;
use \sndsgd\Field;
use \sndsgd\field\Collection;


/**
 * @coversDefaultClass \sndsgd\cli\ArgumentParser
 */
class ArgumentParserTest extends \PHPUnit_Framework_TestCase
{
   /**
    * @coversNothing
    */
   public function setUp()
   {
      $this->fc = new Collection;
      $this->fc->addFields(
         Field::boolean('bool1'),
         Field::boolean('bool2'),
         Field::string('string1'),
         Field::string('string2'),
         Field::integer('integer1'),
         Field::integer('integer2'),
         Field::float('float1'),
         Field::float('float2')
      );
   }

   /**
    * @coversNothing
    */
   private function getAllValues($values)
   {
      return array_merge([
         'bool1' => null,
         'bool2' => null,
         'string1' => null,
         'string2' => null,
         'integer1' => null,
         'integer2' => null,
         'float1' => null,
         'float2' => null,
      ], $values);
   }


   // NOTE: quotes are removed by bash
   // ❯ php print-argv.php --test="1 2 3 4"
   // Array
   // (
   //     [0] => bin/dev.php
   //     [1] => --test=1 2 3 4
   // )


   /**
    * @covers ::__construct
    * @covers ::parseInto
    * @covers ::processNamedArgument
    */
   public function testOne()
   {
      $args = [
         '-bool1',
         '--string1', 'one',
         '--string2=note the spaces',
         '-bool2',
      ];

      $parser = new ArgumentParser($args);
      $parser->parseInto($this->fc);

      $expect = $this->getAllValues([
         'bool1' => true,
         'bool2' => true,
         'string1' => 'one',
         'string2' => 'note the spaces',
      ]);

      $this->assertEquals($expect, $this->fc->exportValues());
   }

   /**
    * @covers ::parseInto
    * @expectedException UnexpectedValueException
    * @expectedExceptionMessage unexpected argument 'bad-value'
    */
   public function testUnexpectedValueException()
   {
      $parser = new ArgumentParser(['-string1', 'test', 'bad-value']);
      $parser->parseInto($this->fc);
   }

   /**
    * @covers ::processNamedArgument
    * @expectedException \sndsgd\field\UnknownFieldException
    */
   public function testUnknownFieldException()
   {
      $parser = new ArgumentParser(['--not-a-defined-field']);
      $parser->parseInto($this->fc);
   }
}

