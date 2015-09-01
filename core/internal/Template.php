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
 * For more information : contact@centreon.com
 * 
 */

namespace Centreon\Internal;

use Centreon\Internal\Di;
use Centreon\Internal\Exception;

/**
 * @author Lionel Assepo <lassepo@centreon.com>
 * @package Centreon
 * @subpackage Core
 */
class Template extends \Smarty
{
    /**
     *
     * @var string 
     */
    private $templateFile;
    
    /**
     *
     * @var array 
     */
    private $cssResources;
    
    /**
     *
     * @var array 
     */
    private $jsTopResources;
    
    /**
     *
     * @var array 
     */
    private $jsBottomResources;
    
    /**
     *
     * @var array 
     */
    private $exclusionList;

    /**
     *
     * @var string
     */
    private $customJs;

    /**
     * 
     * @param string $newTemplateFile
     * @param boolean $enableCaching
     */
    public function __construct($newTemplateFile = '', $enableCaching = 0)
    {
        $this->templateFile = $newTemplateFile;
        $this->caching = $enableCaching;
        
        $this->cssResources = array();
        $this->jsTopResources = array();
        $this->jsBottomResources = array();
        $this->buildExclusionList();
        $this->customJs = "";
        parent::__construct();
        $this->initConfig();
        $menu = new Menu();
        $this->assign('appMenu', $menu->getMenu());
    }
    
    /**
     * 
     */
    public function initConfig()
    {
        $di = Di::getDefault();
        $config = $di->get('config');
        
        $this->setTemplateDir($this->buildTemplateDirList());
        $this->addPluginsDir(realpath(__DIR__ . '/../custom/Smarty/'));
        
        // Custom configuration
        $this->setCompileDir($config->get('template', 'compile_dir'));
        $this->setCacheDir($config->get('template', 'cache_dir'));
        
        if ($config->get('template', 'debug')) {
            $this->compile_check = true;
            $this->force_compile = true;
        }
        /* Set the current route */
        $this->assign('currentRoute', $di->get('router')->request()->pathname());
    }
    
    private function buildTemplateDirList()
    {
        $config = Di::getDefault()->get('config');
        $path = rtrim($config->get('global', 'centreon_path'), '/');

        $templateDirList = array();
        $templateDirList['Core'] = realpath($path . '/core/views/');

        // Add standalone widget dir
        foreach (glob($path . "/widgets/*Widget/views") as $widgetTemplateDir) {
            if (preg_match('/\/([a-zA-Z0-9]+Widget)\//', $widgetTemplateDir, $matches)) {
                $widgetTemplateDir = realpath($widgetTemplateDir);
                $templateDirList[$matches[1]] = $widgetTemplateDir;
            }
        }
        // Add Module Template Dir
        foreach (glob($path . "/modules/*Module/views") as $moduleTemplateDir) {
            if (preg_match('/\/([a-zA-Z0-9]+Module)\//', $moduleTemplateDir, $matches)) {
                $moduleTemplateDir = realpath($moduleTemplateDir);
                $templateDirList[$matches[1]] = $moduleTemplateDir;
            }
        }
        // Add Widget Template Dir
        foreach (glob($path . "/modules/*Module/widgets/*Widget/views") as $widgetTemplateDir) {
            if (preg_match('/\/([a-zA-Z0-9]+Widget)\//', $widgetTemplateDir, $matches)) {
                $widgetTemplateDir = realpath($widgetTemplateDir);
                $templateDirList[$matches[1]] = $widgetTemplateDir;
            }
        }
        return $templateDirList;
    }

    /**
     * Load statics file (css/js)
     *
     * jQuery, bootstrap, font-awesome and centreon
     */
    public function initStaticFiles()
    {
        /* Load css */
        $this->addCss('bootstrap.min.css');
        $this->addCss('bootstrap-toggle.min.css');
        $this->addCss('bootstrap2-toggle.min.css');
        $this->addCss('dataTables.bootstrap.css');
        $this->addCss('font-awesome.min.css');
        $this->addCss('jquery-ui.min.css');
        $this->addCss('centreon.qtip.css');
        $this->addCss('jquery.sidr.light.css');
        $this->addCss('centreon.css');
        $this->addCss('centreon.tag.css', 'centreon-administration');

        /* Load javascript */
        $this->addJs('jquery.min.js');
        $this->addJs('jquery-ui.min.js');

        $this->addJs('sideSlide.plugin.js');

        $this->addJs('jquery.qtip.min.js');
        $this->addJs('centreon.help.tooltip.js');
        $this->addJs('bootstrap.min.js');
        $this->addJs('bootstrap-toggle.min.js');
        $this->addJs('bootstrap2-toggle.min.js');
        $this->addJs('jquery.ba-resize.js');
        $this->addJs('moment-with-langs.min.js');
        $this->addJs('centreon.functions.js');
        $this->addJs('centreon-timezone.js');
        $this->addJs('centreon-wizard.js');
        $this->addJs('centreon.csrf.js');
        $this->addJs('jquery.metisMenu.js');
        $this->addJs('centreon.custom.js');
        $this->addJs('jquery.sidr.min.js');
        $this->addJs('centreon.custom.js');
        $this->addJs('jquery.slimscroll.js');
        $this->addJs('moment-with-locales.js');
        $this->addJs('moment-timezone-with-data.min.js');
        $this->addJs('centreon.parentfield.js');
        $this->addJs('centreon.form.js');
        $this->addJs('centreon.validate.js');
        $this->addJs('centreon.utils.js');
        $this->addJs('jquery.validation/jquery.validate.min.js');
    }
    
