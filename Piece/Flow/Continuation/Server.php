<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * PHP versions 4 and 5
 *
 * Copyright (c) 2006-2007 KUBO Atsuhiro <iteman@users.sourceforge.net>,
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
 * @copyright  2006-2007 KUBO Atsuhiro <iteman@users.sourceforge.net>
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
 * @copyright  2006-2007 KUBO Atsuhiro <iteman@users.sourceforge.net>
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
    var $_flowNameCallback;
    var $_eventNameCallback;
    var $_isFirstTime;
    var $_currentFlowName;
    var $_currentFlowExecutionTicket;
    var $_gc;
    var $_enableGC = false;
    var $_flowExecution;
    var $_actionDirectory;

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
     * @param string  $name
     * @param mixed   $source
     * @param boolean $isExclusive
     * @throws PIECE_FLOW_ERROR_ALREADY_EXISTS
     */
    function addFlow($name, $source, $isExclusive = false)
    {
        if ($this->_enableSingleFlowMode && count($this->_flowDefinitions)) {
            Piece_Flow_Error::push(PIECE_FLOW_ERROR_ALREADY_EXISTS,
                                   'A flow definition already exists in the continuation object.'
                                   );
            return;
        }

        $this->_flowDefinitions[$name] = array('source' => $source,
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
     * @throws PIECE_FLOW_ERROR_NOT_GIVEN
     * @throws PIECE_FLOW_ERROR_NOT_FOUND
     * @throws PIECE_FLOW_ERROR_NOT_READABLE
     * @throws PIECE_FLOW_ERROR_INVALID_FORMAT
     * @throws PIECE_FLOW_ERROR_INVALID_OPERATION
     * @throws PIECE_FLOW_ERROR_FLOW_NAME_NOT_GIVEN
     * @throws PIECE_FLOW_ERROR_CANNOT_READ
     * @throws PIECE_FLOW_ERROR_FLOW_EXECUTION_EXPIRED
     */
    function invoke(&$payload, $bindActionsWithFlowExecution = false)
    {
        if ($this->_enableGC) {
            $this->_gc->setGCCallback(array(&$this->_flowExecution, 'disableFlowExecution'));
            $this->_gc->mark();
        }

        $this->_prepare();
        if (Piece_Flow_Error::hasErrors('exception')) {
            return;
        }

        if (!$this->_isFirstTime) {
            $this->_continue($payload, $bindActionsWithFlowExecution);
        } else {
            $this->_start($payload);
        }

        if (Piece_Flow_Error::hasErrors('exception')) {
            return;
        }

        if ($this->_enableGC && !$this->_isExclusive()) {
            $this->_gc->update($this->_currentFlowExecutionTicket);
        }

        if ($bindActionsWithFlowExecution) {
            $flow = &$this->_flowExecution->getFlow();
            $flow->clearPayload();
            $flow->setAttribute('_actionInstances', Piece_Flow_Action_Factory::getInstances());
        }

        $GLOBALS['PIECE_FLOW_Continuation_Server_ActiveInstances'][] = &$this;
        if (!$GLOBALS['PIECE_FLOW_Continuation_Server_ShutdownRegistered']) {
            $GLOBALS['PIECE_FLOW_Continuation_Server_ShutdownRegistered'] = true;
            register_shutdown_function(array(__CLASS__, 'shutdown'));
        }

        return $this->_currentFlowExecutionTicket;
    }

    // }}}
    // {{{ getView()

    /**
     * Gets an appropriate view string which corresponding to the current
     * state.
     *
     * @return string
     * @throws PIECE_FLOW_ERROR_INVALID_TRANSITION
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

        $flow = &$this->_flowExecution->getFlow();
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
     * Sets a callback for getting a flow name.
     *
     * @param callback $callback
     */
    function setFlowNameCallback($callback)
    {
        $this->_flowNameCallback = $callback;
    }

    // }}}
    // {{{ setCacheDirectory()

    /**
     * Sets a cache directory for the flow definitions.
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
        if ($this->_flowExecution->hasFlowExecution($this->_currentFlowExecutionTicket)
            && !$this->_enableSingleFlowMode
            ) {
            $flow = &$this->_flowExecution->getFlow();
            if ($flow->isFinalState()) {
                $this->_flowExecution->removeFlowExecution($this->_currentFlowExecutionTicket, $this->_currentFlowName);
            }
        }

        $this->_isFirstTime = null;
        $this->_currentFlowName = null;
        $this->_currentFlowExecutionTicket = null;
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
     */
    function setActionDirectory($actionDirectory)
    {
        $this->_actionDirectory = $actionDirectory;
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
     * Prepares a flow execution ticket, a flow name, and whether the
     * flow invocation is the first time or not.
     *
     * @throws PIECE_FLOW_ERROR_FLOW_NAME_NOT_GIVEN
     */
    function _prepare()
    {
        $currentFlowExecutionTicket = call_user_func($this->_flowExecutionTicketCallback);
        if ($this->_flowExecution->hasFlowExecution($currentFlowExecutionTicket)) {
            $flowName = $this->_getFlowName();
            if (!$this->_enableSingleFlowMode) {
                if (is_null($flowName) || !strlen($flowName)) {
                    Piece_Flow_Error::push(PIECE_FLOW_ERROR_FLOW_NAME_NOT_GIVEN,
                                           'A flow name must be given in this case.'
                                           );
                    return;
                }
            }

            $this->_currentFlowName = $flowName;
            $this->_isFirstTime = false;
            $this->_currentFlowExecutionTicket = $currentFlowExecutionTicket;
        } else {
            $flowName = $this->_getFlowName();
            if (is_null($flowName) || !strlen($flowName)) {
                Piece_Flow_Error::push(PIECE_FLOW_ERROR_FLOW_NAME_NOT_GIVEN,
                                       'A flow name must be given in this case.'
                                       );
                return;
            }

            if ($this->_flowExecution->hasExclusiveFlowExecution($flowName)) {
                Piece_Flow_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));
                Piece_Flow_Error::push(PIECE_FLOW_ERROR_ALREADY_EXISTS,
                                       "Another flow execution of the current flow [ $flowName ] already exists in the flow executions. Starting a new flow execution.",
                                       'warning'
                                       );
                Piece_Flow_Error::popCallback();
                $this->_flowExecution->removeFlowExecution($this->_flowExecution->getFlowExecutionTicketByFlowName($flowName), $flowName);
            }

            $this->_currentFlowName = $flowName;
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
     * @throws PIECE_FLOW_ERROR_CANNOT_INVOKE
     * @throws PIECE_FLOW_ERROR_ALREADY_SHUTDOWN
     * @throws PIECE_FLOW_ERROR_FLOW_EXECUTION_EXPIRED
     */
    function _continue(&$payload, $bindActionsWithFlowExecution)
    {
        if ($this->_enableGC) {
            if ($this->_gc->isMarked($this->_currentFlowExecutionTicket)) {
                $this->_flowExecution->removeFlowExecution($this->_currentFlowExecutionTicket, $this->_currentFlowName);
                Piece_Flow_Error::push(PIECE_FLOW_ERROR_FLOW_EXECUTION_EXPIRED,
                                       'The flow execution for the given flow execution ticket has expired.'
                                       );
                return;
            }
        }

        $this->_flowExecution->activateFlowExecution($this->_currentFlowExecutionTicket, $this->_currentFlowName);
        $flow = &$this->_flowExecution->getFlow();
        $flow->setPayload($payload);

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
     * @throws PIECE_FLOW_ERROR_NOT_READABLE
     * @throws PIECE_FLOW_ERROR_INVALID_FORMAT
     * @throws PIECE_FLOW_ERROR_PROTECTED_EVENT
     * @throws PIECE_FLOW_ERROR_PROTECTED_STATE
     * @throws PIECE_FLOW_ERROR_CANNOT_INVOKE
     * @throws PIECE_FLOW_ERROR_ALREADY_SHUTDOWN
     * @throws PIECE_FLOW_ERROR_CANNOT_READ
     */
    function _start(&$payload)
    {
        if (!array_key_exists($this->_currentFlowName, $this->_flowDefinitions)) {
            Piece_Flow_Error::push(PIECE_FLOW_ERROR_NOT_FOUND,
                                   "The flow name [ {$this->_currentFlowName} ] not found in the flow definitions."
                                   );
            return;
        }

        $flow = &new Piece_Flow();
        $flow->configure($this->_flowDefinitions[$this->_currentFlowName]['source'],
                         null,
                         $this->_cacheDirectory,
                         $this->_actionDirectory
                         );
        if (Piece_Flow_Error::hasErrors('exception')) {
            return;
        }

        while (true) {
            $flowExecutionTicket = $this->_generateFlowExecutionTicket();
            if (!$this->_flowExecution->hasFlowExecution($flowExecutionTicket)) {
                $this->_flowExecution->addFlowExecution($flowExecutionTicket, $flow);
                if ($this->_isExclusive()) {
                    $this->_flowExecution->markFlowExecutionAsExclusive($flowExecutionTicket, $this->_currentFlowName);
                }

                break;
            }
        }

        $this->_flowExecution->activateFlowExecution($flowExecutionTicket, $this->_currentFlowName);
        $this->_currentFlowExecutionTicket = $flowExecutionTicket;
        $flow->setPayload($payload);
        $flow->start();
        if (Piece_Flow_Error::hasErrors('exception')) {
            return;
        }

        return $flowExecutionTicket;
    }

    // }}}
    // {{{ _getFlowName()

    /**
     * Gets a flow name which will be started or continued.
     *
     * @return string
     */
    function _getFlowName()
    {
        if (!$this->_enableSingleFlowMode) {
            return call_user_func($this->_flowNameCallback);
        } else {
            $flowNames = array_keys($this->_flowDefinitions);
            return $flowNames[0];
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
            return $this->_flowDefinitions[$this->_currentFlowName]['isExclusive'];
        } else {
            return true;
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
?>
