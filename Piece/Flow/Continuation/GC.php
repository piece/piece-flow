<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * PHP versions 4 and 5
 *
 * Copyright (c) 2006-2007 KUBO Atsuhiro <iteman@users.sourceforge.net>,
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
 * @copyright  2006-2007 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    SVN: $Id$
 * @since      File available since Release 1.11.0
 */

// {{{ Piece_Flow_Continuation_GC

/**
 * The garbage collector for expired flow executions.
 *
 * @package    Piece_Flow
 * @copyright  2006-2007 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @since      Class available since Release 1.11.0
 */
class Piece_Flow_Continuation_GC
{

    // {{{ properties

    /**#@+
     * @access public
     */

    /**#@-*/

    /**#@+
     * @access private
     */

    var $_expirationTime;
    var $_statesByFlowExecutionTicket = array();
    var $_gcCallback;

    /**#@-*/

    /**#@+
     * @access public
     */

    // }}}
    // {{{ constructor

    /**
     * Sets the expiration time in seconds.
     *
     * @param integer $expirationTime
     */
    function Piece_Flow_Continuation_GC($expirationTime)
    {
        $this->_expirationTime = $expirationTime;
    }

    // }}}
    // {{{ update()

    /**
     * Updates the state for the flow execution by the given flow execution
     * ticket.
     *
     * @param string $flowExecutionTicket
     */
    function update($flowExecutionTicket)
    {
        if (!$this->isMarked($flowExecutionTicket)) {
            $this->_statesByFlowExecutionTicket[$flowExecutionTicket] = array('mtime'   => time(),
                                                                              'sweep'   => false,
                                                                              'isSwept' => false
                                                                              );
        }
    }

    // }}}
    // {{{ isMarked()

    /**
     * Returns whether the given flow execution ticket is marked as a target for sweeping or not.
     *
     * @param string $flowExecutionTicket
     * @return boolean
     */
    function isMarked($flowExecutionTicket)
    {
        if (array_key_exists($flowExecutionTicket, $this->_statesByFlowExecutionTicket)) {
            return $this->_statesByFlowExecutionTicket[$flowExecutionTicket]['sweep'];
        } else {
            return false;
        }
    }

    // }}}
    // {{{ mark()

    /**
     * Marks expired flow executions for sweeping.
     */
    function mark()
    {
        $thresholdTime = time();
        reset($this->_statesByFlowExecutionTicket);
        while (list($flowExecutionTicket, $state) = each($this->_statesByFlowExecutionTicket)) {
            if ($state['isSwept']) {
                continue;
            }

            $this->_statesByFlowExecutionTicket[$flowExecutionTicket]['sweep'] = $thresholdTime - $state['mtime'] > $this->_expirationTime;
        }
    }

    // }}}
    // {{{ sweep()

    /**
     * Sweeps all marked flow execution by the callback for GC.
     */
    function sweep()
    {
        reset($this->_statesByFlowExecutionTicket);
        while (list($flowExecutionTicket, $state) = each($this->_statesByFlowExecutionTicket)) {
            if ($state['isSwept']) {
                continue;
            }

            if ($state['sweep']) {
                call_user_func($this->_gcCallback, $flowExecutionTicket);
                $this->_statesByFlowExecutionTicket[$flowExecutionTicket]['isSwept'] = true;
            }
        }

        $this->_gcCallback = null;
    }

    // }}}
    // {{{ setGCCallback()

    /**
     * Sets the callback for GC.
     *
     * @param callback $gcCallback
     */
    function setGCCallback($gcCallback)
    {
        $this->_gcCallback = &$gcCallback;
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
