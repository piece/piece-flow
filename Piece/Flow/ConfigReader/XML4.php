<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * PHP versions 4
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
 * @link       http://www.php.net/manual/ja/ref.domxml.php
 * @link       http://iteman.typepad.jp/piece/
 * @since      File available since Release 0.1.0
 */

require_once 'Piece/Flow/ConfigReader/File.php';

// {{{ Piece_Flow_ConfigReader_XML4

/**
 * A Piece_Flow_Config driver for XML under PHP 4.
 *
 * @package    Piece_Flow
 * @author     KUBO Atsuhiro <iteman2002@yahoo.co.jp>
 * @copyright  2006 KUBO Atsuhiro <iteman2002@yahoo.co.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @link       http://www.php.net/manual/ja/ref.domxml.php
 * @link       http://iteman.typepad.jp/piece/
 * @since      Class available since Release 0.1.0
 */
class Piece_Flow_ConfigReader_XML4 extends Piece_Flow_ConfigReader_File
{

    // {{{ properties

    /**#@+
     * @access public
     */

    /**#@-*/

    /**#@+
     * @access private
     */

    /**#@-*/

    /**#@+
     * @access public
     */

    // }}}
    // {{{ parseFile()

    /**
     * Parses the given file and returns an array which represent a flow
     * structure.
     *
     * This method is to be overriden by the appropriate driver for the given
     * file.
     *
     * @return array
     * @throws PEAR_ErrorStack
     */
    function parseFile()
    {
        ob_start();
        $dom = domxml_open_mem(file_get_contents($this->_source));
        $contents = ob_get_contents();
        ob_end_clean();
        if (!$dom) {
            return Piece_Flow_Error::raiseError(PIECE_FLOW_ERROR_INVALID_FORMAT,
                                                "File [{$this->_source}] containts invalid format. See below for more details.
$contents"
                                                );
        }

        $element = $dom->document_element();
        $flow['name'] = $element->get_attribute('name');
        $flow['firstState'] = $element->get_attribute('firstState');
        $lastState = $element->get_elements_by_tagname('lastState');
        $flow['lastState'] = array('name' => $lastState[0]->get_attribute('name'),
                                   'view' => $lastState[0]->get_attribute('view')
                                   );
        $viewStates = $element->get_elements_by_tagname('viewState');
        $flow['viewState'] = $this->_parseViewStates($viewStates);
        $actionState = $element->get_elements_by_tagname('actionState');
        $flow['actionState'] = $this->_parseActionStates($actionState);

        return $flow;
    }

    /**#@-*/

    /**#@+
     * @access private
     */

    // }}}
    // {{{ _parseViewStates()

    /**
     * Parses view states.
     *
     * @param array $states
     * @return array
     */
    function _parseViewStates($states)
    {
        $viewStates = array();

        for ($i = 0; $i < count($states); ++$i) {
            $viewState = array();
            $viewState['name'] = $states[$i]->get_attribute('name');
            $viewState['view'] = $states[$i]->get_attribute('view');
            $viewState = array_merge($viewState,
                                     $this->_parseState($states[$i])
                                     );
            array_push($viewStates, $viewState);
        }

        return $viewStates;
    }

    // }}}
    // {{{ _parseActionStates()

    /**
     * Parses action states.
     *
     * @param array $states
     * @return array
     */
    function _parseActionStates($states)
    {
        $actionStates = array();

        for ($i = 0; $i < count($states); ++$i) {
            $actionState = array();
            $actionState['name'] = $states[$i]->get_attribute('name');
            $actionState = array_merge($actionState,
                                       $this->_parseState($states[$i])
                                       );
            array_push($actionStates, $actionState);
        }

        return $actionStates;
    }

    // }}}
    // {{{ _parseState()

    /**
     * Parses the state.
     *
     * @param DomElement $state
     * @return array
     */
    function _parseState($state)
    {
        $parsedState = array();

        $parsedTransitions = array();
        $transitions = $state->get_elements_by_tagname('transition');
        for ($i = 0; $i < count($transitions); ++$i) {
            $parsedTransition = array();
            $parsedTransition['event'] =
                $transitions[$i]->get_attribute('event');
            $parsedTransition['nextState'] =
                $transitions[$i]->get_attribute('nextState');
            $action = $transitions[$i]->get_elements_by_tagname('action');
            $parsedTransition['action'] = $this->_parseAction(@$action[0]);
            $guard = $transitions[$i]->get_elements_by_tagname('guard');
            $parsedTransition['guard'] = $this->_parseAction(@$guard[0]);
            array_push($parsedTransitions, $parsedTransition);
        }
        if (count($parsedTransitions)) {
            $parsedState['transition'] = $parsedTransitions;
        }

        $entry = $state->get_elements_by_tagname('entry');
        $parsedState['entry'] = $this->_parseAction(@$entry[0]);
        $exit = $state->get_elements_by_tagname('exit');
        $parsedState['exit'] = $this->_parseAction(@$exit[0]);
        $activity = $state->get_elements_by_tagname('activity');
        $parsedState['activity'] = $this->_parseAction(@$activity[0]);

        return $parsedState;
    }

    // }}}
    // {{{ _parseAction()

    /**
     * Parses the action.
     *
     * @param DomElement $action
     * @return array
     */
    function _parseAction($action)
    {
        if (is_null($action)) {
            return $action;
        }

        return array('class' => $action->get_attribute('class'),
                     'method' => $action->get_attribute('method')
                     );
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