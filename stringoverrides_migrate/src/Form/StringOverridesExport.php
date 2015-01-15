<?php

/**
 * @file
 * Contains \Drupal\stringoverrides_migrate\Form\StringOverridesExport.
 */

namespace Drupal\stringoverrides_migrate\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpKernel\Exception\ServiceUnavailableHttpException;

class StringOverridesExport extends ConfigFormBase {

  public function getFormId() {
    return 'stringoverrides_migrate_admin_export';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {

    $languages = \Drupal::moduleHandler()->moduleExists('locale') ? locale_language_list() : array('en' => t('English'));
    $form['lang'] = array(
      '#type' => 'select',
      '#title' => t('Language'),
      '#description' => t('The language you would like to export.'),
      '#options' => $languages,
      '#required' => TRUE,
    );
    return parent::buildForm($form, $form_state);
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    
    $userInputValues = $form_state->getUserInput();
    $lang = $userInputValues['lang'];
    $export = stringoverrides_migrate_admin_export_text($lang);
    $filename = "my-string-overrides.$lang.po";
    $headers = array(
      'Content-Type' => 'text/plain; charset=UTF-8',
      'Content-Length' => strlen($export),
      'Content-Disposition' => 'attachment; filename="' . $filename . '"',
      'Cache-Control' => 'private',
    );
    foreach ($headers as $name => $value) {
      throw new ServiceUnavailableHttpException($value, $name);
    }
    echo $export;
  }
}

/**
 * Returns the exported *.po text from the given language.
 */
function stringoverrides_migrate_admin_export_text($lang = 'en') {
  $languages = language_list();
  $config = \Drupal::config('stringoverrides.settings');

  $custom_strings = $config->get("locale_custom_strings_$lang") ?: array();
  foreach ($custom_strings as $context => $translations) {
    foreach ($translations as $source => $translation) {
      $strings[] = array(
        'context' => $context,
        'source' => $source,
        'translation' => $translation,
        'comment' => '',
      );
    }
  }
  return _locale_export_po_generate($languages[$lang], $strings);
}