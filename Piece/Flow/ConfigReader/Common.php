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
require_once 'Piece/Flow/Env.php';

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
     * @param mixed  $source
     * @param string $cacheDirectory
     */
    function Piece_Flow_ConfigReader_Common($source, $cacheDirectory)
    {
        $this->_source = $source;
        $this->_cacheDirectory = $cacheDirectory;
    }

    // }}}
    // {{{ read()

    /**
     * Reads configuration from the given source and creates
     * a Piece_Flow_Config object.
     *
     * @return Piece_Flow_Config
     * @throws PIECE_FLOW_ERROR_NOT_FOUND
     * @throws PIECE_FLOW_ERROR_NOT_READABLE
     * @throws PIECE_FLOW_ERROR_INVALID_FORMAT
     */
    function &read()
    {
        if (!file_exists($this->_source)) {
            Piece_Flow_Error::push(PIECE_FLOW_ERROR_NOT_FOUND,
                                   "The configuration file [ {$this->_source} ] not found."
                                   );
            $return = null;
            return $return;
        }

        if (!is_readable($this->_source)) {
            Piece_Flow_Error::push(PIECE_FLOW_ERROR_NOT_READABLE,
                                   "The configuration file [ {$this->_source} ] is not readable."
                                   );
            $return = null;
            return $return;
        }

        if (is_null($this->_cacheDirectory)) {
            return $this->_createConfigurationFromSource();
        }

        if (!file_exists($this->_cacheDirectory)) {
            Piece_Flow_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));
            Piece_Flow_Error::push(PIECE_FLOW_ERROR_NOT_FOUND,
                                   "The cache directory [ {$this->_cacheDirectory} ] not found.",
                                   'warning'
                                   );
            Piece_Flow_Error::popCallback();

            return $this->_createConfigurationFromSource();
        }

        if (!is_readable($this->_cacheDirectory) || !is_writable($this->_cacheDirectory)) {
            Piece_Flow_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));
            Piece_Flow_Error::push(PIECE_FLOW_ERROR_NOT_READABLE,
                                   "The cache directory [ {$this->_cacheDirectory} ] is not readable or writable.",
                                   'warning'
                                   );
            Piece_Flow_Error::popCallback();

            return $this->_createConfigurationFromSource();
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
     * @throws PIECE_FLOW_ERROR_INVALID_FORMAT
     */
    function _configureViewStates($states)
    {
        if (is_null($states)) {
            Piece_Flow_Error::push(PIECE_FLOW_ERROR_INVALID_FORMAT,
                                   "The \"viewState\" element is required in the flow definition file [ {$this->_source} ]."
                                   );
            return;
        }

        if (!is_array($states)) {
            Piece_Flow_Error::push(PIECE_FLOW_ERROR_INVALID_FORMAT,
                                   "The \"viewState\" element is invalid in the flow definition file [ {$this->_source} ]."
                                   );
            return;
        }

        if (!count($states)) {
            Piece_Flow_Error::push(PIECE_FLOW_ERROR_INVALID_FORMAT,
                                   "The \"viewState\" element requires one or more child elements in the flow definition file [ {$this->_source} ]."
                                   );
            return;
        }

        for ($i = 0, $count = count($states); $i < $count; ++$i) {
            if (@!array_key_exists('name', $states[$i])) {
                Piece_Flow_Error::push(PIECE_FLOW_ERROR_INVALID_FORMAT,
                                       "The \"name\" element in the \"viewState\" element is required in the flow definition file [ {$this->_source} ]."
                                       );
                return;
            }

            if (is_null($states[$i]['name']) || !strlen($states[$i]['name'])) {
                Piece_Flow_Error::push(PIECE_FLOW_ERROR_INVALID_FORMAT,
                                       "The \"name\" element in the \"viewState\" element is invalid in the flow definition file [ {$this->_source} ]."
                                       );
                return;
            }

            if (!array_key_exists('view', $states[$i])) {
                Piece_Flow_Error::push(PIECE_FLOW_ERROR_INVALID_FORMAT,
                                       "The \"view\" element in the \"viewState\" element is required in the flow definition file [ {$this->_source} ]."
                                       );
                return;
            }

            if (is_null($states[$i]['view']) || !strlen($states[$i]['view'])) {
                Piece_Flow_Error::push(PIECE_FLOW_ERROR_INVALID_FORMAT,
                                       "The \"view\" element in the \"viewState\" element is invalid in the flow definition file [ {$this->_source} ]."
                                       );
                return;
            }

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
     * @throws PIECE_FLOW_ERROR_INVALID_FORMAT
     */
    function _configureActionStates($states)
    {
        if (is_null($states)) {
            return;
        }

        if (!is_array($states)) {
            Piece_Flow_Error::push(PIECE_FLOW_ERROR_INVALID_FORMAT,
                                   "The \"actionState\" element is invalid in the flow definition file [ {$this->_source} ]."
                                   );
            return;
        }

        for ($i = 0, $count = count($states); $i < $count; ++$i) {
            if (@!array_key_exists('name', $states[$i])) {
                Piece_Flow_Error::push(PIECE_FLOW_ERROR_INVALID_FORMAT,
                                       "The \"name\" element in the \"actionState\" element is required in the flow definition file [ {$this->_source} ]."
                                       );
                return;
            }

            if (is_null($states[$i]['name']) || !strlen($states[$i]['name'])) {
                Piece_Flow_Error::push(PIECE_FLOW_ERROR_INVALID_FORMAT,
                                       "The \"name\" element in the \"actionState\" element is invalid in the flow definition file [ {$this->_source} ]."
                                       );
                return;
            }

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
     * @throws PIECE_FLOW_ERROR_INVALID_FORMAT
     */
    function _configureState($state)
    {
        if (array_key_exists('transition', $state)) {
            if (!is_array($state['transition'])) {
                Piece_Flow_Error::push(PIECE_FLOW_ERROR_INVALID_FORMAT,
                                       "The \"transition\" element is invalid in the flow definition file [ {$this->_source} ]."
                                       );
                return;
            }

            for ($i = 0, $count = count($state['transition']); $i < $count; ++$i) {
                if (@!array_key_exists('event', $state['transition'][$i])) {
                    Piece_Flow_Error::push(PIECE_FLOW_ERROR_INVALID_FORMAT,
                                           "The \"event\" element in the \"transition\" element is required in the flow definition file [ {$this->_source} ]."
                                           );
                    return;
                }

                if (is_null($state['transition'][$i]['event']) || !strlen($state['transition'][$i]['event'])) {
                    Piece_Flow_Error::push(PIECE_FLOW_ERROR_INVALID_FORMAT,
                                           "The \"event\" element in the \"transition\" element is invalid in the flow definition file [ {$this->_source} ]."
                                           );
                    return;
                }

                if (!array_key_exists('nextState', $state['transition'][$i])) {
                    Piece_Flow_Error::push(PIECE_FLOW_ERROR_INVALID_FORMAT,
                                           "The \"nextState\" element in the \"transition\" element is required in the flow definition file [ {$this->_source} ]."
                                           );
                    return;
                }

                if (is_null($state['transition'][$i]['nextState']) || !strlen($state['transition'][$i]['nextState'])) {
                    Piece_Flow_Error::push(PIECE_FLOW_ERROR_INVALID_FORMAT,
                                           "The \"nextState\" element in the \"transition\" element is invalid in the flow definition file [ {$this->_source} ]."
                                           );
                    return;
                }

                if (!array_key_exists('action', $state['transition'][$i])) {
                    $state['transition'][$i]['action'] = null;
                } else {
                    if (!is_array($state['transition'][$i]['action'])) {
                        Piece_Flow_Error::push(PIECE_FLOW_ERROR_INVALID_FORMAT,
                                               "The \"action\" element is invalid in the flow definition file [ {$this->_source} ]."
                                               );
                        return;
                    }

                    if (!array_key_exists('method', $state['transition'][$i]['action'])) {
                        Piece_Flow_Error::push(PIECE_FLOW_ERROR_INVALID_FORMAT,
                                               "The \"method\" element in the \"action\" element is required in the flow definition file [ {$this->_source} ]."
                                               );
                        return;
                    }

                    if (is_null($state['transition'][$i]['action']['method']) || !strlen($state['transition'][$i]['action']['method'])) {
                        Piece_Flow_Error::push(PIECE_FLOW_ERROR_INVALID_FORMAT,
                                               "The \"method\" element in the \"action\" element is invalid in the flow definition file [ {$this->_source} ]."
                                               );
                        return;
                    }

                    if (array_key_exists('class', $state['transition'][$i]['action'])) {
                        if (!strlen($state['transition'][$i]['action']['class'])) {
                            Piece_Flow_Error::push(PIECE_FLOW_ERROR_INVALID_FORMAT,
                                                   "The \"class\" element in the \"action\" element is invalid in the flow definition file [ {$this->_source} ]."
                                                   );
                            return;
                        }
                    }
                }

                if (!array_key_exists('guard', $state['transition'][$i])) {
                    $state['transition'][$i]['guard'] = null;
                } else {
                    if (!is_array($state['transition'][$i]['guard'])) {
                        Piece_Flow_Error::push(PIECE_FLOW_ERROR_INVALID_FORMAT,
                                               "The \"guard\" element is invalid in the flow definition file [ {$this->_source} ]."
                                               );
                        return;
                    }

                    if (!array_key_exists('method', $state['transition'][$i]['guard'])) {
                        Piece_Flow_Error::push(PIECE_FLOW_ERROR_INVALID_FORMAT,
                                               "The \"method\" element in the \"guard\" element is required in the flow definition file [ {$this->_source} ]."
                                               );
                        return;
                    }

                    if (is_null($state['transition'][$i]['guard']['method']) || !strlen($state['transition'][$i]['guard']['method'])) {
                        Piece_Flow_Error::push(PIECE_FLOW_ERROR_INVALID_FORMAT,
                                               "The \"method\" element in the \"guard\" element is invalid in the flow definition file [ {$this->_source} ]."
                                               );
                        return;
                    }

                    if (array_key_exists('class', $state['transition'][$i]['guard'])) {
                        if (!strlen($state['transition'][$i]['guard']['class'])) {
                            Piece_Flow_Error::push(PIECE_FLOW_ERROR_INVALID_FORMAT,
                                                   "The \"class\" element in the \"guard\" element is invalid in the flow definition file [ {$this->_source} ]."
                                                   );
                            return;
                        }
                    }
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
            if (!is_array($state['entry'])) {
                Piece_Flow_Error::push(PIECE_FLOW_ERROR_INVALID_FORMAT,
                                       "The \"entry\" element is invalid in the flow definition file [ {$this->_source} ]."
                                       );
                return;
            }

            if (!array_key_exists('method', $state['entry'])) {
                Piece_Flow_Error::push(PIECE_FLOW_ERROR_INVALID_FORMAT,
                                       "The \"method\" element in the \"entry\" element is required in the flow definition file [ {$this->_source} ]."
                                       );
                return;
            }

            if (is_null($state['entry']['method']) || !strlen($state['entry']['method'])) {
                Piece_Flow_Error::push(PIECE_FLOW_ERROR_INVALID_FORMAT,
                                       "The \"method\" element in the \"entry\" element is invalid in the flow definition file [ {$this->_source} ]."
                                       );
                return;
            }

            if (array_key_exists('class', $state['entry'])) {
                if (!strlen($state['entry']['class'])) {
                    Piece_Flow_Error::push(PIECE_FLOW_ERROR_INVALID_FORMAT,
                                           "The \"class\" element in the \"entry\" element is invalid in the flow definition file [ {$this->_source} ]."
                                           );
                    return;
                }
            }

            $this->_config->setEntryAction($state['name'], $state['entry']);
        }

        if (array_key_exists('exit', $state)) {
            if (!is_array($state['exit'])) {
                Piece_Flow_Error::push(PIECE_FLOW_ERROR_INVALID_FORMAT,
                                       "The \"exit\" element is invalid in the flow definition file [ {$this->_source} ]."
                                       );
                return;
            }

            if (!array_key_exists('method', $state['exit'])) {
                Piece_Flow_Error::push(PIECE_FLOW_ERROR_INVALID_FORMAT,
                                       "The \"method\" element in the \"exit\" element is required in the flow definition file [ {$this->_source} ]."
                                       );
                return;
            }

            if (is_null($state['exit']['method']) || !strlen($state['exit']['method'])) {
                Piece_Flow_Error::push(PIECE_FLOW_ERROR_INVALID_FORMAT,
                                       "The \"method\" element in the \"exit\" element is invalid in the flow definition file [ {$this->_source} ]."
                                       );
                return;
            }

            if (array_key_exists('class', $state['exit'])) {
                if (!strlen($state['exit']['class'])) {
                    Piece_Flow_Error::push(PIECE_FLOW_ERROR_INVALID_FORMAT,
                                           "The \"class\" element in the \"exit\" element is invalid in the flow definition file [ {$this->_source} ]."
                                           );
                    return;
                }
            }

            $this->_config->setExitAction($state['name'], $state['exit']);
        }

        if (array_key_exists('activity', $state)) {
            if (!is_array($state['activity'])) {
                Piece_Flow_Error::push(PIECE_FLOW_ERROR_INVALID_FORMAT,
                                       "The \"activity\" element is invalid in the flow definition file [ {$this->_source} ]."
                                       );
                return;
            }

            if (!array_key_exists('method', $state['activity'])) {
                Piece_Flow_Error::push(PIECE_FLOW_ERROR_INVALID_FORMAT,
                                       "The \"method\" element in the \"activity\" element is required in the flow definition file [ {$this->_source} ]."
                                       );
                return;
            }

            if (is_null($state['activity']['method']) || !strlen($state['activity']['method'])) {
                Piece_Flow_Error::push(PIECE_FLOW_ERROR_INVALID_FORMAT,
                                       "The \"method\" element in the \"activity\" element is invalid in the flow definition file [ {$this->_source} ]."
                                       );
                return;
            }

            if (array_key_exists('class', $state['activity'])) {
                if (!strlen($state['activity']['class'])) {
                    Piece_Flow_Error::push(PIECE_FLOW_ERROR_INVALID_FORMAT,
                                           "The \"class\" element in the \"activity\" element is invalid in the flow definition file [ {$this->_source} ]."
                                           );
                    return;
                }
            }

            $this->_config->setActivity($state['name'], $state['activity']);
        }
    }

    // }}}
    // {{{ _getConfiguration()

    /**
     * Gets a Piece_Flow_Config object from a configuration file or a cache.
     *
     * @return Piece_Flow_Config
     * @throws PIECE_FLOW_ERROR_INVALID_FORMAT
     */
    function &_getConfiguration()
    {
        $masterFile = realpath($this->_source);
        $cache = &new Cache_Lite_File(array('cacheDir' => "{$this->_cacheDirectory}/",
                                            'masterFile' => $masterFile,
                                            'automaticSerialization' => true,
                                            'errorHandlingAPIBreak' => true)
                                      );

        if (!Piece_Flow_Env::isProduction()) {
            $cache->remove($masterFile);
        }

        /*
         * The Cache_Lite class always specifies PEAR_ERROR_RETURN when
         * calling PEAR::raiseError in default.
         */
        $config = $cache->get($masterFile);
        if (PEAR::isError($config)) {
            Piece_Flow_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));
            Piece_Flow_Error::push(PIECE_FLOW_ERROR_CANNOT_READ,
                                   "Cannot read the cache file in the directory [ {$this->_cacheDirectory} ].",
                                   'warning'
                                   );
            Piece_Flow_Error::popCallback();

            return $this->_createConfigurationFromSource();
        }

        if (!$config) {
            $config = &$this->_createConfigurationFromSource();
            if (Piece_Flow_Error::hasErrors('exception')) {
                $return = null;
                return $return;
            }

            $result = $cache->save($config);
            if (PEAR::isError($result)) {
                Piece_Flow_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));
                Piece_Flow_Error::push(PIECE_FLOW_ERROR_CANNOT_WRITE,
                                       "Cannot write the Piece_Flow object to the cache file in the directory [ {$this->_cacheDirectory} ].",
                                       'warning'
                                       );
                Piece_Flow_Error::popCallback();
            }
        }

        return $config;
    }

    // }}}
    // {{{ _parseSource()

    /**
     * Parses the given source and returns an array which represent a flow
     * structure.
     *
     * This method is to be overriden by the appropriate driver for the given
     * file.
     *
     * @return array
     * @throws PIECE_FLOW_ERROR_INVALID_FORMAT
     */
    function _parseSource() {}

    // }}}
    // {{{ _configureFirstState()

    /**
     * Configures the first state.
     *
     * @param array $firstState
     * @throws PIECE_FLOW_ERROR_INVALID_FORMAT
     * @since Method available since Release 1.10.0
     */
    function _configureFirstState($firstState)
    {
        if (is_null($firstState)) {
            Piece_Flow_Error::push(PIECE_FLOW_ERROR_INVALID_FORMAT,
                                   "The \"firstState\" element is required in the flow definition file [ {$this->_source} ]."
                                   );
            return;
        }

        if (!strlen($firstState)) {
            Piece_Flow_Error::push(PIECE_FLOW_ERROR_INVALID_FORMAT,
                                   "The \"firstState\" element is invalid in the flow definition file [ {$this->_source} ]."
                                   );
            return;
        }

        $this->_config->setFirstState($firstState);
    }

    // }}}
    // {{{ _configureLastState()

    /**
     * Configures the last state.
     *
     * @param array $lastState
     * @throws PIECE_FLOW_ERROR_INVALID_FORMAT
     * @since Method available since Release 1.10.0
     */
    function _configureLastState($lastState)
    {
        if (is_null($lastState)) {
            return;
        }

        if (!array_key_exists('name', $lastState)) {
            Piece_Flow_Error::push(PIECE_FLOW_ERROR_INVALID_FORMAT,
                                   "The \"name\" element in the \"lastState\" element is required in the flow definition file [ {$this->_source} ]."
                                   );
            return;
        }

        if (is_null($lastState['name']) || !strlen($lastState['name'])) {
            Piece_Flow_Error::push(PIECE_FLOW_ERROR_INVALID_FORMAT,
                                   "The \"name\" element in the \"lastState\" element is invalid in the flow definition file [ {$this->_source} ]."
                                   );
            return;
        }

        if (!array_key_exists('view', $lastState)) {
            Piece_Flow_Error::push(PIECE_FLOW_ERROR_INVALID_FORMAT,
                                   "The \"view\" element in the \"lastState\" element is required in the flow definition file [ {$this->_source} ]."
                                   );
            return;
        }

        if (is_null($lastState['view']) || !strlen($lastState['view'])) {
            Piece_Flow_Error::push(PIECE_FLOW_ERROR_INVALID_FORMAT,
                                   "The \"view\" element in the \"lastState\" element is invalid in the flow definition file [ {$this->_source} ]."
                                   );
            return;
        }

        $this->_config->setLastState($lastState['name'], $lastState['view']);
        $this->_configureState($lastState);
    }

    // }}}
    // {{{ _configureInitialAction()

    /**
     * Configures the initial action.
     *
     * @param array $initialAction
     * @throws PIECE_FLOW_ERROR_INVALID_FORMAT
     * @since Method available since Release 1.10.0
     */
    function _configureInitialAction($initialAction)
    {
        if (is_null($initialAction)) {
            return;
        }

        if (!is_array($initialAction)) {
            Piece_Flow_Error::push(PIECE_FLOW_ERROR_INVALID_FORMAT,
                                   "The \"initial\" element is invalid in the flow definition file [ {$this->_source} ]."
                                   );
            return;
        }

        if (!array_key_exists('method', $initialAction)) {
            Piece_Flow_Error::push(PIECE_FLOW_ERROR_INVALID_FORMAT,
                                   "The \"method\" element in the \"initial\" element is required in the flow definition file [ {$this->_source} ]."
                                   );
            return;
        }

        if (is_null($initialAction['method']) || !strlen($initialAction['method'])) {
            Piece_Flow_Error::push(PIECE_FLOW_ERROR_INVALID_FORMAT,
                                   "The \"method\" element in the \"initial\" element is invalid in the flow definition file [ {$this->_source} ]."
                                   );
            return;
        }

        if (array_key_exists('class', $initialAction)) {
            if (!strlen($initialAction['class'])) {
                Piece_Flow_Error::push(PIECE_FLOW_ERROR_INVALID_FORMAT,
                                       "The \"class\" element in the \"initial\" element is invalid in the flow definition file [ {$this->_source} ]."
                                       );
                return;
            }
        }

        $this->_config->setInitialAction($initialAction);
    }

    // }}}
    // {{{ _configureFinalAction()

    /**
     * Configures the final action.
     *
     * @param array $finalAction
     * @throws PIECE_FLOW_ERROR_INVALID_FORMAT
     * @since Method available since Release 1.10.0
     */
    function _configureFinalAction($finalAction)
    {
        if (is_null($finalAction)) {
            return;
        }

        if (!is_array($finalAction)) {
            Piece_Flow_Error::push(PIECE_FLOW_ERROR_INVALID_FORMAT,
                                   "The \"final\" element is invalid in the flow definition file [ {$this->_source} ]."
                                   );
            return;
        }

        if (!array_key_exists('method', $finalAction)) {
            Piece_Flow_Error::push(PIECE_FLOW_ERROR_INVALID_FORMAT,
                                   "The \"method\" element in the \"final\" element is required in the flow definition file [ {$this->_source} ]."
                                   );
            return;
        }

        if (is_null($finalAction['method']) || !strlen($finalAction['method'])) {
            Piece_Flow_Error::push(PIECE_FLOW_ERROR_INVALID_FORMAT,
                                   "The \"method\" element in the \"final\" element is invalid in the flow definition file [ {$this->_source} ]."
                                   );
            return;
        }

        if (array_key_exists('class', $finalAction)) {
            if (!strlen($finalAction['class'])) {
                Piece_Flow_Error::push(PIECE_FLOW_ERROR_INVALID_FORMAT,
                                       "The \"class\" element in the \"final\" element is invalid in the flow definition file [ {$this->_source} ]."
                                       );
                return;
            }
        }

        $this->_config->setFinalAction($finalAction);
    }

    // }}}
    // {{{ _createConfigurationFromSource()

    /**
     * Parses the given source and returns a Piece_Flow_Config object.
     *
     * @return Piece_Flow_Config
     * @throws PIECE_FLOW_ERROR_INVALID_FORMAT
     * @since Method available since Release 1.11.0
     */
    function &_createConfigurationFromSource()
    {
        $flow = $this->_parseSource();
        if (Piece_Flow_Error::hasErrors('exception')) {
            $return = null;
            return $return;
        }

        $this->_config = &new Piece_Flow_Config();
        $this->_configureFirstState(@$flow['firstState']);
        if (Piece_Flow_Error::hasErrors('exception')) {
            $return = null;
            return $return;
        }

        $this->_configureLastState(@$flow['lastState']);
        if (Piece_Flow_Error::hasErrors('exception')) {
            $return = null;
            return $return;
        }

        $this->_configureViewStates(@$flow['viewState']);
        if (Piece_Flow_Error::hasErrors('exception')) {
            $return = null;
            return $return;
        }

        $this->_configureActionStates(@$flow['actionState']);
        if (Piece_Flow_Error::hasErrors('exception')) {
            $return = null;
            return $return;
        }

        $this->_configureInitialAction(@$flow['initial']);
        if (Piece_Flow_Error::hasErrors('exception')) {
            $return = null;
            return $return;
        }

        $this->_configureFinalAction(@$flow['final']);
        if (Piece_Flow_Error::hasErrors('exception')) {
            $return = null;
            return $return;
        }

        return $this->_config;
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
