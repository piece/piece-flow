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
    }

    public function testSettingFlowInMultipleFlowMode()
    {
        $server = new ContinuationServer();
        $server->setCacheDirectory($this->cacheDirectory);
        $server->addFlow('foo', '/path/to/foo.flow');
        $server->addFlow('bar', '/path/to/bar.flow');
    }

    public function testInvocationInMultipleFlowModeAndFlowInNonExclusiveMode()
    {
        $server = new ContinuationServer();
        $server->setCacheDirectory($this->cacheDirectory);
        $server->addFlow('Counter', "{$this->cacheDirectory}/Counter.yaml");
        $server->setEventNameCallback(array($this, 'getEventName'));
        $server->setFlowExecutionTicketCallback(array($this, 'getFlowExecutionTicket'));
        $server->setFlowIDCallback(array($this, 'getFlowID'));
        $actionInvoker = $this->createCounterActionInvoker();
        $server->setActionInvoker($actionInvoker);

        $this->flowID = 'Counter';
        $this->eventName = null;
        $this->flowExecutionTicket = null;
        $flowExecutionTicket1 = $server->invoke(new \stdClass());
        $server->shutdown();

        $this->flowID = 'Counter';
        $this->eventName = 'increase';
        $this->flowExecutionTicket = $flowExecutionTicket1;
        $flowExecutionTicket2 = $server->invoke(new \stdClass());
        $service = $server->createService();

        $this->assertRegexp('/[0-9a-f]{40}/', $flowExecutionTicket1);
        $this->assertEquals('Counter', $server->getView());
        $this->assertEquals(1, $service->getAttribute('counter'));
        $this->assertEquals($flowExecutionTicket1, $flowExecutionTicket2);
    }

    public function testMultipleInvocationInMultipleFlowModeAndFlowInNonExclusiveMode()
    {
        $server = new ContinuationServer();
        $server->setCacheDirectory($this->cacheDirectory);
        $server->addFlow('Counter', "{$this->cacheDirectory}/Counter.yaml");
        $server->addFlow('SecondCounter', "{$this->cacheDirectory}/SecondCounter.yaml");
        $server->setEventNameCallback(array($this, 'getEventName'));
        $server->setFlowExecutionTicketCallback(array($this, 'getFlowExecutionTicket'));
        $server->setFlowIDCallback(array($this, 'getFlowID'));
        $server->setActionInvoker($this->createCounterActionInvoker());

        /*
         * Starting a new 'Counter'.
         */
        $this->flowID = 'Counter';
        $this->eventName = null;
        $this->flowExecutionTicket = null;
        $flowExecutionTicket1 = $server->invoke(new \stdClass());
        $service = $server->createService();

        $this->assertEquals(0, $service->getAttribute('counter'));

        $server->shutdown();

        /*
         * Starting a new 'SecondCounter'.
         */
        $this->flowID = 'SecondCounter';
        $this->eventName = null;
        $this->flowExecutionTicket = null;
        $flowExecutionTicket2 = $server->invoke(new \stdClass());
        $service = $server->createService();

        $this->assertEquals(0, $service->getAttribute('counter'));
        $this->assertRegexp('/[0-9a-f]{40}/', $flowExecutionTicket1);
        $this->assertRegexp('/[0-9a-f]{40}/', $flowExecutionTicket2);
        $this->assertEquals('SecondCounter', $server->getView());
        $this->assertTrue($flowExecutionTicket1 != $flowExecutionTicket2);

        $server->shutdown();

        /*
         * Continuing the first 'Counter'.
         */
        $this->flowID = 'Counter';
        $this->eventName = 'increase';
        $this->flowExecutionTicket = $flowExecutionTicket1;
        $flowExecutionTicket3 = $server->invoke(new \stdClass());
        $service = $server->createService();

        $this->assertEquals(1, $service->getAttribute('counter'));

        $this->assertEquals('Counter', $server->getView());
        $this->assertEquals($flowExecutionTicket1, $flowExecutionTicket3);

        $server->shutdown();

        /*
         * Continuing the first 'SecondCounter'.
         */
        $this->flowID = 'SecondCounter';
        $this->eventName = 'increase';
        $this->flowExecutionTicket = $flowExecutionTicket2;
        $flowExecutionTicket4 = $server->invoke(new \stdClass());
        $service = $server->createService();

        $this->assertEquals('SecondCounter', $server->getView());
        $this->assertEquals(1, $service->getAttribute('counter'));
        $this->assertEquals($flowExecutionTicket2, $flowExecutionTicket4);

        $server->shutdown();

        /*
         * Starting a new 'SecondCounter'.
         */
        $this->flowID = 'SecondCounter';
        $this->eventName = null;
        $this->flowExecutionTicket = null;
        $flowExecutionTicket5 = $server->invoke(new \stdClass());
        $service = $server->createService();

        $this->assertEquals('SecondCounter', $server->getView());
        $this->assertEquals(0, $service->getAttribute('counter'));
        $this->assertTrue($flowExecutionTicket2 != $flowExecutionTicket5);
    }

    /**
     * @expectedException \Piece\Flow\Continuation\InvalidFlowIDException
     */
    public function testFailureOfContinuationByInvalidFlowNameInMultipleFlowMode()
    {
        $server = new ContinuationServer();
        $server->setCacheDirectory($this->cacheDirectory);
        $server->addFlow('Counter', "{$this->cacheDirectory}/Counter.yaml");
        $server->setEventNameCallback(array($this, 'getEventName'));
        $server->setFlowExecutionTicketCallback(array($this, 'getFlowExecutionTicket'));
        $server->setFlowIDCallback(array($this, 'getFlowID'));
        $server->setActionInvoker($this->createCounterActionInvoker());

        $this->flowID = 'Counter';
        $this->eventName = null;
        $this->flowExecutionTicket = null;
        $flowExecutionTicket = $server->invoke(new \stdClass());
        $server->shutdown();

        $this->flowID = 'InvalidFlowName';
        $this->eventName = 'increase';
        $this->flowExecutionTicket = $flowExecutionTicket;
        $server->invoke(new \stdClass());
    }

    /**
     * @expectedException \Piece\Flow\PageFlow\FileNotFoundException
     */
    public function testFailureToInvokeByNonExistingFlowConfiguration()
    {
        $server = new ContinuationServer();
        $server->setCacheDirectory($this->cacheDirectory);
        $server->addFlow('NonExistingFile', "{$this->cacheDirectory}/NonExistingFile.yaml");
        $server->setEventNameCallback(array($this, 'getEventName'));
        $server->setFlowExecutionTicketCallback(array($this, 'getFlowExecutionTicket'));
        $server->setFlowIDCallback(array($this, 'getFlowID'));
        $server->setActionInvoker(\Phake::mock('Piece\Flow\PageFlow\ActionInvoker'));

        $this->flowID = 'NonExistingFile';
        $this->eventName = null;
        $this->flowExecutionTicket = null;
        $server->invoke(new \stdClass());
    }

    public function testInvocationInMultipleFlowModeAndFlowInExclusiveMode()
    {
        $server = new ContinuationServer();
        $server->setCacheDirectory($this->cacheDirectory);
        $server->addFlow('Counter', "{$this->cacheDirectory}/Counter.yaml", true);
        $server->addFlow('SecondCounter', "{$this->cacheDirectory}/SecondCounter.yaml");
        $server->setEventNameCallback(array($this, 'getEventName'));
        $server->setFlowExecutionTicketCallback(array($this, 'getFlowExecutionTicket'));
        $server->setFlowIDCallback(array($this, 'getFlowID'));
        $server->setActionInvoker($this->createCounterActionInvoker());

        $this->flowID = 'Counter';
        $this->eventName = null;
        $this->flowExecutionTicket = null;
        $flowExecutionTicket1 = $server->invoke(new \stdClass());
        $service = $server->createService();

        $this->assertEquals(0, $service->getAttribute('counter'));

        $server->shutdown();

        $this->flowID = 'SecondCounter';
        $this->eventName = null;
        $this->flowExecutionTicket = null;
        $flowExecutionTicket3 = $server->invoke(new \stdClass());
        $service = $server->createService();

        $this->assertEquals(0, $service->getAttribute('counter'));

        $server->shutdown();

        $this->flowID = 'Counter';
        $this->eventName = 'increase';
        $this->flowExecutionTicket = $flowExecutionTicket1;
        $flowExecutionTicket2 = $server->invoke(new \stdClass());
        $service = $server->createService();

        $this->assertEquals(1, $service->getAttribute('counter'));

        $this->assertRegexp('/[0-9a-f]{40}/', $flowExecutionTicket1);
        $this->assertRegexp('/[0-9a-f]{40}/', $flowExecutionTicket3);
        $this->assertEquals('Counter', $server->getView());
        $this->assertEquals($flowExecutionTicket1, $flowExecutionTicket2);
        $this->assertTrue($flowExecutionTicket1 != $flowExecutionTicket3);
    }

    public function testSettingAttribute()
    {
        $server = new ContinuationServer();
        $server->setCacheDirectory($this->cacheDirectory);
        $server->addFlow('Counter', "{$this->cacheDirectory}/Counter.yaml", true);
        $server->setEventNameCallback(array($this, 'getEventName'));
        $server->setFlowExecutionTicketCallback(array($this, 'getFlowExecutionTicket'));
        $server->setFlowIDCallback(array($this, 'getFlowID'));
        $server->setActionInvoker($this->createCounterActionInvoker());

        $this->flowID = 'Counter';
        $this->eventName = null;
        $this->flowExecutionTicket = null;
        $flowExecutionTicket = $server->invoke(new \stdClass());
        $service = $server->createService();
        $service->setAttribute('foo', 'bar');
        $server->shutdown();

        $this->flowID = 'Counter';
        $this->eventName = 'increase';
        $this->flowExecutionTicket = $flowExecutionTicket;
        $server->invoke(new \stdClass());
        $service = $server->createService();
        $service->setAttribute('bar', 'baz');
        $baz1 = new \stdClass();
        $service->setAttribute('baz', $baz1);
        $server->shutdown();

        $this->flowID = 'Counter';
        $this->eventName = 'increase';
        $this->flowExecutionTicket = $flowExecutionTicket;
        $server->invoke(new \stdClass());
        $service = $server->createService();

        $this->assertTrue($service->hasAttribute('foo'));
        $this->assertEquals('bar', $service->getAttribute('foo'));
        $this->assertTrue($service->hasAttribute('bar'));
        $this->assertEquals('baz', $service->getAttribute('bar'));

        $baz1->foo = 'bar';

        $this->assertTrue(property_exists($baz1, 'foo'));
        $this->assertEquals('bar', $baz1->foo);

        $baz2 = $service->getAttribute('baz');

        $this->assertEquals(strtolower('stdClass'), strtolower(get_class($baz2)));

        $this->assertTrue(property_exists($baz2, 'foo'));
        $this->assertEquals('bar', $baz2->foo);
    }

    /**
     * @expectedException \Piece\Flow\Core\MethodInvocationException
     */
    public function testFailureToSetAttributeBeforeStartingContinuation()
    {
        $server = new ContinuationServer();
        $server->setCacheDirectory($this->cacheDirectory);
        $server->addFlow('Counter', "{$this->cacheDirectory}/Counter.yaml", true);
        $server->setEventNameCallback(array($this, 'getEventName'));
        $server->setFlowExecutionTicketCallback(array($this, 'getFlowExecutionTicket'));
        $server->setFlowIDCallback(array($this, 'getFlowID'));
        $service = $server->createService();
        $service->setAttribute('foo', 'bar');
    }

    /**
     * @expectedException \Piece\Flow\Core\MethodInvocationException
     */
    public function testFailureToGetAttributeBeforeStartingContinuation()
    {
        $server = new ContinuationServer();
        $server->setCacheDirectory($this->cacheDirectory);
        $server->addFlow('Counter', "{$this->cacheDirectory}/Counter.yaml", true);
        $server->setEventNameCallback(array($this, 'getEventName'));
        $server->setFlowExecutionTicketCallback(array($this, 'getFlowExecutionTicket'));
        $server->setFlowIDCallback(array($this, 'getFlowID'));
        $service = $server->createService();
        $service->getAttribute('foo');
    }

    public function testStartingNewFlowExecutionAfterShuttingDownContinuationInNonExclusiveMode()
    {
        $shutdownCount = 0;
        $actionInvoker = \Phake::mock('Piece\Flow\PageFlow\ActionInvoker');
        \Phake::when($actionInvoker)->invoke('finalize', $this->anything())->thenGetReturnByLambda(function ($actionID, EventContext $eventContext) use (&$shutdownCount) {
            ++$shutdownCount;
        });
        $server = new ContinuationServer();
        $server->setCacheDirectory($this->cacheDirectory);
        $server->addFlow('Shutdown', "{$this->cacheDirectory}/Shutdown.yaml");
        $server->setEventNameCallback(array($this, 'getEventName'));
        $server->setFlowExecutionTicketCallback(array($this, 'getFlowExecutionTicket'));
        $server->setFlowIDCallback(array($this, 'getFlowID'));
        $server->setActionInvoker($actionInvoker);

        /*
         * Starting a new 'Shutdown'.
         */
        $this->flowID = 'Shutdown';
        $this->eventName = null;
        $this->flowExecutionTicket = null;
        $flowExecutionTicket1 = $server->invoke(new \stdClass());
        $server->shutdown();

        $this->flowID = 'Shutdown';
        $this->eventName = 'go';
        $this->flowExecutionTicket = $flowExecutionTicket1;
        $flowExecutionTicket2 = $server->invoke(new \stdClass());

        $this->assertEquals(1, $shutdownCount);
        $this->assertEquals($flowExecutionTicket1, $flowExecutionTicket2);

        $server->shutdown();

        /*
         * Failure to continue the 'Shutdown' from the previous flow
         * execution ticket.
         */
        $this->flowID = null;
        $this->eventName = 'go';
        $this->flowExecutionTicket = $flowExecutionTicket1;

        try {
            $server->invoke(new \stdClass());
            $this->fail('An expected exception has not been raised.');
        } catch (FlowIDRequiredException $e) {
        }
    }

    public function testStartingNewFlowExecutionAfterShuttingDownContinuationInExclusiveMode()
    {
        $shutdownCount = 0;
        $actionInvoker = \Phake::mock('Piece\Flow\PageFlow\ActionInvoker');
        \Phake::when($actionInvoker)->invoke('finalize', $this->anything())->thenGetReturnByLambda(function ($actionID, EventContext $eventContext) use (&$shutdownCount) {
            ++$shutdownCount;
        });
        $server = new ContinuationServer();
        $server->setCacheDirectory($this->cacheDirectory);
        $server->addFlow('Shutdown', "{$this->cacheDirectory}/Shutdown.yaml", true);
        $server->setEventNameCallback(array($this, 'getEventName'));
        $server->setFlowExecutionTicketCallback(array($this, 'getFlowExecutionTicket'));
        $server->setFlowIDCallback(array($this, 'getFlowID'));
        $server->setActionInvoker($actionInvoker);

        /*
         * Starting a new 'Shutdown'.
         */
        $this->flowID = 'Shutdown';
        $this->eventName = null;
        $this->flowExecutionTicket = null;
        $flowExecutionTicket1 = $server->invoke(new \stdClass());
        $server->shutdown();

        $this->flowID = 'Shutdown';
        $this->eventName = 'go';
        $this->flowExecutionTicket = $flowExecutionTicket1;
        $flowExecutionTicket2 = $server->invoke(new \stdClass());
        $server->shutdown();

        $this->assertEquals(1, $shutdownCount);
        $this->assertEquals($flowExecutionTicket1, $flowExecutionTicket2);
        $this->assertRegexp('/[0-9a-f]{40}/', $flowExecutionTicket1);

        /*
         * Failure to continue the 'Shutdown' from the previous flow
         * execution ticket. And starting a new 'Shutdown'.
         */
        $this->flowID = 'Shutdown';
        $this->eventName = 'go';
        $this->flowExecutionTicket = $flowExecutionTicket1;
        $flowExecutionTicket3 = $server->invoke(new \stdClass());

        $this->assertTrue($flowExecutionTicket1 != $flowExecutionTicket3);
        $this->assertRegexp('/[0-9a-f]{40}/', $flowExecutionTicket3);
    }

    /**
     * @since Method available since Release 1.7.0
     */
    public function testShouldBeRequiredFlowExecutionTicketWheneverContinuingFlowExecution()
    {
        $server = new ContinuationServer();
        $server->setCacheDirectory($this->cacheDirectory);
        $server->addFlow('Counter', "{$this->cacheDirectory}/Counter.yaml", true);
        $server->setEventNameCallback(array($this, 'getEventName'));
        $server->setFlowExecutionTicketCallback(array($this, 'getFlowExecutionTicket'));
        $server->setFlowIDCallback(array($this, 'getFlowID'));
        $server->setActionInvoker($this->createCounterActionInvoker());

        $this->flowID = 'Counter';
        $this->eventName = null;
        $this->flowExecutionTicket = null;
        $flowExecutionTicket1 = $server->invoke(new \stdClass());
        $service = $server->createService();

        $this->assertEquals(0, $service->getAttribute('counter'));

        $server->shutdown();

        $this->flowID = 'Counter';
        $this->eventName = null;
        $this->flowExecutionTicket = null;
        $hasWarnings = false;
        set_error_handler(function ($code, $message, $file, $line) use (&$hasWarnings) {
            if ($code == E_USER_WARNING) {
                $hasWarnings = true;
            }
        });
        $flowExecutionTicket2 = $server->invoke(new \stdClass());
        restore_error_handler();
        $service = $server->createService();

        $this->assertTrue($hasWarnings);
        $this->assertEquals(0, $service->getAttribute('counter'));
        $this->assertTrue($flowExecutionTicket1 != $flowExecutionTicket2);
    }

    /**
     * @since Method available since Release 1.7.0
     */
    public function testGettingFlowExecutionTicketByFlowName()
    {
        $server = new ContinuationServer();
        $server->setCacheDirectory($this->cacheDirectory);
        $server->addFlow('Counter', "{$this->cacheDirectory}/Counter.yaml", true);
        $server->addFlow('SecondCounter', "{$this->cacheDirectory}/SecondCounter.yaml");
        $server->setEventNameCallback(array($this, 'getEventName'));
        $server->setFlowExecutionTicketCallback(array($this, 'getFlowExecutionTicket'));
        $server->setFlowIDCallback(array($this, 'getFlowID'));
        $server->setActionInvoker($this->createCounterActionInvoker());

        $this->flowID = 'Counter';
        $this->eventName = null;
        $this->flowExecutionTicket = null;
        $flowExecutionTicket1 = $server->invoke(new \stdClass());

        $this->flowID = 'Counter';
        $this->eventName = 'increase';
        $this->flowExecutionTicket = $flowExecutionTicket1;
        $server->invoke(new \stdClass());
        $service = $server->createService();

        $this->assertEquals(1, $service->getAttribute('counter'));

        $this->flowID = 'SecondCounter';
        $this->eventName = null;
        $this->flowExecutionTicket = null;
        $flowExecutionTicket2 = $server->invoke(new \stdClass());
        $service = $server->createService();

        $this->assertEquals(0, $service->getAttribute('counter'));
        $this->assertFalse($flowExecutionTicket1 == $flowExecutionTicket2);
        $this->assertEquals($flowExecutionTicket1, $service->getFlowExecutionTicketByFlowID('Counter'));
        $this->assertNull($service->getFlowExecutionTicketByFlowID('SecondCounter'));
    }

    /**
     * @expectedException \Piece\Flow\Continuation\FlowExecutionExpiredException
     * @since Method available since Release 1.11.0
     */
    public function testFlowExecutionExpiredExceptionShouldBeRaisedWhenFlowExecutionHasExpired()
    {
        $flowName = 'FlowExecutionExpired';
        $server = new ContinuationServer(true, 1);
        $server->setCacheDirectory($this->cacheDirectory);
        $server->addFlow($flowName, "{$this->cacheDirectory}/$flowName.yaml");
        $server->setEventNameCallback(array($this, 'getEventName'));
        $server->setFlowExecutionTicketCallback(array($this, 'getFlowExecutionTicket'));
        $server->setFlowIDCallback(array($this, 'getFlowID'));
        $server->setActionInvoker(\Phake::mock('Piece\Flow\PageFlow\ActionInvoker'));

        $this->flowID = $flowName;
        $this->eventName = null;
        $this->flowExecutionTicket = null;
        $flowExecutionTicket = $server->invoke(new \stdClass());
        $server->shutdown();

        sleep(2);

        $this->flowID = $flowName;
        $this->eventName = null;
        $this->flowExecutionTicket = $flowExecutionTicket;
        $server->invoke(new \stdClass());
    }

    /**
     * @since Method available since Release 1.11.0
     */
    public function testFlowExecutionExpiredExceptionShouldNotBeRaisedWhenFlowExecutionHasNotExpired()
    {
        $flowName = 'FlowExecutionExpired';
        $server = new ContinuationServer(true, 2);
        $server->setCacheDirectory($this->cacheDirectory);
        $server->addFlow($flowName, "{$this->cacheDirectory}/$flowName.yaml");
        $server->setEventNameCallback(array($this, 'getEventName'));
        $server->setFlowExecutionTicketCallback(array($this, 'getFlowExecutionTicket'));
        $server->setFlowIDCallback(array($this, 'getFlowID'));
        $server->setActionInvoker(\Phake::mock('Piece\Flow\PageFlow\ActionInvoker'));

        $this->flowID = $flowName;
        $this->eventName = null;
        $this->flowExecutionTicket = null;
        $flowExecutionTicket = $server->invoke(new \stdClass());
        $server->shutdown();

        sleep(1);

        $this->flowID = $flowName;
        $this->eventName = null;
        $this->flowExecutionTicket = $flowExecutionTicket;
        $server->invoke(new \stdClass());

        sleep(1);

        $this->flowID = $flowName;
        $this->eventName = null;
        $this->flowExecutionTicket = $flowExecutionTicket;
        $server->invoke(new \stdClass());

        sleep(1);

        $this->flowID = $flowName;
        $this->eventName = null;
        $this->flowExecutionTicket = $flowExecutionTicket;
        $server->invoke(new \stdClass());
    }

    /**
     * @since Method available since Release 1.11.0
     */
    public function testNewFlowExecutionShouldBeAbleToStartWithSameRequestAfterFlowExecutionIsExpired()
    {
        $flowName = 'FlowExecutionExpired';
        $this->flowID = $flowName;
        $server = new ContinuationServer(true, 1);
        $server->setCacheDirectory($this->cacheDirectory);
        $server->addFlow($flowName, "{$this->cacheDirectory}/$flowName.yaml");
        $server->setEventNameCallback(array($this, 'getEventName'));
        $server->setFlowExecutionTicketCallback(array($this, 'getFlowExecutionTicket'));
        $server->setFlowIDCallback(array($this, 'getFlowID'));
        $server->setActionInvoker(\Phake::mock('Piece\Flow\PageFlow\ActionInvoker'));

        $this->flowID = $flowName;
        $this->eventName = null;
        $this->flowExecutionTicket = null;
        $flowExecutionTicket = $server->invoke(new \stdClass());
        $server->shutdown();

        sleep(2);

        $this->flowID = $flowName;
        $this->eventName = null;
        $this->flowExecutionTicket = $flowExecutionTicket;

        try {
            $server->invoke(new \stdClass());
            $this->fail('An expected exception has not been raised.');
        } catch (FlowExecutionExpiredException $e) {
        }

        $server->shutdown();

        $this->flowID = $flowName;
        $this->eventName = null;
        $this->flowExecutionTicket = $flowExecutionTicket;
        $newFlowExecutionTicket = $server->invoke(new \stdClass());

        $this->assertTrue($newFlowExecutionTicket != $this->flowExecutionTicket);
    }

    /**
     * @since Method available since Release 1.13.0
     */
    public function testCheckLastEventShouldReturnTrueIfContinuationHasJustStarted()
    {
        $flowName = 'CheckLastEvent';
        $server = new ContinuationServer();
        $server->setCacheDirectory($this->cacheDirectory);
        $server->addFlow($flowName, "{$this->cacheDirectory}/$flowName.yaml");
        $server->setEventNameCallback(array($this, 'getEventName'));
        $server->setFlowExecutionTicketCallback(array($this, 'getFlowExecutionTicket'));
        $server->setFlowIDCallback(array($this, 'getFlowID'));
        $server->setActionInvoker(\Phake::mock('Piece\Flow\PageFlow\ActionInvoker'));

        $this->flowID = $flowName;
        $this->eventName = 'foo';
        $this->flowExecutionTicket = null;
        $server->invoke(new \stdClass());
        $service = $server->createService();

        $this->assertTrue($service->checkLastEvent());
    }

    /**
     * @since Method available since Release 1.13.0
     */
    public function testCheckLastEventShouldReturnTrueWhenValidEventIsGivenByUser()
    {
        $flowName = 'CheckLastEvent';
        $server = new ContinuationServer();
        $server->setCacheDirectory($this->cacheDirectory);
        $server->addFlow($flowName, "{$this->cacheDirectory}/$flowName.yaml");
        $server->setEventNameCallback(array($this, 'getEventName'));
        $server->setFlowExecutionTicketCallback(array($this, 'getFlowExecutionTicket'));
        $server->setFlowIDCallback(array($this, 'getFlowID'));
        $server->setActionInvoker(\Phake::mock('Piece\Flow\PageFlow\ActionInvoker'));

        $this->flowID = $flowName;
        $this->eventName = null;
        $this->flowExecutionTicket = null;
        $flowExecutionTicket = $server->invoke(new \stdClass());
        $server->shutdown();

        $this->flowID = $flowName;
        $this->eventName = 'DisplayEditConfirmFromDisplayEdit';
        $this->flowExecutionTicket = $flowExecutionTicket;
        $server->invoke(new \stdClass());
        $service = $server->createService();

        $this->assertTrue($service->checkLastEvent());

        $server->shutdown();

        $this->flowID = $flowName;
        $this->eventName = 'DisplayEditFinishFromDisplayEditConfirm';
        $this->flowExecutionTicket = $flowExecutionTicket;
        $server->invoke(new \stdClass());
        $service = $server->createService();

        $this->assertTrue($service->checkLastEvent());
    }

    /**
     * @since Method available since Release 1.13.0
     */
    public function testCheckLastEventShouldReturnFalseWhenInvalidEventIsGivenByUser()
    {
        $flowName = 'CheckLastEvent';
        $server = new ContinuationServer();
        $server->setCacheDirectory($this->cacheDirectory);
        $server->addFlow($flowName, "{$this->cacheDirectory}/$flowName.yaml");
        $server->setEventNameCallback(array($this, 'getEventName'));
        $server->setFlowExecutionTicketCallback(array($this, 'getFlowExecutionTicket'));
        $server->setFlowIDCallback(array($this, 'getFlowID'));
        $server->setActionInvoker(\Phake::mock('Piece\Flow\PageFlow\ActionInvoker'));

        $this->flowID = $flowName;
        $this->eventName = null;
        $this->flowExecutionTicket = null;
        $flowExecutionTicket = $server->invoke(new \stdClass());
        $server->shutdown();

        $this->flowID = $flowName;
        $this->eventName = 'foo';
        $this->flowExecutionTicket = $flowExecutionTicket;
        $server->invoke(new \stdClass());
        $service = $server->createService();

        $this->assertFalse($service->checkLastEvent());
    }

    /**
     * @since Method available since Release 1.13.0
     */
    public function testCheckLastEventShouldReturnTrueIfContinuationHasNotActivatedYet()
    {
        $flowName = 'CheckLastEvent';
        $server = new ContinuationServer();
        $server->setCacheDirectory($this->cacheDirectory);
        $server->addFlow($flowName, "{$this->cacheDirectory}/$flowName.yaml");
        $server->setEventNameCallback(array($this, 'getEventName'));
        $server->setFlowExecutionTicketCallback(array($this, 'getFlowExecutionTicket'));
        $server->setFlowIDCallback(array($this, 'getFlowID'));

        $this->flowID = $flowName;
        $this->eventName = 'foo';
        $this->flowExecutionTicket = null;
        $service = $server->createService();

        $this->assertTrue($service->checkLastEvent());
    }

    /**
     * @since Method available since Release 1.14.0
     */
    public function testCurrentStateNameShouldBeAbleToGetIfContinuationHasActivated()
    {
        $flowName = 'CheckLastEvent';
        $server = new ContinuationServer();
        $server->setCacheDirectory($this->cacheDirectory);
        $server->addFlow($flowName, "{$this->cacheDirectory}/$flowName.yaml");
        $server->setEventNameCallback(array($this, 'getEventName'));
        $server->setFlowExecutionTicketCallback(array($this, 'getFlowExecutionTicket'));
        $server->setFlowIDCallback(array($this, 'getFlowID'));
        $server->setActionInvoker(\Phake::mock('Piece\Flow\PageFlow\ActionInvoker'));

        $this->flowID = $flowName;
        $this->eventName = null;
        $this->flowExecutionTicket = null;
        $flowExecutionTicket = $server->invoke(new \stdClass());
        $service = $server->createService();

        $this->assertEquals('DisplayEdit', $service->getCurrentStateName());

        $server->shutdown();

        $this->flowID = $flowName;
        $this->eventName = 'DisplayEditConfirmFromDisplayEdit';
        $this->flowExecutionTicket = $flowExecutionTicket;
        $server->invoke(new \stdClass());
        $service = $server->createService();

        $this->assertEquals('DisplayEditConfirm', $service->getCurrentStateName());

        $server->shutdown();

        $this->flowID = $flowName;
        $this->eventName = 'DisplayEditFinishFromDisplayEditConfirm';
        $this->flowExecutionTicket = $flowExecutionTicket;
        $server->invoke(new \stdClass());
        $service = $server->createService();

        $this->assertEquals('DisplayEditFinish', $service->getCurrentStateName());
    }

    /**
     * @expectedException \Piece\Flow\Core\MethodInvocationException
     * @since Method available since Release 1.14.0
     */
    public function testGetCurrentStateNameShouldRaiseExceptionIfContinuationHasNotActivated()
    {
        $flowName = 'CheckLastEvent';
        $server = new ContinuationServer();
        $server->setCacheDirectory($this->cacheDirectory);
        $server->addFlow($flowName, "{$this->cacheDirectory}/$flowName.yaml");
        $server->setEventNameCallback(array($this, 'getEventName'));
        $server->setFlowExecutionTicketCallback(array($this, 'getFlowExecutionTicket'));
        $server->setFlowIDCallback(array($this, 'getFlowID'));

        $this->flowID = $flowName;
        $this->eventName = null;
        $this->flowExecutionTicket = null;
        $service = $server->createService();
        $service->getCurrentStateName();
    }

    /**
     * @since Method available since Release 1.15.0
     */
    public function testFlowExecutionShouldWorkWithConfigDirectory()
    {
        $server = new ContinuationServer();
        $server->setCacheDirectory($this->cacheDirectory);
        $server->addFlow('/counter/one.php', $this->cacheDirectory . '/Counter/One.flow');
        $server->addFlow('/counter/two.php', $this->cacheDirectory . '/Counter/Two.flow');
        $server->setEventNameCallback(array($this, 'getEventName'));
        $server->setFlowExecutionTicketCallback(array($this, 'getFlowExecutionTicket'));
        $server->setFlowIDCallback(array($this, 'getFlowID'));
        $server->setActionInvoker($this->createCounterActionInvoker());

        /*
         * Starting a new '/counter/one.php'.
         */
        $this->flowID = '/counter/one.php';
        $this->eventName = null;
        $this->flowExecutionTicket = null;
        $flowExecutionTicket1 = $server->invoke(new \stdClass());
        $service = $server->createService();

        $this->assertEquals(0, $service->getAttribute('counter'));

        $server->shutdown();

        /*
         * Starting a new '/counter/two.php'.
         */
        $this->flowID = '/counter/two.php';
        $this->eventName = null;
        $this->flowExecutionTicket = null;
        $flowExecutionTicket2 = $server->invoke(new \stdClass());
        $service = $server->createService();

        $this->assertEquals(0, $service->getAttribute('counter'));
        $this->assertRegexp('/[0-9a-f]{40}/', $flowExecutionTicket1);
        $this->assertRegexp('/[0-9a-f]{40}/', $flowExecutionTicket2);
        $this->assertEquals('SecondCounter', $server->getView());
        $this->assertTrue($flowExecutionTicket1 != $flowExecutionTicket2);

        $server->shutdown();

        /*
         * Continuing the first '/counter/one.php'.
         */
        $this->flowID = '/counter/one.php';
        $this->eventName = 'increase';
        $this->flowExecutionTicket = $flowExecutionTicket1;
        $flowExecutionTicket3 = $server->invoke(new \stdClass());
        $service = $server->createService();

        $this->assertEquals(1, $service->getAttribute('counter'));

        $this->assertEquals('Counter', $server->getView());
        $this->assertEquals($flowExecutionTicket1, $flowExecutionTicket3);

        $server->shutdown();

        /*
         * Continuing the first '/counter/two.php'.
         */
        $this->flowID = '/counter/two.php';
        $this->eventName = 'increase';
        $this->flowExecutionTicket = $flowExecutionTicket2;
        $flowExecutionTicket4 = $server->invoke(new \stdClass());
        $service = $server->createService();

        $this->assertEquals('SecondCounter', $server->getView());
        $this->assertEquals(1, $service->getAttribute('counter'));
        $this->assertEquals($flowExecutionTicket2, $flowExecutionTicket4);

        $server->shutdown();

        /*
         * Starting a new '/counter/two.php'.
         */
        $this->flowID = '/counter/two.php';
        $this->eventName = null;
        $this->flowExecutionTicket = null;
        $flowExecutionTicket5 = $server->invoke(new \stdClass());
        $service = $server->createService();

        $this->assertEquals('SecondCounter', $server->getView());
        $this->assertEquals(0, $service->getAttribute('counter'));
        $this->assertTrue($flowExecutionTicket2 != $flowExecutionTicket5);
    }

    /**
     * @since Method available since Release 1.15.1
     */
    public function testFlowExecutionExpiredExceptionShouldRaiseAfterSweepingIt()
    {
        $flowName = 'FlowExecutionExpired';
        $server = new ContinuationServer(true, 1);
        $server->setCacheDirectory($this->cacheDirectory);
        $server->addFlow($flowName, "{$this->cacheDirectory}/$flowName.yaml");
        $server->setEventNameCallback(array($this, 'getEventName'));
        $server->setFlowExecutionTicketCallback(array($this, 'getFlowExecutionTicket'));
        $server->setFlowIDCallback(array($this, 'getFlowID'));
        $server->setActionInvoker(\Phake::mock('Piece\Flow\PageFlow\ActionInvoker'));

        $this->flowID = $flowName;
        $this->eventName = null;
        $this->flowExecutionTicket = null;
        $flowExecutionTicket1 = $server->invoke(new \stdClass());
        $server->shutdown();

        sleep(2);

        $this->flowID = $flowName;
        $this->eventName = null;
        $this->flowExecutionTicket = null;
        $flowExecutionTicket2 = $server->invoke(new \stdClass());
        $server->shutdown();

        $this->assertTrue($flowExecutionTicket1 != $flowExecutionTicket2);

        $this->flowID = $flowName;
        $this->eventName = null;
        $this->flowExecutionTicket = $flowExecutionTicket1;

        try {
            $server->invoke(new \stdClass());
            $this->fail('An expected exception has not been raised.');
        } catch (FlowExecutionExpiredException $e) {
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
