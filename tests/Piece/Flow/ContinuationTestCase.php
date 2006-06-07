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
 * @see        Piece_Flow_Continuation
 * @since      File available since Release 0.1.0
 */

require_once 'Piece/Flow/Continuation.php';

require_once dirname(__FILE__) . '/Counter.php';
require_once 'Cache/Lite/File.php';

// {{{ Piece_Flow_ContinuationTestCase

/**
 * TestCase for Piece_Flow_Continuation
 *
 * @package    Piece_Flow
 * @author     KUBO Atsuhiro <iteman2002@yahoo.co.jp>
 * @copyright  2006 KUBO Atsuhiro <iteman2002@yahoo.co.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @link       http://iteman.typepad.jp/piece/
 * @see        Piece_Flow_Continuation
 * @since      Class available since Release 0.1.0
 */
class Piece_Flow_ContinuationTestCase extends PHPUnit_TestCase
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
        $cache = &new Cache_Lite_File(array('cacheDir' => dirname(__FILE__) . '/',
                                            'masterFile' => dirname(__FILE__) . '/counter.yaml',
                                            'automaticSerialization' => true,
                                            'errorHandlingAPIBreak' => true)
                                      );
        $cache->clean();
        PEAR_ErrorStack::staticPopCallback();
    }

    function testAddingFlowWithLinearFlowControl()
    {
        $continuation = &new Piece_Flow_Continuation(null, true);
        $continuation->addFlow('foo', '/path/to/foo.xml');

        $this->assertTrue($continuation->hasFlow('foo'));
        $this->assertFalse($continuation->hasFlow('bar'));
    }

    function testFailureToAddFlowForSecondTimeWithLinearFlowControl()
    {
        PEAR_ErrorStack::staticPushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));

        $continuation = &new Piece_Flow_Continuation(null, true);
        $continuation->addFlow('foo', '/path/to/foo.xml');

        $this->assertTrue($continuation->hasFlow('foo'));

        $continuation->addFlow('bar', '/path/to/bar.xml');

        $this->assertTrue(PEAR_ErrorStack::staticHasErrors());

        $stack = &Piece_Flow_Error::getErrorStack();

        $this->assertTrue($stack->hasErrors());

        $error = $stack->pop();

        $this->assertEquals(PIECE_FLOW_ERROR_ALREADY_EXISTS, $error['code']);

        PEAR_ErrorStack::staticPopCallback();
    }

    function testSettingFlowWithoutLinearFlowControl()
    {
        $continuation = &new Piece_Flow_Continuation();
        $continuation->addFlow('foo', '/path/to/foo.xml');
        $continuation->addFlow('bar', '/path/to/bar.xml');

        $this->assertTrue($continuation->hasFlow('foo'));
        $this->assertTrue($continuation->hasFlow('bar'));
        $this->assertFalse($continuation->hasFlow('baz'));
    }

    function testFirstTimeInvocationWithLinearFlowControl()
    {
        $continuation = &new Piece_Flow_Continuation(dirname(__FILE__), true);
        $continuation->addFlow('counter', dirname(__FILE__) . '/counter.yaml');

        $flowExecutionTicket = $continuation->invoke();

        $this->assertRegexp('/[0-9a-f]{40}/', $flowExecutionTicket);
        $this->assertEquals('counter', $continuation->getView());
        $this->assertEquals(0, $GLOBALS['counter']);
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
