<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * PHP versions 4 and 5
 *
 * Copyright (c) 2006-2007 KUBO Atsuhiro <iteman@users.sourceforge.net>,
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
 * @copyright  2006-2007 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    SVN: $Id$
 * @since      File available since Release 0.1.0
 */

require_once realpath(dirname(__FILE__) . '/../prepare.php');
require_once 'PHPUnit.php';
require_once 'Piece/Flow.php';
require_once 'Cache/Lite/File.php';
require_once 'Piece/Flow/Action/Factory.php';
require_once 'Piece/Flow/Error.php';
require_once 'Piece/Flow/ConfigReader.php';

// {{{ Piece_FlowTestCase

/**
 * TestCase for Piece_Flow
 *
 * @package    Piece_Flow
 * @copyright  2006-2007 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @since      Class available since Release 0.1.0
 */
class Piece_FlowTestCase extends PHPUnit_TestCase
{

    // {{{ properties

    /**#@+
     * @access public
     */

    /**#@-*/

    /**#@+
     * @access private
     */

    var $_source;
    var $_config;
    var $_cacheDirectory;

    /**#@-*/

    /**#@+
     * @access public
     */

    function setUp()
    {
        Piece_Flow_Error::pushCallback(create_function('$error', 'var_dump($error); return ' . PEAR_ERRORSTACK_DIE . ';'));
        $this->_cacheDirectory = dirname(__FILE__) . '/' . basename(__FILE__, '.php');
        $this->_source = "{$this->_cacheDirectory}/Registration.yaml";
        $this->_config = &Piece_Flow_ConfigReader::read($this->_source, null, $this->_cacheDirectory, null, null);
    }

    function tearDown()
    {
        Piece_Flow_Action_Factory::clearInstances();
        Piece_Flow_Action_Factory::setActionDirectory(null);
        $cache = &new Cache_Lite_File(array('cacheDir' => "{$this->_cacheDirectory}/",
                                            'masterFile' => '',
                                            'automaticSerialization' => true,
                                            'errorHandlingAPIBreak' => true)
                                      );
        $cache->clean();
        $this->_source = null;
        $this->_config = null;
        Piece_Flow_Error::clearErrors();
        Piece_Flow_Error::popCallback();
     }

    function testConfiguration()
    {
        $flow = &new Piece_Flow();
        $flow->configure($this->_source, null, $this->_cacheDirectory, $this->_cacheDirectory);

        $this->assertEquals($this->_config->getName(), $flow->getName());
    }

    function testGettingView()
    {
        $viewStates = $this->_config->getViewStates();
        $flow = &new Piece_Flow();
        $flow->configure($this->_source, null, $this->_cacheDirectory, $this->_cacheDirectory);
        $flow->start();

        $this->assertEquals($viewStates['DisplayForm']['view'],
                            $flow->getView()
                            );
    }

    function testInvokingCallback()
    {
        $GLOBALS['validateInputCalled'] = false;
        $GLOBALS['prepareCalled'] = false;
        $viewStates = $this->_config->getViewStates();
        $flow = &new Piece_Flow();
        $flow->configure($this->_source, null, $this->_cacheDirectory, $this->_cacheDirectory);
        $flow->start();
        $flow->triggerEvent('submit');

        $this->assertTrue($GLOBALS['validateInputCalled']);
        $this->assertEquals($viewStates['ConfirmForm']['view'],
                            $flow->getView()
                            );
        $this->assertTrue($GLOBALS['prepareCalled']);

        unset($GLOBALS['validateInputCalled']);
        unset($GLOBALS['prepareCalled']);
    }

    function testGettingPreviousStateName()
    {
        $flow = &new Piece_Flow();
        $flow->configure($this->_source, null, $this->_cacheDirectory, $this->_cacheDirectory);
        $flow->start();
        $flow->triggerEvent('submit');

        $this->assertEquals('processSubmitDisplayForm', $flow->getPreviousStateName());
    }

    function testGettingCurrentStateName()
    {
        $flow = &new Piece_Flow();
        $flow->configure($this->_source, null, $this->_cacheDirectory, $this->_cacheDirectory);
        $flow->start();
        $flow->triggerEvent('submit');

        $this->assertEquals('ConfirmForm', $flow->getCurrentStateName());
    }

