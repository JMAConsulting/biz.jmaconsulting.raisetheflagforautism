<?php
// This file declares a managed database record of type "ReportTemplate".
// The record will be automatically inserted, updated, or deleted from the
// database as appropriate. For more details, see "hook_civicrm_managed" at:
// http://wiki.civicrm.org/confluence/display/CRMDOC42/Hook+Reference
return [
  [
    'module' => 'biz.jmaconsulting.raisetheflagforautism',
    'name' => 'Flag Raising',
    'update' => 'never',
    'entity' => 'CustomGroup',
    'params' => [
      'version' => 3,
      "title" => "Flag Raising",
      'name' => 'flag_raising',
      "extends" => "Event",
      'is_active' => 1,
      'api.CustomField.create' => [
        'custom_group_id' => '$value.id',
        'label' => "Do you require a flag?",
        'name' => 'require_flag',
        'data_type' => "Boolean",
        'html_type' => "Radio",
        'weight' => 1,
        'is_view' => 1,
      ],
      'api.CustomField.create.1' => [
        'custom_group_id' => '$value.id',
        'label' => "What is your name?",
        'name' => 'rf_name',
        'data_type' => "String",
        'html_type' => "Text",
        'text_length' => '255',
        'is_view' => 1,
        'weight' => 2,
        'help_post' => '(Will not be shared with the public - will be used to verify ceremony and send flag as required)',
      ],
      'api.CustomField.create.2' => [
        'custom_group_id' => '$value.id',
        'label' => "What is your email address?",
        'name' => 'rf_email_address',
        'data_type' => "String",
        'html_type' => "Text",
        'text_length' => '255',
        'is_view' => 1,
        'weight' => 3,
        'help_post' => '(Will not be shared with the public - will be used to verify ceremony and send flag as required)',
      ],
      'api.CustomField.create.3' => [
        'custom_group_id' => '$value.id',
        'label' => "What is your mailing address?",
        'name' => 'rf_mailing_address',
        'data_type' => "Memo",
        'html_type' => "TextArea",
        'mask' => "rows=4,cols=60",
        'is_view' => 1,
        'weight' => 4,
        'help_post' => '(Will not be shared with the public - will be used to verify ceremony and send flag as required)',
      ],
    ],
  ]
];
