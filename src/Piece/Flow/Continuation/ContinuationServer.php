<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * PHP version 5.3
 *
 * Copyright (c) 2006-2008, 2012 KUBO Atsuhiro <kubo@iteman.jp>,
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
 * @copyright  2006-2008, 2012 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version    Release: @package_version@
 * @since      File available since Release 1.14.0
 */

namespace Piece\Flow\Continuation;

use Piece\Flow\PageFlow\ActionInvoker;

/**
 * The continuation server.
 *
 * @package    Piece_Flow
 * @copyright  2006-2008, 2012 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version    Release: @package_version@
 * @since      Class available since Release 1.14.0
 */
class ContinuationServer
{
    protected $gc;
    protected $pageFlowInstanceRepository;

    /**
     * @var \Piece\Flow\PageFlow\ActionInvoker
     * @since Property available since Release 2.0.0
     */
    protected $actionInvoker;

    /**
     * @var \Piece\Flow\Continuation\ContinuationContextProvider
     * @since Property available since Release 2.0.0
     */
    protected $continuationContextProvider;

    /**
     * @var \Piece\Flow\Continuation\PageFlowInstance
     * @since Property available since Release 2.0.0
     */
    protected $pageFlowInstance;

    private static $activeInstances = array();
    private static $shutdownRegistered = false;

    /**
     * @param \Piece\Flow\Continuation\PageFlowInstanceRepository $pageFlowInstanceRepository
     * @param \Piece\Flow\Continuation\GC $gc
     */
    public function __construct(PageFlowInstanceRepository $pageFlowInstanceRepository, GC $gc = null)
    {
        $this->pageFlowInstanceRepository = $pageFlowInstanceRepository;
        $this->gc = $gc;
    }

    /**
     * Invokes a flow and returns a flow execution ticket.
     *
     * @param mixed   $payload
     * @return string
     */
    public function invoke($payload)
    {
        if (!is_null($this->gc)) {
            $this->gc->mark();
        }

        $this->pageFlowInstance = $this->prepare($payload);
        $this->pageFlowInstance->activate($this->continuationContextProvider->getEventID());

        if (!is_null($this->gc) && !$this->pageFlowInstanceRepository->checkPageFlowIsExclusive($this->pageFlowInstance)) {
            $this->gc->update($this->pageFlowInstance->getID());
        }

        self::$activeInstances[] = $this;
        if (!self::$shutdownRegistered) {
            self::$shutdownRegistered = true;
            register_shutdown_function(array(__CLASS__, 'shutdown'));
        }

        return $this->pageFlowInstance->getID();
    }

    /**
     * Shutdown the continuation server for next events.
     */
    public static function shutdown()
    {
        for ($i = 0, $count = count(self::$activeInstances); $i < $count; ++$i) {
            $instance = self::$activeInstances[$i];
            if (!($instance instanceof self)) {
                unset(self::$activeInstances[$i]);
                continue;
            }
            $instance->clear();
        }
    }

    /**
     * Clears some properties for the next use.
     */
    public function clear()
    {
        if (!is_null($this->pageFlowInstance)) {
            if ($this->pageFlowInstance->isFinalState()) {
                $this->pageFlowInstanceRepository->remove($this->pageFlowInstance);
            }
        }

        $this->pageFlowInstance = null;
        if (!is_null($this->gc)) {
            $pageFlowInstanceRepository = $this->pageFlowInstanceRepository;
            $this->gc->sweep(function ($pageFlowInstanceID) use ($pageFlowInstanceRepository) {
                $pageFlowInstance = $pageFlowInstanceRepository->findByID($pageFlowInstanceID);
                if (!is_null($pageFlowInstance)) {
                    $pageFlowInstance->removePageFlow();
                }
            });
        }
    }

