<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * PHP version 5.3
 *
 * Copyright (c) 2007-2008, 2012 KUBO Atsuhiro <kubo@iteman.jp>,
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
 * @copyright  2007-2008, 2012 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version    Release: @package_version@
 * @since      File available since Release 1.14.0
 */

namespace Piece\Flow;

use Stagehand\FSM\Event;
use Stagehand\FSM\State;

use Piece\Flow\PageFlow\EventHandler;
use Piece\Flow\PageFlow\PageFlow;

/**
 * The FSM builder.
 *
 * @package    Piece_Flow
 * @copyright  2007-2008, 2012 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version    Release: @package_version@
 * @since      Class available since Release 1.14.0
 */
class FSMBuilder
{
    protected $flow;
    protected $actionDirectory;

    /**
     * @var \Stagehand\FSM\FSMBuilder
     * @since Property available since Release 2.0.0
     */
    protected $fsmBuilder;

    /**
     * Sets a Flow object to the property.
     *
     * @param \Piece\Flow\PageFlow\PageFlow $flow
     * @param string     $actionDirectory
     */
    public function __construct(PageFlow $flow, $actionDirectory)
    {
        $this->fsmBuilder = new \Stagehand\FSM\FSMBuilder();
        $this->flow = $flow;
        $this->actionDirectory = $actionDirectory;
    }

    /**
     * Builds a FSM with the given configuration.
     *
     * @param \Piece\Flow\Config $config
     * @return \Stagehand\FSM\FSM
     * @throws \Piece\Flow\ProtectedStateException
     */
    public function build(Config $config)
    {
        $firstState = $config->getFirstState();
        if ($this->fsmBuilder->getFSM()->isProtectedState($firstState)) {
            throw new ProtectedStateException("The state [ $firstState ] cannot be used in flow definitions.");
        }

        $this->fsmBuilder->setFirstState($firstState);
        $this->fsmBuilder->setName($config->getName());

        $lastState = $config->getLastState();
        if (!is_null($lastState)) {
            if ($this->fsmBuilder->getFSM()->isProtectedState($lastState)) {
                throw new ProtectedStateException("The state [ $lastState ] cannot be used in flow definitions.");
            }

            $this->fsmBuilder->addTransition($lastState, Event::EVENT_END, State::STATE_FINAL);
        }

        $this->configureViewStates($config->getViewStates());
        $this->configureActionStates($config->getActionStates());

        $initial = $config->getInitialAction();
        if (!is_null($initial)) {
            $this->fsmBuilder->setExitAction(State::STATE_INITIAL, $this->wrapAction($initial));
        }

        $final = $config->getFinalAction();
        if (!is_null($final)) {
            $this->fsmBuilder->setEntryAction(State::STATE_FINAL, $this->wrapAction($final));
        }

        return $this->fsmBuilder->getFSM();
    }

    /**
     * Configures view states.
     *
     * @param array $states
     * @throws \Piece\Flow\ProtectedStateException
     */
    protected function configureViewStates(array $states)
    {
        foreach ($states as $key => $state) {
            if ($this->fsmBuilder->getFSM()->isProtectedState($state['name'])) {
                throw new ProtectedStateException("The state [ {$state['name']} ] cannot be used in flow definitions.");
            }

            $this->configureViewState($state);
        }
    }

    /**
     * Configures action states.
     *
     * @param array $states
     * @throws \Piece\Flow\ProtectedStateException
     */
    protected function configureActionStates(array $states)
    {
        foreach ($states as $key => $state) {
            if ($this->fsmBuilder->getFSM()->isProtectedState($state['name'])) {
                throw new ProtectedStateException("The state [ {$state['name']} ] cannot be used in flow definitions.");
            }

            $this->configureState($state);
        }
    }

    /**
     * Configures a state.
     *
     * @param array $state
     * @throws \Piece\Flow\ProtectedEventException
     */
    protected function configureState(array $state)
    {
        for ($i = 0, $count = count(@$state['transitions']); $i < $count; ++$i) {
            if ($state['transitions'][$i]['event'] == PageFlow::EVENT_PROTECTED
                || $this->fsmBuilder->getFSM()->isProtectedEvent($state['transitions'][$i]['event'])
                ) {
                throw new ProtectedEventException("The event [ {$state['transitions'][$i]['event']} ] cannot be used in flow definitions.");
            }

            $this->fsmBuilder->addTransition($state['name'],
                                       $state['transitions'][$i]['event'],
                                       $state['transitions'][$i]['nextState'],
                                       $this->wrapEventTriggerAction(@$state['transitions'][$i]['action']),
                                       $this->wrapAction(@$state['transitions'][$i]['guard'])
                                       );
        }

        if (array_key_exists('entry', $state)) {
            $this->fsmBuilder->setEntryAction($state['name'],
                                        $this->wrapAction(@$state['entry'])
                                        );
        }

        if (array_key_exists('exit', $state)) {
            $this->fsmBuilder->setExitAction($state['name'],
                                       $this->wrapAction(@$state['exit'])
                                       );
        }

        if (array_key_exists('activity', $state)) {
            $this->fsmBuilder->setActivity($state['name'],
                                     $this->wrapEventTriggerAction(@$state['activity'])
                                     );
        }
    }

    /**
     * Wraps a simple action up with an Action object and returns
     * a callback. The simple action means that the action is entry action or
     * exit action or guard.
     *
     * @param array $action
     * @return array
     */
    protected function wrapAction(array $action = null)
    {
        if (is_null($action)) {
            return $action;
        }

        $eventHandler = new EventHandler($this->flow, @$action['class'], $action['method'], $this->actionDirectory);
        return array($eventHandler, 'invokeAction');
    }

    /**
     * Configures a view state.
     *
     * @param array $state
     */
    protected function configureViewState(array $state)
    {
        $this->configureState($state);
    }

    /**
     * Wraps an event trigger action up with an Action object and
     * returns a callback. The event trigger action means that the action is
     * transition action or activity.
     *
     * @param array $action
     * @return array
     */
    protected function wrapEventTriggerAction(array $action = null)
    {
        if (is_null($action)) {
            return $action;
        }

        $eventHandler = new EventHandler($this->flow, @$action['class'], $action['method'], $this->actionDirectory);
        return array($eventHandler, 'invokeAndTriggerEvent');
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
