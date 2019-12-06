<?php

use CRM_Raisetheflagforautism_ExtensionUtil as E;

/**
 * Form controller class
 *
 * @see https://wiki.civicrm.org/confluence/display/CRMDOC/QuickForm+Reference
 */
class CRM_Raisetheflagforautism_Form_AddEvent extends CRM_Core_Form {

  public $_elementNames;
  public static function customFieldBuildQuickForm(&$form, &$groupTree, $inactiveNeeded = FALSE, $prefix = '') {
    $form->assign_by_ref("{$prefix}groupTree", $groupTree);
    $postHelp = [];

    foreach ($groupTree as $id => $group) {
      CRM_Core_ShowHideBlocks::links($form, $group['title'], '', '');
      foreach ($group['fields'] as $field) {
        $required = CRM_Utils_Array::value('is_required', $field);
        //fix for CRM-1620
        if ($field['data_type'] == 'File') {
          if (!empty($field['element_value']['data'])) {
            $required = 0;
          }
        }

        $fieldId = $field['id'];
        $elementName = $field['element_name'];
        if (!empty($field['help_post'])) {
          $postHelp[$elementName] = $field['help_post'];
        }
        self::addQuickFormElement($form, $elementName, $fieldId, $required);
      }
    }
    $form->assign_by_ref('postHelp', $postHelp);
  }

