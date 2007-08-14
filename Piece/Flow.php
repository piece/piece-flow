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
 * @link       http://www.martinfowler.com/eaaCatalog/applicationController.html
 * @link       http://opensource2.atlassian.com/confluence/spring/display/WEBFLOW/Home
 * @link       http://www-128.ibm.com/developerworks/java/library/j-cb03216/
 * @link       http://www-06.ibm.com/jp/developerworks/java/060412/j_j-cb03216.shtml
 * @see        Stagehand_FSM
 * @since      File available since Release 0.1.0
 */

require_once 'Stagehand/FSM.php';
require_once 'Piece/Flow/EventHandler.php';
require_once 'Piece/Flow/Error.php';
require_once 'Piece/Flow/ConfigReader.php';

// {{{ constants

define('PIECE_FLOW_PROTECTED_EVENT', '_Piece_Flow_Protected_Event');

// }}}
// {{{ Piece_Flow

/**
 * A web flow engine which to handle page flows of web applications.
 *
 * Piece_Flow provides a web flow engine based on Finite State Machine(FSM).
 * Piece_Flow can handle two different states. The view state is a state
 * which is associated with a view string. The action state is a simple
 * state, which has no association with all views.
 * If the engine once started, the application will be put under control of
 * it.
 *
 * @package    Piece_Flow
 * @copyright  2006-2007 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @link       http://www.martinfowler.com/eaaCatalog/applicationController.html
 * @link       http://opensource2.atlassian.com/confluence/spring/display/WEBFLOW/Home
 * @link       http://www-128.ibm.com/developerworks/java/library/j-cb03216/
 * @link       http://www-06.ibm.com/jp/developerworks/java/060412/j_j-cb03216.shtml
 * @see        Stagehand_FSM
 * @since      Class available since Release 0.1.0
 */
