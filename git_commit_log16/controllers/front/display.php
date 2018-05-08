<?php
// 

class git_commit_logdisplayModuleFrontController extends ModuleFrontController
{  
   
  	public function initContent()
  	{
        include(dirname(__FILE__).'../../data.php'); 
        
      	parent::initContent();

      	$client = new Data();
        $client->connexion(Configuration::get('GIT_COMMIT_LOG_ACCOUNT_GIT'), Configuration::get('GIT_COMMIT_LOG_ACCOUNT_PASSWORD'));
        $listCommits = $client->recupRepoCommits($client->recupRepo());

      	$this->context->smarty->assign(
        		array(
      	    		'depo' => $client->recupRepo(),
      	    		'commits' => $listCommits
    	    	)
      	);

      	// $this->setTemplate(__FILE__, '../../views/templates/front/display.tpl');
        $this->setTemplate('display.tpl');
  	}
}
?>