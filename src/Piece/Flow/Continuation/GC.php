<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * PHP version 5.3
 *
 * Copyright (c) 2007, 2012 KUBO Atsuhiro <kubo@iteman.jp>,
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
 * @copyright  2007, 2012 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version    Release: @package_version@
 * @since      File available since Release 1.11.0
 */

namespace Piece\Flow\Continuation;

/**
 * The garbage collector for expired flow executions.
 *
 * @package    Piece_Flow
 * @copyright  2007, 2012 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version    Release: @package_version@
 * @since      Class available since Release 1.11.0
 */
class GC
{
    protected $expirationTime;
    protected $markers = array();

    /**
     * Sets the expiration time in seconds.
     *
     * @param integer $expirationTime
     */
    public function __construct($expirationTime = 1440)
    {
        $this->expirationTime = $expirationTime;
    }

    /**
     * Updates the state for the flow execution by the given flow execution
     * ticket.
     *
     * @param string $pageFlowInstanceID
     */
    public function update($pageFlowInstanceID)
    {
        if (!$this->isMarked($pageFlowInstanceID)) {
            $this->markers[$pageFlowInstanceID] = array(
                'mtime' => time(),
                'shouldSweep' => false,
                'swept' => false
            );
        }
    }

    /**
     * Returns whether the given flow execution ticket is marked as a target for sweeping or not.
     *
     * @param string $pageFlowInstanceID
     * @return boolean
     */
    public function isMarked($pageFlowInstanceID)
    {
        if (array_key_exists($pageFlowInstanceID, $this->markers)) {
            return $this->markers[$pageFlowInstanceID]['shouldSweep'];
        } else {
            return false;
        }
    }

    /**
     * Marks expired flow executions for sweeping.
     */
    public function mark()
    {
        $thresholdTime = time();
        reset($this->markers);
        while (list($pageFlowInstanceID, $marker) = each($this->markers)) {
            if ($marker['swept']) {
                continue;
            }

            $this->markers[$pageFlowInstanceID]['shouldSweep'] = $thresholdTime - $marker['mtime'] > $this->expirationTime;
        }
    }

    /**
     * Sweeps all marked flow execution by the callback for GC.
     *
     * @param callback $gcCallback
     */
    public function sweep($gcCallback)
    {
        reset($this->markers);
        while (list($pageFlowInstanceID, $marker) = each($this->markers)) {
            if ($marker['swept']) {
                continue;
            }

            if ($marker['shouldSweep']) {
                call_user_func($gcCallback, $pageFlowInstanceID);
                $this->markers[$pageFlowInstanceID]['swept'] = true;
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
