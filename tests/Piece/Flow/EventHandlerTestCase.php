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
 * @since      File available since Release 0.1.0
 */

require_once realpath(dirname(__FILE__) . '/../../prepare.php');
require_once 'PHPUnit.php';
require_once 'Piece/Flow/EventHandler.php';
require_once 'Piece/Flow/Error.php';
require_once 'Piece/Flow/Action/Factory.php';

// {{{ Piece_Flow_EventHandlerTestCase

/**
 * Some tests for Piece_Flow_EventHandler.
 *
 * @package    Piece_Flow
 * @copyright  2006-2008 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @since      Class available since Release 0.1.0
 */
class Piece_Flow_EventHandlerTestCase extends PHPUnit_TestCase
{

    // {{{ properties

    /**#@+
     * @access public
     */

    /**#@-*/

    /**#@+
     * @access private
     */

    var $_actionDirectory;

    /**#@-*/

    /**#@+
     * @access public
     */

    function setUp()
    {
        $this->_actionDirectory = dirname(__FILE__) . '/' . basename(__FILE__, '.php');
    }

    function tearDown()
    {
        Piece_Flow_Action_Factory::clearInstances();
        Piece_Flow_Action_Factory::setActionDirectory(null);
        Piece_Flow_Error::clearErrors();
    }

    /**
     * @since Method available since Release 1.9.0
     */
    function testPieceFlowAction()
    {
        $flow = &new stdClass();
        $payload = &new stdClass();
        $invoker = &new Piece_Flow_EventHandler($flow, 'PieceFlowEventHandlerTestCasePieceFlowAction', 'foo', $this->_actionDirectory);
        $invoker->invoke(new stdClass(), new Piece_Flow_EventHandlerTestCaseMockEvent(), $payload);
        $action = &Piece_Flow_Action_Factory::factory('PieceFlowEventHandlerTestCasePieceFlowAction');

        $this->assertEquals(strtolower('PieceFlowEventHandlerTestCasePieceFlowAction'), strtolower(get_class($action)));
        $this->assertTrue(array_key_exists('_flow', $action));
        $this->assertEquals(strtolower('stdClass'), strtolower(get_class($action->_flow)));
        $this->assertTrue(array_key_exists('_payload', $action));
        $this->assertEquals(strtolower('stdClass'), strtolower(get_class($action->_payload)));
        $this->assertTrue(array_key_exists('_event', $action));
        $this->assertEquals('bar', $action->_event);
        $this->assertTrue($action->prepareCalled);
        $this->assertTrue($action->eventHandlerCalled);

        $flow->foo = 'bar';
        $payload->bar = 'baz';

        $this->assertTrue(array_key_exists('foo', $action->_flow));
        $this->assertEquals('bar', $action->_flow->foo);
        $this->assertTrue(array_key_exists('bar', $action->_payload));
        $this->assertEquals('baz', $action->_payload->bar);
    }

    /**
     * @since Method available since Release 1.9.0
     */
    function testPlainPHPAction()
    {
        $flow = &new stdClass();
        $payload = &new stdClass();
        $invoker = &new Piece_Flow_EventHandler($flow, 'PieceFlowEventHandlerTestCasePlainPHPAction', 'foo', $this->_actionDirectory);
        $invoker->invoke(new stdClass(), new Piece_Flow_EventHandlerTestCaseMockEvent(), $payload);
        $action = &Piece_Flow_Action_Factory::factory('PieceFlowEventHandlerTestCasePlainPHPAction');

        $this->assertEquals(strtolower('PieceFlowEventHandlerTestCasePlainPHPAction'), strtolower(get_class($action)));
        $this->assertTrue(array_key_exists('_flow', $action));
        $this->assertEquals(strtolower('stdClass'), strtolower(get_class($action->_flow)));
        $this->assertTrue(array_key_exists('_payload', $action));
        $this->assertEquals(strtolower('stdClass'), strtolower(get_class($action->_payload)));
        $this->assertTrue(array_key_exists('_event', $action));
        $this->assertEquals('bar', $action->_event);
        $this->assertTrue($action->prepareCalled);
        $this->assertTrue($action->eventHandlerCalled);

        $flow->foo = 'bar';
        $payload->bar = 'baz';

        $this->assertTrue(array_key_exists('foo', $action->_flow));
        $this->assertEquals('bar', $action->_flow->foo);
        $this->assertTrue(array_key_exists('bar', $action->_payload));
        $this->assertEquals('baz', $action->_payload->bar);
    }

    /**
     * @since Method available since Release 1.9.0
     */
    function testActionHasNoMethods()
    {
        $invoker = &new Piece_Flow_EventHandler(new stdClass(), 'PieceFlowEventHandlerTestCaseNoMethodsAction', 'foo', $this->_actionDirectory);
        $invoker->invoke(new stdClass(), new Piece_Flow_EventHandlerTestCaseMockEvent(), new stdClass());
        $action = &Piece_Flow_Action_Factory::factory('PieceFlowEventHandlerTestCaseNoMethodsAction');

        $this->assertEquals(strtolower('PieceFlowEventHandlerTestCaseNoMethodsAction'), strtolower(get_class($action)));
        $this->assertFalse(array_key_exists('_flow', $action));
        $this->assertFalse(array_key_exists('_payload', $action));
        $this->assertFalse(array_key_exists('_event', $action));
        $this->assertTrue($action->constructorCalled);
        $this->assertTrue($action->eventHandlerCalled);
    }

    /**
     * @since Method available since Release 1.9.0
     */
    function testEventHandlerNotFound()
    {
        $flow = &new stdClass();
        $payload = &new stdClass();
        $invoker = &new Piece_Flow_EventHandler($flow, 'PieceFlowEventHandlerTestCasePlainPHPAction', 'bar', $this->_actionDirectory);
        Piece_Flow_Error::disableCallback();
        $invoker->invoke(new stdClass(), new Piece_Flow_EventHandlerTestCaseMockEvent(), $payload);
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

class Piece_Flow_EventHandlerTestCaseMockEvent
{
    function getName()
    {
        return 'bar';
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
