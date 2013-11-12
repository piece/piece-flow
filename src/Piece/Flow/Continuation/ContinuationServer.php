<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * PHP version 5.3
 *
 * Copyright (c) 2006-2008, 2012-2013 KUBO Atsuhiro <kubo@iteman.jp>,
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
 * @copyright  2006-2008, 2012-2013 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version    Release: @package_version@
 * @since      File available since Release 1.14.0
 */

namespace Piece\Flow\Continuation;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Piece\Flow\PageFlow\ActionInvokerInterface;

/**
 * The continuation server.
 *
 * @package    Piece_Flow
 * @copyright  2006-2008, 2012-2013 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version    Release: @package_version@
 * @since      Class available since Release 1.14.0
 */
class ContinuationServer
{
    /**
     * @var \Piece\Flow\Continuation\GarbageCollector
     */
    protected $garbageCollector;

    /**
     * @var \Piece\Flow\Continuation\PageFlowInstanceRepository
     */
    protected $pageFlowInstanceRepository;

    /**
     * @var \Piece\Flow\PageFlow\ActionInvokerInterface
     * @since Property available since Release 2.0.0
     */
    protected $actionInvoker;

    /**
     * @var \Piece\Flow\Continuation\ContinuationContextProvider
     * @since Property available since Release 2.0.0
     */
    protected $continuationContextProvider;

    /**
     * @var \Symfony\Component\EventDispatcher\EventDispatcherInterface
     * @since Property available since Release 2.0.0
     */
    protected $eventDispatcher;

    /**
     * @var \Piece\Flow\Continuation\PageFlowInstance
     * @since Property available since Release 2.0.0
     */
    protected $pageFlowInstance;

    private static $activeInstances = array();
    private static $shutdownRegistered = false;

    /**
     * @param \Piece\Flow\Continuation\PageFlowInstanceRepository $pageFlowInstanceRepository
     * @param \Piece\Flow\Continuation\GarbageCollector           $garbageCollector
     */
    public function __construct(PageFlowInstanceRepository $pageFlowInstanceRepository, GarbageCollector $garbageCollector = null)
    {
        $this->pageFlowInstanceRepository = $pageFlowInstanceRepository;
        $this->garbageCollector = $garbageCollector;
    }

    /**
     * @return array
     * @since Method available since Release 2.0.0
     */
    public function __sleep()
    {
        return array(
            'garbageCollector',
            'pageFlowInstanceRepository',
        );
    }

    /**
     * Activates a page flow instance.
     *
     * @param mixed $payload
     */
    public function activate($payload)
    {
        if (!is_null($this->garbageCollector)) {
            $this->garbageCollector->mark();
        }

        $this->pageFlowInstance = $this->createPageFlowInstance($payload);
        $this->pageFlowInstance->activate($this->continuationContextProvider->getEventID());

        if (!is_null($this->garbageCollector) && !$this->pageFlowInstanceRepository->checkPageFlowIsExclusive($this->pageFlowInstance)) {
            $this->garbageCollector->update($this->pageFlowInstance->getID());
        }

        self::$activeInstances[] = $this;
        if (!self::$shutdownRegistered) {
            self::$shutdownRegistered = true;
            register_shutdown_function(array(__CLASS__, 'shutdown'));
        }
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
            if ($this->pageFlowInstance->isInFinalState()) {
                $this->pageFlowInstanceRepository->remove($this->pageFlowInstance);
            }
        }

        if (!is_null($this->garbageCollector)) {
            $pageFlowInstanceRepository = $this->pageFlowInstanceRepository;
            $this->garbageCollector->sweep(function ($pageFlowInstanceID) use ($pageFlowInstanceRepository) {
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
     * @param \Piece\Flow\PageFlow\ActionInvokerInterface $actionInvoker
     * @since Method available since Release 2.0.0
     */
    public function setActionInvoker(ActionInvokerInterface $actionInvoker)
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
     * @param \Symfony\Component\EventDispatcher\EventDispatcherInterface $eventDispatcher
     * @since Method available since Release 2.0.0
     */
    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @return \Piece\Flow\Continuation\PageFlowInstance
     * @since Method available since Release 2.0.0
     */
    public function getPageFlowInstance()
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
     * Generates the ID for a page flow instance.
     *
     * @throws \Piece\Flow\Continuation\SecurityException
     */
    protected function generatePageFlowInstanceID()
    {
        $bytes = openssl_random_pseudo_bytes(24, $cryptographicallyStrong);
        if ($bytes === false) {
            throw new SecurityException('Generating a pseudo-random string of bytes is failed.');
        }
        if ($cryptographicallyStrong === false) {
            throw new SecurityException('Any cryptographically strong algorithm is not used to generate the pseudo-random string of bytes.');
        }

        $pageFlowInstanceID = base64_encode($bytes);
        if ($pageFlowInstanceID === false) {
            throw new SecurityException('Encoding the pseudo-random string of bytes with Base64 is failed.');
        }

        return $pageFlowInstanceID;
    }

    /**
     * Creates a page flow instance.
     *
     * @param  mixed                                                     $payload
     * @return \Piece\Flow\Continuation\PageFlowInstance
     * @throws \Piece\Flow\Continuation\PageFlowInstanceExpiredException
     * @throws \Piece\Flow\Continuation\PageFlowIDRequiredException
     * @throws \Piece\Flow\Continuation\PageFlowNotFoundException
     * @throws \Piece\Flow\Continuation\UnexpectedPageFlowIDException
     */
    protected function createPageFlowInstance($payload)
    {
        $pageFlowID = $this->continuationContextProvider->getPageFlowID();
        if (empty($pageFlowID)) {
            throw new PageFlowIDRequiredException('A page flow ID must be specified.');
        }

        $pageFlowInstance = $this->pageFlowInstanceRepository->findByID($this->continuationContextProvider->getPageFlowInstanceID());
        if (is_null($pageFlowInstance)) {
            $pageFlow = $this->pageFlowInstanceRepository->getPageFlowRepository()->findByID($pageFlowID);
            if (is_null($pageFlow)) {
                throw new PageFlowNotFoundException(sprintf('The page flow for ID [ %s ] is not found in the repository.', $pageFlowID));
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
        } else {
            if ($pageFlowID != $pageFlowInstance->getPageFlowID()) {
                throw new UnexpectedPageFlowIDException(sprintf('The specified page flow ID [ %s ] is different from the expected page flow ID [ %s ].', $pageFlowID, $pageFlowInstance->getPageFlowID()));
            }

            if (!is_null($this->garbageCollector)) {
                if ($this->garbageCollector->isMarked($pageFlowInstance->getID())) {
                    $this->pageFlowInstanceRepository->remove($pageFlowInstance);
                    throw new PageFlowInstanceExpiredException('The page flow instance has been expired.');
                }
            }
        }

        $pageFlowInstance->setActionInvoker($this->actionInvoker);
        $pageFlowInstance->setPayload($payload);
        $pageFlowInstance->setEventDispatcher($this->eventDispatcher);

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
