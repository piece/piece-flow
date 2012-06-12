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
 * @since      File available since Release 0.1.0
 */

namespace Piece\Flow\PageFlow;

use Stagehand\FSM\Event;
use Stagehand\FSM\FSMAlreadyShutdownException;

use Piece\Flow\Action\Factory;
use Piece\Flow\ConfigReader;
use Piece\Flow\PageFlow\EventNotFoundException;
use Piece\Flow\Util\ErrorReporting;

/**
 * @package    Piece_Flow
 * @copyright  2006-2008, 2012 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version    Release: @package_version@
 * @since      Class available since Release 0.1.0
 */
class PageFlowTest extends \PHPUnit_Framework_TestCase
{
    protected $source;
    protected $config;
    protected $cacheDirectory;

    protected function setUp()
    {
        $this->cacheDirectory = dirname(__FILE__) . '/' . basename(__FILE__, '.php');
        $this->source = "{$this->cacheDirectory}/Registration.yaml";
        $this->config = ConfigReader::read($this->source, null, $this->cacheDirectory, null, null);
    }

    protected function tearDown()
    {
        Factory::clearInstances();
        Factory::setActionDirectory(null);
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
        $this->source = null;
        $this->config = null;
     }

    public function testConfiguration()
    {
        $flow = new PageFlow();
        $flow->configure($this->source, null, $this->cacheDirectory, $this->cacheDirectory);

        $this->assertEquals($this->config->getName(), $flow->getName());
    }

    public function testGettingView()
    {
        $viewStates = $this->config->getViewStates();
        $flow = new PageFlow();
        $flow->configure($this->source, null, $this->cacheDirectory, $this->cacheDirectory);
        $flow->start();

        $this->assertEquals($viewStates['DisplayForm']['view'],
                            $flow->getView()
                            );
    }

    public function testInvokingCallback()
    {
        $GLOBALS['validateInputCalled'] = false;
        $GLOBALS['prepareCalled'] = false;
        $viewStates = $this->config->getViewStates();
        $flow = new PageFlow();
        $flow->configure($this->source, null, $this->cacheDirectory, $this->cacheDirectory);
        $flow->start();
        $flow->triggerEvent('submit');

        $this->assertTrue($GLOBALS['validateInputCalled']);
        $this->assertEquals($viewStates['ConfirmForm']['view'],
                            $flow->getView()
                            );
        $this->assertTrue($GLOBALS['prepareCalled']);
    }

    public function testGettingPreviousStateName()
    {
        $flow = new PageFlow();
        $flow->configure($this->source, null, $this->cacheDirectory, $this->cacheDirectory);
        $flow->start();
        $flow->triggerEvent('submit');

        $this->assertEquals('processSubmitDisplayForm', $flow->getPreviousStateName());
    }

    public function testGettingCurrentStateName()
    {
        $flow = new PageFlow();
        $flow->configure($this->source, null, $this->cacheDirectory, $this->cacheDirectory);
        $flow->start();
        $flow->triggerEvent('submit');

        $this->assertEquals('ConfirmForm', $flow->getCurrentStateName());
    }

    public function testTriggeringEventAndInvokingTransitionAction()
    {
        $GLOBALS['validateInputCalled'] = false;
        $GLOBALS['validateConfirmationCalled'] = false;
        $viewStates = $this->config->getViewStates();
        $flow = new PageFlow();
        $flow->configure($this->source, null, $this->cacheDirectory, $this->cacheDirectory);
        $flow->start();
        $flow->triggerEvent('submit');

        $this->assertTrue($GLOBALS['validateInputCalled']);
        $this->assertEquals($viewStates['ConfirmForm']['view'],
                            $flow->getView()
                            );

        $flow->triggerEvent('submit');

        $this->assertTrue($GLOBALS['validateConfirmationCalled']);

        $lastState = $this->config->getLastState();

        $this->assertEquals($viewStates[$lastState]['view'], $flow->getView());
    }

    public function testTriggeringRaiseErrorEvent()
    {
        $GLOBALS['hasErrors'] = true;
        $viewStates = $this->config->getViewStates();
        $flow = new PageFlow();
        $flow->configure($this->source, null, $this->cacheDirectory, $this->cacheDirectory);
        $flow->start();
        $flow->triggerEvent('submit');

        $this->assertEquals($viewStates['DisplayForm']['view'],
                            $flow->getView()
                            );
    }

