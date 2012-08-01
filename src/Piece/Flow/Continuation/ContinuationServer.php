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
 * @since      File available since Release 1.14.0
 */

namespace Piece\Flow\Continuation;

use Piece\Flow\Core\MethodInvocationException;
use Piece\Flow\PageFlow\ActionInvoker;
use Piece\Flow\PageFlow\PageFlowRepository;

/**
 * The continuation server.
 *
 * @package    Piece_Flow
 * @copyright  2006-2008, 2012 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version    Release: @package_version@
 * @since      Class available since Release 1.14.0
 */
class ContinuationServer
{
    protected $exclusivePageFlows = array();
    protected $flowExecutionTicketCallback;
    protected $flowIDCallback;
    protected $eventNameCallback;
    protected $isFirstTime;
    protected $activeFlowID;
    protected $activeFlowExecutionTicket;
    protected $gc;
    protected $flowExecution;

    /**
     * @var \Piece\Flow\PageFlow\ActionInvoker
     * @since Property available since Release 2.0.0
     */
    protected $actionInvoker;

    /**
     * @var \Piece\Flow\PageFlow\PageFlowRepository
     * @since Property available since Release 2.0.0
     */
    protected $pageFlowRepository;

    private static $activeInstances = array();
    private static $shutdownRegistered = false;

    /**
     * @param \Piece\Flow\PageFlow\PageFlowRepository $pageFlowRepository
     * @param \Piece\Flow\Continuation\GC $gc
     */
    public function __construct(PageFlowRepository $pageFlowRepository, GC $gc = null)
    {
        $this->flowExecution = new FlowExecution();
        $this->pageFlowRepository = $pageFlowRepository;
        $this->gc = $gc;
    }

    /**
     * Adds a flow definition to the Continuation object.
     *
     * @param string  $flowID
     * @param boolean $isExclusive
     */
    public function addFlow($flowID, $isExclusive = false)
    {
        $this->pageFlowRepository->add($flowID);
        if ($isExclusive) {
            $this->exclusivePageFlows[] = $flowID;
        }
    }

    /**
     * Invokes a flow and returns a flow execution ticket.
     *
     * @param mixed   $payload
     * @return string
     */
    public function invoke($payload)
    {
        if (!is_null($this->gc)) {
            $this->gc->setGCCallback(array($this->flowExecution, 'disableFlowExecution'));
            $this->gc->mark();
        }

        $this->prepare();

        if (!$this->isFirstTime) {
            $this->continueFlowExecution($payload);
        } else {
            $this->startFlowExecution($payload);
        }

        if (!is_null($this->gc) && !$this->isExclusive()) {
            $this->gc->update($this->activeFlowExecutionTicket);
        }

        self::$activeInstances[] = $this;
        if (!self::$shutdownRegistered) {
            self::$shutdownRegistered = true;
            register_shutdown_function(array(__CLASS__, 'shutdown'));
        }

        return $this->activeFlowExecutionTicket;
    }

    /**
     * Gets an appropriate view string which corresponding to the current
     * state.
     *
     * @return string
     * @throws \Piece\Flow\Core\MethodInvocationException
     */
    public function getView()
    {
        if (!$this->flowExecution->activated()) {
            throw new MethodInvocationException(__FUNCTION__ . ' method must be called after starting/continuing flows.');
        }

        $flow = $this->flowExecution->getActiveFlow();
        return $flow->getView();
    }

    /**
     * Sets a callback for getting an event name.
     *
     * @param callback $callback
     */
    public function setEventNameCallback($callback)
    {
        $this->eventNameCallback = $callback;
    }

    /**
     * Sets a callback for getting a flow execution ticket.
     *
     * @param callback $callback
     */
    public function setFlowExecutionTicketCallback($callback)
    {
        $this->flowExecutionTicketCallback = $callback;
    }

    /**
     * Shutdown the continuation server for next events.
     */
    public static function shutdown()
    {
        for ($i = 0, $count = count(self::$activeInstances); $i < $count; ++$i) {
            $instance = self::$activeInstances[$i];
            if (!($instance instanceof self)) {
                unset(self::$activeInstances[$i]);
                continue;
            }
            $instance->clear();
        }
    }

    /**
     * Clears some properties for the next use.
     */
    public function clear()
    {
        if ($this->flowExecution->hasFlowExecution($this->activeFlowExecutionTicket)) {
            $flow = $this->flowExecution->getActiveFlow();
            if ($flow->isFinalState()) {
                $this->flowExecution->removeFlowExecution($this->activeFlowExecutionTicket, $this->activeFlowID);
            }
        }

        $this->isFirstTime = null;
        $this->activeFlowID = null;
        $this->activeFlowExecutionTicket = null;
        $this->flowExecution->inactivateFlowExecution();
        if (!is_null($this->gc)) {
            $this->gc->sweep();
        }
    }

    /**
     * Sets the action invoker.
     *
     * @param \Piece\Flow\PageFlow\ActionInvoker $actionInvoker
     * @since Method available since Release 2.0.0
     */
    public function setActionInvoker(ActionInvoker $actionInvoker)
    {
        $this->actionInvoker = $actionInvoker;
    }

    /**
     * Sets a callback for getting a flow ID.
     *
     * @param callback $callback
     * @since Method available since Release 1.15.0
     */
    public function setFlowIDCallback($callback)
    {
        $this->flowIDCallback = $callback;
    }

