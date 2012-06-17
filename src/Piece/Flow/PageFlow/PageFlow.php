<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * PHP version 5.3
 *
 * Copyright (c) 2006-2008, 2012 KUBO Atsuhiro <kubo@iteman.jp>,
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
 * @copyright  2006-2008, 2012 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version    Release: @package_version@
 * @link       http://www.martinfowler.com/eaaCatalog/applicationController.html
 * @link       http://opensource2.atlassian.com/confluence/spring/display/WEBFLOW/Home
 * @link       http://www-128.ibm.com/developerworks/java/library/j-cb03216/
 * @link       http://www-06.ibm.com/jp/developerworks/java/060412/j_j-cb03216.shtml
 * @since      File available since Release 0.1.0
 */

namespace Piece\Flow\PageFlow;

use Stagehand\FSM\Event;
use Stagehand\FSM\FSM;
use Stagehand\FSM\FSMAlreadyShutdownException;
use Stagehand\FSM\State;

use Piece\Flow\ConfigReader;
use Piece\Flow\FSMBuilder;
use Piece\Flow\InvalidTransitionException;
use Piece\Flow\Core\MethodInvocationException;

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
 * @copyright  2006-2008, 2012 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version    Release: @package_version@
 * @link       http://www.martinfowler.com/eaaCatalog/applicationController.html
 * @link       http://opensource2.atlassian.com/confluence/spring/display/WEBFLOW/Home
 * @link       http://www-128.ibm.com/developerworks/java/library/j-cb03216/
 * @link       http://www-06.ibm.com/jp/developerworks/java/060412/j_j-cb03216.shtml
 * @since      Class available since Release 0.1.0
 */
class PageFlow
{
    /**
     * @since Constant available since Release 2.0.0
     */
    const EVENT_PROTECTED = '__protected';

    protected $fsm;
    protected $name;
    protected $views;
    protected $attributes = array();
    protected $lastState;
    protected $lastEventIsValid = true;
    protected $actionDirectory;

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
    public function configure($source,
                       $driverName = null,
                       $cacheDirectory = null,
                       $actionDirectory = null,
                       $configDirectory = null,
                       $configExtension = null
                       )
    {
        $config = ConfigReader::read($source,
                                                 $driverName,
                                                 $cacheDirectory,
                                                 $configDirectory,
                                                 $configExtension
                                                 );
        $this->name = $config->getName();
        $fsmBuilder = new FSMBuilder($this, $actionDirectory);
        $this->fsm = $fsmBuilder->build($config);

        $lastState = $config->getLastState();
        if (!is_null($lastState)) {
            $this->lastState = $lastState;
        }

        foreach ($config->getViewStates() as $key => $state) {
            $this->views[ $state['name'] ] = $state['view'];
        }

        $this->actionDirectory = $actionDirectory;
    }

    /**
     * Gets an appropriate view string which corresponding to the current
     * state.
     *
     * @return string
     * @throws \Piece\Flow\Core\MethodInvocationException
     * @throws \Piece\Flow\InvalidTransitionException
     */
    public function getView()
    {
        if (!$this->started()) {
            throw new MethodInvocationException(__FUNCTION__ . ' method must be called after starting flows.');
        }

        if (!$this->isFinalState()) {
            $viewIndex = $this->getCurrentStateName();
        } else {
            $viewIndex = $this->getPreviousStateName();
        }

        if (!array_key_exists($viewIndex, $this->views)) {
            throw new InvalidTransitionException("A invalid transition detected. The state [ $viewIndex ] does not have a view. Maybe The state [ $viewIndex ] is an action state. Check the definition of the flow [ {$this->name} ].");
        }

        return $this->views[$viewIndex];
    }

    /**
     * Gets the name of the flow.
     *
     * @return string
     */
    function getName()
    {
        return $this->name;
    }

    /**
     * Starts the Finite State Machine.
     */
    public function start()
    {
        $this->fsm->start();
    }

    /**
     * Triggers the given event.
     *
     * @param string $eventName
     * @param boolean $transitionToHistoryMarker
     * @return \Stagehand\FSM\State
     * @throws \Piece\Flow\Core\MethodInvocationException
     */
    public function triggerEvent($eventName, $transitionToHistoryMarker = false)
    {
        if (!$this->started()) {
            throw new MethodInvocationException(__FUNCTION__ . ' method must be called after starting flows.');
        }

        if ($eventName == self::EVENT_PROTECTED || $this->fsm->isProtectedEvent($eventName)) {
            trigger_error("The event [ $eventName ] cannot be called directly. The current state [ " .
                          $this->getCurrentStateName() . ' ] will only be updated.',
                          E_USER_WARNING
                          );
            $eventName = self::EVENT_PROTECTED;
        }

        $this->lastEventIsValid = $this->fsm->hasEvent($eventName);

        $state = $this->fsm->triggerEvent($eventName,
                                            $transitionToHistoryMarker
                                            );

        if (!is_null($this->lastState)
            && $state->getID() == $this->lastState
            ) {
            $state = $this->fsm->triggerEvent(Event::EVENT_END);
        }

        return $state;
    }