    public function testActivity()
    {
        $GLOBALS['displayCounter'] = 0;
        $flow = new PageFlow();
        $flow->configure($this->source, null, $this->cacheDirectory, $this->cacheDirectory);
        $flow->start();

        $this->assertEquals(1, $GLOBALS['displayCounter']);

        $flow->triggerEvent('foo');
        $flow->triggerEvent('bar');

        $this->assertEquals(3, $GLOBALS['displayCounter']);
    }

    public function testExitAndEntryActions()
    {
        $GLOBALS['setupFormCalled'] = false;
        $GLOBALS['teardownFormCalled'] = false;
        $flow = new PageFlow();
        $flow->configure($this->source, null, $this->cacheDirectory, $this->cacheDirectory);
        $flow->start();

        $this->assertTrue($GLOBALS['setupFormCalled']);
        $this->assertFalse($GLOBALS['teardownFormCalled']);

        $flow->triggerEvent('submit');

        $this->assertTrue($GLOBALS['teardownFormCalled']);
    }

    public function testSettingAttribute()
    {
        $flow = new PageFlow();
        $flow->configure($this->source, null, $this->cacheDirectory, $this->cacheDirectory);
        $flow->start();
        $flow->setAttribute('foo', 'bar');

        $this->assertTrue($flow->hasAttribute('foo'));
        $this->assertEquals('bar', $flow->getAttribute('foo'));
    }

    /**
     * @expectedException \Piece\Flow\Core\MethodInvocationException
     */
    public function testFailureToSetAttributeBeforeStartingFlow()
    {
        $flow = new PageFlow();
        $flow->configure($this->source, null, $this->cacheDirectory, $this->cacheDirectory);
        $flow->setAttribute('foo', 'bar');
    }

    /**
     * @expectedException \Piece\Flow\Core\MethodInvocationException
     */
    public function testFailureToSetPayloadBeforeConfiguringFlow()
    {
        $flow = new PageFlow();
        $flow->setPayload(new \stdClass());
    }

    public function testOptionalElements()
    {
        $flow = new PageFlow();
        $flow->configure("{$this->cacheDirectory}/optional.xml", null, $this->cacheDirectory, $this->cacheDirectory);
        $flow->setPayload(new \stdClass());
        $flow->start();

        $this->assertEquals('foo', $flow->getView());

        $flow = new PageFlow();
        $flow->configure("{$this->cacheDirectory}/optional.yaml", null, $this->cacheDirectory, $this->cacheDirectory);
        $flow->setPayload(new \stdClass());
        $flow->start();

        $this->assertEquals('foo', $flow->getView());
    }

    public function testInitialAndFinalActionsWithXML()
    {
        $this->assertInitialAndFinalActions('/initial.xml');
    }

    public function testInitialAndFinalActionsWithYAML()
    {
        $this->assertInitialAndFinalActions('/initial.yaml');
    }

    /**
     * @expectedException \Piece\Flow\Core\MethodInvocationException
     */
    public function testFailureToGetViewBeforeStartingFlow()
    {
        $flow = new PageFlow();
        $flow->configure($this->source, null, $this->cacheDirectory, $this->cacheDirectory);
        $flow->getView();
    }

    /**
     * @expectedException \Piece\Flow\InvalidTransitionException
     */
    public function testInvalidTransition()
    {
        $flow = new PageFlow();
        $flow->configure("{$this->cacheDirectory}/invalid.yaml", null, $this->cacheDirectory, $this->cacheDirectory);
        $flow->setPayload(new \stdClass());
        $flow->start();
        $flow->triggerEvent('go');
        $flow->getView();
    }

    public function testCheckingWhetherCurrentStateIsFinalState()
    {
        $flow = new PageFlow();
        $flow->configure("{$this->cacheDirectory}/initial.yaml", null, $this->cacheDirectory, $this->cacheDirectory);
        $flow->setPayload(new \stdClass());
        $flow->start();

        $this->assertFalse($flow->isFinalState());

        $flow->triggerEvent('go');

        $this->assertTrue($flow->isFinalState());
    }

