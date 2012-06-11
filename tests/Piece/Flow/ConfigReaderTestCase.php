<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * PHP version 5.3
 *
 * Copyright (c) 2006-2008, 2012 KUBO Atsuhiro <kubo@iteman.jp>,
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
 * @copyright  2006-2008, 2012 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version    Release: @package_version@
 * @since      File available since Release 0.1.0
 */

namespace Piece\Flow;

require_once 'Cache/Lite/File.php';

use Piece\Flow\Util\ErrorReporting;

/**
 * @package    Piece_Flow
 * @copyright  2006-2008, 2012 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version    Release: @package_version@
 * @since      Class available since Release 0.1.0
 */
class ConfigReaderTestCase extends \PHPUnit_Framework_TestCase
{
    protected $cacheDirectory;

    protected function setUp()
    {
        $this->cacheDirectory = dirname(__FILE__) . '/' . basename(__FILE__, '.php');
    }

    protected function tearDown()
    {
        $cacheDirectory = $this->cacheDirectory;
        $cache = ErrorReporting::invokeWith(error_reporting() & ~E_STRICT, function () use ($cacheDirectory) {
            return new \Cache_Lite_File(array(
                'cacheDir' => $cacheDirectory . '/',
                'masterFile' => '',
                'automaticSerialization' => true,
                'errorHandlingAPIBreak' => true
            ));
        });
        $cache->clean();
    }

    public function testGuessingFromFileExtension()
    {
        $this->assertTrue(ConfigReader::read("{$this->cacheDirectory}/foo.yaml", null, null, null, null) instanceof Config);
        $this->assertTrue(ConfigReader::read("{$this->cacheDirectory}/foo.xml", null, null, null, null) instanceof Config);
    }

    public function testSpecifyingDriverType()
    {
        $this->assertTrue(ConfigReader::read("{$this->cacheDirectory}/foo.yaml", 'YAML', null, null, null) instanceof Config);
        $this->assertTrue(ConfigReader::read("{$this->cacheDirectory}/foo.xml", 'XML', null, null, null) instanceof Config);
    }

    /**
     * @since Method available since Release 1.13.0
     */
    public function testConfigurationFileWithoutExtensionShouldBeReadAsYAML()
    {
        ConfigReader::read("{$this->cacheDirectory}/foo.flow", null, $this->cacheDirectory, null, null);
    }

    /**
     * @expectedException \Piece\Flow\FileNotFoundException
     * @since Method available since Release 1.13.0
     */
    public function testNotFoundExceptionShouldBeRaisedWhenNonExistingConfigurationFileIsSpecified()
    {
        ConfigReader::read("{$this->cacheDirectory}/foo.bar", null, $this->cacheDirectory, null, null);
    }
}

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
