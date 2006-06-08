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
 */

require_once 'Piece/Flow/Error.php';

// {{{ GLOBALS

$GLOBALS['PIECE_FLOW_Action_Instances'] = array();
$GLOBALS['PIECE_FLOW_Action_Path'] = null;

// }}}
// {{{ Piece_Flow_Action_Factory

/**
 * A factory class for creating action objects.
 *
 * @package    Piece_Flow
 * @author     KUBO Atsuhiro <iteman2002@yahoo.co.jp>
 * @copyright  2006 KUBO Atsuhiro <iteman2002@yahoo.co.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @link       http://iteman.typepad.jp/piece/
 */
class Piece_Flow_Action_Factory
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
    // {{{ factory()

    /**
     * Creates an action object from a configuration file or a cache.
     *
     * @param string $class
     * @return mixed
     * @throws PEAR_ErrorStack
     * @static
     */
    function &factory($class)
    {
        if (!array_key_exists($class, $GLOBALS['PIECE_FLOW_Action_Instances'])) {
            $result = &Piece_Flow_Action_Factory::_load($class);
            if (Piece_Flow_Error::isError($result)) {
                return $result;
            }

            $instance = &new $class();
            $GLOBALS['PIECE_FLOW_Action_Instances'][$class] = &$instance;
        }

        return $GLOBALS['PIECE_FLOW_Action_Instances'][$class];
    }

    // }}}
    // {{{ setActionPath()

    /**
     * Sets a action path.
     *
     * @param string $actionPath
     */
    function setActionPath($actionPath)
    {
        $GLOBALS['PIECE_FLOW_Action_Path'] = $actionPath;
    }

    /**#@-*/

    /**#@+
     * @access private
     */

    // }}}
    // {{{ _load()

    /**
     * Loads a action class corresponding to the given class name.
     *
     * @param string $className
     * @throws PEAR_ErrorStack
     * @static
     */
    function &_load($className)
    {
        if (is_null($GLOBALS['PIECE_FLOW_Action_Path'])) {
            $error = &Piece_Flow_Error::raiseError(PIECE_FLOW_ERROR_NOT_GIVEN,
                                                   'The action path was not given.'
                                                   );
            return $error;
        }

        $file = realpath("{$GLOBALS['PIECE_FLOW_Action_Path']}/" . str_replace('_', '/', $className) . '.php');

        if (!$file) {
            $error = &Piece_Flow_Error::raiseError(PIECE_FLOW_ERROR_NOT_FOUND,
                                                   "The action file for the class [ $className ] not found."
                                                   );
            return $error;
        }

        if (!is_readable($file)) {
            $error = &Piece_Flow_Error::raiseError(PIECE_FLOW_ERROR_NOT_READABLE,
                                                   "The action file [ $file ] was not readable."
                                                   );
            return $error;
        }

        if (!@include_once $file) {
            $error = &Piece_Flow_Error::raiseError(PIECE_FLOW_ERROR_NOT_FOUND,
                                                   "The action file [ $file ] not found or was not readable."
                                                   );
            return $error;
        }

        if (version_compare(phpversion(), '5.0.0', '<')) {
            $result = class_exists($className);
        } else {
            $result = class_exists($className, false);
        }

        if (!$result) {
            $error = &Piece_Flow_Error::raiseError(PIECE_FLOW_ERROR_NOT_FOUND,
                                                   "The action [ $className ] not defined in the file [ $file ]."
                                                   );
            return $error;
        }

        $return = null;
        return $return;
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
