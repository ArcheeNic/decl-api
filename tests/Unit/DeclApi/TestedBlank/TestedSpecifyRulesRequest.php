<?php namespace Tests\Unit\DeclApi\TestedBlank;

use DeclApi\Core\Request;

class TestedSpecifyRulesRequest extends Request
{
    /**
     * @throws \Exception
     */
    protected function initRules()
    {
        $this
            ->rulesInfo()
            ->see('json',TestedSpecifyRulesObject::class);
    }
}