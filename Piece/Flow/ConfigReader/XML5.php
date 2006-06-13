<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * PHP versions 5
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
 * @link       http://www.php.net/manual/ja/ref.dom.php
 * @link       http://iteman.typepad.jp/piece/
 * @since      File available since Release 0.1.0
 */

require_once 'Piece/Flow/ConfigReader/Common.php';
require_once 'Piece/Flow/Error.php';

// {{{ Piece_Flow_ConfigReader_XML5

/**
 * A Piece_Flow_Config driver for XML under PHP 5.
 *
 * @package    Piece_Flow
 * @author     KUBO Atsuhiro <iteman2002@yahoo.co.jp>
 * @copyright  2006 KUBO Atsuhiro <iteman2002@yahoo.co.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @link       http://www.php.net/manual/ja/ref.dom.php
 * @link       http://iteman.typepad.jp/piece/
 * @since      Class available since Release 0.1.0
 */
class Piece_Flow_ConfigReader_XML5 extends Piece_Flow_ConfigReader_Common
{

    // {{{ properties

    /**#@+
     * @access public
     */

    /**#@-*/

    /**#@+
     * @access private
     */

    var $_schema = '<?xml version="1.0" encoding="UTF-8"?>
<grammar xmlns="http://relaxng.org/ns/structure/1.0" datatypeLibrary="http://www.w3.org/2001/XMLSchema-datatypes">
  <start>
    <ref name="flow"/>
  </start>
  <define name="flow">    
    <element name="flow">
      <attribute name="name">
        <data type="string"/>
      </attribute>
      <optional>
        <ref name="initial"/>
      </optional>
      <optional>
        <ref name="final"/>
      </optional>
      <attribute name="firstState">
        <data type="string"/>
      </attribute>
      <interleave>
        <optional>
          <element name="lastState">
            <attribute name="name">
              <data type="string"/>
            </attribute>
            <attribute name="view">
              <data type="string"/>
            </attribute>
            <interleave>
              <optional>
                <ref name="entry"/>
              </optional>
              <optional>
                <ref name="exit"/>
              </optional>
              <optional>
                <ref name="activity"/>
              </optional>
            </interleave>
          </element>
        </optional>
        <ref name="viewStates"/>
        <ref name="actionStates"/>
      </interleave>
    </element>
  </define>
  <define name="viewStates">
    <oneOrMore>
      <element name="viewState">
        <attribute name="name">
          <data type="string"/>
        </attribute>
        <attribute name="view">
          <data type="string"/>
        </attribute>
        <interleave>
          <optional>
          <ref name="transitions"/>
          </optional>
          <optional>
            <ref name="entry"/>
          </optional>
          <optional>
            <ref name="exit"/>
          </optional>
          <optional>
            <ref name="activity"/>
          </optional>
        </interleave>
      </element>
    </oneOrMore>
  </define>
  <define name="actionStates">
    <zeroOrMore>
      <element name="actionState">
        <attribute name="name">
          <data type="string"/>
        </attribute>
        <interleave>
          <ref name="transitions"/>
          <optional>
            <ref name="entry"/>
          </optional>
          <optional>
            <ref name="exit"/>
          </optional>
          <optional>
            <ref name="activity"/>
          </optional>
        </interleave>
      </element>
    </zeroOrMore>
  </define>
  <define name="transitions">
    <oneOrMore>
      <element name="transition">
        <attribute name="event">
          <data type="string"/>
        </attribute>
        <attribute name="nextState">
          <data type="string"/>
        </attribute>
        <optional>
          <ref name="action"/>
        </optional>
        <optional>
          <ref name="guard"/>
        </optional>
      </element>
    </oneOrMore>
  </define>
  <define name="action">
    <element name="action">
      <ref name="service"/>
    </element>
  </define>
  <define name="guard">
    <element name="guard">
      <ref name="service"/>
    </element>
  </define>
  <define name="service">
    <attribute name="class">
      <data type="string"/>
    </attribute>
    <attribute name="method">
      <data type="string"/>
    </attribute>
  </define>
  <define name="entry">
    <element name="entry">
      <ref name="service"/>
    </element>
  </define>
  <define name="exit">
    <element name="exit">
      <ref name="service"/>
    </element>
  </define>
  <define name="activity">
    <element name="activity">
      <ref name="service"/>
    </element>
  </define>
  <define name="initial">
    <element name="initial">
      <ref name="service"/>
    </element>
  </define>
  <define name="final">
    <element name="final">
      <ref name="service"/>
    </element>
  </define>
</grammar>
';

    /**#@-*/

