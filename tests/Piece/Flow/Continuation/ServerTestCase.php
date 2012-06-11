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

require_once 'Cache/Lite/File.php';

use Piece\Flow\Action\Factory;
use Piece\Flow\Util\ErrorReporting;

/**
 * @package    Piece_Flow
 * @copyright  2006-2008, 2012 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version    Release: @package_version@
 * @since      Class available since Release 1.14.0
 */
class ServerTestCase extends \PHPUnit_Framework_TestCase
{
    protected $cacheDirectory;

    public static function getFlowExecutionTicket()
    {
        return $GLOBALS['flowExecutionTicket'];
    }

    public static function getFlowID()
    {
        return $GLOBALS['flowID'];
    }

    public static function getEventName()
    {
        return $GLOBALS['eventName'];
    }

    protected function setUp()
    {
        $this->cacheDirectory = dirname(__FILE__) . '/' . basename(__FILE__, '.php');
        Factory::clearInstances();
        Factory::setActionDirectory(null);
        $GLOBALS['eventName'] = null;
        $GLOBALS['flowID'] = null;
        $GLOBALS['flowExecutionTicket'] = null;
    }

    protected function tearDown()
    {
        $cacheDirectory = $this->cacheDirectory;
        $cache = ErrorReporting::invokeWith(error_reporting() & ~E_STRICT, function () use ($cacheDirectory) {
            return new \Cache_Lite_File(array(
                'cacheDir' => $cacheDirectory . '/',
                'masterFile' => '',
                'automaticSerialization' => true,
                'errorHandlingAPIBreak' => true
            ));
        });
        $cache->clean();
    }

    public function testAddingFlowInSingleFlowMode()
    {
        $server = new Server(true);
        $server->setCacheDirectory($this->cacheDirectory);
        $server->addFlow('foo', '/path/to/foo.xml');
    }

    /**
     * @expectedException \Piece\Flow\Continuation\FlowDefinitionAlreadyExistsException
     */
    public function testFailureToAddFlowForSecondTimeInSingleFlowMode()
    {
        $server = new Server(true);
        $server->setCacheDirectory($this->cacheDirectory);
        $server->addFlow('foo', '/path/to/foo.xml');
        $server->addFlow('bar', '/path/to/bar.xml');
    }

    public function testSettingFlowInMultipleFlowMode()
    {
        $server = new Server();
        $server->setCacheDirectory($this->cacheDirectory);
        $server->addFlow('foo', '/path/to/foo.xml');
        $server->addFlow('bar', '/path/to/bar.xml');
    }