    /**
     * Gets the flow ID for the active flow execution.
     *
     * @return mixed
     * @since Method available since Release 1.15.0
     */
    public function getActiveFlowID()
    {
        return $this->activeFlowID;
    }

    /**
     * @return \Piece\Flow\Continuation\PageFlowInstance
     * @throws \Piece\Flow\Core\MethodInvocationException
     * @since Method available since Release 2.0.0
     */
    public function getActivePageFlowInstance()
    {
        if (!$this->flowExecution->activated()) {
            throw new MethodInvocationException(__FUNCTION__ . ' method must be called after starting/continuing flows.');
        }

        return $this->flowExecution->findByID($this->activeFlowExecutionTicket);
    }

    /**
     * @return \Piece\Flow\Continuation\FlowExecution
     * @since Method available since Release 2.0.0
     */
    public function getPageFlowInstanceRepository()
    {
        return $this->flowExecution;
    }

    /**
     * Generates a flow execution ticket.
     */
    protected function generateFlowExecutionTicket()
    {
        return sha1(uniqid(mt_rand(), true));
    }

    /**
     * Prepares a flow execution ticket, a flow ID, and whether the
     * flow invocation is the first time or not.
     *
     * @throws \Piece\Flow\Continuation\FlowIDRequiredException
     * @throws \Piece\Flow\Continuation\InvaidFlowIDException
     */
    protected function prepare()
    {
        $currentFlowExecutionTicket = call_user_func($this->flowExecutionTicketCallback);
        if ($this->flowExecution->hasFlowExecution($currentFlowExecutionTicket)) {
            $registeredFlowID = $this->flowExecution->getFlowID($currentFlowExecutionTicket);

            $flowID = $this->getFlowID();
            if (is_null($flowID) || !strlen($flowID)) {
                throw new FlowIDRequiredException('A flow ID must be given in this case.');
            }

            if ($flowID != $registeredFlowID) {
                throw new InvalidFlowIDException('The given flow ID is different from the registerd flow ID.');
            }

            $this->activeFlowID = $registeredFlowID;
            $this->isFirstTime = false;
            $this->activeFlowExecutionTicket = $currentFlowExecutionTicket;
        } else {
            $flowID = $this->getFlowID();
            if (is_null($flowID) || !strlen($flowID)) {
                throw new FlowIDRequiredException('A flow ID must be given in this case.');
            }

            if ($this->flowExecution->hasExclusiveFlowExecution($flowID)) {
                trigger_error("Another flow execution of the current flow [ $flowID ] already exists in the flow executions. Starting a new flow execution.",
                              E_USER_WARNING
                              );
                $this->flowExecution->removeFlowExecution($this->flowExecution->getFlowExecutionTicketByFlowID($flowID), $flowID);
            }

            $this->activeFlowID = $flowID;
            $this->isFirstTime = true;
        }
    }

    /**
     * Continues a flow execution.
     *
     * @param mixed   $payload
     * @throws \Piece\Flow\Continuation\FlowExecutionExpiredException
     */
    protected function continueFlowExecution($payload)
    {
        if (!is_null($this->gc)) {
            if ($this->gc->isMarked($this->activeFlowExecutionTicket)) {
                $this->flowExecution->removeFlowExecution($this->activeFlowExecutionTicket, $this->activeFlowID);
                throw new FlowExecutionExpiredException('The flow execution for the given flow execution ticket has expired.');
            }
        }

        $this->flowExecution->activateFlowExecution($this->activeFlowExecutionTicket, $this->activeFlowID);
        $flow = $this->flowExecution->getActiveFlow();
        $flow->setActionInvoker($this->actionInvoker);
        $flow->setPayload($payload);

        $flow->triggerEvent(call_user_func($this->eventNameCallback));
    }

    /**
     * Starts a flow execution.
     *
     * @param mixed $payload
     * @return string
     * @throws \Piece\Flow\Continuation\FlowNotFoundException
     */
    protected function startFlowExecution($payload)
    {
        $flow = $this->pageFlowRepository->findByID($this->activeFlowID);
        if (is_null($flow)) {
            throw new FlowNotFoundException(sprintf('The page flow for ID [ %s ] is not found in the repository.', $this->activeFlowID));
        }

        $flow->setActionInvoker($this->actionInvoker);

        while (true) {
            $flowExecutionTicket = $this->generateFlowExecutionTicket();
            if (!$this->flowExecution->hasFlowExecution($flowExecutionTicket)) {
                $this->flowExecution->addFlowExecution(new PageFlowInstance($flowExecutionTicket, $flow));
                if ($this->isExclusive()) {
                    $this->flowExecution->markFlowExecutionAsExclusive($flowExecutionTicket, $this->activeFlowID);
                }

                break;
            }
        }

        $this->flowExecution->activateFlowExecution($flowExecutionTicket, $this->activeFlowID);
        $this->activeFlowExecutionTicket = $flowExecutionTicket;
        $flow->setPayload($payload);
        $flow->start();

        return $flowExecutionTicket;
    }

    /**
     * Gets a flow ID which will be started or continued.
     *
     * @return string
     */
    protected function getFlowID()
    {
        return call_user_func($this->flowIDCallback);
    }

    /**
     * Checks whether the curent flow execution is exclusive or not.
     *
     * @return boolean
     */
    protected function isExclusive()
    {
        return in_array($this->activeFlowID, $this->exclusivePageFlows);
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
