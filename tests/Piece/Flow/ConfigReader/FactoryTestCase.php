<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * PHP versions 4 and 5
 *
 * Copyright (c) 2006, KUBO Atsuhiro <iteman2002@yahoo.co.jp>
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @package    Piece_Flow
 * @author     KUBO Atsuhiro <iteman2002@yahoo.co.jp>
 * @copyright  2006 KUBO Atsuhiro <iteman2002@yahoo.co.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    SVN: $Id$
 * @link       http://iteman.typepad.jp/piece/
 * @see        Piece_Flow_ConfigReader_Factory
 * @since      File available since Release 0.1.0
 */

require_once 'Piece/Flow/ConfigReader/Factory.php';

require_once 'PHPUnit.php';

// {{{ Piece_Flow_ConfigReader_FactoryTestCase

/**
 * TestCase for Piece_Flow_ConfigReader_Factory
 *
 * @package    Piece_Flow
 * @author     KUBO Atsuhiro <iteman2002@yahoo.co.jp>
 * @copyright  2006 KUBO Atsuhiro <iteman2002@yahoo.co.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @link       http://iteman.typepad.jp/piece/
 * @see        Piece_Flow_ConfigReader_Factory
 * @since      Class available since Release 0.1.0
 */
class Piece_Flow_ConfigReader_FactoryTestCase extends PHPUnit_TestCase
{

    // {{{ properties

    /**#@+
     * @access public
     */

    /**#@-*/

    /**#@+
     * @access private
     */

    /**#@-*/

    /**#@+
     * @access public
     */

    function setUp()
    {
        PEAR_ErrorStack::staticPushCallback(create_function('$error', 'var_dump($error); return ' . PEAR_ERRORSTACK_DIE . ';'));
    }

    function tearDown()
    {
        $stack = &Piece_Flow_Error::getErrorStack();
        $stack->getErrors(true);
        PEAR_ErrorStack::staticPopCallback();
    }

    function testGuessingFromFileExtension()
    {
        $this->assertTrue(is_a(Piece_Flow_ConfigReader_Factory::factory('foo.yaml'),
                               'Piece_Flow_ConfigReader_YAML')
                          );
        $this->assertTrue(is_a(Piece_Flow_ConfigReader_Factory::factory('foo.xml'),
                               version_compare(phpversion(), '5.0.0', '>=') ?
                               'Piece_Flow_ConfigReader_XML5' :
                               'Piece_Flow_ConfigReader_XML4')
                          );
    }

    function testSpecifyingDriverType()
    {
        $this->assertTrue(is_a(Piece_Flow_ConfigReader_Factory::factory('foo', 'YAML'),
                               'Piece_Flow_ConfigReader_YAML')
                          );
        $this->assertTrue(is_a(Piece_Flow_ConfigReader_Factory::factory('foo', 'XML'),
                               version_compare(phpversion(), '5.0.0', '>=') ?
                               'Piece_Flow_ConfigReader_XML5' :
                               'Piece_Flow_ConfigReader_XML4')
                          );
    }

    function testNonExistentDriver()
    {
        PEAR_ErrorStack::staticPushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));

        Piece_Flow_ConfigReader_Factory::factory('foo.bar');
        $stack = &Piece_Flow_Error::getErrorStack();

        $this->assertTrue($stack->hasErrors());

        $error = $stack->pop();

        $this->assertEquals(PIECE_FLOW_ERROR_NOT_FOUND, $error['code']);

        PEAR_ErrorStack::staticPopCallback();
    }

    function testInvalidDriver()
    {
        PEAR_ErrorStack::staticPushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));

        $includePath = ini_get('include_path');
        ini_set('include_path',
                dirname(__FILE__) .
                DIRECTORY_SEPARATOR . '..' .
                DIRECTORY_SEPARATOR . '..' .
                DIRECTORY_SEPARATOR . '..' .
                PATH_SEPARATOR . $includePath
                );


        Piece_Flow_ConfigReader_Factory::factory('foo.bar', 'Baz');
        $stack = &Piece_Flow_Error::getErrorStack();

        $this->assertTrue($stack->hasErrors());

        $error = $stack->pop();

        $this->assertEquals(PIECE_FLOW_ERROR_INVALID_DRIVER, $error['code']);

        ini_set('include_path', $includePath);
        PEAR_ErrorStack::staticPopCallback();
    }

    /**#@-*/

    /**#@+
     * @access private
     */

    /**#@-*/

    // }}}
}

// }}}

/*
 * Local Variables:
 * mode: php
 * coding: iso-8859-1
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * indent-tabs-mode: nil
 * End:
 */
?>
