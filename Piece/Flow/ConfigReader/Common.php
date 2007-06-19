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

require_once 'Piece/Flow/Config.php';
require_once 'Piece/Flow/Error.php';
require_once 'Cache/Lite/File.php';
require_once 'PEAR.php';

// {{{ Piece_Flow_ConfigReader_Common

/**
 * The base class for Piece_Flow_Config drivers.
 *
 * @package    Piece_Flow
 * @copyright  2006-2007 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @since      Class available since Release 0.1.0
 */
class Piece_Flow_ConfigReader_Common
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

    // }}}
    // {{{ constructor

    /**
     * Constructor
     *
     * @param mixed $source
     */
    function Piece_Flow_ConfigReader_Common($source)
    {
        $this->_source = $source;
    }

    // }}}
    // {{{ configure()

    /**
     * Configures a Piece_Flow_Config object from the given source.
     *
     * @param string $cacheDirectory
     * @return Piece_Flow_Config
     * @throws PIECE_FLOW_ERROR_NOT_FOUND
     * @throws PIECE_FLOW_ERROR_NOT_READABLE
     * @throws PIECE_FLOW_ERROR_INVALID_FORMAT
     */
    function &configure($cacheDirectory = null)
    {
        $this->_cacheDirectory = $cacheDirectory;
        $flow = $this->parse();
        if (Piece_Flow_Error::hasErrors('exception')) {
            $return = null;
            return $return;
        }

        $this->_config = &new Piece_Flow_Config();
        $this->_config->setName($this->_getFlowNameFromSource());
        $this->_config->setFirstState($flow['firstState']);

        if (array_key_exists('lastState', $flow)) {
            $this->_config->setLastState($flow['lastState']['name'],
                                         $flow['lastState']['view']
                                         );
            $this->_configureState($flow['lastState']);
        }

        $this->_configureViewStates($flow['viewState']);

        if (array_key_exists('actionState', $flow)) {
            $this->_configureActionStates($flow['actionState']);
        }

        if (array_key_exists('initial', $flow)) {
            $this->_config->setInitialAction($flow['initial']);
        }

        if (array_key_exists('final', $flow)) {
            $this->_config->setFinalAction($flow['final']);
        }

        return $this->_config;
    }

    // }}}
    // {{{ parse()

    /**
     * Parses the given source and returns an array which represent a flow
     * structure.
     *
     * This method is to be overriden by the appropriate driver for the given
     * source.
     *
     * @return array
     * @throws PIECE_FLOW_ERROR_NOT_FOUND
     * @throws PIECE_FLOW_ERROR_NOT_READABLE
     * @throws PIECE_FLOW_ERROR_INVALID_FORMAT
     */
    function parse()
    {
        if (!file_exists($this->_source)) {
            Piece_Flow_Error::push(PIECE_FLOW_ERROR_NOT_FOUND,
                                   "The configuration file [ {$this->_source} ] not found."
                                   );
            return;
        }

        if (!is_readable($this->_source)) {
            Piece_Flow_Error::push(PIECE_FLOW_ERROR_NOT_READABLE,
                                   "The configuration file [ {$this->_source} ] is not readable."
                                   );
            return;
        }

        if (is_null($this->_cacheDirectory)) {
            $this->_cacheDirectory = './cache';
        }

        if (!file_exists($this->_cacheDirectory)) {
            Piece_Flow_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));
            Piece_Flow_Error::push(PIECE_FLOW_ERROR_NOT_FOUND,
                                   "The cache directory [ {$this->_cacheDirectory} ] not found.",
                                   'warning'
                                   );
            Piece_Flow_Error::popCallback();

            return $this->_parseFile();
        }

        if (!is_readable($this->_cacheDirectory)
            || !is_writable($this->_cacheDirectory)
            ) {
            Piece_Flow_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));
            Piece_Flow_Error::push(PIECE_FLOW_ERROR_NOT_READABLE,
                                   "The cache directory [ {$this->_cacheDirectory} ] is not readable or writable.",
                                   'warning'
                                   );
            Piece_Flow_Error::popCallback();

            return $this->_parseFile();
        }

        return $this->_getConfiguration();
    }

    /**#@-*/

    /**#@+
     * @access private
     */

    // }}}
    // {{{ _configureViewStates()

    /**
     * Configures view states.
     *
     * @param array $states
     */
    function _configureViewStates($states)
    {
        for ($i = 0, $count = count($states); $i < $count; ++$i) {
            $this->_config->addViewState($states[$i]['name'],
                                         $states[$i]['view']
                                         );
            $this->_configureState($states[$i]);
        }
    }

    // }}}
    // {{{ _configureActionStates()

    /**
     * Configures action states.
     *
     * @param array $states
     */
    function _configureActionStates($states)
    {
        for ($i = 0, $count = count($states); $i < $count; ++$i) {
            $this->_config->addActionState($states[$i]['name']);
            $this->_configureState($states[$i]);
        }
    }

    // }}}
    // {{{ _configureState()

    /**
     * Configures the state.
     *
     * @param array $state
     */
    function _configureState($state)
    {
        if (array_key_exists('transition', $state)) {
            for ($i = 0, $count = count($state['transition']); $i < $count; ++$i) {
                if (!array_key_exists('action', $state['transition'][$i])) {
                    $state['transition'][$i]['action'] = null;
                }

                if (!array_key_exists('guard', $state['transition'][$i])) {
                    $state['transition'][$i]['guard'] = null;
                }

                $this->_config->addTransition($state['name'],
                                              $state['transition'][$i]['event'],
                                              $state['transition'][$i]['nextState'],
                                              $state['transition'][$i]['action'],
                                              $state['transition'][$i]['guard']
                                              );

            }
        }

        if (array_key_exists('entry', $state)) {
            $this->_config->setEntryAction($state['name'], $state['entry']);
        }

        if (array_key_exists('exit', $state)) {
            $this->_config->setExitAction($state['name'], $state['exit']);
        }

        if (array_key_exists('activity', $state)) {
            $this->_config->setActivity($state['name'], $state['activity']);
        }
    }

    // }}}
    // {{{ _getConfiguration()

    /**
     * Gets a Piece_Flow_Config object from a configuration file or a cache.
     *
     * @return array
     * @throws PIECE_FLOW_ERROR_INVALID_FORMAT
     */
    function _getConfiguration()
    {
        $cache = &new Cache_Lite_File(array('cacheDir' => "{$this->_cacheDirectory}/",
                                            'masterFile' => $this->_source,
                                            'automaticSerialization' => true,
                                            'errorHandlingAPIBreak' => true)
                                      );
        /*
         * The Cache_Lite class always specifies PEAR_ERROR_RETURN when
         * calling PEAR::raiseError in default.
         */
        $flow = $cache->get($this->_source);
        if (PEAR::isError($flow)) {
            Piece_Flow_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));
            Piece_Flow_Error::push(PIECE_FLOW_ERROR_CANNOT_READ,
                                   "Cannot read the cache file in the directory [ {$this->_cacheDirectory} ].",
                                   'warning'
                                   );
            Piece_Flow_Error::popCallback();
            return $this->_parseFile();
        }

        if (!$flow) {
            $flow = $this->_parseFile();
            if (Piece_Flow_Error::hasErrors('exception')) {
                return;
            }

            $result = $cache->save($flow);
            if (PEAR::isError($result)) {
                Piece_Flow_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));
                Piece_Flow_Error::push(PIECE_FLOW_ERROR_CANNOT_WRITE,
                                       "Cannot write the Piece_Flow object to the cache file in the directory [ {$this->_cacheDirectory} ].",
                                       'warning'
                                       );
                Piece_Flow_Error::popCallback();
            }
        }

        return $flow;
    }

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
     * @throws PIECE_FLOW_ERROR_INVALID_FORMAT
     */
    function _parseFile() {}

    // }}}
    // {{{ _getFlowNameFromSource()

    /**
     * Gets the flow name from the current source.
     *
     * @return string
     */
    function _getFlowNameFromSource()
    {
        $name = basename($this->_source);
        $positionOfExtension = strrpos($name, '.');
        if ($positionOfExtension !== false) {
            return substr($name, 0, $positionOfExtension);
        }

        return $name;
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
