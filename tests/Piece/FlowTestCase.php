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
 * @author     MIYAI Fumihiko <fumichz@yahoo.co.jp>
 * @copyright  2006 KUBO Atsuhiro <iteman2002@yahoo.co.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    SVN: $Id$
 * @link       http://iteman.typepad.jp/piece/
 * @see        Piece_Flow
 * @since      File available since Release 0.1.0
 */

require_once 'Piece/Flow.php';

// {{{ Piece_FlowTestCase

/**
 * TestCase for Piece_Flow
 *
 * @package    Piece_Flow
 * @author     KUBO Atsuhiro <iteman2002@yahoo.co.jp>
 * @author     MIYAI Fumihiko <fumichz@yahoo.co.jp>
 * @copyright  2006 KUBO Atsuhiro <iteman2002@yahoo.co.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @link       http://iteman.typepad.jp/piece/
 * @see        Piece_Flow
 * @since      Class available since Release 0.1.0
 */
class Piece_FlowTestCase extends PHPUnit_TestCase
{

    // {{{ properties

    /**#@+
     * @access public
     */

    /**#@-*/

    /**#@+
     * @access private
     */

    var $_source;
    var $_config;

    /**#@-*/

    /**#@+
     * @access public
     */

    function setUp()
    {
        PEAR_ErrorStack::staticPushCallback(create_function('$error', 'var_dump($error); return ' . PEAR_ERRORSTACK_DIE . ';'));
        $this->_source =
            dirname(__FILE__) . '/../../data/registrationFlow.yaml';
        $driver = &Piece_Flow_ConfigReader_Factory::factory($this->_source);
        $this->_config = &$driver->configure();
        Piece_Flow_Action_Factory::setActionPath(dirname(__FILE__) . '/..');
    }

    function tearDown()
    {
        $GLOBALS['PIECE_FLOW_Action_Instances'] = array();
        $GLOBALS['PIECE_FLOW_Action_Path'] = null;
        $cache = &new Cache_Lite_File(array('cacheDir' => dirname(__FILE__) . '/',
                                            'masterFile' => '',
                                            'automaticSerialization' => true,
                                            'errorHandlingAPIBreak' => true)
                                      );
        $cache->clean();
        $this->_source = null;
        $this->_config = null;
        $stack = &Piece_Flow_Error::getErrorStack();
        $stack->getErrors(true);
        PEAR_ErrorStack::staticPopCallback();
     }

    function testConfiguration()
    {
        $flow = &new Piece_Flow();
        $flow->configure($this->_source, null, dirname(__FILE__));

        $this->assertEquals($this->_config->getName(), $flow->getName());
    }

    function testGettingView()
    {
        $viewStates = $this->_config->getViewStates();
        $flow = &new Piece_Flow();
        $flow->configure($this->_source, null, dirname(__FILE__));
        $flow->start();

        $this->assertEquals($viewStates['displaying']['view'],
                            $flow->getView()
                            );
    }

    function testInvokingCallback()
    {
        $GLOBALS['validateInputCalled'] = false;
        $viewStates = $this->_config->getViewStates();
        $flow = &new Piece_Flow();
        $flow->configure($this->_source, null, dirname(__FILE__));
        $flow->start();
        $flow->triggerEvent('submit');

        $this->assertTrue($GLOBALS['validateInputCalled']);
        $this->assertEquals($viewStates['confirming']['view'],
                            $flow->getView()
                            );

        unset($GLOBALS['validateInputCalled']);
    }

    function testGettingPreviousStateName()
    {
        $flow = &new Piece_Flow();
        $flow->configure($this->_source, null, dirname(__FILE__));
        $flow->start();
        $flow->triggerEvent('submit');

        $this->assertEquals('inputValidated', $flow->getPreviousStateName());
    }

    function testGettingCurrentStateName()
    {
        $flow = &new Piece_Flow();
        $flow->configure($this->_source, null, dirname(__FILE__));
        $flow->start();
        $flow->triggerEvent('submit');

        $this->assertEquals('confirming', $flow->getCurrentStateName());
    }

