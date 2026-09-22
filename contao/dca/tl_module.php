<?php

$GLOBALS['TL_DCA']['tl_module']['palettes']['google_reviews_widget'] =
    '{title_legend},name,type;' .
    '{google_reviews_legend},google_api_key,google_place_id,google_business_name,google_business_url,google_review_url;' .
    '{google_appearance_legend},google_position,google_color;' .
    '{template_legend:hide},customTpl;' .
    '{protected_legend:hide},protected;' .
    '{expert_legend:hide},cssID';

$GLOBALS['TL_DCA']['tl_module']['fields']['google_api_key'] = [
    'exclude' => true,
    'inputType' => 'text',
    'eval' => ['tl_class' => 'w50'],
    'sql' => "varchar(255) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_module']['fields']['google_place_id'] = [
    'exclude' => true,
    'inputType' => 'text',
    'eval' => ['tl_class' => 'w50', 'mandatory' => true],
    'sql' => "varchar(255) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_module']['fields']['google_business_name'] = [
    'exclude' => true,
    'inputType' => 'text',
    'eval' => ['tl_class' => 'w50'],
    'sql' => "varchar(255) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_module']['fields']['google_business_url'] = [
    'exclude' => true,
    'inputType' => 'text',
    'eval' => ['tl_class' => 'w50', 'rgxp' => 'url', 'decodeEntities' => true],
    'sql' => "varchar(255) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_module']['fields']['google_review_url'] = [
    'exclude' => true,
    'inputType' => 'text',
    'eval' => ['tl_class' => 'long clr', 'rgxp' => 'url', 'decodeEntities' => true],
    'sql' => "varchar(500) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_module']['fields']['google_position'] = [
    'exclude' => true,
    'inputType' => 'select',
    'options' => ['bottom-left', 'bottom-right'],
    'reference' => &$GLOBALS['TL_LANG']['tl_module']['google_position_options'],
    'eval' => ['tl_class' => 'w50', 'includeBlankOption' => false],
    'sql' => "varchar(32) NOT NULL default 'bottom-left'",
];

$GLOBALS['TL_DCA']['tl_module']['fields']['google_color'] = [
    'exclude' => true,
    'inputType' => 'text',
    'eval' => ['tl_class' => 'w50', 'colorpicker' => true, 'isHexColor' => true, 'maxlength' => 6, 'decodeEntities' => true],
    'sql' => "varchar(6) NOT NULL default ''",
];
