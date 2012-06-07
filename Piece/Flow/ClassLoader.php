<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * PHP versions 4 and 5
 *
 * Copyright (c) 2007-2008 KUBO Atsuhiro <kubo@iteman.jp>,
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
 * @copyright  2007-2008 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version    Release: @package_version@
 * @since      File available since Release 1.10.0
 */

require_once 'Piece/Flow/Error.php';

// {{{ Piece_Flow_ClassLoader

/**
 * A class loader.
 *
 * @package    Piece_Flow
 * @copyright  2007-2008 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  New BSD License
 * @version    Release: @package_version@
 * @since      Class available since Release 1.10.0
 */
class Piece_Flow_ClassLoader
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
    // {{{ load()

    /**
     * Loads a class.
     *
     * @param string $class
     * @param string $directory
     * @throws PIECE_FLOW_ERROR_NOT_READABLE
     * @throws PIECE_FLOW_ERROR_NOT_FOUND
     * @throws PIECE_FLOW_ERROR_CANNOT_READ
     * @static
     */
    function load($class, $directory = null)
    {
        $file = str_replace('_', '/', $class) . '.php';

        if (!is_null($directory)) {
            $file = "$directory/$file";

            if (!file_exists($file)) {
                Piece_Flow_Error::push(PIECE_FLOW_ERROR_NOT_FOUND,
                                       "The class file [ $file ] for the class [ $class ] is not found."
                                       );
                return;
            }

            if (!is_readable($file)) {
                Piece_Flow_Error::push(PIECE_FLOW_ERROR_NOT_READABLE,
                                       "The class file [ $file ] is not readable."
                                       );
                return;
            }
        }

        if (!include_once $file) {
            Piece_Flow_Error::push(PIECE_FLOW_ERROR_CANNOT_READ,
                                   "The class file [ $file ] is not found or is not readable."
                                   );
        }
    }

    // }}}
    // {{{ loaded()

    /**
     * Returns whether the given class has already been loaded or not.
     *
     * @param string $class
     * @return boolean
     * @static
     */
    function loaded($class)
    {
        if (version_compare(phpversion(), '5.0.0', '<')) {
            return class_exists($class);
        } else {
            return class_exists($class, false);
        }
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
