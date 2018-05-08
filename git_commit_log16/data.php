<?php
require_once __DIR__ . '/views/json/vendor/autoload.php';
/**
* 
*/
class Data
{
	public function __construct()
	{
		$this->connexion = false;
		$this->client = null;
		$this->email = null;
		$this->commits = null;
	}

	public function connexion($userOrToken, $passwd)
	{	
		$this->client = new GitHubClient();
		$this->client->setCredentials($userOrToken, $passwd);
		// $this->client->setOauthKey($userOrToken);  // ce renseigner
	}

	protected function recupCommits($owner, $repo)
	{
		$this->client->setPage();
		$this->client->setPageSize(100);
		$commits = $this->client->repos->commits->listCommitsOnRepository($owner, $repo);
		$tabCommits = array();
		
		//affichage + pagination a modifié 
		while(count($commits))
		{
			$page = $this->client->getPage();
			foreach($commits as $commit)
			{
				$sha = $commit->getSha();

				$tabCommits[$sha]['SHA'] = $sha;
				$tabCommits[$sha]['PAGE'] = $page;
				$tabCommits[$sha]['NAME'] = $commit->getCommit()->getAuthor()->getName();
				$tabCommits[$sha]['DATE'] = $commit->getCommit()->getAuthor()->getDate();
				$tabCommits[$sha]['MESSAGE'] = $commit->getCommit()->getMessage();
				$tabCommits[$sha]['URL'] = $commit->getHtmlUrl();
			}
			
			if(!$this->client->hasNextPage())
				break;
				
			$commits = $this->client->getNextPage();
			if($this->client->getPage() == $page)
				break;
		}

		return $tabCommits;
	}

	protected function recupRepoBDD() 
	{
		$req = Db::getInstance()->Executes("SELECT * FROM ps_git_commit_log");
		return $req[0]['depot'];
	}

	protected function ajoutRepoBDD($depo) 
	{
		$req = Db::getInstance()->Executes("SELECT * FROM ps_git_commit_log");
  		if(count($req) != 0) {
			Db::getInstance()->Executes("UPDATE ps_git_commit_log SET depot = '$depo'");
  		} else {
  			Db::getInstance()->Executes("INSERT INTO ps_git_commit_log (depot) VALUES ('$depo')");
  		}
	}

	public function recupRepoCommits($repoHTTP)
	{
		$commits = null;
		// Si ce format : "User/Repo" -> en enléve juste le slash
		// Si ce format : "https://github.com/User/Repo.git" -> on enléve les 19 premiers et les 4 derniers caract 
		if(substr($repoHTTP, 0, 8) == "https://" && substr($repoHTTP, -4) == ".git")
		{
			$repo = substr($repoHTTP, 19, -4);
			$this->ajoutRepoBDD($repo);
			$tabRepo = explode("/", $repo);
			$commits = $this->recupCommits($tabRepo[0], $tabRepo[1]);
		}
		else
		{
			if(strrpos($repoHTTP, "/") != false)
			{
				$this->ajoutRepoBDD($repoHTTP);
				$tabRepo = explode("/", $repoHTTP);
				$commits = $this->recupCommits($tabRepo[0], $tabRepo[1]);
			}
		}

		return $commits;
	}

	public function recupClient() 
	{
		return $this->client->user;
	}

	public function recupRepo()
	{
		$repoBdd = $this->recupRepoBDD();
		if(substr($repoBdd, 0, 8) == "https://" && substr($repoBdd, -4) == ".git")
		{
			$Repo = substr($repoBdd, 19, -4);
		} 
		else
		{
			$Repo = $repoBdd;
		}
		return $Repo;
	}
}
?>