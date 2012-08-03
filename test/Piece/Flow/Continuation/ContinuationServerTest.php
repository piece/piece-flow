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
 * @since      File available since Release 1.14.0
 */

namespace Piece\Flow\Continuation;

use Piece\Flow\PageFlow\EventContext;
use Piece\Flow\PageFlow\PageFlowCacheFactory;
use Piece\Flow\PageFlow\PageFlowRegistry;
use Piece\Flow\PageFlow\PageFlowRepository;

/**
 * @package    Piece_Flow
 * @copyright  2006-2008, 2012 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version    Release: @package_version@
 * @since      Class available since Release 1.14.0
 */
class ContinuationServerTest extends \PHPUnit_Framework_TestCase
{
    protected $cacheDirectory;

    /**
     * @var string
     * @since Property available since Release 2.0.0
     */
    protected $flowExecutionTicket;

    /**
     * @var string
     * @since Property available since Release 2.0.0
     */
    protected $flowID;

    /**
     * @var string
     * @since Property available since Release 2.0.0
     */
    protected $eventName;

    /**
     * @var \Piece\Flow\Continuation\ContinuationContextProvider
     * @since Property available since Release 2.0.0
     */
    protected $continuationContextProvider;

    public function getFlowExecutionTicket()
    {
        return $this->flowExecutionTicket;
    }

    public function getFlowID()
    {
        return $this->flowID;
    }

    public function getEventName()
    {
        return $this->eventName;
    }

    protected function setUp()
    {
        $this->cacheDirectory = dirname(__FILE__) . '/' . basename(__FILE__, '.php');
        $this->continuationContextProvider = \Phake::mock('Piece\Flow\Continuation\ContinuationContextProvider');
        $self = $this;
        \Phake::when($this->continuationContextProvider)->getEventID()->thenGetReturnByLambda(function () use ($self) { return $self->getEventName(); });
        \Phake::when($this->continuationContextProvider)->getPageFlowID()->thenGetReturnByLambda(function () use ($self) { return $self->getFlowID(); });
        \Phake::when($this->continuationContextProvider)->getPageFlowInstanceID()->thenGetReturnByLambda(function () use ($self) { return $self->getFlowExecutionTicket(); });
    }

    public function testInvocationInMultipleFlowModeAndFlowInNonExclusiveMode()
    {
        $pageFlowInstanceRepository = new PageFlowInstanceRepository(new PageFlowRepository(new PageFlowRegistry($this->cacheDirectory, '.yaml'), $this->cacheDirectory, true));
        $pageFlowInstanceRepository->addPageFlow('Counter', false);
        $server = new ContinuationServer($pageFlowInstanceRepository);
        $actionInvoker = $this->createCounterActionInvoker();
        $server->setActionInvoker($actionInvoker);
        $server->setContinuationContextProvider($this->continuationContextProvider);

        $this->flowID = 'Counter';
        $this->eventName = null;
        $this->flowExecutionTicket = null;
        $server->invoke(new \stdClass());
        $pageFlowInstance1 = $server->getPageFlowInstance();
        $server->shutdown();

        $this->flowID = 'Counter';
        $this->eventName = 'increase';
        $this->flowExecutionTicket = $pageFlowInstance1->getID();
        $server->invoke(new \stdClass());
        $pageFlowInstance2 = $server->getPageFlowInstance();

        $this->assertRegexp('/[0-9a-f]{40}/', $pageFlowInstance1->getID());
        $this->assertEquals('Counter', $pageFlowInstance2->getView());
        $this->assertEquals(1, $pageFlowInstance2->getAttribute('counter'));
        $this->assertEquals($pageFlowInstance1->getID(), $pageFlowInstance2->getID());
    }

