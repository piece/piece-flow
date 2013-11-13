<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * PHP version 5.3
 *
 * Copyright (c) 2007, 2012-2013 KUBO Atsuhiro <kubo@iteman.jp>,
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
 * @copyright  2007, 2012-2013 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version    Release: @package_version@
 * @since      File available since Release 1.11.0
 */

namespace Piece\Flow\Continuation\GarbageCollection;

use Piece\Flow\Continuation\GarbageCollection\Clock;

/**
 * The garbage collector for expired page flow instances.
 *
 * @package    Piece_Flow
 * @copyright  2007, 2012-2013 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version    Release: @package_version@
 * @since      Class available since Release 1.11.0
 */
class GarbageCollector
{
    protected $expirationTime;

    /**
     * @var \Piece\Flow\Continuation\GarbageCollection\Clock
     * @since Property available since Release 2.0.0
     */
    protected $clock;

    /**
     * @var \Piece\Flow\Continuation\GarbageCollection\GarbageMarker[]
     */
    protected $markers = array();

    /**
     * Sets the expiration time in seconds.
     *
     * @param integer                                          $expirationTime
     * @param \Piece\Flow\Continuation\GarbageCollection\Clock $clock
     */
    public function __construct($expirationTime, Clock $clock)
    {
        $this->expirationTime = $expirationTime;
        $this->clock = $clock;
    }

    /**
     * Updates the state of the specified page flow instance.
     *
     * @param string $pageFlowInstanceID
     */
    public function update($pageFlowInstanceID)
    {
        if (array_key_exists($pageFlowInstanceID, $this->garbageMarkers)) {
            $this->markers[$pageFlowInstanceID]->updateModificationTimestamp($this->clock->now()->getTimestamp());
        } else {
            $this->markers[$pageFlowInstanceID] = new GarbageMarker($this->clock->now()->getTimestamp());
        }
    }

    /**
     * Returns whether or not the specified page flow instance is marked as
     * a target for sweeping.
     *
     * @param  string  $pageFlowInstanceID
     * @return boolean
     */
    public function isMarked($pageFlowInstanceID)
    {
        if (array_key_exists($pageFlowInstanceID, $this->markers)) {
            return $this->markers[$pageFlowInstanceID]->isEnabled();
        } else {
            return false;
        }
    }

    /**
     * Marks expired page flow instances as a target for sweeping.
     */
    public function mark()
    {
        reset($this->markers);
        while (list($pageFlowInstanceID, $marker) = each($this->markers)) {
            if ($marker->isSwept()) {
                continue;
            }

            if (($this->clock->now()->getTimestamp() - $marker->getModificationTimestamp()) > $this->expirationTime) {
                $marker->markAsEnabled();
            }
        }
    }

    /**
     * Sweeps all marked page flow instance with the specified callback.
     *
     * @param callback $callback
     */
    public function sweep($callback)
    {
        reset($this->markers);
        while (list($pageFlowInstanceID, $marker) = each($this->markers)) {
            if ($marker->isSwept()) {
                continue;
            }

            if ($marker->isEnabled()) {
                call_user_func($callback, $pageFlowInstanceID);
                $marker->markAsSwept();
            }
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
