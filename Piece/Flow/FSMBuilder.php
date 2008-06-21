<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * PHP versions 4 and 5
 *
 * Copyright (c) 2007-2008 KUBO Atsuhiro <iteman@users.sourceforge.net>,
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
 * @copyright  2007-2008 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    SVN: $Id$
 * @since      File available since Release 1.14.0
 */

require_once 'Stagehand/FSM.php';
require_once 'Piece/Flow/EventHandler.php';
require_once 'Piece/Flow/Error.php';
require_once 'Stagehand/FSM/State.php';
require_once 'Piece/Flow/ProtedtedEvent.php';

// {{{ Piece_Flow_FSMBuilder

/**
 * The FSM builder.
 *
 * @package    Piece_Flow
 * @copyright  2007-2008 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @since      Class available since Release 1.14.0
 */
class Piece_Flow_FSMBuilder
{

    // {{{ properties

    /**#@+
     * @access public
     */

    /**#@-*/

    /**#@+
     * @access private
     */

    var $_fsm;
    var $_flow;
    var $_actionDirectory;

    /**#@-*/

    /**#@+
     * @access public
     */

    // }}}
    // {{{ constructor

    /**
     * Sets a Piece_Flow object to the property.
     *
     * @param Piece_Flow &$flow
     * @param string     $actionDirectory
     */
    function Piece_Flow_FSMBuilder(&$flow, $actionDirectory)
    {
        $this->_flow = &$flow;
        $this->_actionDirectory = $actionDirectory;
    }

    // }}}
    // {{{ build()

    /**
     * Builds a FSM with the given configuration.
     *
     * @param Piece_Flow_Config &$config
     * @return Stagehand_FSM
     * @throws PIECE_FLOW_ERROR_PROTECTED_STATE
     */
    function &build(&$config)
    {
        $this->_fsm = &new Stagehand_FSM();

        $firstState = $config->getFirstState();
        if ($this->_fsm->isProtectedState($firstState)) {
            Piece_Flow_Error::push(PIECE_FLOW_ERROR_PROTECTED_STATE,
                                   "The state [ $firstState ] cannot be used in flow definitions."
                                   );
            $return = null;
            return $return;
        }

        $this->_fsm->setFirstState($firstState);
        $this->_fsm->setName($config->getName());

        $lastState = $config->getLastState();
        if (!is_null($lastState)) {
            if ($this->_fsm->isProtectedState($lastState)) {
                Piece_Flow_Error::push(PIECE_FLOW_ERROR_PROTECTED_STATE,
                                       "The state [ $lastState ] cannot be used in flow definitions."
                                       );
                $return = null;
                return $return;
            }

            $this->_fsm->addTransition($lastState,
                                       STAGEHAND_FSM_EVENT_END,
                                       STAGEHAND_FSM_STATE_FINAL
                                       );
        }

        $this->_configureViewStates($config->getViewStates());
        if (Piece_Flow_Error::hasErrors()) {
            $return = null;
            return $return;
        }

        $this->_configureActionStates($config->getActionStates());
        if (Piece_Flow_Error::hasErrors()) {
            $return = null;
            return $return;
        }

        $initial = $config->getInitialAction();
        if (!is_null($initial)) {
            $this->_fsm->setExitAction(STAGEHAND_FSM_STATE_INITIAL,
                                       $this->_wrapAction($initial)
                                       );
        }

        $final = $config->getFinalAction();
        if (!is_null($final)) {
            $this->_fsm->setEntryAction(STAGEHAND_FSM_STATE_FINAL,
                                        $this->_wrapAction($final)
                                        );
        }

        return $this->_fsm;
    }

    /**#@-*/

    /**#@+
     * @access private
     */

    // }}}
    // {{{ _configureViewStates()

