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
require_once 'PEAR.php';

use Piece\Flow\Config;
use Piece\Flow\Env;
use Piece\Flow\FileNotFoundException;
use Piece\Flow\FileNotReadableException;
use Piece\Flow\Util\ErrorReporting;

/**
 * The base class for Config drivers.
 *
 * @package    Piece_Flow
 * @copyright  2006-2008, 2012 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version    Release: @package_version@
 * @since      Class available since Release 0.1.0
 */
class Common
{
    protected $source;
    protected $config;
    protected $cacheDirectory;

    /**
     * Constructor
     *
     * @param mixed  $source
     * @param string $cacheDirectory
     */
    public function __construct($source, $cacheDirectory)
    {
        $this->source = $source;
        $this->cacheDirectory = $cacheDirectory;
    }

    /**
     * Reads configuration from the given source and creates
     * a Config object.
     *
     * @return \Piece\Flow\Config
     * @throws \Piece\Flow\FileNotFoundException
     * @throws \Piece\Flow\FileNotReadableException
     */
    public function read()
    {
        if (!file_exists($this->source)) {
            throw new FileNotFoundException("The configuration file [ {$this->source} ] not found.");
        }

        if (!is_readable($this->source)) {
            throw new FileNotReadableException("The configuration file [ {$this->source} ] is not readable.");
        }

        if (is_null($this->cacheDirectory)) {
            return $this->createConfigurationFromSource();
        }

        if (!file_exists($this->cacheDirectory)) {
            trigger_error("The cache directory [ {$this->cacheDirectory} ] is not found.",
                          E_USER_WARNING
                          );
            return $this->createConfigurationFromSource();
        }

        if (!is_readable($this->cacheDirectory) || !is_writable($this->cacheDirectory)) {
            trigger_error("The cache directory [ {$this->cacheDirectory} ] is not readable or writable.",
                          E_USER_WARNING
                          );
            return $this->createConfigurationFromSource();
        }

        return $this->getConfiguration();
    }

    /**
     * Configures view states.
     *
     * @param array $states
     * @throws \Piece\Flow\ConfigReader\InvalidFormatException
     */
    protected function configureViewStates($states)
    {
        if (is_null($states)) {
            throw new InvalidFormatException("The \"viewState\" element is required in the flow definition file [ {$this->source} ].");
        }

        if (!is_array($states)) {
            throw new InvalidFormatException("The \"viewState\" element is invalid in the flow definition file [ {$this->source} ].");
        }

        if (!count($states)) {
            throw new InvalidFormatException("The \"viewState\" element requires one or more child elements in the flow definition file [ {$this->source} ].");
        }

        for ($i = 0, $count = count($states); $i < $count; ++$i) {
            if (@!array_key_exists('name', $states[$i])) {
                throw new InvalidFormatException("The \"name\" element in the \"viewState\" element is required in the flow definition file [ {$this->source} ].");
            }

            if (is_null($states[$i]['name']) || !strlen($states[$i]['name'])) {
                throw new InvalidFormatException("The \"name\" element in the \"viewState\" element is invalid in the flow definition file [ {$this->source} ].");
            }

            if (!array_key_exists('view', $states[$i])) {
                throw new InvalidFormatException("The \"view\" element in the \"viewState\" element is required in the flow definition file [ {$this->source} ].");
            }

            if (is_null($states[$i]['view']) || !strlen($states[$i]['view'])) {
                throw new InvalidFormatException("The \"view\" element in the \"viewState\" element is invalid in the flow definition file [ {$this->source} ].");
            }

            $this->config->addViewState($states[$i]['name'],
                                         $states[$i]['view']
                                         );
            $this->configureState($states[$i]);
        }
    }

    /**
     * Configures action states.
     *
     * @param array $states
     * @throws \Piece\Flow\ConfigReader\InvalidFormatException
     */
    protected function configureActionStates($states)
    {
        if (is_null($states)) {
            return;
        }

        if (!is_array($states)) {
            throw new InvalidFormatException("The \"actionState\" element is invalid in the flow definition file [ {$this->source} ].");
        }

        for ($i = 0, $count = count($states); $i < $count; ++$i) {
            if (@!array_key_exists('name', $states[$i])) {
                throw new InvalidFormatException("The \"name\" element in the \"actionState\" element is required in the flow definition file [ {$this->source} ].");
            }

            if (is_null($states[$i]['name']) || !strlen($states[$i]['name'])) {
                throw new InvalidFormatException("The \"name\" element in the \"actionState\" element is invalid in the flow definition file [ {$this->source} ].");
            }

            $this->config->addActionState($states[$i]['name']);
            $this->configureState($states[$i]);
        }
    }

