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
 * @since      File available since Release 1.0.0
 */

namespace Piece\Flow\Action;

use Piece\Flow\ClassLoader;

/**
 * A factory class for creating action objects.
 *
 * @package    Piece_Flow
 * @copyright  2006-2008, 2012 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version    Release: @package_version@
 * @since      Class available since Release 1.0.0
 */
class Factory
{
    const DEFAULT_CONTEXT_ID = '_default';

    private static $instances = array();
    private static $actionDirectory;
    private static $contextID = self::DEFAULT_CONTEXT_ID;

    /**
     * Creates an action object from a configuration file or a cache.
     *
     * @param string $class
     * @return mixed
     */
    public static function factory($class)
    {
        if (!array_key_exists(self::$contextID, self::$instances)
            || !array_key_exists($class, self::$instances[ self::$contextID ])
            ) {
            self::load($class);
            self::$instances[ self::$contextID ][$class] = new $class();
        }

        return self::$instances[ self::$contextID ][$class];
    }

    /**
     * Sets a directory as the action directory.
     *
     * @param string $directory
     */
    public static function setActionDirectory($directory)
    {
        self::$actionDirectory = $directory;
    }

    /**
     * Clears the action instances.
     */
    public static function clearInstances()
    {
        self::$instances = array();
    }

    /**
     * Gets the action instances.
     *
     * @return array
     */
    public static function getInstances()
    {
        if (array_key_exists(self::$contextID, self::$instances)) {
            return self::$instances[ self::$contextID ];
        } else {
            return array();
        }
    }

    /**
     * Sets an array as the action instances.
     *
     * @param array $instances
     */
    public static function setInstances($instances)
    {
        self::$instances[ self::$contextID ]= $instances;
    }

    /**
     * Loads an action class corresponding to the given class name.
     *
     * @param string $class
     * @throws \Piece\Flow\Action\ActionDirectoryRequiredException
     * @throws \Piece\Flow\Action\ClassNotFoundException
     */
    public static function load($class)
    {
        if (!ClassLoader::loaded($class)) {
            if (is_null(self::$actionDirectory)) {
                throw new ActionDirectoryRequiredException('The action directory is not given.');
            }

            ClassLoader::load($class, self::$actionDirectory);

            if (!ClassLoader::loaded($class)) {
                throw new ClassNotFoundException("The class [ $class ] not found in the loaded file.");
            }
        }
    }

    /**
     * Sets the context ID.
     *
     * @param string $contextID
     */
    public static function setContextID($contextID)
    {
        self::$contextID = $contextID;
    }

    /**
     * Clears the context ID.
     *
     * @param string $contextID
     */
    public static function clearContextID()
    {
        self::$contextID = self::DEFAULT_CONTEXT_ID;
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
