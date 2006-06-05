<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * PHP versions 4 and 5
 *
 * Copyright (c) 2006, KUBO Atsuhiro <iteman2002@yahoo.co.jp>
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
 * @author     KUBO Atsuhiro <iteman2002@yahoo.co.jp>
 * @copyright  2006 KUBO Atsuhiro <iteman2002@yahoo.co.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    SVN: $Id$
 * @link       http://iteman.typepad.jp/piece/
 * @see        Piece_Flow_ConfigReader_Common
 * @since      File available since Release 0.1.0
 */

require_once 'PHPUnit.php';
require_once 'Piece/Flow/Config.php';

// {{{ Piece_Flow_ConfigReader_CompatibilityTest

/**
 * Base class for compatibility test of Piece_Flow_Config drivers.
 *
 * @package    Piece_Flow
 * @author     KUBO Atsuhiro <iteman2002@yahoo.co.jp>
 * @copyright  2006 KUBO Atsuhiro <iteman2002@yahoo.co.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @link       http://iteman.typepad.jp/piece/
 * @see        Piece_Flow_ConfigReader_Common
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
    var $_source;

    /**#@-*/

    /**#@+
     * @access public
     */

    function setUp()
    {
        PEAR_ErrorStack::staticPushCallback(create_function('$error', 'var_dump($error); return ' . PEAR_ERRORSTACK_DIE . ';'));
        $name = 'registrationFlow';
        $firstState = 'displaying';
        $lastState = array('name' => 'finishing', 'view' => 'finish');
        $viewState1 = array('name' => 'displaying', 'view' => 'input',
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
        $transition1 = array('event' => 'submit',
                             'nextState' => 'validated',
                             'action' =>
                             array('class' => 'Piece_FlowTestCaseAction',
                                   'method' => 'validate'),
                             'guard' =>
                             array('class' => 'Piece_FlowTestCaseAction',
                                   'method' => 'isPermitted')
                             );
        $viewState2 = array('name' => 'confirming', 'view' => 'confirmation');
        $transition2 = array('event' => 'submit',
                             'nextState' => 'validated',
                             'action' =>
                             array('class' => 'Piece_FlowTestCaseAction',
                                   'method' => 'validate')
                             );
        $actionState1 = 'validated';
        $transition3 = array('event' => 'raiseError',
                             'nextState' => 'displaying'
                             );
        $transition4 = array('event' => 'succeedInValidatingViaDisplaying',
                             'nextState' => 'confirming'
                             );
        $transition5 = array('event' => 'succeedInValidatingViaConfirming',
                             'nextState' => 'registered',
                             'action' =>
                             array('class' => 'Piece_FlowTestCaseAction',
                                   'method' => 'register')
                             );
        $actionState2 = 'registered';
        $transition6 = array('event' => 'raiseError',
                             'nextState' => 'displaying'
                             );
        $transition7 = array('event' => 'succeed',
                             'nextState' => 'finishing'
                             );
                                  
        $this->_config = new Piece_Flow_Config();
        $this->_config->setName($name);
        $this->_config->setFirstState($firstState);
        $this->_config->setLastState($lastState['name'], $lastState['view']);
        $this->_config->addViewState($viewState1['name'], $viewState1['view']);
        $this->_config->setEntryAction($viewState1['name'], $viewState1['entry']);
        $this->_config->setExitAction($viewState1['name'], $viewState1['exit']);
        $this->_config->setActivity($viewState1['name'], $viewState1['activity']);
        $this->_config->addViewState($viewState2['name'], $viewState2['view']);
        $this->_config->addTransition($viewState1['name'],
                                      $transition1['event'],
                                      $transition1['nextState'],
                                      $transition1['action'],
                                      $transition1['guard']
                                      );
        $this->_config->addTransition($viewState2['name'],
                                      $transition2['event'],
                                      $transition2['nextState'],
                                      $transition1['action']
                                      );
        $this->_config->addActionState($actionState1);
        $this->_config->addTransition($actionState1,
                                      $transition3['event'],
                                      $transition3['nextState']
                                      );
        $this->_config->addTransition($actionState1,
                                      $transition4['event'],
                                      $transition4['nextState']
                                      );
        $this->_config->addTransition($actionState1,
                                      $transition5['event'],
                                      $transition5['nextState'],
                                      $transition5['action']
                                      );
        $this->_config->addActionState($actionState2);
        $this->_config->addTransition($actionState2,
                                      $transition6['event'],
                                      $transition6['nextState']
                                      );
        $this->_config->addTransition($actionState2,
                                      $transition7['event'],
                                      $transition7['nextState']
                                      );
    }

    function tearDown()
    {
        $cache = &new Cache_Lite_File(array('cacheDir' => dirname(__FILE__) . '/',
                                            'masterFile' => $this->_source,
                                            'automaticSerialization' => true,
                                            'errorHandlingAPIBreak' => true)
                                      );
        $cache->clean();
        $this->_source = null;
        $this->_config = null;
        PEAR_ErrorStack::staticPopCallback();
    }

    function testConfiguration()
    {
        $config = $this->getConfig();

        $this->assertTrue(is_a($config, 'Piece_Flow_Config'));
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
    }

    function getConfig() {}

    /**#@-*/

    /**#@+
     * @access private
     */

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