    /**
     * Configures the state.
     *
     * @param array $state
     * @throws \Piece\Flow\ConfigReader\InvalidFormatException
     */
    protected function configureState($state)
    {
        if (array_key_exists('transition', $state)) {
            if (!is_array($state['transition'])) {
                throw new InvalidFormatException("The \"transition\" element is invalid in the flow definition file [ {$this->source} ].");
            }

            for ($i = 0, $count = count($state['transition']); $i < $count; ++$i) {
                if (@!array_key_exists('event', $state['transition'][$i])) {
                    throw new InvalidFormatException("The \"event\" element in the \"transition\" element is required in the flow definition file [ {$this->source} ].");
                }

                if (is_null($state['transition'][$i]['event']) || !strlen($state['transition'][$i]['event'])) {
                    throw new InvalidFormatException("The \"event\" element in the \"transition\" element is invalid in the flow definition file [ {$this->source} ].");
                }

                if (!array_key_exists('nextState', $state['transition'][$i])) {
                    throw new InvalidFormatException("The \"nextState\" element in the \"transition\" element is required in the flow definition file [ {$this->source} ].");
                }

                if (is_null($state['transition'][$i]['nextState']) || !strlen($state['transition'][$i]['nextState'])) {
                    throw new InvalidFormatException("The \"nextState\" element in the \"transition\" element is invalid in the flow definition file [ {$this->source} ].");
                }

                if (!array_key_exists('action', $state['transition'][$i])) {
                    $state['transition'][$i]['action'] = null;
                } else {
                    if (!is_array($state['transition'][$i]['action'])) {
                        throw new InvalidFormatException("The \"action\" element is invalid in the flow definition file [ {$this->source} ].");
                    }

                    if (!array_key_exists('method', $state['transition'][$i]['action'])) {
                        throw new InvalidFormatException("The \"method\" element in the \"action\" element is required in the flow definition file [ {$this->source} ].");
                    }

                    if (is_null($state['transition'][$i]['action']['method']) || !strlen($state['transition'][$i]['action']['method'])) {
                        throw new InvalidFormatException("The \"method\" element in the \"action\" element is invalid in the flow definition file [ {$this->source} ].");
                    }

                    if (array_key_exists('class', $state['transition'][$i]['action'])) {
                        if (!strlen($state['transition'][$i]['action']['class'])) {
                            throw new InvalidFormatException("The \"class\" element in the \"action\" element is invalid in the flow definition file [ {$this->source} ].");
                        }
                    }
                }

                if (!array_key_exists('guard', $state['transition'][$i])) {
                    $state['transition'][$i]['guard'] = null;
                } else {
                    if (!is_array($state['transition'][$i]['guard'])) {
                        throw new InvalidFormatException("The \"guard\" element is invalid in the flow definition file [ {$this->source} ].");
                    }

                    if (!array_key_exists('method', $state['transition'][$i]['guard'])) {
                        throw new InvalidFormatException("The \"method\" element in the \"guard\" element is required in the flow definition file [ {$this->source} ].");
                    }

                    if (is_null($state['transition'][$i]['guard']['method']) || !strlen($state['transition'][$i]['guard']['method'])) {
                        throw new InvalidFormatException("The \"method\" element in the \"guard\" element is invalid in the flow definition file [ {$this->source} ].");
                    }

                    if (array_key_exists('class', $state['transition'][$i]['guard'])) {
                        if (!strlen($state['transition'][$i]['guard']['class'])) {
                            throw new InvalidFormatException("The \"class\" element in the \"guard\" element is invalid in the flow definition file [ {$this->source} ].");
                        }
                    }
                }

                $this->config->addTransition($state['name'],
                                              $state['transition'][$i]['event'],
                                              $state['transition'][$i]['nextState'],
                                              $state['transition'][$i]['action'],
                                              $state['transition'][$i]['guard']
                                              );

            }
        }

        if (array_key_exists('entry', $state)) {
            if (!is_array($state['entry'])) {
                throw new InvalidFormatException("The \"entry\" element is invalid in the flow definition file [ {$this->source} ].");
            }

            if (!array_key_exists('method', $state['entry'])) {
                throw new InvalidFormatException("The \"method\" element in the \"entry\" element is required in the flow definition file [ {$this->source} ].");
            }

            if (is_null($state['entry']['method']) || !strlen($state['entry']['method'])) {
                throw new InvalidFormatException("The \"method\" element in the \"entry\" element is invalid in the flow definition file [ {$this->source} ].");
            }

            if (array_key_exists('class', $state['entry'])) {
                if (!strlen($state['entry']['class'])) {
                    throw new InvalidFormatException("The \"class\" element in the \"entry\" element is invalid in the flow definition file [ {$this->source} ].");
                }
            }

            $this->config->setEntryAction($state['name'], $state['entry']);
        }

        if (array_key_exists('exit', $state)) {
            if (!is_array($state['exit'])) {
                throw new InvalidFormatException("The \"exit\" element is invalid in the flow definition file [ {$this->source} ].");
            }

            if (!array_key_exists('method', $state['exit'])) {
                throw new InvalidFormatException("The \"method\" element in the \"exit\" element is required in the flow definition file [ {$this->source} ].");
            }

            if (is_null($state['exit']['method']) || !strlen($state['exit']['method'])) {
                throw new InvalidFormatException("The \"method\" element in the \"exit\" element is invalid in the flow definition file [ {$this->source} ].");
            }

            if (array_key_exists('class', $state['exit'])) {
                if (!strlen($state['exit']['class'])) {
                    throw new InvalidFormatException("The \"class\" element in the \"exit\" element is invalid in the flow definition file [ {$this->source} ].");
                }
            }

            $this->config->setExitAction($state['name'], $state['exit']);
        }

        if (array_key_exists('activity', $state)) {
            if (!is_array($state['activity'])) {
                throw new InvalidFormatException("The \"activity\" element is invalid in the flow definition file [ {$this->source} ].");
            }

            if (!array_key_exists('method', $state['activity'])) {
                throw new InvalidFormatException("The \"method\" element in the \"activity\" element is required in the flow definition file [ {$this->source} ].");
            }

            if (is_null($state['activity']['method']) || !strlen($state['activity']['method'])) {
                throw new InvalidFormatException("The \"method\" element in the \"activity\" element is invalid in the flow definition file [ {$this->source} ].");
            }

            if (array_key_exists('class', $state['activity'])) {
                if (!strlen($state['activity']['class'])) {
                    throw new InvalidFormatException("The \"class\" element in the \"activity\" element is invalid in the flow definition file [ {$this->source} ].");
                }
            }

            $this->config->setActivity($state['name'], $state['activity']);
        }
    }