    public function testSettingAttributeByReference()
    {
        $flow = new PageFlow();
        $flow->configure($this->source, null, $this->cacheDirectory, $this->cacheDirectory);
        $flow->start();

        $foo1 = new \stdClass();
        $flow->setAttributeByRef('foo', $foo1);
        $foo1->bar = 'baz';

        $this->assertTrue($flow->hasAttribute('foo'));

        $foo2 = &$flow->getAttribute('foo');

        $this->assertTrue(property_exists($foo2, 'bar'));
        $this->assertEquals('baz', $foo2->bar);
    }

    public function testRemovingAttribute()
    {
        $flow = new PageFlow();
        $flow->configure($this->source, null, $this->cacheDirectory, $this->cacheDirectory);
        $flow->start();
        $flow->setAttribute('foo', 'bar');

        $this->assertTrue($flow->hasAttribute('foo'));

        $flow->removeAttribute('foo');

        $this->assertFalse($flow->hasAttribute('foo'));
    }

    public function testClearingAttributes()
    {
        $flow = new PageFlow();
        $flow->configure($this->source, null, $this->cacheDirectory, $this->cacheDirectory);
        $flow->start();
        $flow->setAttribute('foo', 'bar');
        $flow->setAttribute('bar', 'baz');

        $this->assertTrue($flow->hasAttribute('foo'));
        $this->assertTrue($flow->hasAttribute('bar'));

        $flow->clearAttributes();

        $this->assertFalse($flow->hasAttribute('foo'));
        $this->assertFalse($flow->hasAttribute('bar'));
    }

    /**
     * @since Method available since Release 1.2.0
     */
    public function testToPreventTriggeringProtectedEvents()
    {
        $flow = new PageFlow();
        $flow->configure("{$this->cacheDirectory}/CDPlayer.yaml", null, $this->cacheDirectory, $this->cacheDirectory);
        $flow->setPayload(new \stdClass());
        $flow->start();

        $this->assertEquals('Stop', $flow->getCurrentStateName());
        $this->assertEquals(1, $flow->getAttribute('numberOfUpdate'));

        $flow->triggerEvent('foo');

        $this->assertEquals('Stop', $flow->getCurrentStateName());
        $this->assertEquals(2, $flow->getAttribute('numberOfUpdate'));

        @$flow->triggerEvent(Event::EVENT_ENTRY);

        $this->assertEquals('Stop', $flow->getCurrentStateName());
        $this->assertEquals(3, $flow->getAttribute('numberOfUpdate'));

        @$flow->triggerEvent(Event::EVENT_EXIT);

        $this->assertEquals('Stop', $flow->getCurrentStateName());
        $this->assertEquals(4, $flow->getAttribute('numberOfUpdate'));

        @$flow->triggerEvent(Event::EVENT_START);

        $this->assertEquals('Stop', $flow->getCurrentStateName());
        $this->assertEquals(5, $flow->getAttribute('numberOfUpdate'));

        @$flow->triggerEvent(Event::EVENT_END);

        $this->assertEquals('Stop', $flow->getCurrentStateName());
        $this->assertEquals(6, $flow->getAttribute('numberOfUpdate'));

        @$flow->triggerEvent(Event::EVENT_DO);

        $this->assertEquals('Stop', $flow->getCurrentStateName());
        $this->assertEquals(7, $flow->getAttribute('numberOfUpdate'));

        $flow->triggerEvent('play');

        $this->assertEquals('Playing', $flow->getCurrentStateName());
        $this->assertEquals(7, $flow->getAttribute('numberOfUpdate'));
    }

    /**
     * @expectedException \Piece\Flow\ProtectedEventException
     * @since Method available since Release 1.2.0
     */
    public function testProtectedEvents()
    {
        $flow = new PageFlow();
        $flow->configure("{$this->cacheDirectory}/ProtectedEvents.yaml", null, $this->cacheDirectory, $this->cacheDirectory);
    }

    /**
     * @expectedException \Piece\Flow\ProtectedStateException
     * @since Method available since Release 1.2.0
     */
    public function testProtectedStates()
    {
        $flow = new PageFlow();
        $flow->configure("{$this->cacheDirectory}/ProtectedStates.yaml", null, $this->cacheDirectory, $this->cacheDirectory);
    }

