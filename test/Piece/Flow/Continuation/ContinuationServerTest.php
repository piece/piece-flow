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
    protected $pageFlowInstanceID;

    /**
     * @var string
     * @since Property available since Release 2.0.0
     */
    protected $pageFlowID;

    /**
     * @var string
     * @since Property available since Release 2.0.0
     */
    protected $eventID;

    /**
     * @var \Piece\Flow\Continuation\ContinuationContextProvider
     * @since Property available since Release 2.0.0
     */
    protected $continuationContextProvider;

    public function getPageFlowInstanceID()
    {
        return $this->pageFlowInstanceID;
    }

    public function getPageFlowID()
    {
        return $this->pageFlowID;
    }

    public function getEventID()
    {
        return $this->eventID;
    }

    protected function setUp()
    {
        $this->cacheDirectory = dirname(__FILE__) . '/' . basename(__FILE__, '.php');
        $this->continuationContextProvider = \Phake::mock('Piece\Flow\Continuation\ContinuationContextProvider');
        $self = $this;
        \Phake::when($this->continuationContextProvider)->getEventID()->thenGetReturnByLambda(function () use ($self) { return $self->getEventID(); });
        \Phake::when($this->continuationContextProvider)->getPageFlowID()->thenGetReturnByLambda(function () use ($self) { return $self->getPageFlowID(); });
        \Phake::when($this->continuationContextProvider)->getPageFlowInstanceID()->thenGetReturnByLambda(function () use ($self) { return $self->getPageFlowInstanceID(); });
    }

    /**
     * @param string $pageFlowID1
     * @param string $pageFlowID2
     *
     * @test
     * @dataProvider providePageFlowIDs
     */
    public function activatesPageFlowInstances($pageFlowID1, $pageFlowID2)
    {
        $pageFlowInstanceRepository = new PageFlowInstanceRepository(new PageFlowRepository(new PageFlowRegistry($this->cacheDirectory, '.yaml'), $this->cacheDirectory, true));
        $pageFlowInstanceRepository->addPageFlow($pageFlowID1, false);
        $pageFlowInstanceRepository->addPageFlow($pageFlowID2, false);
        $server = new ContinuationServer($pageFlowInstanceRepository);
        $actionInvoker = $this->createCounterActionInvoker();
        $server->setActionInvoker($actionInvoker);
        $server->setContinuationContextProvider($this->continuationContextProvider);

        $this->pageFlowID = $pageFlowID1;
        $this->eventID = null;
        $this->pageFlowInstanceID = null;
        $server->activate(new \stdClass());
        $pageFlowInstance1 = $server->getPageFlowInstance();
        $server->shutdown();

        $this->pageFlowID = $pageFlowID2;
        $this->eventID = null;
        $this->pageFlowInstanceID = null;
        $server->activate(new \stdClass());
        $pageFlowInstance2 = $server->getPageFlowInstance();
        $server->shutdown();

        $this->pageFlowID = $pageFlowID1;
        $this->eventID = null;
        $this->pageFlowInstanceID = $pageFlowInstance1->getID();
        $server->activate(new \stdClass());
        $pageFlowInstance3 = $server->getPageFlowInstance();

        $this->pageFlowID = $pageFlowID2;
        $this->eventID = null;
        $this->pageFlowInstanceID = $pageFlowInstance2->getID();
        $server->activate(new \stdClass());
        $pageFlowInstance4 = $server->getPageFlowInstance();

        $this->assertThat($pageFlowInstance3->getID(), $this->equalTo($pageFlowInstance1->getID()));
        $this->assertThat($pageFlowInstance4->getID(), $this->equalTo($pageFlowInstance2->getID()));
        $this->assertThat($pageFlowInstance4->getID(), $this->logicalNot($this->equalTo($pageFlowInstance3->getID())));
        $this->assertThat($pageFlowInstance3->getAttributes()->get('counter'), $this->equalTo(2));
        $this->assertThat($pageFlowInstance4->getAttributes()->get('counter'), $this->equalTo(2));
    }

    public function providePageFlowIDs()
    {
        return array(
            array('Counter', 'Counter'),
            array('Counter', 'SecondCounter'),
        );
    }

    /**
     * @test
     * @expectedException \Piece\Flow\Continuation\UnexpectedPageFlowIDException
     */
    public function raisesAnExceptionWhenAnUnexpectedPageFlowIdIsSpecifiedForTheSecondTimeOrLater()
    {
        $pageFlowInstanceRepository = new PageFlowInstanceRepository(new PageFlowRepository(new PageFlowRegistry($this->cacheDirectory, '.yaml'), $this->cacheDirectory, true));
        $pageFlowInstanceRepository->addPageFlow('Counter', false);
        $server = new ContinuationServer($pageFlowInstanceRepository);
        $server->setActionInvoker($this->createCounterActionInvoker());
        $server->setContinuationContextProvider($this->continuationContextProvider);

        $this->pageFlowID = 'Counter';
        $this->eventID = null;
        $this->pageFlowInstanceID = null;
        $server->activate(new \stdClass());
        $pageFlowInstance = $server->getPageFlowInstance();
        $server->shutdown();

        $this->pageFlowID = 'SecondCounter';
        $this->eventID = null;
        $this->pageFlowInstanceID = $pageFlowInstance->getID();
        $server->activate(new \stdClass());
    }

    public function testInvocationInMultipleFlowModeAndFlowInExclusiveMode()
    {
        $pageFlowInstanceRepository = new PageFlowInstanceRepository(new PageFlowRepository(new PageFlowRegistry($this->cacheDirectory, '.yaml'), $this->cacheDirectory, true));
        $pageFlowInstanceRepository->addPageFlow('Counter', false);
        $pageFlowInstanceRepository->addPageFlow('SecondCounter', false);
        $server = new ContinuationServer($pageFlowInstanceRepository);
        $server->setActionInvoker($this->createCounterActionInvoker());
        $server->setContinuationContextProvider($this->continuationContextProvider);

        $this->pageFlowID = 'Counter';
        $this->eventID = null;
        $this->pageFlowInstanceID = null;
        $server->activate(new \stdClass());
        $pageFlowInstance1 = $server->getPageFlowInstance();

        $this->assertEquals(1, $pageFlowInstance1->getAttributes()->get('counter'));

        $server->shutdown();

        $this->pageFlowID = 'SecondCounter';
        $this->eventID = null;
        $this->pageFlowInstanceID = null;
        $server->activate(new \stdClass());
        $pageFlowInstance3 = $server->getPageFlowInstance();

        $this->assertEquals(1, $pageFlowInstance3->getAttributes()->get('counter'));

        $server->shutdown();

        $this->pageFlowID = 'Counter';
        $this->eventID = null;
        $this->pageFlowInstanceID = $pageFlowInstance1->getID();
        $server->activate(new \stdClass());
        $pageFlowInstance2 = $server->getPageFlowInstance();

        $this->assertEquals(2, $pageFlowInstance2->getAttributes()->get('counter'));

        $this->assertThat(strlen($pageFlowInstance1->getID()), $this->greaterThan(0));
        $this->assertThat(strlen($pageFlowInstance3->getID()), $this->greaterThan(0));
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

        $this->pageFlowID = 'Counter';
        $this->eventID = null;
        $this->pageFlowInstanceID = null;
        $server->activate(new \stdClass());
        $pageFlowInstance = $server->getPageFlowInstance();
        $pageFlowInstance->getAttributes()->set('foo', 'bar');
        $server->shutdown();

        $this->pageFlowID = 'Counter';
        $this->eventID = null;
        $this->pageFlowInstanceID = $pageFlowInstance->getID();
        $server->activate(new \stdClass());
        $pageFlowInstance = $server->getPageFlowInstance();
        $pageFlowInstance->getAttributes()->set('bar', 'baz');
        $baz1 = new \stdClass();
        $pageFlowInstance->getAttributes()->set('baz', $baz1);
        $server->shutdown();

        $this->pageFlowID = 'Counter';
        $this->eventID = null;
        $this->pageFlowInstanceID = $pageFlowInstance->getID();
        $server->activate(new \stdClass());
        $pageFlowInstance = $server->getPageFlowInstance();

        $this->assertTrue($pageFlowInstance->getAttributes()->has('foo'));
        $this->assertEquals('bar', $pageFlowInstance->getAttributes()->get('foo'));
        $this->assertTrue($pageFlowInstance->getAttributes()->has('bar'));
        $this->assertEquals('baz', $pageFlowInstance->getAttributes()->get('bar'));

        $baz1->foo = 'bar';

        $this->assertTrue(property_exists($baz1, 'foo'));
        $this->assertEquals('bar', $baz1->foo);

        $baz2 = $pageFlowInstance->getAttributes()->get('baz');

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
        $this->pageFlowID = 'Shutdown';
        $this->eventID = null;
        $this->pageFlowInstanceID = null;
        $server->activate(new \stdClass());
        $pageFlowInstance1 = $server->getPageFlowInstance();
        $server->shutdown();

        $this->pageFlowID = 'Shutdown';
        $this->eventID = 'go';
        $this->pageFlowInstanceID = $pageFlowInstance1->getID();
        $server->activate(new \stdClass());
        $pageFlowInstance2 = $server->getPageFlowInstance();

        $this->assertEquals(1, $shutdownCount);
        $this->assertEquals($pageFlowInstance1->getID(), $pageFlowInstance2->getID());

        $server->shutdown();

        /*
         * Failure to continue the 'Shutdown' from the previous flow
         * execution ticket.
         */
        $this->pageFlowID = null;
        $this->eventID = 'go';
        $this->pageFlowInstanceID = $pageFlowInstance1->getID();

        try {
            $server->activate(new \stdClass());
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
        $this->pageFlowID = 'Shutdown';
        $this->eventID = null;
        $this->pageFlowInstanceID = null;
        $server->activate(new \stdClass());
        $pageFlowInstance1 = $server->getPageFlowInstance();
        $server->shutdown();

        $this->pageFlowID = 'Shutdown';
        $this->eventID = 'go';
        $this->pageFlowInstanceID = $pageFlowInstance1->getID();
        $server->activate(new \stdClass());
        $pageFlowInstance2 = $server->getPageFlowInstance();
        $server->shutdown();

        $this->assertEquals(1, $shutdownCount);
        $this->assertEquals($pageFlowInstance1->getID(), $pageFlowInstance2->getID());
        $this->assertThat(strlen($pageFlowInstance1->getID()), $this->greaterThan(0));

        /*
         * Failure to continue the 'Shutdown' from the previous flow
         * execution ticket. And starting a new 'Shutdown'.
         */
        $this->pageFlowID = 'Shutdown';
        $this->eventID = 'go';
        $this->pageFlowInstanceID = $pageFlowInstance1->getID();
        $server->activate(new \stdClass());
        $pageFlowInstance3 = $server->getPageFlowInstance();

        $this->assertTrue($pageFlowInstance1->getID() != $pageFlowInstance3->getID());
        $this->assertThat(strlen($pageFlowInstance3->getID()), $this->greaterThan(0));
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

        $this->pageFlowID = 'Counter';
        $this->eventID = null;
        $this->pageFlowInstanceID = null;
        $server->activate(new \stdClass());
        $pageFlowInstance1 = $server->getPageFlowInstance();

        $this->assertEquals(1, $pageFlowInstance1->getAttributes()->get('counter'));

        $server->shutdown();

        $this->pageFlowID = 'Counter';
        $this->eventID = null;
        $this->pageFlowInstanceID = null;
        $server->activate(new \stdClass());
        $pageFlowInstance2 = $server->getPageFlowInstance();

        \Phake::verify($pageFlowInstanceRepository)->remove($pageFlowInstance1);
        $this->assertEquals(1, $pageFlowInstance2->getAttributes()->get('counter'));
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

        $this->pageFlowID = 'Counter';
        $this->eventID = null;
        $this->pageFlowInstanceID = null;
        $server->activate(new \stdClass());
        $pageFlowInstance1 = $server->getPageFlowInstance();

        $this->pageFlowID = 'Counter';
        $this->eventID = null;
        $this->pageFlowInstanceID = $pageFlowInstance1->getID();
        $server->activate(new \stdClass());

        $this->assertEquals(2, $pageFlowInstance1->getAttributes()->get('counter'));

        $this->pageFlowID = 'SecondCounter';
        $this->eventID = null;
        $this->pageFlowInstanceID = null;
        $server->activate(new \stdClass());
        $pageFlowInstance2 = $server->getPageFlowInstance();

        $this->assertEquals(1, $pageFlowInstance2->getAttributes()->get('counter'));
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
        $server = new ContinuationServer($pageFlowInstanceRepository, new GC(1, new Clock()));
        $server->setActionInvoker(\Phake::mock('Piece\Flow\PageFlow\ActionInvoker'));
        $server->setContinuationContextProvider($this->continuationContextProvider);

        $this->pageFlowID = $flowName;
        $this->eventID = null;
        $this->pageFlowInstanceID = null;
        $server->activate(new \stdClass());
        $pageFlowInstance = $server->getPageFlowInstance();
        $server->shutdown();

        sleep(2);

        $this->pageFlowID = $flowName;
        $this->eventID = null;
        $this->pageFlowInstanceID = $pageFlowInstance->getID();
        $server->activate(new \stdClass());
    }

    /**
     * @since Method available since Release 1.11.0
     */
    public function testPageFlowInstanceExpiredExceptionShouldNotBeRaisedWhenFlowExecutionHasNotExpired()
    {
        $flowName = 'FlowExecutionExpired';
        $pageFlowInstanceRepository = new PageFlowInstanceRepository(new PageFlowRepository(new PageFlowRegistry($this->cacheDirectory, '.yaml'), $this->cacheDirectory, true));
        $pageFlowInstanceRepository->addPageFlow($flowName, false);
        $server = new ContinuationServer($pageFlowInstanceRepository, new GC(2, new Clock()));
        $server->setActionInvoker(\Phake::mock('Piece\Flow\PageFlow\ActionInvoker'));
        $server->setContinuationContextProvider($this->continuationContextProvider);

        $this->pageFlowID = $flowName;
        $this->eventID = null;
        $this->pageFlowInstanceID = null;
        $server->activate(new \stdClass());
        $pageFlowInstance = $server->getPageFlowInstance();
        $server->shutdown();

        sleep(1);

        $this->pageFlowID = $flowName;
        $this->eventID = null;
        $this->pageFlowInstanceID = $pageFlowInstance->getID();
        $server->activate(new \stdClass());

        sleep(1);

        $this->pageFlowID = $flowName;
        $this->eventID = null;
        $this->pageFlowInstanceID = $pageFlowInstance->getID();
        $server->activate(new \stdClass());

        sleep(1);

        $this->pageFlowID = $flowName;
        $this->eventID = null;
        $this->pageFlowInstanceID = $pageFlowInstance->getID();
        $server->activate(new \stdClass());
    }

    /**
     * @since Method available since Release 1.11.0
     */
    public function testNewFlowExecutionShouldBeAbleToStartWithSameRequestAfterFlowExecutionIsExpired()
    {
        $flowName = 'FlowExecutionExpired';
        $this->pageFlowID = $flowName;
        $pageFlowInstanceRepository = new PageFlowInstanceRepository(new PageFlowRepository(new PageFlowRegistry($this->cacheDirectory, '.yaml'), $this->cacheDirectory, true));
        $pageFlowInstanceRepository->addPageFlow($flowName, false);
        $server = new ContinuationServer($pageFlowInstanceRepository, new GC(1, new Clock()));
        $server->setActionInvoker(\Phake::mock('Piece\Flow\PageFlow\ActionInvoker'));
        $server->setContinuationContextProvider($this->continuationContextProvider);

        $this->pageFlowID = $flowName;
        $this->eventID = null;
        $this->pageFlowInstanceID = null;
        $server->activate(new \stdClass());
        $pageFlowInstance = $server->getPageFlowInstance();
        $server->shutdown();

        sleep(2);

        $this->pageFlowID = $flowName;
        $this->eventID = null;
        $this->pageFlowInstanceID = $pageFlowInstance->getID();

        try {
            $server->activate(new \stdClass());
            $this->fail('An expected exception has not been raised.');
        } catch (PageFlowInstanceExpiredException $e) {
        }

        $server->shutdown();

        $this->pageFlowID = $flowName;
        $this->eventID = null;
        $this->pageFlowInstanceID = $pageFlowInstance->getID();
        $server->activate(new \stdClass());
        $newPageFlowInstance = $server->getPageFlowInstance();

        $this->assertTrue($newPageFlowInstance->getID() != $this->pageFlowInstanceID);
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

        $this->pageFlowID = $flowName;
        $this->eventID = 'foo';
        $this->pageFlowInstanceID = null;
        $server->activate(new \stdClass());

        $this->assertTrue($server->getPageFlowInstance()->validateReceivedEvent());
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

        $this->pageFlowID = $flowName;
        $this->eventID = null;
        $this->pageFlowInstanceID = null;
        $server->activate(new \stdClass());
        $pageFlowInstance = $server->getPageFlowInstance();
        $server->shutdown();

        $this->pageFlowID = $flowName;
        $this->eventID = 'DisplayEditConfirmFromDisplayEdit';
        $this->pageFlowInstanceID = $pageFlowInstance->getID();
        $server->activate(new \stdClass());
        $pageFlowInstance = $server->getPageFlowInstance();

        $this->assertTrue($pageFlowInstance->validateReceivedEvent());

        $server->shutdown();

        $this->pageFlowID = $flowName;
        $this->eventID = 'DisplayEditFinishFromDisplayEditConfirm';
        $this->pageFlowInstanceID = $pageFlowInstance->getID();
        $server->activate(new \stdClass());
        $pageFlowInstance = $server->getPageFlowInstance();

        $this->assertTrue($pageFlowInstance->validateReceivedEvent());
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

        $this->pageFlowID = $flowName;
        $this->eventID = null;
        $this->pageFlowInstanceID = null;
        $server->activate(new \stdClass());
        $pageFlowInstance = $server->getPageFlowInstance();
        $server->shutdown();

        $this->pageFlowID = $flowName;
        $this->eventID = 'foo';
        $this->pageFlowInstanceID = $pageFlowInstance->getID();
        $server->activate(new \stdClass());
        $pageFlowInstance = $server->getPageFlowInstance();

        $this->assertFalse($pageFlowInstance->validateReceivedEvent());
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

        $this->pageFlowID = $flowName;
        $this->eventID = null;
        $this->pageFlowInstanceID = null;
        $server->activate(new \stdClass());
        $pageFlowInstance = $server->getPageFlowInstance();

        $this->assertEquals('DisplayEdit', $pageFlowInstance->getCurrentState()->getID());

        $server->shutdown();

        $this->pageFlowID = $flowName;
        $this->eventID = 'DisplayEditConfirmFromDisplayEdit';
        $this->pageFlowInstanceID = $pageFlowInstance->getID();
        $server->activate(new \stdClass());
        $pageFlowInstance = $server->getPageFlowInstance();

        $this->assertEquals('DisplayEditConfirm', $pageFlowInstance->getCurrentState()->getID());

        $server->shutdown();

        $this->pageFlowID = $flowName;
        $this->eventID = 'DisplayEditFinishFromDisplayEditConfirm';
        $this->pageFlowInstanceID = $pageFlowInstance->getID();
        $server->activate(new \stdClass());
        $pageFlowInstance = $server->getPageFlowInstance();

        $this->assertEquals('DisplayEditFinish', $pageFlowInstance->getCurrentState()->getID());
    }

    /**
     * @since Method available since Release 1.15.1
     */
    public function testPageFlowInstanceExpiredExceptionShouldRaiseAfterSweepingIt()
    {
        $flowName = 'FlowExecutionExpired';
        $pageFlowInstanceRepository = new PageFlowInstanceRepository(new PageFlowRepository(new PageFlowRegistry($this->cacheDirectory, '.yaml'), $this->cacheDirectory, true));
        $pageFlowInstanceRepository->addPageFlow($flowName, false);
        $server = new ContinuationServer($pageFlowInstanceRepository, new GC(1, new Clock()));
        $server->setActionInvoker(\Phake::mock('Piece\Flow\PageFlow\ActionInvoker'));
        $server->setContinuationContextProvider($this->continuationContextProvider);

        $this->pageFlowID = $flowName;
        $this->eventID = null;
        $this->pageFlowInstanceID = null;
        $server->activate(new \stdClass());
        $pageFlowInstance1 = $server->getPageFlowInstance();
        $server->shutdown();

        sleep(2);

        $this->pageFlowID = $flowName;
        $this->eventID = null;
        $this->pageFlowInstanceID = null;
        $server->activate(new \stdClass());
        $pageFlowInstance2 = $server->getPageFlowInstance();
        $server->shutdown();

        $this->assertTrue($pageFlowInstance1->getID() != $pageFlowInstance2->getID());

        $this->pageFlowID = $flowName;
        $this->eventID = null;
        $this->pageFlowInstanceID = $pageFlowInstance1->getID();

        try {
            $server->activate(new \stdClass());
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
            $eventContext->getPageFlow()->getAttributes()->set('counter', 0);
        });
        \Phake::when($actionInvoker)->invoke('increase', $this->anything())->thenGetReturnByLambda(function ($actionID, EventContext $eventContext) {
            $eventContext->getPageFlow()->getAttributes()->set('counter', $eventContext->getPageFlow()->getAttributes()->get('counter') + 1);
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
