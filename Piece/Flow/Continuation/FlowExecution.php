<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * PHP versions 4 and 5
 *
 * Copyright (c) 2007 KUBO Atsuhiro <iteman@users.sourceforge.net>,
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
 * @copyright  2007 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    SVN: $Id$
 * @since      File available since Release 1.14.0
 */

// {{{ Piece_Flow_Continuation_FlowExecution

/**
 * The container class for all flow executions in the continuation server.
 *
 * @package    Piece_Flow
 * @copyright  2007 KUBO Atsuhiro <iteman@users.sourceforge.net>
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
    var $_exclusiveFlowExecutionTicketsByFlowID = array();
    var $_exclusiveFlowIDsByFlowExecutionTicket = array();
    var $_activeFlowID;

    /**#@-*/

    /**#@+
     * @access public
     */

    // }}}
    // {{{ disableFlowExecution()

    /**
     * Disables the flow execution for the given flow execution ticket.
     *
     * @param string $flowExecutionTicket
     */
    function disableFlowExecution($flowExecutionTicket)
    {
        $this->_flowExecutions[$flowExecutionTicket]['flow'] = null;
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

        return $this->_flowExecutions[ $this->_activeFlowExecutionTicket ]['flow']->checkLastEvent();
    }

    // }}}
    // {{{ activateFlowExecution()

    /**
     * Activates the flow execution which is indicated by the given flow
     * execution ticket.
     *
     * @param string $flowExecutionTicket
     * @param string $flowID
     */
    function activateFlowExecution($flowExecutionTicket, $flowID)
    {
        $this->_activeFlowExecutionTicket = $flowExecutionTicket;
        $this->_activeFlowID = $flowID;
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
     * @param string $flowID
     */
    function removeFlowExecution($flowExecutionTicket, $flowID)
    {
        $this->_flowExecutions[$flowExecutionTicket]['flow'] = null;
        $this->_flowExecutions[$flowExecutionTicket]['id'] = null;
        unset($this->_flowExecutions[$flowExecutionTicket]);
        if ($this->hasExclusiveFlowExecution($flowID)) {
            unset($this->_exclusiveFlowExecutionTicketsByFlowID[$flowID]);
            unset($this->_exclusiveFlowIDsByFlowExecutionTicket[$flowExecutionTicket]);
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
        $this->_activeFlowID = null;
    }

    // }}}
    // {{{ addFlowExecution()

    /**
     * Adds a Piece_Flow object with the given flow execution ticket to
     * the list of flow executions.
     *
     * @param string     $flowExecutionTicket
     * @param Piece_Flow &$flow
     * @param string     $flowID
     */
    function addFlowExecution($flowExecutionTicket, &$flow, $flowID)
    {
        $this->_flowExecutions[$flowExecutionTicket] = array('flow' => &$flow,
                                                             'id' => $flowID
                                                             );
    }

    // }}}
    // {{{ markFlowExecutionAsExclusive()

    /**
     * Marks a flow execution which is indicated by the given flow execution
     * ticket and flow ID as exclusive.
     *
     * @param string $flowExecutionTicket
     * @param string $flowID
     */
    function markFlowExecutionAsExclusive($flowExecutionTicket, $flowID)
    {
        $this->_exclusiveFlowExecutionTicketsByFlowID[$flowID] = $flowExecutionTicket;
        $this->_exclusiveFlowIDsByFlowExecutionTicket[$flowExecutionTicket] = $flowID;
    }

    // }}}
    // {{{ hasExclusiveFlowExecution()

    /**
     * Returns whether the given flow ID has the exclusive flow execution
     * or not.
     *
     * @param string $flowID
     * @return boolean
     */
    function hasExclusiveFlowExecution($flowID)
    {
        return array_key_exists($flowID, $this->_exclusiveFlowExecutionTicketsByFlowID);
    }

    // }}}
    // {{{ getActiveFlow()

    /**
     * Gets the active Piece_Flow object.
     *
     * @return Piece_Flow
     */
    function &getActiveFlow()
    {
        return $this->_flowExecutions[ $this->_activeFlowExecutionTicket ]['flow'];
    }

    // }}}
    // {{{ getFlowID()

    /**
     * Gets the flow ID by the given flow execution ticket.
     *
     * @param string $flowExecutionTicket
     * @return string
     * @since Method available since Release 1.15.0
     */
    function getFlowID($flowExecutionTicket)
    {
        return $this->_flowExecutions[$flowExecutionTicket]['id'];
    }

    // }}}
    // {{{ getFlowExecutionTicketByFlowID()

    /**
     * Gets a flow execution ticket by the given flow ID.
     * This method will be used for getting flow execution ticket else than
     * the active flow execution.
     * This method is only available if the flow execution is exclusive.
     *
     * @param string $flowID
     * @return string
     * @since Method available since Release 1.15.0
     */
    function getFlowExecutionTicketByFlowID($flowID)
    {
        return @$this->_exclusiveFlowExecutionTicketsByFlowID[$flowID];
    }

    // }}}
    // {{{ getActiveFlowID()

    /**
     * Gets the flow ID for the active flow execution.
     *
     * @return string
     * @since Method available since Release 1.15.0
     */
    function getActiveFlowID()
    {
        return $this->_activeFlowID;
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
