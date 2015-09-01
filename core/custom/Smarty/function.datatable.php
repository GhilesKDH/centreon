<?php
/*
 * Copyright 2015 Centreon (http://www.centreon.com/)
 * 
 * Centreon is a full-fledged industry-strength solution that meets 
 * the needs in IT infrastructure and application monitoring for 
 * service performance.
 * 
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 * 
 *    http://www.apache.org/licenses/LICENSE-2.0  
 * 
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * 
 */

/*
 * Smarty plugin
 * -------------------------------------------------------------
 * File:     function.datatable.php
 * Type:     function
 * Name:     datatable
 * Purpose:  returns a datatable
 * -------------------------------------------------------------
 */
function smarty_function_datatable($params, $smarty)
{
    $smarty->assign('object', $params['object']);
    
    if (isset($params['objectAddUrl'])) {
        $smarty->assign('objectAddUrl', $params['objectAddUrl']);
    }
    
    $datatableParameters = array();
    $datatableParameters['header'] = $params['datatableObject']::getHeader();
    $datatableParameters['configuration'] = $params['datatableObject']::getConfiguration();

    $datatableParameters = array_merge($datatableParameters, $params['datatableObject']::getExtraParams());
    
    $smarty->assign('datatableParameters', $datatableParameters);
    
    return $smarty->fetch('tools/datatable.tpl');
}