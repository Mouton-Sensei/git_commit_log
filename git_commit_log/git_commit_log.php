<?php
/**
* 2007-2018 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2018 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/

if (!defined('_PS_VERSION_')) {
    exit;
}

global $smarty;

class Git_commit_log extends Module
{

    public function __construct()
    {
        $this->name = 'git_commit_log';
        $this->tab = 'front_office_features';
        $this->version = '0.0.1';
        $this->author = 'Thibault Fayolle';
        $this->need_instance = 0;
        $this->connexion = false;
        $this->bootstrap = true;
        $this->link = null;
        $this->depo = 'Aucun dépot !';

        parent::__construct();

        $this->displayName = $this->l('Git Commit Log');
        $this->description = $this->l('Un module qui permet de visualiser les commits d\'un dépot GitHub !');
        $this->confirmUninstall = $this->l('Etes vous sure vouloir supprimer le module ?');
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
    }

    public function install()
    {
        if (Shop::isFeatureActive())
            Shop::setContext(Shop::CONTEXT_ALL);

        include(dirname(__FILE__).'/sql/install.php');

        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('backOfficeHeader') &&
            $this->registerHook('leftColumn');
            
        Configuration::updateValue('GIT_COMMIT_LOG', 'my friend');
    }

    public function uninstall()
    {
        Configuration::deleteByName('GIT_COMMIT_LOG');

        include(dirname(__FILE__).'/sql/uninstall.php');

        return parent::uninstall();
    }

    public function getContent()
    {
        include(dirname(__FILE__).'/data.php');

        if(!empty(Tools::getValue('GIT_COMMIT_LOG_ACCOUNT_GIT')) || !empty(Tools::getValue('GIT_COMIT_LOG_DEPOT')))
        {
            $output = $this->context->smarty->fetch($this->local_path.'views/templates/admin/configure.tpl');
            $this->context->smarty->assign('module_dir', $this->_path);
        }

        //on vérifie su des valeur on était envoyé via les form généré
        if (((bool)Tools::isSubmit('submitGit_commit_logModule')) == true) 
        {
            if(!empty(Tools::getValue('GIT_COMIT_LOG_DEPOT'))) {$this->postProcess('not_null');} else {$this->postProcess('other');}
            if(!empty(Tools::getValue('GIT_COMMIT_LOG_ACCOUNT_GIT')) || !empty(Configuration::get('GIT_COMMIT_LOG_ACCOUNT_GIT')))
            {
                $client = new Data();
                $client->connexion(Tools::getValue('GIT_COMMIT_LOG_ACCOUNT_GIT'), Tools::getValue('GIT_COMMIT_LOG_ACCOUNT_PASSWORD'));
                $listCommits = $client->recupRepoCommits(Configuration::get('GIT_COMIT_LOG_DEPOT'));
                $link = $this->context->link->getModuleLink($this->name, 'display');
                $this->html1 = '<div class="panel">
                        <h3><i class="icon icon-tags"></i>Test</h3>
                        <p>Vous êtes actuellment connecté avec le compte : '.Configuration::get('GIT_COMMIT_LOG_ACCOUNT_GIT').' !</p>
                    </div>';
                $this->html2 = '<div class="panel" style="display: none;" id="infoDepo">
                        <h3><i class="icon icon-tags"></i>Info Commits</h3>
                        <ul>
                            <li>Information provenant du depo '.Configuration::get('GIT_COMIT_LOG_DEPOT').'</li>
                            <li>Il y a acutellement '.count($listCommits).' commits sur ce depo.</li>
                            <li>Vous pouvez accéder à la liste des commits <a target="_blank" href="'.$link.'">ici</a></li>
                            <li>Un accès aux commits est aussi disponible sur la gauche de certaine page</li>
                        </ul>
                    </div>';
                $this->html3 = '<div class="panel" style="display: none;" id="infoDepo">
                        <h3><i class="icon icon-tags"></i>Info Commits</h3>
                        <p>Aucun dépot a été enregistré</p>
                    </div>';

                $output = $output.$this->html1;
                $output = !empty(Configuration::get('GIT_COMIT_LOG_DEPOT')) ? $output.$this->html2 : $output.$this->html3;
                $output = $output.$this->renderFormConnexion('getConfigDepot');
            } 
            else 
            {
                $output = $output.$this->renderFormConnexion('getConnForm');
            }      
        } else {
            $output = $output.$this->renderFormConnexion('getConnForm');
        }
        
        return $output;
    }

    // Permet la création des forms
    protected function renderFormConnexion($formulaire)
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitGit_commit_logModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            .'&configure='.$this->name.'&tab_module='.$this->tab.'&module_name='.$this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), //Ajout les valeurs des input
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->{$formulaire}()));
    }

    //form connexion
    protected function getConnForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                    'name' => 'FormCo',
                    'title' => $this->l('Token GitHub'),
                    'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-user"></i>',
                        'name' => 'GIT_COMMIT_LOG_ACCOUNT_GIT',
                        'label' => $this->l('Token'),
                    ),
                    array(
                        'type' => 'password',
                        'name' => 'GIT_COMMIT_LOG_ACCOUNT_PASSWORD',
                        'label' => $this->l('Password'),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Connexion'),
                    'name' => 'GIT_COMMIT_LOG_ACCOUNT_SUBMIT',
                ),
            ),
        );
    }

    //Form pour les Dépot
    protected function getConfigDepot()
    {
        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Dépot GitHub'),
                    'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'col' => 5,
                        'type' => 'text',
                        'label' => $this->l('Dépot Git'),
                        'name' => 'GIT_COMIT_LOG_DEPOT',
                        'value' => Configuration::get('GIT_COMIT_LOG_DEPOT'),
                        'desc' => $this->l('Les dépots ce doivent de respecter certain format : - utilisateur/repositories - https://github.com/utilisateur/repositories.git'),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Enregistrer'),
                    'name' => 'GIT_COMMIT_LOG_DEPOT_SUBMIT',
                ),
            )
        );
    }

    //permet de récuérer les nouvelles val des inputs
    protected function getConfigFormValues()
    {
        return array(
            'GIT_COMMIT_LOG_ACCOUNT_GIT' => Configuration::get('GIT_COMMIT_LOG_ACCOUNT_GIT', null),
            'GIT_COMMIT_LOG_ACCOUNT_PASSWORD' => Configuration::get('GIT_COMMIT_LOG_ACCOUNT_PASSWORD', null),
            'GIT_COMMIT_LOG_ACCOUNT_SUBMIT' => Configuration::get('GIT_COMMIT_LOG_ACCOUNT_SUBMIT', null),
            'GIT_COMIT_LOG_DEPOT' => Configuration::get('GIT_COMIT_LOG_DEPOT', null),
        );
    }

    protected function getConfigFormValuesDepot()
    {
        return array('GIT_COMIT_LOG_DEPOT' => Configuration::get('GIT_COMIT_LOG_DEPOT', null),);
    }

    protected function postProcess($option)
    {
        if($option == 'not_null') {
            $form_values = $this->getConfigFormValuesDepot();
        } else {
            $form_values = $this->getConfigFormValues();   
        }
       
        foreach (array_keys($form_values) as $key) {
            Configuration::updateValue($key, Tools::getValue($key));
        }
    }

    // JS et CSS BO/ FRONT
    public function hookBackOfficeHeader()
    {
        $this->context->controller->addJS($this->_path.'$this->_path.views/js/jquery-3.3.1.min.js');
        $this->context->controller->addJS($this->_path.'views/js/back.js');
        $this->context->controller->addCSS($this->_path.'views/css/back.css'); 
    }
    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path.'/views/js/front.js');
        $this->context->controller->addCSS($this->_path.'/views/css/front.css');
    }

    public function hookDisplayLeftColumn($params)
    {
        include(dirname(__FILE__).'/data.php');
        $client = new Data();
        $client->connexion(Configuration::get('GIT_COMMIT_LOG_ACCOUNT_GIT'), Configuration::get('GIT_COMMIT_LOG_ACCOUNT_PASSWORD'));
        $listCommits = $client->recupRepoCommits($client->recupRepo());
        $tabRepoOwner = explode("/", $client->recupRepo());
        $this->context->smarty->assign(
            array(
                'my_module_name' => $this->displayName,
                'my_module_link' => $this->context->link->getModuleLink($this->name, 'display'),
                'my_module_nbr_commits' => count($listCommits),
                'my_module_owner' => $tabRepoOwner[0],
                'my_module_repo' => $tabRepoOwner[1]
            )
        );
        return $this->display(__FILE__, 'git_commit_log.tpl');
    }

    public function hookDisplayRightColumn($params)
    {
        return $this->hookDisplayLeftColumn($params);
    }
}
