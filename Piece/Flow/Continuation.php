<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * PHP versions 4 and 5
 *
 * Copyright (c) 2006, KUBO Atsuhiro <iteman2002@yahoo.co.jp>
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
 * @author     KUBO Atsuhiro <iteman2002@yahoo.co.jp>
 * @copyright  2006 KUBO Atsuhiro <iteman2002@yahoo.co.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    SVN: $Id$
 * @link       http://iteman.typepad.jp/piece/
 * @since      File available since Release 1.0.0
 */

require_once 'Piece/Flow.php';
require_once 'Piece/Flow/Error.php';
require_once 'PEAR/ErrorStack.php';

// {{{ GLOBALS

$GLOBALS['PIECE_FLOW_Continuation_Active_Instances'] = array();
$GLOBALS['PIECE_FLOW_Continuation_Shutdown_Registered'] = false;

// }}}
// {{{ Piece_Flow_Continuation

/**
 * The continuation server for the Piece_Flow package.
 *
 * @package    Piece_Flow
 * @author     KUBO Atsuhiro <iteman2002@yahoo.co.jp>
 * @copyright  2006 KUBO Atsuhiro <iteman2002@yahoo.co.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @link       http://iteman.typepad.jp/piece/
 * @since      Class available since Release 1.0.0
 */
