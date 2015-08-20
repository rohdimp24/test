<?php
   class DisplayJunctionsData
   {
	   private $junctionString;
	   private $totalDistance;
	   	   
	   public function __construct($junctionString,$totalDistance)
	   {
			$this->junctionString=$junctionString;
			$this->totalDistance=$totalDistance;			
	   }
	   
	   	
	   public function getJunctionString()
	   {
		   return $this->junctionString;		   
	   }
		
	   public function getTotalRouteDistance()
	   {
		   return $this->totalDistance;		   
	   }

		
   }

?>