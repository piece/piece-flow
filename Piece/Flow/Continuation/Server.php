<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * PHP versions 4 and 5
 *
 * Copyright (c) 2006-2008 KUBO Atsuhiro <kubo@iteman.jp>,
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
 * @copyright  2006-2008 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    SVN: $Id$
 * @since      File available since Release 1.14.0
 */

require_once 'Piece/Flow.php';
require_once 'Piece/Flow/Error.php';
require_once 'Piece/Flow/Action/Factory.php';
require_once 'Piece/Flow/Continuation/GC.php';
require_once 'Piece/Flow/Continuation/FlowExecution.php';
require_once 'Piece/Flow/Continuation/Service.php';

// {{{ GLOBALS

$GLOBALS['PIECE_FLOW_Continuation_Server_ActiveInstances'] = array();
$GLOBALS['PIECE_FLOW_Continuation_Server_ShutdownRegistered'] = false;

// }}}
// {{{ Piece_Flow_Continuation_Server

/**
 * The continuation server.
 *
 * @package    Piece_Flow
 * @copyright  2006-2008 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @since      Class available since Release 1.14.0
 */
class Piece_Flow_Continuation_Server
{

    // {{{ properties

    /**#@+
     * @access public
     */

    /**#@-*/

    /**#@+
     * @access private
     */

    var $_flowDefinitions = array();
    var $_enableSingleFlowMode;
    var $_cacheDirectory;
    var $_flowExecutionTicketCallback;
    var $_flowIDCallback;
    var $_eventNameCallback;
    var $_isFirstTime;
    var $_activeFlowID;
    var $_activeFlowExecutionTicket;
    var $_gc;
    var $_enableGC = false;
    var $_flowExecution;
    var $_actionDirectory;
    var $_useContext = false;
    var $_configDirectory;
    var $_configExtension;

    /**#@-*/

    /**#@+
     * @access public
     */

    // }}}
    // {{{ constructor

    /**
     * Sets whether the continuation server should be work in the single flow
     * mode.
     *
     * @param boolean $enableSingleFlowMode
     * @param integer $enableGC
     * @param integer $gcExpirationTime
     */
    function Piece_Flow_Continuation_Server($enableSingleFlowMode = false, $enableGC = false, $gcExpirationTime = 1440)
    {
        if (!$enableSingleFlowMode) {
            if ($enableGC) {
                $this->_gc = &new Piece_Flow_Continuation_GC($gcExpirationTime);
                $this->_enableGC = true;
            }
        }

        $this->_enableSingleFlowMode = $enableSingleFlowMode;
        $this->_flowExecution = &new Piece_Flow_Continuation_FlowExecution();
    }

    // }}}
    // {{{ addFlow()

    /**
     * Adds a flow definition to the Piece_Flow_Continuation object.
     *
     * @param string  $flowID
     * @param mixed   $source
     * @param boolean $isExclusive
     * @throws PIECE_FLOW_ERROR_ALREADY_EXISTS
     */
    function addFlow($flowID, $source, $isExclusive = false)
    {
        if ($this->_enableSingleFlowMode && count($this->_flowDefinitions)) {
            Piece_Flow_Error::push(PIECE_FLOW_ERROR_ALREADY_EXISTS,
                                   'A flow definition already exists in the continuation object.'
                                   );
            return;
        }

        $this->_flowDefinitions[$flowID] = array('source' => $source,
                                                 'isExclusive' => $isExclusive
                                                 );
    }

    // }}}
    // {{{ invoke()

    /**
     * Invokes a flow and returns a flow execution ticket.
     *
     * @param mixed   &$payload
     * @param boolean $bindActionsWithFlowExecution
     * @return string
     */
    function invoke(&$payload, $bindActionsWithFlowExecution = false)
    {
        if ($this->_enableGC) {
            $this->_gc->setGCCallback(array(&$this->_flowExecution, 'disableFlowExecution'));
            $this->_gc->mark();
        }

        $this->_prepare();
        if (Piece_Flow_Error::hasErrors()) {
            return;
        }

        if (!$this->_isFirstTime) {
            $this->_continue($payload, $bindActionsWithFlowExecution);
        } else {
            $this->_start($payload);
        }

        if (Piece_Flow_Error::hasErrors()) {
            return;
        }

        if ($this->_enableGC && !$this->_isExclusive()) {
            $this->_gc->update($this->_activeFlowExecutionTicket);
        }

        if ($bindActionsWithFlowExecution) {
            $flow = &$this->_flowExecution->getActiveFlow();
            $flow->clearPayload();
            $this->_prepareContext();
            $flow->setAttribute('_actionInstances', Piece_Flow_Action_Factory::getInstances());
        }

        $GLOBALS['PIECE_FLOW_Continuation_Server_ActiveInstances'][] = &$this;
        if (!$GLOBALS['PIECE_FLOW_Continuation_Server_ShutdownRegistered']) {
            $GLOBALS['PIECE_FLOW_Continuation_Server_ShutdownRegistered'] = true;
            register_shutdown_function(array(__CLASS__, 'shutdown'));
        }

        return $this->_activeFlowExecutionTicket;
    }