    /**#@+
     * @access public
     */

    /**#@-*/

    /**#@+
     * @access private
     */

    // }}}
    // {{{ _parseFile()

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
    function _parseFile()
    {
        $dom = DOMDocument::loadXML(file_get_contents($this->_source));
        ob_start();
        $validationResult = $dom->relaxNGValidateSource($this->_schema);
        $contents = ob_get_contents();
        ob_end_clean();
        if (!$validationResult) {
            return Piece_Flow_Error::raiseError(PIECE_FLOW_ERROR_INVALID_FORMAT,
                                                "The file [{$this->_source}] containts invalid format. See below for more details.
$contents"
                                                );
        }

        $element = $dom->getElementsByTagName('flow')->item(0);
        $flow['name'] = $element->getAttribute('name');
        $flow['firstState'] = $element->getAttribute('firstState');

        $lastState = $element->getElementsByTagName('lastState')->item(0);
        if (!is_null($lastState)) {
            $flow['lastState'] = array('name' => $lastState->getAttribute('name'),
                                       'view' => $lastState->getAttribute('view')
                                       );
        }

        $flow['viewState'] =
            $this->_parseViewStates($element->getElementsByTagName('viewState'));
        $flow['actionState'] =
            $this->_parseActionStates($element->getElementsByTagName('actionState'));

        $initial = $element->getElementsByTagName('initial')->item(0);
        if (!is_null($initial)) {
            $flow['initial'] = $this->_parseAction($initial);
        }

        $final = $element->getElementsByTagName('final')->item(0);
        if (!is_null($final)) {
            $flow['final'] = $this->_parseAction($final);
        }

        return $flow;
    }

    // }}}
    // {{{ _parseViewStates()

    /**
     * Parses view states.
     *
     * @param DOMNodeList $states
     * @return array
     */
    function _parseViewStates($states)
    {
        $viewStates = array();

        for ($i = 0; $i < $states->length; ++$i) {
            $state = $states->item($i);
            $viewState = array();
            $viewState['name'] = $state->getAttribute('name');
            $viewState['view'] = $state->getAttribute('view');
            $viewState = array_merge($viewState, $this->_parseState($state));
            array_push($viewStates, $viewState);
        }

        return $viewStates;
    }

    // }}}
    // {{{ _parseActionStates()

    /**
     * Parses action states.
     *
     * @param DOMNodeList $states
     * @return array
     */
    function _parseActionStates($states)
    {
        $actionStates = array();

        for ($i = 0; $i < $states->length; ++$i) {
            $state = $states->item($i);
            $actionState = array();
            $actionState['name'] = $state->getAttribute('name');
            $actionState = array_merge($actionState,
                                       $this->_parseState($state)
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
     * @param DOMElement $state
     * @return array
     */
    function _parseState($state)
    {
        $parsedState = array();

        $parsedTransitions = array();
        $transitions = $state->getElementsByTagName('transition');
        for ($i = 0; $i < $transitions->length; ++$i) {
            $transition = $transitions->item($i);
            $parsedTransition = array();
            $parsedTransition['event'] = $transition->getAttribute('event');
            $parsedTransition['nextState'] =
                $transition->getAttribute('nextState');

            $action = $transition->getElementsByTagName('action')->item(0);
            if (!is_null($action)) {
                $parsedTransition['action'] = $this->_parseAction($action);
            }

            $guard = $transition->getElementsByTagName('guard')->item(0);
            if (!is_null($guard)) {
                $parsedTransition['guard'] = $this->_parseAction($guard);
            }

            array_push($parsedTransitions, $parsedTransition);
        }
        if (count($parsedTransitions)) {
            $parsedState['transition'] = $parsedTransitions;
        }

        $entry = $state->getElementsByTagName('entry')->item(0);
        if (!is_null($entry)) {
            $parsedState['entry'] = $this->_parseAction($entry);
        }

        $exit = $state->getElementsByTagName('exit')->item(0);
        if (!is_null($exit)) {
            $parsedState['exit'] = $this->_parseAction($exit);
        }

        $activity = $state->getElementsByTagName('activity')->item(0);
        if (!is_null($activity)) {
            $parsedState['activity'] = $this->_parseAction($activity);
        }

        return $parsedState;
    }

    // }}}
    // {{{ _parseAction()

    /**
     * Parses the action.
     *
     * @param DOMElement $action
     * @return array
     */
    function _parseAction($action)
    {
        if (is_null($action)) {
            return $action;
        }

        return array('class' => $action->getAttribute('class'),
                     'method' => $action->getAttribute('method')
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