    public function testMultipleInvocationInMultipleFlowModeAndFlowInNonExclusiveMode()
    {
        $pageFlowInstanceRepository = new PageFlowInstanceRepository(new PageFlowRepository(new PageFlowRegistry($this->cacheDirectory, '.yaml'), $this->cacheDirectory, true));
        $pageFlowInstanceRepository->addPageFlow('Counter', false);
        $pageFlowInstanceRepository->addPageFlow('SecondCounter', false);
        $server = new ContinuationServer($pageFlowInstanceRepository);
        $server->setActionInvoker($this->createCounterActionInvoker());
        $server->setContinuationContextProvider($this->continuationContextProvider);

        /*
         * Starting a new 'Counter'.
         */
        $this->flowID = 'Counter';
        $this->eventName = null;
        $this->flowExecutionTicket = null;
        $server->invoke(new \stdClass());
        $pageFlowInstance1 = $server->getPageFlowInstance();

        $this->assertEquals(0, $pageFlowInstance1->getAttribute('counter'));

        $server->shutdown();

        /*
         * Starting a new 'SecondCounter'.
         */
        $this->flowID = 'SecondCounter';
        $this->eventName = null;
        $this->flowExecutionTicket = null;
        $server->invoke(new \stdClass());
        $pageFlowInstance2 = $server->getPageFlowInstance();

        $this->assertEquals(0, $pageFlowInstance2->getAttribute('counter'));
        $this->assertRegexp('/[0-9a-f]{40}/', $pageFlowInstance1->getID());
        $this->assertRegexp('/[0-9a-f]{40}/', $pageFlowInstance2->getID());
        $this->assertEquals('SecondCounter', $pageFlowInstance2->getView());
        $this->assertTrue($pageFlowInstance1->getID() != $pageFlowInstance2->getID());

        $server->shutdown();

        /*
         * Continuing the first 'Counter'.
         */
        $this->flowID = 'Counter';
        $this->eventName = 'increase';
        $this->flowExecutionTicket = $pageFlowInstance1->getID();
        $server->invoke(new \stdClass());
        $pageFlowInstance3 = $server->getPageFlowInstance();

        $this->assertEquals(1, $pageFlowInstance3->getAttribute('counter'));

        $this->assertEquals('Counter', $pageFlowInstance3->getView());
        $this->assertEquals($pageFlowInstance1->getID(), $pageFlowInstance3->getID());

        $server->shutdown();

        /*
         * Continuing the first 'SecondCounter'.
         */
        $this->flowID = 'SecondCounter';
        $this->eventName = 'increase';
        $this->flowExecutionTicket = $pageFlowInstance2->getID();
        $server->invoke(new \stdClass());
        $pageFlowInstance4 = $server->getPageFlowInstance();

        $this->assertEquals('SecondCounter', $pageFlowInstance4->getView());
        $this->assertEquals(1, $pageFlowInstance4->getAttribute('counter'));
        $this->assertEquals($pageFlowInstance2->getID(), $pageFlowInstance4->getID());

        $server->shutdown();

        /*
         * Starting a new 'SecondCounter'.
         */
        $this->flowID = 'SecondCounter';
        $this->eventName = null;
        $this->flowExecutionTicket = null;
        $server->invoke(new \stdClass());
        $pageFlowInstance5 = $server->getPageFlowInstance();

        $this->assertEquals('SecondCounter', $pageFlowInstance5->getView());
        $this->assertEquals(0, $pageFlowInstance5->getAttribute('counter'));
        $this->assertTrue($pageFlowInstance2->getID() != $pageFlowInstance5->getID());
    }

    /**
     * @expectedException \Piece\Flow\Continuation\UnexpectedPageFlowIDException
     */
    public function testFailureOfContinuationByInvalidFlowNameInMultipleFlowMode()
    {
        $pageFlowInstanceRepository = new PageFlowInstanceRepository(new PageFlowRepository(new PageFlowRegistry($this->cacheDirectory, '.yaml'), $this->cacheDirectory, true));
        $pageFlowInstanceRepository->addPageFlow('Counter', false);
        $server = new ContinuationServer($pageFlowInstanceRepository);
        $server->setActionInvoker($this->createCounterActionInvoker());
        $server->setContinuationContextProvider($this->continuationContextProvider);

        $this->flowID = 'Counter';
        $this->eventName = null;
        $this->flowExecutionTicket = null;
        $server->invoke(new \stdClass());
        $pageFlowInstance = $server->getPageFlowInstance();
        $server->shutdown();

        $this->flowID = 'InvalidFlowName';
        $this->eventName = 'increase';
        $this->flowExecutionTicket = $pageFlowInstance->getID();
        $server->invoke(new \stdClass());
    }

