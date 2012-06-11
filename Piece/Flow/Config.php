<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * PHP version 5.3
 *
 * Copyright (c) 2006-2007, 2012 KUBO Atsuhiro <kubo@iteman.jp>,
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
 * @copyright  2006-2007, 2012 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version    Release: @package_version@
 * @since      File available since Release 0.1.0
 */

namespace Piece\Flow;

/**
 * A class representing a configuration of one flow.
 *
 * @package    Piece_Flow
 * @copyright  2006-2007, 2012 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version    Release: @package_version@
 * @since      Class available since Release 0.1.0
 */
class Config
{
    protected $name;
    protected $firstState;
    protected $lastState;
    protected $viewStates = array();
    protected $actionStates = array();
    protected $initialAction;
    protected $finalAction;

    /**
     * Sets the name of the flow.
     *
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * Sets the given state as the first state.
     *
     * @param string $state
     */
    public function setFirstState($state)
    {
        $this->firstState = $state;
    }

    /**
     * Sets the given state and the view string as the last state.
     *
     * @param string $state
     * @param string $view
     */
    public function setLastState($state, $view)
    {
        $this->lastState = $state;
        $this->addViewState($state, $view);
    }

    /**
     * Adds the state as view state. The view string will correspond to the
     * given state.
     *
     * @param string $state
     * @param string $view
     */
    public function addViewState($state, $view)
    {
        $this->viewStates[$state] = array('name' => $state,
                                           'view' => $view,
                                           'transitions' => array(),
                                           'entry' => null,
                                           'exit' => null,
                                           'activity' => null
                                           );
    }

    /**
     * Adds the state as action state.
     *
     * @param string $state
     */
    public function addActionState($state)
    {
        $this->actionStates[$state] = array('name' => $state,
                                             'transitions' => array(),
                                             'entry' => null,
                                             'exit' => null,
                                             'activity' => null
                                             );
    }

    /**
     * Adds the state transition.
     *
     * @param string $state
     * @param string $event
     * @param string $nextState
     * @param array  $action
     * @param array  $guard
     */
    public function addTransition($state, $event, $nextState, $action = null,
                           $guard = null
                           )
    {
        $states = &$this->getAppropriateStates($state);
        $states[$state]['transitions'][] = array('event' => $event,
                                                 'nextState' => $nextState,
                                                 'action' => $action,
                                                 'guard' => $guard
                                                 );
    }

    /**
     * Sets the entry action to the given state.
     *
     * @param string $state
     * @param array  $action
     */
    public function setEntryAction($state, $action)
    {
        $states = &$this->getAppropriateStates($state);
        $states[$state]['entry'] = $action;
    }

    /**
     * Sets the exit action to the given state.
     *
     * @param string $state
     * @param array  $action
     */
    public function setExitAction($state, $action)
    {
        $states = &$this->getAppropriateStates($state);
        $states[$state]['exit'] = $action;
    }

    /**
     * Sets the activity to the given state.
     *
     * @param string $state
     * @param array  $activity
     */
    public function setActivity($state, $activity)
    {
        $states = &$this->getAppropriateStates($state);
        $states[$state]['activity'] = $activity;
    }

    /**
     * Gets the name of the flow.
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Gets the first state of the flow.
     *
     * @return string
     */
    public function getFirstState()
    {
        return $this->firstState;
    }

    /**
     * Gets the last state of the flow.
     *
     * @return string
     */
    public function getLastState()
    {
        return $this->lastState;
    }

    /**
     * Gets view states of the flow.
     *
     * @return array
     */
    public function getViewStates()
    {
        return $this->viewStates;
    }

    /**
     * Gets action states of the flow.
     *
     * @return array
     */
    public function getActionStates()
    {
        return $this->actionStates;
    }

    /**
     * Sets the initial action of the flow.
     *
     * @param array $action
     */
    public function setInitialAction($action)
    {
        $this->initialAction = $action;
    }

    /**
     * Gets the initial action of the flow.
     *
     * @return array
     */
    public function getInitialAction()
    {
        return $this->initialAction;
    }

    /**
     * Sets the final action of the flow.
     *
     * @param array $action
     */
    public function setFinalAction($action)
    {
        $this->finalAction = $action;
    }

    /**
     * Gets the final action of the flow.
     *
     * @return array
     */
    public function getFinalAction()
    {
        return $this->finalAction;
    }

    /**
     * Gets an appropriate states corresponding to the given state.
     *
     * @param string $state
     * @return array
     */
    protected function &getAppropriateStates($state)
    {
        if (array_key_exists($state, $this->viewStates)) {
            return $this->viewStates;
        }

        return $this->actionStates;
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
