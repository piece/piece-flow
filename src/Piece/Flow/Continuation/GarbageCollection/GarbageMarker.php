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

namespace Piece\Flow\Continuation\GarbageCollection;

/**
 * @since Class available since Release 2.0.0
 */
class GarbageMarker
{
    /**
     * @var boolean
     */
    protected $enabled = false;

    /**
     * @var integer
     */
    protected $modificationTimestamp;

    /**
     * @var boolean
     */
    protected $swept = false;

    /**
     * @param integer $modificationTimestamp
     */
    public function __construct($modificationTimestamp)
    {
        $this->modificationTimestamp = $modificationTimestamp;
    }

    /**
     * @return boolean
     */
    public function markAsEnabled()
    {
        $this->enabled = true;
    }

    /**
     * @return boolean
     */
    public function isEnabled()
    {
        return $this->enabled;
    }

    /**
     * @param integer $modificationTimestamp
     */
    public function updateModificationTimestamp($modificationTimestamp)
    {
        $this->modificationTimestamp = $modificationTimestamp;
    }

    /**
     * @return boolean
     */
    public function markAsSwept()
    {
        $this->swept = true;
    }

    /**
     * @return boolean
     */
    public function isSwept()
    {
        return $this->swept;
    }

    /**
     * @param  integer $currentTimestamp
     * @param  integer $expirationTime
     * @return boolean
     */
    public function isExpired($currentTimestamp, $expirationTime)
    {
        return $currentTimestamp - $this->modificationTimestamp > $expirationTime;
    }
}