    public function testInvocationInMultipleFlowModeAndFlowInExclusiveMode()
    {
        $pageFlowInstanceRepository = new PageFlowInstanceRepository(new PageFlowRepository(new PageFlowRegistry($this->cacheDirectory, '.yaml'), $this->cacheDirectory, true));
        $pageFlowInstanceRepository->addPageFlow('Counter', false);
        $pageFlowInstanceRepository->addPageFlow('SecondCounter', false);
        $server = new ContinuationServer($pageFlowInstanceRepository);
        $server->setActionInvoker($this->createCounterActionInvoker());
        $server->setContinuationContextProvider($this->continuationContextProvider);

        $this->flowID = 'Counter';
        $this->eventName = null;
        $this->flowExecutionTicket = null;
        $server->invoke(new \stdClass());
        $pageFlowInstance1 = $server->getPageFlowInstance();

        $this->assertEquals(0, $pageFlowInstance1->getAttribute('counter'));

        $server->shutdown();

        $this->flowID = 'SecondCounter';
        $this->eventName = null;
        $this->flowExecutionTicket = null;
        $server->invoke(new \stdClass());
        $pageFlowInstance3 = $server->getPageFlowInstance();

        $this->assertEquals(0, $pageFlowInstance3->getAttribute('counter'));

        $server->shutdown();

        $this->flowID = 'Counter';
        $this->eventName = 'increase';
        $this->flowExecutionTicket = $pageFlowInstance1->getID();
        $server->invoke(new \stdClass());
        $pageFlowInstance2 = $server->getPageFlowInstance();

        $this->assertEquals(1, $pageFlowInstance2->getAttribute('counter'));

        $this->assertRegexp('/[0-9a-f]{40}/', $pageFlowInstance1->getID());
        $this->assertRegexp('/[0-9a-f]{40}/', $pageFlowInstance3->getID());
        $this->assertEquals('Counter', $pageFlowInstance2->getView());
        $this->assertEquals($pageFlowInstance1->getID(), $pageFlowInstance2->getID());
        $this->assertTrue($pageFlowInstance1->getID() != $pageFlowInstance3->getID());
    }

    public function testSettingAttribute()
    {
        $pageFlowInstanceRepository = new PageFlowInstanceRepository(new PageFlowRepository(new PageFlowRegistry($this->cacheDirectory, '.yaml'), $this->cacheDirectory, true));
        $pageFlowInstanceRepository->addPageFlow('Counter', false);
        $server = new ContinuationServer($pageFlowInstanceRepository);
        $server->setActionInvoker($this->createCounterActionInvoker());
        $server->setContinuationContextProvider($this->continuationContextProvider);

        $this->flowID = 'Counter';
        $this->eventName = null;
        $this->flowExecutionTicket = null;
        $server->invoke(new \stdClass());
        $pageFlowInstance = $server->getPageFlowInstance();
        $pageFlowInstance->setAttribute('foo', 'bar');
        $server->shutdown();

        $this->flowID = 'Counter';
        $this->eventName = 'increase';
        $this->flowExecutionTicket = $pageFlowInstance->getID();
        $server->invoke(new \stdClass());
        $pageFlowInstance = $server->getPageFlowInstance();
        $pageFlowInstance->setAttribute('bar', 'baz');
        $baz1 = new \stdClass();
        $pageFlowInstance->setAttribute('baz', $baz1);
        $server->shutdown();

        $this->flowID = 'Counter';
        $this->eventName = 'increase';
        $this->flowExecutionTicket = $pageFlowInstance->getID();
        $server->invoke(new \stdClass());
        $pageFlowInstance = $server->getPageFlowInstance();

        $this->assertTrue($pageFlowInstance->hasAttribute('foo'));
        $this->assertEquals('bar', $pageFlowInstance->getAttribute('foo'));
        $this->assertTrue($pageFlowInstance->hasAttribute('bar'));
        $this->assertEquals('baz', $pageFlowInstance->getAttribute('bar'));

        $baz1->foo = 'bar';

        $this->assertTrue(property_exists($baz1, 'foo'));
        $this->assertEquals('bar', $baz1->foo);

        $baz2 = $pageFlowInstance->getAttribute('baz');

        $this->assertEquals(strtolower('stdClass'), strtolower(get_class($baz2)));

        $this->assertTrue(property_exists($baz2, 'foo'));
        $this->assertEquals('bar', $baz2->foo);
    }

