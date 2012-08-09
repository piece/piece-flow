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
        $pageFlowInstanceRepository = new PageFlowInstanceRepository(new PageFlowRepository(new PageFlowRegistry($this->cacheDirectory, '.yaml'), $this->cacheDirectory, true));
        $pageFlowInstanceRepository->addPageFlow('Counter', true);
        $server = new ContinuationServer($pageFlowInstanceRepository);
        $server->setActionInvoker($this->createCounterActionInvoker());
        $server->setContinuationContextProvider($this->continuationContextProvider);

        $this->pageFlowID = 'Counter';
        $this->eventID = null;
        $this->pageFlowInstanceID = null;
        $server->activate(new \stdClass());
        $pageFlowInstance1 = $server->getPageFlowInstance();
        $server->shutdown();

        $this->pageFlowID = 'Counter';
        $this->eventID = null;
        $this->pageFlowInstanceID = null;
        $server->activate(new \stdClass());
        $pageFlowInstance2 = $server->getPageFlowInstance();
        $server->shutdown();

        $this->assertThat($pageFlowInstance2->getID(), $this->logicalNot($this->equalTo($pageFlowInstance1->getID())));
        $this->assertThat($server->getPageFlowInstanceRepository()->findByID($pageFlowInstance1->getID()), $this->isNull());
        $this->assertThat($server->getPageFlowInstanceRepository()->findByID($pageFlowInstance2->getID()), $this->logicalNot($this->isNull()));
    }

    /**
     * @since Method available since Release 2.0.0
     *
     * @test
     */
    public function startsPageFlowInstancesForANonExclusivePageFlow()
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
        $pageFlowInstance1 = $server->getPageFlowInstance();
        $server->shutdown();

        $this->pageFlowID = 'Counter';
        $this->eventID = null;
        $this->pageFlowInstanceID = null;
        $server->activate(new \stdClass());
        $pageFlowInstance2 = $server->getPageFlowInstance();
        $server->shutdown();

        $this->assertThat($pageFlowInstance2->getID(), $this->logicalNot($this->equalTo($pageFlowInstance1->getID())));
        $this->assertThat($server->getPageFlowInstanceRepository()->findByID($pageFlowInstance1->getID()), $this->logicalNot($this->isNull()));
        $this->assertThat($server->getPageFlowInstanceRepository()->findByID($pageFlowInstance2->getID()), $this->logicalNot($this->isNull()));
    }

    /**
     * @since Method available since Release 2.0.0
     *
     * @test
     */
    public function startsPageFlowInstancesForMultiplePageFlows()
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
        $server->shutdown();

        $this->pageFlowID = 'SecondCounter';
        $this->eventID = null;
        $this->pageFlowInstanceID = null;
        $server->activate(new \stdClass());
        $pageFlowInstance2 = $server->getPageFlowInstance();
        $server->shutdown();

        $this->assertThat($pageFlowInstance2->getID(), $this->logicalNot($this->equalTo($pageFlowInstance1->getID())));
        $this->assertThat($server->getPageFlowInstanceRepository()->findByID($pageFlowInstance1->getID()), $this->logicalNot($this->isNull()));
        $this->assertThat($server->getPageFlowInstanceRepository()->findByID($pageFlowInstance2->getID()), $this->logicalNot($this->isNull()));
    }

    /**
     * @since Method available since Release 2.0.0
     *
     * @test
     */
    public function continuesAPageFlowInstance()
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
        $pageFlowInstance1 = $server->getPageFlowInstance();
        $server->shutdown();

        $this->pageFlowID = 'Counter';
        $this->eventID = null;
        $this->pageFlowInstanceID = $pageFlowInstance1->getID();
        $server->activate(new \stdClass());
        $pageFlowInstance2 = $server->getPageFlowInstance();
        $server->shutdown();

        $this->assertThat($pageFlowInstance2->getID(), ($this->equalTo($pageFlowInstance1->getID())));
        $this->assertThat($pageFlowInstance2->getAttributes()->get('counter'), $this->equalTo(2));
    }

    /**
     * @test
     * @expectedException \Piece\Flow\Continuation\UnexpectedPageFlowIDException
     */
    public function raisesAnExceptionWhenAnUnexpectedPageFlowIdIsSpecifiedForTheSecondTimeOrLater()
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
        $pageFlowInstance = $server->getPageFlowInstance();
        $server->shutdown();

        $this->pageFlowID = 'SecondCounter';
        $this->eventID = null;
        $this->pageFlowInstanceID = $pageFlowInstance->getID();
        $server->activate(new \stdClass());
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
        $pageFlowInstanceRepository = new PageFlowInstanceRepository(new PageFlowRepository(new PageFlowRegistry($this->cacheDirectory, '.yaml'), $this->cacheDirectory, true));
        $pageFlowInstanceRepository->addPageFlow('Counter', $exclusive);
        $server = new ContinuationServer($pageFlowInstanceRepository);
        $server->setActionInvoker($this->createCounterActionInvoker());
        $server->setContinuationContextProvider($this->continuationContextProvider);

        $this->pageFlowID = 'Counter';
        $this->eventID = null;
        $this->pageFlowInstanceID = null;
        $server->activate(new \stdClass());
        $server->shutdown();

        $this->assertThat($server->getPageFlowInstanceRepository()->findByPageFlowID('Counter'), $exclusive ? $this->identicalTo($server->getPageFlowInstance()) : $this->isNull());
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
        $pageFlowInstanceRepository = new PageFlowInstanceRepository(new PageFlowRepository(new PageFlowRegistry($this->cacheDirectory, '.yaml'), $this->cacheDirectory, true));
        $pageFlowInstanceRepository->addPageFlow('Counter', false);
        $server = new ContinuationServer($pageFlowInstanceRepository, new GC($expirationTime, $clock));
        $server->setActionInvoker(\Phake::mock('Piece\Flow\PageFlow\ActionInvoker'));
        $server->setContinuationContextProvider($this->continuationContextProvider);

        $this->pageFlowID = 'Counter';
        $this->eventID = null;
        $this->pageFlowInstanceID = null;
        $server->activate(new \stdClass());
        $pageFlowInstance = $server->getPageFlowInstance();
        $server->shutdown();

        $this->pageFlowID = 'Counter';
        $this->eventID = null;
        $this->pageFlowInstanceID = $pageFlowInstance->getID();

        if ($shouldRaiseException) {
            try {
                $server->activate(new \stdClass());
                $server->shutdown();
                $this->fail('An expected exception has not been raised.');
            } catch (PageFlowInstanceExpiredException $e) {
            }
        } else {
            $server->activate(new \stdClass());
            $server->shutdown();
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
        $pageFlowInstanceRepository = new PageFlowInstanceRepository(new PageFlowRepository(new PageFlowRegistry($this->cacheDirectory, '.yaml'), $this->cacheDirectory, true));
        $pageFlowInstanceRepository->addPageFlow('CheckLastEvent', false);
        $server = new ContinuationServer($pageFlowInstanceRepository);
        $server->setActionInvoker($this->createCounterActionInvoker());
        $server->setContinuationContextProvider($this->continuationContextProvider);

        $this->pageFlowID = 'CheckLastEvent';
        $this->eventID = 'nonExistingEvent';
        $this->pageFlowInstanceID = null;
        $server->activate(new \stdClass());
        $pageFlowInstance = $server->getPageFlowInstance();
        $server->shutdown();

        $this->assertThat($pageFlowInstance->validateReceivedEvent(), $this->isTrue());

        $this->pageFlowID = 'CheckLastEvent';
        $this->eventID = 'DisplayEditConfirmFromDisplayEdit';
        $this->pageFlowInstanceID = $pageFlowInstance->getID();
        $server->activate(new \stdClass());
        $pageFlowInstance = $server->getPageFlowInstance();
        $server->shutdown();

        $this->assertThat($pageFlowInstance->validateReceivedEvent(), $this->isTrue());

        $this->pageFlowID = 'CheckLastEvent';
        $this->eventID = 'nonExistingEvent';
        $this->pageFlowInstanceID = $pageFlowInstance->getID();
        $server->activate(new \stdClass());
        $pageFlowInstance = $server->getPageFlowInstance();
        $server->shutdown();

        $this->assertThat($pageFlowInstance->validateReceivedEvent(), $this->isFalse());
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
