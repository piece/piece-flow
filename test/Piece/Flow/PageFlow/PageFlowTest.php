<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * PHP version 5.3
 *
 * Copyright (c) 2006-2008, 2012-2013 KUBO Atsuhiro <kubo@iteman.jp>,
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
 * @copyright  2006-2008, 2012-2013 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version    Release: @package_version@
 * @since      File available since Release 0.1.0
 */

namespace Piece\Flow\PageFlow;

use Stagehand\FSM\State\StateInterface;

/**
 * @package    Piece_Flow
 * @copyright  2006-2008, 2012-2013 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version    Release: @package_version@
 * @since      Class available since Release 0.1.0
 */
class PageFlowTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \Piece\Flow\PageFlow\PageFlowFactory
     * @since Property available since Release 2.0.0
     */
    protected $pageFlowFactory;

    protected function setUp()
    {
        $this->pageFlowFactory = new PageFlowFactory(new PageFlowRegistry(__DIR__ . '/' . basename(__FILE__, '.php'), '.flow'));
    }

    /**
     * @test
     */
    public function getsTheViewOfTheCurrentState()
    {
        $pageFlow = $this->pageFlowFactory->create('Registration');
        $pageFlow->setActionInvoker(\Phake::mock('Piece\Flow\PageFlow\ActionInvokerInterface'));

        $this->assertThat($pageFlow->getCurrentView(), $this->isNull());

        $pageFlow->start();

        $this->assertThat($pageFlow->getCurrentView(), $this->equalTo('Input'));
    }

    /**
     * @test
     */
    public function triggersAnEvent()
    {
        $actionInvoker = \Phake::mock('Piece\Flow\PageFlow\ActionInvokerInterface');
        \Phake::when($actionInvoker)->invoke('onValidation', $this->anything())->thenReturn('valid');
        \Phake::when($actionInvoker)->invoke('onRegistration', $this->anything())->thenReturn('done');
        $pageFlow = $this->pageFlowFactory->create('Registration');
        $pageFlow->setActionInvoker($actionInvoker);

        $this->assertThat($pageFlow->isInFinalState(), $this->isFalse());

        $pageFlow->start();
        $pageFlow->triggerEvent('next');
        $pageFlow->triggerEvent('next');

        $this->assertThat($pageFlow->getCurrentState()->getStateID(), $this->equalTo(StateInterface::STATE_FINAL));
        $this->assertThat($pageFlow->getPreviousState()->getStateID(), $this->equalTo('Finish'));
        $this->assertThat($pageFlow->isInFinalState(), $this->isTrue());
        \Phake::verify($actionInvoker)->invoke('onValidation', $this->anything());
        \Phake::verify($actionInvoker)->invoke('onRegistration', $this->anything());
    }

    /**
     * @expectedException \Piece\Flow\PageFlow\PageFlowNotActivatedException
     * @since Method available since Release 2.0.0
     *
     * @test
     */
    public function raisesAnExceptionWhenAnEventIsTriggeredIfThePageFlowIsNotActive()
    {
        $pageFlow = \Phake::partialMock('Piece\Flow\PageFlow\PageFlow', 'foo');
        $pageFlow->triggerEvent('bar');
    }

    /**
     * @test
     */
    public function accessesTheAttributes()
    {
        $pageFlow = $this->pageFlowFactory->create('Registration');
        $pageFlow->setActionInvoker(\Phake::mock('Piece\Flow\PageFlow\ActionInvokerInterface'));
        $pageFlow->start();
        $pageFlow->getAttributes()->set('foo', 'bar');

        $this->assertThat($pageFlow->getAttributes()->has('foo'), $this->isTrue());
        $this->assertThat($pageFlow->getAttributes()->get('foo'), $this->equalTo('bar'));
    }

    /**
     * @expectedException \Piece\Flow\PageFlow\ProtectedEventException
     * @since Method available since Release 1.2.0
     *
     * @test
     */
    public function raisesAnExceptionWhenThePageFlowDefinitionHasAProtectedEvent()
    {
        $this->pageFlowFactory->create('ProtectedEvent', \Phake::mock('Piece\Flow\PageFlow\ActionInvokerInterface'));
    }

    /**
     * @expectedException \Piece\Flow\PageFlow\ProtectedStateException
     * @since Method available since Release 1.2.0
     *
     * @test
     */
    public function raisesAnExceptionWhenThePageFlowDefinitionHasAProtectedState()
    {
        $this->pageFlowFactory->create('ProtectedState', \Phake::mock('Piece\Flow\PageFlow\ActionInvokerInterface'));
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
