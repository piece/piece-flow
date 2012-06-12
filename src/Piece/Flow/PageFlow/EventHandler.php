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

use Piece\Flow\Action\Factory;

/**
 * The invoker for an event handler.
 *
 * @package    Piece_Flow
 * @copyright  2006-2008, 2012 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version    Release: @package_version@
 * @since      Class available since Release 0.1.0
 */
class EventHandler
{
    protected $flow;
    protected $class;
    protected $method;
    protected $actionDirectory;

    /**
     * Wraps a action up with an EventHandler object.
     *
     * @param \Piece\Flow\PageFlow\PageFlow $flow
     * @param string     $class
     * @param string     $method
     * @param string     $actionDirectory
     */
    public function __construct(PageFlow $flow, $class, $method, $actionDirectory)
    {
        $this->flow = $flow;

        if (is_null($class) || !strlen($class)) {
            $this->class = $this->flow->getName() . 'Action';
        } else {
            $this->class = $class;
        }

        $this->method = $method;
        $this->actionDirectory = $actionDirectory;
    }

    /**
     * Invokes an event handler in an action.
     *
     * @param \Stagehand\FSM\FSM $fsm
     * @param \Stagehand\FSM\Event $event
     * @param mixed               &$payload
     * @return mixed
     * @throws \Piece\Flow\PageFlow\HandlerNotFoundException
     */
    public function invoke(FSM $fsm, Event $event, &$payload)
    {
        if (!is_null($this->actionDirectory)) {
            Factory::setActionDirectory($this->actionDirectory);
        }

        $action = Factory::factory($this->class);
        if (!method_exists($action, $this->method)) {
            throw new HandlerNotFoundException("The method [ {$this->method} ] does not exist in the action class [ {$this->class} ].");
        }

        if (method_exists($action, 'setFlow')) {
            $action->setFlow($this->flow);
        }

        if (method_exists($action, 'setPayload')) {
            $action->setPayload($payload);
        }

        if (method_exists($action, 'setEvent')) {
            $action->setEvent($event->getName());
        }

        if (method_exists($action, 'prepare')) {
            $action->prepare();
        }

        $result = call_user_func(array($action, $this->method));

        if (method_exists($action, 'clear')) {
            $action->clear();
        }

        return $result;
    }

    /**
     * Invokes an event handler in an action and triggers an event returned
     * from the action.
     *
     * @param \Stagehand\FSM\FSM $fsm
     * @param \Stagehand\FSM\Event $event
     * @param mixed               &$payload
     * @throws \Piece\Flow\PageFlow\EventNotFoundException
     */
    public function invokeAndTriggerEvent(FSM $fsm, Event $event, &$payload)
    {
        $result = $this->invokeEventHandler($event->getName(), $payload);
        if (!is_null($result)) {
            if ($fsm->hasEvent($result)) {
                $fsm->queueEvent($result);
            } else {
                throw new EventNotFoundException("An invalid event [ $result ] is returned from [ {$this->class}::{$this->method}() ] method on the state [ " . $this->flow->getCurrentStateName() . ' ]. Check the flow definition and the action class.');
            }
        }
    }

    /**
     * Invokes an event handler in an action.
     *
     * @param string $eventName
     * @param mixed  &$payload
     * @return string
     * @throws \Piece\Flow\PageFlow\HandlerNotFoundException
     */
    protected function invokeEventHandler($eventName, &$payload)
    {
        if (!is_null($this->actionDirectory)) {
            Factory::setActionDirectory($this->actionDirectory);
        }

        $action = Factory::factory($this->class);
        if (!method_exists($action, $this->method)) {
            throw new HandlerNotFoundException("The method [ {$this->method} ] does not exist in the action class [ {$this->class} ].");
        }

        if (method_exists($action, 'setFlow')) {
            $action->setFlow($this->flow);
        }

        if (method_exists($action, 'setPayload')) {
            $action->setPayload($payload);
        }

        if (method_exists($action, 'setEvent')) {
            $action->setEvent($eventName);
        }

        if (method_exists($action, 'prepare')) {
            $action->prepare();
        }

        $result = call_user_func(array($action, $this->method));

        if (method_exists($action, 'clear')) {
            $action->clear();
        }

        return $result;
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
