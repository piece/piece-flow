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

// {{{ Piece_Flow_Continuation_FlowExecution

/**
 * The container class for all flow executions in the continuation server.
 *
 * @package    Piece_Flow
 * @copyright  2006-2007 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @since      Class available since Release 1.14.0
 */
class Piece_Flow_Continuation_FlowExecution
{

    // {{{ properties

    /**#@+
     * @access public
     */

    /**#@-*/

    /**#@+
     * @access private
     */

    var $_flowExecutions = array();
    var $_activeFlowExecutionTicket;
    var $_activated = false;
    var $_exclusiveFlowExecutionTicketsByFlowName = array();
    var $_exclusiveFlowNamesByFlowExecutionTicket = array();
    var $_activeFlowName;

    /**#@-*/

    /**#@+
     * @access public
     */

    // }}}
    // {{{ getFlowExecutionTicketByFlowName()

    /**
     * Gets a flow execution ticket by the given flow name.
     * This method will be used for getting flow execution ticket else than
     * the current flow execution.
     * This method is only available if the flow execution is exclusive.
     *
     * @param string $flowName
     * @return string
     */
    function getFlowExecutionTicketByFlowName($flowName)
    {
        return @$this->_exclusiveFlowExecutionTicketsByFlowName[$flowName];
    }

    // }}}
    // {{{ disableFlowExecution()

    /**
     * Disables the flow execution for the given flow execution ticket.
     *
     * @param string $flowExecutionTicket
     */
    function disableFlowExecution($flowExecutionTicket)
    {
        $this->_flowExecutions[$flowExecutionTicket] = null;
    }

    // }}}
    // {{{ checkLastEvent()

    /**
     * Returns whether the last event which is given by a user is valid or
     * not.
     *
     * @return boolean
     */
    function checkLastEvent()
    {
        if (!$this->activated()) {
            return true;
        }

        return $this->_flowExecutions[ $this->_activeFlowExecutionTicket ]->checkLastEvent();
    }

    // }}}
    // {{{ activateFlowExecution()

    /**
     * Activates the flow execution which is indicated by the given flow
     * execution ticket.
     *
     * @param string $flowExecutionTicket
     * @param string $flowName
     */
    function activateFlowExecution($flowExecutionTicket, $flowName)
    {
        $this->_activeFlowExecutionTicket = $flowExecutionTicket;
        $this->_activeFlowName = $flowName;
        $this->_activated = true;
    }

    // }}}
    // {{{ activated()

    /**
     * Returns whether the flow execution has activated or not.
     *
     * @return boolean
     */
    function activated()
    {
        return $this->_activated;
    }

    // }}}
    // {{{ hasFlowExecution()

    /**
     * Returns whether or not a flow execution exists in the flow executions.
     *
     * @param string $flowExecutionTicket
     * @return boolean
     */
    function hasFlowExecution($flowExecutionTicket)
    {
        return array_key_exists($flowExecutionTicket, $this->_flowExecutions);
    }

    // }}}
    // {{{ removeFlowExecution()

    /**
     * Removes a flow execution.
     *
     * @param string $flowExecutionTicket
     * @param string $flowName
     */
    function removeFlowExecution($flowExecutionTicket, $flowName)
    {
        $this->_flowExecutions[$flowExecutionTicket] = null;
        unset($this->_flowExecutions[$flowExecutionTicket]);
        if ($this->hasExclusiveFlowExecution($flowName)) {
            unset($this->_exclusiveFlowExecutionTicketsByFlowName[$flowName]);
            unset($this->_exclusiveFlowNamesByFlowExecutionTicket[$flowExecutionTicket]);
        }
    }

    // }}}
    // {{{ inactivateFlowExecution()

    /**
     * Inactivates the flow execution.
     */
    function inactivateFlowExecution()
    {
        $this->_activated = false;
        $this->_activeFlowExecutionTicket = null;
        $this->_activeFlowName = null;
    }

    // }}}
    // {{{ addFlowExecution()

    /**
     * Adds a Piece_Flow object with the given flow execution ticket to
     * the list of flow executions.
     *
     * @param string $flowExecutionTicket
     */
    function addFlowExecution($flowExecutionTicket, &$flow)
    {
        $this->_flowExecutions[$flowExecutionTicket] = &$flow;
    }

    // }}}
    // {{{ markFlowExecutionAsExclusive()

    /**
     * Marks a flow execution which is indicated by the given flow execution
     * ticket and flow name as exclusive.
     *
     * @param string $flowExecutionTicket
     * @param string $flowName
     */
    function markFlowExecutionAsExclusive($flowExecutionTicket, $flowName)
    {
        $this->_exclusiveFlowExecutionTicketsByFlowName[$flowName] = $flowExecutionTicket;
        $this->_exclusiveFlowNamesByFlowExecutionTicket[$flowExecutionTicket] = $flowName;
    }

    // }}}
    // {{{ hasExclusiveFlowExecution()

    /**
     * Returns whether the given flow name has the exclusive flow execution
     * or not.
     *
     * @param string $flowName
     * @return boolean
     */
    function hasExclusiveFlowExecution($flowName)
    {
        return array_key_exists($flowName, $this->_exclusiveFlowExecutionTicketsByFlowName);
    }

    // }}}
    // {{{ getFlow()

    /**
     * Gets the active Piece_Flow object.
     *
     * @return Piece_Flow
     */
    function &getFlow()
    {
        return $this->_flowExecutions[ $this->_activeFlowExecutionTicket ];
    }

    // }}}
    // {{{ getActiveFlowName()

    /**
     * Gets the flow name for the active flow execution.
     *
     * @return string
     */
    function getActiveFlowName()
    {
        return $this->_activeFlowName;
    }

    /**#@-*/

    /**#@+
     * @access private
     */

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
