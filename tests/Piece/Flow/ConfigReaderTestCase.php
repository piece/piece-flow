<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * PHP versions 4 and 5
 *
 * Copyright (c) 2006-2008 KUBO Atsuhiro <kubo@iteman.jp>,
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
 * @copyright  2006-2008 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version    Release: @package_version@
 * @since      File available since Release 0.1.0
 */

require_once realpath(dirname(__FILE__) . '/../../prepare.php');
require_once 'PHPUnit.php';
require_once 'Piece/Flow/ConfigReader.php';
require_once 'Piece/Flow/Error.php';

// {{{ Piece_Flow_ConfigReaderTestCase

/**
 * Some tests for Piece_Flow_ConfigReader.
 *
 * @package    Piece_Flow
 * @copyright  2006-2008 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
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
        $this->_cacheDirectory = dirname(__FILE__) . '/' . basename(__FILE__, '.php');
    }

    function tearDown()
    {
        $cache = &new Cache_Lite_File(array('cacheDir' => "{$this->_cacheDirectory}/",
                                            'masterFile' => '',
                                            'automaticSerialization' => true,
                                            'errorHandlingAPIBreak' => true)
                                      );
        $cache->clean();
        Piece_Flow_Error::clearErrors();
    }

    function testGuessingFromFileExtension()
    {
        $this->assertEquals(strtolower('Piece_Flow_Config'), strtolower(get_class(Piece_Flow_ConfigReader::read("{$this->_cacheDirectory}/foo.yaml", null, null, null, null))));
        $this->assertEquals(strtolower('Piece_Flow_Config'), strtolower(get_class(Piece_Flow_ConfigReader::read("{$this->_cacheDirectory}/foo.xml", null, null, null, null))));
    }

    function testSpecifyingDriverType()
    {
        $this->assertEquals(strtolower('Piece_Flow_Config'), strtolower(get_class(Piece_Flow_ConfigReader::read("{$this->_cacheDirectory}/foo.yaml", 'YAML', null, null, null))));
        $this->assertEquals(strtolower('Piece_Flow_Config'), strtolower(get_class(Piece_Flow_ConfigReader::read("{$this->_cacheDirectory}/foo.xml", 'XML', null, null, null))));
    }

    function testInvalidDriver()
    {
        $oldIncludePath = set_include_path(dirname(__FILE__) . '/' . basename(__FILE__, '.php'));
        Piece_Flow_Error::disableCallback();
        Piece_Flow_ConfigReader::read('foo.bar', 'Baz', null, null, null);
        Piece_Flow_Error::enableCallback();

        $this->assertTrue(Piece_Flow_Error::hasErrors());

        $error = Piece_Flow_Error::pop();

        $this->assertEquals(PIECE_FLOW_ERROR_NOT_FOUND, $error['code']);

        set_include_path($oldIncludePath);
    }

    /**
     * @since Method available since Release 1.13.0
     */
    function testConfigurationFileWithoutExtensionShouldBeReadAsYAML()
    {
        Piece_Flow_Error::disableCallback();
        Piece_Flow_ConfigReader::read("{$this->_cacheDirectory}/foo.flow", null, $this->_cacheDirectory, null, null);
        Piece_Flow_Error::enableCallback();

        $this->assertFalse(Piece_Flow_Error::hasErrors());
    }

    /**
     * @since Method available since Release 1.13.0
     */
    function testNotFoundExceptionShouldBeRaisedWhenNonExistingConfigurationFileIsSpecified()
    {
        Piece_Flow_Error::disableCallback();
        Piece_Flow_ConfigReader::read("{$this->_cacheDirectory}/foo.bar", null, $this->_cacheDirectory, null, null);
        Piece_Flow_Error::enableCallback();

        $this->assertTrue(Piece_Flow_Error::hasErrors());

        $error = Piece_Flow_Error::pop();

        $this->assertEquals(PIECE_FLOW_ERROR_NOT_FOUND, $error['code']);
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