    public function testStartingNewFlowExecutionAfterShuttingDownContinuationInNonExclusiveMode()
    {
        $shutdownCount = 0;
        $actionInvoker = \Phake::mock('Piece\Flow\PageFlow\ActionInvoker');
        \Phake::when($actionInvoker)->invoke('finalize', $this->anything())->thenGetReturnByLambda(function ($actionID, EventContext $eventContext) use (&$shutdownCount) {
            ++$shutdownCount;
        });
        $pageFlowInstanceRepository = new PageFlowInstanceRepository(new PageFlowRepository(new PageFlowRegistry($this->cacheDirectory, '.yaml'), $this->cacheDirectory, true));
        $pageFlowInstanceRepository->addPageFlow('Shutdown', false);
        $server = new ContinuationServer($pageFlowInstanceRepository);
        $server->setActionInvoker($actionInvoker);
        $server->setContinuationContextProvider($this->continuationContextProvider);

        /*
         * Starting a new 'Shutdown'.
         */
        $this->flowID = 'Shutdown';
        $this->eventName = null;
        $this->flowExecutionTicket = null;
        $server->invoke(new \stdClass());
        $pageFlowInstance1 = $server->getPageFlowInstance();
        $server->shutdown();

        $this->flowID = 'Shutdown';
        $this->eventName = 'go';
        $this->flowExecutionTicket = $pageFlowInstance1->getID();
        $server->invoke(new \stdClass());
        $pageFlowInstance2 = $server->getPageFlowInstance();

        $this->assertEquals(1, $shutdownCount);
        $this->assertEquals($pageFlowInstance1->getID(), $pageFlowInstance2->getID());

        $server->shutdown();

        /*
         * Failure to continue the 'Shutdown' from the previous flow
         * execution ticket.
         */
        $this->flowID = null;
        $this->eventName = 'go';
        $this->flowExecutionTicket = $pageFlowInstance1->getID();

        try {
            $server->invoke(new \stdClass());
            $this->fail('An expected exception has not been raised.');
        } catch (PageFlowIDRequiredException $e) {
        }
    }

    public function testStartingNewFlowExecutionAfterShuttingDownContinuationInExclusiveMode()
    {
        $shutdownCount = 0;
        $actionInvoker = \Phake::mock('Piece\Flow\PageFlow\ActionInvoker');
        \Phake::when($actionInvoker)->invoke('finalize', $this->anything())->thenGetReturnByLambda(function ($actionID, EventContext $eventContext) use (&$shutdownCount) {
            ++$shutdownCount;
        });
        $pageFlowInstanceRepository = new PageFlowInstanceRepository(new PageFlowRepository(new PageFlowRegistry($this->cacheDirectory, '.yaml'), $this->cacheDirectory, true));
        $pageFlowInstanceRepository->addPageFlow('Shutdown', false);
        $server = new ContinuationServer($pageFlowInstanceRepository);
        $server->setActionInvoker($actionInvoker);
        $server->setContinuationContextProvider($this->continuationContextProvider);

        /*
         * Starting a new 'Shutdown'.
         */
        $this->flowID = 'Shutdown';
        $this->eventName = null;
        $this->flowExecutionTicket = null;
        $server->invoke(new \stdClass());
        $pageFlowInstance1 = $server->getPageFlowInstance();
        $server->shutdown();

        $this->flowID = 'Shutdown';
        $this->eventName = 'go';
        $this->flowExecutionTicket = $pageFlowInstance1->getID();
        $server->invoke(new \stdClass());
        $pageFlowInstance2 = $server->getPageFlowInstance();
        $server->shutdown();

        $this->assertEquals(1, $shutdownCount);
        $this->assertEquals($pageFlowInstance1->getID(), $pageFlowInstance2->getID());
        $this->assertRegexp('/[0-9a-f]{40}/', $pageFlowInstance1->getID());

        /*
         * Failure to continue the 'Shutdown' from the previous flow
         * execution ticket. And starting a new 'Shutdown'.
         */
        $this->flowID = 'Shutdown';
        $this->eventName = 'go';
        $this->flowExecutionTicket = $pageFlowInstance1->getID();
        $server->invoke(new \stdClass());
        $pageFlowInstance3 = $server->getPageFlowInstance();

        $this->assertTrue($pageFlowInstance1->getID() != $pageFlowInstance3->getID());
        $this->assertRegexp('/[0-9a-f]{40}/', $pageFlowInstance3->getID());
    }