    function testTriggeringEventAndInvokingTransitionAction()
    {
        $GLOBALS['validateInputCalled'] = false;
        $GLOBALS['validateConfirmationCalled'] = false;
        $viewStates = $this->_config->getViewStates();
        $flow = &new Piece_Flow();
        $flow->configure($this->_source, null, $this->_cacheDirectory, $this->_cacheDirectory);
        $flow->start();
        $flow->triggerEvent('submit');

        $this->assertTrue($GLOBALS['validateInputCalled']);
        $this->assertEquals($viewStates['ConfirmForm']['view'],
                            $flow->getView()
                            );

        $flow->triggerEvent('submit');

        $this->assertTrue($GLOBALS['validateConfirmationCalled']);

        $lastState = $this->_config->getLastState();

        $this->assertEquals($viewStates[$lastState]['view'], $flow->getView());

        unset($GLOBALS['validateInputCalled']);
        unset($GLOBALS['validateConfirmationCalled']);
    }

    function testTriggeringRaiseErrorEvent()
    {
        $GLOBALS['hasErrors'] = true;
        $viewStates = $this->_config->getViewStates();
        $flow = &new Piece_Flow();
        $flow->configure($this->_source, null, $this->_cacheDirectory, $this->_cacheDirectory);
        $flow->start();
        $flow->triggerEvent('submit');

        $this->assertEquals($viewStates['DisplayForm']['view'],
                            $flow->getView()
                            );

        unset($GLOBALS['hasErrors']);
    }

    function testActivity()
    {
        $GLOBALS['displayCounter'] = 0;
        $flow = &new Piece_Flow();
        $flow->configure($this->_source, null, $this->_cacheDirectory, $this->_cacheDirectory);
        $flow->start();

        $this->assertEquals(1, $GLOBALS['displayCounter']);

        $flow->triggerEvent('foo');
        $flow->triggerEvent('bar');

        $this->assertEquals(3, $GLOBALS['displayCounter']);

        unset($GLOBALS['displayCounter']);
    }

    function testExitAndEntryActions()
    {
        $GLOBALS['setupFormCalled'] = false;
        $GLOBALS['teardownFormCalled'] = false;
        $flow = &new Piece_Flow();
        $flow->configure($this->_source, null, $this->_cacheDirectory, $this->_cacheDirectory);
        $flow->start();

        $this->assertTrue($GLOBALS['setupFormCalled']);
        $this->assertFalse($GLOBALS['teardownFormCalled']);

        $flow->triggerEvent('submit');

        $this->assertTrue($GLOBALS['teardownFormCalled']);
    }

    function testSettingAttribute()
    {
        $flow = &new Piece_Flow();
        $flow->configure($this->_source, null, $this->_cacheDirectory, $this->_cacheDirectory);
        $flow->start();
        $flow->setAttribute('foo', 'bar');

        $this->assertTrue($flow->hasAttribute('foo'));
        $this->assertEquals('bar', $flow->getAttribute('foo'));
    }

