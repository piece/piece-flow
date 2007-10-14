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
 * @see        http://ja.wikipedia.org/wiki/%E3%82%AF%E3%83%AD%E3%83%BC%E3%82%B8%E3%83%A3
 * @see        http://ja.wikipedia.org/wiki/%E9%AB%98%E9%9A%8E%E9%96%A2%E6%95%B0
 * @since      File available since Release 1.5.0
 */

require_once realpath(dirname(__FILE__) . '/../../prepare.php');
require_once 'PHPUnit.php';
require_once 'Piece/Flow/Closure.php';

// {{{ Piece_Flow_ClosureTestCase

/**
 * TestCase for Piece_Flow_Closure
 *
 * @package    Piece_Flow
 * @copyright  2006-2007 KUBO Atsuhiro <iteman@users.sourceforge.net>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    Release: @package_version@
 * @see        http://ja.wikipedia.org/wiki/%E3%82%AF%E3%83%AD%E3%83%BC%E3%82%B8%E3%83%A3
 * @see        http://ja.wikipedia.org/wiki/%E9%AB%98%E9%9A%8E%E9%96%A2%E6%95%B0
 * @since      Class available since Release 1.5.0
 */
class Piece_Flow_ClosureTestCase extends PHPUnit_TestCase
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

    function testSingleClosureShouldWork()
    {
        $count = 0;
        $counter = Piece_Flow_Closure::create('', 'return ++$count;', array('count' => $count));

        $this->assertEquals(1, $counter());
        $this->assertEquals(2, $counter());
        $this->assertEquals(3, $counter());
        $this->assertEquals(4, $counter());
        $this->assertEquals(5, $counter());
    }

    function testMultipleClosuresShouldWork()
    {
        $count = 0;
        $counter1 = Piece_Flow_Closure::create('', 'return ++$count;', array('count' => $count));
        $counter2 = Piece_Flow_Closure::create('', 'return ++$count;', array('count' => $count));

        $this->assertEquals(1, $counter1());
        $this->assertEquals(1, $counter2());

        $this->assertEquals(2, $counter1());
        $this->assertEquals(2, $counter2());

        $this->assertEquals(3, $counter1());
        $this->assertEquals(4, $counter1());

        $this->assertEquals(3, $counter2());
        $this->assertEquals(4, $counter2());

        $this->assertEquals(5, $counter1());
        $this->assertEquals(5, $counter2());
    }

    function functionAndTwoClosuresByReference()
    {
        $x = 0;
        $this->_f = Piece_Flow_Closure::create('', 'return ++$x;', array('x' => &$x));
        $this->_g = Piece_Flow_Closure::create('', 'return --$x;', array('x' => &$x));
        $x = 1;
        $f = $this->_f;
        return $f();
    }

    function testFunctionAndTwoClosuresShouldUseSameScopeByReference()
    {
        $this->assertEquals(2, $this->functionAndTwoClosuresByReference());

        $g = $this->_g;

        $this->assertEquals(1, $g());

        $f = $this->_f;

        $this->assertEquals(2, $f());
    }

    function functionAndTwoClosuresByValue()
    {
        $x = 0;
        $this->_f = Piece_Flow_Closure::create('', 'return ++$x;', array('x' => $x));
        $this->_g = Piece_Flow_Closure::create('', 'return --$x;', array('x' => $x));
        $x = 1;
        $f = $this->_f;
        return $f();
    }

    function testFunctionAndTwoClosuresShouldUseDifferentScopeByValue()
    {
        $this->assertEquals(1, $this->functionAndTwoClosuresByValue());

        $g = $this->_g;

        $this->assertEquals(-1, $g());

        $f = $this->_f;

        $this->assertEquals(2, $f());
    }

    function testClosureShouldWorkWithArguments()
    {
        $add = Piece_Flow_Closure::create('$x, $y', 'return $x + $y;');
        $sub = Piece_Flow_Closure::create('$x, $y', 'return $x - $y;');
        $mul = Piece_Flow_Closure::create('$x, $y', 'return $x * $y;');
        $div = Piece_Flow_Closure::create('$x, $y', 'return $x / $y;');

        $this->assertEquals(15, $add(10, 5));
        $this->assertEquals(5, $sub(10, 5));
        $this->assertEquals(50, $mul(10, 5));
        $this->assertEquals(2, $div(10, 5));
    }

    function testHigherOrderFunctionShouldWork1()
    {
        $add = Piece_Flow_Closure::create('$x, $y', 'return $x + $y;');
        $mul = Piece_Flow_Closure::create('$x, $y', 'return $x * $y;');
        $calc = Piece_Flow_Closure::create('$f', 'return $f(2, 3);');

        $this->assertEquals(5, $calc($add));
        $this->assertEquals(6, $calc($mul));
    }

    function testHigherOrderFunctionShouldWork2()
    {
        $add = Piece_Flow_Closure::create('$x',
                                          "return Piece_Flow_Closure::create('\$y', 'return \$y + \$x;', array('x' => \$x));"
                                          );
        $f = $add(2);

        $this->assertTrue(is_callable($f));
        $this->assertEquals(5, $f(3));
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
