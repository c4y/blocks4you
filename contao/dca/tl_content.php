<?php

/**
 * Global operations for content elements
 */
$GLOBALS['TL_DCA']['tl_content']['list']['global_operations']['insert_block'] = [
    'label'           =>  &$GLOBALS['TL_LANG']['tl_content']['insert_block'],
    'href'            => 'act=paste&mode=create&showBlocks=1',
    'icon'            => 'bundles/blocks4you/icons/block-insert.svg',
    'primary'         => true,
];
