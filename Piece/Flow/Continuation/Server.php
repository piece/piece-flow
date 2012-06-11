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

use Piece\Flow\Action\Factory;
use Piece\Flow\Flow;
use Piece\Flow\MethodInvocationException;

/**
 * The continuation server.
 *
 * @package    Piece_Flow
 * @copyright  2006-2008, 2012 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version    Release: @package_version@
 * @since      Class available since Release 1.14.0
 */
class Server
{
    protected $flowDefinitions = array();
    protected $enableSingleFlowMode;
    protected $cacheDirectory;
    protected $flowExecutionTicketCallback;
    protected $flowIDCallback;
    protected $eventNameCallback;
    protected $isFirstTime;
    protected $activeFlowID;
    protected $activeFlowExecutionTicket;
    protected $gc;
    protected $enableGC = false;
    protected $flowExecution;
    protected $actionDirectory;
    protected $useContext = false;
    protected $configDirectory;
    protected $configExtension;

    private static $activeInstances = array();
    private static $shutdownRegistered = false;

    /**
     * Sets whether the continuation server should be work in the single flow
     * mode.
     *
     * @param boolean $enableSingleFlowMode
     * @param integer $enableGC
     * @param integer $gcExpirationTime
     */
    public function __construct($enableSingleFlowMode = false, $enableGC = false, $gcExpirationTime = 1440)
    {
        if (!$enableSingleFlowMode) {
            if ($enableGC) {
                $this->gc = new GC($gcExpirationTime);
                $this->enableGC = true;
            }
        }

        $this->enableSingleFlowMode = $enableSingleFlowMode;
        $this->flowExecution = new FlowExecution();
    }

    /**
     * Adds a flow definition to the Continuation object.
     *
     * @param string  $flowID
     * @param mixed   $source
     * @param boolean $isExclusive
     * @throws \Piece\Flow\Continuation\FlowDefinitionAlreadyExistsException
     */
    public function addFlow($flowID, $source, $isExclusive = false)
    {
        if ($this->enableSingleFlowMode && count($this->flowDefinitions)) {
            throw new FlowDefinitionAlreadyExistsException('A flow definition already exists in the continuation object.');
        }

        $this->flowDefinitions[$flowID] = array('source' => $source,
                                                 'isExclusive' => $isExclusive
                                                 );
    }

