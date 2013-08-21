<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * PHP version 5.3
 *
 * Copyright (c) 2007-2008, 2012-2013 KUBO Atsuhiro <kubo@iteman.jp>,
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
 * @copyright  2007-2008, 2012-2013 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version    Release: @package_version@
 * @since      File available since Release 1.14.0
 */

namespace Piece\Flow\PageFlow;

use Stagehand\FSM\Event\DoEvent;
use Stagehand\FSM\Event\EntryEvent;
use Stagehand\FSM\Event\EventInterface;
use Stagehand\FSM\Event\ExitEvent;
use Stagehand\FSM\StateMachine\StateMachineBuilder;
use Stagehand\FSM\State\StateInterface;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Yaml\Yaml;

use Piece\Flow\PageFlow\State\ActionState;
use Piece\Flow\PageFlow\State\ViewState;

/**
 * @package    Piece_Flow
 * @copyright  2007-2008, 2012-2013 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version    Release: @package_version@
 * @since      Class available since Release 1.14.0
 */
class PageFlowGenerator
{
    /**
     * @var \Piece\Flow\PageFlow\PageFlow
     */
    protected $pageFlow;

    /**
     * @var \Piece\Flow\PageFlow\PageFlowRegistry
     * @since Property available since Release 2.0.0
     */
    protected $pageFlowRegistry;

    /**
     * @var \Stagehand\FSM\StateMachine\StateMachineBuilder
     * @since Property available since Release 2.0.0
     */
    protected $fsmBuilder;

    /**
     * @param string $id
     * @param \Piece\Flow\PageFlow\PageFlowRegistry $pageFlowRegistry
     */
    public function __construct($id, PageFlowRegistry $pageFlowRegistry)
    {
        $this->pageFlow = new PageFlow($id);
        $this->pageFlowRegistry = $pageFlowRegistry;
        $this->fsmBuilder = new StateMachineBuilder($this->pageFlow->getID());
    }

    /**
     * Configures a PageFlow object from the specified definition.
     *
     * @throws \Piece\Flow\PageFlow\ProtectedStateException
     */
    public function generate()
    {
        $definition = $this->readDefinition();
        if (in_array($definition['firstState'], array(StateInterface::STATE_INITIAL, StateInterface::STATE_FINAL))) {
            throw new ProtectedStateException("The state [ {$definition['firstState']} ] cannot be used in flow definitions.");
        }

        foreach ($definition['viewState'] as $state) {
            if (in_array($state['name'], array(StateInterface::STATE_INITIAL, StateInterface::STATE_FINAL))) {
                throw new ProtectedStateException("The state [ {$state['name']} ] cannot be used in flow definitions.");
            }

            $this->addState(new ViewState($state['name']));
        }
        foreach ($definition['actionState'] as $state) {
            if (in_array($state['name'], array(StateInterface::STATE_INITIAL, StateInterface::STATE_FINAL))) {
                throw new ProtectedStateException("The state [ {$state['name']} ] cannot be used in flow definitions.");
            }

            $this->addState(new ActionState($state['name']));
        }
        if (!empty($definition['lastState'])) {
            if (in_array($definition['lastState']['name'], array(StateInterface::STATE_INITIAL, StateInterface::STATE_FINAL))) {
                throw new ProtectedStateException("The state [ {$definition['lastState']['name']} ] cannot be used in flow definitions.");
            }

            $this->addState(new ViewState($definition['lastState']['name']));
        }

        if (empty($definition['initial'])) {
            $this->fsmBuilder->setStartState($definition['firstState']);
        } else {
            $this->fsmBuilder->setStartState($definition['firstState'], $this->wrapAction($definition['initial']));
        }

        if (!empty($definition['lastState'])) {
            if (empty($definition['final'])) {
                $this->fsmBuilder->setEndState($definition['lastState']['name'], PageFlowInterface::EVENT_END);
            } else {
                $this->fsmBuilder->setEndState($definition['lastState']['name'], PageFlowInterface::EVENT_END, $this->wrapAction($definition['final']));
            }
            $this->configureViewState($definition['lastState']);
            $this->fsmBuilder->getStateMachine()->getState($definition['lastState']['name'])->setView($definition['lastState']['view']);
        }

        $this->configureViewStates($definition['viewState']);
        $this->configureActionStates($definition['actionState']);

        $this->pageFlow->setFSM($this->fsmBuilder->getStateMachine());

        return $this->pageFlow;
    }

