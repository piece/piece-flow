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

namespace Piece\Flow\PageFlow;

use Stagehand\FSM\Event;
use Stagehand\FSM\FSM;
use Stagehand\FSM\State;

/**
 * @package    Piece_Flow
 * @copyright  2006-2008, 2012 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version    Release: @package_version@
 * @since      Class available since Release 0.1.0
 */
class EventHandlerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     * @since Method available since Release 2.0.0
     */
    public function invokesTheAction()
    {
        $pageFlow = \Phake::mock('Piece\Flow\PageFlow\PageFlow');
        \Phake::when($pageFlow)->invokeAction($this->anything(), $this->anything())->thenReturn('foo');
        $event = new Event('bar');
        $payload = new \stdClass();
        $eventHandler = new EventHandler('my_controller:onRegister', $pageFlow);
        $nextEvent = $eventHandler->invokeAction($event, $payload, new FSM());

        $this->assertEquals($nextEvent, 'foo');
        \Phake::verify($pageFlow)->invokeAction('my_controller:onRegister', \Phake::capture($eventContext)); /* @var $eventContext \Piece\Flow\PageFlow\EventContext */
        $this->assertSame($event, $eventContext->getEvent());
        $this->assertSame($pageFlow, $eventContext->getPageFlow());
        $this->assertSame($payload, $eventContext->getPayload());
    }

    /**
     * @test
     * @since Method available since Release 2.0.0
     */
    public function invokesTheActionAndTriggersTheNextEvent()
    {
        $fsm = \Phake::mock('Stagehand\FSM\FSM');
        \Phake::when($fsm)->hasEvent($this->anything())->thenReturn(true);
        $pageFlow = \Phake::mock('Piece\Flow\PageFlow\PageFlow');
        \Phake::when($pageFlow)->invokeAction($this->anything(), $this->anything())->thenReturn('foo');
        $event = new Event('bar');
        $payload = new \stdClass();
        $eventHandler = new EventHandler('my_controller:onRegister', $pageFlow);
        $eventHandler->invokeActionAndTriggerEvent($event, $payload, $fsm);

        \Phake::verify($pageFlow)->invokeAction('my_controller:onRegister', \Phake::capture($eventContext)); /* @var $eventContext \Piece\Flow\PageFlow\EventContext */
        $this->assertSame($event, $eventContext->getEvent());
        $this->assertSame($pageFlow, $eventContext->getPageFlow());
        $this->assertSame($payload, $eventContext->getPayload());

        \Phake::verify($fsm)->hasEvent('foo');
        \Phake::verify($fsm)->queueEvent('foo');
    }

    /**
     * @test
     * @expectedException \Piece\Flow\PageFlow\EventNotFoundException
     * @since Method available since Release 2.0.0
     */
    public function raisesAnExceptionWhenTheNextEventIsNotFound()
    {
        $fsm = \Phake::mock('Stagehand\FSM\FSM');
        \Phake::when($fsm)->hasEvent($this->anything())->thenReturn(false);
        \Phake::when($fsm)->getCurrentState()->thenReturn(new State('foo'));
        $pageFlow = \Phake::mock('Piece\Flow\PageFlow\PageFlow');
        \Phake::when($pageFlow)->invokeAction($this->anything(), $this->anything())->thenReturn('foo');
        $event = new Event('bar');
        $payload = new \stdClass();
        $eventHandler = new EventHandler('my_controller:onRegister', $pageFlow);
        $eventHandler->invokeActionAndTriggerEvent($event, $payload, $fsm);
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
