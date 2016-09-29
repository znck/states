<?php

namespace Znck\Tests\States;

use Illuminate\Filesystem\Filesystem;
use Znck\States\FileLoader;
use Znck\States\Translator;

class TranslatorTest extends \PHPUnit_Framework_TestCase
{
    public function test_it_works()
    {
        $translator = new Translator(new FileLoader(new Filesystem(), dirname(__DIR__).'/data'), 'en');

        $this->assertEquals('Assam', $translator->getName('in.as'));
    }

    public function test_it_works_too()
    {
        $translator = new Translator(new FileLoader(new Filesystem(), dirname(__DIR__).'/data'), 'en');

        $this->assertEquals('Assam', $translator->get('in.as'));
        $this->assertEquals('Assam', $translator->get('in as'));
        $this->assertEquals('Assam', $translator->get('IN AS'));

        $this->assertEquals('in.lk', $translator->get('in.lk'));
    }
}
