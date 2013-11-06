<?php
/*
 * Copyright (c) 2013 KUBO Atsuhiro <kubo@iteman.jp>,
 * All rights reserved.
 *
 * This file is part of Piece_Flow.
 *
 * This program and the accompanying materials are made available under
 * the terms of the BSD 2-Clause License which accompanies this
 * distribution, and is available at http://opensource.org/licenses/BSD-2-Clause
 */

namespace Piece\Flow\PageFlow;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @package    Piece_Flow
 * @copyright  2013 KUBO Atsuhiro <kubo@iteman.jp>
 * @license    http://opensource.org/licenses/BSD-2-Clause  The BSD 2-Clause License
 * @version    Release: @package_version@
 * @since      Class available since Release 2.0.0
 */
class PageFlowRegistries extends ArrayCollection
{
    /**
     * @param  string $pageFlowID
     * @return string
     */
    public function getFileName($pageFlowID)
    {
        foreach ($this as $pageFlowRegistry) {
            $definitionFile = $pageFlowRegistry->getFileName($pageFlowID);
            if (file_exists($definitionFile)) {
                return $definitionFile;
            }
        }

        return null;
    }
}
