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
 * @since      File available since Release 1.0.0
 */

require_once 'PHPUnit.php';
require_once 'Piece/Flow/Continuation.php';
require_once 'Piece/Flow/Error.php';
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
 * @since      Class available since Release 1.0.0
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

    var $_flowExecutionTicket;
    var $_flowName;
    var $_eventName;

    /**#@-*/

    /**#@+
     * @access public
     */

    function setUp()
    {
        Piece_Flow_Error::pushCallback(create_function('$error', 'var_dump($error); return ' . PEAR_ERRORSTACK_DIE . ';'));
        Piece_Flow_Continuation::setActionDirectory(dirname(__FILE__) . '/../..');
        $this->_flowName = 'Counter';
        $this->_eventName = 'increase';
    }

    function tearDown()
    {
        $GLOBALS['PIECE_FLOW_Action_Instances'] = array();
        $GLOBALS['PIECE_FLOW_Action_Directory'] = null;
        $this->_eventName = null;
        $this->_flowName = null;
        $this->_flowExecutionTicket = null;
        $cache = &new Cache_Lite_File(array('cacheDir' => dirname(__FILE__) . '/',
                                            'masterFile' => '',
                                            'automaticSerialization' => true,
                                            'errorHandlingAPIBreak' => true)
                                      );
        $cache->clean();
        Piece_Flow_Error::clearErrors();
        Piece_Flow_Error::popCallback();
    }

    function getFlowExecutionTicket()
    {
        return $this->_flowExecutionTicket;
    }

    function getFlowName()
    {
        return $this->_flowName;
    }

    function getEventName()
    {
        return $this->_eventName;
    }

    function testAddingFlowInSingleFlowMode()
    {
        Piece_Flow_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));

        $continuation = &new Piece_Flow_Continuation(true);
        $continuation->setCacheDirectory(dirname(__FILE__));
        $continuation->addFlow('foo', '/path/to/foo.xml');

        $this->assertFalse(Piece_Flow_Error::hasErrors('exception'));

        Piece_Flow_Error::popCallback();
    }

    function testFailureToAddFlowForSecondTimeInSingleFlowMode()
    {
        Piece_Flow_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));

        $continuation = &new Piece_Flow_Continuation(true);
        $continuation->setCacheDirectory(dirname(__FILE__));
        $continuation->addFlow('foo', '/path/to/foo.xml');
        $continuation->addFlow('bar', '/path/to/bar.xml');

        $this->assertTrue(Piece_Flow_Error::hasErrors('exception'));

        $error = Piece_Flow_Error::pop();

        $this->assertEquals(PIECE_FLOW_ERROR_ALREADY_EXISTS, $error['code']);

        Piece_Flow_Error::popCallback();
    }

    function testSettingFlowInMultipleFlowMode()
    {
        Piece_Flow_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));

        $continuation = &new Piece_Flow_Continuation();
        $continuation->setCacheDirectory(dirname(__FILE__));
        $continuation->addFlow('foo', '/path/to/foo.xml');
        $continuation->addFlow('bar', '/path/to/bar.xml');

        $this->assertFalse(Piece_Flow_Error::hasErrors('exception'));

        Piece_Flow_Error::popCallback();
    }

    function testFirstTimeInvocationInSingleFlowMode()
    {
        $continuation = &new Piece_Flow_Continuation(true);
        $continuation->setCacheDirectory(dirname(__FILE__));
        $continuation->addFlow('Counter', dirname(__FILE__) . '/Counter.yaml');

        $flowExecutionTicket = $continuation->invoke(new stdClass());

        $this->assertRegexp('/[0-9a-f]{40}/', $flowExecutionTicket);
        $this->assertEquals('Counter', $continuation->getView());

        $counter = &Piece_Flow_Action_Factory::factory('Piece_Flow_CounterAction');

        $this->assertEquals(0, $continuation->getAttribute('counter'));
    }

    function testSecondTimeInvocationInSingleFlowMode()
    {
        $continuation = &new Piece_Flow_Continuation(true);
        $continuation->setCacheDirectory(dirname(__FILE__));
        $continuation->addFlow('Counter', dirname(__FILE__) . '/Counter.yaml');
        $continuation->setEventNameCallback(array(&$this, 'getEventName'));

        $flowExecutionTicket1 = $continuation->invoke(new stdClass());
        $flowExecutionTicket2 = $continuation->invoke(new stdClass());

        $this->assertRegexp('/[0-9a-f]{40}/', $flowExecutionTicket1);
        $this->assertEquals('Counter', $continuation->getView());

        $counter = &Piece_Flow_Action_Factory::factory('Piece_Flow_CounterAction');

        $this->assertEquals(1, $continuation->getAttribute('counter'));
        $this->assertEquals($flowExecutionTicket1, $flowExecutionTicket2);
    }

    function testInvocationInMultipleFlowModeAndFlowInNonExclusiveMode()
    {
        $continuation = &new Piece_Flow_Continuation();
        $continuation->setCacheDirectory(dirname(__FILE__));
        $continuation->addFlow('Counter', dirname(__FILE__) . '/Counter.yaml');
        $continuation->setEventNameCallback(array(&$this, 'getEventName'));
        $continuation->setFlowExecutionTicketCallback(array(&$this, 'getFlowExecutionTicket'));
        $continuation->setFlowNameCallback(array(&$this, 'getFlowName'));

        $this->_flowExecutionTicket = $continuation->invoke(new stdClass());
        $flowExecutionTicket = $continuation->invoke(new stdClass());

        $this->assertRegexp('/[0-9a-f]{40}/', $this->_flowExecutionTicket);
        $this->assertEquals('Counter', $continuation->getView());

        $counter = &Piece_Flow_Action_Factory::factory('Piece_Flow_CounterAction');

        $this->assertEquals(1, $continuation->getAttribute('counter'));
        $this->assertEquals($this->_flowExecutionTicket, $flowExecutionTicket);
    }

    function testMultipleInvocationInMultipleFlowModeAndFlowInNonExclusiveMode()
    {
        $continuation = &new Piece_Flow_Continuation();
        $continuation->setCacheDirectory(dirname(__FILE__));
        $continuation->addFlow('Counter', dirname(__FILE__) . '/Counter.yaml');
        $continuation->addFlow('SecondCounter', dirname(__FILE__) . '/SecondCounter.yaml');
        $continuation->setEventNameCallback(array(&$this, 'getEventName'));
        $continuation->setFlowExecutionTicketCallback(array(&$this, 'getFlowExecutionTicket'));
        $continuation->setFlowNameCallback(array(&$this, 'getFlowName'));

        /*
         * Starting a new 'Counter'.
         */
        $flowExecutionTicket1 = $continuation->invoke(new stdClass());

        $this->assertEquals(0, $continuation->getAttribute('counter'));

        /*
         * Starting a new 'SecondCounter'.
         */
        $this->_flowName = 'SecondCounter';
        $flowExecutionTicket2 = $continuation->invoke(new stdClass());

        $this->assertEquals(0, $continuation->getAttribute('counter'));
        $this->assertRegexp('/[0-9a-f]{40}/', $flowExecutionTicket1);
        $this->assertRegexp('/[0-9a-f]{40}/', $flowExecutionTicket2);
        $this->assertEquals('SecondCounter', $continuation->getView());
        $this->assertTrue($flowExecutionTicket1 != $flowExecutionTicket2);

        /*
         * Continuing the first 'Counter'.
         */
        $this->_flowExecutionTicket = $flowExecutionTicket1;
        $flowExecutionTicket3 = $continuation->invoke(new stdClass());

        $this->assertEquals(1, $continuation->getAttribute('counter'));

        $this->assertEquals('Counter', $continuation->getView());
        $this->assertEquals($flowExecutionTicket1, $flowExecutionTicket3);

        /*
         * Continuing the first 'SecondCounter'.
         */
        $this->_flowExecutionTicket = $flowExecutionTicket2;
        $flowExecutionTicket4 = $continuation->invoke(new stdClass());

        $this->assertEquals('SecondCounter', $continuation->getView());
        $this->assertEquals(1, $continuation->getAttribute('counter'));
        $this->assertEquals($flowExecutionTicket2, $flowExecutionTicket4);

        /*
         * Starting a new 'SecondCounter'.
         */
        $this->_flowExecutionTicket = null;
        $secondCounter->counter = null;
        $this->_flowName = 'SecondCounter';
        $flowExecutionTicket5 = $continuation->invoke(new stdClass());

        $this->assertEquals('SecondCounter', $continuation->getView());
        $this->assertEquals(0, $continuation->getAttribute('counter'));
        $this->assertTrue($flowExecutionTicket2 != $flowExecutionTicket5);
    }

    function testSuccessOfContinuationByInvalidFlowNameInSingleFlowMode()
    {
        $continuation = &new Piece_Flow_Continuation(true);
        $continuation->setCacheDirectory(dirname(__FILE__));
        $continuation->addFlow('Counter', dirname(__FILE__) . '/Counter.yaml');
        $continuation->setEventNameCallback(array(&$this, 'getEventName'));
        $continuation->setFlowExecutionTicketCallback(array(&$this, 'getFlowExecutionTicket'));
        $continuation->setFlowNameCallback(array(&$this, 'getFlowName'));

        $continuation->invoke(new stdClass());
        $this->_flowName = 'InvalidFlowName';
        $continuation->invoke(new stdClass());

        $this->assertFalse(Piece_Flow_Error::hasErrors('exception'));

        $counter = &Piece_Flow_Action_Factory::factory('Piece_Flow_CounterAction');
        $this->assertEquals(1, $continuation->getAttribute('counter'));
    }

    function testFailureOfContinuationByInvalidFlowNameInMultipleFlowMode()
    {
        Piece_Flow_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));

        $continuation = &new Piece_Flow_Continuation();
        $continuation->setCacheDirectory(dirname(__FILE__));
        $continuation->addFlow('Counter', dirname(__FILE__) . '/Counter.yaml');
        $continuation->setEventNameCallback(array(&$this, 'getEventName'));
        $continuation->setFlowExecutionTicketCallback(array(&$this, 'getFlowExecutionTicket'));
        $continuation->setFlowNameCallback(array(&$this, 'getFlowName'));

        $continuation->invoke(new stdClass());
        $this->_flowName = 'InvalidFlowName';
        $continuation->invoke(new stdClass());

        $this->assertTrue(Piece_Flow_Error::hasErrors('exception'));

        $error = Piece_Flow_Error::pop();

        $this->assertEquals(PIECE_FLOW_ERROR_NOT_FOUND, $error['code']);

        Piece_Flow_Error::popCallback();
    }

    function testFailureToInvokeByNonExistingFlowConfiguration()
    {
        Piece_Flow_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));

        $continuation = &new Piece_Flow_Continuation();
        $continuation->setCacheDirectory(dirname(__FILE__));
        $continuation->addFlow('NonExistingFile', dirname(__FILE__) . '/NonExistingFile.yaml');
        $continuation->setEventNameCallback(array(&$this, 'getEventName'));
        $continuation->setFlowExecutionTicketCallback(array(&$this, 'getFlowExecutionTicket'));
        $continuation->setFlowNameCallback(array(&$this, 'getFlowName'));

        $this->_flowName = 'NonExistingFile';
        $continuation->invoke(new stdClass());

        $this->assertTrue(Piece_Flow_Error::hasErrors('exception'));

        $error = Piece_Flow_Error::pop();

        $this->assertEquals(PIECE_FLOW_ERROR_NOT_FOUND, $error['code']);

        Piece_Flow_Error::popCallback();
    }

    function testInvocationInMultipleFlowModeAndFlowInExclusiveMode()
    {
        $continuation = &new Piece_Flow_Continuation();
        $continuation->setCacheDirectory(dirname(__FILE__));
        $continuation->addFlow('Counter', dirname(__FILE__) . '/Counter.yaml', true);
        $continuation->addFlow('SecondCounter', dirname(__FILE__) . '/SecondCounter.yaml');
        $continuation->setEventNameCallback(array(&$this, 'getEventName'));
        $continuation->setFlowExecutionTicketCallback(array(&$this, 'getFlowExecutionTicket'));
        $continuation->setFlowNameCallback(array(&$this, 'getFlowName'));

        $flowExecutionTicket1 = $continuation->invoke(new stdClass());

        $this->assertEquals(0, $continuation->getAttribute('counter'));

        $this->_flowName = 'SecondCounter';
        $flowExecutionTicket3 = $continuation->invoke(new stdClass());

        $this->assertEquals(0, $continuation->getAttribute('counter'));

        $this->_flowName = 'Counter';
        $this->_flowExecutionTicket = null;
        $flowExecutionTicket2 = $continuation->invoke(new stdClass());

        $this->assertEquals(1, $continuation->getAttribute('counter'));

        $this->assertRegexp('/[0-9a-f]{40}/', $flowExecutionTicket1);
        $this->assertRegexp('/[0-9a-f]{40}/', $flowExecutionTicket3);
        $this->assertEquals('Counter', $continuation->getView());
        $this->assertEquals($flowExecutionTicket1, $flowExecutionTicket2);
        $this->assertTrue($flowExecutionTicket1 != $flowExecutionTicket3);
    }

    function testInvocationInSingleFlowModeAndFlowInExclusiveMode()
    {
        $continuation = &new Piece_Flow_Continuation(true);
        $continuation->setCacheDirectory(dirname(__FILE__));
        $continuation->addFlow('Counter', dirname(__FILE__) . '/Counter.yaml', true);
        $continuation->setEventNameCallback(array(&$this, 'getEventName'));
        $continuation->setFlowExecutionTicketCallback(array(&$this, 'getFlowExecutionTicket'));
        $continuation->setFlowNameCallback(array(&$this, 'getFlowName'));

        $flowExecutionTicket1 = $continuation->invoke(new stdClass());
        $this->_flowExecutionTicket = null;
        $flowExecutionTicket2 = $continuation->invoke(new stdClass());

        $this->assertRegexp('/[0-9a-f]{40}/', $flowExecutionTicket1);
        $this->assertEquals('Counter', $continuation->getView());

        $counter = &Piece_Flow_Action_Factory::factory('Piece_Flow_CounterAction');

        $this->assertEquals(1, $continuation->getAttribute('counter'));
        $this->assertEquals($flowExecutionTicket1, $flowExecutionTicket2);
    }

    function testSettingAttribute()
    {
        $continuation = &new Piece_Flow_Continuation(true);
        $continuation->setCacheDirectory(dirname(__FILE__));
        $continuation->addFlow('Counter', dirname(__FILE__) . '/Counter.yaml', true);
        $continuation->setEventNameCallback(array(&$this, 'getEventName'));
        $continuation->setFlowExecutionTicketCallback(array(&$this, 'getFlowExecutionTicket'));
        $continuation->setFlowNameCallback(array(&$this, 'getFlowName'));

        $this->_flowExecutionTicket = $continuation->invoke(new stdClass());
        $continuation->setAttribute('foo', 'bar');
        $continuation->invoke(new stdClass());
        $continuation->setAttribute('bar', 'baz');

        $this->assertTrue($continuation->hasAttribute('foo'));
        $this->assertEquals('bar', $continuation->getAttribute('foo'));
        $this->assertTrue($continuation->hasAttribute('bar'));
        $this->assertEquals('baz', $continuation->getAttribute('bar'));
    }

    function testFailureToSetAttributeBeforeStartingFlow()
    {
        $continuation = &new Piece_Flow_Continuation(true);
        $continuation->setCacheDirectory(dirname(__FILE__));
        $continuation->addFlow('Counter', dirname(__FILE__) . '/Counter.yaml', true);
        $continuation->setEventNameCallback(array(&$this, 'getEventName'));
        $continuation->setFlowExecutionTicketCallback(array(&$this, 'getFlowExecutionTicket'));
        $continuation->setFlowNameCallback(array(&$this, 'getFlowName'));

        $continuation->setAttribute('foo', 'bar');

        $this->assertTrue(Piece_Flow_Error::hasErrors('warning'));

        $error = Piece_Flow_Error::pop();

        $this->assertEquals(PIECE_FLOW_ERROR_INVALID_OPERATION, $error['code']);
    }

    function testFailureToGetAttributeBeforeStartingFlow()
    {
        $continuation = &new Piece_Flow_Continuation(true);
        $continuation->setCacheDirectory(dirname(__FILE__));
        $continuation->addFlow('Counter', dirname(__FILE__) . '/Counter.yaml', true);
        $continuation->setEventNameCallback(array(&$this, 'getEventName'));
        $continuation->setFlowExecutionTicketCallback(array(&$this, 'getFlowExecutionTicket'));
        $continuation->setFlowNameCallback(array(&$this, 'getFlowName'));

        $continuation->getAttribute('foo');

        $this->assertTrue(Piece_Flow_Error::hasErrors('warning'));

        $error = Piece_Flow_Error::pop();

        $this->assertEquals(PIECE_FLOW_ERROR_INVALID_OPERATION, $error['code']);
    }

    function testStartingNewFlowAfterFlowWasShutdownInNonExclusiveMode()
    {
        Piece_Flow_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));
        $GLOBALS['ShutdownCount'] = 0;

        $continuation = &new Piece_Flow_Continuation();
        $continuation->setCacheDirectory(dirname(__FILE__));
        $continuation->addFlow('Shutdown', dirname(__FILE__) . '/Shutdown.yaml');
        $continuation->setEventNameCallback(array(&$this, 'getEventName'));
        $continuation->setFlowExecutionTicketCallback(array(&$this, 'getFlowExecutionTicket'));
        $continuation->setFlowNameCallback(array(&$this, 'getFlowName'));

        /*
         * Starting a new 'Shutdown'.
         */
        $this->_flowName = 'Shutdown';
        $flowExecutionTicket1 = $continuation->invoke(new stdClass());
        $this->_flowExecutionTicket = $flowExecutionTicket1;
        $this->_flowName = null;
        $this->_eventName = 'go';
        $flowExecutionTicket2 = $continuation->invoke(new stdClass());
        $continuation->shutdown();

        $this->assertEquals(1, $GLOBALS['ShutdownCount']);
        $this->assertEquals($flowExecutionTicket1, $flowExecutionTicket2);

        /*
         * Failure to continue the 'Shutdown' from the previous flow
         * execution ticket.
         */
        $this->_flowName = null;
        $this->_eventName = 'go';
        $continuation->invoke(new stdClass());

        $this->assertTrue(Piece_Flow_Error::hasErrors('exception'));

        $error = Piece_Flow_Error::pop();

        $this->assertEquals(PIECE_FLOW_ERROR_FLOW_NAME_NOT_GIVEN, $error['code']);

        unset($GLOBALS['ShutdownCount']);
        Piece_Flow_Error::popCallback();
    }

    function testStartingNewFlowAfterFlowWasShutdownInExclusiveMode()
    {
        $GLOBALS['ShutdownCount'] = 0;

        $continuation = &new Piece_Flow_Continuation();
        $continuation->setCacheDirectory(dirname(__FILE__));
        $continuation->addFlow('Shutdown', dirname(__FILE__) . '/Shutdown.yaml', true);
        $continuation->setEventNameCallback(array(&$this, 'getEventName'));
        $continuation->setFlowExecutionTicketCallback(array(&$this, 'getFlowExecutionTicket'));
        $continuation->setFlowNameCallback(array(&$this, 'getFlowName'));

        /*
         * Starting a new 'Shutdown'.
         */
        $this->_flowName = 'Shutdown';
        $flowExecutionTicket1 = $continuation->invoke(new stdClass());
        $this->_flowExecutionTicket = $flowExecutionTicket1;
        $this->_flowName = null;
        $this->_eventName = 'go';
        $flowExecutionTicket2 = $continuation->invoke(new stdClass());
        $continuation->shutdown();

        $this->assertEquals(1, $GLOBALS['ShutdownCount']);
        $this->assertEquals($flowExecutionTicket1, $flowExecutionTicket2);

        /*
         * Failure to continue the 'Shutdown' from the previous flow
         * execution ticket. And starting a new 'Shutdown'.
         */
        $this->_flowName = 'Shutdown';
        $flowExecutionTicket3 = $continuation->invoke(new stdClass());

        $this->assertTrue($flowExecutionTicket1 != $flowExecutionTicket3);

        unset($GLOBALS['ShutdownCount']);
    }

    function testStartingNewFlowAfterFlowWasShutdownInSingleFlowMode()
    {
        $GLOBALS['ShutdownCount'] = 0;

        $continuation = &new Piece_Flow_Continuation(true);
        $continuation->setCacheDirectory(dirname(__FILE__));
        $continuation->addFlow('Shutdown', dirname(__FILE__) . '/Shutdown.yaml');
        $continuation->setEventNameCallback(array(&$this, 'getEventName'));
        $continuation->setFlowExecutionTicketCallback(array(&$this, 'getFlowExecutionTicket'));
        $continuation->setFlowNameCallback(array(&$this, 'getFlowName'));

        /*
         * Starting a new 'Shutdown'.
         */
        $flowExecutionTicket1 = $continuation->invoke(new stdClass());
        $this->_eventName = 'go';
        $flowExecutionTicket2 = $continuation->invoke(new stdClass());
        $continuation->shutdown();

        $this->assertEquals(1, $GLOBALS['ShutdownCount']);
        $this->assertEquals($flowExecutionTicket1, $flowExecutionTicket2);

        /*
         * Failure to continue the 'Shutdown' from the previous flow
         * execution ticket. And starting a new 'Shutdown'.
         */
        $flowExecutionTicket3 = $continuation->invoke(new stdClass());

        $this->assertTrue($flowExecutionTicket1 != $flowExecutionTicket3);

        unset($GLOBALS['ShutdownCount']);
    }

    function testGettingCurrentFlowExecutionTicket()
    {
        $continuation = &new Piece_Flow_Continuation();
        $continuation->setCacheDirectory(dirname(__FILE__));
        $continuation->addFlow('Counter', dirname(__FILE__) . '/Counter.yaml');
        $continuation->setEventNameCallback(array(&$this, 'getEventName'));
        $continuation->setFlowExecutionTicketCallback(array(&$this, 'getFlowExecutionTicket'));
        $continuation->setFlowNameCallback(array(&$this, 'getFlowName'));

        $flowExecutionTicket = $continuation->invoke(new stdClass());

        $this->assertEquals($flowExecutionTicket, $continuation->getCurrentFlowExecutionTicket());
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