    /**
     * @since Method available since Release 1.7.0
     */
    public function testShouldBeRequiredFlowExecutionTicketWheneverContinuingFlowExecution()
    {
        $pageFlowInstanceRepository = \Phake::partialMock('Piece\Flow\Continuation\PageFlowInstanceRepository', new PageFlowRepository(new PageFlowRegistry($this->cacheDirectory, '.yaml'), $this->cacheDirectory, true));
        $pageFlowInstanceRepository->addPageFlow('Counter', true);
        $server = new ContinuationServer($pageFlowInstanceRepository);
        $server->setActionInvoker($this->createCounterActionInvoker());
        $server->setContinuationContextProvider($this->continuationContextProvider);

        $this->flowID = 'Counter';
        $this->eventName = null;
        $this->flowExecutionTicket = null;
        $server->invoke(new \stdClass());
        $pageFlowInstance1 = $server->getPageFlowInstance();

        $this->assertEquals(0, $pageFlowInstance1->getAttribute('counter'));

        $server->shutdown();

        $this->flowID = 'Counter';
        $this->eventName = null;
        $this->flowExecutionTicket = null;
        $server->invoke(new \stdClass());
        $pageFlowInstance2 = $server->getPageFlowInstance();

        \Phake::verify($pageFlowInstanceRepository)->remove($pageFlowInstance1);
        $this->assertEquals(0, $pageFlowInstance2->getAttribute('counter'));
        $this->assertTrue($pageFlowInstance1->getID() != $pageFlowInstance2->getID());
    }

    /**
     * @since Method available since Release 1.7.0
     */
    public function testGettingFlowExecutionTicketByFlowName()
    {
        $pageFlowInstanceRepository = new PageFlowInstanceRepository(new PageFlowRepository(new PageFlowRegistry($this->cacheDirectory, '.yaml'), $this->cacheDirectory, true));
        $pageFlowInstanceRepository->addPageFlow('Counter', true);
        $pageFlowInstanceRepository->addPageFlow('SecondCounter', false);
        $server = new ContinuationServer($pageFlowInstanceRepository);
        $server->setActionInvoker($this->createCounterActionInvoker());
        $server->setContinuationContextProvider($this->continuationContextProvider);

        $this->flowID = 'Counter';
        $this->eventName = null;
        $this->flowExecutionTicket = null;
        $server->invoke(new \stdClass());
        $pageFlowInstance1 = $server->getPageFlowInstance();

        $this->flowID = 'Counter';
        $this->eventName = 'increase';
        $this->flowExecutionTicket = $pageFlowInstance1->getID();
        $server->invoke(new \stdClass());

        $this->assertEquals(1, $pageFlowInstance1->getAttribute('counter'));

        $this->flowID = 'SecondCounter';
        $this->eventName = null;
        $this->flowExecutionTicket = null;
        $server->invoke(new \stdClass());
        $pageFlowInstance2 = $server->getPageFlowInstance();

        $this->assertEquals(0, $pageFlowInstance2->getAttribute('counter'));
        $this->assertFalse($pageFlowInstance1->getID() == $pageFlowInstance2->getID());
        $this->assertThat($server->getPageFlowInstanceRepository()->findByPageFlowID('Counter'), $this->logicalNot($this->equalTo(null)));
        $this->assertEquals($pageFlowInstance1->getID(), $server->getPageFlowInstanceRepository()->findByPageFlowID('Counter')->getID());
        $this->assertNull($server->getPageFlowInstanceRepository()->findByPageFlowID('SecondCounter'));
    }