    /**
     * @todo Maybe load this list from a config file
     */
    private function buildExclusionList()
    {
        $this->exclusionList = array(
            'cssFileList',
            'jsTopFileList',
            'jsBottomFileList'
        );
    }
    
    /**
     * 
     * {@inheritdoc}
     * @throws \Centreon\Exception If the template file is not defined
     */
    public function display($template = null, $cache_id = null, $compile_id = null, $parent = null)
    {
        $response = Di::getDefault()->get('router')->response();
        $response->body($this->fetch($template, $cache_id, $compile_id, $parent));
    }
    
    /**
     * 
     * {@inheritdoc}
     * @throws \Centreon\Exception If the template file is not defined
     * @return type
     */
    public function fetch(
        $template = null,
        $cache_id = null,
        $compile_id = null,
        $parent = null,
        $display = false,
        $merge_tpl_vars = true,
        $no_output_filter = false
    ) {
        if ($this->templateFile === "" && is_null($template)) {
            throw \Exception("Template is not defined.");
        } else if ($this->templateFile !== "" && is_null($template)) {
            $template = $this->templateFile;
        }
        $this->loadResources();
        $this->assign('customJs', $this->customJs);
        return parent::fetch(
            $template,
            $cache_id,
            $compile_id,
            $parent,
            $display,
            $merge_tpl_vars,
            $no_output_filter
        );
    }
    
    /**
     * 
     */
    private function loadResources()
    {
        parent::assign('cssFileList', $this->cssResources);
        parent::assign('jsTopFileList', $this->jsTopResources);
        parent::assign('jsBottomFileList', $this->jsBottomResources);
    }
    
    /**
     * 
     * @param string $fileName $fileName CSS file to add
     * @param string $module
     * @return \Centreon\Template
     * @throws Exception
     */
    public function addCss($fileName, $module = 'centreon')
    {
        if ($this->isStaticFileExist('css', $fileName, $module) === false) {
            throw new Exception('The given file does not exist');
        }

        $config = Di::getDefault()->get('config');
        $baseUrl = rtrim($config->get('global', 'base_url'), '/');
        $fileName = $baseUrl . '/static/'  . $module . '/css/' . $fileName;

        if (!in_array($fileName, $this->cssResources)) {
            $this->cssResources[] = $fileName;
        }
        
        return $this;
    }
    
    /**
     * 
     * @param string $fileName Javascript file to add
     * @param string $loadingLocation
     * @param string $module
     * @return \Centreon\Template
     * @throws Exception
     */
    public function addJs($fileName, $loadingLocation = 'bottom', $module = 'centreon')
    {
        if ($this->isStaticFileExist('js', $fileName, $module) === false) {
            throw new Exception('The given file does not exist');
        }
        
        switch(strtolower($loadingLocation)) {
            case 'bottom':
            default:
                $jsArray = 'jsBottomResources';
                break;
            case 'top':
                $jsArray = 'jsTopResources';
                break;
        }

        $config = Di::getDefault()->get('config');
        $baseUrl = rtrim($config->get('global', 'base_url'), '/');
        $fileName = $baseUrl . '/static/' . $module . '/js/' . $fileName;

        if (!in_array($fileName, $this->$jsArray)) {
            $this->{$jsArray}[] = $fileName;
        }
        
        return $this;
    }

    /**
     * 
     * @param string $varName
     * @param mixed $varValue
     * @param boolean $nocache
     * @return \Centreon\Template
     * @throws \Centreon\Internal\Exception
     */
    public function assign($varName, $varValue = null, $nocache = false)
    {
        if (in_array($varName, $this->exclusionList)) {
            throw new Exception(_('This variable name is reserved'));
        }
        parent::assign($varName, $varValue, $nocache);
        return $this;
    }
    
    /**
     * 
     * @param string $type
     * @param string $filename
     * @param string $module
     * @return boolean
     * @throws \Centreon\Internal\Exception
     */
    private function isStaticFileExist($type, $filename, $module)
    {
        $di = Di::getDefault();
        $config = $di->get('config');
        $centreonPath = rtrim($config->get('global', 'centreon_path'), '/');
        $basePath = $centreonPath . '/www/static/' . $module . '/' . strtolower($type) . '/';
        if (!file_exists($basePath . $filename)) {
            if (strtolower($type) == 'css') {
                $filename = $centreonPath . '/www/static/' . $module . '/less/' .
                    str_replace('.css', '.less', $filename);
                if (file_exists($filename)) {
                    return true;
                }
            }
            return false;
        }
        return true;
    }

    /**
     * Add custom js code
     *
     * @param string $jsStr
     */
    public function addCustomJs($jsStr)
    {
        $this->customJs .= $jsStr . "\n";
    }

    /**
     * Getter
     *
     * @return string
     */
    public function getCustomJs()
    {
        return $this->customJs;
    }
}