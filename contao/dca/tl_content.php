<?php

$GLOBALS['TL_DCA']['tl_content']['palettes']['google_reviews_widget'] =
  '{type_legend},type,headline;' .
  '{google_reviews_legend},google_api_key,google_place_id,google_business_name,google_business_url,google_review_url;' .
  '{template_legend:hide},customTpl;' .
  '{protected_legend:hide},protected;' .
  '{expert_legend:hide},cssID;' .
  '{invisible_legend:hide},invisible,start,stop';

$GLOBALS['TL_DCA']['tl_content']['fields']['google_api_key'] = [
  'exclude' => true,
  'inputType' => 'text',
  'eval' => ['tl_class' => 'w50', 'mandatory' => false],
  'sql' => "varchar(255) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['google_place_id'] = [
  'exclude' => true,
  'inputType' => 'text',
  'eval' => ['tl_class' => 'w50', 'mandatory' => false],
  'sql' => "varchar(255) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['google_business_name'] = [
  'exclude' => true,
  'inputType' => 'text',
  'eval' => ['tl_class' => 'w50'],
  'sql' => "varchar(255) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['google_business_url'] = [
  'exclude' => true,
  'inputType' => 'text',
  'eval' => ['tl_class' => 'w50', 'rgxp' => 'url', 'decodeEntities' => true],
  'sql' => "varchar(255) NOT NULL default ''",
];

$GLOBALS['TL_DCA']['tl_content']['fields']['google_review_url'] = [
  'exclude' => true,
  'inputType' => 'text',
  'eval' => ['tl_class' => 'long clr', 'rgxp' => 'url', 'decodeEntities' => true],
  'sql' => "varchar(500) NOT NULL default ''",
];