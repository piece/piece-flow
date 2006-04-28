<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * PHP versions 4 and 5
 *
 * Copyright (c) 2006, KUBO Atsuhiro <iteman2002@yahoo.co.jp>
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
 * @author     KUBO Atsuhiro <iteman2002@yahoo.co.jp>
 * @author     MIYAI Fumihiko <fumichz@yahoo.co.jp>
 * @copyright  2006 KUBO Atsuhiro <iteman2002@yahoo.co.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    SVN: $Id$
 * @link       http://iteman.typepad.jp/piece/
 * @see        Piece_Flow
 * @since      File available since Release 0.1.0
 */

// {{{ Piece_Flow_Action

/**
 * The action wrapper and invoker.
 *
 * @package    Piece_Flow
 * @author     KUBO Atsuhiro <iteman2002@yahoo.co.jp>
 * @author     MIYAI Fumihiko <fumichz@yahoo.co.jp>
 * @copyright  2006 KUBO Atsuhiro <iteman2002@yahoo.co.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @link       http://iteman.typepad.jp/piece/
 * @see        Piece_Flow
 * @since      Class available since Release 0.1.0
 */
class Piece_Flow_Action
{

    // {{{ properties

    /**#@+
     * @access public
     */

    /**#@-*/

    /**#@+
     * @access private
     */

    var $_flow;
    var $_class;
    var $_method;

    /**#@-*/

    /**#@+
     * @access public
     */

    // }}}
    // {{{ constructor

    /**
     * Wraps a action up with a Piece_Flow_Action object.
     *
     * @param Piece_Flow $flow
     * @param string     $class
     * @param string     $method
     */
    function Piece_Flow_Action(&$flow, $class, $method)
    {
        $this->_flow = &$flow;
        $this->_class = $class;
        $this->_method = $method;
    }

    // }}}
    // {{{ invoke()

    /**
     * Invokes the action.
     *
     * @param Stagehand_FSM       $fsm
     * @param Stagehand_FSM_Event $event
     * @param mixed               $payload
     */
    function invoke(&$fsm, &$event, &$payload)
    {
        return call_user_func_array(array($this->_class, $this->_method),
                                    array(&$this->_flow, $event->getName(), &$payload)
                                    );
    }

    // }}}
    // {{{ invokeAndTriggerEvent()

    /**
     * Invokes the action and triggers an event returned from the action.
     *
     * @param Stagehand_FSM       $fsm
     * @param Stagehand_FSM_Event $event
     * @param mixed               $payload
     */
    function invokeAndTriggerEvent(&$fsm, &$event, &$payload)
    {
        $result = call_user_func_array(array($this->_class, $this->_method),
                                       array(&$this->_flow, $event->getName(), &$payload)
                                       );
        if (!is_null($result)) {
            $this->_flow->triggerEvent($result);
        }
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
