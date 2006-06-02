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
 * @since      File available since Release 0.1.0
 */

require_once 'Piece/Flow/Error.php';
require_once 'Piece/Flow/Config.php';

// {{{ Piece_Flow_ConfigReader_Common

/**
 * Base class for Piece_Flow_Config drivers.
 *
 * @package    Piece_Flow
 * @author     KUBO Atsuhiro <iteman2002@yahoo.co.jp>
 * @copyright  2006 KUBO Atsuhiro <iteman2002@yahoo.co.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @link       http://iteman.typepad.jp/piece/
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
     * @throws PEAR_ErrorStack
     */
    function &configure($cacheDirectory = null)
    {
        $flow = &$this->parse($cacheDirectory);
        if (Piece_Flow_Error::isError($flow)) {
            return $flow;
        }

        $this->_config = &new Piece_Flow_Config();
        $this->_config->setName($flow['name']);
        $this->_config->setFirstState($flow['firstState']);
        $this->_config->setLastState($flow['lastState']['name'],
                                     $flow['lastState']['view']
                                     );
        $this->_configureViewStates($flow['viewState']);
        $this->_configureActionStates($flow['actionState']);

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
     * @param string $cacheDirectory
     * @return array
     * @throws PEAR_ErrorStack
     */
    function &parse($cacheDirectory)
    {
        $absolutePathOfConfigFile = realpath($this->_source);

        if (!is_readable($absolutePathOfConfigFile)) {
            $error = &Piece_Flow_Error::raiseError(PIECE_FLOW_ERROR_NOT_READABLE,
                                                   "The configuration file [ $absolutePathOfConfigFile ] was not readable."
                                                   );
            return $error;
        }

        if (is_null($cacheDirectory)) {
            $cacheDirectory = './cache';
        }

        $absolutePathOfCacheDirectory = realpath($cacheDirectory);
        if (!$absolutePathOfCacheDirectory) {
            PEAR_ErrorStack::staticPushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));
            Piece_Flow_Error::raiseError(PIECE_FLOW_ERROR_NOT_FOUND,
                                         "The cache directory [ $cacheDirectory ] not found."
                                          );
            PEAR_ErrorStack::staticPopCallback();

            $flow = &$this->_parseFile($absolutePathOfConfigFile);
            return $flow;
        }

        if (!is_readable($absolutePathOfCacheDirectory)
            || !is_writable($absolutePathOfCacheDirectory)
            ) {
            PEAR_ErrorStack::staticPushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));
            Piece_Flow_Error::raiseError(PIECE_FLOW_ERROR_NOT_READABLE,
                                         "The cache directory [ $absolutePathOfCacheDirectory ] was not readable or writable."
                                          );
            PEAR_ErrorStack::staticPopCallback();

            $flow = &$this->_parseFile($absolutePathOfConfigFile);
            return $flow;
        }

        $flow = &$this->_getConfiguration($absolutePathOfCacheDirectory,
                                          $absolutePathOfConfigFile
                                          );

        return $flow;
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
        for ($i = 0; $i < count($states); ++$i) {
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
        for ($i = 0; $i < count($states); ++$i) {
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
        for ($i = 0; $i < count($state['transition']); ++$i) {
            $this->_config->addTransition($state['name'],
                                          $state['transition'][$i]['event'],
                                          $state['transition'][$i]['nextState'],
                                          @$state['transition'][$i]['action'],
                                          @$state['transition'][$i]['guard']
                                          );

        }

        $this->_config->setEntryAction($state['name'], @$state['entry']);
        $this->_config->setExitAction($state['name'], @$state['exit']);
        $this->_config->setActivity($state['name'], @$state['activity']);
    }

    // }}}
    // {{{ _getConfiguration()

    /**
     * Gets a Piece_Flow_Config object from a configuration file or a cache.
     *
     * @param string $cacheDirectory
     * @param string $masterFile
     * @return Piece_Flow_Config
     */
    function &_getConfiguration($cacheDirectory, $masterFile)
    {
        $cache = &new Cache_Lite_File(array('cacheDir' => "$cacheDirectory/",
                                            'masterFile' => $masterFile,
                                            'automaticSerialization' => true,
                                            'errorHandlingAPIBreak' => true)
                                      );
        /*
         * The Cache_Lite class always specifies PEAR_ERROR_RETURN when
         * calling PEAR::raiseError in default.
         */
        $flow = $cache->get($masterFile);
        if (PEAR::isError($flow)) {
            Piece_Unity_Error::raiseError(PIECE_FLOW_ERROR_CANNOT_READ,
                                          "Cannot read the cache file in the directory [ $cacheDirectory ]."
                                          );
            $flow = &$this->_parseFile($masterFile);
            return $flow;
        }

        if (!$flow) {
            $flow = &$this->_parseFile($masterFile);
            if (Piece_Flow_Error::isError($flow)) {
                return $flow;
            }

            $result = $cache->save($flow);
            if (PEAR::isError($result)) {
                Piece_Unity_Error::raiseError(PIECE_FLOW_ERROR_CANNOT_WRITE,
                                              "Cannot write the Piece_Unity_Flow object to the cache file in the directory [ $cacheDirectory ]."
                                              );
                $flow = &$this->_parseFile($masterFile);
                return $flow;
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
     * @throws PEAR_ErrorStack
     */
    function &_parseFile() {}

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
