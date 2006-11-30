<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * PHP versions 4 and 5
 *
 * Copyright (c) 2006 KUBO Atsuhiro <iteman@users.sourceforge.net>,
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
 * @author     KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @copyright  2006 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    SVN: $Id$
 * @link       http://piece-framework.com/piece-flow/
 * @see        Piece_Flow_Action_Factory
 * @since      File available since Release 1.0.0
 */

require_once 'PHPUnit.php';
require_once 'Piece/Flow/Action/Factory.php';
require_once 'Piece/Flow/Error.php';

// {{{ Piece_Flow_Action_FactoryTestCase

/**
 * TestCase for Piece_Flow_Action_Factory
 *
 * @package    Piece_Flow
 * @author     KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @copyright  2006 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @link       http://piece-framework.com/piece-flow/
 * @see        Piece_Flow_Action_Factory
 * @since      Class available since Release 1.0.0
 */
class Piece_Flow_Action_FactoryTestCase extends PHPUnit_TestCase
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

    function setUp()
    {
        Piece_Flow_Error::pushCallback(create_function('$error', 'var_dump($error); return ' . PEAR_ERRORSTACK_DIE . ';'));
    }

    function tearDown()
    {
        $GLOBALS['PIECE_FLOW_Action_Instances'] = array();
        Piece_Flow_Action_Factory::setActionDirectory(null);
        Piece_Flow_Error::clearErrors();
        Piece_Flow_Error::popCallback();
    }

    function testFailureToCreateByEmptyActionDirectory()
    {
        Piece_Flow_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));

        Piece_Flow_Action_Factory::factory('Piece_Flow_Action_FooAction');

        $this->assertTrue(Piece_Flow_Error::hasErrors('exception'));

        $error = Piece_Flow_Error::pop();

        $this->assertEquals(PIECE_FLOW_ERROR_NOT_GIVEN, $error['code']);

        Piece_Flow_Error::popCallback();
    }

    function testFailureToCreateByNonExistingFile()
    {
        Piece_Flow_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));

        Piece_Flow_Action_Factory::setActionDirectory(dirname(__FILE__) . '/../../..');
        Piece_Flow_Action_Factory::factory('Piece_Flow_Action_NonExistingAction');

        $this->assertTrue(Piece_Flow_Error::hasErrors('exception'));

        $error = Piece_Flow_Error::pop();

        $this->assertEquals(PIECE_FLOW_ERROR_NOT_FOUND, $error['code']);

        Piece_Flow_Error::popCallback();
    }

    function testFailureToCreateByInvalidAction()
    {
        Piece_Flow_Error::pushCallback(create_function('$error', 'return ' . PEAR_ERRORSTACK_PUSHANDLOG . ';'));

        Piece_Flow_Action_Factory::setActionDirectory(dirname(__FILE__) . '/../../..');
        Piece_Flow_Action_Factory::factory('Piece_Flow_Action_InvalidAction');

        $this->assertTrue(Piece_Flow_Error::hasErrors('exception'));

        $error = Piece_Flow_Error::pop();

        $this->assertEquals(PIECE_FLOW_ERROR_NOT_FOUND, $error['code']);

        Piece_Flow_Error::popCallback();
    }

    function testFactory()
    {
        Piece_Flow_Action_Factory::setActionDirectory(dirname(__FILE__) . '/../../..');
        $fooAction = &Piece_Flow_Action_Factory::factory('Piece_Flow_Action_FooAction');

        $this->assertTrue(is_a($fooAction, 'Piece_Flow_Action_FooAction'));

        $barAction = &Piece_Flow_Action_Factory::factory('Piece_Flow_Action_BarAction');

        $this->assertTrue(is_a($barAction, 'Piece_Flow_Action_BarAction'));

        $fooAction->baz = 'qux';

        $action = &Piece_Flow_Action_Factory::factory('Piece_Flow_Action_FooAction');

        $this->assertTrue(array_key_exists('baz', $fooAction));
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
