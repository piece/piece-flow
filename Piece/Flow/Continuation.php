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
 * @since      File available since Release 0.1.0
 */

require_once 'Piece/Flow.php';
require_once 'Piece/Flow/Error.php';

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
 * @since      Class available since Release 0.1.0
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
    var $_useLinearFlowControl;
    var $_cacheDirectory;
    var $_flows = array();
    var $_flowExecutionTicket;
    var $_flowExecutionTicketCallback;
    var $_flowNameCallback;
    var $_eventNameCallback;
    var $_exclusiveFlows = array();
    var $_isFirstTime;
    var $_flowName;

    /**#@-*/

    /**#@+
     * @access public
     */

    // }}}
    // {{{ constructor

    /**
     * Sets whether the Piece_Flow_Continuation object uses linear flow
     * control.
     *
     * @param boolean $useLinearFlowControl
     */
    function Piece_Flow_Continuation($useLinearFlowControl = false)
    {
        $this->_useLinearFlowControl = $useLinearFlowControl;
    }

    // }}}
    // {{{ addFlow()

    /**
     * Adds a flow definition to the Piece_Flow_Continuation object.
     *
     * @param string  $name
     * @param string  $file
     * @param boolean $isExclusive
     */
    function &addFlow($name, $file, $isExclusive = false)
    {
        if ($this->_useLinearFlowControl && count(array_keys($this->_flowDefinitions))) {
            $error = &Piece_Flow_Error::raiseError(PIECE_FLOW_ERROR_ALREADY_EXISTS,
                                                   'A flow definition already exists in the continuation object.'
                                                   );
            return $error;
        }

        $this->_flowDefinitions[$name] = array('file' => $file,
                                               'isExclusive' => $isExclusive
                                               );

        $return = null;
        return $return;
    }

    // }}}
    // {{{ hasFlow()

    /**
     * Returns whether the Piece_Flow_Continuation object has a flow with the
     * given name.
     *
     * @param string $name
     * @return boolean
     */
    function hasFlow($name)
    {
        return array_key_exists($name, $this->_flowDefinitions);
    }

    // }}}
    // {{{ invoke()

    /**
     * Invokes a flow and returns a flow execution ticket.
     *
     * @return string
     * @throws PEAR_ErrorStack
     */
    function &invoke()
    {
        $this->_prepare();

        if (!$this->_isFirstTime) {
            $this->_continue();
        } else {
            $result = &$this->_start();
            if (Piece_Flow_Error::isError($result)) {
                return $result;
            }
        }

        return $this->_flowExecutionTicket;
    }

    // }}}
    // {{{ getView()

    /**
     * Gets an appropriate view string which corresponding to the current
     * state.
     *
     * @return string
     */
    function getView()
    {
        return $this->_flows[$this->_flowExecutionTicket]->getView();
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
     */
    function _prepare()
    {
        if ($this->_useLinearFlowControl) {
            $flowExecutionTickets = array_keys($this->_flows);
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
            if (array_key_exists($this->_flowExecutionTicket, $this->_flows)) {
                $this->_isFirstTime = false;
            } else {
                $this->_flowName = call_user_func($this->_flowNameCallback);
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
     */
    function _continue()
    {
        $this->_flows[$this->_flowExecutionTicket]->triggerEvent(call_user_func($this->_eventNameCallback));
    }

    // }}}
    // {{{ _start()

    /**
     * Starts a new flow.
     *
     * @throws PEAR_ErrorStack
     */
    function &_start()
    {
        if (!array_key_exists($this->_flowName, $this->_flowDefinitions)) {
            $error = &Piece_Flow_Error::raiseError(PIECE_FLOW_ERROR_NOT_FOUND,
                                                   "The flow name [ {$this->_flowName} ] not found in the flow definitions."
                                                   );
            return $error;
        }

        $flow = &new Piece_Flow();
        $result = &$flow->configure($this->_flowDefinitions[$this->_flowName]['file'],
                                    null,
                                    $this->_cacheDirectory
                                    );
        if (Piece_Flow_Error::isError($result)) {
            return $result;
        }

        $flow->start();

        while (true) {
            $flowExecutionTicket = $this->_generateFlowExecutionTicket();
            if (!array_key_exists($flowExecutionTicket, $this->_flows)) {
                $this->_flows[$flowExecutionTicket] = &$flow;
                $this->_flowExecutionTicket = $flowExecutionTicket;
                break;
            }
        }

        if (!$this->_useLinearFlowControl
            && $this->_flowDefinitions[$this->_flowName]['isExclusive']
            ) {
            $this->_exclusiveFlows[$this->_flowName] = $this->_flowExecutionTicket;
        }

        $return = null;
        return $return;
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
