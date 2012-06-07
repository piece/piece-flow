<?php
/* vim: set expandtab tabstop=4 shiftwidth=4: */

/**
 * PHP versions 4 and 5
 *
 * Copyright (c) 2006-2008 KUBO Atsuhiro <kubo@iteman.jp>,
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
 * @copyright  2006-2008 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://www.opensource.org/licenses/bsd-license.php  BSD License (revised)
 * @version    SVN: $Id$
 * @since      File available since Release 0.1.0
 */

require_once 'PEAR/PackageFileManager2.php';
require_once 'PEAR.php';

PEAR::staticPushErrorHandling(PEAR_ERROR_CALLBACK, create_function('$error', 'var_dump($error); exit();'));

$releaseVersion = '1.16.0';
$releaseStability = 'stable';
$apiVersion = '1.7.0';
$apiStability = 'stable';
$notes = 'A new release of Piece_Flow is now available.

What\'s New in Piece_Flow 1.16.0

 * Enhanced interfaces: getActiveFlowExecutionTicket() has been added to the Piece_Flow_Continuation_Service class. It can be used to get the flow execution ticket for the active flow execution. And also isViewState() has been added to the Piece_Flow class. It can be used to get whether the current state of a flow execution is a view state or not.
 * Improved error handling: The behavior of internal error handling has been changed so as to handle only own and "exception" level errors.
 * A defect fix: A defect that the outer frame of an already removed flow execution to be created by garbage collection has been fixed.

See the following release notes for details.

Enhancements
============

- Added getActiveFlowExecutionTicket() to get the flow execution ticket for the active flow execution. (Piece_Flow_Continuation_FlowExecution, Piece_Flow_Continuation_Service)
- Added isViewState() to get whether the current state of a flow execution is a view state or not. (Piece_Flow)
- Changed the behavior of internal error handling so as to handle only own and "exception" level errors.
- Replaced all uses of PIECE_FLOW_ERROR_FLOW_NAME_NOT_GIVEN with PIECE_FLOW_ERROR_FLOW_ID_NOT_GIVEN.
- Changed the behavior of internal error handling that an exception from Stagehand_FSM is always wrapped with Piece_Flow_Error::push().

Defect Fixes
============

- Fixed a defect that the outer frame of an already removed flow execution to be created by garbage collection. (Piece_Flow_Continuation_FlowExecution)';

$package = new PEAR_PackageFileManager2();
$package->setOptions(array('filelistgenerator' => 'file',
                           'changelogoldtonew' => false,
                           'simpleoutput'      => true,
                           'baseinstalldir'    => '/',
                           'packagefile'       => 'package.xml',
                           'packagedirectory'  => '.',
                           'ignore'            => array('package.php'))
                     );

$package->setPackage('Piece_Flow');
$package->setPackageType('php');
$package->setSummary('A web flow engine and continuation server');
$package->setDescription('Piece_Flow is a web flow engine and continuation server.

Piece_Flow provides a stateful programming model for developers, and high security for applications.');
$package->setChannel('pear.piece-framework.com');
$package->setLicense('BSD License (revised)', 'http://www.opensource.org/licenses/bsd-license.php');
$package->setAPIVersion($apiVersion);
$package->setAPIStability($apiStability);
$package->setReleaseVersion($releaseVersion);
$package->setReleaseStability($releaseStability);
$package->setNotes($notes);
$package->setPhpDep('4.3.0');
$package->setPearinstallerDep('1.4.3');
$package->addPackageDepWithChannel('required', 'Stagehand_FSM', 'pear.piece-framework.com', '1.10.0');
$package->addPackageDepWithChannel('required', 'Cache_Lite', 'pear.php.net', '1.7.0');
$package->addPackageDepWithChannel('required', 'PEAR', 'pear.php.net', '1.4.3');
$package->addMaintainer('lead', 'iteman', 'KUBO Atsuhiro', 'kubo@iteman.jp');
$package->addGlobalReplacement('package-info', '@package_version@', 'version');
$package->generateContents();

if (array_key_exists(1, $_SERVER['argv']) && $_SERVER['argv'][1] == 'make') {
    $package->writePackageFile();
} else {
    $package->debugPackageFile();
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
