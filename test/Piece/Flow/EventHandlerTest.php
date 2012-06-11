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

use Stagehand\FSM\Event;
use Stagehand\FSM\FSM;

use Piece\Flow\Action\Factory;
use Piece\Flow\EventHandler;

/**
 * @package    Piece_Flow
 * @copyright  2006-2008, 2012 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version    Release: @package_version@
 * @since      Class available since Release 0.1.0
 */
class EventHandlerTest extends \PHPUnit_Framework_TestCase
{
    protected $actionDirectory;

    protected function setUp()
    {
        $this->actionDirectory = dirname(__FILE__) . '/' . basename(__FILE__, '.php');
    }

    protected function tearDown()
    {
        Factory::clearInstances();
        Factory::setActionDirectory(null);
    }

    /**
     * @since Method available since Release 1.9.0
     */
    public function testPieceFlowAction()
    {
        $flow = new Flow();
        $payload = new \stdClass();
        $invoker = new EventHandler($flow, '\PieceFlowEventHandlerTestCasePieceFlowAction', 'foo', $this->actionDirectory);
        $invoker->invoke(new FSM(), new Event('bar'), $payload);
        $action = Factory::factory('\PieceFlowEventHandlerTestCasePieceFlowAction');

        $this->assertTrue($action instanceof Action);
        $this->assertSame($payload, $this->readAttribute($action, 'payload'));
        $this->assertEquals('bar', $this->readAttribute($action, 'event'));
        $this->assertTrue($action->prepareCalled);
        $this->assertTrue($action->eventHandlerCalled);

        $flow->foo = 'bar';
        $payload->bar = 'baz';

        $this->assertTrue(property_exists($this->readAttribute($action, 'flow'), 'foo'));
        $this->assertEquals('bar', $this->readAttribute($action, 'flow')->foo);
        $this->assertTrue(property_exists($this->readAttribute($action, 'payload'), 'bar'));
        $this->assertEquals('baz', $this->readAttribute($action, 'payload')->bar);
    }

    /**
     * @since Method available since Release 1.9.0
     */
    public function testPlainPHPAction()
    {
        $flow = new Flow();
        $payload = new \stdClass();
        $invoker = new EventHandler($flow, '\PieceFlowEventHandlerTestCasePlainPHPAction', 'foo', $this->actionDirectory);
        $invoker->invoke(new FSM(), new Event('bar'), $payload);
        $action = Factory::factory('\PieceFlowEventHandlerTestCasePlainPHPAction');

        $this->assertFalse($action instanceof Action);
        $this->assertTrue($action instanceof \PieceFlowEventHandlerTestCasePlainPHPAction);
        $this->assertTrue(property_exists($action, 'flow'));
        $this->assertSame($flow, $action->flow);
        $this->assertTrue(property_exists($action, 'payload'));
        $this->assertSame($payload, $action->payload);
        $this->assertTrue(property_exists($action, 'event'));
        $this->assertEquals('bar', $action->event);
        $this->assertTrue($action->prepareCalled);
        $this->assertTrue($action->eventHandlerCalled);

        $flow->foo = 'bar';
        $payload->bar = 'baz';

        $this->assertTrue(property_exists($this->readAttribute($action, 'flow'), 'foo'));
        $this->assertEquals('bar', $this->readAttribute($action, 'flow')->foo);
        $this->assertTrue(property_exists($this->readAttribute($action, 'payload'), 'bar'));
        $this->assertEquals('baz', $this->readAttribute($action, 'payload')->bar);
    }

    /**
     * @since Method available since Release 1.9.0
     */
    public function testActionHasNoMethods()
    {
        $invoker = new EventHandler(new Flow(), '\PieceFlowEventHandlerTestCaseNoMethodsAction', 'foo', $this->actionDirectory);
        $invoker->invoke(new FSM(), new Event('bar'), new \stdClass());
        $action = Factory::factory('\PieceFlowEventHandlerTestCaseNoMethodsAction');

        $this->assertTrue($action instanceof \PieceFlowEventHandlerTestCaseNoMethodsAction);
        $this->assertTrue($action->constructorCalled);
        $this->assertTrue($action->eventHandlerCalled);
    }

    /**
     * @expectedException \Piece\Flow\HandlerNotFoundException
     * @since Method available since Release 1.9.0
     */
    public function testEventHandlerNotFound()
    {
        $invoker = new EventHandler(new Flow(), 'PieceFlowEventHandlerTestCasePlainPHPAction', 'bar', $this->actionDirectory);
        $invoker->invoke(new FSM(), new Event('bar'), new \stdClass());
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
