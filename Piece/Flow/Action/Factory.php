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
 * @since      File available since Release 1.0.0
 */

require_once 'Piece/Flow/Error.php';
require_once 'Piece/Flow/ClassLoader.php';

// {{{ GLOBALS

$GLOBALS['PIECE_FLOW_Action_Instances'] = array();
$GLOBALS['PIECE_FLOW_Action_Directory'] = null;

// }}}
// {{{ Piece_Flow_Action_Factory

/**
 * A factory class for creating action objects.
 *
 * @package    Piece_Flow
 * @copyright  2006-2007 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @since      Class available since Release 1.0.0
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
     * @static
     */

    // }}}
    // {{{ factory()

    /**
     * Creates an action object from a configuration file or a cache.
     *
     * @param string $class
     * @return mixed
     * @throws PIECE_FLOW_ERROR_NOT_GIVEN
     * @throws PIECE_FLOW_ERROR_NOT_FOUND
     * @throws PIECE_FLOW_ERROR_NOT_READABLE
     * @throws PIECE_FLOW_ERROR_CANNOT_READ
     */
    function &factory($class)
    {
        if (!array_key_exists($class, $GLOBALS['PIECE_FLOW_Action_Instances'])) {
            if (!Piece_Flow_ClassLoader::loaded($class)) {
                if (is_null($GLOBALS['PIECE_FLOW_Action_Directory'])) {
                    Piece_Flow_Error::push(PIECE_FLOW_ERROR_NOT_GIVEN,
                                           'The action directory is not given.'
                                           );
                    $return = null;
                    return $return;
                }

                Piece_Flow_ClassLoader::load($class, $GLOBALS['PIECE_FLOW_Action_Directory']);
                if (Piece_Flow_Error::hasErrors('exception')) {
                    $return = null;
                    return $return;
                }

                if (!Piece_Flow_ClassLoader::loaded($class)) {
                    Piece_Flow_Error::push(PIECE_FLOW_ERROR_NOT_FOUND,
                                           "The class [ $class ] not found in the loaded file."
                                           );
                    $return = null;
                    return $return;
                }
            }

            $GLOBALS['PIECE_FLOW_Action_Instances'][$class] = &new $class();
        }

        return $GLOBALS['PIECE_FLOW_Action_Instances'][$class];
    }

    // }}}
    // {{{ setActionDirectory()

    /**
     * Sets a directory as the action directory.
     *
     * @param string $directory
     */
    function setActionDirectory($directory)
    {
        $GLOBALS['PIECE_FLOW_Action_Directory'] = $directory;
    }

    // }}}
    // {{{ clearInstances()

    /**
     * Clears the action instances.
     */
    function clearInstances()
    {
        $GLOBALS['PIECE_FLOW_Action_Instances'] = array();
    }

    // }}}
    // {{{ getInstances()

    /**
     * Gets the action instances.
     *
     * @return array
     */
    function getInstances()
    {
        return $GLOBALS['PIECE_FLOW_Action_Instances'];
    }

    // }}}
    // {{{ setInstances()

    /**
     * Sets an array as the action instances.
     *
     * @param array $instances
     */
    function setInstances($instances)
    {
        $GLOBALS['PIECE_FLOW_Action_Instances'] = $instances;
    }

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
