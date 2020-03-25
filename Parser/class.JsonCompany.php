<?php

class JsonCompany
{
	protected $company;
	protected $companyNames;
	protected $companyAddresses;
	protected $companyLeaders;
	protected $companyOwners;

	public function __construct()
	{
		$this->company = null;
		$this->companyNames = array();
		$this->companyAddresses = array();
		$this->companyLeaders = array();
		$this->companyOwners = array();
	}

	public function setCompany($fbnr, $stammeinlage, $scheinunternehmenDate, $companyEndDate)
	{
		$this->company = (object) array('fbnr' => $fbnr, 'stammeinlage' => $stammeinlage, 'scheinunternehmenDate' => $scheinunternehmenDate, 'endDate' => $companyEndDate);
	}

	public function addCompanyName($text, $dateFrom, $dateTo)
	{
		array_push($this->companyNames, (object) array('text' => $text, 'dateFrom' => $dateFrom, 'dateTo' => $dateTo)); 
	}

	public function addCompanyAddress($text, $dateFrom, $dateTo)
	{
		array_push($this->companyAddresses, (object) array('text' => $text, 'dateFrom' => $dateFrom, 'dateTo' => $dateTo)); 
	}

	public function addCompanyLeader($text, $dateFrom, $dateTo)
	{
		array_push($this->companyLeaders, (object) array('text' => $text, 'dateFrom' => $dateFrom, 'dateTo' => $dateTo)); 
	}

	public function addCompanyOwner($text, $dateFrom, $dateTo, $capital)
	{
		array_push($this->companyOwners, (object) array('text' => $text, 'dateFrom' => $dateFrom, 'dateTo' => $dateTo, 'capital' => $capital)); 
	}

	protected function createJson()
	{
		$arr = array('company' => $this->company, 'companyNames' => $this->companyNames, 'companyAddresses' => $this->companyAddresses, 'companyLeaders' => $this->companyLeaders, 'companyOwners' => $this->companyOwners);
		$json = json_encode($arr);

		return $json;
	}

	protected function addDateToForAll()
	{
		for($i = 0; $i < count($this->companyNames); $i++) 
		{
			if($this->companyNames[$i]->dateTo != "today")
				$this->companyNames[$i]->dateTo = $this->companyNames[$i+1]->dateFrom;
		}

		for($i = 0; $i < count($this->companyAddresses); $i++) 
		{
			if($this->companyAddresses[$i]->dateTo != "today")
				$this->companyAddresses[$i]->dateTo = $this->companyAddresses[$i+1]->dateFrom;
		}

		for($i = 0; $i < count($this->companyOwners); $i++)
		{
			if($this->companyOwners[$i]->dateTo == null)
				$this->companyOwners[$i]->dateTo = $this->companyOwners[$i+1]->dateFrom;
		}
	}

	public function writeJsonToFile()
	{
		$file = preg_replace('/\s+/', '', strval(end($this->companyNames)->text).'.json');
		$content = self::createJson();
		file_put_contents('jsonFiles/'.$file, $content);
		return $file;
	}

	public function replaceToday($newDateTo)
	{
		self::addDateToForAll();

		if($newDateTo != null) 
		{
			for($i = 0; $i < count($this->companyNames); $i++){
				if($this->companyNames[$i]->dateTo == 'today')
					$this->companyNames[$i]->dateTo = $newDateTo;
			}
			for($i = 0; $i < count($this->companyAddresses); $i++){
				if($this->companyAddresses[$i]->dateTo == 'today')
					$this->companyAddresses[$i]->dateTo = $newDateTo;
			}
			for($i = 0; $i < count($this->companyLeaders); $i++){
				if($this->companyLeaders[$i]->dateTo == 'today')
					$this->companyLeaders[$i]->dateTo = $newDateTo;
			}
			for($i = 0; $i < count($this->companyOwners); $i++){
				if($this->companyOwners[$i]->dateTo == 'today')
					$this->companyOwners[$i]->dateTo = $newDateTo;
			}
		}
	}
}
?>