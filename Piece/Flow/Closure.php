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
 * @since      File available since Release 1.15.0
 */

require_once 'Piece/Flow/ProtedtedEvent.php';
require_once 'Piece/Flow/Continuation/Server.php';
require_once 'Piece/Flow/Config.php';

// {{{ GLOBALS

$GLOBALS['PIECE_FLOW_Closure_ContinuationServer']  = null;
$GLOBALS['PIECE_FLOW_Closure_FlowExecutionTicket'] = null;
$GLOBALS['PIECE_FLOW_Closure_Result']              = null;

// {{{ Piece_Flow_Closure

/**
 * A closure implementation.
 *
 * @package    Piece_Flow
 * @copyright  2006-2007 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @since      Class available since Release 1.15.0
 */
class Piece_Flow_Closure
{

    // {{{ properties

    /**#@+
     * @access public
     */

    /**#@-*/

    /**#@+
     * @access private
     */

    /**#@-*/

    /**#@+
     * @access public
     */

    /**
     * Creates a closure with the given arguments.
     *
     * @param string $args
     * @param string $code
     * @param array  $contextVariables
     * @return callback
     * @static
     */
    function create($args, $code, $contextVariables = array())
    {
        $GLOBALS['PIECE_FLOW_Closure_FlowExecutionTicket'] = null;
        $payload = array($args, $code, $contextVariables);
        $flowExecutionTicket = $GLOBALS['PIECE_FLOW_Closure_ContinuationServer']->invoke($payload);

        return create_function($args, "
\$GLOBALS['PIECE_FLOW_Closure_FlowExecutionTicket'] = '$flowExecutionTicket';
\$GLOBALS['PIECE_FLOW_Closure_ContinuationServer']->invoke(func_get_args());
return \$GLOBALS['PIECE_FLOW_Closure_Result'];
"
                               );
    }

    /**#@-*/

    /**#@+
     * @access private
     */

    /**#@-*/

    // }}}
}

// }}}

$GLOBALS['PIECE_FLOW_Closure_ContinuationServer']  = &new Piece_Flow_Continuation_Server();
$GLOBALS['PIECE_FLOW_Closure_ContinuationServer']->setActionDirectory(dirname(__FILE__) . '/../..');
$GLOBALS['PIECE_FLOW_Closure_ContinuationServer']->addFlow('Closure',
                                                           create_function('',
                                                                           "return array('firstState' => 'Closure',
                                                                                         'viewState'  => array(array('name' => 'Closure',
                                                                                         'view'       => 'Closure',
                                                                                         'activity'   => array('class' => 'Piece_Flow_Closure_Action',
                                                                                                               'method' => 'invoke')))
                                                                                         );")
                                                           );
$GLOBALS['PIECE_FLOW_Closure_ContinuationServer']->setEventNameCallback(create_function('', 'return PIECE_FLOW_PROTECTED_EVENT;'));
$GLOBALS['PIECE_FLOW_Closure_ContinuationServer']->setFlowExecutionTicketCallback(create_function('', "return \$GLOBALS['PIECE_FLOW_Closure_FlowExecutionTicket'];"));
$GLOBALS['PIECE_FLOW_Closure_ContinuationServer']->setFlowNameCallback(create_function('', "return 'Closure';"));
$GLOBALS['PIECE_FLOW_Closure_ContinuationServer']->setUseContext(true);

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