    /**
     * Configures view states.
     *
     * @param array $states
     * @throws PIECE_FLOW_ERROR_PROTECTED_STATE
     */
    function _configureViewStates($states)
    {
        foreach ($states as $key => $state) {
            if ($this->_fsm->isProtectedState($state['name'])) {
                Piece_Flow_Error::push(PIECE_FLOW_ERROR_PROTECTED_STATE,
                                       "The state [ {$state['name']} ] cannot be used in flow definitions."
                                       );
                return;
            }

            $this->_configureViewState($state);
            if (Piece_Flow_Error::hasErrors()) {
                return;
            }
        }
    }

    // }}}
    // {{{ _configureActionStates()

    /**
     * Configures action states.
     *
     * @param array $states
     * @throws PIECE_FLOW_ERROR_PROTECTED_STATE
     */
    function _configureActionStates($states)
    {
        foreach ($states as $key => $state) {
            if ($this->_fsm->isProtectedState($state['name'])) {
                Piece_Flow_Error::push(PIECE_FLOW_ERROR_PROTECTED_STATE,
                                       "The state [ {$state['name']} ] cannot be used in flow definitions."
                                       );
                return;
            }

            $this->_configureState($state);
            if (Piece_Flow_Error::hasErrors()) {
                return;
            }
        }
    }

    // }}}
    // {{{ _configureState()

    /**
     * Configures a state.
     *
     * @param array $state
     * @throws PIECE_FLOW_ERROR_PROTECTED_EVENT
     */
    function _configureState($state)
    {
        for ($i = 0, $count = count(@$state['transitions']); $i < $count; ++$i) {
            if ($state['transitions'][$i]['event'] == PIECE_FLOW_PROTECTED_EVENT
                || $this->_fsm->isProtectedEvent($state['transitions'][$i]['event'])
                ) {
                Piece_Flow_Error::push(PIECE_FLOW_ERROR_PROTECTED_EVENT,
                                       "The event [ {$state['transitions'][$i]['event']} ] cannot be used in flow definitions."
                                       );
                return;
            }

            $this->_fsm->addTransition($state['name'],
                                       $state['transitions'][$i]['event'],
                                       $state['transitions'][$i]['nextState'],
                                       $this->_wrapEventTriggerAction(@$state['transitions'][$i]['action']),
                                       $this->_wrapAction(@$state['transitions'][$i]['guard'])
                                       );
        }

        if (array_key_exists('entry', $state)) {
            $this->_fsm->setEntryAction($state['name'],
                                        $this->_wrapAction(@$state['entry'])
                                        );
        }

        if (array_key_exists('exit', $state)) {
            $this->_fsm->setExitAction($state['name'],
                                       $this->_wrapAction(@$state['exit'])
                                       );
        }

        if (array_key_exists('activity', $state)) {
            $this->_fsm->setActivity($state['name'],
                                     $this->_wrapEventTriggerAction(@$state['activity'])
                                     );
        }
    }

    // }}}
    // {{{ _wrapAction()

    /**
     * Wraps a simple action up with a Piece_Flow_Action object and returns
     * a callback. The simple action means that the action is entry action or
     * exit action or guard.
     *
     * @param array $action
     * @return array
     */
    function _wrapAction($action)
    {
        if (is_null($action)) {
            return $action;
        }

        $eventHandler = &new Piece_Flow_EventHandler($this->_flow, @$action['class'], $action['method'], $this->_actionDirectory);
        return array(&$eventHandler, 'invoke');
    }

    // }}}
    // {{{ _configureViewState()

    /**
     * Configures a view state.
     *
     * @param array $state
     */
    function _configureViewState($state)
    {
        $this->_configureState($state);
    }

    // }}}
    // {{{ _wrapEventTriggerAction()

    /**
     * Wraps an event trigger action up with a Piece_Flow_Action object and
     * returns a callback. The event trigger action means that the action is
     * transition action or activity.
     *
     * @param array $action
     * @return array
     */
    function _wrapEventTriggerAction($action)
    {
        if (is_null($action)) {
            return $action;
        }

        $eventHandler = &new Piece_Flow_EventHandler($this->_flow, @$action['class'], $action['method'], $this->_actionDirectory);
        return array(&$eventHandler, 'invokeAndTriggerEvent');
    }

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
