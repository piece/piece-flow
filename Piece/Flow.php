<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * PHP versions 4 and 5
 *
 * Copyright (c) 2006-2008 KUBO Atsuhiro <iteman@users.sourceforge.net>,
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
 * @copyright  2006-2008 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    SVN: $Id$
 * @link       http://www.martinfowler.com/eaaCatalog/applicationController.html
 * @link       http://opensource2.atlassian.com/confluence/spring/display/WEBFLOW/Home
 * @link       http://www-128.ibm.com/developerworks/java/library/j-cb03216/
 * @link       http://www-06.ibm.com/jp/developerworks/java/060412/j_j-cb03216.shtml
 * @since      File available since Release 0.1.0
 */

require_once 'Piece/Flow/Error.php';
require_once 'Piece/Flow/ConfigReader.php';
require_once 'Piece/Flow/FSMBuilder.php';
require_once 'Stagehand/FSM/Error.php';
require_once 'Piece/Flow/ProtedtedEvent.php';

// {{{ Piece_Flow

/**
 * A web flow engine for handling page flows of web applications.
 *
 * Piece_Flow provides a web flow engine based on Finite State Machine (FSM).
 * Piece_Flow can handle two different states. The view state is a state
 * which is associated with a view string. The action state is a simple
 * state, which has no association with all views.
 * If the engine once started, the application will be put under control of
 * it.
 *
 * @package    Piece_Flow
 * @copyright  2006-2008 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @link       http://www.martinfowler.com/eaaCatalog/applicationController.html
 * @link       http://opensource2.atlassian.com/confluence/spring/display/WEBFLOW/Home
 * @link       http://www-128.ibm.com/developerworks/java/library/j-cb03216/
 * @link       http://www-06.ibm.com/jp/developerworks/java/060412/j_j-cb03216.shtml
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
    var $_lastEventIsValid = true;
    var $_actionDirectory;

    /**#@-*/

    /**#@+
     * @access public
     */

    // }}}
    // {{{ configure()

    /**
     * Builds a FSM with the given configuration.
     *
     * @param mixed  $source
     * @param string $driverName
     * @param string $cacheDirectory
     * @param string $actionDirectory
     * @param string $configDirectory
     * @param string $configExtension
     */
    function configure($source,
                       $driverName = null,
                       $cacheDirectory = null,
                       $actionDirectory = null,
                       $configDirectory = null,
                       $configExtension = null
                       )
    {
        $config = &Piece_Flow_ConfigReader::read($source,
                                                 $driverName,
                                                 $cacheDirectory,
                                                 $configDirectory,
                                                 $configExtension
                                                 );
        if (Piece_Flow_Error::hasErrors()) {
            return;
        }

        $this->_name = $config->getName();
        $fsmBuilder = &new Piece_Flow_FSMBuilder($this, $actionDirectory);
        $fsm = &$fsmBuilder->build($config);
        if (Piece_Flow_Error::hasErrors()) {
            return;
        }

        $this->_fsm = &$fsm;

        $lastState = $config->getLastState();
        if (!is_null($lastState)) {
            $this->_lastState = $lastState;
        }

        foreach ($config->getViewStates() as $key => $state) {
            $this->_views[ $state['name'] ] = $state['view'];
        }

        $this->_actionDirectory = $actionDirectory;
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
     */
    function start()
    {
        $this->_fsm->start();
    }

    // }}}
    // {{{ triggerEvent()

    /**
     * Triggers the given event.
     *
     * @param string $eventName
     * @param boolean $transitionToHistoryMarker
     * @return Stagehand_FSM_State
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

        if ($eventName == PIECE_FLOW_PROTECTED_EVENT || $this->_fsm->isProtectedEvent($eventName)) {
            trigger_error("The event [ $eventName ] cannot be called directly. The current state [ " .
                          $this->getCurrentStateName() . ' ] will only be updated.',
                          E_USER_WARNING
                          );
            $eventName = PIECE_FLOW_PROTECTED_EVENT;
        }

        $this->_lastEventIsValid = $this->_fsm->hasEvent($eventName);

        $state = &$this->_fsm->triggerEvent($eventName,
                                            $transitionToHistoryMarker
                                            );
        if (Stagehand_FSM_Error::hasErrors()) {
            $return = null;
            return $return;
        }

        if (!is_null($this->_lastState)
            && $state->getName() == $this->_lastState
            ) {
            $state = &$this->_fsm->triggerEvent(STAGEHAND_FSM_EVENT_END);
            if (Stagehand_FSM_Error::hasErrors()) {
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
     * @throws PIECE_FLOW_ERROR_INVALID_OPERATION
     */
    function getPreviousStateName()
    {
        if (!$this->_started()) {
            Piece_Flow_Error::push(PIECE_FLOW_ERROR_INVALID_OPERATION,
                                   __FUNCTION__ . ' method must be called after starting flows.'
                                   );
            return;
        }

        $state = &$this->_fsm->getPreviousState();
        return $state->getName();
    }

    // }}}
    // {{{ getCurrentStateName()

    /**
     * Gets the current state name.
     *
     * @return string
     * @throws PIECE_FLOW_ERROR_INVALID_OPERATION
     */
    function getCurrentStateName()
    {
        if (!$this->_started()) {
            Piece_Flow_Error::push(PIECE_FLOW_ERROR_INVALID_OPERATION,
                                   __FUNCTION__ . ' method must be called after starting flows.'
                                   );
            return;
        }

        $state = &$this->_fsm->getCurrentState();
        return $state->getName();
    }

    // }}}
    // {{{ setAttribute()

    /**
     * Sets an attribute for the flow execution.
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
     * Sets an attribute by reference for the flow execution.
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
     * Returns whether the flow execution has an attribute with a given name.
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
     * Gets an attribute for the flow execution.
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
     * Returns whether the current state of the flow execution is the final
     * state.
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
     * Removes an attribute from the flow execution.
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
     * Removes all attributes from the flow execution.
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
     *
     * @since Method available since Release 1.11.0
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

    // }}}
    // {{{ checkLastEvent()

    /**
     * Returns whether the last event which is given by a user is valid or
     * not.
     *
     * @return boolean
     * @since Method available since Release 1.13.0
     */
    function checkLastEvent()
    {
        return $this->_lastEventIsValid;
    }

    // }}}
    // {{{ isViewState()

    /**
     * Tells whether the current state of a flow execution is a view state or not.
     *
     * @return boolean
     * @since Method available since Release 1.16.0
     */
    function isViewState()
    {
        return array_key_exists($this->_getViewIndex(), $this->_views);
    }

    /**#@-*/

    /**#@+
     * @access private
     */

    // }}}
    // {{{ _started()

    /**
     * Returns whether the flow execution has started or not.
     *
     * @return boolean
     */
    function _started()
    {
        return is_a($this->_fsm, 'Stagehand_FSM') && !is_null($this->_fsm->getCurrentState());
    }

    // }}}
    // {{{ _getViewIndex()

    /**
     * Gets an appropriate view index which corresponding to the current state.
     *
     * @return string
     * @since Method available since Release 1.16.0
     */
    function _getViewIndex()
    {
        return !$this->isFinalState() ? $this->getCurrentStateName()
                                      : $this->getPreviousStateName();
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