    /**
     * @expectedException \Piece\Flow\Continuation\PageFlowInstanceExpiredException
     * @since Method available since Release 1.11.0
     */
    public function testPageFlowInstanceExpiredExceptionShouldBeRaisedWhenFlowExecutionHasExpired()
    {
        $flowName = 'FlowExecutionExpired';
        $pageFlowInstanceRepository = new PageFlowInstanceRepository(new PageFlowRepository(new PageFlowRegistry($this->cacheDirectory, '.yaml'), $this->cacheDirectory, true));
        $pageFlowInstanceRepository->addPageFlow($flowName, false);
        $server = new ContinuationServer($pageFlowInstanceRepository, new GC(1));
        $server->setActionInvoker(\Phake::mock('Piece\Flow\PageFlow\ActionInvoker'));
        $server->setContinuationContextProvider($this->continuationContextProvider);

        $this->flowID = $flowName;
        $this->eventName = null;
        $this->flowExecutionTicket = null;
        $server->invoke(new \stdClass());
        $pageFlowInstance = $server->getPageFlowInstance();
        $server->shutdown();

        sleep(2);

        $this->flowID = $flowName;
        $this->eventName = null;
        $this->flowExecutionTicket = $pageFlowInstance->getID();
        $server->invoke(new \stdClass());
    }

    /**
     * @since Method available since Release 1.11.0
     */
    public function testPageFlowInstanceExpiredExceptionShouldNotBeRaisedWhenFlowExecutionHasNotExpired()
    {
        $flowName = 'FlowExecutionExpired';
        $pageFlowInstanceRepository = new PageFlowInstanceRepository(new PageFlowRepository(new PageFlowRegistry($this->cacheDirectory, '.yaml'), $this->cacheDirectory, true));
        $pageFlowInstanceRepository->addPageFlow($flowName, false);
        $server = new ContinuationServer($pageFlowInstanceRepository, new GC(2));
        $server->setActionInvoker(\Phake::mock('Piece\Flow\PageFlow\ActionInvoker'));
        $server->setContinuationContextProvider($this->continuationContextProvider);

        $this->flowID = $flowName;
        $this->eventName = null;
        $this->flowExecutionTicket = null;
        $server->invoke(new \stdClass());
        $pageFlowInstance = $server->getPageFlowInstance();
        $server->shutdown();

        sleep(1);

        $this->flowID = $flowName;
        $this->eventName = null;
        $this->flowExecutionTicket = $pageFlowInstance->getID();
        $server->invoke(new \stdClass());

        sleep(1);

        $this->flowID = $flowName;
        $this->eventName = null;
        $this->flowExecutionTicket = $pageFlowInstance->getID();
        $server->invoke(new \stdClass());

        sleep(1);

        $this->flowID = $flowName;
        $this->eventName = null;
        $this->flowExecutionTicket = $pageFlowInstance->getID();
        $server->invoke(new \stdClass());
    }

    /**
     * @since Method available since Release 1.11.0
     */
    public function testNewFlowExecutionShouldBeAbleToStartWithSameRequestAfterFlowExecutionIsExpired()
    {
        $flowName = 'FlowExecutionExpired';
        $this->flowID = $flowName;
        $pageFlowInstanceRepository = new PageFlowInstanceRepository(new PageFlowRepository(new PageFlowRegistry($this->cacheDirectory, '.yaml'), $this->cacheDirectory, true));
        $pageFlowInstanceRepository->addPageFlow($flowName, false);
        $server = new ContinuationServer($pageFlowInstanceRepository, new GC(1));
        $server->setActionInvoker(\Phake::mock('Piece\Flow\PageFlow\ActionInvoker'));
        $server->setContinuationContextProvider($this->continuationContextProvider);

        $this->flowID = $flowName;
        $this->eventName = null;
        $this->flowExecutionTicket = null;
        $server->invoke(new \stdClass());
        $pageFlowInstance = $server->getPageFlowInstance();
        $server->shutdown();

        sleep(2);

        $this->flowID = $flowName;
        $this->eventName = null;
        $this->flowExecutionTicket = $pageFlowInstance->getID();

        try {
            $server->invoke(new \stdClass());
            $this->fail('An expected exception has not been raised.');
        } catch (PageFlowInstanceExpiredException $e) {
        }

        $server->shutdown();

        $this->flowID = $flowName;
        $this->eventName = null;
        $this->flowExecutionTicket = $pageFlowInstance->getID();
        $server->invoke(new \stdClass());
        $newPageFlowInstance = $server->getPageFlowInstance();

        $this->assertTrue($newPageFlowInstance->getID() != $this->flowExecutionTicket);
    }

