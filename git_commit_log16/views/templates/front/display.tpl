<h3 class="titreCommits"> {l s='Commits du dépot : ' mod='git_commit_log'} {$depo} </h3>
	
	{foreach from=$commits item="v"} 
		<div class="commitsLine">
			<div class="firstPartCommits">
				<h5>{$v.MESSAGE|substr:0:70}...</h5>	   
	    		{$v.NAME} - {$v.DATE}
	    		<div>sha : {$v.SHA}</div>
			</div>
			<div class="secondPartCommits">
				<div><a class="buttonCommits btn btn-primary float-sm-right hidden-sm-down" href="{$v.URL}">plus d'info</a></div>
			</div>
		</div>
	{/foreach}


<!-- Pagination a faire -->

