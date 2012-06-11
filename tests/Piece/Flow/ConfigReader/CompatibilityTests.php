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

namespace Piece\Flow\ConfigReader;

require_once 'Cache/Lite/File.php';

use Piece\Flow\Config;
use Piece\Flow\Util\ErrorReporting;

/**
 * The base class for compatibility test of Config drivers.
 *
 * @package    Piece_Flow
 * @copyright  2006-2008, 2012 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version    Release: @package_version@
 * @since      Class available since Release 0.1.0
 */
abstract class CompatibilityTests extends \PHPUnit_Framework_TestCase
{
    protected $cacheDirectory;

    protected function setUp()
    {
        $this->doSetUp();
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

    public function testConfiguration()
    {
        $firstState = 'DisplayForm';
        $lastState = array('name' => 'Finish', 'view' => 'Finish',
                           'entry' =>
                           array('class' => 'Piece_FlowTestCaseAction',
                                 'method' => 'setupForm'),
                           'exit' =>
                           array('class' => 'Piece_FlowTestCaseAction',
                                 'method' => 'teardownForm'),
                           'activity' =>
                           array('class' => 'Piece_FlowTestCaseAction',
                                 'method' => 'countDisplay')
                           );
        $initial = array('class' => 'Piece_FlowTestCaseAction',
                         'method' => 'initialize'
                         );
        $final = array('class' => 'Piece_FlowTestCaseAction',
                       'method' => 'finalize'
                       );

        $viewState5 = array('name' => 'DisplayForm', 'view' => 'Form',
                            'entry' =>
                            array('class' => 'Piece_FlowTestCaseAction',
                                  'method' => 'setupForm'),
                            'exit' =>
                            array('class' => 'Piece_FlowTestCaseAction',
                                  'method' => 'teardownForm'),
                            'activity' =>
                            array('class' => 'Piece_FlowTestCaseAction',
                                  'method' => 'countDisplay')
                            );
        $transition51 = array('event' => 'submit',
                              'nextState' => 'processSubmitDisplayForm',
                              'action' =>
                              array('class' => 'Piece_FlowTestCaseAction',
                                    'method' => 'validateInput'),
                              'guard' =>
                              array('class' => 'Piece_FlowTestCaseAction',
                                    'method' => 'isPermitted')
                              );

        $viewState6 = array('name' => 'ConfirmForm', 'view' => 'Confirmation');
        $transition61 = array('event' => 'submit',
                              'nextState' => 'processSubmitConfirmForm',
                              'action' =>
                              array('class' => 'Piece_FlowTestCaseAction',
                                    'method' => 'validateConfirmation')
                              );

        $actionState1 = 'processSubmitDisplayForm';
        $transition11 = array('event' => 'raiseError',
                              'nextState' => 'DisplayForm'
                              );
        $transition12 = array('event' => 'succeed',
                              'nextState' => 'ConfirmForm'
                              );

        $actionState7 = 'processSubmitConfirmForm';
        $transition71 = array('event' => 'raiseError',
                              'nextState' => 'DisplayForm'
                              );
        $transition72 = array('event' => 'succeed',
                              'nextState' => 'Register',
                              'action' =>
                              array('class' => 'Piece_FlowTestCaseAction',
                                    'method' => 'register')
                              );

        $actionState2 = 'Register';
        $transition21 = array('event' => 'raiseError',
                              'nextState' => 'DisplayForm'
                              );
        $transition22 = array('event' => 'succeed',
                              'nextState' => 'Finish'
                              );

        $expectedConfig = new Config();
        $expectedConfig->setFirstState($firstState);
        $expectedConfig->setLastState($lastState['name'], $lastState['view']);
        $expectedConfig->setEntryAction($lastState['name'], $lastState['entry']);
        $expectedConfig->setExitAction($lastState['name'], $lastState['exit']);
        $expectedConfig->setActivity($lastState['name'], $lastState['activity']);
        $expectedConfig->setInitialAction($initial);
        $expectedConfig->setFinalAction($final);
        $expectedConfig->addViewState($viewState5['name'], $viewState5['view']);
        $expectedConfig->setEntryAction($viewState5['name'], $viewState5['entry']);
        $expectedConfig->setExitAction($viewState5['name'], $viewState5['exit']);
        $expectedConfig->setActivity($viewState5['name'], $viewState5['activity']);
        $expectedConfig->addViewState($viewState6['name'], $viewState6['view']);
        $expectedConfig->addTransition($viewState5['name'],
                                       $transition51['event'],
                                       $transition51['nextState'],
                                       $transition51['action'],
                                       $transition51['guard']
                                       );
        $expectedConfig->addTransition($viewState6['name'],
                                       $transition61['event'],
                                       $transition61['nextState'],
                                       $transition61['action']
                                       );
        $expectedConfig->addActionState($actionState1);
        $expectedConfig->addTransition($actionState1,
                                       $transition11['event'],
                                       $transition11['nextState']
                                       );
        $expectedConfig->addTransition($actionState1,
                                       $transition12['event'],
                                       $transition12['nextState']
                                       );
        $expectedConfig->addActionState($actionState7);
        $expectedConfig->addTransition($actionState7,
                                       $transition71['event'],
                                       $transition71['nextState']
                                       );
        $expectedConfig->addTransition($actionState7,
                                       $transition72['event'],
                                       $transition72['nextState'],
                                       $transition72['action']
                                       );
        $expectedConfig->addActionState($actionState2);
        $expectedConfig->addTransition($actionState2,
                                       $transition21['event'],
                                       $transition21['nextState']
                                       );
        $expectedConfig->addTransition($actionState2,
                                       $transition22['event'],
                                       $transition22['nextState']
                                       );

        $reader = $this->createConfigReader("{$this->cacheDirectory}/Registration" . $this->getExtension());
        $actualConfig = $reader->read();

        $this->assertTrue($actualConfig instanceof Config);
        $this->assertEquals($expectedConfig->getFirstState(), $actualConfig->getFirstState());
        $this->assertEquals($expectedConfig->getLastState(), $actualConfig->getLastState());
        $this->assertEquals($expectedConfig->getViewStates(), $actualConfig->getViewStates());
        $this->assertEquals($expectedConfig->getActionStates(), $actualConfig->getActionStates());
        $this->assertEquals($expectedConfig->getInitialAction(), $actualConfig->getInitialAction());
        $this->assertEquals($expectedConfig->getFinalAction(), $actualConfig->getFinalAction());
    }

    /**
     * @since Method available since Release 1.10.0
     */
    public function testExceptionShouldBeRaisedIfInvalidFormatIsDetected()
    {
        $this->assertExceptionShouldBeRaisedIfInvalidFormatIsDetected('FirstStateNotFound');
        $this->assertExceptionShouldBeRaisedIfInvalidFormatIsDetected('FirstStateIsInvalid');
        $this->assertExceptionShouldBeRaisedIfInvalidFormatIsDetected('NameInLastStateNotFound');
        $this->assertExceptionShouldBeRaisedIfInvalidFormatIsDetected('NameInLastStateIsInvalid');
        $this->assertExceptionShouldBeRaisedIfInvalidFormatIsDetected('ViewInLastStateNotFound');
        $this->assertExceptionShouldBeRaisedIfInvalidFormatIsDetected('ViewInLastStateIsInvalid');
        $this->assertExceptionShouldBeRaisedIfInvalidFormatIsDetected('MethodInFinalActionNotFound');
        $this->assertExceptionShouldBeRaisedIfInvalidFormatIsDetected('MethodInFinalActionIsInvalid');
        $this->assertExceptionShouldBeRaisedIfInvalidFormatIsDetected('ClassInFinalActionIsInvalid');
        $this->assertExceptionShouldBeRaisedIfInvalidFormatIsDetected('MethodInInitialActionNotFound');
        $this->assertExceptionShouldBeRaisedIfInvalidFormatIsDetected('MethodInInitialActionIsInvalid');
        $this->assertExceptionShouldBeRaisedIfInvalidFormatIsDetected('ClassInInitialActionIsInvalid');
        $this->assertExceptionShouldBeRaisedIfInvalidFormatIsDetected('ViewStateHasNoElements');
        $this->assertExceptionShouldBeRaisedIfInvalidFormatIsDetected('NameInViewStateNotFound');
        $this->assertExceptionShouldBeRaisedIfInvalidFormatIsDetected('NameInViewStateIsInvalid');
        $this->assertExceptionShouldBeRaisedIfInvalidFormatIsDetected('ViewInViewStateNotFound');
        $this->assertExceptionShouldBeRaisedIfInvalidFormatIsDetected('ViewInViewStateIsInvalid');
        $this->assertExceptionShouldBeRaisedIfInvalidFormatIsDetected('NameInActionStateNotFound');
        $this->assertExceptionShouldBeRaisedIfInvalidFormatIsDetected('NameInActionStateIsInvalid');
        $this->assertExceptionShouldBeRaisedIfInvalidFormatIsDetected('EventInTransitionNotFound');
        $this->assertExceptionShouldBeRaisedIfInvalidFormatIsDetected('EventInTransitionIsInvalid');
        $this->assertExceptionShouldBeRaisedIfInvalidFormatIsDetected('NextStateInTransitionNotFound');
        $this->assertExceptionShouldBeRaisedIfInvalidFormatIsDetected('NextStateInTransitionIsInvalid');
        $this->assertExceptionShouldBeRaisedIfInvalidFormatIsDetected('MethodInActionNotFound');
        $this->assertExceptionShouldBeRaisedIfInvalidFormatIsDetected('MethodInActionIsInvalid');
        $this->assertExceptionShouldBeRaisedIfInvalidFormatIsDetected('ClassInActionIsInvalid');
        $this->assertExceptionShouldBeRaisedIfInvalidFormatIsDetected('MethodInGuardNotFound');
        $this->assertExceptionShouldBeRaisedIfInvalidFormatIsDetected('MethodInGuardIsInvalid');
        $this->assertExceptionShouldBeRaisedIfInvalidFormatIsDetected('ClassInGuardIsInvalid');
        $this->assertExceptionShouldBeRaisedIfInvalidFormatIsDetected('MethodInEntryNotFound');
        $this->assertExceptionShouldBeRaisedIfInvalidFormatIsDetected('MethodInEntryIsInvalid');
        $this->assertExceptionShouldBeRaisedIfInvalidFormatIsDetected('ClassInEntryIsInvalid');
        $this->assertExceptionShouldBeRaisedIfInvalidFormatIsDetected('MethodInExitNotFound');
        $this->assertExceptionShouldBeRaisedIfInvalidFormatIsDetected('MethodInExitIsInvalid');
        $this->assertExceptionShouldBeRaisedIfInvalidFormatIsDetected('ClassInExitIsInvalid');
        $this->assertExceptionShouldBeRaisedIfInvalidFormatIsDetected('MethodInActivityNotFound');
        $this->assertExceptionShouldBeRaisedIfInvalidFormatIsDetected('MethodInActivityIsInvalid');
        $this->assertExceptionShouldBeRaisedIfInvalidFormatIsDetected('ClassInActivityIsInvalid');
    }

    /**
     * @since Method available since Release 1.14.0
     */
    function testCacheIDsShouldUniqueInOneCacheDirectory()
    {
        $oldDirectory = getcwd();
        chdir("{$this->cacheDirectory}/CacheIDsShouldBeUniqueInOneCacheDirectory1");
        $reader = $this->createConfigReader('New' . $this->getExtension());
        $reader->read();

        $this->assertEquals(1, $this->getCacheFileCount($this->cacheDirectory));

        chdir("{$this->cacheDirectory}/CacheIDsShouldBeUniqueInOneCacheDirectory2");
        $reader = $this->createConfigReader('New' . $this->getExtension());
        $reader->read();

        $this->assertEquals(2, $this->getCacheFileCount($this->cacheDirectory));

        chdir($oldDirectory);
    }

    abstract protected function createConfigReader($source);
    abstract protected function doSetUp();

    protected function getSource($name)
    {
    }

    /**
     * @param string $name
     */
    protected function assertExceptionShouldBeRaisedIfInvalidFormatIsDetected($name)
    {
        try {
            $this->createConfigReader("{$this->cacheDirectory}/$name" . $this->getExtension())->read();
            $this->fail('An expected exception has not been raised.');
        } catch (InvalidFormatException $e) {
        }
    }

    /**
     * @since Method available since Release 1.14.0
     */
    protected function getCacheFileCount($directory)
    {
        $cacheFileCount = 0;
        if ($dh = opendir($directory)) {
            while (true) {
                $file = readdir($dh);
                if ($file === false) {
                    break;
                }

                if (filetype("$directory/$file") == 'file') {
                    if (preg_match('/^cache_.+/', $file)) {
                        ++$cacheFileCount;
                    }
                }
            }

            closedir($dh);
        }

        return $cacheFileCount;
    }

    /**
     * @since Method available since Release 1.14.0
     */
    abstract protected function getExtension();
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