    /**
     * Gets a Config object from a configuration file or a cache.
     *
     * @return \Piece\Flow\Config
     */
    protected function getConfiguration()
    {
        $masterFile = realpath($this->source);
        $cache = new \Cache_Lite_File(array('cacheDir' => "{$this->cacheDirectory}/",
                                            'masterFile' => $masterFile,
                                            'automaticSerialization' => true,
                                            'errorHandlingAPIBreak' => true)
                                      );

        if (!Env::isProduction()) {
            $cache->remove($masterFile);
        }

        /*
         * The Cache_Lite class always specifies PEAR_ERROR_RETURN when
         * calling PEAR::raiseError in default.
         */
        $config = $cache->get($masterFile);
        $self = $this;
        $isError = ErrorReporting::invokeWith(error_reporting() & ~E_STRICT, function () use ($config) {
            return \PEAR::isError($config);
        });
        if ($isError) {
            trigger_error("Cannot read the cache file in the directory [ {$this->cacheDirectory} ].",
                          E_USER_WARNING
                          );
            return $this->createConfigurationFromSource();
        }

        if (!$config) {
            $config = $this->createConfigurationFromSource();
            $result = $cache->save($config);
            $isError = ErrorReporting::invokeWith(error_reporting() & ~E_STRICT, function () use ($result) {
                return \PEAR::isError($result);
            });
            if ($isError) {
                trigger_error("Cannot write the Piece_Flow object to the cache file in the directory [ {$this->cacheDirectory} ].",
                              E_USER_WARNING
                              );
            }
        }

        return $config;
    }

    /**
     * Parses the given source and returns an array which represent a flow
     * structure.
     *
     * This method is to be overriden by the appropriate driver for the given
     * file.
     *
     * @return array
     */
    protected function parseSource()
    {
    }

