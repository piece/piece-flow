<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * PHP versions 4 and 5
 *
 * Copyright (c) 2006-2008 KUBO Atsuhiro <iteman@users.sourceforge.net>,
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
 * @copyright  2006-2008 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    SVN: $Id$
 * @since      File available since Release 1.0.0
 * @deprecated File deprecated in Release 1.14.0
 */

require_once realpath(dirname(__FILE__) . '/../../prepare.php');
require_once 'PHPUnit.php';
require_once 'Piece/Flow/Continuation.php';
require_once 'Piece/Flow/Error.php';
require_once 'Cache/Lite/File.php';
require_once 'Piece/Flow/Action/Factory.php';
require_once 'PEAR/ErrorStack.php';

// {{{ GLOBALS

$GLOBALS['PIECE_FLOW_ContinuationTestCase_hasWarnings'] = false;

// }}}
// {{{ Piece_Flow_ContinuationTestCase

/**
 * Some tests for Piece_Flow_Continuation.
 *
 * @package    Piece_Flow
 * @copyright  2006-2008 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @since      Class available since Release 1.0.0
 * @deprecated Class deprecated in Release 1.14.0
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
    var $_cacheDirectory;

    /**#@-*/

    /**#@+
     * @access public
     */

    function setUp()
    {
        PEAR_ErrorStack::setDefaultCallback(create_function('$error', 'var_dump($error); return ' . PEAR_ERRORSTACK_DIE . ';'));
        $GLOBALS['flowName'] = 'Counter';
        $GLOBALS['eventName'] = 'increase';
        $GLOBALS['flowExecutionTicket'] = null;
        $this->_cacheDirectory = dirname(__FILE__) . '/' . basename(__FILE__, '.php');
        Piece_Flow_Action_Factory::setActionDirectory($this->_cacheDirectory);
    }

    function tearDown()
    {
        Piece_Flow_Action_Factory::clearInstances();
        Piece_Flow_Action_Factory::setActionDirectory(null);
        $GLOBALS['eventName'] = null;
        $GLOBALS['flowName'] = null;
        $GLOBALS['flowExecutionTicket'] = null;
        $cache = &new Cache_Lite_File(array('cacheDir' => "{$this->_cacheDirectory}/",
                                            'masterFile' => '',
                                            'automaticSerialization' => true,
                                            'errorHandlingAPIBreak' => true)
                                      );
        $cache->clean();
        Piece_Flow_Error::clearErrors();
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
        $continuation = &new Piece_Flow_Continuation(true);
        $continuation->setCacheDirectory($this->_cacheDirectory);
        Piece_Flow_Error::disableCallback();
        $continuation->addFlow('foo', '/path/to/foo.xml');
        Piece_Flow_Error::enableCallback();

        $this->assertFalse(Piece_Flow_Error::hasErrors());
    }

    function testFailureToAddFlowForSecondTimeInSingleFlowMode()
    {
        $continuation = &new Piece_Flow_Continuation(true);
        $continuation->setCacheDirectory($this->_cacheDirectory);
        $continuation->addFlow('foo', '/path/to/foo.xml');
        Piece_Flow_Error::disableCallback();
        $continuation->addFlow('bar', '/path/to/bar.xml');
        Piece_Flow_Error::enableCallback();

        $this->assertTrue(Piece_Flow_Error::hasErrors());

        $error = Piece_Flow_Error::pop();

        $this->assertEquals(PIECE_FLOW_ERROR_ALREADY_EXISTS, $error['code']);
    }

    function testSettingFlowInMultipleFlowMode()
    {
        $continuation = &new Piece_Flow_Continuation();
        $continuation->setCacheDirectory($this->_cacheDirectory);
        $continuation->addFlow('foo', '/path/to/foo.xml');
        Piece_Flow_Error::disableCallback();
        $continuation->addFlow('bar', '/path/to/bar.xml');
        Piece_Flow_Error::enableCallback();

        $this->assertFalse(Piece_Flow_Error::hasErrors());
    }

    function testFirstTimeInvocationInSingleFlowMode()
    {
        $continuation = &new Piece_Flow_Continuation(true);
        $continuation->setCacheDirectory($this->_cacheDirectory);
        $continuation->addFlow('Counter', "{$this->_cacheDirectory}/Counter.yaml");
        $continuation->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));
        $flowExecutionTicket = $continuation->invoke(new stdClass());

        $this->assertRegexp('/[0-9a-f]{40}/', $flowExecutionTicket);
        $this->assertEquals('Counter', $continuation->getView());
        $this->assertEquals(0, $continuation->getAttribute('counter'));
    }

    function testSecondTimeInvocationInSingleFlowMode()
    {
        $continuation = &new Piece_Flow_Continuation(true);
        $continuation->setCacheDirectory($this->_cacheDirectory);
        $continuation->addFlow('Counter', "{$this->_cacheDirectory}/Counter.yaml");
        $continuation->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $continuation->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));

        $flowExecutionTicket1 = $continuation->invoke(new stdClass());
        $GLOBALS['flowExecutionTicket'] = $flowExecutionTicket1;
        $continuation->shutdown();
        $flowExecutionTicket2 = $continuation->invoke(new stdClass());

        $this->assertRegexp('/[0-9a-f]{40}/', $flowExecutionTicket1);
        $this->assertEquals('Counter', $continuation->getView());
        $this->assertEquals(1, $continuation->getAttribute('counter'));
        $this->assertEquals($flowExecutionTicket1, $flowExecutionTicket2);
    }

    function testInvocationInMultipleFlowModeAndFlowInNonExclusiveMode()
    {
        $continuation = &new Piece_Flow_Continuation();
        $continuation->setCacheDirectory($this->_cacheDirectory);
        $continuation->addFlow('Counter', "{$this->_cacheDirectory}/Counter.yaml");
        $continuation->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $continuation->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));
        $continuation->setFlowNameCallback(array(__CLASS__, 'getFlowName'));

        $flowExecutionTicket1 = $continuation->invoke(new stdClass());
        $GLOBALS['flowExecutionTicket'] = $flowExecutionTicket1;
        $continuation->shutdown();
        $flowExecutionTicket2 = $continuation->invoke(new stdClass());

        $this->assertRegexp('/[0-9a-f]{40}/', $flowExecutionTicket1);
        $this->assertEquals('Counter', $continuation->getView());
        $this->assertEquals(1, $continuation->getAttribute('counter'));
        $this->assertEquals($flowExecutionTicket1, $flowExecutionTicket2);
    }

    function testMultipleInvocationInMultipleFlowModeAndFlowInNonExclusiveMode()
    {
        $continuation = &new Piece_Flow_Continuation();
        $continuation->setCacheDirectory($this->_cacheDirectory);
        $continuation->addFlow('Counter', "{$this->_cacheDirectory}/Counter.yaml");
        $continuation->addFlow('SecondCounter', "{$this->_cacheDirectory}/SecondCounter.yaml");
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
        $continuation->setCacheDirectory($this->_cacheDirectory);
        $continuation->addFlow('Counter', "{$this->_cacheDirectory}/Counter.yaml");
        $continuation->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $continuation->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));
        $continuation->setFlowNameCallback(array(__CLASS__, 'getFlowName'));

        $GLOBALS['flowExecutionTicket'] = $continuation->invoke(new stdClass());
        $GLOBALS['flowName'] = 'InvalidFlowName';
        $continuation->shutdown();
        $continuation->invoke(new stdClass());

        $this->assertFalse(Piece_Flow_Error::hasErrors());
        $this->assertEquals(1, $continuation->getAttribute('counter'));
    }

    function testFailureOfContinuationByInvalidFlowNameInMultipleFlowMode()
    {
        $continuation = &new Piece_Flow_Continuation();
        $continuation->setCacheDirectory($this->_cacheDirectory);
        $continuation->addFlow('Counter', "{$this->_cacheDirectory}/Counter.yaml");
        $continuation->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $continuation->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));
        $continuation->setFlowNameCallback(array(__CLASS__, 'getFlowName'));

        $continuation->invoke(new stdClass());
        $GLOBALS['flowName'] = 'InvalidFlowName';
        $continuation->shutdown();
        Piece_Flow_Error::disableCallback();
        $continuation->invoke(new stdClass());
        Piece_Flow_Error::enableCallback();

        $this->assertTrue(Piece_Flow_Error::hasErrors());

        $error = Piece_Flow_Error::pop();

        $this->assertEquals(PIECE_FLOW_ERROR_NOT_FOUND, $error['code']);
    }

    function testFailureToInvokeByNonExistingFlowConfiguration()
    {
        $continuation = &new Piece_Flow_Continuation();
        $continuation->setCacheDirectory($this->_cacheDirectory);
        $continuation->addFlow('NonExistingFile', "{$this->_cacheDirectory}/NonExistingFile.yaml");
        $continuation->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $continuation->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));
        $continuation->setFlowNameCallback(array(__CLASS__, 'getFlowName'));

        $GLOBALS['flowName'] = 'NonExistingFile';
        Piece_Flow_Error::disableCallback();
        $continuation->invoke(new stdClass());
        Piece_Flow_Error::enableCallback();

        $this->assertTrue(Piece_Flow_Error::hasErrors());

        $error = Piece_Flow_Error::pop();

        $this->assertEquals(PIECE_FLOW_ERROR_NOT_FOUND, $error['code']);
    }

    function testInvocationInMultipleFlowModeAndFlowInExclusiveMode()
    {
        $continuation = &new Piece_Flow_Continuation();
        $continuation->setCacheDirectory($this->_cacheDirectory);
        $continuation->addFlow('Counter', "{$this->_cacheDirectory}/Counter.yaml", true);
        $continuation->addFlow('SecondCounter', "{$this->_cacheDirectory}/SecondCounter.yaml");
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
        $continuation->setCacheDirectory($this->_cacheDirectory);
        $continuation->addFlow('Counter', "{$this->_cacheDirectory}/Counter.yaml", true);
        $continuation->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $continuation->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));
        $continuation->setFlowNameCallback(array(__CLASS__, 'getFlowName'));

        $flowExecutionTicket1 = $continuation->invoke(new stdClass());
        $GLOBALS['flowExecutionTicket'] = $flowExecutionTicket1;
        $continuation->shutdown();
        $flowExecutionTicket2 = $continuation->invoke(new stdClass());

        $this->assertRegexp('/[0-9a-f]{40}/', $flowExecutionTicket1);
        $this->assertEquals('Counter', $continuation->getView());
        $this->assertEquals(1, $continuation->getAttribute('counter'));
        $this->assertEquals($flowExecutionTicket1, $flowExecutionTicket2);
    }

    function testSettingAttribute()
    {
        $continuation = &new Piece_Flow_Continuation(true);
        $continuation->setCacheDirectory($this->_cacheDirectory);
        $continuation->addFlow('Counter', "{$this->_cacheDirectory}/Counter.yaml", true);
        $continuation->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $continuation->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));
        $continuation->setFlowNameCallback(array(__CLASS__, 'getFlowName'));

        $GLOBALS['flowExecutionTicket'] = $continuation->invoke(new stdClass());
        $continuation->setAttribute('foo', 'bar');
        $continuation->shutdown();
        $continuation->invoke(new stdClass());
        $continuation->setAttribute('bar', 'baz');
        $baz1 = &new stdClass();
        $continuation->setAttributeByRef('baz', $baz1);
        $continuation->shutdown();
        $continuation->invoke(new stdClass());

        $this->assertTrue($continuation->hasAttribute('foo'));
        $this->assertEquals('bar', $continuation->getAttribute('foo'));
        $this->assertTrue($continuation->hasAttribute('bar'));
        $this->assertEquals('baz', $continuation->getAttribute('bar'));

        $baz1->foo = 'bar';

        $this->assertTrue(array_key_exists('foo', $baz1));
        $this->assertEquals('bar', $baz1->foo);

        $baz2 = &$continuation->getAttribute('baz');

        $this->assertEquals(strtolower('stdClass'), strtolower(get_class($baz2)));

        $this->assertTrue(array_key_exists('foo', $baz2));
        $this->assertEquals('bar', $baz2->foo);
    }

    function testFailureToSetAttributeBeforeStartingContinuation()
    {
        $continuation = &new Piece_Flow_Continuation(true);
        $continuation->setCacheDirectory($this->_cacheDirectory);
        $continuation->addFlow('Counter', "{$this->_cacheDirectory}/Counter.yaml", true);
        $continuation->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $continuation->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));
        $continuation->setFlowNameCallback(array(__CLASS__, 'getFlowName'));
        Piece_Flow_Error::disableCallback();
        $continuation->setAttribute('foo', 'bar');
        Piece_Flow_Error::enableCallback();

        $this->assertTrue(Piece_Flow_Error::hasErrors());

        $error = Piece_Flow_Error::pop();

        $this->assertEquals(PIECE_FLOW_ERROR_INVALID_OPERATION, $error['code']);
    }

    function testFailureToGetAttributeBeforeStartingContinuation()
    {
        $continuation = &new Piece_Flow_Continuation(true);
        $continuation->setCacheDirectory($this->_cacheDirectory);
        $continuation->addFlow('Counter', "{$this->_cacheDirectory}/Counter.yaml", true);
        $continuation->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $continuation->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));
        $continuation->setFlowNameCallback(array(__CLASS__, 'getFlowName'));
        Piece_Flow_Error::disableCallback();
        $continuation->getAttribute('foo');
        Piece_Flow_Error::enableCallback();

        $this->assertTrue(Piece_Flow_Error::hasErrors());

        $error = Piece_Flow_Error::pop();

        $this->assertEquals(PIECE_FLOW_ERROR_INVALID_OPERATION, $error['code']);
    }

    function testStartingNewFlowExecutionAfterShuttingDownContinuationInNonExclusiveMode()
    {
        $GLOBALS['ShutdownCount'] = 0;
        $continuation = &new Piece_Flow_Continuation();
        $continuation->setCacheDirectory($this->_cacheDirectory);
        $continuation->addFlow('Shutdown', "{$this->_cacheDirectory}/Shutdown.yaml");
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
        Piece_Flow_Error::disableCallback();
        $continuation->invoke(new stdClass());
        Piece_Flow_Error::enableCallback();

        $this->assertTrue(Piece_Flow_Error::hasErrors());

        $error = Piece_Flow_Error::pop();

        $this->assertEquals(PIECE_FLOW_ERROR_FLOW_ID_NOT_GIVEN, $error['code']);

        unset($GLOBALS['ShutdownCount']);
    }

    function testStartingNewFlowExecutionAfterShuttingDownContinuationInExclusiveMode()
    {
        $GLOBALS['ShutdownCount'] = 0;

        $continuation = &new Piece_Flow_Continuation();
        $continuation->setCacheDirectory($this->_cacheDirectory);
        $continuation->addFlow('Shutdown', "{$this->_cacheDirectory}/Shutdown.yaml", true);
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

    function testStartingNewFlowExecutionAfterShuttingDownContinuationInSingleFlowMode()
    {
        $GLOBALS['ShutdownCount'] = 0;

        $continuation = &new Piece_Flow_Continuation(true);
        $continuation->setCacheDirectory($this->_cacheDirectory);
        $continuation->addFlow('Shutdown', "{$this->_cacheDirectory}/Shutdown.yaml");
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
        Piece_Flow_Error::disableCallback();
        $continuation->invoke(new stdClass());
        Piece_Flow_Error::enableCallback();

        $this->assertTrue(Piece_Flow_Error::hasErrors());

        $error = Piece_Flow_Error::pop();

        $this->assertEquals(PIECE_FLOW_ERROR_CANNOT_INVOKE, $error['code']);

        unset($GLOBALS['ShutdownCount']);
    }

    function testGettingCurrentFlowExecutionTicket()
    {
        $continuation = &new Piece_Flow_Continuation();
        $continuation->setCacheDirectory($this->_cacheDirectory);
        $continuation->addFlow('Counter', "{$this->_cacheDirectory}/Counter.yaml");
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
        $continuation = &new Piece_Flow_Continuation();
        $continuation->setCacheDirectory($this->_cacheDirectory);
        $continuation->addFlow('Counter', "{$this->_cacheDirectory}/Counter.yaml", true);
        $continuation->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $continuation->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));
        $continuation->setFlowNameCallback(array(__CLASS__, 'getFlowName'));

        $continuation->invoke(new stdClass());

        $this->assertEquals(0, $continuation->getAttribute('counter'));

        $flowExecutionTicket1 = $continuation->getCurrentFlowExecutionTicket();
        $continuation->shutdown();
        set_error_handler(create_function('$code, $message, $file, $line', "
if (\$code == E_USER_WARNING) {
    \$GLOBALS['PIECE_FLOW_ContinuationTestCase_hasWarnings'] = true;
}
"));
        $continuation->invoke(new stdClass());
        restore_error_handler();
        $flowExecutionTicket2 = $continuation->getCurrentFlowExecutionTicket();

        $this->assertTrue($GLOBALS['PIECE_FLOW_ContinuationTestCase_hasWarnings']);
        $this->assertEquals(0, $continuation->getAttribute('counter'));
        $this->assertTrue($flowExecutionTicket1 != $flowExecutionTicket2);

        $GLOBALS['PIECE_FLOW_ContinuationTestCase_hasWarnings'] = false;
    }

    /**
     * @since Method available since Release 1.7.0
     */
    function testGettingFlowExecutionTicketByFlowName()
    {
        $continuation = &new Piece_Flow_Continuation();
        $continuation->setCacheDirectory($this->_cacheDirectory);
        $continuation->addFlow('Counter', "{$this->_cacheDirectory}/Counter.yaml", true);
        $continuation->addFlow('SecondCounter', "{$this->_cacheDirectory}/SecondCounter.yaml");
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

    /**
     * @since Method available since Release 1.8.0
     */
    function testBindActionsWithFlowExecution()
    {
        Piece_Flow_Action_Factory::setActionDirectory($this->_cacheDirectory);
        $flowName = 'BindActionsWithFlowExecution';
        $GLOBALS['flowName'] = $flowName;
        $GLOBALS['eventName'] = 'goDisplayFinishFromDisplayCounter';
        $continuation = &new Piece_Flow_Continuation();
        $continuation->setCacheDirectory($this->_cacheDirectory);
        $continuation->addFlow($flowName, "{$this->_cacheDirectory}/$flowName.yaml");
        $continuation->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $continuation->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));
        $continuation->setFlowNameCallback(array(__CLASS__, 'getFlowName'));

        // The first time invocation for the flow execution one.
        $flowExecutionTicket1 = $continuation->invoke(new stdClass(), true);

        $this->assertEquals('Counter', $continuation->getView());

        $continuation->shutdown();
        Piece_Flow_Action_Factory::clearInstances();

        // The first time invocation for the flow execution two.
        $flowExecutionTicket2 = $continuation->invoke(new stdClass(), true);

        $this->assertEquals('Counter', $continuation->getView());

        $continuation->shutdown();
        Piece_Flow_Action_Factory::clearInstances();

        $GLOBALS['flowExecutionTicket'] = $flowExecutionTicket1;

        $this->assertTrue($flowExecutionTicket1 != $flowExecutionTicket2);

        // The second time invocation for the flow execution one.
        $continuation->invoke(new stdClass(), true);

        $this->assertEquals('Counter', $continuation->getView());

        $continuation->shutdown();
        Piece_Flow_Action_Factory::clearInstances();

        // The last invocation for the flow execution one.
        $continuation->invoke(new stdClass(), true);

        $this->assertEquals('Finish', $continuation->getView());

        $continuation->shutdown();
        Piece_Flow_Action_Factory::clearInstances();

        $GLOBALS['flowExecutionTicket'] = $flowExecutionTicket2;

        // The second time invocation for the flow execution two.
        $continuation->invoke(new stdClass(), true);

        $this->assertEquals('Counter', $continuation->getView());

        $continuation->shutdown();
        Piece_Flow_Action_Factory::clearInstances();

        // The last invocation for the flow execution two.
        $continuation->invoke(new stdClass(), true);

        $this->assertEquals('Finish', $continuation->getView());

        $continuation->shutdown();
        Piece_Flow_Action_Factory::clearInstances();
    }

    /**
     * @since Method available since Release 1.11.0
     */
    function testFlowExecutionExpiredExceptionShouldBeRaisedWhenFlowExecutionHasExpired()
    {
        $flowName = 'FlowExecutionExpired';
        $GLOBALS['flowName'] = $flowName;
        $continuation = &new Piece_Flow_Continuation(false, true, 1);
        $continuation->setCacheDirectory($this->_cacheDirectory);
        $continuation->addFlow($flowName, "{$this->_cacheDirectory}/$flowName.yaml");
        $continuation->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $continuation->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));
        $continuation->setFlowNameCallback(array(__CLASS__, 'getFlowName'));
        $GLOBALS['flowExecutionTicket'] = $continuation->invoke(new stdClass());
        $continuation->shutdown();
        sleep(2);
        Piece_Flow_Error::disableCallback();
        $continuation->invoke(new stdClass());
        Piece_Flow_Error::enableCallback();

        $this->assertTrue(Piece_Flow_Error::hasErrors());

        $error = Piece_Flow_Error::pop();

        $this->assertEquals(PIECE_FLOW_ERROR_FLOW_EXECUTION_EXPIRED, $error['code']);
    }

    /**
     * @since Method available since Release 1.11.0
     */
    function testFlowExecutionExpiredExceptionShouldNotBeRaisedWhenFlowExecutionHasNotExpired()
    {
        $flowName = 'FlowExecutionExpired';
        $GLOBALS['flowName'] = $flowName;
        $continuation = &new Piece_Flow_Continuation(false, true, 2);
        $continuation->setCacheDirectory($this->_cacheDirectory);
        $continuation->addFlow($flowName, "{$this->_cacheDirectory}/$flowName.yaml");
        $continuation->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $continuation->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));
        $continuation->setFlowNameCallback(array(__CLASS__, 'getFlowName'));
        $GLOBALS['flowExecutionTicket'] = $continuation->invoke(new stdClass());
        $continuation->shutdown();
        sleep(1);
        Piece_Flow_Error::disableCallback();
        $continuation->invoke(new stdClass());
        Piece_Flow_Error::enableCallback();

        $this->assertFalse(Piece_Flow_Error::hasErrors());

        sleep(1);
        Piece_Flow_Error::disableCallback();
        $continuation->invoke(new stdClass());
        Piece_Flow_Error::enableCallback();

        $this->assertFalse(Piece_Flow_Error::hasErrors());

        sleep(1);
        Piece_Flow_Error::disableCallback();
        $continuation->invoke(new stdClass());
        Piece_Flow_Error::enableCallback();

        $this->assertFalse(Piece_Flow_Error::hasErrors());
    }

    /**
     * @since Method available since Release 1.11.0
     */
    function testNewFlowExecutionShouldBeAbleToStartWithSameRequestAfterFlowExecutionIsExpired()
    {
        $flowName = 'FlowExecutionExpired';
        $GLOBALS['flowName'] = $flowName;
        $continuation = &new Piece_Flow_Continuation(false, true, 1);
        $continuation->setCacheDirectory($this->_cacheDirectory);
        $continuation->addFlow($flowName, "{$this->_cacheDirectory}/$flowName.yaml");
        $continuation->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $continuation->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));
        $continuation->setFlowNameCallback(array(__CLASS__, 'getFlowName'));
        $GLOBALS['flowExecutionTicket'] = $continuation->invoke(new stdClass());
        $continuation->shutdown();
        sleep(2);
        Piece_Flow_Error::disableCallback();
        $continuation->invoke(new stdClass());
        Piece_Flow_Error::enableCallback();

        $this->assertTrue(Piece_Flow_Error::hasErrors());

        $error = Piece_Flow_Error::pop();

        $this->assertEquals(PIECE_FLOW_ERROR_FLOW_EXECUTION_EXPIRED, $error['code']);

        $newFlowExecutionTicket = $continuation->invoke(new stdClass());

        $this->assertFalse(Piece_Flow_Error::hasErrors());
        $this->assertTrue($newFlowExecutionTicket != $GLOBALS['flowExecutionTicket']);
    }

    /**
     * @since Method available since Release 1.13.0
     */
    function testCheckLastEventShouldReturnTrueIfContinuationHasJustStarted()
    {
        $flowName = 'CheckLastEvent';
        $GLOBALS['flowName'] = $flowName;
        $GLOBALS['eventName'] = 'foo';
        $continuation = &new Piece_Flow_Continuation(false);
        $continuation->setCacheDirectory($this->_cacheDirectory);
        $continuation->addFlow($flowName, "{$this->_cacheDirectory}/$flowName.yaml");
        $continuation->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $continuation->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));
        $continuation->setFlowNameCallback(array(__CLASS__, 'getFlowName'));
        $GLOBALS['flowExecutionTicket'] = $continuation->invoke(new stdClass());

        $this->assertTrue($continuation->checkLastEvent());
    }

    /**
     * @since Method available since Release 1.13.0
     */
    function testCheckLastEventShouldReturnTrueWhenValidEventIsGivenByUser()
    {
        $flowName = 'CheckLastEvent';
        $GLOBALS['flowName'] = $flowName;
        $continuation = &new Piece_Flow_Continuation(false);
        $continuation->setCacheDirectory($this->_cacheDirectory);
        $continuation->addFlow($flowName, "{$this->_cacheDirectory}/$flowName.yaml");
        $continuation->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $continuation->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));
        $continuation->setFlowNameCallback(array(__CLASS__, 'getFlowName'));
        $GLOBALS['flowExecutionTicket'] = $continuation->invoke(new stdClass());
        $continuation->shutdown();

        $GLOBALS['eventName'] = 'DisplayEditConfirmFromDisplayEdit';

        $continuation->invoke(new stdClass());

        $this->assertTrue($continuation->checkLastEvent());

        $continuation->shutdown();

        $GLOBALS['eventName'] = 'DisplayEditFinishFromDisplayEditConfirm';

        $continuation->invoke(new stdClass());

        $this->assertTrue($continuation->checkLastEvent());
    }

    /**
     * @since Method available since Release 1.13.0
     */
    function testCheckLastEventShouldReturnFalseWhenInvalidEventIsGivenByUser()
    {
        $flowName = 'CheckLastEvent';
        $GLOBALS['flowName'] = $flowName;
        $continuation = &new Piece_Flow_Continuation(false);
        $continuation->setCacheDirectory($this->_cacheDirectory);
        $continuation->addFlow($flowName, "{$this->_cacheDirectory}/$flowName.yaml");
        $continuation->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $continuation->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));
        $continuation->setFlowNameCallback(array(__CLASS__, 'getFlowName'));
        $GLOBALS['flowExecutionTicket'] = $continuation->invoke(new stdClass());
        $continuation->shutdown();

        $GLOBALS['eventName'] = 'foo';

        $continuation->invoke(new stdClass());

        $this->assertFalse($continuation->checkLastEvent());
    }

    /**
     * @since Method available since Release 1.13.0
     */
    function testCheckLastEventShouldReturnTrueIfContinuationHasNotActivatedYet()
    {
        $flowName = 'CheckLastEvent';
        $GLOBALS['flowName'] = $flowName;
        $GLOBALS['eventName'] = 'foo';
        $continuation = &new Piece_Flow_Continuation(false);
        $continuation->setCacheDirectory($this->_cacheDirectory);
        $continuation->addFlow($flowName, "{$this->_cacheDirectory}/$flowName.yaml");
        $continuation->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $continuation->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));
        $continuation->setFlowNameCallback(array(__CLASS__, 'getFlowName'));

        $this->assertTrue($continuation->checkLastEvent());
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
