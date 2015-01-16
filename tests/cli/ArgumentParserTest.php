<?php

namespace sndsgd\cli;

use \ReflectionClass;
use \sndsgd\Field;
use \sndsgd\field\Collection;
use \sndsgd\field\BooleanField;
use \sndsgd\field\StringField;
use \sndsgd\field\IntegerField;
use \sndsgd\field\FloatField;


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
      $this->fc->addFields([
         new BooleanField('bool1'),
         new BooleanField('bool2'),
         new StringField('string1'),
         new StringField('string2'),
         new IntegerField('integer1'),
         new IntegerField('integer2'),
         new FloatField('float1'),
         new FloatField('float2')
      ]);
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