  public static function addQuickFormElement(
    $qf, $elementName, $fieldId, $useRequired = TRUE, $search = FALSE, $label = NULL
  ) {
    $field = CRM_Core_BAO_CustomField::getFieldObject($fieldId);
    $widget = $field->html_type;
    $element = NULL;
    $customFieldAttributes = array();

    // Custom field HTML should indicate group+field name
    $groupName = CRM_Core_DAO::getFieldValue('CRM_Core_DAO_CustomGroup', $field->custom_group_id);
    $dataCrmCustomVal = $groupName . ':' . $field->name;
    $dataCrmCustomAttr = 'data-crm-custom="' . $dataCrmCustomVal . '"';
    $field->attributes .= $dataCrmCustomAttr;

    // Fixed for Issue CRM-2183
    if ($widget == 'TextArea' && $search) {
      $widget = 'Text';
    }

    $placeholder = $search ? ts('- any -') : ($useRequired ? ts('- select -') : ts('- none -'));

    // FIXME: Why are select state/country separate widget types?
    $isSelect = (in_array($widget, array(
      'Select',
      'Multi-Select',
      'Select State/Province',
      'Multi-Select State/Province',
      'Select Country',
      'Multi-Select Country',
      'CheckBox',
      'Radio',
    )));

    if ($isSelect) {
      $options = $field->getOptions($search ? 'search' : 'create');

      // Consolidate widget types to simplify the below switch statement
      if ($search || (strpos($widget, 'Select') !== FALSE)) {
        $widget = 'Select';
      }

      $customFieldAttributes['data-crm-custom'] = $dataCrmCustomVal;
      $selectAttributes = array('class' => 'crm-select2');

      // Search field is always multi-select
      if ($search || strpos($field->html_type, 'Multi') !== FALSE) {
        $selectAttributes['class'] .= ' huge';
        $selectAttributes['multiple'] = 'multiple';
        $selectAttributes['placeholder'] = $placeholder;
      }

      // Add data for popup link. Normally this is handled by CRM_Core_Form->addSelect
      $isSupportedWidget = in_array($widget, ['Select', 'Radio']);
      $canEditOptions = CRM_Core_Permission::check('administer CiviCRM');
      if ($field->option_group_id && !$search && $isSelect && $canEditOptions) {
        $customFieldAttributes += array(
          'data-api-entity' => $field->getEntity(),
          'data-api-field' => 'custom_' . $field->id,
          'data-option-edit-path' => 'civicrm/admin/options/' . CRM_Core_DAO::getFieldValue('CRM_Core_DAO_OptionGroup', $field->option_group_id),
        );
        $selectAttributes += $customFieldAttributes;
      }
    }

    $rangeDataTypes = ['Int', 'Float', 'Money'];

    if (!isset($label)) {
      $label = $field->label;
    }

    // at some point in time we might want to split the below into small functions

    switch ($widget) {
      case 'Text':
      case 'Link':
        if ($field->is_search_range && $search && in_array($field->data_type, $rangeDataTypes)) {
          $qf->add('text', $elementName . '_from', $label . ' ' . ts('From'), $field->attributes);
          $qf->add('text', $elementName . '_to', ts('To'), $field->attributes);
        }
        else {
          if ($field->text_length) {
            $field->attributes .= ' maxlength=' . $field->text_length;
            if ($field->text_length < 20) {
              $field->attributes .= ' size=' . $field->text_length;
            }
          }
          $element = $qf->add('text', $elementName, $label,
            $field->attributes,
            $useRequired && !$search
          );
        }
        break;

      case 'TextArea':
        $attributes = $dataCrmCustomAttr;
        if ($field->note_rows) {
          $attributes .= 'rows=' . $field->note_rows;
        }
        else {
          $attributes .= 'rows=4';
        }
        if ($field->note_columns) {
          $attributes .= ' cols=' . $field->note_columns;
        }
        else {
          $attributes .= ' cols=60';
        }
        if ($field->text_length) {
          $attributes .= ' maxlength=' . $field->text_length;
        }
        $element = $qf->add('textarea',
          $elementName,
          $label,
          $attributes,
          $useRequired && !$search
        );
        break;

      case 'Select Date':
        $attr = array('data-crm-custom' => $dataCrmCustomVal);
        //CRM-18379: Fix for date range of 'Select Date' custom field when include in profile.
        $minYear = isset($field->start_date_years) ? (date('Y') - $field->start_date_years) : NULL;
        $maxYear = isset($field->end_date_years) ? (date('Y') + $field->end_date_years) : NULL;

        $params = array(
          'date' => $field->date_format,
          'minDate' => isset($minYear) ? $minYear . '-01-01' : NULL,
          //CRM-18487 - max date should be the last date of the year.
          'maxDate' => isset($maxYear) ? $maxYear . '-12-31' : NULL,
          'time' => $field->time_format ? $field->time_format * 12 : FALSE,
        );
        if ($field->is_search_range && $search) {
          $qf->add('datepicker', $elementName . '_from', $label, $attr + array('placeholder' => ts('From')), FALSE, $params);
          $qf->add('datepicker', $elementName . '_to', NULL, $attr + array('placeholder' => ts('To')), FALSE, $params);
        }
        else {
          $element = $qf->add('datepicker', $elementName, $label, $attr, $useRequired && !$search, $params);
        }
        break;

      case 'Radio':
        if ($field->is_search_range && $search && in_array($field->data_type, $rangeDataTypes)) {
          $qf->add('text', $elementName . '_from', $label . ' ' . ts('From'), $field->attributes);
          $qf->add('text', $elementName . '_to', ts('To'), $field->attributes);
        }
        else {
          $choice = array();
          parse_str($field->attributes, $radioAttributes);
          $radioAttributes = array_merge($radioAttributes, $customFieldAttributes);

          foreach ($options as $v => $l) {
            $choice[] = $qf->createElement('radio', NULL, '', $l, (string) $v, $radioAttributes);
          }
          $element = $qf->addGroup($choice, $elementName, $label);
          $optionEditKey = 'data-option-edit-path';
          if (isset($selectAttributes[$optionEditKey])) {
            $element->setAttribute($optionEditKey, $selectAttributes[$optionEditKey]);
          }

          if ($useRequired && !$search) {
            $qf->addRule($elementName, ts('%1 is a required field.', array(1 => $label)), 'required');
          }
          else {
            $element->setAttribute('allowClear', TRUE);
          }
        }
        break;

      // For all select elements
      case 'Select':
        if ($field->is_search_range && $search && in_array($field->data_type, $rangeDataTypes)) {
          $qf->add('text', $elementName . '_from', $label . ' ' . ts('From'), $field->attributes);
          $qf->add('text', $elementName . '_to', ts('To'), $field->attributes);
        }
        else {
          if (empty($selectAttributes['multiple'])) {
            $options = array('' => $placeholder) + $options;
          }
          $element = $qf->add('select', $elementName, $label, $options, $useRequired && !$search, $selectAttributes);

          // Add and/or option for fields that store multiple values
          if ($search && self::isSerialized($field)) {

            $operators = array(
              $qf->createElement('radio', NULL, '', ts('Any'), 'or', array('title' => ts('Results may contain any of the selected options'))),
              $qf->createElement('radio', NULL, '', ts('All'), 'and', array('title' => ts('Results must have all of the selected options'))),
            );
            $qf->addGroup($operators, $elementName . '_operator');
            $qf->setDefaults(array($elementName . '_operator' => 'or'));
          }
        }
        break;

      case 'CheckBox':
        $check = array();
        foreach ($options as $v => $l) {
          $check[] = &$qf->addElement('advcheckbox', $v, NULL, $l, $customFieldAttributes);
        }

        $group = $element = $qf->addGroup($check, $elementName, $label);
        $optionEditKey = 'data-option-edit-path';
        if (isset($customFieldAttributes[$optionEditKey])) {
          $group->setAttribute($optionEditKey, $customFieldAttributes[$optionEditKey]);
        }

        if ($useRequired && !$search) {
          $qf->addRule($elementName, ts('%1 is a required field.', array(1 => $label)), 'required');
        }
        break;

      case 'File':
        // we should not build upload file in search mode
        if ($search) {
          return NULL;
        }
        $element = $qf->add(
          strtolower($field->html_type),
          $elementName,
          $label,
          $field->attributes,
          $useRequired && !$search
        );
        $qf->addUploadElement($elementName);
        break;

      case 'RichTextEditor':
        $attributes = array(
          'rows' => $field->note_rows,
          'cols' => $field->note_columns,
          'data-crm-custom' => $dataCrmCustomVal,
        );
        if ($field->text_length) {
          $attributes['maxlength'] = $field->text_length;
        }
        $element = $qf->add('wysiwyg', $elementName, $label, $attributes, $useRequired && !$search);
        break;

      case 'Autocomplete-Select':
        static $customUrls = array();
        // Fixme: why is this a string in the first place??
        $attributes = array();
        if ($field->attributes) {
          foreach (explode(' ', $field->attributes) as $at) {
            if (strpos($at, '=')) {
              list($k, $v) = explode('=', $at);
              $attributes[$k] = trim($v, ' "');
            }
          }
        }
        if ($field->data_type == 'ContactReference') {
          // break if contact does not have permission to access ContactReference
          if (!CRM_Core_Permission::check('access contact reference fields')) {
            break;
          }
          $attributes['class'] = (isset($attributes['class']) ? $attributes['class'] . ' ' : '') . 'crm-form-contact-reference huge';
          $attributes['data-api-entity'] = 'Contact';
          $element = $qf->add('text', $elementName, $label, $attributes, $useRequired && !$search);

          $urlParams = "context=customfield&id={$field->id}";
          $idOfelement = $elementName;
          // dev/core#362 if in an onbehalf profile clean up the name to get rid of square brackets that break the select 2 js
          // However this caused regression https://lab.civicrm.org/dev/core/issues/619 so it has been hacked back to
          // only affecting on behalf - next time someone looks at this code it should be with a view to overhauling it
          // rather than layering on more hacks.
          if (substr($elementName, 0, 8) === 'onbehalf' && strpos($elementName, '[') && strpos($elementName, ']')) {
            $idOfelement = substr(substr($elementName, (strpos($elementName, '[') + 1)), 0, -1);
          }
          $customUrls[$idOfelement] = CRM_Utils_System::url('civicrm/ajax/contactref',
            $urlParams,
            FALSE, NULL, FALSE
          );

        }
        else {
          // FIXME: This won't work with customFieldOptions hook
          $attributes += array(
            'entity' => 'OptionValue',
            'placeholder' => $placeholder,
            'multiple' => $search,
            'api' => array(
              'params' => array('option_group_id' => $field->option_group_id, 'is_active' => 1),
            ),
          );
          $element = $qf->addEntityRef($elementName, $label, $attributes, $useRequired && !$search);
        }

        $qf->assign('customUrls', $customUrls);
        break;
    }

    switch ($field->data_type) {
      case 'Int':
        // integers will have numeric rule applied to them.
        if ($field->is_search_range && $search) {
          $qf->addRule($elementName . '_from', ts('%1 From must be an integer (whole number).', array(1 => $label)), 'integer');
          $qf->addRule($elementName . '_to', ts('%1 To must be an integer (whole number).', array(1 => $label)), 'integer');
        }
        elseif ($widget == 'Text') {
          $qf->addRule($elementName, ts('%1 must be an integer (whole number).', array(1 => $label)), 'integer');
        }
        break;

      case 'Float':
        if ($field->is_search_range && $search) {
          $qf->addRule($elementName . '_from', ts('%1 From must be a number (with or without decimal point).', array(1 => $label)), 'numeric');
          $qf->addRule($elementName . '_to', ts('%1 To must be a number (with or without decimal point).', array(1 => $label)), 'numeric');
        }
        elseif ($widget == 'Text') {
          $qf->addRule($elementName, ts('%1 must be a number (with or without decimal point).', array(1 => $label)), 'numeric');
        }
        break;

      case 'Money':
        if ($field->is_search_range && $search) {
          $qf->addRule($elementName . '_from', ts('%1 From must in proper money format. (decimal point/comma/space is allowed).', array(1 => $label)), 'money');
          $qf->addRule($elementName . '_to', ts('%1 To must in proper money format. (decimal point/comma/space is allowed).', array(1 => $label)), 'money');
        }
        elseif ($widget == 'Text') {
          $qf->addRule($elementName, ts('%1 must be in proper money format. (decimal point/comma/space is allowed).', array(1 => $label)), 'money');
        }
        break;

      case 'Link':
        $element->setAttribute('class', "url");
        $qf->addRule($elementName, ts('Enter a valid web address beginning with \'http://\' or \'https://\'.'), 'wikiURL');
        break;
    }

    return $element;
  }

