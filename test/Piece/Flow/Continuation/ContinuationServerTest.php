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
     * @since Method available since Release 2.0.0
     *
     * @test
     */
    public function startsPageFlowInstancesForAnExclusivePageFlow()
    {
        $pageFlowInstanceRepository = $this->createPageFlowInstanceRepository();
        $pageFlowInstanceRepository->addPageFlow('Counter', true);
        $continuationServer = new ContinuationServer($pageFlowInstanceRepository);
        $continuationServer->setActionInvoker($this->createCounterActionInvoker());
        $continuationServer->setContinuationContextProvider($this->continuationContextProvider);

        $this->pageFlowID = 'Counter';
        $this->eventID = null;
        $this->pageFlowInstanceID = null;
        $continuationServer->activate(new \stdClass());
        $pageFlowInstance1 = $continuationServer->getPageFlowInstance();
        $continuationServer->shutdown();

        $this->pageFlowID = 'Counter';
        $this->eventID = null;
        $this->pageFlowInstanceID = null;
        $continuationServer->activate(new \stdClass());
        $pageFlowInstance2 = $continuationServer->getPageFlowInstance();
        $continuationServer->shutdown();

        $this->assertThat($pageFlowInstance2->getID(), $this->logicalNot($this->equalTo($pageFlowInstance1->getID())));
        $this->assertThat($continuationServer->getPageFlowInstanceRepository()->findByID($pageFlowInstance1->getID()), $this->isNull());
        $this->assertThat($continuationServer->getPageFlowInstanceRepository()->findByID($pageFlowInstance2->getID()), $this->logicalNot($this->isNull()));
    }

    /**
     * @since Method available since Release 2.0.0
     *
     * @test
     */
    public function startsPageFlowInstancesForANonExclusivePageFlow()
    {
        $pageFlowInstanceRepository = $this->createPageFlowInstanceRepository();
        $pageFlowInstanceRepository->addPageFlow('Counter', false);
        $continuationServer = new ContinuationServer($pageFlowInstanceRepository);
        $continuationServer->setActionInvoker($this->createCounterActionInvoker());
        $continuationServer->setContinuationContextProvider($this->continuationContextProvider);

        $this->pageFlowID = 'Counter';
        $this->eventID = null;
        $this->pageFlowInstanceID = null;
        $continuationServer->activate(new \stdClass());
        $pageFlowInstance1 = $continuationServer->getPageFlowInstance();
        $continuationServer->shutdown();

        $this->pageFlowID = 'Counter';
        $this->eventID = null;
        $this->pageFlowInstanceID = null;
        $continuationServer->activate(new \stdClass());
        $pageFlowInstance2 = $continuationServer->getPageFlowInstance();
        $continuationServer->shutdown();

        $this->assertThat($pageFlowInstance2->getID(), $this->logicalNot($this->equalTo($pageFlowInstance1->getID())));
        $this->assertThat($continuationServer->getPageFlowInstanceRepository()->findByID($pageFlowInstance1->getID()), $this->logicalNot($this->isNull()));
        $this->assertThat($continuationServer->getPageFlowInstanceRepository()->findByID($pageFlowInstance2->getID()), $this->logicalNot($this->isNull()));
    }

    /**
     * @since Method available since Release 2.0.0
     *
     * @test
     */
    public function startsPageFlowInstancesForMultiplePageFlows()
    {
        $pageFlowInstanceRepository = $this->createPageFlowInstanceRepository();
        $pageFlowInstanceRepository->addPageFlow('Counter', false);
        $pageFlowInstanceRepository->addPageFlow('SecondCounter', false);
        $continuationServer = new ContinuationServer($pageFlowInstanceRepository);
        $continuationServer->setActionInvoker($this->createCounterActionInvoker());
        $continuationServer->setContinuationContextProvider($this->continuationContextProvider);

        $this->pageFlowID = 'Counter';
        $this->eventID = null;
        $this->pageFlowInstanceID = null;
        $continuationServer->activate(new \stdClass());
        $pageFlowInstance1 = $continuationServer->getPageFlowInstance();
        $continuationServer->shutdown();

        $this->pageFlowID = 'SecondCounter';
        $this->eventID = null;
        $this->pageFlowInstanceID = null;
        $continuationServer->activate(new \stdClass());
        $pageFlowInstance2 = $continuationServer->getPageFlowInstance();
        $continuationServer->shutdown();

        $this->assertThat($pageFlowInstance2->getID(), $this->logicalNot($this->equalTo($pageFlowInstance1->getID())));
        $this->assertThat($continuationServer->getPageFlowInstanceRepository()->findByID($pageFlowInstance1->getID()), $this->logicalNot($this->isNull()));
        $this->assertThat($continuationServer->getPageFlowInstanceRepository()->findByID($pageFlowInstance2->getID()), $this->logicalNot($this->isNull()));
    }

    /**
     * @since Method available since Release 2.0.0
     *
     * @test
     */
    public function continuesAPageFlowInstance()
    {
        $pageFlowInstanceRepository = $this->createPageFlowInstanceRepository();
        $pageFlowInstanceRepository->addPageFlow('Counter', false);
        $continuationServer = new ContinuationServer($pageFlowInstanceRepository);
        $continuationServer->setActionInvoker($this->createCounterActionInvoker());
        $continuationServer->setContinuationContextProvider($this->continuationContextProvider);

        $this->pageFlowID = 'Counter';
        $this->eventID = null;
        $this->pageFlowInstanceID = null;
        $continuationServer->activate(new \stdClass());
        $pageFlowInstance1 = $continuationServer->getPageFlowInstance();
        $continuationServer->shutdown();

        $this->pageFlowID = 'Counter';
        $this->eventID = null;
        $this->pageFlowInstanceID = $pageFlowInstance1->getID();
        $continuationServer->activate(new \stdClass());
        $pageFlowInstance2 = $continuationServer->getPageFlowInstance();
        $continuationServer->shutdown();

        $this->assertThat($pageFlowInstance2->getID(), ($this->equalTo($pageFlowInstance1->getID())));
        $this->assertThat($pageFlowInstance2->getAttributes()->get('counter'), $this->equalTo(2));
    }

    /**
     * @test
     * @expectedException \Piece\Flow\Continuation\UnexpectedPageFlowIDException
     */
    public function raisesAnExceptionWhenAnUnexpectedPageFlowIdIsSpecifiedForTheSecondTimeOrLater()
    {
        $pageFlowInstanceRepository = $this->createPageFlowInstanceRepository();
        $pageFlowInstanceRepository->addPageFlow('Counter', false);
        $pageFlowInstanceRepository->addPageFlow('SecondCounter', false);
        $continuationServer = new ContinuationServer($pageFlowInstanceRepository);
        $continuationServer->setActionInvoker($this->createCounterActionInvoker());
        $continuationServer->setContinuationContextProvider($this->continuationContextProvider);

        $this->pageFlowID = 'Counter';
        $this->eventID = null;
        $this->pageFlowInstanceID = null;
        $continuationServer->activate(new \stdClass());
        $pageFlowInstance = $continuationServer->getPageFlowInstance();
        $continuationServer->shutdown();

        $this->pageFlowID = 'SecondCounter';
        $this->eventID = null;
        $this->pageFlowInstanceID = $pageFlowInstance->getID();
        $continuationServer->activate(new \stdClass());
    }

    /**
     * @param boolean $exclusive
     * @since Method available since Release 2.0.0
     *
     * @test
     * @dataProvider providePageFlowExclusiveness
     */
    public function findsThePageFlowInstanceByAPageFlowId($exclusive)
    {
        $pageFlowInstanceRepository = $this->createPageFlowInstanceRepository();
        $pageFlowInstanceRepository->addPageFlow('Counter', $exclusive);
        $continuationServer = new ContinuationServer($pageFlowInstanceRepository);
        $continuationServer->setActionInvoker($this->createCounterActionInvoker());
        $continuationServer->setContinuationContextProvider($this->continuationContextProvider);

        $this->pageFlowID = 'Counter';
        $this->eventID = null;
        $this->pageFlowInstanceID = null;
        $continuationServer->activate(new \stdClass());
        $continuationServer->shutdown();

        $this->assertThat($continuationServer->getPageFlowInstanceRepository()->findByPageFlowID('Counter'), $exclusive ? $this->identicalTo($continuationServer->getPageFlowInstance()) : $this->isNull());
    }

    /**
     * @return array
     * @since Method available since Release 2.0.0
     */
    public function providePageFlowExclusiveness()
    {
        return array(array(true), array(false));
    }

    /**
     * @param integer $expirationTime
     * @param string $firstTime
     * @param string $secondTime
     * @param boolean $shouldRaiseException
     * @since Method available since Release 2.0.0
     *
     * @test
     * @dataProvider provideTimesForExpiration
     */
    public function raisesAnExceptionWhenThePageFlowInstanceHasExpired($expirationTime, $firstTime, $secondTime, $shouldRaiseException)
    {
        $clock = \Phake::mock('Piece\Flow\Continuation\Clock');
        \Phake::when($clock)->now()
            ->thenReturn(new \DateTime($firstTime))
            ->thenReturn(new \DateTime($secondTime));
        $pageFlowInstanceRepository = $this->createPageFlowInstanceRepository();
        $pageFlowInstanceRepository->addPageFlow('Counter', false);
        $continuationServer = new ContinuationServer($pageFlowInstanceRepository, new GC($expirationTime, $clock));
        $continuationServer->setActionInvoker(\Phake::mock('Piece\Flow\PageFlow\ActionInvokerInterface'));
        $continuationServer->setContinuationContextProvider($this->continuationContextProvider);

        $this->pageFlowID = 'Counter';
        $this->eventID = null;
        $this->pageFlowInstanceID = null;
        $continuationServer->activate(new \stdClass());
        $pageFlowInstance = $continuationServer->getPageFlowInstance();
        $continuationServer->shutdown();

        $this->pageFlowID = 'Counter';
        $this->eventID = null;
        $this->pageFlowInstanceID = $pageFlowInstance->getID();

        if ($shouldRaiseException) {
            try {
                $continuationServer->activate(new \stdClass());
                $continuationServer->shutdown();
                $this->fail('An expected exception has not been raised.');
            } catch (PageFlowInstanceExpiredException $e) {
            }
        } else {
            $continuationServer->activate(new \stdClass());
            $continuationServer->shutdown();
        }
    }

    /**
     * @return array
     */
    public function provideTimesForExpiration()
    {
        return array(
            array(1440, '2012-08-09 15:43:00', '2012-08-09 16:07:00', false),
            array(1440, '2012-08-09 15:43:00', '2012-08-09 16:07:01', true),
        );
    }

    /**
     * @since Method available since Release 2.0.0
     *
     * @test
     */
    public function validatesTheLastReceivedEvent()
    {
        $pageFlowInstanceRepository = $this->createPageFlowInstanceRepository();
        $pageFlowInstanceRepository->addPageFlow('CheckLastEvent', false);
        $continuationServer = new ContinuationServer($pageFlowInstanceRepository);
        $continuationServer->setActionInvoker($this->createCounterActionInvoker());
        $continuationServer->setContinuationContextProvider($this->continuationContextProvider);

        $this->pageFlowID = 'CheckLastEvent';
        $this->eventID = 'nonExistingEvent';
        $this->pageFlowInstanceID = null;
        $continuationServer->activate(new \stdClass());
        $pageFlowInstance = $continuationServer->getPageFlowInstance();
        $continuationServer->shutdown();

        $this->assertThat($pageFlowInstance->validateReceivedEvent(), $this->isTrue());

        $this->pageFlowID = 'CheckLastEvent';
        $this->eventID = 'DisplayEditConfirmFromDisplayEdit';
        $this->pageFlowInstanceID = $pageFlowInstance->getID();
        $continuationServer->activate(new \stdClass());
        $pageFlowInstance = $continuationServer->getPageFlowInstance();
        $continuationServer->shutdown();

        $this->assertThat($pageFlowInstance->validateReceivedEvent(), $this->isTrue());

        $this->pageFlowID = 'CheckLastEvent';
        $this->eventID = 'nonExistingEvent';
        $this->pageFlowInstanceID = $pageFlowInstance->getID();
        $continuationServer->activate(new \stdClass());
        $pageFlowInstance = $continuationServer->getPageFlowInstance();
        $continuationServer->shutdown();

        $this->assertThat($pageFlowInstance->validateReceivedEvent(), $this->isFalse());
    }

    /**
     * @return \Piece\Flow\PageFlow\ActionInvokerInterface
     */
    protected function createCounterActionInvoker()
    {
        $actionInvoker = \Phake::mock('Piece\Flow\PageFlow\ActionInvokerInterface');
        \Phake::when($actionInvoker)->invoke('setup', $this->anything())->thenGetReturnByLambda(function ($actionID, EventContext $eventContext) {
            $eventContext->getPageFlow()->getAttributes()->set('counter', 0);
        });
        \Phake::when($actionInvoker)->invoke('increase', $this->anything())->thenGetReturnByLambda(function ($actionID, EventContext $eventContext) {
            $eventContext->getPageFlow()->getAttributes()->set('counter', $eventContext->getPageFlow()->getAttributes()->get('counter') + 1);
        });

        return $actionInvoker;
    }

    /**
     * @since Method available since Release 2.0.0
     *
     * @return \Piece\Flow\Continuation\PageFlowInstanceRepository
     */
    protected function createPageFlowInstanceRepository()
    {
        return new PageFlowInstanceRepository(new PageFlowRepository(new PageFlowRegistry($this->cacheDirectory, '.yaml'), $this->cacheDirectory, true));
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