    /**
     * Sets the action invoker.
     *
     * @param \Piece\Flow\PageFlow\ActionInvoker $actionInvoker
     * @since Method available since Release 2.0.0
     */
    public function setActionInvoker(ActionInvoker $actionInvoker)
    {
        $this->actionInvoker = $actionInvoker;
    }

    /**
     * @param \Piece\Flow\Continuation\ContinuationContextProvider $continuationContextProvider
     * @since Method available since Release 2.0.0
     */
    public function setContinuationContextProvider(ContinuationContextProvider $continuationContextProvider)
    {
        $this->continuationContextProvider = $continuationContextProvider;
    }

    /**
     * @return \Piece\Flow\Continuation\PageFlowInstance
     * @since Method available since Release 2.0.0
     */
    public function getActivePageFlowInstance()
    {
        return $this->pageFlowInstance;
    }

    /**
     * @return \Piece\Flow\Continuation\PageFlowInstanceRepository
     * @since Method available since Release 2.0.0
     */
    public function getPageFlowInstanceRepository()
    {
        return $this->pageFlowInstanceRepository;
    }

    /**
     * Generates a flow execution ticket.
     */
    protected function generatePageFlowInstanceID()
    {
        return sha1(uniqid(mt_rand(), true));
    }

    /**
     * Prepares a flow execution ticket, a flow ID, and whether the
     * flow invocation is the first time or not.
     *
     * @param mixed $payload
     * @return \Piece\Flow\Continuation\PageFlowInstance
     * @throws \Piece\Flow\Continuation\PageFlowInstanceExpiredException
     * @throws \Piece\Flow\Continuation\PageFlowIDRequiredException
     * @throws \Piece\Flow\Continuation\FlowNotFoundException
     * @throws \Piece\Flow\Continuation\InvaidPageFlowIDException
     */
    protected function prepare($payload)
    {
        $pageFlowInstance = $this->pageFlowInstanceRepository->findByID($this->continuationContextProvider->getPageFlowInstanceID());
        if (!is_null($pageFlowInstance)) {
            $registeredFlowID = $pageFlowInstance->getPageFlowID();

            $pageFlowID = $this->continuationContextProvider->getPageFlowID();
            if (is_null($pageFlowID) || !strlen($pageFlowID)) {
                throw new PageFlowIDRequiredException('A flow ID must be given in this case.');
            }

            if ($pageFlowID != $registeredFlowID) {
                throw new UnexpectedPageFlowIDException('The given flow ID is different from the registerd flow ID.');
            }

            if (!is_null($this->gc)) {
                if ($this->gc->isMarked($pageFlowInstance->getID())) {
                    $this->pageFlowInstanceRepository->remove($pageFlowInstance);
                    throw new PageFlowInstanceExpiredException('The flow execution for the given flow execution ticket has expired.');
                }
            }
        } else {
            $pageFlowID = $this->continuationContextProvider->getPageFlowID();
            if (is_null($pageFlowID) || !strlen($pageFlowID)) {
                throw new PageFlowIDRequiredException('A flow ID must be given in this case.');
            }

            $pageFlow = $this->pageFlowInstanceRepository->getPageFlowRepository()->findByID($pageFlowID);
            if (is_null($pageFlow)) {
                throw new FlowNotFoundException(sprintf('The page flow for ID [ %s ] is not found in the repository.', $pageFlowID));
            }

            while (true) {
                $pageFlowInstanceID = $this->generatePageFlowInstanceID();
                $pageFlowInstance = $this->pageFlowInstanceRepository->findByID($pageFlowInstanceID);
                if (is_null($pageFlowInstance)) {
                    $pageFlowInstance = new PageFlowInstance($pageFlowInstanceID, $pageFlow);
                    $this->pageFlowInstanceRepository->add($pageFlowInstance);
                    break;
                }
            }
        }

        $pageFlowInstance->setActionInvoker($this->actionInvoker);
        $pageFlowInstance->setPayload($payload);

        return $pageFlowInstance;
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