    public function testFirstTimeInvocationInSingleFlowMode()
    {
        $server = new Server(true);
        $server->setCacheDirectory($this->cacheDirectory);
        $server->addFlow('Counter', "{$this->cacheDirectory}/Counter.yaml");
        $server->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));
        $server->setActionDirectory($this->cacheDirectory);
        $flowExecutionTicket = $server->invoke(new \stdClass());
        $service = $server->createService();

        $this->assertRegexp('/[0-9a-f]{40}/', $flowExecutionTicket);
        $this->assertEquals('Counter', $server->getView());
        $this->assertEquals(0, $service->getAttribute('counter'));
    }

    public function testSecondTimeInvocationInSingleFlowMode()
    {
        $server = new Server(true);
        $server->setCacheDirectory($this->cacheDirectory);
        $server->addFlow('Counter', "{$this->cacheDirectory}/Counter.yaml");
        $server->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $server->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));
        $server->setActionDirectory($this->cacheDirectory);

        $GLOBALS['flowID'] = 'Counter';
        $GLOBALS['eventName'] = null;
        $GLOBALS['flowExecutionTicket'] = null;
        $flowExecutionTicket1 = $server->invoke(new \stdClass());
        $server->shutdown();

        $GLOBALS['flowID'] = 'Counter';
        $GLOBALS['eventName'] = 'increase';
        $GLOBALS['flowExecutionTicket'] = $flowExecutionTicket1;
        $flowExecutionTicket2 = $server->invoke(new \stdClass());
        $service = $server->createService();

        $this->assertRegexp('/[0-9a-f]{40}/', $flowExecutionTicket1);
        $this->assertEquals('Counter', $server->getView());
        $this->assertEquals(1, $service->getAttribute('counter'));
        $this->assertEquals($flowExecutionTicket1, $flowExecutionTicket2);
    }

    public function testInvocationInMultipleFlowModeAndFlowInNonExclusiveMode()
    {
        $server = new Server();
        $server->setCacheDirectory($this->cacheDirectory);
        $server->addFlow('Counter', "{$this->cacheDirectory}/Counter.yaml");
        $server->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $server->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));
        $server->setFlowIDCallback(array(__CLASS__, 'getFlowID'));
        $server->setActionDirectory($this->cacheDirectory);

        $GLOBALS['flowID'] = 'Counter';
        $GLOBALS['eventName'] = null;
        $GLOBALS['flowExecutionTicket'] = null;
        $flowExecutionTicket1 = $server->invoke(new \stdClass());
        $server->shutdown();

        $GLOBALS['flowID'] = 'Counter';
        $GLOBALS['eventName'] = 'increase';
        $GLOBALS['flowExecutionTicket'] = $flowExecutionTicket1;
        $flowExecutionTicket2 = $server->invoke(new \stdClass());
        $service = $server->createService();

        $this->assertRegexp('/[0-9a-f]{40}/', $flowExecutionTicket1);
        $this->assertEquals('Counter', $server->getView());
        $this->assertEquals(1, $service->getAttribute('counter'));
        $this->assertEquals($flowExecutionTicket1, $flowExecutionTicket2);
    }

    public function testMultipleInvocationInMultipleFlowModeAndFlowInNonExclusiveMode()
    {
        $server = new Server();
        $server->setCacheDirectory($this->cacheDirectory);
        $server->addFlow('Counter', "{$this->cacheDirectory}/Counter.yaml");
        $server->addFlow('SecondCounter', "{$this->cacheDirectory}/SecondCounter.yaml");
        $server->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $server->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));
        $server->setFlowIDCallback(array(__CLASS__, 'getFlowID'));
        $server->setActionDirectory($this->cacheDirectory);

        /*
         * Starting a new 'Counter'.
         */
        $GLOBALS['flowID'] = 'Counter';
        $GLOBALS['eventName'] = null;
        $GLOBALS['flowExecutionTicket'] = null;
        $flowExecutionTicket1 = $server->invoke(new \stdClass());
        $service = $server->createService();

        $this->assertEquals(0, $service->getAttribute('counter'));

        $server->shutdown();

        /*
         * Starting a new 'SecondCounter'.
         */
        $GLOBALS['flowID'] = 'SecondCounter';
        $GLOBALS['eventName'] = null;
        $GLOBALS['flowExecutionTicket'] = null;
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
        $GLOBALS['flowID'] = 'Counter';
        $GLOBALS['eventName'] = 'increase';
        $GLOBALS['flowExecutionTicket'] = $flowExecutionTicket1;
        $flowExecutionTicket3 = $server->invoke(new \stdClass());
        $service = $server->createService();

        $this->assertEquals(1, $service->getAttribute('counter'));

        $this->assertEquals('Counter', $server->getView());
        $this->assertEquals($flowExecutionTicket1, $flowExecutionTicket3);

        $server->shutdown();

        /*
         * Continuing the first 'SecondCounter'.
         */
        $GLOBALS['flowID'] = 'SecondCounter';
        $GLOBALS['eventName'] = 'increase';
        $GLOBALS['flowExecutionTicket'] = $flowExecutionTicket2;
        $flowExecutionTicket4 = $server->invoke(new \stdClass());
        $service = $server->createService();

        $this->assertEquals('SecondCounter', $server->getView());
        $this->assertEquals(1, $service->getAttribute('counter'));
        $this->assertEquals($flowExecutionTicket2, $flowExecutionTicket4);

        $server->shutdown();

        /*
         * Starting a new 'SecondCounter'.
         */
        $GLOBALS['flowID'] = 'SecondCounter';
        $GLOBALS['eventName'] = null;
        $GLOBALS['flowExecutionTicket'] = null;
        $flowExecutionTicket5 = $server->invoke(new \stdClass());
        $service = $server->createService();

        $this->assertEquals('SecondCounter', $server->getView());
        $this->assertEquals(0, $service->getAttribute('counter'));
        $this->assertTrue($flowExecutionTicket2 != $flowExecutionTicket5);
    }

    public function testSuccessOfContinuationByInvalidFlowNameInSingleFlowMode()
    {
        $server = new Server(true);
        $server->setCacheDirectory($this->cacheDirectory);
        $server->addFlow('Counter', "{$this->cacheDirectory}/Counter.yaml");
        $server->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $server->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));
        $server->setFlowIDCallback(array(__CLASS__, 'getFlowID'));
        $server->setActionDirectory($this->cacheDirectory);

        $GLOBALS['flowID'] = 'Counter';
        $GLOBALS['eventName'] = null;
        $GLOBALS['flowExecutionTicket'] = null;
        $flowExecutionTicket = $server->invoke(new \stdClass());
        $server->shutdown();

        $GLOBALS['flowID'] = 'InvalidFlowName';
        $GLOBALS['eventName'] = 'increase';
        $GLOBALS['flowExecutionTicket'] = $flowExecutionTicket;
        $server->invoke(new \stdClass());
        $service = $server->createService();

        $this->assertEquals(1, $service->getAttribute('counter'));
    }

    /**
     * @expectedException \Piece\Flow\Continuation\InvalidFlowIDException
     */
    public function testFailureOfContinuationByInvalidFlowNameInMultipleFlowMode()
    {
        $server = new Server();
        $server->setCacheDirectory($this->cacheDirectory);
        $server->addFlow('Counter', "{$this->cacheDirectory}/Counter.yaml");
        $server->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $server->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));
        $server->setFlowIDCallback(array(__CLASS__, 'getFlowID'));

        $GLOBALS['flowID'] = 'Counter';
        $GLOBALS['eventName'] = null;
        $GLOBALS['flowExecutionTicket'] = null;
        $flowExecutionTicket = $server->invoke(new \stdClass());
        $server->shutdown();

        $GLOBALS['flowID'] = 'InvalidFlowName';
        $GLOBALS['eventName'] = 'increase';
        $GLOBALS['flowExecutionTicket'] = $flowExecutionTicket;
        $server->invoke(new \stdClass());
    }

    /**
     * @expectedException \Piece\Flow\FileNotFoundException
     */
    public function testFailureToInvokeByNonExistingFlowConfiguration()
    {
        $server = new Server();
        $server->setCacheDirectory($this->cacheDirectory);
        $server->addFlow('NonExistingFile', "{$this->cacheDirectory}/NonExistingFile.yaml");
        $server->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $server->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));
        $server->setFlowIDCallback(array(__CLASS__, 'getFlowID'));

        $GLOBALS['flowID'] = 'NonExistingFile';
        $GLOBALS['eventName'] = null;
        $GLOBALS['flowExecutionTicket'] = null;
        $server->invoke(new \stdClass());
    }

    public function testInvocationInMultipleFlowModeAndFlowInExclusiveMode()
    {
        $server = new Server();
        $server->setCacheDirectory($this->cacheDirectory);
        $server->addFlow('Counter', "{$this->cacheDirectory}/Counter.yaml", true);
        $server->addFlow('SecondCounter', "{$this->cacheDirectory}/SecondCounter.yaml");
        $server->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $server->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));
        $server->setFlowIDCallback(array(__CLASS__, 'getFlowID'));
        $server->setActionDirectory($this->cacheDirectory);

        $GLOBALS['flowID'] = 'Counter';
        $GLOBALS['eventName'] = null;
        $GLOBALS['flowExecutionTicket'] = null;
        $flowExecutionTicket1 = $server->invoke(new \stdClass());
        $service = $server->createService();

        $this->assertEquals(0, $service->getAttribute('counter'));

        $server->shutdown();

        $GLOBALS['flowID'] = 'SecondCounter';
        $GLOBALS['eventName'] = null;
        $GLOBALS['flowExecutionTicket'] = null;
        $flowExecutionTicket3 = $server->invoke(new \stdClass());
        $service = $server->createService();

        $this->assertEquals(0, $service->getAttribute('counter'));

        $server->shutdown();

        $GLOBALS['flowID'] = 'Counter';
        $GLOBALS['eventName'] = 'increase';
        $GLOBALS['flowExecutionTicket'] = $flowExecutionTicket1;
        $flowExecutionTicket2 = $server->invoke(new \stdClass());
        $service = $server->createService();

        $this->assertEquals(1, $service->getAttribute('counter'));

        $this->assertRegexp('/[0-9a-f]{40}/', $flowExecutionTicket1);
        $this->assertRegexp('/[0-9a-f]{40}/', $flowExecutionTicket3);
        $this->assertEquals('Counter', $server->getView());
        $this->assertEquals($flowExecutionTicket1, $flowExecutionTicket2);
        $this->assertTrue($flowExecutionTicket1 != $flowExecutionTicket3);
    }

    public function testInvocationInSingleFlowModeAndFlowInExclusiveMode()
    {
        $server = new Server(true);
        $server->setCacheDirectory($this->cacheDirectory);
        $server->addFlow('Counter', "{$this->cacheDirectory}/Counter.yaml", true);
        $server->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $server->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));
        $server->setFlowIDCallback(array(__CLASS__, 'getFlowID'));
        $server->setActionDirectory($this->cacheDirectory);

        $GLOBALS['flowID'] = 'Counter';
        $GLOBALS['eventName'] = null;
        $GLOBALS['flowExecutionTicket'] = null;
        $flowExecutionTicket1 = $server->invoke(new \stdClass());
        $server->shutdown();

        $GLOBALS['flowID'] = 'Counter';
        $GLOBALS['eventName'] = 'increase';
        $GLOBALS['flowExecutionTicket'] = $flowExecutionTicket1;
        $flowExecutionTicket2 = $server->invoke(new \stdClass());
        $service = $server->createService();

        $this->assertRegexp('/[0-9a-f]{40}/', $flowExecutionTicket1);
        $this->assertEquals('Counter', $server->getView());
        $this->assertEquals(1, $service->getAttribute('counter'));
        $this->assertEquals($flowExecutionTicket1, $flowExecutionTicket2);
    }

    public function testSettingAttribute()
    {
        $server = new Server(true);
        $server->setCacheDirectory($this->cacheDirectory);
        $server->addFlow('Counter', "{$this->cacheDirectory}/Counter.yaml", true);
        $server->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $server->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));
        $server->setFlowIDCallback(array(__CLASS__, 'getFlowID'));
        $server->setActionDirectory($this->cacheDirectory);

        $GLOBALS['flowID'] = 'Counter';
        $GLOBALS['eventName'] = null;
        $GLOBALS['flowExecutionTicket'] = null;
        $flowExecutionTicket = $server->invoke(new \stdClass());
        $service = $server->createService();
        $service->setAttribute('foo', 'bar');
        $server->shutdown();

        $GLOBALS['flowID'] = 'Counter';
        $GLOBALS['eventName'] = 'increase';
        $GLOBALS['flowExecutionTicket'] = $flowExecutionTicket;
        $server->invoke(new \stdClass());
        $service = $server->createService();
        $service->setAttribute('bar', 'baz');
        $baz1 = new \stdClass();
        $service->setAttributeByRef('baz', $baz1);
        $server->shutdown();

        $GLOBALS['flowID'] = 'Counter';
        $GLOBALS['eventName'] = 'increase';
        $GLOBALS['flowExecutionTicket'] = $flowExecutionTicket;
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
     * @expectedException \Piece\Flow\MethodInvocationException
     */
    public function testFailureToSetAttributeBeforeStartingContinuation()
    {
        $server = new Server(true);
        $server->setCacheDirectory($this->cacheDirectory);
        $server->addFlow('Counter', "{$this->cacheDirectory}/Counter.yaml", true);
        $server->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $server->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));
        $server->setFlowIDCallback(array(__CLASS__, 'getFlowID'));
        $service = $server->createService();
        $service->setAttribute('foo', 'bar');
    }

    /**
     * @expectedException \Piece\Flow\MethodInvocationException
     */
    public function testFailureToGetAttributeBeforeStartingContinuation()
    {
        $server = new Server(true);
        $server->setCacheDirectory($this->cacheDirectory);
        $server->addFlow('Counter', "{$this->cacheDirectory}/Counter.yaml", true);
        $server->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $server->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));
        $server->setFlowIDCallback(array(__CLASS__, 'getFlowID'));
        $service = $server->createService();
        $service->getAttribute('foo');
    }

    public function testStartingNewFlowExecutionAfterShuttingDownContinuationInNonExclusiveMode()
    {
        $GLOBALS['ShutdownCount'] = 0;

        $server = new Server();
        $server->setCacheDirectory($this->cacheDirectory);
        $server->addFlow('Shutdown', "{$this->cacheDirectory}/Shutdown.yaml");
        $server->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $server->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));
        $server->setFlowIDCallback(array(__CLASS__, 'getFlowID'));
        $server->setActionDirectory($this->cacheDirectory);

        /*
         * Starting a new 'Shutdown'.
         */
        $GLOBALS['flowID'] = 'Shutdown';
        $GLOBALS['eventName'] = null;
        $GLOBALS['flowExecutionTicket'] = null;
        $flowExecutionTicket1 = $server->invoke(new \stdClass());
        $server->shutdown();

        $GLOBALS['flowID'] = 'Shutdown';
        $GLOBALS['eventName'] = 'go';
        $GLOBALS['flowExecutionTicket'] = $flowExecutionTicket1;
        $flowExecutionTicket2 = $server->invoke(new \stdClass());

        $this->assertEquals(1, $GLOBALS['ShutdownCount']);
        $this->assertEquals($flowExecutionTicket1, $flowExecutionTicket2);

        $server->shutdown();

        /*
         * Failure to continue the 'Shutdown' from the previous flow
         * execution ticket.
         */
        $GLOBALS['flowID'] = null;
        $GLOBALS['eventName'] = 'go';
        $GLOBALS['flowExecutionTicket'] = $flowExecutionTicket1;

        try {
            $server->invoke(new \stdClass());
            $this->fail('An expected exception has not been raised.');
        } catch (FlowIDRequiredException $e) {
        }
    }

    public function testStartingNewFlowExecutionAfterShuttingDownContinuationInExclusiveMode()
    {
        $GLOBALS['ShutdownCount'] = 0;

        $server = new Server();
        $server->setCacheDirectory($this->cacheDirectory);
        $server->addFlow('Shutdown', "{$this->cacheDirectory}/Shutdown.yaml", true);
        $server->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $server->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));
        $server->setFlowIDCallback(array(__CLASS__, 'getFlowID'));

        /*
         * Starting a new 'Shutdown'.
         */
        $GLOBALS['flowID'] = 'Shutdown';
        $GLOBALS['eventName'] = null;
        $GLOBALS['flowExecutionTicket'] = null;
        $flowExecutionTicket1 = $server->invoke(new \stdClass());
        $server->shutdown();

        $GLOBALS['flowID'] = 'Shutdown';
        $GLOBALS['eventName'] = 'go';
        $GLOBALS['flowExecutionTicket'] = $flowExecutionTicket1;
        $flowExecutionTicket2 = $server->invoke(new \stdClass());
        $server->shutdown();

        $this->assertEquals(1, $GLOBALS['ShutdownCount']);
        $this->assertEquals($flowExecutionTicket1, $flowExecutionTicket2);
        $this->assertRegexp('/[0-9a-f]{40}/', $flowExecutionTicket1);

        /*
         * Failure to continue the 'Shutdown' from the previous flow
         * execution ticket. And starting a new 'Shutdown'.
         */
        $GLOBALS['flowID'] = 'Shutdown';
        $GLOBALS['eventName'] = 'go';
        $GLOBALS['flowExecutionTicket'] = $flowExecutionTicket1;
        $flowExecutionTicket3 = $server->invoke(new \stdClass());

        $this->assertTrue($flowExecutionTicket1 != $flowExecutionTicket3);
        $this->assertRegexp('/[0-9a-f]{40}/', $flowExecutionTicket3);
    }

    /**
     * @expectedException \Stagehand\FSM\FSMAlreadyShutdownException
     */
    public function testStartingNewFlowExecutionAfterShuttingDownContinuationInSingleFlowMode()
    {
        $GLOBALS['ShutdownCount'] = 0;

        $server = new Server(true);
        $server->setCacheDirectory($this->cacheDirectory);
        $server->addFlow('Shutdown', "{$this->cacheDirectory}/Shutdown.yaml");
        $server->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $server->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));
        $server->setFlowIDCallback(array(__CLASS__, 'getFlowID'));

        /*
         * Starting a new 'Shutdown'.
         */
        $GLOBALS['flowID'] = 'Shutdown';
        $GLOBALS['eventName'] = null;
        $GLOBALS['flowExecutionTicket'] = null;
        $flowExecutionTicket1 = $server->invoke(new \stdClass());
        $server->shutdown();

        $GLOBALS['flowID'] = 'Shutdown';
        $GLOBALS['eventName'] = 'go';
        $GLOBALS['flowExecutionTicket'] = $flowExecutionTicket1;
        $flowExecutionTicket2 = $server->invoke(new \stdClass());
        $server->shutdown();

        $this->assertEquals(1, $GLOBALS['ShutdownCount']);
        $this->assertEquals($flowExecutionTicket1, $flowExecutionTicket2);

        /*
         * Failure to continue the 'Shutdown' from the previous flow
         * execution ticket. The continuation server never starts a new
         * 'Shutdown' again.
         */
        $GLOBALS['flowID'] = 'Shutdown';
        $GLOBALS['eventName'] = 'go';
        $GLOBALS['flowExecutionTicket'] = $flowExecutionTicket1;
        $server->invoke(new \stdClass());
    }

    /**
     * @since Method available since Release 1.7.0
     */
    public function testShouldBeRequiredFlowExecutionTicketWheneverContinuingFlowExecution()
    {
        $server = new Server();
        $server->setCacheDirectory($this->cacheDirectory);
        $server->addFlow('Counter', "{$this->cacheDirectory}/Counter.yaml", true);
        $server->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $server->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));
        $server->setFlowIDCallback(array(__CLASS__, 'getFlowID'));

        $GLOBALS['flowID'] = 'Counter';
        $GLOBALS['eventName'] = null;
        $GLOBALS['flowExecutionTicket'] = null;
        $flowExecutionTicket1 = $server->invoke(new \stdClass());
        $service = $server->createService();

        $this->assertEquals(0, $service->getAttribute('counter'));

        $server->shutdown();

        $GLOBALS['flowID'] = 'Counter';
        $GLOBALS['eventName'] = null;
        $GLOBALS['flowExecutionTicket'] = null;
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
        $server = new Server();
        $server->setCacheDirectory($this->cacheDirectory);
        $server->addFlow('Counter', "{$this->cacheDirectory}/Counter.yaml", true);
        $server->addFlow('SecondCounter', "{$this->cacheDirectory}/SecondCounter.yaml");
        $server->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $server->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));
        $server->setFlowIDCallback(array(__CLASS__, 'getFlowID'));

        $GLOBALS['flowID'] = 'Counter';
        $GLOBALS['eventName'] = null;
        $GLOBALS['flowExecutionTicket'] = null;
        $flowExecutionTicket1 = $server->invoke(new \stdClass());

        $GLOBALS['flowID'] = 'Counter';
        $GLOBALS['eventName'] = 'increase';
        $GLOBALS['flowExecutionTicket'] = $flowExecutionTicket1;
        $server->invoke(new \stdClass());
        $service = $server->createService();

        $this->assertEquals(1, $service->getAttribute('counter'));

        $GLOBALS['flowID'] = 'SecondCounter';
        $GLOBALS['eventName'] = null;
        $GLOBALS['flowExecutionTicket'] = null;
        $flowExecutionTicket2 = $server->invoke(new \stdClass());
        $service = $server->createService();

        $this->assertEquals(0, $service->getAttribute('counter'));
        $this->assertFalse($flowExecutionTicket1 == $flowExecutionTicket2);
        $this->assertEquals($flowExecutionTicket1, $service->getFlowExecutionTicketByFlowID('Counter'));
        $this->assertNull($service->getFlowExecutionTicketByFlowID('SecondCounter'));
    }

    /**
     * @since Method available since Release 1.8.0
     */
    public function testBindActionsWithFlowExecution()
    {
        $flowName = 'BindActionsWithFlowExecution';
        $server = new Server();
        $server->setCacheDirectory($this->cacheDirectory);
        $server->addFlow($flowName, "{$this->cacheDirectory}/$flowName.yaml");
        $server->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $server->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));
        $server->setFlowIDCallback(array(__CLASS__, 'getFlowID'));
        $server->setActionDirectory($this->cacheDirectory);

        // The first time invocation for the flow execution one.
        $GLOBALS['flowID'] = $flowName;
        $GLOBALS['eventName'] = 'goDisplayFinishFromDisplayCounter';
        $GLOBALS['flowExecutionTicket'] = null;
        $flowExecutionTicket1 = $server->invoke(new \stdClass(), true);

        $this->assertEquals('Counter', $server->getView());

        $server->shutdown();
        Factory::clearInstances();

        // The first time invocation for the flow execution two.
        $GLOBALS['flowID'] = $flowName;
        $GLOBALS['eventName'] = 'goDisplayFinishFromDisplayCounter';
        $GLOBALS['flowExecutionTicket'] = null;
        $flowExecutionTicket2 = $server->invoke(new \stdClass(), true);

        $this->assertEquals('Counter', $server->getView());

        $server->shutdown();
        Factory::clearInstances();

        $this->assertTrue($flowExecutionTicket1 != $flowExecutionTicket2);

        // The second time invocation for the flow execution one.
        $GLOBALS['flowID'] = $flowName;
        $GLOBALS['eventName'] = 'goDisplayFinishFromDisplayCounter';
        $GLOBALS['flowExecutionTicket'] = $flowExecutionTicket1;
        $server->invoke(new \stdClass(), true);

        $this->assertEquals('Counter', $server->getView());

        $server->shutdown();
        Factory::clearInstances();

        // The last invocation for the flow execution one.
        $GLOBALS['flowID'] = $flowName;
        $GLOBALS['eventName'] = 'goDisplayFinishFromDisplayCounter';
        $GLOBALS['flowExecutionTicket'] = $flowExecutionTicket1;
        $server->invoke(new \stdClass(), true);

        $this->assertEquals('Finish', $server->getView());

        $server->shutdown();
        Factory::clearInstances();

        // The second time invocation for the flow execution two.
        $GLOBALS['flowID'] = $flowName;
        $GLOBALS['eventName'] = 'goDisplayFinishFromDisplayCounter';
        $GLOBALS['flowExecutionTicket'] = $flowExecutionTicket2;
        $server->invoke(new \stdClass(), true);

        $this->assertEquals('Counter', $server->getView());

        $server->shutdown();
        Factory::clearInstances();

        // The last invocation for the flow execution two.
        $GLOBALS['flowID'] = $flowName;
        $GLOBALS['eventName'] = 'goDisplayFinishFromDisplayCounter';
        $GLOBALS['flowExecutionTicket'] = $flowExecutionTicket2;
        $server->invoke(new \stdClass(), true);

        $this->assertEquals('Finish', $server->getView());

        $server->shutdown();
        Factory::clearInstances();
    }

    /**
     * @expectedException \Piece\Flow\Continuation\FlowExecutionExpiredException
     * @since Method available since Release 1.11.0
     */
    public function testFlowExecutionExpiredExceptionShouldBeRaisedWhenFlowExecutionHasExpired()
    {
        $flowName = 'FlowExecutionExpired';
        $server = new Server(false, true, 1);
        $server->setCacheDirectory($this->cacheDirectory);
        $server->addFlow($flowName, "{$this->cacheDirectory}/$flowName.yaml");
        $server->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $server->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));
        $server->setFlowIDCallback(array(__CLASS__, 'getFlowID'));

        $GLOBALS['flowID'] = $flowName;
        $GLOBALS['eventName'] = null;
        $GLOBALS['flowExecutionTicket'] = null;
        $flowExecutionTicket = $server->invoke(new \stdClass());
        $server->shutdown();

        sleep(2);

        $GLOBALS['flowID'] = $flowName;
        $GLOBALS['eventName'] = null;
        $GLOBALS['flowExecutionTicket'] = $flowExecutionTicket;
        $server->invoke(new \stdClass());
    }

    /**
     * @since Method available since Release 1.11.0
     */
    public function testFlowExecutionExpiredExceptionShouldNotBeRaisedWhenFlowExecutionHasNotExpired()
    {
        $flowName = 'FlowExecutionExpired';
        $server = new Server(false, true, 2);
        $server->setCacheDirectory($this->cacheDirectory);
        $server->addFlow($flowName, "{$this->cacheDirectory}/$flowName.yaml");
        $server->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $server->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));
        $server->setFlowIDCallback(array(__CLASS__, 'getFlowID'));

        $GLOBALS['flowID'] = $flowName;
        $GLOBALS['eventName'] = null;
        $GLOBALS['flowExecutionTicket'] = null;
        $flowExecutionTicket = $server->invoke(new \stdClass());
        $server->shutdown();

        sleep(1);

        $GLOBALS['flowID'] = $flowName;
        $GLOBALS['eventName'] = null;
        $GLOBALS['flowExecutionTicket'] = $flowExecutionTicket;
        $server->invoke(new \stdClass());

        sleep(1);

        $GLOBALS['flowID'] = $flowName;
        $GLOBALS['eventName'] = null;
        $GLOBALS['flowExecutionTicket'] = $flowExecutionTicket;
        $server->invoke(new \stdClass());

        sleep(1);

        $GLOBALS['flowID'] = $flowName;
        $GLOBALS['eventName'] = null;
        $GLOBALS['flowExecutionTicket'] = $flowExecutionTicket;
        $server->invoke(new \stdClass());
    }

    /**
     * @since Method available since Release 1.11.0
     */
    public function testNewFlowExecutionShouldBeAbleToStartWithSameRequestAfterFlowExecutionIsExpired()
    {
        $flowName = 'FlowExecutionExpired';
        $GLOBALS['flowID'] = $flowName;
        $server = new Server(false, true, 1);
        $server->setCacheDirectory($this->cacheDirectory);
        $server->addFlow($flowName, "{$this->cacheDirectory}/$flowName.yaml");
        $server->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $server->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));
        $server->setFlowIDCallback(array(__CLASS__, 'getFlowID'));

        $GLOBALS['flowID'] = $flowName;
        $GLOBALS['eventName'] = null;
        $GLOBALS['flowExecutionTicket'] = null;
        $flowExecutionTicket = $server->invoke(new \stdClass());
        $server->shutdown();

        sleep(2);

        $GLOBALS['flowID'] = $flowName;
        $GLOBALS['eventName'] = null;
        $GLOBALS['flowExecutionTicket'] = $flowExecutionTicket;

        try {
            $server->invoke(new \stdClass());
            $this->fail('An expected exception has not been raised.');
        } catch (FlowExecutionExpiredException $e) {
        }

        $server->shutdown();

        $GLOBALS['flowID'] = $flowName;
        $GLOBALS['eventName'] = null;
        $GLOBALS['flowExecutionTicket'] = $flowExecutionTicket;
        $newFlowExecutionTicket = $server->invoke(new \stdClass());

        $this->assertTrue($newFlowExecutionTicket != $GLOBALS['flowExecutionTicket']);
    }

    /**
     * @since Method available since Release 1.13.0
     */
    public function testCheckLastEventShouldReturnTrueIfContinuationHasJustStarted()
    {
        $flowName = 'CheckLastEvent';
        $server = new Server(false);
        $server->setCacheDirectory($this->cacheDirectory);
        $server->addFlow($flowName, "{$this->cacheDirectory}/$flowName.yaml");
        $server->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $server->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));
        $server->setFlowIDCallback(array(__CLASS__, 'getFlowID'));

        $GLOBALS['flowID'] = $flowName;
        $GLOBALS['eventName'] = 'foo';
        $GLOBALS['flowExecutionTicket'] = null;
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
        $server = new Server(false);
        $server->setCacheDirectory($this->cacheDirectory);
        $server->addFlow($flowName, "{$this->cacheDirectory}/$flowName.yaml");
        $server->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $server->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));
        $server->setFlowIDCallback(array(__CLASS__, 'getFlowID'));

        $GLOBALS['flowID'] = $flowName;
        $GLOBALS['eventName'] = null;
        $GLOBALS['flowExecutionTicket'] = null;
        $flowExecutionTicket = $server->invoke(new \stdClass());
        $server->shutdown();

        $GLOBALS['flowID'] = $flowName;
        $GLOBALS['eventName'] = 'DisplayEditConfirmFromDisplayEdit';
        $GLOBALS['flowExecutionTicket'] = $flowExecutionTicket;
        $server->invoke(new \stdClass());
        $service = $server->createService();

        $this->assertTrue($service->checkLastEvent());

        $server->shutdown();

        $GLOBALS['flowID'] = $flowName;
        $GLOBALS['eventName'] = 'DisplayEditFinishFromDisplayEditConfirm';
        $GLOBALS['flowExecutionTicket'] = $flowExecutionTicket;
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
        $server = new Server(false);
        $server->setCacheDirectory($this->cacheDirectory);
        $server->addFlow($flowName, "{$this->cacheDirectory}/$flowName.yaml");
        $server->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $server->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));
        $server->setFlowIDCallback(array(__CLASS__, 'getFlowID'));

        $GLOBALS['flowID'] = $flowName;
        $GLOBALS['eventName'] = null;
        $GLOBALS['flowExecutionTicket'] = null;
        $flowExecutionTicket = $server->invoke(new \stdClass());
        $server->shutdown();

        $GLOBALS['flowID'] = $flowName;
        $GLOBALS['eventName'] = 'foo';
        $GLOBALS['flowExecutionTicket'] = $flowExecutionTicket;
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
        $server = new Server(false);
        $server->setCacheDirectory($this->cacheDirectory);
        $server->addFlow($flowName, "{$this->cacheDirectory}/$flowName.yaml");
        $server->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $server->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));
        $server->setFlowIDCallback(array(__CLASS__, 'getFlowID'));

        $GLOBALS['flowID'] = $flowName;
        $GLOBALS['eventName'] = 'foo';
        $GLOBALS['flowExecutionTicket'] = null;
        $service = $server->createService();

        $this->assertTrue($service->checkLastEvent());
    }

    /**
     * @since Method available since Release 1.14.0
     */
    public function testCurrentStateNameShouldBeAbleToGetIfContinuationHasActivated()
    {
        $flowName = 'CheckLastEvent';
        $server = new Server(false);
        $server->setCacheDirectory($this->cacheDirectory);
        $server->addFlow($flowName, "{$this->cacheDirectory}/$flowName.yaml");
        $server->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $server->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));
        $server->setFlowIDCallback(array(__CLASS__, 'getFlowID'));

        $GLOBALS['flowID'] = $flowName;
        $GLOBALS['eventName'] = null;
        $GLOBALS['flowExecutionTicket'] = null;
        $flowExecutionTicket = $server->invoke(new \stdClass());
        $service = $server->createService();

        $this->assertEquals('DisplayEdit', $service->getCurrentStateName());

        $server->shutdown();

        $GLOBALS['flowID'] = $flowName;
        $GLOBALS['eventName'] = 'DisplayEditConfirmFromDisplayEdit';
        $GLOBALS['flowExecutionTicket'] = $flowExecutionTicket;
        $server->invoke(new \stdClass());
        $service = $server->createService();

        $this->assertEquals('DisplayEditConfirm', $service->getCurrentStateName());

        $server->shutdown();

        $GLOBALS['flowID'] = $flowName;
        $GLOBALS['eventName'] = 'DisplayEditFinishFromDisplayEditConfirm';
        $GLOBALS['flowExecutionTicket'] = $flowExecutionTicket;
        $server->invoke(new \stdClass());
        $service = $server->createService();

        $this->assertEquals('DisplayEditFinish', $service->getCurrentStateName());
    }

    /**
     * @expectedException \Piece\Flow\MethodInvocationException
     * @since Method available since Release 1.14.0
     */
    public function testGetCurrentStateNameShouldRaiseExceptionIfContinuationHasNotActivated()
    {
        $flowName = 'CheckLastEvent';
        $server = new Server(false);
        $server->setCacheDirectory($this->cacheDirectory);
        $server->addFlow($flowName, "{$this->cacheDirectory}/$flowName.yaml");
        $server->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $server->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));
        $server->setFlowIDCallback(array(__CLASS__, 'getFlowID'));

        $GLOBALS['flowID'] = $flowName;
        $GLOBALS['eventName'] = null;
        $GLOBALS['flowExecutionTicket'] = null;
        $service = $server->createService();
        $service->getCurrentStateName();
    }

    /**
     * @since Method available since Release 1.15.0
     */
    public function testFlowExecutionShouldWorkWithConfigDirectory()
    {
        $server = new Server();
        $server->setCacheDirectory($this->cacheDirectory);
        $server->addFlow('/counter/one.php', 'Counter_One');
        $server->addFlow('/counter/two.php', 'Counter_Two');
        $server->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $server->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));
        $server->setFlowIDCallback(array(__CLASS__, 'getFlowID'));
        $server->setActionDirectory($this->cacheDirectory);
        $server->setConfigDirectory($this->cacheDirectory);
        $server->setConfigExtension('.flow');

        /*
         * Starting a new '/counter/one.php'.
         */
        $GLOBALS['flowID'] = '/counter/one.php';
        $GLOBALS['eventName'] = null;
        $GLOBALS['flowExecutionTicket'] = null;
        $flowExecutionTicket1 = $server->invoke(new \stdClass());
        $service = $server->createService();

        $this->assertEquals(0, $service->getAttribute('counter'));

        $server->shutdown();

        /*
         * Starting a new '/counter/two.php'.
         */
        $GLOBALS['flowID'] = '/counter/two.php';
        $GLOBALS['eventName'] = null;
        $GLOBALS['flowExecutionTicket'] = null;
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
        $GLOBALS['flowID'] = '/counter/one.php';
        $GLOBALS['eventName'] = 'increase';
        $GLOBALS['flowExecutionTicket'] = $flowExecutionTicket1;
        $flowExecutionTicket3 = $server->invoke(new \stdClass());
        $service = $server->createService();

        $this->assertEquals(1, $service->getAttribute('counter'));

        $this->assertEquals('Counter', $server->getView());
        $this->assertEquals($flowExecutionTicket1, $flowExecutionTicket3);

        $server->shutdown();

        /*
         * Continuing the first '/counter/two.php'.
         */
        $GLOBALS['flowID'] = '/counter/two.php';
        $GLOBALS['eventName'] = 'increase';
        $GLOBALS['flowExecutionTicket'] = $flowExecutionTicket2;
        $flowExecutionTicket4 = $server->invoke(new \stdClass());
        $service = $server->createService();

        $this->assertEquals('SecondCounter', $server->getView());
        $this->assertEquals(1, $service->getAttribute('counter'));
        $this->assertEquals($flowExecutionTicket2, $flowExecutionTicket4);

        $server->shutdown();

        /*
         * Starting a new '/counter/two.php'.
         */
        $GLOBALS['flowID'] = '/counter/two.php';
        $GLOBALS['eventName'] = null;
        $GLOBALS['flowExecutionTicket'] = null;
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
        $server = new Server(false, true, 1);
        $server->setCacheDirectory($this->cacheDirectory);
        $server->addFlow($flowName, "{$this->cacheDirectory}/$flowName.yaml");
        $server->setEventNameCallback(array(__CLASS__, 'getEventName'));
        $server->setFlowExecutionTicketCallback(array(__CLASS__, 'getFlowExecutionTicket'));
        $server->setFlowIDCallback(array(__CLASS__, 'getFlowID'));

        $GLOBALS['flowID'] = $flowName;
        $GLOBALS['eventName'] = null;
        $GLOBALS['flowExecutionTicket'] = null;
        $flowExecutionTicket1 = $server->invoke(new \stdClass());
        $server->shutdown();

        sleep(2);

        $GLOBALS['flowID'] = $flowName;
        $GLOBALS['eventName'] = null;
        $GLOBALS['flowExecutionTicket'] = null;
        $flowExecutionTicket2 = $server->invoke(new \stdClass());
        $server->shutdown();

        $this->assertTrue($flowExecutionTicket1 != $flowExecutionTicket2);

        $GLOBALS['flowID'] = $flowName;
        $GLOBALS['eventName'] = null;
        $GLOBALS['flowExecutionTicket'] = $flowExecutionTicket1;

        try {
            $server->invoke(new \stdClass());
            $this->fail('An expected exception has not been raised.');
        } catch (FlowExecutionExpiredException $e) {
        }
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