    function testTriggeringEventAndInvokingTransitionAction()
    {
        $GLOBALS['validateInputCalled'] = false;
        $GLOBALS['validateConfirmationCalled'] = false;
        $viewStates = $this->_config->getViewStates();
        $flow = &new Piece_Flow();
        $flow->configure($this->_source, null, dirname(__FILE__));
        $flow->start();
        $flow->triggerEvent('submit');

        $this->assertTrue($GLOBALS['validateInputCalled']);
        $this->assertEquals($viewStates['confirming']['view'],
                            $flow->getView()
                            );

        $flow->triggerEvent('submit');

        $this->assertTrue($GLOBALS['validateConfirmationCalled']);

        $lastState = $this->_config->getLastState();

        $this->assertEquals($lastState['view'], $flow->getView());

        unset($GLOBALS['validateInputCalled']);
        unset($GLOBALS['validateConfirmationCalled']);
    }

    function testTriggeringRaiseErrorEvent()
    {
        $GLOBALS['hasErrors'] = true;
        $viewStates = $this->_config->getViewStates();
        $flow = &new Piece_Flow();
        $flow->configure($this->_source, null, dirname(__FILE__));
        $flow->start();
        $flow->triggerEvent('submit');

        $this->assertEquals($viewStates['displaying']['view'],
                            $flow->getView()
                            );

        unset($GLOBALS['hasErrors']);
    }

    function testActivity()
    {
        $GLOBALS['displayCounter'] = 0;
        $flow = &new Piece_Flow();
        $flow->configure($this->_source, null, dirname(__FILE__));
        $flow->start();

        $this->assertEquals(1, $GLOBALS['displayCounter']);

        $flow->triggerEvent('foo');
        $flow->triggerEvent('bar');

        $this->assertEquals(3, $GLOBALS['displayCounter']);

        unset($GLOBALS['displayCounter']);
    }

    function testExitAndEntryActions()
    {
        $GLOBALS['setupFormCalled'] = false;
        $GLOBALS['teardownFormCalled'] = false;
        $flow = &new Piece_Flow();
        $flow->configure($this->_source, null, dirname(__FILE__));
        $flow->start();

        $this->assertTrue($GLOBALS['setupFormCalled']);
        $this->assertFalse($GLOBALS['teardownFormCalled']);

        $flow->triggerEvent('submit');

        $this->assertTrue($GLOBALS['teardownFormCalled']);
    }

    function testSettingAttribute()
    {
        $flow = &new Piece_Flow();
        $flow->configure($this->_source, null, dirname(__FILE__));
        $flow->start();
        $flow->setAttribute('foo', 'bar');

        $this->assertTrue($flow->hasAttribute('foo'));
        $this->assertEquals('bar', $flow->getAttribute('foo'));
    }

    function testFailureToSetAttributeBeforeStartingFlow()
    {
        $flow = &new Piece_Flow();
        $flow->configure($this->_source, null, dirname(__FILE__));
        $flow->setAttribute('foo', 'bar');

        $this->assertTrue(PEAR_ErrorStack::staticHasErrors());

        $stack = &Piece_Flow_Error::getErrorStack();
        $error = $stack->pop();

        $this->assertEquals(PIECE_FLOW_ERROR_INVALID_OPERATION, $error['code']);
    }

    function testSettingPayload()
    {
        $flow = &new Piece_Flow();
        $flow->configure($this->_source, null, dirname(__FILE__));
        $flow->setPayload(new stdClass());

        $stack = &Piece_Flow_Error::getErrorStack();
        $error = $stack->pop();

        $this->assertEquals(PIECE_FLOW_ERROR_NOT_FOUND, $error['code']);
        $this->assertFalse(PEAR_ErrorStack::staticHasErrors());
    }

    function testFailureToSetPayloadBeforeConfiguringFlow()
    {
        $flow = &new Piece_Flow();
        $flow->setPayload(new stdClass());

        $this->assertTrue(PEAR_ErrorStack::staticHasErrors());

        $stack = &Piece_Flow_Error::getErrorStack();
        $error = $stack->pop();

        $this->assertEquals(PIECE_FLOW_ERROR_INVALID_OPERATION, $error['code']);
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
