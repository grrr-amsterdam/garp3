<?php

namespace Garp;

use Model_Snippet;
use Garp_I18n_ModelFactory;
use Zend_Controller_Action_HelperBroker;
use Zend_Registry;

/**
 * Grab the rendered output of a snippet.
 *
 * @param  string $identifier
 * @return string
 */
function snippet(string $identifier): string {
    $model = new Model_Snippet();
    if ($model->isMultilingual()) {
        $model = (new Garp_I18n_ModelFactory())->getModel('Snippet');
    }
    $snippet = $model->fetchByIdentifier($identifier);
    if (!$snippet) {
        return $identifier;
    }
    return strval(
        $snippet->has_html
        ? $snippet->html
        : $snippet->text
    );
}

/**
 * Render a partial.
 *
 * @param  string $filename
 * @param  array  $params
 * @param  string $module
 * @return string
 */
function partial(string $filename, array $params = [], string $module = 'default'): string {
    return Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer')
        ->view
        ->partial($filename, $module, $params);
}

/**
 * Translate text
 *
 * @param String $str
 * @return String
 */
function __($str) {
    if (Zend_Registry::isRegistered('Zend_Translate')) {
        $translate = Zend_Registry::get('Zend_Translate');
        return call_user_func_array(array($translate, '_'), func_get_args());
    }
    return $str;
}
