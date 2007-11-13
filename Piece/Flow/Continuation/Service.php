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

require_once 'Piece/Flow/Error.php';

// {{{ Piece_Flow_Continuation_Service

/**
 * A service class which provides simple interfaces to access attributes of
 * the active flow object and to get some information from flow executions.
 *
 * @package    Piece_Flow
 * @copyright  2007 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @since      Class available since Release 1.14.0
 */
class Piece_Flow_Continuation_Service
{

    // {{{ properties

    /**#@+
     * @access public
     */

    /**#@-*/

    /**#@+
     * @access private
     */

    var $_flowExecution;

    /**#@-*/

    /**#@+
     * @access public
     */

    // }}}
    // {{{ constructor

    /**
     * Sets the active Piece_Flow object if the flow execution has activated
     * and the Piece_Flow_Continuation_FlowExecution object to the properties.
     *
     * @param Piece_Flow_Continuation_FlowExecution &$flowExecution
     */
    function Piece_Flow_Continuation_Service(&$flowExecution)
    {
        $this->_flowExecution = &$flowExecution;
    }

    // }}}
    // {{{ setAttribute()

    /**
     * Sets an attribute for the active flow object.
     *
     * @param string $name
     * @param mixed  $value
     * @throws PIECE_FLOW_ERROR_INVALID_OPERATION
     */
    function setAttribute($name, $value)
    {
        if (!$this->_flowExecution->activated()) {
            Piece_Flow_Error::push(PIECE_FLOW_ERROR_INVALID_OPERATION,
                                   __FUNCTION__ . ' method must be called after starting/continuing flows.'
                                   );
            return;
        }

        $flow = &$this->_flowExecution->getActiveFlow();
        $flow->setAttribute($name, $value);
    }

    // }}}
    // {{{ hasAttribute()

    /**
     * Returns whether the active flow object has an attribute with a given name.
     *
     * @param string $name
     * @return boolean
     * @throws PIECE_FLOW_ERROR_INVALID_OPERATION
     */
    function hasAttribute($name)
    {
        if (!$this->_flowExecution->activated()) {
            Piece_Flow_Error::push(PIECE_FLOW_ERROR_INVALID_OPERATION,
                                   __FUNCTION__ . ' method must be called after starting/continuing flows.'
                                   );
            return;
        }

        $flow = &$this->_flowExecution->getActiveFlow();
        return $flow->hasAttribute($name);
    }

    // }}}
    // {{{ getAttribute()

    /**
     * Gets an attribute for the active flow object.
     *
     * @param string $name
     * @return mixed
     * @throws PIECE_FLOW_ERROR_INVALID_OPERATION
     */
    function &getAttribute($name)
    {
        if (!$this->_flowExecution->activated()) {
            Piece_Flow_Error::push(PIECE_FLOW_ERROR_INVALID_OPERATION,
                                   __FUNCTION__ . ' method must be called after starting/continuing flows.'
                                   );
            $return = null;
            return $return;
        }

        $flow = &$this->_flowExecution->getActiveFlow();
        return $flow->getAttribute($name);
    }

    // }}}
    // {{{ setAttributeByRef()

    /**
     * Sets an attribute by reference for the active flow object.
     *
     * @param string $name
     * @param mixed  &$value
     * @throws PIECE_FLOW_ERROR_INVALID_OPERATION
     */
    function setAttributeByRef($name, &$value)
    {
        if (!$this->_flowExecution->activated()) {
            Piece_Flow_Error::push(PIECE_FLOW_ERROR_INVALID_OPERATION,
                                   __FUNCTION__ . ' method must be called after starting/continuing flows.'
                                   );
            return;
        }

        $flow = &$this->_flowExecution->getActiveFlow();
        $flow->setAttributeByRef($name, $value);
    }

    // }}}
    // {{{ getFlowExecutionTicketByFlowName()

    /**
     * Gets a flow execution ticket by the given flow ID.
     * This method will be used for getting flow execution ticket else than
     * the active flow execution.
     * This method is only available if the flow execution is exclusive.
     *
     * @param string $flowID
     * @return string
     * @deprecated Method deprecated in Release 1.15.0
     */
    function getFlowExecutionTicketByFlowName($flowID)
    {
        return $this->getFlowExecutionTicketByFlowID($flowID);
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
        return $this->_flowExecution->checkLastEvent();
    }

    // }}}
    // {{{ getActiveFlowName()

    /**
     * Gets the flow ID for the active flow execution.
     *
     * @return string
     * @deprecated Method deprecated in Release 1.15.0
     */
    function getActiveFlowName()
    {
        return $this->getActiveFlowID();
    }

    // }}}
    // {{{ getCurrentFlowName()

    /**
     * Gets the flow ID for the active flow execution.
     *
     * @return string
     * @deprecated Method deprecated in Release 1.14.0
     */
    function getCurrentFlowName()
    {
        return $this->getActiveFlowID();
    }

    // }}}
    // {{{ getCurrentStateName()

    /**
     * Gets the current state name for the active flow object.
     *
     * @return string
     * @throws PIECE_FLOW_ERROR_INVALID_OPERATION
     */
    function getCurrentStateName()
    {
        if (!$this->_flowExecution->activated()) {
            Piece_Flow_Error::push(PIECE_FLOW_ERROR_INVALID_OPERATION,
                                   __FUNCTION__ . ' method must be called after starting/continuing flows.'
                                   );
            return;
        }

        $flow = &$this->_flowExecution->getActiveFlow();
        return $flow->getCurrentStateName();
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
        return $this->_flowExecution->getFlowExecutionTicketByFlowID($flowID);
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
        return $this->_flowExecution->getActiveFlowID();
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
