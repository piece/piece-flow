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

use Piece\Flow\Core\MethodInvocationException;

/**
 * A service class which provides simple interfaces to access attributes of
 * the active flow object and to get some information from flow executions.
 *
 * @package    Piece_Flow
 * @copyright  2007-2008, 2012 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version    Release: @package_version@
 * @since      Class available since Release 1.14.0
 */
class Service
{
    protected $flowExecution;

    /**
     * Sets the active Flow object if the flow execution has activated
     * and the FlowExecution object to the properties.
     *
     * @param \Piece\Flow\Continuation\FlowExecution $flowExecution
     */
    public function __construct(FlowExecution $flowExecution)
    {
        $this->flowExecution = $flowExecution;
    }

    /**
     * Sets an attribute for the active flow object.
     *
     * @param string $name
     * @param mixed  $value
     * @throws \Piece\Flow\Core\MethodInvocationException
     */
    public function setAttribute($name, $value)
    {
        if (!$this->flowExecution->activated()) {
            throw new MethodInvocationException(__FUNCTION__ . ' method must be called after starting/continuing flows.');
        }

        $flow = $this->flowExecution->getActiveFlow();
        $flow->setAttribute($name, $value);
    }

    /**
     * Returns whether the active flow object has an attribute with a given name.
     *
     * @param string $name
     * @return boolean
     * @throws \Piece\Flow\Core\MethodInvocationException
     */
    public function hasAttribute($name)
    {
        if (!$this->flowExecution->activated()) {
            throw new MethodInvocationException(__FUNCTION__ . ' method must be called after starting/continuing flows.');
        }

        $flow = $this->flowExecution->getActiveFlow();
        return $flow->hasAttribute($name);
    }

    /**
     * Gets an attribute for the active flow object.
     *
     * @param string $name
     * @return mixed
     * @throws \Piece\Flow\Core\MethodInvocationException
     */
    public function getAttribute($name)
    {
        if (!$this->flowExecution->activated()) {
            throw new MethodInvocationException(__FUNCTION__ . ' method must be called after starting/continuing flows.');
            $return = null;
            return $return;
        }

        $flow = $this->flowExecution->getActiveFlow();
        return $flow->getAttribute($name);
    }

    /**
     * Returns whether the last event which is given by a user is valid or
     * not.
     *
     * @return boolean
     */
    public function checkLastEvent()
    {
        return $this->flowExecution->checkLastEvent();
    }

    /**
     * Gets the current state name for the active flow object.
     *
     * @return string
     * @throws \Piece\Flow\Core\MethodInvocationException
     */
    public function getCurrentStateName()
    {
        if (!$this->flowExecution->activated()) {
            throw new MethodInvocationException(__FUNCTION__ . ' method must be called after starting/continuing flows.');
        }

        $flow = $this->flowExecution->getActiveFlow();
        return $flow->getCurrentStateName();
    }

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
    public function getFlowExecutionTicketByFlowID($flowID)
    {
        return $this->flowExecution->getFlowExecutionTicketByFlowID($flowID);
    }

    /**
     * Gets the flow ID for the active flow execution.
     *
     * @return string
     * @since Method available since Release 1.15.0
     */
    public function getActiveFlowID()
    {
        return $this->flowExecution->getActiveFlowID();
    }

    /**
     * Gets the flow execution ticket for the active flow execution.
     *
     * @return string
     * @since Method available since Release 1.16.0
     */
    public function getActiveFlowExecutionTicket()
    {
        return $this->flowExecution->getActiveFlowExecutionTicket();
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
