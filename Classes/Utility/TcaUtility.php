<?php

namespace DiakonieSH\DshMastodon\Utility;

class TcaUtility
{
    /**
     * @param array{table: string, row: array<string, mixed>} $params
     * @return void
     */
    public function accountLabel(array &$params): void
    {
        $params['title'] = $params['row']['domain'] . '/@' . $params['row']['username'];
    }
}
