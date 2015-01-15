<?php

/**
 * @file
 * Contains \Drupal\stringoverrides_migrate\Form\StringOverridesConfiguration.
 */

namespace Drupal\stringoverrides_migrate\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\FileUsage\FileUsageBase;

class StringOverridesImport extends ConfigFormBase {

  public function getFormId() {
    return 'stringoverrides_migrate_admin_import';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {

    $form = array();
    $form['#attributes'] = array('enctype' => "multipart/form-data");
    $languages = \Drupal::moduleHandler()->moduleExists('locale') ? locale_language_list() : array('en' => t('English'));
    $form['file'] = array(
      '#type' => 'file',
      '#title' => t('File'),
      '#description' => t('Attach your *.po file here to import the string overrides.'),
    );
    $form['lang'] = array(
      '#type' => 'select',
      '#title' => t('Language'),
      '#description' => t('Which language to import the overrides to.'),
      '#options' => $languages,
    );
    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {

    $userInputValues = $form_state->getUserInput();
    $config = $this->config('stringoverrides.settings');

    // Check if the file uploaded correctly
    $file = file_save_upload('file');
    if (!$file) {
      $form_state->setErrorByName('file', t('A file to import is required.'));
      return;
    }

    // Try to allocate enough time to parse and import the data.
    drupal_set_time_limit(240);
    $lang = $userInputValues['lang'];

    // Get strings from file (returns on failure after a partial import, or on success)
    $status = _locale_import_read_po('mem-store', $file, LOCALE_IMPORT_OVERWRITE, $lang, 'stringoverrides');
    if ($status === FALSE) {
      // Error messages are set in _locale_import_read_po().
      return FALSE;
    }

    // Get the import result.
    $strings = _locale_import_one_string('mem-report');

    FileUsageBase::delete($file);
    $config->set('locale_custom_strings_$lang',$strings);
    drupal_set_message(t('The overrides have been imported.'));
  }
}