    // }}}
    // {{{ getView()

    /**
     * Gets an appropriate view string which corresponding to the current
     * state.
     *
     * @return string
     * @throws PIECE_FLOW_ERROR_INVALID_OPERATION
     */
    function getView()
    {
        if (!$this->_flowExecution->activated()) {
            Piece_Flow_Error::push(PIECE_FLOW_ERROR_INVALID_OPERATION,
                                   __FUNCTION__ . ' method must be called after starting/continuing flows.'
                                   );
            return;
        }

        $flow = &$this->_flowExecution->getActiveFlow();
        return $flow->getView();
    }

    // }}}
    // {{{ setEventNameCallback()

    /**
     * Sets a callback for getting an event name.
     *
     * @param callback $callback
     */
    function setEventNameCallback($callback)
    {
        $this->_eventNameCallback = $callback;
    }

    // }}}
    // {{{ setFlowExecutionTicketCallback()

    /**
     * Sets a callback for getting a flow execution ticket.
     *
     * @param callback $callback
     */
    function setFlowExecutionTicketCallback($callback)
    {
        $this->_flowExecutionTicketCallback = $callback;
    }

    // }}}
    // {{{ setFlowNameCallback()

    /**
     * Sets a callback for getting a flow ID.
     *
     * @param callback $callback
     * @deprecated Method deprecated in Release 1.15.0
     */
    function setFlowNameCallback($callback)
    {
        $this->setFlowIDCallback($callback);
    }

    // }}}
    // {{{ setCacheDirectory()

    /**
     * Sets the cache directory for the flow definitions.
     *
     * @param string $cacheDirectory
     */
    function setCacheDirectory($cacheDirectory)
    {
        $this->_cacheDirectory = $cacheDirectory;
    }

    // }}}
    // {{{ shutdown()

    /**
     * Shutdown the continuation server for next events.
     *
     * @static
     */
    function shutdown()
    {
        for ($i = 0, $count = count($GLOBALS['PIECE_FLOW_Continuation_Server_ActiveInstances']); $i < $count; ++$i) {
            $instance = &$GLOBALS['PIECE_FLOW_Continuation_Server_ActiveInstances'][$i];
            if (!is_a($instance, __CLASS__)) {
                unset($GLOBALS['PIECE_FLOW_Continuation_Server_ActiveInstances'][$i]);
                continue;
            }
            $instance->clear();
        }
    }

    // }}}
    // {{{ clear()

    /**
     * Clears some properties for the next use.
     */
    function clear()
    {
        if ($this->_flowExecution->hasFlowExecution($this->_activeFlowExecutionTicket)
            && !$this->_enableSingleFlowMode
            ) {
            $flow = &$this->_flowExecution->getActiveFlow();
            if ($flow->isFinalState()) {
                $this->_flowExecution->removeFlowExecution($this->_activeFlowExecutionTicket, $this->_activeFlowID);
            }
        }

        $this->_isFirstTime = null;
        $this->_activeFlowID = null;
        $this->_activeFlowExecutionTicket = null;
        $this->_flowExecution->inactivateFlowExecution();
        if ($this->_enableGC) {
            $this->_gc->sweep();
        }
    }

    // }}}
    // {{{ createService()

    /**
     * Creates a new Piece_Flow_Continuation_Service object for client use.
     */
    function &createService()
    {
        $service = &new Piece_Flow_Continuation_Service($this->_flowExecution);
        return $service;
    }

    // }}}
    // {{{ setActionDirectory()