  public function buildQuickForm() {
    CRM_Utils_System::setTitle(ts('Autism Ontario - Flag Raising'));
    $groupID = civicrm_api3('CustomGroup', 'getValue', ['name' => 'flag_raising', 'return' => 'id']);
    $groupTree = CRM_Core_BAO_CustomGroup::getTree('Event', NULL, $groupID, 0, NULL);
    $groupTree = CRM_Core_BAO_CustomGroup::formatGroupTree($groupTree, 1, $this);
    //CRM_Core_Error::debug('a', CRM_Event_PseudoConstant::eventType());

    $fields = [
      'local_chapter' => [
        'title' => ts('Closest local chapter of Austism Ontario?'),
        'type' => 'select',
        'required' => TRUE,
      ],
      'event_title' => [
        'title' => ts('Event Title'),
        'type' => 'text',
        'required' => TRUE,
      ],
      'location' => [
        'title' => ts('Name of location of flag raising?'),
        'type' => 'text',
        'required' => TRUE,
      ],
      'street_address' => [
        'title' => ts('Street Address'),
        'type' => 'text',
        'required' => TRUE,
      ],
      'city' => [
        'title' => ts('City'),
        'type' => 'text',
        'required' => TRUE,
      ],
      'postal_code' => [
        'title' => ts('Postal Code'),
        'type' => 'text',
        'required' => TRUE,
      ],
      'ceremony_date' => [
        'title' => ts('What date and time of the ceremony'),
        'type' => 'date',
        'required' => TRUE,
      ],
      'attending' => [
        'title' => ts('Autism Ontario - Who is attending?'),
        'type' => 'Text',
        'required' => TRUE,
      ],
      'open_to_public' => [
        'title' => ts('Is this ceremony open to public?'),
        'post_help' => ts('i.e. do you want Autism Ontario to list your ceremony and help spread the word?'),
        'type' => 'YesNo',
        'required' => TRUE,
      ],
    ];
    foreach ($fields as $name => $field) {
      $title = $field['title'];
      if ($field['type'] == 'select') {
         $this->add('select', $name, $title, CRM_Core_OptionGroup::values('chapter_20180619153429'), $field['required']);
      }
      if ($field['type'] == 'text') {
        $this->add('text', $name, $title, NULL, $field['required']);
      }
      if ($field['type'] == 'YesNo') {
        $this->addYesNo($name, $title, FALSE, $field['required']);
      }
      if ($field['type'] == 'date') {
        $params = array(
          'date' => 'dd/mm/yy',
          'time' => 12,
        );
        $this->add('datepicker', $name, $title, '', $field['required'], $params);
      }
    }

    self::customFieldBuildQuickForm($this, $groupTree);

    $this->addButtons(array(
      array(
        'type' => 'submit',
        'name' => E::ts('Submit'),
        'isDefault' => TRUE,
      ),
    ));
    $this->_elementNames = $this->getRenderableElementNames();
    // export form elements
    $this->assign('elementNames', $this->_elementNames);
    parent::buildQuickForm();
  }

