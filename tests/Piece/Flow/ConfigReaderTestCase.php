<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * PHP versions 4 and 5
 *
 * Copyright (c) 2006-2007 KUBO Atsuhiro <iteman@users.sourceforge.net>,
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
 * @copyright  2006-2007 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    SVN: $Id$
 * @since      File available since Release 0.1.0
 */

require_once 'PHPUnit.php';
require_once 'Piece/Flow/ConfigReader.php';
require_once 'Piece/Flow/Error.php';

// {{{ Piece_Flow_ConfigReaderTestCase

/**
 * TestCase for Piece_Flow_ConfigReader
 *
 * @package    Piece_Flow
 * @copyright  2006-2007 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @since      Class available since Release 0.1.0
 */
class Piece_Flow_ConfigReaderTestCase extends PHPUnit_TestCase
{

    // {{{ properties

    /**#@+
     * @access public
     */

    /**#@-*/

    /**#@+
     * @access private
     */

    var $_cacheDirectory;

    /**#@-*/

    /**#@+
     * @access public
     */

    function setUp()
    {
        Piece_Flow_Error::pushCallback(create_function('$error', 'var_dump($error); return ' . PEAR_ERRORSTACK_DIE . ';'));
        $this->_cacheDirectory = dirname(__FILE__) . '/' . basename(__FILE__, '.php');
    }

    function tearDown()
    {
        Piece_Flow_Error::clearErrors();
        Piece_Flow_Error::popCallback();
    }

    function testGuessingFromFileExtension()
    {
        $this->assertEquals(strtolower('Piece_Flow_Config'), strtolower(get_class(Piece_Flow_ConfigReader::read("{$this->_cacheDirectory}/foo.yaml", null, null))));
        $this->assertEquals(strtolower('Piece_Flow_Config'), strtolower(get_class(Piece_Flow_ConfigReader::read("{$this->_cacheDirectory}/foo.xml", null, null))));
    }

    function testSpecifyingDriverType()
    {
        $this->assertEquals(strtolower('Piece_Flow_Config'), strtolower(get_class(Piece_Flow_ConfigReader::read("{$this->_cacheDirectory}/foo.yaml", 'YAML', null))));
        $this->assertEquals(strtolower('Piece_Flow_Config'), strtolower(get_class(Piece_Flow_ConfigReader::read("{$this->_cacheDirectory}/foo.xml", 'XML', null))));
    }

    function testNonExistingDriver()
    {
        Piece_Flow_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));
        @Piece_Flow_ConfigReader::read("{$this->_cacheDirectory}/foo.bar", null, null);

        $this->assertTrue(Piece_Flow_Error::hasErrors('exception'));

        $error = Piece_Flow_Error::pop();

        $this->assertEquals(PIECE_FLOW_ERROR_CANNOT_READ, $error['code']);

        Piece_Flow_Error::popCallback();
    }

    function testInvalidDriver()
    {
        Piece_Flow_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));
        $oldIncludePath = set_include_path(dirname(__FILE__) . '/' . basename(__FILE__, '.php'));
        Piece_Flow_ConfigReader::read('foo.bar', 'Baz', null);

        $this->assertTrue(Piece_Flow_Error::hasErrors('exception'));

        $error = Piece_Flow_Error::pop();

        $this->assertEquals(PIECE_FLOW_ERROR_NOT_FOUND, $error['code']);

        set_include_path($oldIncludePath);
        Piece_Flow_Error::popCallback();
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