    /**
     * Configures the first state.
     *
     * @param array $firstState
     * @throws \Piece\Flow\ConfigReader\InvalidFormatException
     * @since Method available since Release 1.10.0
     */
    protected function configureFirstState($firstState)
    {
        if (is_null($firstState)) {
            throw new InvalidFormatException("The \"firstState\" element is required in the flow definition file [ {$this->source} ].");
        }

        if (!strlen($firstState)) {
            throw new InvalidFormatException("The \"firstState\" element is invalid in the flow definition file [ {$this->source} ].");
        }

        $this->config->setFirstState($firstState);
    }

    /**
     * Configures the last state.
     *
     * @param array $lastState
     * @throws \Piece\Flow\ConfigReader\InvalidFormatException
     * @since Method available since Release 1.10.0
     */
    protected function configureLastState($lastState)
    {
        if (is_null($lastState)) {
            return;
        }

        if (!array_key_exists('name', $lastState)) {
            throw new InvalidFormatException("The \"name\" element in the \"lastState\" element is required in the flow definition file [ {$this->source} ].");
        }

        if (is_null($lastState['name']) || !strlen($lastState['name'])) {
            throw new InvalidFormatException("The \"name\" element in the \"lastState\" element is invalid in the flow definition file [ {$this->source} ].");
        }

        if (!array_key_exists('view', $lastState)) {
            throw new InvalidFormatException("The \"view\" element in the \"lastState\" element is required in the flow definition file [ {$this->source} ].");
        }

        if (is_null($lastState['view']) || !strlen($lastState['view'])) {
            throw new InvalidFormatException("The \"view\" element in the \"lastState\" element is invalid in the flow definition file [ {$this->source} ].");
        }

        $this->config->setLastState($lastState['name'], $lastState['view']);
        $this->configureState($lastState);
    }

    /**
     * Configures the initial action.
     *
     * @param array $initialAction
     * @throws \Piece\Flow\ConfigReader\InvalidFormatException
     * @since Method available since Release 1.10.0
     */
    protected function configureInitialAction($initialAction)
    {
        if (is_null($initialAction)) {
            return;
        }

        if (!is_array($initialAction)) {
            throw new InvalidFormatException("The \"initial\" element is invalid in the flow definition file [ {$this->source} ].");
        }

        if (!array_key_exists('method', $initialAction)) {
            throw new InvalidFormatException("The \"method\" element in the \"initial\" element is required in the flow definition file [ {$this->source} ].");
        }

        if (is_null($initialAction['method']) || !strlen($initialAction['method'])) {
            throw new InvalidFormatException("The \"method\" element in the \"initial\" element is invalid in the flow definition file [ {$this->source} ].");
        }

        if (array_key_exists('class', $initialAction)) {
            if (!strlen($initialAction['class'])) {
                throw new InvalidFormatException("The \"class\" element in the \"initial\" element is invalid in the flow definition file [ {$this->source} ].");
            }
        }

        $this->config->setInitialAction($initialAction);
    }

    /**
     * Configures the final action.
     *
     * @param array $finalAction
     * @throws \Piece\Flow\ConfigReader\InvalidFormatException
     * @since Method available since Release 1.10.0
     */
    protected function configureFinalAction($finalAction)
    {
        if (is_null($finalAction)) {
            return;
        }

        if (!is_array($finalAction)) {
            throw new InvalidFormatException("The \"final\" element is invalid in the flow definition file [ {$this->source} ].");
        }

        if (!array_key_exists('method', $finalAction)) {
            throw new InvalidFormatException("The \"method\" element in the \"final\" element is required in the flow definition file [ {$this->source} ].");
        }

        if (is_null($finalAction['method']) || !strlen($finalAction['method'])) {
            throw new InvalidFormatException("The \"method\" element in the \"final\" element is invalid in the flow definition file [ {$this->source} ].");
        }

        if (array_key_exists('class', $finalAction)) {
            if (!strlen($finalAction['class'])) {
                throw new InvalidFormatException("The \"class\" element in the \"final\" element is invalid in the flow definition file [ {$this->source} ].");
            }
        }

        $this->config->setFinalAction($finalAction);
    }

    /**
     * Parses the given source and returns a Config object.
     *
     * @return \Piece\Flow\Config
     * @since Method available since Release 1.11.0
     */
    protected function createConfigurationFromSource()
    {
        $flow = $this->parseSource();

        $this->config = new Config();
        $this->configureFirstState(@$flow['firstState']);
        $this->configureLastState(@$flow['lastState']);
        $this->configureViewStates(@$flow['viewState']);
        $this->configureActionStates(@$flow['actionState']);
        $this->configureInitialAction(@$flow['initial']);
        $this->configureFinalAction(@$flow['final']);

        return $this->config;
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