class Piece_Flow_Continuation
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
    var $_flowExecutions = array();
    var $_flowExecutionTicketCallback;
    var $_flowNameCallback;
    var $_eventNameCallback;
    var $_exclusiveFlows = array();
    var $_flowExecutionTicket;
    var $_isFirstTime;
    var $_flowName;
    var $_currentFlowExecutionTicket;
    var $_activated = false;

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
     */
    function Piece_Flow_Continuation($enableSingleFlowMode = false)
    {
        $this->_enableSingleFlowMode = $enableSingleFlowMode;
    }

    // }}}
    // {{{ addFlow()

    /**
     * Adds a flow definition to the Piece_Flow_Continuation object.
     *
     * @param string  $name
     * @param string  $file
     * @param boolean $isExclusive
     * @throws PEAR_ErrorStack
     */
     function addFlow($name, $file, $isExclusive = false)
     {
         if ($this->_enableSingleFlowMode && count(array_keys($this->_flowDefinitions))) {
             return Piece_Flow_Error::raiseError(PIECE_FLOW_ERROR_ALREADY_EXISTS,
                                                 'A flow definition already exists in the continuation object.'
                                                 );
         }

         $this->_flowDefinitions[$name] = array('file' => $file,
                                                'isExclusive' => $isExclusive
                                                );

         $return = null;
         return $return;
    }

    // }}}
    // {{{ invoke()

    /**
     * Invokes a flow and returns a flow execution ticket.
     *
     * @param mixed &$payload
     * @return string
     * @throws PEAR_ErrorStack
     */
    function invoke(&$payload)
    {
        $resultOfPrepare = $this->_prepare();
        if (Piece_Flow_Error::isError($resultOfPrepare)) {
            return $resultOfPrepare;
        }

        if (!$this->_isFirstTime) {
            $currentFlowExecutionTicket = $this->_continue($payload);
        } else {
            $currentFlowExecutionTicket = $this->_start($payload);
        }

        if (Piece_Flow_Error::isError($currentFlowExecutionTicket)) {
            return $currentFlowExecutionTicket;
        }

        $this->_activated = true;
        $this->_currentFlowExecutionTicket = $currentFlowExecutionTicket;

        $GLOBALS['PIECE_FLOW_Continuation_Active_Instances'][] = &$this;
        if (!$GLOBALS['PIECE_FLOW_Continuation_Shutdown_Registered']) {
            $GLOBALS['PIECE_FLOW_Continuation_Shutdown_Registered'] = true;
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
     * @throws PEAR_ErrorStack
     */
    function getView()
    {
        if (!$this->_activated()) {
            PEAR_ErrorStack::staticPushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));
            $error = Piece_Flow_Error::raiseError(PIECE_FLOW_ERROR_INVALID_OPERATION,
                                                  __FUNCTION__ . ' method must be called after starting/continuing flows.'
                                                  );
            PEAR_ErrorStack::staticPopCallback();
            return $error;
        }

        return $this->_flowExecutions[$this->_currentFlowExecutionTicket]->getView();
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
    // {{{ setAttribute()

    /**
     * Sets an attribute for this flow.
     *
     * @param string $name
     * @param mixed  $value
     * @throws PEAR_ErrorStack
     */
    function setAttribute($name, $value)
    {
        if (!$this->_activated()) {
            PEAR_ErrorStack::staticPushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));
            $error = Piece_Flow_Error::raiseError(PIECE_FLOW_ERROR_INVALID_OPERATION,
                                                  __FUNCTION__ . ' method must be called after starting/continuing flows.'
                                                  );
            PEAR_ErrorStack::staticPopCallback();
            return $error;
        }

        $this->_flowExecutions[$this->_currentFlowExecutionTicket]->setAttribute($name, $value);
    }

    // }}}
    // {{{ hasAttribute()

    /**
     * Returns whether this flow has an attribute with a given name.
     *
     * @param string $name
     * @return boolean
     * @throws PEAR_ErrorStack
     */
    function hasAttribute($name)
    {
        if (!$this->_activated()) {
            PEAR_ErrorStack::staticPushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));
            $error = Piece_Flow_Error::raiseError(PIECE_FLOW_ERROR_INVALID_OPERATION,
                                                  __FUNCTION__ . ' method must be called after starting/continuing flows.'
                                                  );
            PEAR_ErrorStack::staticPopCallback();
            return $error;
        }

        return $this->_flowExecutions[$this->_currentFlowExecutionTicket]->hasAttribute($name);
    }

    // }}}
    // {{{ getAttribute()

    /**
     * Gets an attribute for this flow.
     *
     * @param string $name
     * @return mixed
     * @throws PEAR_ErrorStack
     */
    function getAttribute($name)
    {
        if (!$this->_activated()) {
            PEAR_ErrorStack::staticPushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));
            $error = Piece_Flow_Error::raiseError(PIECE_FLOW_ERROR_INVALID_OPERATION,
                                                  __FUNCTION__ . ' method must be called after starting/continuing flows.'
                                                  );
            PEAR_ErrorStack::staticPopCallback();
            return $error;
        }

        return $this->_flowExecutions[$this->_currentFlowExecutionTicket]->getAttribute($name);
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
        $count = count($GLOBALS['PIECE_FLOW_Continuation_Active_Instances']);
        for ($i = 0; $i < $count; ++$i) {
            $instance = &$GLOBALS['PIECE_FLOW_Continuation_Active_Instances'][$i];
            if (!is_a($instance, __CLASS__)) {
                unset($GLOBALS['PIECE_FLOW_Continuation_Active_Instances'][$i]);
                continue;
            }
            $instance->clear();
        }
    }

    // }}}
    // {{{ clear()

    /**
     * Clears some properties for next events.
     */
    function clear()
    {
        if (array_key_exists($this->_flowExecutionTicket, $this->_flowExecutions)
            && $this->_flowExecutions[$this->_currentFlowExecutionTicket]->isFinalState()
            ) {
            unset($this->_flowExecutions[$this->_currentFlowExecutionTicket]);
            if (array_key_exists($this->_flowName, $this->_exclusiveFlows)) {
                unset($this->_exclusiveFlows[$this->_flowName]);
            }
        }

        $this->_flowExecutionTicket = null;
        $this->_isFirstTime = null;
        $this->_flowName = null;
        $this->_currentFlowExecutionTicket = null;
        $this->_activated = false;
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
     * Prepares the flow execution ticket, the flow name, and whether the
     * flow invocation is the first time or not.
     *
     * @throws PEAR_ErrorStack
     */
    function _prepare()
    {
        if ($this->_enableSingleFlowMode) {
            $flowExecutionTickets = array_keys($this->_flowExecutions);
            if (count($flowExecutionTickets)) {
                $this->_isFirstTime = false;
                $this->_flowExecutionTicket = $flowExecutionTickets[0];
            } else {
                $this->_isFirstTime = true;
                $flowNames = array_keys($this->_flowDefinitions);
                $this->_flowName = $flowNames[0];
            }
        } else {
            $this->_flowExecutionTicket = call_user_func($this->_flowExecutionTicketCallback);
            if ($this->_hasFlowExecutionTicket($this->_flowExecutionTicket)) {
                $this->_isFirstTime = false;
            } else {
                $this->_flowName = call_user_func($this->_flowNameCallback);
                if (is_null($this->_flowName) || !strlen($this->_flowName)) {
                    return Piece_Flow_Error::raiseError(PIECE_FLOW_ERROR_NOT_GIVEN,
                                                        'A flow name must be given in this case.'
                                                        );
                }

                if (array_key_exists($this->_flowName, $this->_exclusiveFlows)) {
                    $this->_isFirstTime = false;
                    $this->_flowExecutionTicket = $this->_exclusiveFlows[$this->_flowName];
                } else {
                    $this->_isFirstTime = true;
                }
            }
        }
    }

    // }}}
    // {{{ _continue()

    /**
     * Continues with the current continuation.
     *
     * @param mixed &$payload
     * @throws PEAR_ErrorStack
     */
    function _continue(&$payload)
    {
        $this->_flowExecutions[$this->_flowExecutionTicket]->setPayload($payload);
        $result = $this->_flowExecutions[$this->_flowExecutionTicket]->triggerEvent(call_user_func($this->_eventNameCallback));
        if (Piece_Flow_Error::isError($result)) {
            return $result;
        }

        return $this->_flowExecutionTicket;
    }

    // }}}
    // {{{ _start()

    /**
     * Starts a new flow.
     *
     * @param mixed &$payload
     * @return string
     * @throws PEAR_ErrorStack
     */
    function _start(&$payload)
    {
        if (!array_key_exists($this->_flowName, $this->_flowDefinitions)) {
            return Piece_Flow_Error::raiseError(PIECE_FLOW_ERROR_NOT_FOUND,
                                                "The flow name [ {$this->_flowName} ] not found in the flow definitions."
                                                );
        }

        $flow = &new Piece_Flow();
        $result = &$flow->configure($this->_flowDefinitions[$this->_flowName]['file'],
                                    null,
                                    $this->_cacheDirectory
                                    );
        if (Piece_Flow_Error::isError($result)) {
            return $result;
        }

        $flow->setPayload($payload);
        $flow->start();

        while (true) {
            $flowExecutionTicket = $this->_generateFlowExecutionTicket();
            if (!$this->_hasFlowExecutionTicket($flowExecutionTicket)) {
                $this->_flowExecutions[$flowExecutionTicket] = &$flow;
                break;
            }
        }

        if (!$this->_enableSingleFlowMode
            && $this->_flowDefinitions[$this->_flowName]['isExclusive']
            ) {
            $this->_exclusiveFlows[$this->_flowName] = $flowExecutionTicket;
        }

        return $flowExecutionTicket;
    }

    // }}}
    // {{{ _activated()

    /**
     * Returns whether the current flow has activated or not.
     *
     * @return boolean
     */
    function _activated()
    {
        return $this->_activated;
    }

    // }}}
    // {{{ _hasFlowExecutionTicket()

    /**
     * Returns whether the current flow has the flow execution ticket or not.
     *
     * @param string $flowExecutionTicket
     * @return boolean
     */
    function _hasFlowExecutionTicket($flowExecutionTicket)
    {
        return array_key_exists($flowExecutionTicket, $this->_flowExecutions);
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