    /**
     * Configures view states.
     *
     * @param array $states
     * @throws \Piece\Flow\PageFlow\ProtectedStateException
     */
    protected function configureViewStates(array $states)
    {
        foreach ($states as $state) {
            $this->configureViewState($state);
        }
    }

    /**
     * Configures action states.
     *
     * @param array $states
     * @throws \Piece\Flow\PageFlow\ProtectedStateException
     */
    protected function configureActionStates(array $states)
    {
        foreach ($states as $state) {
            $this->configureState($state);
        }
    }

    /**
     * Configures a state.
     *
     * @param array $state
     * @throws \Piece\Flow\PageFlow\ProtectedEventException
     */
    protected function configureState(array $state)
    {
        for ($i = 0, $count = count(@$state['transition']); $i < $count; ++$i) {
            if (in_array($state['transition'][$i]['event'], array(EventInterface::EVENT_ENTRY, EventInterface::EVENT_EXIT, EventInterface::EVENT_START, EventInterface::EVENT_DO))) {
                throw new ProtectedEventException("The event [ {$state['transition'][$i]['event']} ] cannot be used in flow definitions.");
            }

            $this->fsmBuilder->addTransition($state['name'],
                                       $state['transition'][$i]['event'],
                                       $state['transition'][$i]['nextState'],
                                       $this->wrapEventTriggerAction(@$state['transition'][$i]['action']),
                                       $this->wrapAction(@$state['transition'][$i]['guard'])
                                       );
        }

        if (!empty($state['entry'])) {
            $this->fsmBuilder->setEntryAction($state['name'],
                                        $this->wrapAction(@$state['entry'])
                                        );
        }

        if (!empty($state['exit'])) {
            $this->fsmBuilder->setExitAction($state['name'],
                                       $this->wrapAction(@$state['exit'])
                                       );
        }

        if (!empty($state['activity'])) {
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

        if (is_null($action['class'])) {
            $actionID = $action['method'];
        } else {
            $actionID = $action['class'] . ':' . $action['method'];
        }

        $eventHandler = new EventHandler($actionID, $this->pageFlow);
        return array($eventHandler, 'invokeAction');
    }

    /**
     * Configures a view state.
     *
     * @param array $state
     */
    protected function configureViewState(array $state)
    {
        $this->fsmBuilder->getStateMachine()->getState($state['name'])->setView($state['view']);
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

        if (is_null($action['class'])) {
            $actionID = $action['method'];
        } else {
            $actionID = $action['class'] . ':' . $action['method'];
        }

        $eventHandler = new EventHandler($actionID, $this->pageFlow);
        return array($eventHandler, 'invokeActionAndTriggerEvent');
    }

    /**
     * @return array
     * @since Method available since Release 2.0.0
     */
    protected function readDefinition()
    {
        $processor = new Processor();
        return $processor->processConfiguration(
            new Definition17Configuration(),
            array('definition17' => Yaml::parse($this->pageFlowRegistry->getFileName($this->pageFlow->getID())))
        );
    }

    /**
     * @param \Stagehand\FSM\State\StateInterface $state
     * @since Method available since Release 2.0.0
     */
    protected function addState(StateInterface $state)
    {
        $state->setEntryEvent(new EntryEvent());
        $state->setExitEvent(new ExitEvent());
        $state->setDoEvent(new DoEvent());
        $this->fsmBuilder->getStateMachine()->addState($state);
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
