<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * PHP version 5.3
 *
 * Copyright (c) 2007-2008, 2012 KUBO Atsuhiro <kubo@iteman.jp>,
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
 * @copyright  2007-2008, 2012 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version    Release: @package_version@
 * @since      File available since Release 1.14.0
 */

namespace Piece\Flow\Continuation;

/**
 * The container class for all flow executions in the continuation server.
 *
 * @package    Piece_Flow
 * @copyright  2007-2008, 2012 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version    Release: @package_version@
 * @since      Class available since Release 1.14.0
 */
class FlowExecution
{
    protected $flowExecutions = array();
    protected $exclusiveFlowExecutionTicketsByFlowID = array();
    protected $exclusiveFlowIDsByFlowExecutionTicket = array();

    /**
     * Disables the flow execution for the given flow execution ticket.
     *
     * @param string $flowExecutionTicket
     */
    public function disableFlowExecution($flowExecutionTicket)
    {
        $pageFlowInstance = $this->findByID($flowExecutionTicket);
        if (!is_null($pageFlowInstance)) {
            $pageFlowInstance->removePageFlow();
        }
    }

    /**
     * Removes a flow execution.
     *
     * @param string $flowExecutionTicket
     */
    public function removeFlowExecution($flowExecutionTicket)
    {
        $pageFlowInstance = $this->findByID($flowExecutionTicket);
        if (!is_null($pageFlowInstance)) {
            if ($this->hasExclusiveFlowExecution($pageFlowInstance->getPageFlowID())) {
                unset($this->exclusiveFlowExecutionTicketsByFlowID[ $pageFlowInstance->getPageFlowID() ]);
                unset($this->exclusiveFlowIDsByFlowExecutionTicket[$flowExecutionTicket]);
            }

            unset($this->flowExecutions[$flowExecutionTicket]);
        }
    }

    /**
     * Adds a PageFlow object to the list of PageFlowInstance objects.
     *
     * @param \Piece\Flow\Continuation\PageFlowInstance $pageFlowInstance
     */
    public function addFlowExecution(PageFlowInstance $pageFlowInstance)
    {
        $this->flowExecutions[ $pageFlowInstance->getID() ] = $pageFlowInstance;
    }

    /**
     * Marks a flow execution which is indicated by the given flow execution
     * ticket and flow ID as exclusive.
     *
     * @param string $flowExecutionTicket
     * @param string $flowID
     */
    public function markFlowExecutionAsExclusive($flowExecutionTicket, $flowID)
    {
        $this->exclusiveFlowExecutionTicketsByFlowID[$flowID] = $flowExecutionTicket;
        $this->exclusiveFlowIDsByFlowExecutionTicket[$flowExecutionTicket] = $flowID;
    }

    /**
     * Returns whether the given flow ID has the exclusive flow execution
     * or not.
     *
     * @param string $flowID
     * @return boolean
     */
    public function hasExclusiveFlowExecution($flowID)
    {
        return array_key_exists($flowID, $this->exclusiveFlowExecutionTicketsByFlowID);
    }

    /**
     * @param string $id
     * @return \Piece\Flow\Continuation\PageFlowInstance
     * @since Method available since Release 2.0.0
     */
    public function findByID($id)
    {
        if (array_key_exists($id, $this->flowExecutions)) {
            return $this->flowExecutions[$id];
        } else {
            return null;
        }
    }

    /**
     * @param string $pageFlowID
     * @return \Piece\Flow\Continuation\PageFlowInstance
     * @since Method available since Release 2.0.0
     */
    public function findByPageFlowID($pageFlowID)
    {
        if ($this->hasExclusiveFlowExecution($pageFlowID)) {
            return $this->findByID($this->exclusiveFlowExecutionTicketsByFlowID[$pageFlowID]);
        } else {
            return null;
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