    /**
     * @since Method available since Release 1.3.0
     */
    public function testInvalidEventFromATransitionActionsOrActivities()
    {
        $GLOBALS['invalidEventFrom'] = 'register';
        $flow1 = new PageFlow();
        $flow1->configure("{$this->cacheDirectory}/InvalidEventFromTransitionActionsOrActivities.yaml", null, $this->cacheDirectory, $this->cacheDirectory);
        $flow1->setPayload(new \stdClass());
        $flow1->start();

        $this->assertEquals('DisplayForm', $flow1->getCurrentStateName());

        $flow1->triggerEvent('foo');

        $this->assertEquals('DisplayForm', $flow1->getCurrentStateName());

        try {
            $flow1->triggerEvent('register');
            $this->fail('An expected exception has not been raised.');
        } catch (EventNotFoundException $e) {
        }

        $GLOBALS['invalidEventFrom'] = 'setupFinish';

        $flow2 = new PageFlow();
        $flow2->configure("{$this->cacheDirectory}/InvalidEventFromTransitionActionsOrActivities.yaml", null, $this->cacheDirectory, $this->cacheDirectory);
        $flow2->setPayload(new \stdClass());
        $flow2->start();

        $this->assertEquals('DisplayForm', $flow2->getCurrentStateName());

        $flow2->triggerEvent('foo');

        $this->assertEquals('DisplayForm', $flow2->getCurrentStateName());

        try {
            $flow2->triggerEvent('register');
            $this->fail('An expected exception has not been raised.');
        } catch (EventNotFoundException $e) {
        }
    }

    /**
     * @since Method available since Release 1.4.0
     */
    public function testProblemThatActivityIsInvokedTwiceUnexpectedly()
    {
        $flow = new PageFlow();
        $flow->configure("{$this->cacheDirectory}/ProblemThatActivityIsInvokedTwiceUnexpectedly.yaml", null, $this->cacheDirectory, $this->cacheDirectory);
        $flow->setPayload(new \stdClass());
        $flow->start();

        $this->assertEquals(1, $flow->getAttribute('setupFormProblemThatActivityIsInvokedTwiceCalled'));

        $flow->triggerEvent('confirmForm');

        $this->assertEquals(1, $flow->getAttribute('setupFormProblemThatActivityIsInvokedTwiceCalled'));
        $this->assertEquals(1, $flow->getAttribute('validateProblemThatActivityIsInvokedTwiceCalled'));
        $this->assertEquals(1, $flow->getAttribute('setupConfirmationProblemThatActivityIsInvokedTwiceCalled'));
    }

    /**
     * @since Method available since Release 1.7.0
     */
    public function testOmitClassName()
    {
        $this->assertOmitClassName('.yaml');
        $this->assertOmitClassName('.xml');
    }

    protected function assertInitialAndFinalActions($source)
    {
        $GLOBALS['initializeCalled'] = false;
        $GLOBALS['finalizeCalled'] = false;

        $flow = new PageFlow();
        $flow->configure("{$this->cacheDirectory}/$source", null, $this->cacheDirectory, $this->cacheDirectory);
        $flow->setPayload(new \stdClass());
        $flow->start();

        $this->assertEquals('start', $flow->getView());
        $this->assertTrue($GLOBALS['initializeCalled']);
        $this->assertFalse($GLOBALS['finalizeCalled']);

        $flow->triggerEvent('go');

        $this->assertEquals('end', $flow->getView());
        $this->assertTrue($GLOBALS['finalizeCalled']);

        try {
            $flow->triggerEvent('go');
            $this->fail('An expected exception has not been raised.');
        } catch (FSMAlreadyShutdownException $e) {
        }
    }

    /**
     * @since Method available since Release 1.7.0
     */
    protected function assertOmitClassName($extension)
    {
        $GLOBALS['initializeCalled'] = false;
        $flow = new PageFlow();
        $flow->configure("{$this->cacheDirectory}/OmitClassName$extension",
                         null,
                         $this->cacheDirectory,
                         $this->cacheDirectory
                         );
        $flow->setPayload(new \stdClass());
        $flow->start();

        $this->assertTrue($GLOBALS['initializeCalled']);
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
