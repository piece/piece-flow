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
 * @copyright  2006 KUBO Atsuhiro <iteman2002@yahoo.co.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    SVN: $Id$
 * @link       http://iteman.typepad.jp/piece/
 * @see        Piece_Flow_ConfigReader_YAML
 * @since      File available since Release 0.1.0
 */

require_once 'Piece/Flow/ConfigReader/Callback.php';

require_once dirname(__FILE__) . '/CompatibilityTest.php';
require_once 'Piece/Flow/Config.php';

// {{{ Piece_Flow_ConfigReader_CallbackTestCase

/**
 * TestCase for Piece_Flow_ConfigReader_Callback
 *
 * @package    Piece_Flow
 * @author     KUBO Atsuhiro <iteman2002@yahoo.co.jp>
 * @copyright  2006 KUBO Atsuhiro <iteman2002@yahoo.co.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @link       http://iteman.typepad.jp/piece/
 * @see        Piece_Flow_ConfigReader_Callback
 * @since      Class available since Release 0.1.0
 */
class Piece_Flow_ConfigReader_CallbackTestCase extends Piece_Flow_ConfigReader_CompatibilityTest
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

    function getConfig()
    {
        $callback =
            new Piece_Flow_ConfigReader_Callback(array(&$this, 'configure'));
        return $callback->configure();
    }

    function configure()
    {
        $flow = array();
        $flow['name'] = 'registrationFlow';
        $flow['firstState'] = 'displaying';
        $flow['lastState'] = array('name' => 'finishing',
                                   'view' => 'finish'
                                   );
        $flow['viewState'] = array(array('name' => 'displaying',
                                         'view' => 'input',
                                         'entry' =>
                                         array('class' => 'Piece_FlowTestCaseAction',
                                               'method' => 'setupForm'),
                                         'exit' =>
                                         array('class' => 'Piece_FlowTestCaseAction',
                                               'method' => 'teardownForm'),
                                         'activity' =>
                                         array('class' => 'Piece_FlowTestCaseAction',
                                               'method' => 'countDisplay'),
                                         'transition' =>
                                         array(array('event' => 'submit',
                                                     'nextState' => 'validated',
                                                     'action' =>
                                                     array('class' => 'Piece_FlowTestCaseAction',
                                                           'method' => 'validate'),
                                                     'guard' =>
                                                     array('class' => 'Piece_FlowTestCaseAction',
                                                           'method' => 'isPermitted')))),
                                   array('name' => 'confirming',
                                         'view' => 'confirmation',
                                         'transition' =>
                                         array(array('event' => 'submit',
                                                     'nextState' => 'validated',
                                                     'action' =>
                                                     array('class' => 'Piece_FlowTestCaseAction',
                                                           'method' => 'validate'))))
                                   );
        $flow['actionState'] = array(array('name' => 'validated',
                                           'transition' =>
                                           array(array('event' => 'raiseError',
                                                       'nextState' => 'displaying'),
                                                 array('event' => 'succeedInValidatingViaDisplaying',
                                                       'nextState' => 'confirming'),
                                                 array('event' => 'succeedInValidatingViaConfirming',
                                                       'nextState' => 'registered',
                                                       'action' =>
                                                       array('class' => 'Piece_FlowTestCaseAction',
                                                             'method' => 'register')))),
                                     array('name' => 'registered',
                                           'transition' =>
                                           array(array('event' => 'raiseError',
                                                       'nextState' => 'displaying'),
                                                 array('event' => 'succeed',
                                                       'nextState' => 'finishing')))
                                     );

        return $flow;
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
