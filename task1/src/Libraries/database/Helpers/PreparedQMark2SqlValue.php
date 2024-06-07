<?php

declare(strict_types = 1);

namespace Libraries\database\Helpers;

/**
 *
 */
class PreparedQMark2SqlValue
{
    // Constructor
    /**
     * @param $vals
     */
    public function __construct($vals)
    {
        $this->ctr = 0;
        $this->vals = $vals;
    }

    /**
     * @param $matches
     *
     * @return mixed
     */
    public function call($matches)
    {
        /**
         * If ? is found as expected in regex used in function convert2sql
         * /('[^']*')|(\"[^\"]*\")|([?])/
         *
         */
        if ($matches[3] == '?') {
            $this->ctr++;
            return $this->vals[$this->ctr - 1];
        } else {
            return $matches[0];
        }
    }
}