    /**
     * @since Method available since Release 1.13.0
     */
    public function testCheckLastEventShouldReturnTrueIfContinuationHasJustStarted()
    {
        $flowName = 'CheckLastEvent';
        $pageFlowInstanceRepository = new PageFlowInstanceRepository(new PageFlowRepository(new PageFlowRegistry($this->cacheDirectory, '.yaml'), $this->cacheDirectory, true));
        $pageFlowInstanceRepository->addPageFlow($flowName, false);
        $server = new ContinuationServer($pageFlowInstanceRepository);
        $server->setActionInvoker(\Phake::mock('Piece\Flow\PageFlow\ActionInvoker'));
        $server->setContinuationContextProvider($this->continuationContextProvider);

        $this->flowID = $flowName;
        $this->eventName = 'foo';
        $this->flowExecutionTicket = null;
        $server->invoke(new \stdClass());

        $this->assertTrue($server->getPageFlowInstance()->checkLastEvent());
    }

    /**
     * @since Method available since Release 1.13.0
     */
    public function testCheckLastEventShouldReturnTrueWhenValidEventIsGivenByUser()
    {
        $flowName = 'CheckLastEvent';
        $pageFlowInstanceRepository = new PageFlowInstanceRepository(new PageFlowRepository(new PageFlowRegistry($this->cacheDirectory, '.yaml'), $this->cacheDirectory, true));
        $pageFlowInstanceRepository->addPageFlow($flowName, false);
        $server = new ContinuationServer($pageFlowInstanceRepository);
        $server->setActionInvoker(\Phake::mock('Piece\Flow\PageFlow\ActionInvoker'));
        $server->setContinuationContextProvider($this->continuationContextProvider);

        $this->flowID = $flowName;
        $this->eventName = null;
        $this->flowExecutionTicket = null;
        $server->invoke(new \stdClass());
        $pageFlowInstance = $server->getPageFlowInstance();
        $server->shutdown();

        $this->flowID = $flowName;
        $this->eventName = 'DisplayEditConfirmFromDisplayEdit';
        $this->flowExecutionTicket = $pageFlowInstance->getID();
        $server->invoke(new \stdClass());
        $pageFlowInstance = $server->getPageFlowInstance();

        $this->assertTrue($pageFlowInstance->checkLastEvent());

        $server->shutdown();

        $this->flowID = $flowName;
        $this->eventName = 'DisplayEditFinishFromDisplayEditConfirm';
        $this->flowExecutionTicket = $pageFlowInstance->getID();
        $server->invoke(new \stdClass());
        $pageFlowInstance = $server->getPageFlowInstance();

        $this->assertTrue($pageFlowInstance->checkLastEvent());
    }

    /**
     * @since Method available since Release 1.13.0
     */
    public function testCheckLastEventShouldReturnFalseWhenInvalidEventIsGivenByUser()
    {
        $flowName = 'CheckLastEvent';
        $pageFlowInstanceRepository = new PageFlowInstanceRepository(new PageFlowRepository(new PageFlowRegistry($this->cacheDirectory, '.yaml'), $this->cacheDirectory, true));
        $pageFlowInstanceRepository->addPageFlow($flowName, false);
        $server = new ContinuationServer($pageFlowInstanceRepository);
        $server->setActionInvoker(\Phake::mock('Piece\Flow\PageFlow\ActionInvoker'));
        $server->setContinuationContextProvider($this->continuationContextProvider);

        $this->flowID = $flowName;
        $this->eventName = null;
        $this->flowExecutionTicket = null;
        $server->invoke(new \stdClass());
        $pageFlowInstance = $server->getPageFlowInstance();
        $server->shutdown();

        $this->flowID = $flowName;
        $this->eventName = 'foo';
        $this->flowExecutionTicket = $pageFlowInstance->getID();
        $server->invoke(new \stdClass());
        $pageFlowInstance = $server->getPageFlowInstance();

        $this->assertFalse($pageFlowInstance->checkLastEvent());
    }

