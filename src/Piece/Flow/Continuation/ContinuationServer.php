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

use Piece\Flow\PageFlow\ActionInvoker;

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
    protected $flowExecutionTicketCallback;
    protected $flowIDCallback;
    protected $eventNameCallback;
    protected $gc;
    protected $flowExecution;

    /**
     * @var \Piece\Flow\PageFlow\ActionInvoker
     * @since Property available since Release 2.0.0
     */
    protected $actionInvoker;

    /**
     * @var \Piece\Flow\Continuation\PageFlowInstance
     * @since Property available since Release 2.0.0
     */
    protected $pageFlowInstance;

    private static $activeInstances = array();
    private static $shutdownRegistered = false;

    /**
     * @param \Piece\Flow\Continuation\PageFlowInstanceRepository $pageFlowInstanceRepository
     * @param \Piece\Flow\Continuation\GC $gc
     */
    public function __construct(PageFlowInstanceRepository $pageFlowInstanceRepository, GC $gc = null)
    {
        $this->flowExecution = $pageFlowInstanceRepository;
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
        $this->flowExecution->addPageFlow($flowID, $isExclusive);
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
            $this->gc->mark();
        }

        $this->pageFlowInstance = $this->prepare($payload);
        $this->pageFlowInstance->activate(call_user_func($this->eventNameCallback));

        if (!is_null($this->gc) && !$this->flowExecution->isExclusive($this->pageFlowInstance->getPageFlowID())) {
            $this->gc->update($this->pageFlowInstance->getID());
        }

        self::$activeInstances[] = $this;
        if (!self::$shutdownRegistered) {
            self::$shutdownRegistered = true;
            register_shutdown_function(array(__CLASS__, 'shutdown'));
        }

        return $this->pageFlowInstance->getID();
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
        if (!is_null($this->pageFlowInstance)) {
            if ($this->pageFlowInstance->isFinalState()) {
                $this->flowExecution->remove($this->pageFlowInstance->getID());
            }
        }

        $this->pageFlowInstance = null;
        if (!is_null($this->gc)) {
            $pageFlowInstanceRepository = $this->flowExecution;
            $this->gc->sweep(function ($pageFlowInstanceID) use ($pageFlowInstanceRepository) {
                $pageFlowInstance = $pageFlowInstanceRepository->findByID($pageFlowInstanceID);
                if (!is_null($pageFlowInstance)) {
                    $pageFlowInstance->removePageFlow();
                }
            });
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
     * @return \Piece\Flow\Continuation\PageFlowInstance
     * @since Method available since Release 2.0.0
     */
    public function getActivePageFlowInstance()
    {
        return $this->pageFlowInstance;
    }

    /**
     * @return \Piece\Flow\Continuation\PageFlowInstanceRepository
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
     * @param mixed $payload
     * @return \Piece\Flow\Continuation\PageFlowInstance
     * @throws \Piece\Flow\Continuation\FlowExecutionExpiredException
     * @throws \Piece\Flow\Continuation\FlowIDRequiredException
     * @throws \Piece\Flow\Continuation\FlowNotFoundException
     * @throws \Piece\Flow\Continuation\InvaidFlowIDException
     */
    protected function prepare($payload)
    {
        $currentFlowExecutionTicket = call_user_func($this->flowExecutionTicketCallback);
        $pageFlowInstance = $this->flowExecution->findByID($currentFlowExecutionTicket);
        if (!is_null($pageFlowInstance)) {
            $registeredFlowID = $pageFlowInstance->getPageFlowID();

            $flowID = $this->getFlowID();
            if (is_null($flowID) || !strlen($flowID)) {
                throw new FlowIDRequiredException('A flow ID must be given in this case.');
            }

            if ($flowID != $registeredFlowID) {
                throw new InvalidFlowIDException('The given flow ID is different from the registerd flow ID.');
            }

            if (!is_null($this->gc)) {
                if ($this->gc->isMarked($pageFlowInstance->getID())) {
                    $this->flowExecution->remove($pageFlowInstance->getID());
                    throw new FlowExecutionExpiredException('The flow execution for the given flow execution ticket has expired.');
                }
            }
        } else {
            $flowID = $this->getFlowID();
            if (is_null($flowID) || !strlen($flowID)) {
                throw new FlowIDRequiredException('A flow ID must be given in this case.');
            }

            if ($this->flowExecution->hasExclusiveFlowExecution($flowID)) {
                trigger_error("Another flow execution of the current flow [ $flowID ] already exists in the flow executions. Starting a new flow execution.",
                              E_USER_WARNING
                              );
                $this->flowExecution->remove($this->flowExecution->findByPageFlowID($flowID)->getID());
            }

            $pageFlow = $this->flowExecution->getPageFlowRepository()->findByID($flowID);
            if (is_null($pageFlow)) {
                throw new FlowNotFoundException(sprintf('The page flow for ID [ %s ] is not found in the repository.', $flowID));
            }

            while (true) {
                $flowExecutionTicket = $this->generateFlowExecutionTicket();
                $pageFlowInstance = $this->flowExecution->findByID($flowExecutionTicket);
                if (is_null($pageFlowInstance)) {
                    $pageFlowInstance = new PageFlowInstance($flowExecutionTicket, $pageFlow);
                    $this->flowExecution->addFlowExecution($pageFlowInstance);
                    if ($this->flowExecution->isExclusive($pageFlowInstance->getPageFlowID())) {
                        $this->flowExecution->markFlowExecutionAsExclusive($flowExecutionTicket, $pageFlowInstance->getPageFlowID());
                    }

                    break;
                }
            }
        }

        $pageFlowInstance->setActionInvoker($this->actionInvoker);
        $pageFlowInstance->setPayload($payload);

        return $pageFlowInstance;
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
