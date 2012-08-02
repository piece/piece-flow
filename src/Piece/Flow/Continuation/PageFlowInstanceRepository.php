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

use Piece\Flow\PageFlow\PageFlowRepository;

/**
 * The container class for all flow executions in the continuation server.
 *
 * @package    Piece_Flow
 * @copyright  2007-2008, 2012 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version    Release: @package_version@
 * @since      Class available since Release 1.14.0
 */
class PageFlowInstanceRepository
{
    protected $pageFlowInstances = array();
    protected $exclusivePageFlowInstances = array();

    /**
     * @var \Piece\Flow\PageFlow\PageFlowRepository
     * @since Property available since Release 2.0.0
     */
    protected $pageFlowRepository;

    /**
     * @var array
     * @since Property available since Release 2.0.0
     */
    protected $exclusivePageFlows = array();

    /**
     * @param \Piece\Flow\PageFlow\PageFlowRepository $pageFlowRepository
     * @since Method available since Release 2.0.0
     */
    public function __construct(PageFlowRepository $pageFlowRepository)
    {
        $this->pageFlowRepository = $pageFlowRepository;
    }

    /**
     * Adds a page flow definition.
     *
     * @param string $pageFlowID
     * @param boolean $exclusive
     */
    public function addPageFlow($pageFlowID, $exclusive)
    {
        $this->pageFlowRepository->add($pageFlowID);
        if ($exclusive) {
            $this->exclusivePageFlows[] = $pageFlowID;
        }
    }

    /**
     * Removes a flow execution.
     *
     * @param \Piece\Flow\Continuation\PageFlowInstance $pageFlowInstance
     */
    public function remove(PageFlowInstance $pageFlowInstance)
    {
        if ($this->checkPageFlowHasExclusiveInstance($pageFlowInstance->getPageFlowID())) {
            unset($this->exclusivePageFlowInstances[ $pageFlowInstance->getPageFlowID() ]);
        }

        unset($this->pageFlowInstances[ $pageFlowInstance->getID() ]);
    }

    /**
     * Adds a PageFlow object to the list of PageFlowInstance objects.
     *
     * @param \Piece\Flow\Continuation\PageFlowInstance $pageFlowInstance
     */
    public function add(PageFlowInstance $pageFlowInstance)
    {
        $exclusivePageFlowInstance = $this->findByPageFlowID($pageFlowInstance->getPageFlowID());
        if (!is_null($exclusivePageFlowInstance)) {
            $this->remove($exclusivePageFlowInstance);
        }

        $this->pageFlowInstances[ $pageFlowInstance->getID() ] = $pageFlowInstance;
        if ($this->checkPageFlowIsExclusive($pageFlowInstance)) {
            $this->exclusivePageFlowInstances[ $pageFlowInstance->getPageFlowID() ] = $pageFlowInstance->getID();
        }
    }

    /**
     * Returns whether the given flow ID has the exclusive flow execution
     * or not.
     *
     * @param string $flowID
     * @return boolean
     */
    protected function checkPageFlowHasExclusiveInstance($flowID)
    {
        return array_key_exists($flowID, $this->exclusivePageFlowInstances);
    }

    /**
     * @param string $id
     * @return \Piece\Flow\Continuation\PageFlowInstance
     * @since Method available since Release 2.0.0
     */
    public function findByID($id)
    {
        if (array_key_exists($id, $this->pageFlowInstances)) {
            return $this->pageFlowInstances[$id];
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
        if ($this->checkPageFlowHasExclusiveInstance($pageFlowID)) {
            return $this->findByID($this->exclusivePageFlowInstances[$pageFlowID]);
        } else {
            return null;
        }
    }

    /**
     * @return \Piece\Flow\PageFlow\PageFlowRepository
     * @since Method available since Release 2.0.0
     */
    public function getPageFlowRepository()
    {
        return $this->pageFlowRepository;
    }

    /**
     * Checks whether the specified page flow is exclusive or not.
     *
     * @param \Piece\Flow\Continuation\PageFlowInstance $pageFlowInstance
     * @return boolean
     * @since Method available since Release 2.0.0
     */
    public function checkPageFlowIsExclusive(PageFlowInstance $pageFlowInstance)
    {
        return in_array($pageFlowInstance->getPageFlowID(), $this->exclusivePageFlows);
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