    function testFailureToSetAttributeBeforeStartingFlow()
    {
        Piece_Flow_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));

        $flow = &new Piece_Flow();
        $flow->configure($this->_source, null, $this->_cacheDirectory, $this->_cacheDirectory);
        $flow->setAttribute('foo', 'bar');

        $this->assertTrue(Piece_Flow_Error::hasErrors('exception'));

        $error = Piece_Flow_Error::pop();

        $this->assertEquals(PIECE_FLOW_ERROR_INVALID_OPERATION, $error['code']);

        Piece_Flow_Error::popCallback();
    }

    function testFailureToSetPayloadBeforeConfiguringFlow()
    {
        Piece_Flow_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));
        $flow = &new Piece_Flow();
        $flow->setPayload(new stdClass());

        $this->assertTrue(Piece_Flow_Error::hasErrors('exception'));

        $error = Piece_Flow_Error::pop();

        $this->assertEquals(PIECE_FLOW_ERROR_INVALID_OPERATION, $error['code']);

        Piece_Flow_Error::popCallback();
    }

    function testOptionalElements()
    {
        $flow = &new Piece_Flow();
        $flow->configure("{$this->_cacheDirectory}/optional.xml", null, $this->_cacheDirectory, $this->_cacheDirectory);
        $flow->setPayload(new stdClass());
        $flow->start();

        $this->assertEquals('foo', $flow->getView());

        $flow = &new Piece_Flow();
        $flow->configure("{$this->_cacheDirectory}/optional.yaml", null, $this->_cacheDirectory, $this->_cacheDirectory);
        $flow->setPayload(new stdClass());
        $flow->start();

        $this->assertEquals('foo', $flow->getView());
    }

    function testInitialAndFinalActionsWithXML()
    {
        $this->_assertInitialAndFinalActions('/initial.xml');
    }

    function testInitialAndFinalActionsWithYAML()
    {
        $this->_assertInitialAndFinalActions('/initial.yaml');
    }

    function testFailureToGetViewBeforeStartingFlow()
    {
        Piece_Flow_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));
        $flow = &new Piece_Flow();
        $flow->configure($this->_source, null, $this->_cacheDirectory, $this->_cacheDirectory);
        $flow->getView();

        $this->assertTrue(Piece_Flow_Error::hasErrors('exception'));

        $error = Piece_Flow_Error::pop();

        $this->assertEquals(PIECE_FLOW_ERROR_INVALID_OPERATION, $error['code']);

        Piece_Flow_Error::popCallback();
    }

    function testInvalidTransition()
    {
        Piece_Flow_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));

        $flow = &new Piece_Flow();
        $flow->configure("{$this->_cacheDirectory}/invalid.yaml", null, $this->_cacheDirectory, $this->_cacheDirectory);
        $flow->setPayload(new stdClass());
        $flow->start();
        $flow->triggerEvent('go');
        $flow->getView();

        $this->assertTrue(Piece_Flow_Error::hasErrors('exception'));

        $error = Piece_Flow_Error::pop();

        $this->assertEquals(PIECE_FLOW_ERROR_INVALID_TRANSITION, $error['code']);

        Piece_Flow_Error::popCallback();
    }

    function testCheckingWhetherCurrentStateIsFinalState()
    {
        $flow = &new Piece_Flow();
        $flow->configure("{$this->_cacheDirectory}/initial.yaml", null, $this->_cacheDirectory, $this->_cacheDirectory);
        $flow->setPayload(new stdClass());
        $flow->start();

        $this->assertFalse($flow->isFinalState());

        $flow->triggerEvent('go');

        $this->assertTrue($flow->isFinalState());
    }

    function testSettingAttributeByReference()
    {
        $flow = &new Piece_Flow();
        $flow->configure($this->_source, null, $this->_cacheDirectory, $this->_cacheDirectory);
        $flow->start();

        $foo1 = &new stdClass();
        $flow->setAttributeByRef('foo', $foo1);
        $foo1->bar = 'baz';

        $this->assertTrue($flow->hasAttribute('foo'));

        $foo2 = &$flow->getAttribute('foo');

        $this->assertTrue(array_key_exists('bar', $foo2));
        $this->assertEquals('baz', $foo2->bar);
    }

    function testRemovingAttribute()
    {
        $flow = &new Piece_Flow();
        $flow->configure($this->_source, null, $this->_cacheDirectory, $this->_cacheDirectory);
        $flow->start();
        $flow->setAttribute('foo', 'bar');

        $this->assertTrue($flow->hasAttribute('foo'));

        $flow->removeAttribute('foo');

        $this->assertFalse($flow->hasAttribute('foo'));
    }

    function testClearingAttributes()
    {
        $flow = &new Piece_Flow();
        $flow->configure($this->_source, null, $this->_cacheDirectory, $this->_cacheDirectory);
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
    function testToPreventTriggeringProtectedEvents()
    {
        $flow = &new Piece_Flow();
        $flow->configure("{$this->_cacheDirectory}/CDPlayer.yaml", null, $this->_cacheDirectory, $this->_cacheDirectory);
        $flow->setPayload(new stdClass());
        $flow->start();

        $this->assertEquals('Stop', $flow->getCurrentStateName());
        $this->assertEquals(1, $flow->getAttribute('numberOfUpdate'));

        $flow->triggerEvent('foo');

        $this->assertEquals('Stop', $flow->getCurrentStateName());
        $this->assertEquals(2, $flow->getAttribute('numberOfUpdate'));

        $flow->triggerEvent(STAGEHAND_FSM_EVENT_ENTRY);

        $this->assertEquals('Stop', $flow->getCurrentStateName());
        $this->assertEquals(3, $flow->getAttribute('numberOfUpdate'));

        $flow->triggerEvent(STAGEHAND_FSM_EVENT_EXIT);

        $this->assertEquals('Stop', $flow->getCurrentStateName());
        $this->assertEquals(4, $flow->getAttribute('numberOfUpdate'));

        $flow->triggerEvent(STAGEHAND_FSM_EVENT_START);

        $this->assertEquals('Stop', $flow->getCurrentStateName());
        $this->assertEquals(5, $flow->getAttribute('numberOfUpdate'));

        $flow->triggerEvent(STAGEHAND_FSM_EVENT_END);

        $this->assertEquals('Stop', $flow->getCurrentStateName());
        $this->assertEquals(6, $flow->getAttribute('numberOfUpdate'));

        $flow->triggerEvent(STAGEHAND_FSM_EVENT_DO);

        $this->assertEquals('Stop', $flow->getCurrentStateName());
        $this->assertEquals(7, $flow->getAttribute('numberOfUpdate'));

        $flow->triggerEvent('play');

        $this->assertEquals('Playing', $flow->getCurrentStateName());
        $this->assertEquals(7, $flow->getAttribute('numberOfUpdate'));
    }

    /**
     * @since Method available since Release 1.2.0
     */
    function testProtectedEvents()
    {
        Piece_Flow_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));

        $flow = &new Piece_Flow();
        $flow->configure("{$this->_cacheDirectory}/ProtectedEvents.yaml", null, $this->_cacheDirectory, $this->_cacheDirectory);

        $this->assertTrue(Piece_Flow_Error::hasErrors('exception'));

        $error = Piece_Flow_Error::pop();

        $this->assertEquals(PIECE_FLOW_ERROR_PROTECTED_EVENT, $error['code']);

        Piece_Flow_Error::popCallback();
    }

    /**
     * @since Method available since Release 1.2.0
     */
    function testProtectedStates()
    {
        Piece_Flow_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));

        $flow = &new Piece_Flow();
        $flow->configure("{$this->_cacheDirectory}/ProtectedStates.yaml", null, $this->_cacheDirectory, $this->_cacheDirectory);

        $this->assertTrue(Piece_Flow_Error::hasErrors('exception'));

        $error = Piece_Flow_Error::pop();

        $this->assertEquals(PIECE_FLOW_ERROR_PROTECTED_STATE, $error['code']);

        Piece_Flow_Error::popCallback();
    }

    /**
     * @since Method available since Release 1.3.0
     */
    function testInvalidEventFromATransitionActionsOrActivities()
    {
        Piece_Flow_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));
        $GLOBALS['invalidEventFrom'] = 'register';

        $flow1 = &new Piece_Flow();
        $flow1->configure("{$this->_cacheDirectory}/InvalidEventFromTransitionActionsOrActivities.yaml", null, $this->_cacheDirectory, $this->_cacheDirectory);
        $flow1->setPayload(new stdClass());
        $flow1->start();

        $this->assertEquals('DisplayForm', $flow1->getCurrentStateName());

        $flow1->triggerEvent('foo');

        $this->assertEquals('DisplayForm', $flow1->getCurrentStateName());

        $flow1->triggerEvent('register');

        $this->assertTrue(Piece_Flow_Error::hasErrors('exception'));

        $error = Piece_Flow_Error::pop();

        $this->assertEquals(PIECE_FLOW_ERROR_CANNOT_INVOKE, $error['code']);
        $this->assertEquals(PIECE_FLOW_ERROR_INVALID_EVENT, $error['repackage']['code']);
        $this->assertEquals('invalidEventFromRegister', $error['repackage']['params']['event']);
        $this->assertEquals('Piece_FlowInvalidEventFromTransitionActionsOrActivitiesAction', $error['repackage']['params']['class']);
        $this->assertEquals('register', $error['repackage']['params']['method']);

        $GLOBALS['invalidEventFrom'] = 'setupFinish';

        $flow2 = &new Piece_Flow();
        $flow2->configure("{$this->_cacheDirectory}/InvalidEventFromTransitionActionsOrActivities.yaml", null, $this->_cacheDirectory, $this->_cacheDirectory);
        $flow2->setPayload(new stdClass());
        $flow2->start();

        $this->assertEquals('DisplayForm', $flow2->getCurrentStateName());

        $flow2->triggerEvent('foo');

        $this->assertEquals('DisplayForm', $flow2->getCurrentStateName());

        $flow2->triggerEvent('register');

        $this->assertTrue(Piece_Flow_Error::hasErrors('exception'));

        $error = Piece_Flow_Error::pop();

        $this->assertEquals(PIECE_FLOW_ERROR_CANNOT_INVOKE, $error['code']);
        $this->assertEquals(PIECE_FLOW_ERROR_INVALID_EVENT, $error['repackage']['code']);
        $this->assertEquals('invalidEventFromSetupFinish', $error['repackage']['params']['event']);
        $this->assertEquals('Piece_FlowInvalidEventFromTransitionActionsOrActivitiesAction', $error['repackage']['params']['class']);
        $this->assertEquals('setupFinish', $error['repackage']['params']['method']);

        unset($GLOBALS['invalidEventFrom']);
        Piece_Flow_Error::popCallback();
    }

    /**
     * @since Method available since Release 1.4.0
     */
    function testProblemThatActivityIsInvokedTwiceUnexpectedly()
    {
        $flow = &new Piece_Flow();
        $flow->configure("{$this->_cacheDirectory}/ProblemThatActivityIsInvokedTwiceUnexpectedly.yaml", null, $this->_cacheDirectory, $this->_cacheDirectory);
        $flow->setPayload(new stdClass());
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
    function testOmitClassName()
    {
        $this->_assertOmitClassName('.yaml');
        $this->_assertOmitClassName('.xml');
    }

    /**#@-*/

    /**#@+
     * @access private
     */

    function _assertInitialAndFinalActions($source)
    {
        Piece_Flow_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));
        $GLOBALS['initializeCalled'] = false;
        $GLOBALS['finalizeCalled'] = false;

        $flow = &new Piece_Flow();
        $flow->configure("{$this->_cacheDirectory}/$source", null, $this->_cacheDirectory, $this->_cacheDirectory);
        $flow->setPayload(new stdClass());
        $flow->start();

        $this->assertEquals('start', $flow->getView());
        $this->assertTrue($GLOBALS['initializeCalled']);
        $this->assertFalse($GLOBALS['finalizeCalled']);

        $flow->triggerEvent('go');

        $this->assertEquals('end', $flow->getView());
        $this->assertTrue($GLOBALS['finalizeCalled']);

        $flow->triggerEvent('go');

        $this->assertTrue(Piece_Flow_Error::hasErrors('exception'));

        $error = Piece_Flow_Error::pop();

        $this->assertEquals(PIECE_FLOW_ERROR_ALREADY_SHUTDOWN, $error['code']);

        unset($GLOBALS['initializeCalled']);
        unset($GLOBALS['finalizeCalled']);
        Piece_Flow_Error::popCallback();
    }

    /**
     * @since Method available since Release 1.7.0
     */
    function _assertOmitClassName($extension)
    {
        $GLOBALS['initializeCalled'] = false;
        $flow = &new Piece_Flow();
        $flow->configure("{$this->_cacheDirectory}/OmitClassName$extension",
                         null,
                         $this->_cacheDirectory,
                         $this->_cacheDirectory
                         );
        $flow->setPayload(new stdClass());
        $flow->start();

        $this->assertTrue($GLOBALS['initializeCalled']);

        unset($GLOBALS['initializeCalled']);
    }

    /**#@-*/

    // }}}
}

// }}}

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
?>