  public function postProcess() {

    $values = $this->exportValues();

    $params = [
      'summary' => "",
      'event_type_id' => key(CRM_Event_PseudoConstant::eventType()),
      'is_public' => $values['open_to_public'],
      'is_online_registration' => '1',
      'start_date' => $values['ceremony_date'],
      'is_monetary' => '0',
      'is_active' => 1,
      'title' => $values['event_title'],
      'created_date' => date('YmdHis'),
    ];
    foreach($this->_elementNames as $elementName) {
      if (strstr($elementName, 'custom_')) {
        $params[$elementName] = $values[$elementName];
      }
    }
    $eventID = civicrm_api3('Event', 'create', $params)['id'];

    $address = [
      1 => [
        'state_province_id' => 1108,
        'country_id' => 939,
        'is_primary' => 1,
        'location_type_id' => 1,
      ],
    ];
    foreach ([
      'street_address',
      'city',
      'postal_code',

    ] as $name) {
      $address[1][$name] = $values[$name];
    }
    $locParams = [
      'entity_table' => 'civicrm_event',
      'entity_id' => $eventID,
      'address' => $address,
    ];
    $params = [
      'id' => $eventID,
      'loc_block_id' => CRM_Core_BAO_Location::create($locParams, TRUE, 'event')['id'],
    ];
    civicrm_api3('Event', 'create', $params);
    //CRM_Core_Error::debug('aaaa', $values);exit;
    parent::postProcess();
  }

  public function getColorOptions() {
    $options = array(
      '' => E::ts('- select -'),
      '#f00' => E::ts('Red'),
      '#0f0' => E::ts('Green'),
      '#00f' => E::ts('Blue'),
      '#f0f' => E::ts('Purple'),
    );
    foreach (array('1', '2', '3', '4', '5', '6', '7', '8', '9', 'a', 'b', 'c', 'd', 'e') as $f) {
      $options["#{$f}{$f}{$f}"] = E::ts('Grey (%1)', array(1 => $f));
    }
    return $options;
  }

  /**
   * Get the fields/elements defined in this form.
   *
   * @return array (string)
   */
  public function getRenderableElementNames() {
    // The _elements list includes some items which should not be
    // auto-rendered in the loop -- such as "qfKey" and "buttons".  These
    // items don't have labels.  We'll identify renderable by filtering on
    // the 'label'.
    $elementNames = array();
    foreach ($this->_elements as $element) {
      /** @var HTML_QuickForm_Element $element */
      $label = $element->getLabel();
      if (!empty($label)) {
        $elementNames[] = $element->getName();
      }
    }
    return $elementNames;
  }

}
