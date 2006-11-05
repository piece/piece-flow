<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * PHP versions 4 and 5
 *
 * Copyright (c) 2006 KUBO Atsuhiro <iteman@users.sourceforge.net>,
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
 * @author     KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @copyright  2006 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    SVN: $Id$
 * @link       http://piece-framework.com/piece-flow/
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
 * @author     KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @copyright  2006 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @link       http://piece-framework.com/piece-flow/
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

    /**#@-*/

    /**#@+
     * @access public
     */

    function setUp()
    {
        Piece_Flow_Error::pushCallback(create_function('$error', 'var_dump($error); return ' . PEAR_ERRORSTACK_DIE . ';'));
        Piece_Flow_Continuation::setActionDirectory(dirname(__FILE__) . '/../..');
        $GLOBALS['flowName'] = 'Counter';
        $GLOBALS['eventName'] = 'increase';
        $GLOBALS['flowExecutionTicket'] = null;
    }

    function tearDown()
    {
        $GLOBALS['PIECE_FLOW_Action_Instances'] = array();
        $GLOBALS['PIECE_FLOW_Action_Directory'] = null;
        $GLOBALS['eventName'] = null;
        $GLOBALS['flowName'] = null;
        $GLOBALS['flowExecutionTicket'] = null;
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
        return $GLOBALS['flowExecutionTicket'];
    }

    function getFlowName()
    {
        return $GLOBALS['flowName'];
    }

    function getEventName()
    {
        return $GLOBALS['eventName'];
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
        $continuation->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));

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
        $continuation->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $continuation->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));

        $flowExecutionTicket1 = $continuation->invoke(new stdClass());
        $GLOBALS['flowExecutionTicket'] = $flowExecutionTicket1;
        $continuation->shutdown();
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
        $continuation->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $continuation->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));
        $continuation->setFlowNameCallback(array(__CLASS__, 'getFlowName'));

        $flowExecutionTicket1 = $continuation->invoke(new stdClass());
        $GLOBALS['flowExecutionTicket'] = $flowExecutionTicket1;
        $continuation->shutdown();
        $flowExecutionTicket2 = $continuation->invoke(new stdClass());

        $this->assertRegexp('/[0-9a-f]{40}/', $flowExecutionTicket1);
        $this->assertEquals('Counter', $continuation->getView());

        $counter = &Piece_Flow_Action_Factory::factory('Piece_Flow_CounterAction');

        $this->assertEquals(1, $continuation->getAttribute('counter'));
        $this->assertEquals($flowExecutionTicket1, $flowExecutionTicket2);
    }

    function testMultipleInvocationInMultipleFlowModeAndFlowInNonExclusiveMode()
    {
        $continuation = &new Piece_Flow_Continuation();
        $continuation->setCacheDirectory(dirname(__FILE__));
        $continuation->addFlow('Counter', dirname(__FILE__) . '/Counter.yaml');
        $continuation->addFlow('SecondCounter', dirname(__FILE__) . '/SecondCounter.yaml');
        $continuation->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $continuation->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));
        $continuation->setFlowNameCallback(array(__CLASS__, 'getFlowName'));

        /*
         * Starting a new 'Counter'.
         */
        $flowExecutionTicket1 = $continuation->invoke(new stdClass());

        $this->assertEquals(0, $continuation->getAttribute('counter'));

        /*
         * Starting a new 'SecondCounter'.
         */
        $GLOBALS['flowName'] = 'SecondCounter';
        $continuation->shutdown();
        $flowExecutionTicket2 = $continuation->invoke(new stdClass());

        $this->assertEquals(0, $continuation->getAttribute('counter'));
        $this->assertRegexp('/[0-9a-f]{40}/', $flowExecutionTicket1);
        $this->assertRegexp('/[0-9a-f]{40}/', $flowExecutionTicket2);
        $this->assertEquals('SecondCounter', $continuation->getView());
        $this->assertTrue($flowExecutionTicket1 != $flowExecutionTicket2);

        /*
         * Continuing the first 'Counter'.
         */
        $GLOBALS['flowExecutionTicket'] = $flowExecutionTicket1;
        $continuation->shutdown();
        $flowExecutionTicket3 = $continuation->invoke(new stdClass());

        $this->assertEquals(1, $continuation->getAttribute('counter'));

        $this->assertEquals('Counter', $continuation->getView());
        $this->assertEquals($flowExecutionTicket1, $flowExecutionTicket3);

        /*
         * Continuing the first 'SecondCounter'.
         */
        $GLOBALS['flowExecutionTicket'] = $flowExecutionTicket2;
        $continuation->shutdown();
        $flowExecutionTicket4 = $continuation->invoke(new stdClass());

        $this->assertEquals('SecondCounter', $continuation->getView());
        $this->assertEquals(1, $continuation->getAttribute('counter'));
        $this->assertEquals($flowExecutionTicket2, $flowExecutionTicket4);

        /*
         * Starting a new 'SecondCounter'.
         */
        $GLOBALS['flowExecutionTicket'] = null;
        $secondCounter->counter = null;
        $GLOBALS['flowName'] = 'SecondCounter';
        $continuation->shutdown();
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
        $continuation->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $continuation->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));
        $continuation->setFlowNameCallback(array(__CLASS__, 'getFlowName'));

        $GLOBALS['flowExecutionTicket'] = $continuation->invoke(new stdClass());
        $GLOBALS['flowName'] = 'InvalidFlowName';
        $continuation->shutdown();
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
        $continuation->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $continuation->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));
        $continuation->setFlowNameCallback(array(__CLASS__, 'getFlowName'));

        $continuation->invoke(new stdClass());
        $GLOBALS['flowName'] = 'InvalidFlowName';
        $continuation->shutdown();
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
        $continuation->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $continuation->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));
        $continuation->setFlowNameCallback(array(__CLASS__, 'getFlowName'));

        $GLOBALS['flowName'] = 'NonExistingFile';
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
        $continuation->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $continuation->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));
        $continuation->setFlowNameCallback(array(__CLASS__, 'getFlowName'));

        $flowExecutionTicket1 = $continuation->invoke(new stdClass());

        $this->assertEquals(0, $continuation->getAttribute('counter'));

        $GLOBALS['flowName'] = 'SecondCounter';
        $continuation->shutdown();
        $flowExecutionTicket3 = $continuation->invoke(new stdClass());

        $this->assertEquals(0, $continuation->getAttribute('counter'));

        $GLOBALS['flowName'] = 'Counter';
        $GLOBALS['flowExecutionTicket'] = $flowExecutionTicket1;
        $continuation->shutdown();
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
        $continuation->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $continuation->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));
        $continuation->setFlowNameCallback(array(__CLASS__, 'getFlowName'));

        $flowExecutionTicket1 = $continuation->invoke(new stdClass());
        $GLOBALS['flowExecutionTicket'] = $flowExecutionTicket1;
        $continuation->shutdown();
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
        $continuation->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $continuation->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));
        $continuation->setFlowNameCallback(array(__CLASS__, 'getFlowName'));

        $GLOBALS['flowExecutionTicket'] = $continuation->invoke(new stdClass());
        $continuation->setAttribute('foo', 'bar');
        $continuation->shutdown();
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
        $continuation->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $continuation->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));
        $continuation->setFlowNameCallback(array(__CLASS__, 'getFlowName'));

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
        $continuation->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $continuation->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));
        $continuation->setFlowNameCallback(array(__CLASS__, 'getFlowName'));

        $continuation->getAttribute('foo');

        $this->assertTrue(Piece_Flow_Error::hasErrors('warning'));

        $error = Piece_Flow_Error::pop();

        $this->assertEquals(PIECE_FLOW_ERROR_INVALID_OPERATION, $error['code']);
    }

    function testStartingNewFlowAfterShuttingDownFlowInNonExclusiveMode()
    {
        Piece_Flow_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));
        $GLOBALS['ShutdownCount'] = 0;

        $continuation = &new Piece_Flow_Continuation();
        $continuation->setCacheDirectory(dirname(__FILE__));
        $continuation->addFlow('Shutdown', dirname(__FILE__) . '/Shutdown.yaml');
        $continuation->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $continuation->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));
        $continuation->setFlowNameCallback(array(__CLASS__, 'getFlowName'));

        /*
         * Starting a new 'Shutdown'.
         */
        $GLOBALS['flowName'] = 'Shutdown';
        $flowExecutionTicket1 = $continuation->invoke(new stdClass());
        $GLOBALS['flowExecutionTicket'] = $flowExecutionTicket1;
        $GLOBALS['eventName'] = 'go';
        $continuation->shutdown();
        $flowExecutionTicket2 = $continuation->invoke(new stdClass());

        $this->assertEquals(1, $GLOBALS['ShutdownCount']);
        $this->assertEquals($flowExecutionTicket1, $flowExecutionTicket2);

        /*
         * Failure to continue the 'Shutdown' from the previous flow
         * execution ticket.
         */
        $GLOBALS['flowName'] = null;
        $GLOBALS['eventName'] = 'go';
        $continuation->shutdown();
        $continuation->invoke(new stdClass());

        $this->assertTrue(Piece_Flow_Error::hasErrors('exception'));

        $error = Piece_Flow_Error::pop();

        $this->assertEquals(PIECE_FLOW_ERROR_FLOW_NAME_NOT_GIVEN, $error['code']);

        unset($GLOBALS['ShutdownCount']);
        Piece_Flow_Error::popCallback();
    }

    function testStartingNewFlowAfterShuttingDownFlowInExclusiveMode()
    {
        $GLOBALS['ShutdownCount'] = 0;

        $continuation = &new Piece_Flow_Continuation();
        $continuation->setCacheDirectory(dirname(__FILE__));
        $continuation->addFlow('Shutdown', dirname(__FILE__) . '/Shutdown.yaml', true);
        $continuation->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $continuation->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));
        $continuation->setFlowNameCallback(array(__CLASS__, 'getFlowName'));

        /*
         * Starting a new 'Shutdown'.
         */
        $GLOBALS['flowName'] = 'Shutdown';
        $flowExecutionTicket1 = $continuation->invoke(new stdClass());
        $GLOBALS['flowExecutionTicket'] = $flowExecutionTicket1;
        $GLOBALS['eventName'] = 'go';
        $continuation->shutdown();
        $flowExecutionTicket2 = $continuation->invoke(new stdClass());

        $this->assertEquals(1, $GLOBALS['ShutdownCount']);
        $this->assertEquals($flowExecutionTicket1, $flowExecutionTicket2);
        $this->assertRegexp('/[0-9a-f]{40}/', $flowExecutionTicket1);

        /*
         * Failure to continue the 'Shutdown' from the previous flow
         * execution ticket. And starting a new 'Shutdown'.
         */
        $continuation->shutdown();
        $flowExecutionTicket3 = $continuation->invoke(new stdClass());

        $this->assertTrue($flowExecutionTicket1 != $flowExecutionTicket3);
        $this->assertRegexp('/[0-9a-f]{40}/', $flowExecutionTicket3);

        unset($GLOBALS['ShutdownCount']);
    }

    function testStartingNewFlowAfterShuttingDownFlowInSingleFlowMode()
    {
        Piece_Flow_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));
        $GLOBALS['ShutdownCount'] = 0;

        $continuation = &new Piece_Flow_Continuation(true);
        $continuation->setCacheDirectory(dirname(__FILE__));
        $continuation->addFlow('Shutdown', dirname(__FILE__) . '/Shutdown.yaml');
        $continuation->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $continuation->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));
        $continuation->setFlowNameCallback(array(__CLASS__, 'getFlowName'));

        /*
         * Starting a new 'Shutdown'.
         */
        $flowExecutionTicket1 = $continuation->invoke(new stdClass());
        $GLOBALS['flowExecutionTicket'] = $flowExecutionTicket1;
        $GLOBALS['eventName'] = 'go';
        $continuation->shutdown();
        $flowExecutionTicket2 = $continuation->invoke(new stdClass());

        $this->assertEquals(1, $GLOBALS['ShutdownCount']);
        $this->assertEquals($flowExecutionTicket1, $flowExecutionTicket2);

        /*
         * Failure to continue the 'Shutdown' from the previous flow
         * execution ticket. The continuation server never starts a new
         * 'Shutdown' again.
         */
        $continuation->shutdown();
        $continuation->invoke(new stdClass());

        $this->assertTrue(Piece_Flow_Error::hasErrors('exception'));

        $error = Piece_Flow_Error::pop();

        $this->assertEquals(PIECE_FLOW_ERROR_ALREADY_SHUTDOWN, $error['code']);

        unset($GLOBALS['ShutdownCount']);
        Piece_Flow_Error::popCallback();
    }

    function testGettingCurrentFlowExecutionTicket()
    {
        $continuation = &new Piece_Flow_Continuation();
        $continuation->setCacheDirectory(dirname(__FILE__));
        $continuation->addFlow('Counter', dirname(__FILE__) . '/Counter.yaml');
        $continuation->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $continuation->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));
        $continuation->setFlowNameCallback(array(__CLASS__, 'getFlowName'));

        $flowExecutionTicket = $continuation->invoke(new stdClass());

        $this->assertEquals($flowExecutionTicket, $continuation->getCurrentFlowExecutionTicket());
    }

    /**
     * @since Method available since Release 1.7.0
     */
    function testShouldBeRequiredFlowExecutionTicketWheneverContinuingFlowExecution()
    {
        Piece_Flow_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));

        $continuation = &new Piece_Flow_Continuation();
        $continuation->setCacheDirectory(dirname(__FILE__));
        $continuation->addFlow('Counter', dirname(__FILE__) . '/Counter.yaml', true);
        $continuation->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $continuation->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));
        $continuation->setFlowNameCallback(array(__CLASS__, 'getFlowName'));

        $continuation->invoke(new stdClass());

        $this->assertEquals(0, $continuation->getAttribute('counter'));

        $continuation->shutdown();
        $continuation->invoke(new stdClass());

        $this->assertTrue(Piece_Flow_Error::hasErrors('exception'));

        $error = Piece_Flow_Error::pop();

        $this->assertEquals(PIECE_FLOW_ERROR_ALREADY_EXISTS, $error['code']);

        Piece_Flow_Error::popCallback();
    }

    /**
     * @since Method available since Release 1.7.0
     */
    function testGettingFlowExecutionTicketByFlowName()
    {
        $continuation = &new Piece_Flow_Continuation();
        $continuation->setCacheDirectory(dirname(__FILE__));
        $continuation->addFlow('Counter', dirname(__FILE__) . '/Counter.yaml', true);
        $continuation->addFlow('SecondCounter', dirname(__FILE__) . '/SecondCounter.yaml');
        $continuation->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $continuation->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));
        $continuation->setFlowNameCallback(array(__CLASS__, 'getFlowName'));

        $flowExecutionTicket1 = $continuation->invoke(new stdClass());
        $GLOBALS['flowExecutionTicket'] = $flowExecutionTicket1;
        $continuation->invoke(new stdClass());

        $this->assertEquals(1, $continuation->getAttribute('counter'));

        $GLOBALS['flowExecutionTicket'] = null;
        $GLOBALS['flowName'] = 'SecondCounter';

        $flowExecutionTicket2 = $continuation->invoke(new stdClass());

        $this->assertEquals(0, $continuation->getAttribute('counter'));
        $this->assertFalse($flowExecutionTicket1 == $flowExecutionTicket2);
        $this->assertEquals($flowExecutionTicket1, $continuation->getFlowExecutionTicketByFlowName('Counter'));
        $this->assertNull($continuation->getFlowExecutionTicketByFlowName('SecondCounter'));
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
