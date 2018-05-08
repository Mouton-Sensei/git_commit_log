
<!-- Block mymodule -->
<div id="mymodule_block_home" class="block">
    <p class="title_block">
        {$my_module_name} 
    </p>
    <div class="block_content list-block">
        <ul>
            <li>Auteur: {$my_module_owner}</li>
            <li>Dépot: {$my_module_repo}</li>
            <li>Nombre de commits: {$my_module_nbr_commits}</li>
        </ul>
        <!-- {assign var=commits value=['user' => $listCommits.username, 'passwd' => $listCommits.password] scope="global"}  -->
        <!-- Non fonctionnel les tableaux ne s'envoie pas sur setTemplate -> raison ? à retravailler -->

        <a href="{$my_module_link}" class="btn btn-default button button-small">
        	<span>
        		Liste commits<i class="icon-chevron-right right"></i>
        	</span>
        </a>
    </div>
</div>
<!-- /Block mymodule -->