    /**
     * Invokes a flow and returns a flow execution ticket.
     *
     * @param mixed   &$payload
     * @param boolean $bindActionsWithFlowExecution
     * @return string
     */
    public function invoke(&$payload, $bindActionsWithFlowExecution = false)
    {
        if ($this->enableGC) {
            $this->gc->setGCCallback(array($this->flowExecution, 'disableFlowExecution'));
            $this->gc->mark();
        }

        $this->prepare();

        if (!$this->isFirstTime) {
            $this->continueFlowExecution($payload, $bindActionsWithFlowExecution);
        } else {
            $this->startFlowExecution($payload);
        }

        if ($this->enableGC && !$this->isExclusive()) {
            $this->gc->update($this->activeFlowExecutionTicket);
        }

        if ($bindActionsWithFlowExecution) {
            $flow = $this->flowExecution->getActiveFlow();
            $flow->clearPayload();
            $this->prepareContext();
            $flow->setAttribute('_actionInstances', Factory::getInstances());
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
     * @throws \Piece\Flow\MethodInvocationException
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
     * Sets the cache directory for the flow definitions.
     *
     * @param string $cacheDirectory
     */
    public function setCacheDirectory($cacheDirectory)
    {
        $this->cacheDirectory = $cacheDirectory;
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
        if ($this->flowExecution->hasFlowExecution($this->activeFlowExecutionTicket)
            && !$this->enableSingleFlowMode
            ) {
            $flow = $this->flowExecution->getActiveFlow();
            if ($flow->isFinalState()) {
                $this->flowExecution->removeFlowExecution($this->activeFlowExecutionTicket, $this->activeFlowID);
            }
        }

        $this->isFirstTime = null;
        $this->activeFlowID = null;
        $this->activeFlowExecutionTicket = null;
        $this->flowExecution->inactivateFlowExecution();
        if ($this->enableGC) {
            $this->gc->sweep();
        }
    }

    /**
     * Creates a new Service object for client use.
     */
    public function createService()
    {
        $service = new Service($this->flowExecution);
        return $service;
    }

    /**
     * Sets a directory as the action directory.
     *
     * @param string $actionDirectory
     * @since Method available since Release 1.15.0
     */
    public function setActionDirectory($actionDirectory)
    {
        $this->actionDirectory = $actionDirectory;
    }

    /**
     * Sets whether or not the continuation server use the context by flow
     * execution ticket.
     *
     * @param boolean $useContext
     * @since Method available since Release 1.15.0
     */
    public function setUseContext($useContext)
    {
        $this->useContext = $useContext;
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
     * Sets the config directory for the flow definitions.
     *
     * @param string $configDirectory
     * @since Method available since Release 1.15.0
     */
    public function setConfigDirectory($configDirectory)
    {
        $this->configDirectory = $configDirectory;
    }

    /**
     * Sets the extension for the flow definitions.
     *
     * @param string $configExtension
     * @since Method available since Release 1.15.0
     */
    public function setConfigExtension($configExtension)
    {
        $this->configExtension = $configExtension;
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
     * Gets the flow source for the active flow execution.
     *
     * @return mixed
     * @since Method available since Release 1.15.0
     */
    public function getActiveFlowSource()
    {
        return $this->flowDefinitions[$this->activeFlowID]['source'];
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

            if (!$this->enableSingleFlowMode) {
                $flowID = $this->getFlowID();
                if (is_null($flowID) || !strlen($flowID)) {
                    throw new FlowIDRequiredException('A flow ID must be given in this case.');
                }

                if ($flowID != $registeredFlowID) {
                    throw new InvalidFlowIDException('The given flow ID is different from the registerd flow ID.');
                }
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
     * @param mixed   &$payload
     * @param boolean $bindActionsWithFlowExecution
     * @throws \Piece\Flow\Continuation\FlowExecutionExpiredException
     */
    protected function continueFlowExecution(&$payload, $bindActionsWithFlowExecution)
    {
        if ($this->enableGC) {
            if ($this->gc->isMarked($this->activeFlowExecutionTicket)) {
                $this->flowExecution->removeFlowExecution($this->activeFlowExecutionTicket, $this->activeFlowID);
                throw new FlowExecutionExpiredException('The flow execution for the given flow execution ticket has expired.');
            }
        }

        $this->flowExecution->activateFlowExecution($this->activeFlowExecutionTicket, $this->activeFlowID);
        $flow = $this->flowExecution->getActiveFlow();
        $flow->setPayload($payload);
        $this->prepareContext();

        if ($bindActionsWithFlowExecution) {
            Factory::setInstances($flow->getAttribute('_actionInstances'));
        }

        $flow->triggerEvent(call_user_func($this->eventNameCallback));
    }

    /**
     * Starts a flow execution.
     *
     * @param mixed &$payload
     * @return string
     * @throws \Piece\Flow\Continuation\FlowNotFoundException
     */
    protected function startFlowExecution(&$payload)
    {
        if (!array_key_exists($this->activeFlowID, $this->flowDefinitions)) {
            throw new FlowNotFoundException("The flow ID [ {$this->activeFlowID} ] not found in the flow definitions.");
        }

        $flow = new Flow();
        $flow->configure($this->flowDefinitions[$this->activeFlowID]['source'],
                         null,
                         $this->cacheDirectory,
                         $this->actionDirectory,
                         $this->configDirectory,
                         $this->configExtension
                         );

        while (true) {
            $flowExecutionTicket = $this->generateFlowExecutionTicket();
            if (!$this->flowExecution->hasFlowExecution($flowExecutionTicket)) {
                $this->flowExecution->addFlowExecution($flowExecutionTicket, $flow, $this->activeFlowID);
                if ($this->isExclusive()) {
                    $this->flowExecution->markFlowExecutionAsExclusive($flowExecutionTicket, $this->activeFlowID);
                }

                break;
            }
        }

        $this->flowExecution->activateFlowExecution($flowExecutionTicket, $this->activeFlowID);
        $this->activeFlowExecutionTicket = $flowExecutionTicket;
        $flow->setPayload($payload);
        $this->prepareContext();
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
        if (!$this->enableSingleFlowMode) {
            return call_user_func($this->flowIDCallback);
        } else {
            $flowIDs = array_keys($this->flowDefinitions);
            return $flowIDs[0];
        }
    }

    /**
     * Checks whether the curent flow execution is exclusive or not.
     *
     * @return boolean
     */
    protected function isExclusive()
    {
        if (!$this->enableSingleFlowMode) {
            return $this->flowDefinitions[$this->activeFlowID]['isExclusive'];
        } else {
            return true;
        }
    }

    /**
     * Prepares the context by flow execution ticket.
     *
     * @since Method available since Release 1.15.0
     */
    protected function prepareContext()
    {
        if ($this->useContext) {
            Factory::setContextID($this->activeFlowExecutionTicket);
        } else {
            Factory::clearContextID();
        }
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
