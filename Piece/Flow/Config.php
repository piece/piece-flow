<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * PHP versions 4 and 5
 *
 * Copyright (c) 2006-2007 KUBO Atsuhiro <kubo@iteman.jp>,
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
 * @copyright  2006-2007 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version    Release: @package_version@
 * @since      File available since Release 0.1.0
 */

// {{{ Piece_Flow_Config

/**
 * A class representing a configuration of one flow.
 *
 * @package    Piece_Flow
 * @copyright  2006-2007 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version    Release: @package_version@
 * @since      Class available since Release 0.1.0
 */
class Piece_Flow_Config
{

    // {{{ properties

    /**#@+
     * @access public
     */

    /**#@-*/

    /**#@+
     * @access private
     */

    var $_name;
    var $_firstState;
    var $_lastState;
    var $_viewStates = array();
    var $_actionStates = array();
    var $_initialAction;
    var $_finalAction;

    /**#@-*/

    /**#@+
     * @access public
     */

    // }}}
    // {{{ setName()

    /**
     * Sets the name of the flow.
     *
     * @param string $name
     */
    function setName($name)
    {
        $this->_name = $name;
    }

    // }}}
    // {{{ setFirstState()

    /**
     * Sets the given state as the first state.
     *
     * @param string $state
     */
    function setFirstState($state)
    {
        $this->_firstState = $state;
    }

    // }}}
    // {{{ setLastState()

    /**
     * Sets the given state and the view string as the last state.
     *
     * @param string $state
     * @param string $view
     */
    function setLastState($state, $view)
    {
        $this->_lastState = $state;
        $this->addViewState($state, $view);
    }

    // }}}
    // {{{ addViewState()

    /**
     * Adds the state as view state. The view string will correspond to the
     * given state.
     *
     * @param string $state
     * @param string $view
     */
    function addViewState($state, $view)
    {
        $this->_viewStates[$state] = array('name' => $state,
                                           'view' => $view,
                                           'transitions' => array(),
                                           'entry' => null,
                                           'exit' => null,
                                           'activity' => null
                                           );
    }

    // }}}
    // {{{ addActionState()

    /**
     * Adds the state as action state.
     *
     * @param string $state
     */
    function addActionState($state)
    {
        $this->_actionStates[$state] = array('name' => $state,
                                             'transitions' => array(),
                                             'entry' => null,
                                             'exit' => null,
                                             'activity' => null
                                             );
    }

    // }}}
    // {{{ addTransition()

    /**
     * Adds the state transition.
     *
     * @param string $state
     * @param string $event
     * @param string $nextState
     * @param array  $action
     * @param array  $guard
     */
    function addTransition($state, $event, $nextState, $action = null,
                           $guard = null
                           )
    {
        $states = &$this->_getAppropriateStates($state);
        $states[$state]['transitions'][] = array('event' => $event,
                                                 'nextState' => $nextState,
                                                 'action' => $action,
                                                 'guard' => $guard
                                                 );
    }

    // }}}
    // {{{ setEntryAction()

    /**
     * Sets the entry action to the given state.
     *
     * @param string $state
     * @param array  $action
     */
    function setEntryAction($state, $action)
    {
        $states = &$this->_getAppropriateStates($state);
        $states[$state]['entry'] = $action;
    }

    // }}}
    // {{{ setExitAction()

    /**
     * Sets the exit action to the given state.
     *
     * @param string $state
     * @param array  $action
     */
    function setExitAction($state, $action)
    {
        $states = &$this->_getAppropriateStates($state);
        $states[$state]['exit'] = $action;
    }

    // }}}
    // {{{ setActivity()

    /**
     * Sets the activity to the given state.
     *
     * @param string $state
     * @param array  $activity
     */
    function setActivity($state, $activity)
    {
        $states = &$this->_getAppropriateStates($state);
        $states[$state]['activity'] = $activity;
    }

    // }}}
    // {{{ getName()

    /**
     * Gets the name of the flow.
     *
     * @return string
     */
    function getName()
    {
        return $this->_name;
    }

    // }}}
    // {{{ getFirstState()

    /**
     * Gets the first state of the flow.
     *
     * @return string
     */
    function getFirstState()
    {
        return $this->_firstState;
    }

    // }}}
    // {{{ getLastState()

    /**
     * Gets the last state of the flow.
     *
     * @return array
     */
    function getLastState()
    {
        return $this->_lastState;
    }

    // }}}
    // {{{ getViewStates()

    /**
     * Gets view states of the flow.
     *
     * @return array
     */
    function getViewStates()
    {
        return $this->_viewStates;
    }

    // }}}
    // {{{ getActionStates()

    /**
     * Gets action states of the flow.
     *
     * @return array
     */
    function getActionStates()
    {
        return $this->_actionStates;
    }

    // }}}
    // {{{ setInitialAction()

    /**
     * Sets the initial action of the flow.
     *
     * @param array $action
     */
    function setInitialAction($action)
    {
        $this->_initialAction = $action;
    }

    // }}}
    // {{{ getInitialAction()

    /**
     * Gets the initial action of the flow.
     *
     * @return array
     */
    function getInitialAction()
    {
        return $this->_initialAction;
    }

    /**
     * Sets the final action of the flow.
     *
     * @param array $action
     */
    function setFinalAction($action)
    {
        $this->_finalAction = $action;
    }

    // }}}
    // {{{ getFinalAction()

    /**
     * Gets the final action of the flow.
     *
     * @return array
     */
    function getFinalAction()
    {
        return $this->_finalAction;
    }

    /**#@-*/

    /**#@+
     * @access private
     */

    // }}}
    // {{{ _getAppropriateStates()

    /**
     * Gets an appropriate states corresponding to the given state.
     *
     * @param string $state
     * @return array
     */
    function &_getAppropriateStates($state)
    {
        if (array_key_exists($state, $this->_viewStates)) {
            return $this->_viewStates;
        }

        return $this->_actionStates;
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
