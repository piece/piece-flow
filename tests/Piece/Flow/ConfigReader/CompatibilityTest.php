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

require_once 'PHPUnit.php';
require_once 'Piece/Flow/Error.php';
require_once 'Piece/Flow/Config.php';
require_once 'Cache/Lite/File.php';

// {{{ Piece_Flow_ConfigReader_CompatibilityTest

/**
 * The base class for compatibility test of Piece_Flow_Config drivers.
 *
 * @package    Piece_Flow
 * @copyright  2006-2007 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @since      Class available since Release 0.1.0
 */
class Piece_Flow_ConfigReader_CompatibilityTest extends PHPUnit_TestCase
{

    // {{{ properties

    /**#@+
     * @access public
     */

    /**#@-*/

    /**#@+
     * @access private
     */

    var $_config;
    var $_cacheDirectory;

    /**#@-*/

    /**#@+
     * @access public
     */

    function setUp()
    {
        Piece_Flow_Error::pushCallback(create_function('$error', 'var_dump($error); return ' . PEAR_ERRORSTACK_DIE . ';'));
        $name = 'Registration';
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

        $this->_config = new Piece_Flow_Config();
        $this->_config->setName($name);
        $this->_config->setFirstState($firstState);
        $this->_config->setLastState($lastState['name'], $lastState['view']);
        $this->_config->setEntryAction($lastState['name'], $lastState['entry']);
        $this->_config->setExitAction($lastState['name'], $lastState['exit']);
        $this->_config->setActivity($lastState['name'], $lastState['activity']);
        $this->_config->setInitialAction($initial);
        $this->_config->setFinalAction($final);
        $this->_config->addViewState($viewState5['name'], $viewState5['view']);
        $this->_config->setEntryAction($viewState5['name'], $viewState5['entry']);
        $this->_config->setExitAction($viewState5['name'], $viewState5['exit']);
        $this->_config->setActivity($viewState5['name'], $viewState5['activity']);
        $this->_config->addViewState($viewState6['name'], $viewState6['view']);
        $this->_config->addTransition($viewState5['name'],
                                      $transition51['event'],
                                      $transition51['nextState'],
                                      $transition51['action'],
                                      $transition51['guard']
                                      );
        $this->_config->addTransition($viewState6['name'],
                                      $transition61['event'],
                                      $transition61['nextState'],
                                      $transition61['action']
                                      );
        $this->_config->addActionState($actionState1);
        $this->_config->addTransition($actionState1,
                                      $transition11['event'],
                                      $transition11['nextState']
                                      );
        $this->_config->addTransition($actionState1,
                                      $transition12['event'],
                                      $transition12['nextState']
                                      );
        $this->_config->addActionState($actionState7);
        $this->_config->addTransition($actionState7,
                                      $transition71['event'],
                                      $transition71['nextState']
                                      );
        $this->_config->addTransition($actionState7,
                                      $transition72['event'],
                                      $transition72['nextState'],
                                      $transition72['action']
                                      );
        $this->_config->addActionState($actionState2);
        $this->_config->addTransition($actionState2,
                                      $transition21['event'],
                                      $transition21['nextState']
                                      );
        $this->_config->addTransition($actionState2,
                                      $transition22['event'],
                                      $transition22['nextState']
                                      );

        $this->_doSetUp();
    }

    function tearDown()
    {
        $cache = &new Cache_Lite_File(array('cacheDir' => "{$this->_cacheDirectory}/",
                                            'masterFile' => $this->_source,
                                            'automaticSerialization' => true,
                                            'errorHandlingAPIBreak' => true)
                                      );
        $cache->clean();
        $this->_config = null;
        Piece_Flow_Error::clearErrors();
        Piece_Flow_Error::popCallback();
    }

    function testConfiguration()
    {
        $reader = &$this->_getConfigReader($this->_source);
        $config = &$reader->read($this->_cacheDirectory);

        $this->assertEquals(strtolower('Piece_Flow_Config'), strtolower(get_class($config)));
        $this->assertEquals($this->_config->getName(), $config->getName());
        $this->assertEquals($this->_config->getFirstState(),
                            $config->getFirstState()
                            );
        $this->assertEquals($this->_config->getLastState(),
                            $config->getLastState()
                            );
        $this->assertEquals($this->_config->getViewStates(),
                            $config->getViewStates()
                            );
        $this->assertEquals($this->_config->getActionStates(),
                            $config->getActionStates()
                            );
        $this->assertEquals($this->_config->getInitialAction(),
                            $config->getInitialAction()
                            );
        $this->assertEquals($this->_config->getFinalAction(),
                            $config->getFinalAction()
                            );
    }

    /**#@-*/

    /**#@+
     * @access private
     */

    function &_getConfigReader($source) {}
    function _doSetUp() {}
    function _getSource() {}

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