class Piece_Flow
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
    var $_name;
    var $_views;
    var $_attributes = array();
    var $_lastState;

    /**#@-*/

    /**#@+
     * @access public
     */

    // }}}
    // {{{ configure()

    /**
     * Configures a FSM with the given configuration.
     *
     * @param mixed  $source
     * @param string $driverName
     * @param string $cacheDirectory
     * @throws PIECE_FLOW_ERROR_NOT_FOUND
     * @throws PIECE_FLOW_ERROR_NOT_READABLE
     * @throws PIECE_FLOW_ERROR_INVALID_FORMAT
     * @throws PIECE_FLOW_ERROR_PROTECTED_EVENT
     * @throws PIECE_FLOW_ERROR_PROTECTED_STATE
     * @throws PIECE_FLOW_ERROR_CANNOT_READ
     */
    function configure($source, $driverName = null, $cacheDirectory = null)
    {
        $config = &Piece_Flow_ConfigReader::read($source, $driverName, $cacheDirectory);
        if (Piece_Flow_Error::hasErrors('exception')) {
            return;
        }

        $this->_fsm = &new Stagehand_FSM();

        $firstState = $config->getFirstState();
        if ($this->_fsm->isProtectedState($firstState)) {
            Piece_Flow_Error::push(PIECE_FLOW_ERROR_PROTECTED_STATE,
                                   "The state [ $firstState ] cannot be used in flow definitions."
                                   );
            return;
        }

        $this->_fsm->setFirstState($firstState);

        $this->_name = $config->getName();
        $this->_fsm->setName($this->_name);

        $lastState = $config->getLastState();
        if (!is_null($lastState)) {
            if ($this->_fsm->isProtectedState($lastState)) {
                Piece_Flow_Error::push(PIECE_FLOW_ERROR_PROTECTED_STATE,
                                       "The state [ $lastState ] cannot be used in flow definitions."
                                       );
                return;
            }

            $this->_fsm->addTransition($lastState,
                                       STAGEHAND_FSM_EVENT_END,
                                       STAGEHAND_FSM_STATE_FINAL
                                       );
            $this->_lastState = $lastState;
        }

        $this->_configureViewStates($config->getViewStates());
        if (Piece_Flow_Error::hasErrors('exception')) {
            return;
        }

        $this->_configureActionStates($config->getActionStates());
        if (Piece_Flow_Error::hasErrors('exception')) {
            return;
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
    }

    // }}}
    // {{{ getView()

    /**
     * Gets an appropriate view string which corresponding to the current
     * state.
     *
     * @return string
     * @throws PIECE_FLOW_ERROR_INVALID_TRANSITION
     * @throws PIECE_FLOW_ERROR_INVALID_OPERATION
     */
    function getView()
    {
        if (!$this->_started()) {
            Piece_Flow_Error::push(PIECE_FLOW_ERROR_INVALID_OPERATION,
                                   __FUNCTION__ . ' method must be called after starting flows.'
                                   );
            return;
        }

        if (!$this->isFinalState()) {
            $viewIndex = $this->getCurrentStateName();
        } else {
            $viewIndex = $this->getPreviousStateName();
        }

        if (!array_key_exists($viewIndex, $this->_views)) {
            Piece_Flow_Error::push(PIECE_FLOW_ERROR_INVALID_TRANSITION,
                                   "A invalid transition detected. The state [ $viewIndex ] does not have a view. Maybe The state [ $viewIndex ] is an action state. Check the definition of the flow [ {$this->_name} ]."
                                   );
            return;
        }

        return $this->_views[$viewIndex];
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
    // {{{ start()

    /**
     * Starts the Finite State Machine.
     *
     * @throws PIECE_FLOW_ERROR_CANNOT_INVOKE
     * @throws PIECE_FLOW_ERROR_ALREADY_SHUTDOWN
     */
    function start()
    {
        $this->_fsm->start();
        if (Piece_Flow_Error::hasErrors('exception')) {
            Piece_Flow_Error::push(PIECE_FLOW_ERROR_CANNOT_INVOKE,
                                   "An action could not be invoked for any reasons.",
                                   'exception',
                                   array(),
                                   Piece_Flow_Error::pop()
                                   );
            return;
        }

        if (Stagehand_FSM_Error::hasErrors('exception')) {
            Piece_Flow_Error::push(PIECE_FLOW_ERROR_ALREADY_SHUTDOWN,
                                   "The flow [ {$this->_name} ] was already shutdown.",
                                   'exception',
                                   array(),
                                   Stagehand_FSM_Error::pop()
                                   );
        }
    }

    // }}}
    // {{{ triggerEvent()

    /**
     * Triggers the given state.
     *
     * @param string $eventName
     * @param boolean $transitionToHistoryMarker
     * @return Stagehand_FSM_State
     * @throws PIECE_FLOW_ERROR_CANNOT_INVOKE
     * @throws PIECE_FLOW_ERROR_ALREADY_SHUTDOWN
     * @throws PIECE_FLOW_ERROR_INVALID_OPERATION
     */
    function &triggerEvent($eventName, $transitionToHistoryMarker = false)
    {
        if (!$this->_started()) {
            Piece_Flow_Error::push(PIECE_FLOW_ERROR_INVALID_OPERATION,
                                   __FUNCTION__ . ' method must be called after starting flows.'
                                   );
            $return = null;
            return $return;
        }

        if ($this->_fsm->isProtectedEvent($eventName)) {
            Piece_Flow_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));
            Piece_Flow_Error::push(PIECE_FLOW_ERROR_PROTECTED_EVENT,
                                   "The event [ $eventName] cannot be called directly. The current state [ " .
                                   $this->getCurrentStateName() . ' ] will only be updated.',
                                   'warning'
                                   );
            Piece_Flow_Error::popCallback();
            $eventName = PIECE_FLOW_PROTECTED_EVENT;
        }

        $state = &$this->_fsm->triggerEvent($eventName,
                                            $transitionToHistoryMarker
                                            );
        if (Piece_Flow_Error::hasErrors('exception')) {
            $error = Piece_Flow_Error::pop();
            if ($error['package'] == 'Piece_Flow'
                && $error['code'] == PIECE_FLOW_ERROR_CANNOT_INVOKE
                ) {
                $error = $error['repackage'];
            }

            Piece_Flow_Error::push(PIECE_FLOW_ERROR_CANNOT_INVOKE,
                                   "An action could not be invoked for any reasons.",
                                   'exception',
                                   array(),
                                   $error
                                   );
            $return = null;
            return $return;
        }

        if (Stagehand_FSM_Error::hasErrors('exception')) {
            Piece_Flow_Error::push(PIECE_FLOW_ERROR_ALREADY_SHUTDOWN,
                                   "The flow [ {$this->_name} ] was already shutdown.",
                                   'exception',
                                   array(),
                                   Stagehand_FSM_Error::pop()
                                   );
            $return = null;
            return $return;
        }

        if (!is_null($this->_lastState)
            && $state->getName() == $this->_lastState
            ) {
            Piece_Flow_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));
            $state = &$this->_fsm->triggerEvent(STAGEHAND_FSM_EVENT_END);
            Piece_Flow_Error::popCallback();
            if (Stagehand_FSM_Error::hasErrors('exception')) {
                Piece_Flow_Error::push(PIECE_FLOW_ERROR_ALREADY_SHUTDOWN,
                                       "The flow [ {$this->_name} ] was already shutdown.",
                                       'exception',
                                       array(),
                                       Stagehand_FSM_Error::pop()
                                       );
                $return = null;
                return $return;
            }
        }

        return $state;
    }

    // }}}
    // {{{ getPreviousStateName()

    /**
     * Gets the previous state name.
     *
     * @return string
     */
    function getPreviousStateName()
    {
        $state = &$this->_fsm->getPreviousState();
        return $state->getName();
    }

    // }}}
    // {{{ getCurrentStateName()

    /**
     * Gets the current state name.
     *
     * @return string
     */
    function getCurrentStateName()
    {
        $state = &$this->_fsm->getCurrentState();
        return $state->getName();
    }

    // }}}
    // {{{ setAttribute()

    /**
     * Sets an attribute for the flow.
     *
     * @param string $name
     * @param mixed  $value
     * @throws PIECE_FLOW_ERROR_INVALID_OPERATION
     */
    function setAttribute($name, $value)
    {
        if (!$this->_started()) {
            Piece_Flow_Error::push(PIECE_FLOW_ERROR_INVALID_OPERATION,
                                   __FUNCTION__ . ' method must be called after starting flows.'
                                   );
            return;
        }

        $this->_attributes[$name] = $value;
    }

    // }}}
    // {{{ setAttributeByRef()

    /**
     * Sets an attribute by reference for the flow.
     *
     * @param string $name
     * @param mixed  &$value
     * @throws PIECE_FLOW_ERROR_INVALID_OPERATION
     */
    function setAttributeByRef($name, &$value)
    {
        if (!$this->_started()) {
            Piece_Flow_Error::push(PIECE_FLOW_ERROR_INVALID_OPERATION,
                                   __FUNCTION__ . ' method must be called after starting flows.'
                                   );
            return;
        }

        $this->_attributes[$name] = &$value;
    }

    // }}}
    // {{{ hasAttribute()

    /**
     * Returns whether the flow has an attribute with a given name.
     *
     * @param string $name
     * @return boolean
     * @throws PIECE_FLOW_ERROR_INVALID_OPERATION
     */
    function hasAttribute($name)
    {
        if (!$this->_started()) {
            Piece_Flow_Error::push(PIECE_FLOW_ERROR_INVALID_OPERATION,
                                   __FUNCTION__ . ' method must be called after starting flows.'
                                   );
            return;
        }

        return array_key_exists($name, $this->_attributes);
    }

    // }}}
    // {{{ getAttribute()

    /**
     * Gets an attribute for the flow.
     *
     * @param string $name
     * @return mixed
     * @throws PIECE_FLOW_ERROR_INVALID_OPERATION
     */
    function &getAttribute($name)
    {
        if (!$this->_started()) {
            Piece_Flow_Error::push(PIECE_FLOW_ERROR_INVALID_OPERATION,
                                   __FUNCTION__ . ' method must be called after starting flows.'
                                   );
            $return = null;
            return $return;
        }

        return $this->_attributes[$name];
    }

    // }}}
    // {{{ setPayload()

    /**
     * Sets a user defined payload to the FSM.
     *
     * @param mixed &$payload
     * @throws PIECE_FLOW_ERROR_INVALID_OPERATION
     */
    function setPayload(&$payload)
    {
        if (!is_a($this->_fsm, 'Stagehand_FSM')) {
            Piece_Flow_Error::push(PIECE_FLOW_ERROR_INVALID_OPERATION,
                                   __FUNCTION__ . ' method must be called after configuring flows.'
                                   );
            return;
        }

        $this->_fsm->setPayload($payload);
    }

    // }}}
    // {{{ isFinalState()

    /**
     * Returns whether the current state is the final state of the flow.
     *
     * @return boolean
     * @throws PIECE_FLOW_ERROR_INVALID_OPERATION
     */
    function isFinalState()
    {
        if (!is_a($this->_fsm, 'Stagehand_FSM')) {
            Piece_Flow_Error::push(PIECE_FLOW_ERROR_INVALID_OPERATION,
                                   __FUNCTION__ . ' method must be called after configuring flows.'
                                   );
            return;
        }

        return $this->getCurrentStateName() == STAGEHAND_FSM_STATE_FINAL;
    }

    // }}}
    // {{{ removeAttribute()

    /**
     * Removes an attribute from the flow.
     *
     * @param string $name
     * @throws PIECE_FLOW_ERROR_INVALID_OPERATION
     */
    function removeAttribute($name)
    {
        if (!$this->_started()) {
            Piece_Flow_Error::push(PIECE_FLOW_ERROR_INVALID_OPERATION,
                                   __FUNCTION__ . ' method must be called after starting flows.'
                                   );
            return;
        }

        unset($this->_attributes[$name]);
    }

    // }}}
    // {{{ clearAttributes()

    /**
     * Removes all attributes from the flow.
     *
     * @throws PIECE_FLOW_ERROR_INVALID_OPERATION
     */
    function clearAttributes()
    {
        if (!$this->_started()) {
            Piece_Flow_Error::push(PIECE_FLOW_ERROR_INVALID_OPERATION,
                                   __FUNCTION__ . ' method must be called after starting flows.'
                                   );
            return;
        }

        $this->_attributes = array();
    }

    // }}}
    // {{{ clearPayload()

    /**
     * Removes the payload from the FSM.
     */
    function clearPayload()
    {
        if (!is_a($this->_fsm, 'Stagehand_FSM')) {
            Piece_Flow_Error::push(PIECE_FLOW_ERROR_INVALID_OPERATION,
                                   __FUNCTION__ . ' method must be called after configuring flows.'
                                   );
            return;
        }

        $this->_fsm->clearPayload();
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
     * @throws PIECE_FLOW_ERROR_PROTECTED_EVENT
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
            if (Piece_Flow_Error::hasErrors('exception')) {
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
     * @throws PIECE_FLOW_ERROR_PROTECTED_EVENT
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
            if (Piece_Flow_Error::hasErrors('exception')) {
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
            if ($this->_fsm->isProtectedEvent($state['transitions'][$i]['event'])
                || $state['transitions'][$i]['event'] == PIECE_FLOW_PROTECTED_EVENT
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

        $eventHandler = &new Piece_Flow_EventHandler($this, @$action['class'], $action['method']);
        return array(&$eventHandler, 'invoke');
    }

    // }}}
    // {{{ _configureViewState()

    /**
     * Configures a view state.
     *
     * @param array $state
     * @throws PIECE_FLOW_ERROR_PROTECTED_EVENT
     */
    function _configureViewState($state)
    {
        $this->_views[$state['name']] = $state['view'];
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

        $eventHandler = &new Piece_Flow_EventHandler($this, @$action['class'], $action['method']);
        return array(&$eventHandler, 'invokeAndTriggerEvent');
    }

    // }}}
    // {{{ _started()

    /**
     * Returns whether the flow has started or not.
     *
     * @return boolean
     */
    function _started()
    {
        return is_a($this->_fsm, 'Stagehand_FSM') && !is_null($this->_fsm->getCurrentState());
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
