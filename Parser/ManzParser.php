<?php

include 'class.JsonCompany.php';

class Parser
{
	protected $xmlFile;
	protected $xml;
	protected $company;
	protected $companyVollzuege;
	protected $companyPersons;
	protected $companyRechtstatsachen;
	protected $scheinunternehmenDate;

	public function __construct($fbnr, $scheinunternehmenDate)
	{
		$url = 'https://testing.ishap.at/manz/manz/?fbnr='.$fbnr.'%20&type=xml';
		$this->xmlFile = file_get_contents($url);
		$this->xml = new SimpleXMLElement(str_replace('ns1:', '', $this->xmlFile));

		htmlspecialchars($this->xml);

		$this->company = new JsonCompany();
		
		$this->companyVollzuege = array();
		$this->companyPersons = array();
		$this->companyRechtstatsachen = array();
		$this->scheinunternehmenDate = $scheinunternehmenDate;

		self::getVollzuege();
		self::getPersons();
		self::getRechtstatsachen();
	}

	public function writeCompanyToFile()
	{
		self::getCompanyNamesAndAddresses();
		self::getCompanyLeadersAndOwners();

		//has to be called last!!!
		self::getCompany();

		$fileNameJson = $this->company->writeJsonToFile();
		$fileName = substr($fileNameJson, 0, strpos($fileNameJson, '.json'));

		file_put_contents('xmlFiles/'.$fileName.'.xml', $this->xmlFile);

		return $fileName;
	}

	protected function getCompany()
	{
		$child = $this->xml->xpath('//AUSZUGRESPONSE');
		$fbnr = strval($child[0]['FNR']);

		$child = $this->xml->xpath('//FI_DKZ20');
		$stammeinlage = strval($child[0]->KAPITAL);

		$scheinunternehmenDate = $this->scheinunternehmenDate;

		$companyEndDate = self::getCompanyEndDate();

		$this->company->setCompany($fbnr, $stammeinlage, $scheinunternehmenDate, $companyEndDate);
	}

	protected function getCompanyNamesAndAddresses()
	{
		foreach ($this->xml->FIRMA->children() as $child) {

			$dateFrom = self::getDateFromVnr(strval($child['VNR']));

			if(strval($child['AUFRECHT']) == "false")
			{
				#to be added in the JsonCompany object
				$dateTo = null;
			} else {
				$dateTo = "today";
			}
			
			if($child->getName() == "FI_DKZ02")
			{
				$text = '';
				foreach ($child->xpath('.//NAME') as $textPiece) 
					$text = $text.' '.$textPiece;
				$this->company->addCompanyName($text, $dateFrom, $dateTo);
			} else if($child->getName() == "FI_DKZ03") {
				$text = strval($child->DKZ03->STELLE).", ".strval($child->DKZ03->PLZ)." ".strval($child->DKZ03->ORT);
				$this->company->addCompanyAddress($text, $dateFrom, $dateTo);
			}
		}
	}

	protected function getCompanyLeadersAndOwners()
	{
		foreach ($this->xml->xpath('//FUN') as $children) 
		{
			if (strval($children['FKENTEXT']) == "GESCHÄFTSFÜHRER/IN (handelsrechtlich)") 
			{
				$text = $this->companyPersons[strval($children['PNR'])];
				$dateFrom = null;
				$dateTo = null;

				foreach ($children as $child)
				{
					if(strval($child->TEXT) == "Funktion gelöscht")
					{
						$dateTo = self::getDateFromVnr(strval($child['VNR']));
					} else {
						$dateFrom = self::getDateFromVnr(strval($child['VNR']));
					}
					
					if(isset($dateTo)) {
						$this->company->addCompanyLeader($text, $dateFrom, $dateTo);
						$dateFrom = null;
						$dateTo = null;
					}
				}

				if(!isset($dateTo) && isset($dateFrom))
					$this->company->addCompanyLeader($text, $dateFrom, 'today');

			} else if(strval($children['FKENTEXT']) == "GESELLSCHAFTER/IN")
			{
				$text = $this->companyPersons[strval($children['PNR'])];
				$dateFrom = null;
				$dateTo = null;
				$capital = null;

				foreach ($children as $child) 
				{
					if($child->getName() == "FU_DKZ11")
						break;

					if ($child->getName() == "FU_DKZ10") 
					{

						if (isset($child->TEXT) && $child->TEXT == "Funktion gelöscht") 
						{
							$dateTo = self::getDateFromVnr(strval($child['VNR']));
						} else {

							if(isset($dateFrom))
							{
								$this->company->addCompanyOwner($text, $dateFrom, $dateTo, $capital);
							}

							$capital = strval($child->KAPITAL);
							$dateFrom = self::getDateFromVnr(strval($child['VNR']));
						}

						if(isset($dateTo)) 
						{
							$this->company->addCompanyOwner($text, $dateFrom, $dateTo, $capital);
							$dateFrom = null;
							$dateTo = null;
						}
					} else {
						break;
					}
				}

				if(!isset($dateTo) && isset($dateFrom))
					$this->company->addCompanyOwner($text, $dateFrom, 'today', $capital);
			}
		}
	}

	protected function getVollzuege()
	{
		foreach ($this->xml->xpath('//VOLLZ') as $child) 
			array_push($this->companyVollzuege, (object) array('vnr' => strval($child->VNR), 'date' => strval($child->VOLLZUGSDATUM)));
	}

	protected function getPersons()
	{
		foreach ($this->xml->xpath('//PER') as $children) 
		{
			foreach ($children as $child) 
			{
				if ($child->getName() == "PE_DKZ02" && strval($child['AUFRECHT']) == "true") 
				{
					if(isset($child->PE_NAME->NACHNAME))
					{
						$this->companyPersons[strval($children['PNR'])] = strval($child->PE_NAME->NACHNAME);
					} else {
						$this->companyPersons[strval($children['PNR'])] = strval($child->PE_NAME->NAME);

					}
				}
			}
		}
	}

	protected function getDateFromVnr($vnr)
	{
		foreach ($this->companyVollzuege as $vollzug) 
			if($vnr == strval($vollzug->vnr))
				return strval($vollzug->date);
	}

	protected function getRechtstatsachen()
	{
		foreach ($this->xml->xpath('//RECHTSTATSACHE') as $child) 
		{
			$text = '';
			foreach ($child->xpath('.//TEXT') as $textPiece) 
				$text = $text.' '.$textPiece;
			
			array_push($this->companyRechtstatsachen, (object) array('vnr' => strval($child['VNR']), 'text' => strval($text)));
		}
	}

	protected function getCompanyEndDate()
	{
		$companyEndDate = 'today';
		foreach ($this->companyRechtstatsachen as $rechtstatsache) 
		{
			if(strpos($rechtstatsache->text, 'KONKURS eröffnet') != false || strpos($rechtstatsache->text, 'Insolvenzverfahrens') != false)
			{
				$companyEndDate = self::getDateFromVnr($rechtstatsache->vnr);
			} else if(strpos($rechtstatsache->text, 'Sanierungsplan ist rechtskräftig') != false) {
				$companyEndDate = 'today';
			} else if(strpos($rechtstatsache->text, 'Amtswegige Löschung') != false && $companyEndDate == 'today') {
				$companyEndDate = self::getDateFromVnr($rechtstatsache->vnr);
			}
		}
		
		$this->company->replaceToday($companyEndDate);
		return $companyEndDate;
	}
}

?>