    /**
     * Sets a directory as the action directory.
     *
     * @param string $actionDirectory
     * @since Method available since Release 1.15.0
     */
    function setActionDirectory($actionDirectory)
    {
        $this->_actionDirectory = $actionDirectory;
    }

    // }}}
    // {{{ setUseContext()

    /**
     * Sets whether or not the continuation server use the context by flow
     * execution ticket.
     *
     * @param boolean $useContext
     * @since Method available since Release 1.15.0
     */
    function setUseContext($useContext)
    {
        $this->_useContext = $useContext;
    }

    // }}}
    // {{{ setFlowIDCallback()

    /**
     * Sets a callback for getting a flow ID.
     *
     * @param callback $callback
     * @since Method available since Release 1.15.0
     */
    function setFlowIDCallback($callback)
    {
        $this->_flowIDCallback = $callback;
    }

    // }}}
    // {{{ setConfigDirectory()

    /**
     * Sets the config directory for the flow definitions.
     *
     * @param string $configDirectory
     * @since Method available since Release 1.15.0
     */
    function setConfigDirectory($configDirectory)
    {
        $this->_configDirectory = $configDirectory;
    }

    // }}}
    // {{{ setConfigExtension()

    /**
     * Sets the extension for the flow definitions.
     *
     * @param string $configExtension
     * @since Method available since Release 1.15.0
     */
    function setConfigExtension($configExtension)
    {
        $this->_configExtension = $configExtension;
    }

    // }}}
    // {{{ getActiveFlowID()

    /**
     * Gets the flow ID for the active flow execution.
     *
     * @return mixed
     * @since Method available since Release 1.15.0
     */
    function getActiveFlowID()
    {
        return $this->_activeFlowID;
    }

    // }}}
    // {{{ getActiveFlowSource()

    /**
     * Gets the flow source for the active flow execution.
     *
     * @return mixed
     * @since Method available since Release 1.15.0
     */
    function getActiveFlowSource()
    {
        return $this->_flowDefinitions[$this->_activeFlowID]['source'];
    }

    /**#@-*/

    /**#@+
     * @access private
     */

    // }}}
    // {{{ _generateFlowExecutionTicket()

    /**
     * Generates a flow execution ticket.
     */
    function _generateFlowExecutionTicket()
    {
        return sha1(uniqid(mt_rand(), true));
    }

    // }}}
    // {{{ _prepare()

    /**
     * Prepares a flow execution ticket, a flow ID, and whether the
     * flow invocation is the first time or not.
     *
     * @throws PIECE_FLOW_ERROR_FLOW_ID_NOT_GIVEN
     */
    function _prepare()
    {
        $currentFlowExecutionTicket = call_user_func($this->_flowExecutionTicketCallback);
        if ($this->_flowExecution->hasFlowExecution($currentFlowExecutionTicket)) {
            $registeredFlowID = $this->_flowExecution->getFlowID($currentFlowExecutionTicket);

            if (!$this->_enableSingleFlowMode) {
                $flowID = $this->_getFlowID();
                if (is_null($flowID) || !strlen($flowID)) {
                    Piece_Flow_Error::push(PIECE_FLOW_ERROR_FLOW_ID_NOT_GIVEN,
                                           'A flow ID must be given in this case.'
                                           );
                    return;
                }

                if ($flowID != $registeredFlowID) {
                    Piece_Flow_Error::push(PIECE_FLOW_ERROR_FLOW_ID_NOT_GIVEN,
                                           'The given flow ID is different from the registerd flow ID.'
                                           );
                    return;
                }
            }

            $this->_activeFlowID = $registeredFlowID;
            $this->_isFirstTime = false;
            $this->_activeFlowExecutionTicket = $currentFlowExecutionTicket;
        } else {
            $flowID = $this->_getFlowID();
            if (is_null($flowID) || !strlen($flowID)) {
                Piece_Flow_Error::push(PIECE_FLOW_ERROR_FLOW_ID_NOT_GIVEN,
                                       'A flow ID must be given in this case.'
                                       );
                return;
            }

            if ($this->_flowExecution->hasExclusiveFlowExecution($flowID)) {
                trigger_error("Another flow execution of the current flow [ $flowID ] already exists in the flow executions. Starting a new flow execution.",
                              E_USER_WARNING
                              );
                $this->_flowExecution->removeFlowExecution($this->_flowExecution->getFlowExecutionTicketByFlowID($flowID), $flowID);
            }

            $this->_activeFlowID = $flowID;
            $this->_isFirstTime = true;
        }
    }

