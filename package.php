<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * PHP versions 4 and 5
 *
 * Copyright (c) 2006, KUBO Atsuhiro <iteman@users.sourceforge.net>
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
 * @link       http://iteman.typepad.jp/piece/
 * @see        Stagehand_TestRunner_PHPUnitTestRunner::runAll()
 * @since      File available since Release 0.1.0
 */

require_once 'PEAR/PackageFileManager2.php';

PEAR::staticPushErrorHandling(PEAR_ERROR_CALLBACK, create_function('$error', 'var_dump($error); exit();'));

$version = '1.3.0';
$apiVersion = '1.0.0';
$notes = 'This release includes a few enhancements as follows:

<<< Enhancements >>>

* Piece_Flow_Action
- Added support for raising an exception when an invalid event is returned from actions.

* Piece_Flow
- Changed the error code from PIECE_FLOW_ERROR_INVALID_OPERATION to PIECE_FLOW_ERROR_ALREADY_SHUTDOWN when triggering events after shutting down a flow.

* Piece_Flow_Continuation
- Changed the code so as never to start a new flow execution again after shtting down in single flow mode.

* Piece_Flow_Error
- Added PIECE_FLOW_ERROR_ALREADY_SHUTDOWN constant.
- Added PIECE_FLOW_ERROR_INVALID_EVENT constant.';

$package = new PEAR_PackageFileManager2();
$package->setOptions(array('filelistgenerator' => 'svn',
                           'changelogoldtonew' => false,
                           'simpleoutput'      => true,
                           'baseinstalldir'    => '/',
                           'packagefile'       => 'package2.xml',
                           'packagedirectory'  => '.')
                     );

$package->setPackage('Piece_Flow');
$package->setPackageType('php');
$package->setSummary('A web flow engine and a continuation server');
$package->setDescription('Piece_Flow is a generic web flow engine and a continuation server based on Finite State Machine(FSM). Piece_Flow provides a stateful programming model for developers, and high secrity for applications.');
$package->setChannel('pear.hatotech.org');
$package->setLicense('BSD License (revised)',
                     'http://www.opensource.org/licenses/bsd-license.php'
                     );
$package->setAPIVersion($apiVersion);
$package->setAPIStability('stable');
$package->setReleaseVersion($version);
$package->setReleaseStability('stable');
$package->setNotes($notes);
$package->setPhpDep('4.3.0');
$package->setPearinstallerDep('1.4.3');
$package->addMaintainer('lead', 'iteman', 'KUBO Atsuhiro', 'iteman@users.sourceforge.net');
$package->addIgnore(array('package.php', 'package.xml', 'package2.xml'));
$package->addGlobalReplacement('package-info', '@package_version@', 'version');
$package->generateContents();
$package1 = &$package->exportCompatiblePackageFile1();

if (array_key_exists(1, $_SERVER['argv'])
    && $_SERVER['argv'][1] == 'make'
    ) {
    $package->writePackageFile();
    $package1->writePackageFile();
} else {
    $package->debugPackageFile();
    $package1->debugPackageFile();
}

exit();

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
