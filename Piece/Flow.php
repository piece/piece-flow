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
 * @link       http://www.martinfowler.com/eaaCatalog/applicationController.html
 * @link       http://opensource2.atlassian.com/confluence/spring/display/WEBFLOW/Home
 * @link       http://www-128.ibm.com/developerworks/java/library/j-cb03216/
 * @link       http://www-06.ibm.com/jp/developerworks/java/060412/j_j-cb03216.shtml
 * @see        Stagehand_FSM, Piece_Flow_Action
 * @since      File available since Release 0.1.0
 */

require_once 'Stagehand/FSM.php';
require_once 'Piece/Flow/ActionInvoker.php';
require_once 'Piece/Flow/ConfigReader/Factory.php';

// {{{ Piece_Flow

/**
 * A web flow engine which to handle the page flow of a web application.
 *
 * Piece_Flow provides a web flow engine based on Finite State Machine (FSM).
 * Piece_Flow can handle two different states. The view state is a state
 * associated with a view string. The action state is a simple state, which
 * has no association with all views.
 * If the engine once started, the application will be put under control of
 * it.
 *
 * @package    Piece_Flow
 * @author     KUBO Atsuhiro <iteman2002@yahoo.co.jp>
 * @author     MIYAI Fumihiko <fumichz@yahoo.co.jp>
 * @copyright  2006 KUBO Atsuhiro <iteman2002@yahoo.co.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @link       http://iteman.typepad.jp/piece/
 * @link       http://www.martinfowler.com/eaaCatalog/applicationController.html
 * @link       http://opensource2.atlassian.com/confluence/spring/display/WEBFLOW/Home
 * @link       http://www-128.ibm.com/developerworks/java/library/j-cb03216/
 * @link       http://www-06.ibm.com/jp/developerworks/java/060412/j_j-cb03216.shtml
 * @see        Stagehand_FSM, Piece_Flow_Action
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
     * @param string $type
     * @param string $cacheDirectory
     * @throws PEAR_ErrorStack
     */
    function configure($source, $type = null, $cacheDirectory = null)
    {
        $driver = &Piece_Flow_ConfigReader_Factory::factory($source, $type);
        if (Piece_Flow_Error::isError($driver)) {
            return $driver;
        }

        $config = &$driver->configure($cacheDirectory);
        if (Piece_Flow_Error::isError($config)) {
            return $config;
        }

        $this->_fsm = &new Stagehand_FSM($config->getFirstState());
        $this->_name = $config->getName();
        $this->_fsm->setName($this->_name);
        $this->_configureViewState($config->getLastState());
        $this->_configureViewStates($config->getViewStates());
        $this->_configureActionStates($config->getActionStates());
    }

    // }}}
    // {{{ getView()

    /**
     * Gets an appropriate view string which corresponding to the current
     * state.
     *
     * @return string
     */
    function getView()
    {
        $state = &$this->_fsm->getCurrentState();
        return $this->_views[$state->getName()];
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
     * Triggers the given state.
     *
     * @param string $eventName
     * @param boolean $transitionToHistoryMarker
     * @return Stagehand_FSM_State
     */
    function &triggerEvent($eventName, $transitionToHistoryMarker = false)
    {
        return $this->_fsm->triggerEvent($eventName,
                                         $transitionToHistoryMarker
                                         );
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
     */
    function _configureViewStates($states)
    {
        foreach ($states as $key => $state) {
            $this->_configureViewState($state);
        }
    }

    // }}}
    // {{{ _configureActionStates()

    /**
     * Configures action states.
     *
     * @param array $states
     */
    function _configureActionStates($states)
    {
        foreach ($states as $key => $state) {
            $this->_configureState($state);
        }
    }

    // }}}
    // {{{ _configureState()

    /**
     * Configures the state.
     *
     * @param array $state
     */
    function _configureState($state)
    {
        $this->_fsm->addState($state['name']);

        for ($i = 0; $i < count(@$state['transitions']); ++$i) {
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
     * Wraps a simple action up with a Piece_Flow_ActionInvoker object and
     * returns a callback. The simple action means that the action is entry
     * action or exit action or guard.
     *
     * @param array $action
     * @return array
     */
    function _wrapAction($action)
    {
        if (is_null($action)) {
            return $action;
        }

        $flowAction = &new Piece_Flow_ActionInvoker($this,
                                                    $action['class'],
                                                    $action['method']
                                                    );
        return array(&$flowAction, 'invoke');
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

        $flowAction = &new Piece_Flow_ActionInvoker($this,
                                                    $action['class'],
                                                    $action['method']
                                                    );
        return array(&$flowAction, 'invokeAndTriggerEvent');
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