    // }}}
    // {{{ _continue()

    /**
     * Continues a flow execution.
     *
     * @param mixed   &$payload
     * @param boolean $bindActionsWithFlowExecution
     * @throws PIECE_FLOW_ERROR_FLOW_EXECUTION_EXPIRED
     */
    function _continue(&$payload, $bindActionsWithFlowExecution)
    {
        if ($this->_enableGC) {
            if ($this->_gc->isMarked($this->_activeFlowExecutionTicket)) {
                $this->_flowExecution->removeFlowExecution($this->_activeFlowExecutionTicket, $this->_activeFlowID);
                Piece_Flow_Error::push(PIECE_FLOW_ERROR_FLOW_EXECUTION_EXPIRED,
                                       'The flow execution for the given flow execution ticket has expired.'
                                       );
                return;
            }
        }

        $this->_flowExecution->activateFlowExecution($this->_activeFlowExecutionTicket, $this->_activeFlowID);
        $flow = &$this->_flowExecution->getActiveFlow();
        $flow->setPayload($payload);
        $this->_prepareContext();

        if ($bindActionsWithFlowExecution) {
            Piece_Flow_Action_Factory::setInstances($flow->getAttribute('_actionInstances'));
        }

        $flow->triggerEvent(call_user_func($this->_eventNameCallback));
    }

    // }}}
    // {{{ _start()

    /**
     * Starts a flow execution.
     *
     * @param mixed &$payload
     * @return string
     * @throws PIECE_FLOW_ERROR_NOT_FOUND
     */
    function _start(&$payload)
    {
        if (!array_key_exists($this->_activeFlowID, $this->_flowDefinitions)) {
            Piece_Flow_Error::push(PIECE_FLOW_ERROR_NOT_FOUND,
                                   "The flow ID [ {$this->_activeFlowID} ] not found in the flow definitions."
                                   );
            return;
        }

        $flow = &new Piece_Flow();
        $flow->configure($this->_flowDefinitions[$this->_activeFlowID]['source'],
                         null,
                         $this->_cacheDirectory,
                         $this->_actionDirectory,
                         $this->_configDirectory,
                         $this->_configExtension
                         );
        if (Piece_Flow_Error::hasErrors()) {
            return;
        }

        while (true) {
            $flowExecutionTicket = $this->_generateFlowExecutionTicket();
            if (!$this->_flowExecution->hasFlowExecution($flowExecutionTicket)) {
                $this->_flowExecution->addFlowExecution($flowExecutionTicket, $flow, $this->_activeFlowID);
                if ($this->_isExclusive()) {
                    $this->_flowExecution->markFlowExecutionAsExclusive($flowExecutionTicket, $this->_activeFlowID);
                }

                break;
            }
        }

        $this->_flowExecution->activateFlowExecution($flowExecutionTicket, $this->_activeFlowID);
        $this->_activeFlowExecutionTicket = $flowExecutionTicket;
        $flow->setPayload($payload);
        $this->_prepareContext();
        $flow->start();
        if (Piece_Flow_Error::hasErrors()) {
            return;
        }

        return $flowExecutionTicket;
    }

    // }}}
    // {{{ _getFlowID()

    /**
     * Gets a flow ID which will be started or continued.
     *
     * @return string
     */
    function _getFlowID()
    {
        if (!$this->_enableSingleFlowMode) {
            return call_user_func($this->_flowIDCallback);
        } else {
            $flowIDs = array_keys($this->_flowDefinitions);
            return $flowIDs[0];
        }
    }

    // }}}
    // {{{ _isExclusive()

    /**
     * Checks whether the curent flow execution is exclusive or not.
     *
     * @return boolean
     */
    function _isExclusive()
    {
        if (!$this->_enableSingleFlowMode) {
            return $this->_flowDefinitions[$this->_activeFlowID]['isExclusive'];
        } else {
            return true;
        }
    }

    // }}}
    // {{{ _prepareContext()

    /**
     * Prepares the context by flow execution ticket.
     *
     * @since Method available since Release 1.15.0
     */
    function _prepareContext()
    {
        if ($this->_useContext) {
            Piece_Flow_Action_Factory::setContextID($this->_activeFlowExecutionTicket);
        } else {
            Piece_Flow_Action_Factory::clearContextID();
        }
    }

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
