<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * PHP version 5.3
 *
 * Copyright (c) 2012-2013 KUBO Atsuhiro <kubo@iteman.jp>,
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
 * @copyright  2012-2013 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version    Release: @package_version@
 * @since      File available since Release 2.0.0
 */

namespace Piece\Flow\Continuation;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Piece\Flow\PageFlow\ActionInvokerInterface;
use Piece\Flow\PageFlow\PageFlow;
use Piece\Flow\PageFlow\PageFlowInterface;

/**
 * @package    Piece_Flow
 * @copyright  2012-2013 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version    Release: @package_version@
 * @since      Class available since Release 2.0.0
 */
class PageFlowInstance implements PageFlowInterface
{
    /**
     * @var string
     */
    protected $id;

    /**
     * @var \Piece\Flow\PageFlow\PageFlow
     */
    protected $pageFlow;

    /**
     * @param string $id
     * @param \Piece\Flow\PageFlow\PageFlow $pageFlow
     */
    public function __construct($id, PageFlow $pageFlow)
    {
        $this->id = $id;
        $this->pageFlow = $pageFlow;
    }

    /**
     * @return string
     */
    public function getID()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getPageFlowID()
    {
        return $this->pageFlow->getID();
    }

    public function removePageFlow()
    {
        $this->pageFlow = new NullPageFlow($this->pageFlow->getID());
    }

    /**
     * Activate this page flow instance with the specified event.
     *
     * @param string $eventID
     */
    public function activate($eventID)
    {
        if ($this->pageFlow->isActive()) {
            $this->pageFlow->triggerEvent($eventID);
        } else {
            $this->pageFlow->start();
        }
    }

    public function getAttributes()
    {
        return $this->pageFlow->getAttributes();
    }

    public function validateReceivedEvent()
    {
        return $this->pageFlow->validateReceivedEvent();
    }

    public function getCurrentState()
    {
        return $this->pageFlow->getCurrentState();
    }

    public function getPreviousState()
    {
        return $this->pageFlow->getPreviousState();
    }

    public function getCurrentView()
    {
        return $this->pageFlow->getCurrentView();
    }

    public function isInFinalState()
    {
        return $this->pageFlow->isInFinalState();
    }

    public function setActionInvoker(ActionInvokerInterface $actionInvoker)
    {
        $this->pageFlow->setActionInvoker($actionInvoker);
    }

    /**
     * {@inheritDoc}
     */
    public function getActionInvoker()
    {
        return $this->pageFlow->getActionInvoker();
    }

    public function setPayload($payload)
    {
        $this->pageFlow->setPayload($payload);
    }

    /**
     * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
     * @since Method available since Release 2.0.0
     */
    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher)
    {
        $this->pageFlow->setEventDispatcher($eventDispatcher);
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