    /**
     * @since Method available since Release 1.14.0
     */
    public function testCurrentStateNameShouldBeAbleToGetIfContinuationHasActivated()
    {
        $flowName = 'CheckLastEvent';
        $pageFlowInstanceRepository = new PageFlowInstanceRepository(new PageFlowRepository(new PageFlowRegistry($this->cacheDirectory, '.yaml'), $this->cacheDirectory, true));
        $pageFlowInstanceRepository->addPageFlow($flowName, false);
        $server = new ContinuationServer($pageFlowInstanceRepository);
        $server->setActionInvoker(\Phake::mock('Piece\Flow\PageFlow\ActionInvoker'));
        $server->setContinuationContextProvider($this->continuationContextProvider);

        $this->flowID = $flowName;
        $this->eventName = null;
        $this->flowExecutionTicket = null;
        $server->invoke(new \stdClass());
        $pageFlowInstance = $server->getPageFlowInstance();

        $this->assertEquals('DisplayEdit', $pageFlowInstance->getCurrentStateName());

        $server->shutdown();

        $this->flowID = $flowName;
        $this->eventName = 'DisplayEditConfirmFromDisplayEdit';
        $this->flowExecutionTicket = $pageFlowInstance->getID();
        $server->invoke(new \stdClass());
        $pageFlowInstance = $server->getPageFlowInstance();

        $this->assertEquals('DisplayEditConfirm', $pageFlowInstance->getCurrentStateName());

        $server->shutdown();

        $this->flowID = $flowName;
        $this->eventName = 'DisplayEditFinishFromDisplayEditConfirm';
        $this->flowExecutionTicket = $pageFlowInstance->getID();
        $server->invoke(new \stdClass());
        $pageFlowInstance = $server->getPageFlowInstance();

        $this->assertEquals('DisplayEditFinish', $pageFlowInstance->getCurrentStateName());
    }

    /**
     * @since Method available since Release 1.15.1
     */
    public function testPageFlowInstanceExpiredExceptionShouldRaiseAfterSweepingIt()
    {
        $flowName = 'FlowExecutionExpired';
        $pageFlowInstanceRepository = new PageFlowInstanceRepository(new PageFlowRepository(new PageFlowRegistry($this->cacheDirectory, '.yaml'), $this->cacheDirectory, true));
        $pageFlowInstanceRepository->addPageFlow($flowName, false);
        $server = new ContinuationServer($pageFlowInstanceRepository, new GC(1));
        $server->setActionInvoker(\Phake::mock('Piece\Flow\PageFlow\ActionInvoker'));
        $server->setContinuationContextProvider($this->continuationContextProvider);

        $this->flowID = $flowName;
        $this->eventName = null;
        $this->flowExecutionTicket = null;
        $server->invoke(new \stdClass());
        $pageFlowInstance1 = $server->getPageFlowInstance();
        $server->shutdown();

        sleep(2);

        $this->flowID = $flowName;
        $this->eventName = null;
        $this->flowExecutionTicket = null;
        $server->invoke(new \stdClass());
        $pageFlowInstance2 = $server->getPageFlowInstance();
        $server->shutdown();

        $this->assertTrue($pageFlowInstance1->getID() != $pageFlowInstance2->getID());

        $this->flowID = $flowName;
        $this->eventName = null;
        $this->flowExecutionTicket = $pageFlowInstance1->getID();

        try {
            $server->invoke(new \stdClass());
            $this->fail('An expected exception has not been raised.');
        } catch (PageFlowInstanceExpiredException $e) {
        }
    }

    /**
     * @return \Piece\Flow\PageFlow\ActionInvoker
     */
    protected function createCounterActionInvoker()
    {
        $actionInvoker = \Phake::mock('Piece\Flow\PageFlow\ActionInvoker');
        \Phake::when($actionInvoker)->invoke('setup', $this->anything())->thenGetReturnByLambda(function ($actionID, EventContext $eventContext) {
            if (!$eventContext->getPageFlow()->hasAttribute('counter')) {
                $eventContext->getPageFlow()->setAttribute('counter', 0);
            }
        });
        \Phake::when($actionInvoker)->invoke('increase', $this->anything())->thenGetReturnByLambda(function ($actionID, EventContext $eventContext) {
            $eventContext->getPageFlow()->setAttribute('counter', $eventContext->getPageFlow()->getAttribute('counter') + 1);

            return 'succeed';
        });

        return $actionInvoker;
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
