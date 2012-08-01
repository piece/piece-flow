<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * PHP version 5.3
 *
 * Copyright (c) 2012 KUBO Atsuhiro <kubo@iteman.jp>,
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
 * @copyright  2012 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version    Release: @package_version@
 * @since      File available since Release 2.0.0
 */

namespace Piece\Flow\PageFlow;

/**
 * @package    Piece_Flow
 * @copyright  2012 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version    Release: @package_version@
 * @since      Class available since Release 2.0.0
 */
interface IPageFlow
{
    /**
     * Gets the ID of this page flow.
     *
     * @return string
     */
    public function getID();

    /**
     * Sets an attribute with the specified name.
     *
     * @param string $name
     * @param mixed $value
     * @throws \Piece\Flow\Core\MethodInvocationException
     */
    public function setAttribute($name, $value);

    /**
     * Gets the attribute bound with the specified name.
     *
     * @param string $name
     * @return mixed|null
     * @throws \Piece\Flow\Core\MethodInvocationException
     */
    public function getAttribute($name);

    /**
     * Checks whether this page flow has the attribute bound with the specified name.
     *
     * @param string $name
     * @return boolean
     * @throws \Piece\Flow\Core\MethodInvocationException
     */
    public function hasAttribute($name);

    /**
     * Validates whether the last event given by a user is valid or not.
     *
     * @return boolean
     */
    public function checkLastEvent();

    /**
     * Gets the current state ID.
     *
     * @return string
     * @throws \Piece\Flow\Core\MethodInvocationException
     */
    public function getCurrentStateName();

    /**
     * Gets the appropriate view string corresponding to the current state.
     *
     * @return string
     * @throws \Piece\Flow\Core\MethodInvocationException
     * @throws \Piece\Flow\PageFlow\InvalidTransitionException
     */
    public function getView();

    /**
     * Checks whether the current state is the final state or not.
     *
     * @return boolean
     * @throws \Piece\Flow\Core\MethodInvocationException
     */
    public function isFinalState();

    /**
     * @param \Piece\Flow\PageFlow\ActionInvoker $actionInvoker
     */
    public function setActionInvoker(ActionInvoker $actionInvoker);

    /**
     * Sets a user defined payload.
     *
     * @param mixed $payload
     * @throws \Piece\Flow\Core\MethodInvocationException
     */
    public function setPayload($payload);
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