    /**
     * Gets the previous state name.
     *
     * @return string
     * @throws \Piece\Flow\Core\MethodInvocationException
     */
    public function getPreviousStateName()
    {
        if (!$this->started()) {
            throw new MethodInvocationException(__FUNCTION__ . ' method must be called after starting flows.');
        }

        $state = $this->fsm->getPreviousState();
        return $state->getID();
    }

    /**
     * Gets the current state name.
     *
     * @return string
     * @throws \Piece\Flow\Core\MethodInvocationException
     */
    public function getCurrentStateName()
    {
        if (!$this->started()) {
            throw new MethodInvocationException(__FUNCTION__ . ' method must be called after starting flows.');
        }

        $state = $this->fsm->getCurrentState();
        return $state->getID();
    }

    /**
     * Sets an attribute for the flow execution.
     *
     * @param string $name
     * @param mixed  $value
     * @throws \Piece\Flow\Core\MethodInvocationException
     */
    public function setAttribute($name, $value)
    {
        if (!$this->started()) {
            throw new MethodInvocationException(__FUNCTION__ . ' method must be called after starting flows.');
        }

        $this->attributes[$name] = $value;
    }

    /**
     * Sets an attribute by reference for the flow execution.
     *
     * @param string $name
     * @param mixed  &$value
     * @throws \Piece\Flow\Core\MethodInvocationException
     */
    public function setAttributeByRef($name, &$value)
    {
        if (!$this->started()) {
            throw new MethodInvocationException(__FUNCTION__ . ' method must be called after starting flows.');
        }

        $this->attributes[$name] = &$value;
    }

    /**
     * Returns whether the flow execution has an attribute with a given name.
     *
     * @param string $name
     * @return boolean
     * @throws \Piece\Flow\Core\MethodInvocationException
     */
    public function hasAttribute($name)
    {
        if (!$this->started()) {
            throw new MethodInvocationException(__FUNCTION__ . ' method must be called after starting flows.');
        }

        return array_key_exists($name, $this->attributes);
    }

    /**
     * Gets an attribute for the flow execution.
     *
     * @param string $name
     * @return mixed
     * @throws \Piece\Flow\Core\MethodInvocationException
     */
    public function &getAttribute($name)
    {
        if (!$this->started()) {
            throw new MethodInvocationException(__FUNCTION__ . ' method must be called after starting flows.');
        }

        return $this->attributes[$name];
    }

    /**
     * Sets a user defined payload to the FSM.
     *
     * @param mixed &$payload
     * @throws \Piece\Flow\Core\MethodInvocationException
     */
    public function setPayload(&$payload)
    {
        if (is_null($this->fsm)) {
            throw new MethodInvocationException(__FUNCTION__ . ' method must be called after configuring flows.');
        }

        $this->fsm->setPayload($payload);
    }

    /**
     * Returns whether the current state of the flow execution is the final
     * state.
     *
     * @return boolean
     * @throws \Piece\Flow\Core\MethodInvocationException
     */
    public function isFinalState()
    {
        if (is_null($this->fsm)) {
            throw new MethodInvocationException(__FUNCTION__ . ' method must be called after configuring flows.');
        }

        return $this->getCurrentStateName() == State::STATE_FINAL;
    }

    // }}}
    // {{{ removeAttribute()

    /**
     * Removes an attribute from the flow execution.
     *
     * @param string $name
     * @throws \Piece\Flow\Core\MethodInvocationException
     */
    public function removeAttribute($name)
    {
        if (!$this->started()) {
            throw new MethodInvocationException(__FUNCTION__ . ' method must be called after starting flows.');
        }

        unset($this->attributes[$name]);
    }

    /**
     * Removes all attributes from the flow execution.
     *
     * @throws \Piece\Flow\Core\MethodInvocationException
     */
    public function clearAttributes()
    {
        if (!$this->started()) {
            throw new MethodInvocationException(__FUNCTION__ . ' method must be called after starting flows.');
        }

        $this->attributes = array();
    }

    /**
     * Removes the payload from the FSM.
     *
     * @throws \Piece\Flow\Core\MethodInvocationException
     * @since Method available since Release 1.11.0
     */
    public function clearPayload()
    {
        if (is_null($this->fsm)) {
            throw new MethodInvocationException(__FUNCTION__ . ' method must be called after configuring flows.');
        }

        $this->fsm->clearPayload();
    }

    /**
     * Returns whether the last event which is given by a user is valid or
     * not.
     *
     * @return boolean
     * @since Method available since Release 1.13.0
     */
    public function checkLastEvent()
    {
        return $this->lastEventIsValid;
    }

    /**
     * Tells whether the current state of a flow execution is a view state or not.
     *
     * @return boolean
     * @since Method available since Release 1.16.0
     */
    public function isViewState()
    {
        return array_key_exists($this->_getViewIndex(), $this->views);
    }

    /**
     * Returns whether the flow execution has started or not.
     *
     * @return boolean
     */
    protected function started()
    {
        return !is_null($this->fsm) && !is_null($this->fsm->getCurrentState());
    }

    /**
     * Gets an appropriate view index which corresponding to the current state.
     *
     * @return string
     * @since Method available since Release 1.16.0
     */
    protected function _getViewIndex()
    {
        return !$this->isFinalState() ? $this->getCurrentStateName()
                                      : $this->getPreviousStateName();
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
